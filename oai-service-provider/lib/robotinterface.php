<?php

/**
* Interface for adding, editing and deleting robot entries in the databse
*/
class RobotInterface
{
	/*! the robot to add */
	private $robot;
	/*! the source of the robot or file */
	private $source;
	/*! an optional comment for the robot entry/entries */
	private $comment = null;
	/*! path to a file with robots */
	private $file;
	/*! the robots table */
	const ROBOTS_LIST 			= 'Robots';
	/*! the unreleased robots table */
	const UNRELEASED_ROBOTS_LIST = 'Robots_unreleased';

	function __construct($db,$logger=false)
	{
		$this->db=$db;
		$this->logger=$logger;
	}

	/*!
		adds a robot to the database
	*/
	public function addRobot($robot)
	{
		// add source to source list if not existing
		if (!$sourceid = $this->db->_fetchValue("robotssource_by_name",array($this->source))) {
			$this->db->_exec("robotssource_insert",array($this->source));
			$this->_log("New RobotsSource is inserted: ". $this->source,15);
			$sourceid = $this->db->_fetchValue("robotssource_by_name", array($this->source));
		}
		// add robot to list
		$this->db->_exec("robots_insert",array($robot,$sourceid,$this->comment));
	}

	/*!
		adds a bunch of robots from a text file to the database
	*/
	public function addRobotsFromFile($file)
	{
		if (!file_exists($file)) {
			$this->_log("File " . $file . " is not existing",15);
			return false;
		}
		$this->_log("Add Robots from file: ". $file,15);
		$robots = file($file,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($robots as $robot) {
			$this->addRobot($robot);
		}
	}
	/**
	 * deletes robot from databse
	 * @param  $robot the robot to delete
	 * @return false if robot is not in database
	 */
	public function deleteRobot($robot)
	{
		if (!$id = $this->db->_fetchValue('robots_by_useragent',array($robot))) {
			$this->_log("Robot " . $robot . " not found",15);
			return false;
		}
		$this->db->_exec('robots_delete_by_id',array($id));
		$this->_log("Robot " . $robot . " was deleted",15);
	}

	/**
	 * deletes all robots from a given source
	 * @param  $source the source which robots should be deleted
	 * @return false if source is not in database
	 */
	public function deleteRobotsFromSource($source)
	{
		if (!$sourceid = $this->db->_fetchValue("robotssource_by_name",array($source))) {
			$this->_log("Source " . $source . " not existing",15);
			return false;
		}
		// delete all robots with the given source from Robots table
		$this->db->_exec("robots_delete_by_source",array($sourceid));
		$this->_log("All Robots from Source ". $source . " deleted",15);

		// delete the source from RobotsSource table
		// $this->db->_exec("robotssource_delete_by_id",array($sourceid));
		// $this->_log("Source ". $source . " deleted",15);
	}

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	public function releaseRobotList()
	{
		$this->copyCurrentRobotsToVersionedList();
		$this->copyUnreleasedToCurrentRobots();
		$this->_log("A new Robot List is released. Current Release Version: " . $this->getCurrentRobotListVersion());
	}

	/**
	 * returns a line broken list with all robot user agents in current list
	 * @param  $version the version number of the robot list (default: current list)
	 * @return a string with all robots
	 */
	public function exportRobotList($version = 'current')
	{
		$buffer = '';

		$robotTable = $this->getRobotsTable($version);

		$robots = $this->db->dbc->query('SELECT useragent FROM '.$robotTable);
		while($robot = $robots->fetch()) {
			$buffer .= $robot['useragent'] . "\n";
		}
		return $buffer;
	}

	/**
	 * returns the tablename of the requested robot list
	 * @param  $version the version of the request robot list
	 * @return the table name of the requested robot list
	 */
	public function getRobotsTable($version = 'current')
	{
		switch ($version) {
			case 'current':
				return self::ROBOTS_LIST;

			case 'unreleased':
				return self::UNRELEASED_ROBOTS_LIST;

			default:
				return $robotTable = $this->db->_fetchValue("robotslists_get_table_by_version", array($version));
		}
	}


	/**
	 * creates a new Version of the RobotsList in the database and copies the current data
	 */
	private function copyCurrentRobotsToVersionedList()
	{
		if ($currentVersion = $this->getCurrentRobotListVersion()) {
			$tableName = 'Robots_'.$currentVersion;
			// creates new table with the current version
			$this->db->dbc->exec('
					CREATE TABLE `'.$tableName.'` (
						`id` int(10) NOT NULL AUTO_INCREMENT,
						`useragent` varchar(255) COLLATE utf8_bin NOT NULL,
						`source` int(10) DEFAULT NULL,
						`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						`comment` varchar(255) COLLATE utf8_bin DEFAULT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY `idx_useragent` (`useragent`)
						) ENGINE=MyISAM CHARSET=utf8');

			// copy the current robots to the new table
			$this->db->dbc->exec('INSERT INTO ' .$tableName.' SELECT * FROM Robots');

			// updates the RobotsLists table with this information
			$this->db->_exec("robotslists_update_by_version", array($tableName, $currentVersion));
		}
	}

	private function copyUnreleasedToCurrentRobots()
	{
		$this->db->dbc->exec("TRUNCATE TABLE Robots");
		$this->db->_exec("robots_insert_from_unreleased");

		// add the new version to the RobotsLists table
		if (!$currentVersion = $this->getCurrentRobotListVersion()) {
			$currentVersion = 0;
		}
		$this->db->_exec("robotslists_insert",array(++$currentVersion, 'Robots'));
	}

	private function getCurrentRobotListVersion()
	{
		return $this->db->_fetchValue("robotslists_get_current_version");
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