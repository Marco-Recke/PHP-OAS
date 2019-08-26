<?php
/**
 * Database Connection Class
 *
 * @package modularoutput
 * @version 0.1
 */
class Database {
    var $dbc = false;
    var $dbconfig = false;

    /**
     * Sets the connection dependent on the given config array
     *
     * @param dbConfig the database config array
     */
    function __construct($dbConfig)
	{
        $this->dbConfig = $dbConfig;

        // build dsn string from data
        $dsn = 'mysql:host='.$this->dbConfig['host'].';dbname='.$this->dbConfig['database'];

        $this->dbc = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['password']);

		// enable PDO Exceptions
        $this->dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}


?>