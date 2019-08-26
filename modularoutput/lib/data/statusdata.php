<?php
require_once(dirname(__FILE__).'/data.php');
require_once(dirname(__FILE__).'/summarizedtotalgranularityusagedata.php');


/**
*  Fetches and provides status data from database
*
*  @author Matthias Hitzler
 * @author Marc Giesmann
*  @package modular output
*  @version 0.2
*/
class StatusData extends Data {

    private $godMode = false;

    function __construct(Database $db, params $paramInstance, Logger $logger) {
        $this->db = $db;
        $this->paramInstance = $paramInstance;
        $this->logger = $logger;
    }

    public function fetchData(){
        if($this->godMode && $this->paramInstance->getValue("id")==0){
            $stmt = $this->db->dbc->prepare("SELECT * FROM DataProvider");
            $stmt->execute();

            //Fetch associative array
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dpdata = array();

            foreach($res as $dp){
                $id = $dp['id'];
                $dpdata[$id] = $dp;

                $tblsizes = $this->db->dbc->prepare("SELECT TABLE_NAME,CONCAT(ROUND((DATA_LENGTH/(1024*1024)),2),' MB') AS SIZE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '$id%';");
                $tblsizes->execute();
                $tblsizes_res = $tblsizes->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP);

                array_push($dpdata[$id], $tblsizes_res);

                //Dirty way to append the table with a name
                $dpdata[$id] = $this->replace_key_function($dpdata[$id], "0", 'tablesizes');

                unset($dpdata[$id]['id']);
                unset($dpdata[$id]['identifydata']);

            }

        }else{

            $dpdata = $this->paramInstance->getValue('dataprovider');

            $prefix = $dpdata['id'] . '_';
            $identifier = $dpdata['default_identifier'];

            // add identifiercount to the statusdata
            $identifierCountQuery = "SELECT COUNT(*) AS count_identifiers FROM {$prefix}Identifier WHERE identifier LIKE :identifier";
            $stmt = $this->db->dbc->prepare($identifierCountQuery);
            $stmt->execute(array('identifier' => $identifier));
            $identifierCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dpdata['count_identifiers'] = $identifierCount[0]['count_identifiers'];

            // add earliest data set to the statusdata
            $stmt =  $this->db->dbc->prepare("SELECT MIN(date) AS earliest_date FROM {$prefix}UsageData_total");
            $stmt->execute();
            $earliestDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dpdata['earliest_date'] = $earliestDate[0]['earliest_date'];

            // add total statistics for all available types to the statusdata
            $totalUsageData = new SummarizedTotalGranularityUsageData($this->db,$this->paramInstance,$this->logger);

            $totalUsageData->setAllTypes();
            $totalUsageData->setIdentifier($identifier);

            $totalUsageData->fetchData();
            $totalUsageDataResult = $totalUsageData->getData();
            foreach ($totalUsageData->getTypes() as $type) {
                $dpdata[$type] = $totalUsageDataResult[0][$type];
            }

            // $id     = $this->paramInstance->getValue('id');

            // $tblsizes = $this->db->dbc->prepare("SELECT TABLE_NAME,(DATA_LENGTH/(1024*1024))AS SIZE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '$id%';");
            // $tblsizes->execute();
            // $tblsizes_res = $tblsizes->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

            // array_push($dpdata, $tblsizes_res);

            // // Dirty way to append the table with a name
            // $dpdata = $this->replace_key_function($dpdata, "0", 'tablesizes');

            unset($dpdata['id']); //remove ID
            unset($dpdata['identifydata']); //remove xml-stuff
            unset($dpdata['baseurl']); //remove url with password


        }

        $this->setData($dpdata);
    }

    public function setGodMode($godMode)
    {
        $this->godMode = $godMode;
    }

    private function replace_key_function($array, $key1, $key2){
        $keys = array_keys($array);
        $index = array_search($key1, $keys);

        if ($index !== false) {
        $keys[$index] = $key2;
        $array = array_combine($keys, $array);
        }

        return $array;
    }

}

?>