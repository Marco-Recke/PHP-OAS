<?php

/*
 * Basic class for dataprovider-management. This class interacts with the database,
 * can create all new tables for a new dataprovider and manages it's data.
 */
require_once(dirname(__FILE__).'/oai-service-provider.php');
require_once(dirname(__FILE__).'/database_interface.php');


/*!
	this exception will be thrown when errors occur
	in dp-management
*/
class DataProviderManagerException               extends Exception {}
class DataProviderManagerNotInitializedException extends DataProviderManagerException {}
/**
 * Description of dp-data
 *
 * @author giesmann
 */
class dp_data {

    var $id;
    var $baseurl;
    var $repositoryname;
    var $identifydata;
    var $metadataprefix;
    var $errorpolicy;
    var $email;
    var $granularity;
    var $youngestdatestamp;

    var $websiteurl;
    var $httpuser;

    var $out_granularity;
    var $out_identifier;

    var $harvest_laststart;
    var $harvest_lastend;

    var $harvest_lastexitcode;

    var $harvest_lastlistsize;

    var $parse_laststart;
    var $parse_lastend;
    var $parse_lastexitcode;
    var $parse_lastctxocount;

    var $calc_laststart;
    var $calc_lastend;
    var $calc_lastexitcode;

    var $aggr_laststart;
    var $aggr_lastend;
    var $aggr_lastexitcode;

    var $allctxo_parsed;
    var $created;

    var $db;
    var $logger;
    var $data;
    var $noop;
    var $initialised = false;

