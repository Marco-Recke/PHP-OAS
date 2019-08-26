<?php

/*! we will throw this when errors occur */
class DatabaseException extends Exception {}

/*! a helper wrapper for the PDO database interface */
class DatabaseInterface {
	/*! prepared queries */
	var $queries = array();
	/*! cached ID values for certain objects */
	var $cache = array();
	/*! maximum number of cache slots */
	var $max_slots = 0;
	/*! the PDO database connection handle */
	var $dbc = false;
	/*! if true, we will avoid to modify the database */
	var $noop=false;

	/*!
		set up new instance

		\param $config database configuration
		\param $logger name of a logger callback function
		\param $noop if true, we will avoid modifying the database
	*/
	function __construct($config, $id, $logger=false, $noop=false) {
		$this->config = $config;
		$this->logger = $logger;
		$this->noop = $noop;

		$this->dbc = new PDO($config['dsn'], $config['user'], $config['password']);
		// set timeout to one day for robustness
		$this->dbc->exec("SET wait_timeout=".(24*60*60));

		$P = $this->createPrefix($id);

		// prepare queries, so we do not need to parse them again when we use them
		$this->queries = array(
			"last_id" =>
				$this->dbc->prepare("SELECT LAST_INSERT_ID()"),
			"action_by_action" =>
				$this->dbc->prepare("SELECT * FROM {$P}Actions where action=?"),
			"action_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Actions (action) VALUES (?)"),
			"aggregate_insert_day" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData_day
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					SELECT identifierid, date, servicetypeid, classificationid, counter, robots
					FROM {$P}UsageData
					WHERE date>= ? AND date<=?"),
			"aggregate_insert_week" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData_week
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					SELECT identifierid, DATE_SUB(date,INTERVAL WEEKDAY(date) DAY) as week, servicetypeid, classificationid, SUM(counter) as counter, SUM(robots) as robots
					FROM {$P}UsageData
					WHERE date>= ? AND date<=?
					GROUP BY identifierid,week,servicetypeid,classificationid"),
			"aggregate_insert_month" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData_month
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					SELECT identifierid, DATE_FORMAT(date, '%Y-%m-01') as month, servicetypeid, classificationid, SUM(counter) as counter, SUM(robots) as robots
					FROM {$P}UsageData
					WHERE date>= ? AND date<=?
					GROUP BY identifierid,month,servicetypeid,classificationid"),
			"aggregate_insert_year" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData_year
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					SELECT identifierid, DATE_FORMAT(date, '%Y-01-01') as year, servicetypeid, classificationid, SUM(counter) as counter, SUM(robots) as robots
					FROM {$P}UsageData_month
					WHERE date>= ? AND date<=?
					GROUP BY identifierid,year,servicetypeid,classificationid"),
			"aggregate_insert_total" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData_total
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					SELECT identifierid, min(date) as date , servicetypeid, classificationid, SUM(counter) as counter, SUM(robots) as robots
					FROM {$P}UsageData_day
					GROUP BY identifierid,servicetypeid,classificationid"),
			"aggregate_delete_by_dates_day" =>
				$this->dbc->prepare("DELETE FROM {$P}UsageData_day
						WHERE date>= ? AND date<=?"),
			"aggregate_delete_by_dates_week" =>
				$this->dbc->prepare("DELETE FROM {$P}UsageData_week
						WHERE date>= ? AND date<=?"),
			"aggregate_delete_by_dates_month" =>
				$this->dbc->prepare("DELETE FROM {$P}UsageData_month
						WHERE date>= ? AND date<=?"),
			"aggregate_delete_by_dates_year" =>
				$this->dbc->prepare("DELETE FROM {$P}UsageData_year
						WHERE date>= ? AND date<=?"),
			"aggregate_delete_total" =>
				$this->dbc->prepare("TRUNCATE TABLE {$P}UsageData_total"),
			"classification_by_classification" =>
				$this->dbc->prepare("SELECT * FROM {$P}Classification where classification=?"),
			"classification_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Classification (classification) VALUES (?)"),
			"dataprovider_get" =>
				$this->dbc->prepare("SELECT * FROM DataProvider WHERE id=?"),
			"dataprovider_get_by_url" =>
				$this->dbc->prepare("SELECT id FROM DataProvider WHERE baseurl=?"),
			"dataprovider_get_by_baseurl" =>
				$this->dbc->prepare("SELECT id FROM DataProvider WHERE baseurl=?"),
			"dataprovider_get_by_httpuser" =>
				$this->dbc->prepare("SELECT id FROM DataProvider WHERE httpuser=?"),
			"dataprovider_set_youngestdatestamp" =>
				$this->dbc->prepare("UPDATE DataProvider SET youngestdatestamp=? WHERE id=?"),
            "dataprovider_get_youngestdatestamp" =>
				$this->dbc->prepare("SELECT youngestdatestamp FROM DataProvider WHERE id=?"),
			"dataprovider_insert" =>
				$this->dbc->prepare("INSERT INTO DataProvider
					(baseurl, repositoryname, identifydata, metadataprefix, errorpolicy, email, granularity, youngestdatestamp,
                                         created,httpuser,websiteurl,default_identifier)
					VALUES (?,?,?,?,?,?,?,?,
                                                ?,?,?,?)"),
			"dataprovider_delete" =>
				$this->dbc->prepare("DELETE FROM DataProvider WHERE id=?"),
                        "dataprovider_update_flags" =>
                                $this->dbc->prepare("UPDATE DataProvider SET allctxo_parsed=?,
                                        harvest_laststart=?,harvest_lastend=?,harvest_lastlistsize=?,harvest_lastexitcode=?,
                                        parse_laststart=?,parse_lastend=?,parse_lastctxocount=?,parse_lastexitcode=?,
                                        calc_laststart=?,calc_lastend=?,calc_lastexitcode=?, 
                                        aggr_laststart=?, aggr_lastend=?,aggr_lastexitcode=? WHERE id=?"),
                        "dataprovider_update_httpuser" =>
                                $this->dbc->prepare("UPDATE DataProvider SET httpuser=? WHERE id=?"),
                        "dataprovider_update_default_identifier" =>
                                $this->dbc->prepare("UPDATE DataProvider SET default_identifier=? WHERE id=?"),
                        "dataprovider_update_websiteurl" =>
                                $this->dbc->prepare("UPDATE DataProvider SET websiteurl=? WHERE id=?"),
			"delete_all_tables_by_id" =>
				$this->dbc->prepare("DROP TABLE IF EXISTS {$P}Actions,{$P}Classification,{$P}Format,{$P}Harvest,{$P}HarvestCtxO,{$P}HarvestData,{$P}HarvestError,{$P}HarvestRecord,{$P}Identifier,{$P}ParseError,{$P}Service,{$P}ServiceType,{$P}Status,{$P}UsageData,{$P}UsageData_day,{$P}UsageData_week,{$P}UsageData_month,{$P}UsageData_year"),
			"harvest_get" =>
				$this->dbc->prepare("SELECT
					*, (SELECT COUNT(*) FROM {$P}HarvestError AS E WHERE E.harvestid=H.id) AS errors
					FROM {$P}Harvest AS H WHERE H.id=?"),
			"harvests_get_from_starttime" =>
				$this->dbc->prepare("SELECT id FROM {$P}Harvest WHERE timestart >= ?"),
			"harvest_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Harvest
					(timestart, timeend, status, fromparam, untilparam)
					VALUES (?,?,?,?,?)"),
			"harvest_update_status" =>
				$this->dbc->prepare("UPDATE {$P}Harvest SET status=? WHERE id=?"),
			"harvest_update_timeend" =>
				$this->dbc->prepare("UPDATE {$P}Harvest SET timeend=? WHERE id=?"),
			"harvest_cleanup" =>
				$this->dbc->prepare("DELETE FROM {$P}Harvest WHERE status=?"),
			"harvest_delete" =>
				$this->dbc->prepare("DELETE FROM {$P}Harvest WHERE id=?"),

			/* is done dynamically in oai-parser.php in function write_ctxo	*/
			// "harvestctxo_insert" =>
			// 	$this->dbc->prepare("INSERT DELAYED INTO {$P}HarvestCtxO (
			// 		status, harvestid, harvestdataid,
			// 		ctxocount, parsetimestamp, ctxotimestamp, httpstatus,
			// 		reqip, cclass, format, servicetype,
			// 		size, documentsize, useragent, referent,
			// 		referringentity, service, hostname)
			// 		VALUES (?,?,?,?,?,?,?,UNHEX(?),UNHEX(?),?,?,?,?,?,?,?,?,?)"),


			/* not used right now as it is pretty slow */
			// "harvestctxo_update_status" =>
			//  	$this->dbc->prepare("UPDATE {$P}HarvestCtxO SET status=? WHERE ctxotimestamp >= ? AND ctxotimestamp < ? AND referent LIKE ?"),

			"harvestctxo_by_date_and_identifier" =>
				$this->dbc->prepare("SELECT * FROM {$P}HarvestCtxO USE INDEX (ctxotimestamp)
					WHERE ctxotimestamp >= ? AND ctxotimestamp < ? AND (referent LIKE ? OR referent = ?)
					ORDER BY referent, servicetypeid, reqip, useragent, classificationid, ctxotimestamp", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)),
			"harvestctxo_by_servicetype_and_date_and_identifier" =>
				$this->dbc->prepare("SELECT referent FROM {$P}HarvestCtxO WHERE servicetypeid=? AND ctxotimestamp >= ? AND ctxotimestamp < ? AND referent LIKE ? limit 1"),
			"harvestctxo_get_earliest_date" =>
				$this->dbc->prepare("SELECT MIN(ctxotimestamp) FROM {$P}HarvestCtxO"),
			"harvestctxo_delete_by_recordid"=>
				$this->dbc->prepare("DELETE FROM {$P}HarvestCtxO WHERE recordid=?"),
			"harvestctxo_delete_by_timestamp"=>
				$this->dbc->prepare("DELETE FROM {$P}HarvestCtxO WHERE ctxotimestamp<=?"),
	
			/* status not used right now */
			// "harvestctxo_delete_by_timestamp_and_status"=>
			// 	$this->dbc->prepare("DELETE FROM {$P}HarvestCtxO WHERE ctxotimestamp<=? AND status=1"),

			"harvestctxo_optimize"=>
				$this->dbc->prepare("OPTIMIZE TABLE {$P}HarvestCtxO"),
			"harvestdata_count_status" =>
				$this->dbc->prepare("SELECT COUNT(*) FROM {$P}HarvestData WHERE status=?"),
			"harvestdata_findone_status" =>
				$this->dbc->prepare("SELECT id FROM {$P}HarvestData WHERE status=? LIMIT 1"),
			"harvestdata_update_status" =>
				$this->dbc->prepare("UPDATE {$P}HarvestData SET status=? WHERE id=?"),
			"harvestdata_update_statistics" =>
				$this->dbc->prepare("UPDATE {$P}HarvestData SET records=?, ctxos=?, parsetime=? WHERE id=?"),
			"harvestdata_get" =>
				$this->dbc->prepare("SELECT * FROM {$P}HarvestData WHERE id=?"),
			"harvestdata_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}HarvestData
					(time, harvestid, status, parsetime, records, ctxos, data)
					VALUES (?,?,?,?,?,?,?)"),
			"harvestdata_update_status_for_harvestid" =>
				$this->dbc->prepare("UPDATE {$P}HarvestData SET status=? WHERE harvestid=?"),
			"harvestdata_count_by_status" =>
				$this->dbc->prepare("SELECT COUNT(*) FROM {$P}HarvestData WHERE status=?"),
			"harvestdata_delete_by_harvestid" =>
				$this->dbc->prepare("DELETE FROM {$P}HarvestData WHERE harvestid=?"),
			"harvestdata_cleanup" =>
				$this->dbc->prepare("DELETE FROM {$P}HarvestData WHERE status=?"),
			"harvestdata_optimize" =>
				$this->dbc->prepare("OPTIMIZE TABLE {$P}HarvestData"),
			"harvesterror_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}HarvestError
					(harvestid, time, code, info) VALUES (?,?,?,?)"),
			"harvesterror_delete_by_harvestid" =>
				$this->dbc->prepare("DELETE FROM {$P}HarvestError WHERE harvestid=?"),
			"harvesterrors_by_harvestid" =>
				$this->dbc->prepare("SELECT * FROM {$P}HarvestError WHERE harvestid=?"),
			"harvestrecord_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}HarvestRecord
					(harvestid, harvestdataid, ctxocount, recordtimestamp, recordid)
					VALUES (?,?,?,?,?)"),
			"harvestrecord_by_recordid" =>
				$this->dbc->prepare("SELECT * FROM {$P}HarvestRecord WHERE recordid=?"),
			"harvestrecord_delete_by_id" =>
				$this->dbc->prepare("DELETE FROM {$P}HarvestRecord WHERE id=?"),
			"harvestrecord_delete_disconnected" =>
				$this->dbc->prepare("DELETE {$P}HarvestRecord
					FROM {$P}HarvestRecord
					LEFT JOIN {$P}HarvestCtxO ON {$P}HarvestRecord.id={$P}HarvestCtxO.recordid
					WHERE {$P}HarvestCtxO.recordid IS NULL"),
			"harvestrecord_optimize" =>
				$this->dbc->prepare("OPTIMIZE TABLE {$P}HarvestRecord"),
			"identifier_by_identifier" =>
				$this->dbc->prepare("SELECT id FROM {$P}Identifier WHERE identifier=?"),
			"identifier_like_identifier" =>
				$this->dbc->prepare("SELECT * FROM {$P}Identifier WHERE identifier LIKE ? LIMIT 1"),
			"identifier_by_id" =>
				$this->dbc->prepare("SELECT identifier FROM {$P}Identifier WHERE id=?"),
			"identifier_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Identifier (identifier) VALUES (?)"),
			"format_by_format" =>
				$this->dbc->prepare("SELECT id FROM {$P}Format WHERE format=?"),
			"format_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Format (format) VALUES (?)"),
			"parseerror_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}ParseError
					(time, harvestid, harvestdataid, errormessage)
					VALUES (?,?,?,?)"),
			"robots_by_useragent" =>
				$this->dbc->prepare("SELECT id FROM Robots WHERE useragent=?"),
			"robots_insert" =>
				$this->dbc->prepare("INSERT IGNORE INTO Robots_unreleased
					(useragent,source,comment) VALUES (?,?,?)"),
			"robots_delete_by_id" =>
				$this->dbc->prepare("DELETE FROM Robots_unreleased WHERE id=?"),
			"robots_delete_by_source" =>
				$this->dbc->prepare("DELETE FROM Robots_unreleased WHERE source=?"),
			"robots_insert_from_unreleased" =>
				$this->dbc->prepare("INSERT INTO Robots SELECT * FROM Robots_unreleased;"),
			"robotssource_insert" =>
				$this->dbc->prepare("INSERT INTO RobotsSource
					(name) VALUES (?)"),
			"robotssource_by_name" =>
				$this->dbc->prepare("SELECT id FROM RobotsSource WHERE name=?"),
			// "robotssource_delete_by_id" =>
			// 	$this->dbc->prepare("DELETE FROM RobotsSource WHERE id=?"),
			"robotslists_get_current_version" =>
				$this->dbc->prepare("SELECT MAX(version) FROM RobotsLists"),
			"robotslists_get_table_by_version" =>
				$this->dbc->prepare("SELECT tablename FROM RobotsLists WHERE version = ?"),
			"robotslists_update_by_version" =>
				$this->dbc->prepare("UPDATE RobotsLists SET tablename = ? WHERE version = ?"),
			"robotslists_insert" =>
				$this->dbc->prepare("INSERT INTO RobotsLists (version, tablename) VALUES (?,?)"),
			"robotstxtaccess_insert_or_update" =>
				$this->dbc->prepare("INSERT INTO RobotstxtAccess
			 		(useragent,count) VALUES (?,1) ON DUPLICATE KEY UPDATE count=count+1"),
			"robotstxtaccess_by_useragent" =>
				$this->dbc->prepare("SELECT id FROM RobotstxtAccess WHERE useragent=?"),
			"robothits_by_useragent" =>
				$this->dbc->prepare("SELECT * FROM RobotHits WHERE useragent=?"),
			"robothits_insert" =>
				$this->dbc->prepare("INSERT INTO RobotHits (useragent,robot,lastdate,count) VALUES (?,?,?,0)"),
			"robothits_add" =>
				$this->dbc->prepare("UPDATE RobotHits SET count = count+?, lastdate = IF(lastdate < ?, ?,lastdate) WHERE useragent = ?"),
			"service_by_service" =>
				$this->dbc->prepare("SELECT id FROM {$P}Service WHERE service=?"),
			"service_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Service (service) VALUES (?)"),
			"servicetype_by_servicetype" =>
				$this->dbc->prepare("SELECT id FROM {$P}ServiceType WHERE servicetype=?"),
			"servicetype_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}ServiceType (servicetype) VALUES (?)"),
			"usagedata_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}UsageData
					(identifierid, date, servicetypeid, classificationid, counter, robots)
					VALUES (?,?,?,?,?,?)"),
			"usagedata_delete_by_identifier_and_date" =>
				$this->dbc->prepare("DELETE {$P}UsageData
					FROM {$P}Identifier LEFT JOIN {$P}UsageData
					ON {$P}Identifier.id = {$P}UsageData.identifierid
					WHERE {$P}Identifier.identifier LIKE ? AND {$P}UsageData.date = ?"),
			"usagedata_get_earliest_date" =>
				$this->dbc->prepare("SELECT MIN(date) FROM {$P}UsageData"),
			"status_insert" =>
				$this->dbc->prepare("INSERT INTO {$P}Status
					(time, `from`, until, action)
					VALUES (?,?,?,?)"),

			// UserAgent Table, not currently used
			// "useragent_insert" =>
			// 	$this->dbc->prepare("INSERT INTO {$P}UserAgents
			// 		(useragent)
			// 		VALUES (?)"),
			// "useragent_by_useragent" =>
			// 	$this->dbc->prepare("SELECT id FROM {$P}UserAgents WHERE useragent=?"),
			// 	"useragent_by_id" =>
			// 	$this->dbc->prepare("SELECT useragent FORM {$P}UserAgents WHERE id=?"),
		);

		// this is an usage-based caching mechanism:
		$this->max_slots = 4096; // 4096 slots for each cache
		$this->cache = array(
			"classification" => array(),
			"format" => array(),
			"service" => array(),
			"identifier" => array(),
			"servicetype" => array());
	}

	/*!
		helper function that fetches one single row as an associative array

		\param $queryname the name of the prepared query
		\param $data the data handed over to the prepared query
		\return the values as associative array or false if nothing was found
	*/
	function _fetchValues($queryname, $data=array()) {
		$return = false;
		try {
			if($this->queries[$queryname]->execute($data)) {
				$return = $this->queries[$queryname]->fetch(PDO::FETCH_ASSOC);
				$this->queries[$queryname]->closeCursor();
			}
		} catch(PDOException $e) {
			print_r($this->queries[$queryname]->errorInfo());
			throw $e;
		}
		return $return;
	}

	/*!
		helper function that executes a prepared query, not getting any results

		\param $queryname the name of the prepared query
		\param $data the data handed over to the prepared query
	*/
	function _exec($queryname, $data=array()) {
		try {
			$this->queries[$queryname]->execute($data);
		} catch(PDOException $e) {
			print_r($this->queries[$queryname]->errorInfo());
			throw $e;
		}
	}

	/*!
		fetch a single returned value (row 0, column 0)

		\param $queryname the name of the prepared query
		\param $data the data handed over to the prepared query
		\return the value or false if nothing was found
	*/
	function _fetchValue($queryname, $data=array()) {
		$return = false;
		try {
			if($this->queries[$queryname]->execute($data)) {
				if(!$this->queries[$queryname]->rowCount())
					return false; // fail if we did not get a value
				$return = $this->queries[$queryname]->fetchColumn();
				$this->queries[$queryname]->closeCursor();
			}
		} catch(PDOException $e) {
			print_r($this->queries[$queryname]->errorInfo());
			throw $e;
		}
		return $return;
	}

	/*!
		lookup a value in cache, or, if not cached, in database

		this will create a new database entry if none exists yet.

		\param $type name of the cache area we want to look into
		\param $data value to look up identifier for
		\return identifier
	*/
	function _lookup($type, $data) {
		if(isset($this->cache[$type]) && isset($this->cache[$type][$data])) {
			$this->_log("cache hit for type $type and data <$data>", 40);
			$this->cache[$type][$data][1]++; // handle overflow?
			return $this->cache[$type][$data][0];
		} else {
			$this->_log("no cache hit for type $type and data <$data>", 45);
		}
		$id = $this->_fetchValue($type."_by_".$type, array($data));
		if(!$id) {
			$this->_exec($type."_insert", array($data));
			$id = $this->_fetchValue("last_id");
		}
		if(isset($this->cache[$type])) {
			if(count($this->cache[$type]) >= $this->max_slots) {
				$this->_log("purging cache <$type>", 50);
				// clean cache
				$lowest_ucount = 0x7FFFFFFF;
				foreach($this->cache[$type] as $v) {
					if($v[1] < $lowest_ucount) $lowest_ucount=$v[1];
				}
				foreach(array_keys($this->cache[$type]) as $k) {
					if($this->cache[$type][$k][1] <= $lowest_ucount)
						unset($this->cache[$type][$k]);
				}
			}
			$this->cache[$type][$data]=array($id, 0);
		}
		return $id;
	}

	/*!
		delete a dataprovider from the database

		will make sure that all dependent data is removed, too

		\param $id the ID of the dataprovider to delete
	*/
	function deleteDataprovider($id) {
		if($this->noop) return;
		$this->_exec("delete_all_tables_by_id", array($id));
		$this->_exec("dataprovider_delete", array($id));
	}

	/*!
		delete harvest from the database

		also deletes corresponding replies and errors
		but will NOT delete context objects that belong
		to the harvest. Those are considered primary
		data instead which should be archived and deleted
		on a time-based schedule, if ever.

		\param $id ID of the harvest to delete
	*/
	function deleteHarvest($id) {
		if($this->noop) return;
		$this->_exec("harvestdata_delete_by_harvestid", array($id));
		$this->_exec("harvesterror_delete_by_harvestid", array($id));
		$this->_exec("harvest_delete", array($id));
	}

	/*!
		delete a dataprovider from the database

		will make sure that all dependent data is removed, too

		\param $url BaseURL of the dataprovider to delete
	*/
	function deleteDataproviderURL($url) {
		if(!($dataproviderid=$this->_fetchValue("dataprovider_get_by_url", array($url)))) {
			throw new DataUtilsException("no DataProvider present for BaseURL <$url>");
		}
		$this->deleteDataprovider($dataproviderid);
	}

	/*!
		helper function for deleting a record from the database, also deleting
		corresponding context objects

		\param $id identifier of the record to delete
	*/
	function deleteRecord($id) {
		if($this->noop) return;
		$this->_exec("harvestctxo_delete_by_recordid", array($id));
		$this->_exec("harvestrecord_delete_by_id", array($id));
	}

	/*!
		creates the prefix

		\param $id identifier of the dataprovider
		\return the prefix
	*/
	function createPrefix($id)
	{
		if ($id!=0)
			return $id . '_';
		else
			return '';
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
