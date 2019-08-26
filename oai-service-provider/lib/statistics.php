<?php
/*
+----------------------------------------------------+
| Standard of COUNTER Usage Statistics               |
+------------------------+---------------------------+
| Provider               | COUNTER                   |
+------------------------+---------------------------+
| Counting Clause        | HTTP Status Code is 200   |
|                        | or 304                    |
+------------------------+---------------------------+
| Multi-Click Time Span  | for HTML 10s; for PDF 30s |
|                        | Keep only the last event! |
+------------------------+---------------------------+
| User Identification    | at least IP, preferrably  |
|                        | Session                   |
+------------------------+---------------------------+
| Crawler Clause         | robots, prefetches,       |
|                        | caching, federated        |
|                        | searches (n/a)            |
+------------------------+---------------------------+
| Crawler Identification | Black-List, client HTTP   |
|                        | header                    |
+------------------------+---------------------------+
| Crawler Count          | seperate report           |
+------------------------+---------------------------+
*/

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/robotinterface.php');



/*!
	implementation of the counting algorithms
*/
class Statistics {
	/*! callback name for a logger function */
	var $logger=false;
	/*! no operation flag, will not modify database if set to true */
	var $noop=false;
	/*! a callback function name used for checking whether we should abort harvesting */
	var $abort_callback=false;

	/*! will hold a list of regular expressions for matching crawlers/robots */
	var $blacklist=array();

	/*! first day to analyse (string like "2012-05-31") */
	var $from=false;
	/*! last day to analyse (string like "2012-06-02") */
	var $until=false;
	/*! MySQL compatible (LIKE operator) string for filtering identifiers */
	var $filter=false;

	/*! state information when running: the day currently examined */
	var $day = false;
	/*! state information: identifier ID */
	var $identifier = false;
	/*! state information: servicetype ID */
	var $servicetype = false;
	/*! state information: classification ID */
	var $classification = false;
	/*! state information: the actual crawler */
	var $crawler = false;

	/*! state information current count (COUNTER compatible) */
	var $count_counter = 0;
	/*! state information: robot access count */
	var $count_robots = 0;

	/*! fast cache: last useragent determined to be a crawler */
	var $last_crawler_useragent = false;
	/*! fast cache: last useragent determined to be no crawler */
	var $last_no_crawler_useragent = false;

	/*! the id of the robots.txt identifier*/
	var $robots_txt_identifier = false;

	/*! allows to trace counting, will look up data and is very costly computing-wise */
	var $trace_counting = false;

	/*! calculate day based statistics according to this timezone */
	var $calculation_time_zone = 'UTC';

	/*!
		prepares database connection and instance variables

		\param $db database interface
		\param $dpmanager a dataprovider object
		\param $logger a callback function name for a logging function
		\param $noop if true, we won't modify the database
		\param $abort_callback is the name of a function that is called to determine if we should quit
		\param $trace_counting when true, verbose information is given about the counting process. will slow everything down!
		\param $robotlist_version sets the robotlistversion which is used for calculating
		\param $date_check if set do check if harvestCtxO exists for the given time, so do not overwrite usage data with possible empty data
	*/
	function __construct($db, $dpmanager, $logger=false, $noop=false, $abort_callback=false, $trace_counting=false, $robotlist_version='current', $date_check=true) {
		$this->db=$db;
		$this->dpmanager=$dpmanager;
		$this->logger=$logger;
		$this->noop=$noop;
		$this->abort_callback=$abort_callback;
		$this->trace_counting=$trace_counting;
		$this->date_check = $date_check;

		$this->calculation_time_zone = new DateTimeZone($this->calculation_time_zone);

		$rob = new RobotInterface($db, 'logger');
		$robot_table = $rob->getRobotsTable($robotlist_version);
		$this->_log("Robot List " .$robotlist_version. " is used.",15);

		foreach($db->dbc->query("SELECT useragent FROM ".$robot_table, PDO::FETCH_COLUMN, 0) as $entry) {
			$this->blacklist[] = $entry;
		}
	}