    /* Tabledefinitions */
    var $tabledefs=array(
        'Actions'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `action` text NOT NULL,
                PRIMARY KEY (`id`)
        ',
        'Classification'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `classification` text NOT NULL,
                PRIMARY KEY (`id`),
                KEY `classification` (`classification`(32))
        ',
        'Format'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `format` text NOT NULL,
                PRIMARY KEY (`id`),
                KEY `format` (`format`(32))
        ',
        'Harvest'=>'
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `timestart` datetime DEFAULT NULL,
                `timeend` datetime DEFAULT NULL,
                `status` int(11) NOT NULL,
                `fromparam` text,
                `untilparam` text,
                PRIMARY KEY (`id`)
        ',
        /* Will be created on the fly:
        'HarvestCtxO'=>'
        */
        'HarvestData'=>'
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `time` datetime NOT NULL,
                `harvestid` bigint(20) NOT NULL,
                `run` int(11) NOT NULL,
                `status` tinyint(4) NOT NULL,
                `data` longblob NOT NULL,
                `ctxos` bigint(20) NOT NULL,
                `records` int(11) NOT NULL,
                `parsetime` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `status` (`status`),
                KEY `harvestid` (`harvestid`)
                ',
        'HarvestError'=>'
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `harvestid` bigint(20) NOT NULL,
                `time` datetime NOT NULL,
                `code` int(11) NOT NULL,
                `info` text NOT NULL,
                PRIMARY KEY (`id`),
                KEY `time` (`time`),
                KEY `code` (`code`),
                KEY `harvestid` (`harvestid`)
        ',
        /* Will be created on the fly:
        'HarvestRecord'=>'
        */
        'Identifier'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `identifier` BLOB NOT NULL,
                PRIMARY KEY (`id`),
                KEY `identifier` (`identifier`(128))
        ',
        'ParseError'=>'
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `time` datetime NOT NULL,
                `harvestid` bigint(20) NOT NULL,
                `harvestdataid` bigint(20) NOT NULL,
                `errormessage` longblob NOT NULL,
                PRIMARY KEY (`id`),
                KEY `harvestid` (`harvestid`),
                KEY `harvestdataid` (`harvestdataid`)
        ',
        'Service'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `service` text NOT NULL,
                PRIMARY KEY (`id`),
                KEY `service` (`service`(64))
        ',
        'ServiceType'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `servicetype` text COLLATE utf8_bin NOT NULL,
                PRIMARY KEY (`id`)
        ',
        'Status'=>'
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `time` datetime NOT NULL,
                `from` date NOT NULL,
                `until` date NOT NULL,
                `action` int(11) NOT NULL,
                PRIMARY KEY (`id`)
        ',
        'UsageData'=>'
                `identifierid` bigint(20) NOT NULL,
                `date` date NOT NULL,
                `servicetypeid` int(11) NOT NULL,
                `classificationid` int(11) NOT NULL,
                `counter` int(10) unsigned NOT NULL,
                `robots` int(10) unsigned NOT NULL,
                PRIMARY KEY (`identifierid`, `date`, `servicetypeid`, `classificationid`) USING BTREE
        ');

   function __construct($db, $logger, $noop = false) {
       $this->db = $db;
       $this->logger = $logger;
       $this->noop = $noop;
   }

   /* internal: datetime helper
      gets current datestamp in mysql-datetime*/
   protected function dt(){
       return date('Y-m-d\TH:i:s');
   }

   /* Checks if instance is initialized, to prevent
    * faulty sql-commits.
    */
   public function checkinit(){
       if(!$this->initialised){
            throw new DataProviderManagerNotInitializedException("DPManager isn't initialised");
        }
   }

   /* Warns if integrity of identifier seems stinky */
   protected function identifierIntegrityWarning($identifier){
       if($identifier == "%"){
           echo "!Warning! Identifer '$identifier' is imperformant! Only leave it that way if you know what you're doing! \n";
       }

       if($identifier[strlen($identifier)-1] != "%"){
           echo "!Warning! Identifer '$identifier' has no '%'-ending! This is normally too specific! \n";
       }

   }

     /* internal: copys data from array to internal variables */
   protected function fromdb($data){
        $this->id                   = $data['id']; //15
        $this->baseurl              = $data['baseurl'];
        $this->repositoryname       = $data['repositoryname'];
        $this->identifydata         = $data['identifydata'];
        $this->metadataprefix       = $data['metadataprefix'];
        $this->errorpolicy          = $data['errorpolicy'];
        $this->email                = $data['email'];
        $this->granularity          = $data['granularity'];
        $this->youngestdatestamp    = $data['youngestdatestamp'];

        $this->httpuser             = $data['httpuser'];

        $this->websiteurl           = $data['websiteurl'];
        $this->default_identifier   = $data['default_identifier'];
        $this->allctxo_parsed       = $data['allctxo_parsed'];
        $this->created              = $data['created'];

        $this->harvest_laststart    = $data['harvest_laststart']; //11
        $this->harvest_lastend      = $data['harvest_lastend'];
        $this->harvest_lastlistsize = $data['harvest_lastlistsize'];
        $this->harvest_lastexitcode = $data['harvest_lastexitcode'];

        $this->parse_laststart      = $data['parse_laststart'];
        $this->parse_lastend        = $data['parse_lastend'];
        $this->parse_lastctxocount  = $data['parse_lastctxocount'];
        $this->parse_lastexitcode   = $data['parse_lastexitcode'];

        $this->calc_laststart       = $data['calc_laststart'];
        $this->calc_lastend         = $data['calc_lastend'];
        $this->calc_lastexitcode    = $data['calc_lastexitcode'];

        $this->aggr_laststart       = $data['aggr_laststart'];
        $this->aggr_lastend         = $data['aggr_lastend'];
        $this->aggr_lastexitcode    = $data['aggr_lastexitcode'];

   }


   /* load and save */
       //Tries to load Dataprovider-Data. Returns Array all collumns
    public function loadbyID($id){
        $this->data = $this->db->_fetchValues("dataprovider_get", array($id));
        if($this->data == false){
            throw new DataProviderManagerException("ID $id doesn't exist!");
        }

        $this->fromdb($this->data);

        $this->initialised = true;
        return $this->data;
    }

    //Renews Data
    public function update(){
        $this->checkinit();

        return($this->loadbyID($this->id));
    }

    //Saves all currently changes flags to database.
    public function save_flags(){
        $this->checkinit();

        return $this->db->_exec('dataprovider_update_flags',
                         array(
                            $this->allctxo_parsed,

                            $this->harvest_laststart,
                            $this->harvest_lastend,
                            $this->harvest_lastlistsize,
                            $this->harvest_lastexitcode,

                            $this->parse_laststart,
                            $this->parse_lastend,
                            $this->parse_lastctxocount,
                            $this->parse_lastexitcode,

                            $this->calc_laststart,
                            $this->calc_lastend,
                            $this->calc_lastexitcode,

                            $this->aggr_laststart,
                            $this->aggr_lastend,
                            $this->aggr_lastexitcode,
                            $this->id
                            )
                        );

    }



    /*!
        add new data provider

        call this with the BaseURL of a data provider that shall be added
        to the catalogue of data providers

        this will fetch basic information (OAI query "identify")
        and the metadataPrefix to use (OAI query "listMetadataFormats")
        and set up the catalogue accordingly

        \param $baseurl the BaseURL of the OAI server, may contain HTTP authentification
               $websiteurl       URL of service (like oas.de)
               $httpuser         httpuser REST- interface
               $default_identifier   fallback identifier (for sql likes)
    */
    function add_data_provider($baseurl, $default_identifier = "", $httpuser = "", $websiteurl = "") {
            $this->initialised = false;

            //Open up a new Service Provider, which handles the server requests
            //for the "identify-dialogue" with the oai-server
            $oaisp=new OAIServiceProvider($baseurl, $this->logger);
            $oaisp->abort_callback=false; // TODO: What's this?

            //Open up a OAIHarvester, which is able to decode OAI-Messages.
            //All query - return-values will be in $oai_harvester->data[]
            $oai_harvester = new OAIHarvester($this->db, $this->logger);

            // check "identify" response. Transport through OAIServiceProvider,
            // messages decoded by $oai_harvester->add_data_provider_identify_parser()
            $oaisp->query_identify(array($oai_harvester,'add_data_provider_identify_parser'));

            // check "listMetadataFormats" response for supported metadata scheme
            $oaisp->query_listmetadataformats(array($oai_harvester,'add_data_provider_listmetadataformats_parser'));
            if(!isset($oai_harvester->data['metadataprefix']))
                    throw new OAIHarvesterException('No supported metadataFormat found.');

            // now check if we already have that data provider in our DB (by its baseURL)
            $dp = $this->db->_fetchValue("dataprovider_get_by_baseurl", array($oai_harvester->data['baseurl']));
            if($dp)
                    throw new DataProviderManagerException('duplicate: there already is an entry for a data provider with baseURL <'.$oai_harvester->data['baseurl'].'>');

            $this->check_httpuser($httpuser);

            //If this was successful so far, extrapolate websiteurl, if not specified
            if($websiteurl == ""){
                $websiteurl = parse_url($baseurl,PHP_URL_HOST);
            }

            //If default-identifier is not defined, fallback to "%"
            if($default_identifier == ""){
                $default_identifier = "%";
            }

            $this->identifierIntegrityWarning($default_identifier);

            // save record, clear instance
            $this->db->_exec("dataprovider_insert", array(
                    $baseurl,
                    $oai_harvester->data['repositoryname'],
                    $oai_harvester->data['identifydata'],
                    $oai_harvester->data['metadataprefix'],
                    0,
                    $oai_harvester->data['email'],
                    $oai_harvester->data['granularity'],
                    $oai_harvester->data['earliestdatestamp'],

                    $this->dt(),            //created
                    $httpuser,              //httpuser
                    $websiteurl,            //url of service

                    $default_identifier,    //fallback identifier for data statistic-journal

                    ));
            //get id of new dataprovider
            $id = $this->db->_fetchValue("last_id");

            if($id == false){
                throw new DataProviderManagerException('Fetch Value returned FALSE! SQL-Error.');
            }

            // add all necessary tables to database
            foreach($this->tabledefs as $name=>$tabledef) {
                    $this->db->dbc->exec('CREATE TABLE `'.$id . "_" .$name.'` ('.$tabledef.') ENGINE=MyISAM CHARSET=utf8');
            }


            unset($oai_harvester);
            unset($oaisp);

            //Retrieve Information from DB
            $this->loadbyID($id);
    }


    //Truncates all Dataprovider-Related Tables
    public function resetDP($id){
        $this->initialised = false;

        $this->loadbyID($id);

        $stmt = $this->db->dbc->prepare("SHOW TABLES LIKE '".$id."\_%'");
        $stmt->execute();

        while($table = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $this->_log("Truncate table " . $table, 10);
            $this->db->dbc->exec('TRUNCATE TABLE '.$table);
        }

        $this->resetHarvestDateStamp($id);

        $this->resetFlags($id);

        // get updated informations
        $this->loadbyID($id);
    }

    /*!
            reset HarvestDateStamp

            Sets the given DataProvider to 2001-01-01
    */
    function resetHarvestDateStamp($id,$resetTime = '2000-01-01 00:00:01') {
        if($this->noop) return;

        //Reset Date...
        $this->_log("Setting last harvest date of id " . $id . " to " . $resetTime . ".", 10);
        $this->db->_exec("dataprovider_set_youngestdatestamp", array($resetTime, $id));

        //Control
        $dateTimeStamp = $this->db->_fetchValue("dataprovider_get_youngestdatestamp", array($id));
        $this->_log("New Timestamp is: " . $dateTimeStamp, 10);
        }

    /* Reset */
    public function resetFlags($id)
    {
        $this->_log("Reset all flags for id " . $id, 10);
        $this->db->_exec('dataprovider_update_flags',
                         array(null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,$this->id)
                        );
    }

    /* Functions for light logging */
    public function start_harvest(){
        $this->checkinit();
        $this->harvest_laststart = $this->dt();
        $this->save_flags();
    }

    public function stop_harvest($last_listsize,$exitcode = 0){
       $this->checkinit();
       $this->harvest_lastlistsize     = $last_listsize;
       $this->harvest_lastexitcode     = $exitcode;
       $this->harvest_lastend = $this->dt();
       $this->save_flags();
    }

    public function start_parse(){
        $this->checkinit();
        $this->parse_laststart = $this->dt();
        $this->save_flags();
    }

     public function stop_parse($ctxocount,$exitcode = 0){
         $this->checkinit();
         $this->parse_lastend = $this->dt();
         $this->parse_lastexitcode = $exitcode;

         if($exitcode == 0){
            $this->parse_lastctxocount = $ctxocount;
            $this->allctxo_parsed+=$ctxocount;
         }
         $this->save_flags();
     }

    public function start_calculate(){
        $this->checkinit();
        $this->calc_laststart = $this->dt();
        $this->save_flags();
    }

    public function stop_calculate($exitcode = 0){
         $this->checkinit();
         $this->calc_lastend = $this->dt();
         $this->calc_lastexitcode = $exitcode;
         $this->save_flags();
    }

    public function start_aggregate(){
        $this->checkinit();
        $this->aggr_laststart = $this->dt();
        $this->save_flags();
    }

    public function stop_aggregate($exitcode = 0){
        $this->checkinit();
         $this->aggr_lastend = $this->dt();
         $this->aggr_lastexitcode = $exitcode;
         $this->save_flags();
    }

    /* getter */
    public function get_statusinfo($id = NULL){

        if($id != NULL){
            $this->loadbyID($id);
        }else{
            $this->checkinit();
        }

        return array(
            'allctxo_parsed' => $this->allctxo_parsed,

            'harvest_laststart' => $this->harvest_laststart ,
            'harvest_lastend'   => $this->harvest_lastend ,
            'harvest_lastlistsize' => $this->harvest_lastlistsize,
            'harvest_lastexitcode' => $this->harvest_lastexitcode,

            'parse_laststart' => $this->parse_laststart,
            'parse_lastend' => $this->parse_lastend,
            'parse_lastctxocount' => $this->parse_lastctxocount,
            'parse_lastexitcode' => $this->parse_lastexitcode,

            'calc_laststart' => $this->calc_laststart,
            'calc_lastend' => $this->calc_lastend,
            'calc_lastexitcode' => $this->calc_lastexitcode,

            'aggr_laststart' => $this->aggr_laststart,
            'aggr_lastend' => $this->aggr_lastend,
            'aggr_lastexitcode' => $this->aggr_lastexitcode
        );
    }

    /* getter */
    public function get_all($id = NULL){

        if($id == NULL){
            $this->checkinit();
            return $this->data;
        }

        return($this->loadbyID($id));
    }


    /* Functions for manipulation */
    public function set_httpuser($httpuser){
        $this->checkinit();

        $this->check_httpuser($httpuser);
        $this->db->_exec('dataprovider_update_httpuser', array($httpuser,$this->id));
        $this->httpuser = $httpuser;

    }

    public function get_defaultIdentifier(){
        $this->checkinit();

        return $this->default_identifier;
    }

    public function set_defaultIdentifier($default_identifier){
        $this->checkinit();
        $this->identifierIntegrityWarning($default_identifier);

        $this->db->_exec('dataprovider_update_default_identifier', array($default_identifier,$this->id));
        $this->default_identifier = $default_identifier;
    }

    public function get_websiteURL(){
        $this->checkinit();

        return $this->websiteurl;
    }


    public function set_websiteURL($websiteurl){
        $this->checkinit();

        $this->db->_exec('dataprovider_update_websiteurl', array($websiteurl,$this->id));
        return true;

    }

    public function get_Id()
    {
        $this->checkinit();

        return $this->id;
    }

    /* Checks if we already have a data provider with this http user in our database */
    private function check_httpuser($httpuser)
    {
        if ($httpuser == "")
            return;
        $dp = $this->db->_fetchValue("dataprovider_get_by_httpuser", array($httpuser));
        if($dp)
            throw new DataProviderManagerException('duplicate: there already is an entry for a data provider with user name <'.$httpuser.'>');
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
