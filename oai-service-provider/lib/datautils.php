<?php
/*

   (c) 2012 Hans-Werner Hilse <hilse@sub.uni-goettingen.de>

*/

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/database_interface.php');

/*! we throw this in DataUtils when errors occur */
class DataUtilsException extends Exception {}

/*!
	provides information about database status and entries and offers some generic management funtions
*/
class DataUtils {
	/*! callback name for a logger function */
	var $logger=false;
	/*! no operation flag, will not modify database if set to true */
	var $noop=false;

         /*DATABASE DECLARATIONS */
        var $basic_tabledefinitions=array(

        	'DataProvider'=>'
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `baseurl` text NOT NULL,
    `repositoryname` text,
    `identifydata` longblob,
    `metadataprefix` text NOT NULL,
    `errorpolicy` int(11) DEFAULT NULL,
    `email` text,
    `granularity` tinyint(4) NOT NULL,
    `youngestdatestamp` datetime NOT NULL,
    `created` datetime,
    `httpuser` text,
    `allctxo_parsed` bigint,
    `websiteurl` text,
    `default_identifier` text,
    `harvest_laststart` datetime,
    `harvest_lastend` datetime,
    `harvest_lastexitcode` int(11),
    `harvest_lastlistsize` int(11),
    `parse_laststart` datetime,
    `parse_lastend` datetime,
    `parse_lastexitcode` int(11),
    `parse_lastctxocount` int(11),
    `calc_laststart` datetime,
    `calc_lastend` datetime,
    `calc_lastexitcode` int(11),
    `aggr_laststart` datetime,
    `aggr_lastend` datetime,
    `aggr_lastexitcode` int(11),
    PRIMARY KEY (`id`)
',

			'Robots' => '
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`useragent` varchar(255) COLLATE utf8_bin NOT NULL,
	`source` int(10) DEFAULT NULL,
	`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`comment` varchar(255) COLLATE utf8_bin DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_useragent` (`useragent`)
',
			'Robots_unreleased' => '
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`useragent` varchar(255) COLLATE utf8_bin NOT NULL,
	`source` int(10) DEFAULT NULL,
	`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`comment` varchar(255) COLLATE utf8_bin DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_useragent` (`useragent`)
',
			'RobotsLists' => '
	`version` int(10) NOT NULL,
	`tablename` varchar(255) COLLATE utf8_bin NOT NULL,
	`released` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`version`)

',