	/*!
		check current context object data for being a crawler access

		\param $data holds an associative array containing context object information
		\return true if found to be a crawler access, false otherwise
	*/
	private function check_crawler($data) {
	    // check if we just considered this a crawler
	    if($this->last_crawler_useragent === $data['useragent']) {
	        return $this->crawler;
	    }
	    // check if we just considered this not a crawler
	    if($this->last_no_crawler_useragent === $data['useragent']) {
	        return false;
	    }

	    foreach($this->blacklist as $crawler) {
           if(preg_match("/$crawler/i", $data['useragent'])) {
                $this->last_crawler_useragent = $data['useragent'];
                return $this->crawler = $crawler;
            }
	    }
        $this->last_no_crawler_useragent = $data['useragent'];
        return false;
    }

	/*!
		write usage data to database

		will read values from state information stored in the current instance
	*/
	private function write_usage_data() {
		// don't write if we don't have counted any accesses
		if($this->count_counter == 0 && $this->count_robots == 0)
			return;
		// otherwise, write!
		if(!$this->noop)
			$this->db->_exec("usagedata_insert", array(
				$this->identifier, $this->day, $this->servicetype,
				$this->classification, $this->count_counter, $this->count_robots));
	}

	/*!
		write robot hits to database from array with all hits on the specific day
	*/
	private function write_robot_data($robotHits) {
		foreach ($robotHits as $robot => $robotHit) {
			if (!$this->db->_fetchValue('robothits_by_useragent', array($robot))) {
				$crawlerid = $this->db->_fetchValue('robots_by_useragent', array($robotHit['crawler']));
				$this->db->_exec("robothits_insert", array($robot,$crawlerid,$this->day));
			}
			$this->db->_exec("robothits_add", array($robotHit['count'], $this->day, $this->day, $robot));
		}
	}

	/*!
		get robots.txt identifier/path
		The search for the robots.txt identifier is quite slow because it uses a seach query with an % at the beginning.
		It is done upfront to not slow down the actual calculate process.
	*/
	private function check_robotstxt_identifier() {
		// look up the robots.txt path in the Identifier table
		$this->_log("looking up the robots.txt path in Identifier table...", 30);
		if ($robots_txt = $this->db->_fetchValues("identifier_like_identifier",array("%robots.txt")))
			$this->_log("...found at " . $robots_txt['identifier'], 30);
			return $robots_txt;
		// if not in identifier table yet, try to parse it from context-objects for the given dates
		$this->_log("...not found. Looking up the robots.txt path in HarvestCtxO table for the given timeframe...", 30);
		if ($servicetypeid = $this->db->_fetchValue("servicetype_by_servicetype", array('any'))) {
			// we have to convert the dates
			$from = new DateTime($this->from, $this->calculation_time_zone);
			$from->sub(new DateInterval('PT30S')); // start looking at 23:59:30 (local)
			$until = new DateTime($this->until, $this->calculation_time_zone);
			$until->add(new DateInterval('P1D'));

	 		if ($robots_txt_identifier = $this->db->_fetchValue("harvestctxo_by_servicetype_and_date_and_identifier",array($servicetypeid,$from->format('Y-m-d H:i:s'), $until->format('Y-m-d H:i:s'),"%robots.txt"))) {
	 			// and if found write it to identifier table for faster access in the future
	 			$this->db->_exec("identifier_insert",array($robots_txt_identifier));
	 			$this->_log("...found. It is written to the Identifier table for faster processing on the next calculate.", 30);
	 			return $this->db->_fetchValues("identifier_like_identifier",array("%robots.txt"));
	 		}
		}
		$this->_log("...no robots.txt path found.", 30);
		return false;
	}


	// Not used at the moment!
	// /*!
	// 	writing the relevant informations of the last calculate
	//  */
	// private function write_calculate_status($from, $until)
	// {
	// 	$now = date(DATE_ATOM,time());
	// 	$action = $this->db->_lookup("action", 'calculate');
	// 	$this->_log("writing calculate status");
	// 	$this->db->_exec("status_insert",array($now,$from->format('Y-m-d'),$until->format('Y-m-d'),$action));
	// }

