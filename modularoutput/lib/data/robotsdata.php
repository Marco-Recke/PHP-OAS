<?php
require_once(dirname(__FILE__).'/data.php');

/**
*  Fetches and provides robots data from database
*
*  @package modular output
*  @version 0.2
*/
class RobotsData extends Data {

    function __construct(Database $db, params $paramInstance, Logger $logger) {
        $this->db = $db;
        $this->paramInstance = $paramInstance;
        $this->logger = $logger;
    }

    public function fetchData(){
        $robotsQuery = $this->db->dbc->query("SELECT useragent,name AS source,updated FROM Robots AS R,RobotsSource AS S WHERE R.source=S.id ORDER BY LOWER(useragent)");
        $robots = $robotsQuery->fetchAll(PDO::FETCH_ASSOC);

        $this->setData($robots);
    }
}

?>