			'RobotsSource' => '
	  `id` int(10) NOT NULL AUTO_INCREMENT,
	  `name` varchar(255) COLLATE utf8_bin NOT NULL,
	  `comment` text COLLATE utf8_bin,
	  PRIMARY KEY (`id`)
',
			'RobotHits' => '
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`useragent` blob NOT NULL,
	`robot` varchar(255) COLLATE utf8_bin NOT NULL,
	`lastdate` date NOT NULL,
	`count` int(10) NOT NULL,
	PRIMARY KEY (`id`,`useragent`(128))
',
			'RobotstxtAccess'=>'
 	`id` int(11) NOT NULL AUTO_INCREMENT,
  	`useragent` varchar(255) COLLATE utf8_bin NOT NULL,
  	`count` int(11) NOT NULL,
  	PRIMARY KEY (`id`),
  	UNIQUE KEY `idx_useragent` (`useragent`)
'
);


	/*!
		prepares database connection and instance variables

		\param $db database interface
		\param $id dataprovider id
		\param $logger name of a logger callback function
		\param $noop if true, we will avoid modifying the database
	*/
	function __construct($db, $logger=false, $noop=false) {
		$this->logger=$logger;
		$this->noop=$noop;
		$this->db=$db;
	}

	/*!
		output information about known data providers
	*/
	function infoDataProviderAll() {
		echo "DataProvider overview:\n";
		$provider_q=$this->db->dbc->query('SELECT id FROM DataProvider');
		while($provider = $provider_q->fetchColumn()) {
			$this->infoDataProvider($provider, false);
		}
	}

	/*!
		output information about a dataprovider

		\param $id the ID of the dataprovider to show info for
		\param $header if true, an individual header will be printed
	*/
	function infoDataProvider($id, $header=true) {
		if($header) {
			echo "DataProvider overview:\n";
		}
		echo "-------------------------------------------------------\n";
		$provider=$this->db->_fetchValues("dataprovider_get", array($id));
		if(!$provider) throw new DataUtilsException("Can't find DataProvider #$id");

		echo 'ID:                    '.$provider['id']."\n";
		echo 'URL:                   '.$provider['baseurl']."\n";
		echo 'Name:                  '.$provider['repositoryname']."\n";
		echo 'MetadataPrefix OAS:    '.$provider['metadataprefix']."\n";
		echo 'E-Mail:                '.$provider['email']."\n";
		echo 'Granularity:           '.
			($provider['granularity']==OAIPMH2_GRANULARITY_DAYS ? "days" : "seconds")."\n";
		echo 'Youngest Datestamp:    '.$provider['youngestdatestamp']."\n";
		$ds = new DateTime($provider['youngestdatestamp']);
		$diff = $ds->diff(new DateTime());
		echo 'Time since datestamp:  '.$diff->format('%a days, %h hours, %i minutes, %s seconds')."\n";
		echo 'Seconds since then:    '.(time() - $ds->getTimestamp())."\n";
	}

	/*!
		output information about a harvest

		this will also output any harvest errors if there are some
		in the database

		\param $id the ID of the harvest to show info for
		\param $header if true, an individual header will be printed
	*/
	function infoHarvest($id, $header=true) {
		if($header) {
			echo "Harvest details:\n";
		}
		$harvest=$this->db->_fetchValues("harvest_get", array($id));
		if(!$harvest) throw new DataUtilsException("Can't find Harvest #$id");
		echo "-------------------------------------------------------\n";
		echo 'ID:                        '.$harvest['id']."\n";
		echo 'Status:                    '.(
			($harvest['status'] == OAS_SP_HARVEST_STATUS_RUNNING) ? 'running' : (
			($harvest['status'] == OAS_SP_HARVEST_STATUS_DONE_OK) ? 'done (OK)' : (
			($harvest['status'] == OAS_SP_HARVEST_STATUS_DONE_ERROR) ? 'aborted (Error)': ''
			)))." (".$harvest['status'].")\n";
		echo 'Harvest started:           '.$harvest['timestart']."\n";
		echo 'Harvest ended:             '.$harvest['timeend']."\n";
		echo 'OAI "from" parameter was:  '.$harvest['fromparam']."\n";
		echo 'OAI "until" parameter was: '.$harvest['untilparam']."\n";
		echo 'Recorded errors:           '.$harvest['errors']."\n";
		if((int)$harvest['errors'] > 0) {
			$this->db->queries["harvesterrors_by_harvestid"]->execute(array($harvest['id']));
			while($error = $this->db->queries['harvesterrors_by_harvestid']->fetch(PDO::FETCH_ASSOC)) {
				echo "\n";
				echo 'Error ID:                  '.$error['id']."\n";
				echo 'Timestamp:                 '.$error['time']."\n";
				echo 'Error code:                '.$error['code']."\n";
				echo 'Error info:                '.$error['info']."\n";
			}
		}
	}

	/*!
		output information about robots
	*/
	function infoRobots($version = 0)
	{
		if ($version == 0) {
			$version = $this->db->_fetchValue("robotslists_get_current_version");
		}
		$robotTable = $this->db->_fetchValue("robotslists_get_table_by_version", array($version));

		echo "Robots overview for Version ".$version.":\n";
		echo "-------------------------------------------------------\n";
		$robots_q=$this->db->dbc->query('SELECT R.id,R.useragent,RS.name,R.updated,R.comment FROM '.$robotTable.' AS R ,RobotsSource AS RS WHERE R.source=RS.id');
		while($robot = $robots_q->fetch(PDO::FETCH_ASSOC)) {
			echo "ID:\t\t".$robot['id']."\n";
			echo "Useragent:\t".$robot['useragent']."\n";
			echo "Source:\t\t".$robot['name']."\n";
			echo "Added:\t\t".$robot['updated']."\n";
			echo "Comment:\t\t".$robot['comment']."\n";
			echo "\n";
		}
	}


	/*!
		output information about robots
	*/
	function infoRobotsLists()
	{
		echo "Robots Lists Version History:\n";
		echo "-------------------------------------------------------\n";
		$robots_q=$this->db->dbc->query('SELECT * FROM RobotsLists');
		while($robot = $robots_q->fetch(PDO::FETCH_ASSOC)) {
			echo "Version:\t\t".$robot['version']."\n";
			echo "Released:\t".$robot['released']."\n";
			echo "\n";
		}
	}

	/*!
		output information about the robots which were used to filter the actual data
	 */
	function infoRobotHits()
	{
		echo "Filtered Robots overview:\n";
		echo "-------------------------------------------------------\n";
		$robothits_q=$this->db->dbc->query('SELECT R.useragent as useragent ,SUM(RH.count) as count,RS.name as name FROM RobotHits AS RH LEFT JOIN Robots as R
		 									ON RH.robot=R.id LEFT JOIN RobotsSource AS RS ON R.source=RS.id GROUP BY useragent ORDER BY count DESC; ');
		while($robothit = $robothits_q->fetch(PDO::FETCH_ASSOC)) {
			echo "Robot:\t\t".$robothit['useragent']."\n";
			echo "Source:\t\t".$robothit['name']."\n";
			echo "Hits:\t\t".$robothit['count']."\n";
			echo "\n";
		}
	}

	/*!
		output information about useragents which are not filtered yet but accessed the robots.txt file
		a robots.txt hit from a useragent does not necessarily mean it is a robot, but it can give a
		hint for further lookup.
	 */
	function infoRobotstxtHits()
	{
		echo "Robots.txt hits from non-filtered user-agents:\n";
		echo "(a robots.txt hit from a useragent does not necessarily mean it is a robot, but it can give ";
		echo "a hint for further lookup, especially if there are numerous hits.)";
		echo "-------------------------------------------------------\n";
		$robotstxthits_q=$this->db->dbc->query('SELECT useragent, count FROM RobotstxtAccess ORDER BY count DESC; ');
		while($robotstxthit = $robotstxthits_q->fetch(PDO::FETCH_ASSOC)) {
			echo "Useragent: ".$robotstxthit['useragent']."\n";
			echo "Hits: ".$robotstxthit['count']."\n";
			echo "\n";
		}
	}

	/*!
		output information about known harvests

		\param $starttime timestamp from when we start to output harvests
	*/
	function listHarvests($starttime=0) {
		$q=false;
		$q=$this->db->queries['harvests_get_from_starttime'];
		$q->execute($d=array(gmdate('Y-m-d\TH:i:s\Z', $starttime)));
		echo "Harvest list:\n";
		while($harvest_id = $q->fetchColumn()) {
			$this->infoHarvest($harvest_id, false);
		}
	}

	/*!
		cleanup old data from HarvestData

		will clean out harvestdata entries which were successfully parsed
		or which are marked as being from an incomplete (but finished)
		harvest.
	*/
	function cleanupHarvestData() {
		if($this->noop) return;
		$this->_log("removing data for complete and parsed harvest replies", 10);
		$this->db->_exec("harvestdata_cleanup", array(OAS_SP_HARVESTDATA_STATUS_DONE));
		$this->_log("removing data for errorneous harvest replies", 10);
		$this->db->_exec("harvestdata_cleanup", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED_ERR));
		$this->_log("optimizing harvestdata table", 10);
		$this->db->_exec("harvestdata_optimize");
	}

	/*!
		cleanup temporary data from HarvestData

		will clean out harvestdata entries for which either the harvest is still marked as running,
		or still marked as currently parsing, or parsing has failed for
	*/
	function cleanupHarvestDataTemp() {
		if($this->noop) return;
		$this->_log("removing data from harvests still marked as running", 10);
		$this->db->_exec("harvest_cleanup", array(OAS_SP_HARVEST_STATUS_RUNNING));
		$this->_log("removing temporary harvest data", 10);
		$this->db->_exec("harvestdata_cleanup", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED_TMP));
		$this->_log("removing data for errorneous harvest replies", 10);
		$this->db->_exec("harvestdata_cleanup", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED_ERR));
		$this->_log("optimizing harvestdata table", 10);
		$this->db->_exec("harvestdata_optimize");
	}

	/* not used because status is not implemented for CtxOs at the moment. see below for function
	   to reuse this, do the following:
	   - use the responding statement in datebase_interface
	   - add a toggle for "delete_all" in the CLI interface (as is service-provider.php)
	   - add the toggle in the call in service-provider.php
	*/
	/*!
		cleanup context object data

		this will delete stored usage information. A datestamp shall be given that
		indicates the date up to which the data is deleted. If delete_all status is set, all
		context objects despite their status will be deleted.
	*/
	// function cleanupCtxOs($from = '-1 month', $delete_all = false) {
	// 	if($this->noop) return;
	// 	$from_ds = date('Y-m-d H:i:s', strtotime($from));
	// 	$this->_log("removing context object data up to ".$from_ds, 10);

	// 	$dbinterfacepostfix = ""
	// 	if(!$delete_all)
	// 		$dbinterfacepostfix = "_and_status";

	// 	$this->db->_exec("harvestctxo_delete_by_timestamp".$dbinterfacepostfix, array($from_ds));

	// 	$this->_log("optimizing context object table", 10);
	// 	$this->db->_exec("harvestctxo_optimize");
	// }

	/*!
		cleanup context object data

		this will delete stored usage information. A datestamp shall be given that
		indicates the date up to which the data is deleted. If delete_all status is set, all
		context objects despite their status will be deleted.
	*/
	function cleanupCtxOs($from = '-1 month') {
		if($this->noop) return;
		$from_ds = date('Y-m-d H:i:s', strtotime($from));
		$this->_log("removing context object data up to ".$from_ds, 10);

		$this->db->_exec("harvestctxo_delete_by_timestamp", array($from_ds));

		$this->_log("optimizing context object table", 10);
		$this->db->_exec("harvestctxo_optimize");
	}

	/*!
		cleanup record information

		this will delete information about OAI PMH records for which there is no context
		object information stored in the database (anymore).
	*/
	function cleanupRecords() {
		if($this->noop) return;
		$this->_log("removing old record information", 10);
		$this->db->_exec("harvestrecord_delete_disconnected");
		$this->_log("optimizing record table", 10);
		$this->db->_exec("harvestrecord_optimize");
	}

	/*!
		output information about HarvestData table
	*/
	function infoHarvestData() {
		echo "Harvest buffer status report:\n";
		echo "-------------------------------------------------------\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED_TMP));
		echo 'temporarily stored harvest replies:         '.$num."\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED_ERR));
		echo 'harvested replies from incomplete harvests: '.$num."\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED));
		echo 'harvested unparsed replies:                 '.$num."\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_PARSING));
		echo 'harvested replies currently being parsed:   '.$num."\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_DONE));
		echo 'harvested parsed replies:                   '.$num."\n";
		$num=$this->db->_fetchValue("harvestdata_count_by_status", array(OAS_SP_HARVESTDATA_STATUS_ERROR));
		echo 'harvested replies with parsing errors:      '.$num."\n";
		echo "\n";
	}



    /*!
            creates the basic tables. adm-create
	*/
        function createDatabase(){
            foreach($this->basic_tabledefinitions as $name => $tabledef){
			$this->db->dbc->exec('CREATE TABLE IF NOT EXISTS `'.$name.'` ('.$tabledef.') ENGINE=MyISAM CHARSET=utf8');
            }
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