	/*!
		remove old data when we cover a certain timespan / filter again

		will read needed data from state information stored in this instance
	*/
	private function clear_data() {
		if(!$this->noop)
			$this->db->_exec("usagedata_delete_by_identifier_and_date",
				array($this->filter ? $this->filter : '%', $this->day));
	}

	/*!
		helper for tracing the counting

		\param $ctxo associative array with data from HarvestCtxO table
		\param $countevent message to report about counting
	*/
	private function log_trace($ctxo, $countevent) {
		if(!$this->trace_counting) return;
		$this->_log(
			"<".$ctxo["referent"]."> (ST:".$ctxo['servicetypeid'].
			"): $countevent @".$ctxo['ctxotimestamp'].
			", status=".$ctxo['httpstatus'].
			", hostname='".$ctxo['hostname'].
			"', useragent='".$ctxo['useragent']."'", 15);
	}

	/*!
		run the statistics counting process

		will call run_day() for each day within the configured time span
	*/
	function run() {
		// initialize state
		$this->day = false;
		$this->identifier = false;
		$this->servicetype = false;
		$this->classification = false;

		if(!$this->from) {
			$this->from = $this->db->_fetchValue("harvestctxo_get_earliest_date");
			if(!$this->from) {
				$this->_log("error finding earliest context object timestamp (database empty?).", 5);
				return;
			}
		}
		$from = new DateTime($this->from);
		// $start_date = clone $from;
		if(!$this->until) {
			// choose yesterday as default
			$until = new DateTime();
			$until->setTimezone($this->calculation_time_zone);
			$until->sub(new DateInterval('P1D'));
			$this->until = $until->format('Y-m-d');
		}
		$until = new DateTime($this->until);

		// fetches the id of the robots.txt identifier if existing
		$this->robots_txt_identifier = $this->check_robotstxt_identifier();

		$this->dpmanager->start_calculate();
		try {
			while(strcmp($from->format('Y-m-d'), $until->format('Y-m-d')) <= 0) {
				$this->day = $from->format('Y-m-d');
				$this->_log("running statistics for ".$this->day, 10);
				$this->run_day();
				$from->add(new DateInterval('P1D'));
			}
		} catch (Exception $e) {
			$this->dpmanager->stop_calculate(OAS_SP_CALCULATE_STATUS_ERROR);
		}
		$this->dpmanager->stop_calculate();
		// $this->write_calculate_status($start_date,$until);
	}

