<?php
require_once(dirname(__FILE__).'/datetimehelper.php');
require_once(dirname(__FILE__).'/constants.php');

/**
* Aggregation of Usagedata
*/
class AggregateUsageData{
	/*! prefix for tables */
	var $prefix=false;
	/*! callback name for a logger function */
	var $logger=false;
	/*! no operation flag, will not modify database if set to true */
	var $noop=false;
	/*! the database connection */
	var $db=false;
	/*! is the name of a function that is called to determine if we should quit */
	var $abort_callback=false;
	/*! first day to aggregate (string like "2012-05-31") */
	var $from=false;
	/*! last day to aggregate (string like "2012-06-02") */
	var $until=false;
	/*! list of periods for aggreation */
	var $periods=array('day', 'week', 'month', 'year', 'total');
	/*! use this timezone for determining the default until value (yesterday) */
	var $calculation_time_zone = 'UTC';
    /*! dataprovidermanager */
    var $dpmanager = false;

	/*!
		prepares database connection and instance variables

		\param $db database interface
		\param $dpmanager a dataprovider object
		\param $logger a callback function name for a logging function
		\param $noop if true, we won't modify the database
		\param $abort_callback is the name of a function that is called to determine if we should quit
	*/
	function __construct($db, $dpmanager, $logger=false, $noop=false, $abort_callback=false) {
		$this->logger=$logger;
		$this->noop=$noop;
		$this->db=$db;
        $this->dpmanager = $dpmanager;
		$this->abort_callback=$abort_callback;

		$this->calculation_time_zone = new DateTimeZone($this->calculation_time_zone);

		$this->prefix=$db->createPrefix($dpmanager->get_Id());
		// create rollup tables if not existing
		foreach ($this->periods as $period) {
			$this->db->dbc->exec('
				CREATE TABLE IF NOT EXISTS `'.$this->prefix.'UsageData_'.$period.'` (
					`identifierid` bigint(20) NOT NULL,
				  	`date` date NOT NULL,
				  	`servicetypeid` int(11) NOT NULL,
				  	`classificationid` int(11) NOT NULL,
				  	`counter` int(10) unsigned NOT NULL,
				  	`robots` int(10) unsigned NOT NULL,
				  	PRIMARY KEY (`identifierid`, `date`, `servicetypeid`, `classificationid`) USING BTREE
					) ENGINE=MyISAM CHARSET=utf8');
		}
	}

	public function run()
	{
		// set from date if not set
		if(!$this->from) {
			$this->from = $this->db->_fetchValue("usagedata_get_earliest_date");
			$this->_log("from date for aggregate not set, set to default: earliest date: " . $this->from, 20);
			if(!$this->from) {
				$this->_log("error finding earliest usagedata timestamp (database empty?).", 5);
				return;
			}
		}
		$from = new DateTime($this->from);

		// set until date if not set
		if(!$this->until) {
			// choose yesterday as default
			$until = new DateTime();
			$until->setTimezone($this->calculation_time_zone);
			$this->_log("until date not set, set to default: yesterday", 20);
			$until->sub(new DateInterval('P1D'));
			$until = new DateTime($until->format('Y-m-d'));
		} else {
			$until = new DateTime($this->until);
		}

		// aggregate for each period
		foreach ($this->periods as $period) {
			if($this->abort_callback && call_user_func($this->abort_callback)) {
				// we were killed in the meantime, so abort
				$this->dpmanager->stop_aggregate(OAS_SP_AGGREGATE_STATUS_ERROR);
				throw new Exception('aborted by signal or user request');
			}
			// the start time of the aggregate is saved to database
			$this->dpmanager->start_aggregate();
			// for period 'total' we do not need from and until values as all data is aggregated
			if ($period == 'total') {
				$this->_log("cleaning previously aggregated data for period '$period'", 20);
				$this->db->_exec("aggregate_delete_total");
				$this->_log("aggregating usage data for period '$period'",20);
				$this->db->_exec("aggregate_insert_total");
			} else {
				$startDate 	= clone DateTimeHelper::getStartDateOfPeriod($from,$period);
	        	$endDate 	= clone DateTimeHelper::getEndDateOfPeriod($until,$period);
	        	$this->_log("cleaning previously aggregated data for period '$period' from " .
	        		$startDate->format("Y-m-d") . " until " . $endDate->format("Y-m-d"), 20);
				$this->db->_exec("aggregate_delete_by_dates_".$period,
					array($startDate->format("Y-m-d"), $endDate->format("Y-m-d")));
				$this->_log("aggregating usage data for period '$period' from " .
	        		$startDate->format("Y-m-d") . " until " . $endDate->format("Y-m-d"),20);
				$this->db->_exec("aggregate_insert_".$period,
					array($startDate->format("Y-m-d"), $endDate->format("Y-m-d")));
			}
		}
		// the finished time of the aggregate is saved to database
		$this->dpmanager->stop_aggregate();
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