	/*!
		does the actual counting, looking only at a single day for each invocation

		in order to change the actual counting mechanism, edit this method.
	*/
	private function run_day() {
		if($this->abort_callback && call_user_func($this->abort_callback)) {
			// we were killed in the meantime, so abort
			throw new Exception('aborted by signal or user request');
		}

		$perf_start = new DateTime();
		$this->_log("selecting and sorting context objects", 15);

		/*************************************************************
			set up the time span we are looking at:
			all is based on $this->day, which will contain the date
			as a string value, e.g. '2012-05-31'
		*/

		/*
			$start_counting is the time we use to decide to actually count
			accesses. When the access time stamp is at least (>=) this,
			it might be counted if it qualifies by the counting rules
		*/
		$start_counting = new DateTime($this->day, $this->calculation_time_zone);
		/*
			$from is the timestamp we tell the database to use as the
			date from which on context objects are examined.
			This is currently 30 seconds before $start_counting,
			since we need to look back in time at least 30 seconds
			to effectively implement the COUNTER multiclick time span.
		*/
		$from = new DateTime($this->day, $this->calculation_time_zone);
		$from->sub(new DateInterval('PT30S')); // start looking at 23:59:30 (local)
		/*
			$until is the timestamp up to which (<) we will tell the
			database to return context object information for. This
			is the beginning of the next day following $this->day
		*/
		$until = new DateTime($this->day, $this->calculation_time_zone);
		$until->add(new DateInterval('P1D')); // 1 day
		/*
			The database holds the context object data with UTC
			timestamps. However, the timestamps used to declare the
			timespan to look up are considered to be in local time
			(for now). We need to convert those times to UTC,
			which PHP does with the confusingly named method
			DateTimeZone::setTimezone(timezone)
		*/
		$utc_tz = new DateTimeZone('UTC');
		$start_counting->setTimezone($utc_tz);
		$from->setTimezone($utc_tz);
		$until->setTimezone($utc_tz);

		/*
			For computational effect and simplicity, we use unix
			timestamps for the $start_counting value, since it will
			be used in comparisons very often.
		*/
		$start_counting = $start_counting->getTimestamp();

		if (!$this->filter) {
			$this->filter = $this->dpmanager->get_defaultIdentifier();
		}
		$this->_log("Identifier: " . $this->filter, 15);

		/*
			query database for all relevant context objects

			This is quite resource-consuming, however, it is most
			effective to let the database optimize the selection
			and sorting of the data.
		*/
		if(false === $this->db->queries['harvestctxo_by_date_and_identifier']->execute(
				array($from->format('Y-m-d H:i:s'), $until->format('Y-m-d H:i:s'),
						$this->filter,
						$this->robots_txt_identifier['identifier'] ? $this->robots_txt_identifier['identifier'] : ''))) {
			$this->db->queries['harvestctxo_by_date_and_identifier']->debugDumpParams();
			$this->_log("database error.");
			return;
		}

		/*
			do not process further if no data is set and date_check is set to true
		 */
		if ($this->date_check && ($this->db->queries['harvestctxo_by_date_and_identifier']->rowCount() == 0)) {
			$this->_log("no harvestCtxOs for this day, so no data is written.");
			return;
		}

		/*
			clear usage data for this day if there is any
		 */
		$this->clear_data();

		$this->_log("done in ".$perf_start->diff(new DateTime())->format('%a d, %h:%I:%S').", start counting", 15);

		/*
			now we have a sorted table of context objects for the current day
			it is sorted by identifier (referent), reqip+useragent, timestamp
		*/
		$ctxo_count = 0;  // statistics counter for informational purposes
		$is_first = true; // flag used for special behaviour of the loop in the first run

		/*
			local state that we will use to eliminate multi-click accesses
			by keeping track of the values we've seen last
		*/
		$last = array('reqip'=>false, 'useragent'=>false, 'ts'=>false);

		/*
			we will need this value for checking COUNTER multi-click time spans
		*/
		$formatid_pdf = $this->db->_lookup("format", "application/pdf");

		/*
			we save all robot hits including their useragent and crawler-regex for the specific day
			will be written to database after each day
		*/
		$robotHits = array();

		/*
			loop over the query results and do the actual counting
		*/
		while($ctxo = $this->db->queries['harvestctxo_by_date_and_identifier']->fetch(PDO::FETCH_ASSOC)) {
			if($this->abort_callback && call_user_func($this->abort_callback)) {
				$this->_log("Aborting... This may take a moment.");
				// we were killed in the meantime, so abort
				throw new Exception('aborted by signal or user request');

			}

			/*
				for reasons of efficiency, we use IDs for the identifiers here
			*/
			$identifierid = $this->db->_lookup("identifier", $ctxo["referent"]);

			if($is_first) {
				// initialize buffers when we're in the first loop
				$is_first = false;
				// update buffered values to current data
				$this->identifier = $identifierid;
				$this->servicetype = $ctxo['servicetypeid'];
				$this->classification = $ctxo['classificationid'];
				// reset count
				$this->count_counter = 0;
				$this->count_robots = 0;

			} elseif($this->identifier != $identifierid
				|| $this->servicetype != $ctxo['servicetypeid']
				|| $this->classification != $ctxo['classificationid']) {

				// change w/ regard to buffered values:
				// write out counted values
				$this->write_usage_data();
				// update buffered values to current data
				$this->identifier = $identifierid;
				$this->servicetype = $ctxo['servicetypeid'];
				$this->classification = $ctxo['classificationid'];
				// reset count
				$this->count_counter = 0;
				$this->count_robots = 0;

				// reset history
				$last['reqip'] = false; // invalidates history
			}

			// check history
			if($last['reqip'] != $ctxo['reqip'] || $last['useragent'] != $ctxo['useragent']) {
				// and reset buffered datestamp: we have the next
				// user (identified by reqip plus useragent)
				$last['ts']=strtotime('2000-01-01 00:00:00');
				$counter_last_ts = $last['ts'];
				// update history to current data
				$last['reqip'] = $ctxo['reqip'];
				$last['useragent'] = $ctxo['useragent'];
				// note that $last['ts'] will be updated separately and unconditionally
			}

			// check context object timestamp and calculate difference to last
			// watched access
			$ts = strtotime($ctxo['ctxotimestamp']);
			$last_diff = $ts - $last['ts'];
			$last['ts'] = $ts;

			/* start counting only when the time is in the relevant time span */
			if($ts > $start_counting) {
				/* only count non-robot accesses */
				$robot = false;
				$crawler = $this->check_crawler($ctxo);
				if(!$crawler) {
					if (!($ctxo['httpstatus'] == 200 || $ctxo['httpstatus'] == 304)) {
						$this->log_trace($ctxo, "COUNTER ignore: wrong http status: {$ctxo['httpstatus']})");
					}
					else {
						// check context object timestamp and calculate difference to last watched access with correct http status code
						$counter_ts = strtotime($ctxo['ctxotimestamp']);
						$counter_last_diff = $counter_ts - $counter_last_ts;
						$counter_last_ts = $counter_ts;

						$counter_timespan = 10;
						if($ctxo['formatid'] == $formatid_pdf)
							$counter_timespan = 30;

						if ($counter_last_diff > $counter_timespan) {
								$this->count_counter++;
								$this->log_trace($ctxo, "COUNTER +1");
						} else {
							$this->log_trace($ctxo, "COUNTER ignore (timestamp diff: $counter_last_diff, must be >$counter_timespan, http status {$ctxo['httpstatus']})");
						}
					}
				} else {
					// robot accesses are being counted unconditionally
					$this->count_robots++;
					$this->log_trace($ctxo, "ROBOT +1 identified by blacklisted regular expression $crawler");
					// write robot hits data

					/*
					* Not used right now since there is no status written right now, due to performance issues:
					* Only update robot hits table if ctxo was not already calculated
					* // if ($ctxo['status']==0)
					*/

					// count the robot hits
					if (!array_key_exists($ctxo['useragent'], $robotHits)) {
						$robotHits[$ctxo['useragent']]['count'] = 0;
					}
					$robotHits[$ctxo['useragent']]['count']++;
					$robotHits[$ctxo['useragent']]['crawler'] = $crawler;

					$robot = true;
				}

				// writing robots.txt accesses for all non detected robots
				if ($this->robots_txt_identifier && !$robot) {
					if ($this->robots_txt_identifier['id'] == $identifierid) {
						$this->_log("found a robots.txt access from " . $ctxo['useragent'], 30);
						$this->db->_exec("robotstxtaccess_insert_or_update", array($ctxo['useragent']));
					}
				}
			}

			// informational statistics
			$ctxo_count++;
			if($ctxo_count % 10000) {
				$this->_log("read $ctxo_count entries", 30);
			}
		}
		// if we ran the loop at least once, we have to finally write the
		// data from the latest count run, since in all other cases,
		// data is written upon seeing a new identifier, which won't
		// happen at the end of the loop
		if(!$is_first)
			$this->write_usage_data();

		$this->write_robot_data($robotHits);

		/*
			update status on all context objects which are calculated, this is pretty slow and not needed right now.

			// update the status of the context objects
			// $this->_log("updating status of context objects", 10);
			// $this->db->queries['harvestctxo_update_status']->execute(array(1, $from->format('Y-m-d H:i:s'), $until->format('Y-m-d H:i:s'),
			// 	$this->filter ? $this->filter : '%'));
		*/
		// some more statistics
		$this->_log("completed in ".$perf_start->diff(new DateTime())->format('%a d, %h:%I:%S'), 15);
	}

	/*!
		callback logger function

		we use this to log information with various levels

		\param $text the log message
		\param $level the log level (<10: error, <20: warning/important, >20: info)
	*/
	function _log($text,$level=10) {
		if($this->logger) call_user_func($this->logger, $text, $level);
	}

}

?>
