<?php
/*
   OAI Harvester

   Frontend to the OAI Service Provider functions,
   stores metadata in database.

   (c) 2009  SUB Goettingen / Hans-Werner Hilse <hilse@sub.uni-goettingen.de>

*/

require_once(dirname(__FILE__).'/oai-service-provider.php');
require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/database_interface.php');

// check prerequisite
if(!extension_loaded('dom'))
	trigger_error('DOM extension not found.',E_USER_ERROR);

/*!
	this exception will be thrown when errors occur
	in the harvest management/processing
*/
class OAIHarvesterException extends Exception {}

/*!
	frontend for the OAI Service Provider functions,
	manages the harvesting process and will store the
	queried data in the database
*/
class OAIHarvester {
	/*! callback name for a logger function */
	var $logger=false;
	/*! intermediate store for returned data, for callback methods that report back to this class */
	var $data=false;
	/*! a callback function name used for checking whether we should abort harvesting */
	var $abort_callback=false;
	/*! the dataproviderid for a harvest */
	var $dataprovider_id=false;
	/*! the id for a harvest */
	var $harvest_id=false;
	/*! no operation flag, will not modify database if set to true */
	var $noop=false;

	/*!
		set up harvester

		prepares database connection and instance variables

		\param $db database interface
		\param $logger a callback function name for a logging function
		\param $noop if true, we won't modify the database
		\param $abort_callback is the name of a function that is called to determine if we should quit
	*/
	function __construct($db, $logger=false, $noop=false, $abort_callback=false) {
		$this->logger=$logger;
		$this->noop=$noop;
		$this->db=$db;
		$this->abort_callback=$abort_callback;
	}

	/*!	helper function for adding new data providers
		this will be called from the service provider class for the
		reply of the "identify" query

		\param $data xml document
	*/
	function add_data_provider_identify_parser($data) {
		if(!($xpath=new DOMXPath($data)))
			throw new OAIHarvesterException('Cannot create DOMXPath');

		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);
		if('2.0'!==($protocolVersion=$xpath->query('OAI20:Identify/OAI20:protocolVersion')->item(0)->nodeValue))
			throw new OAIHarvesterException('Server does not support OAI-PMH 2.0, but rather specifies version '.$version);
		if(!($this->data['repositoryname']=$xpath->query('OAI20:Identify/OAI20:repositoryName')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing repositoryName');
		if(!($this->data['baseurl']=$xpath->query('OAI20:Identify/OAI20:baseURL')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing baseURL');
		if(!($this->data['earliestdatestamp']=$xpath->query('OAI20:Identify/OAI20:earliestDatestamp')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing earliestDatestamp');
		if(!($this->data['email']=$xpath->query('OAI20:Identify/OAI20:adminEmail')->item(0)->nodeValue)) {

			// TEMP FIX for incomplete data from Sb:
			//throw new OAIHarvesterException('Missing adminEmail');
			$this->data['email']='noone@example.org';

		}
		if(!($granularity=$xpath->query('OAI20:Identify/OAI20:granularity')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing granularity');
		if('YYYY-MM-DDThh:mm:ssZ' == $granularity) {
			$this->data['granularity']=OAIPMH2_GRANULARITY_SECONDS;
		} else {
			$this->data['granularity']=OAIPMH2_GRANULARITY_DAYS;
		}
		$this->data['identifydata']=$data->saveXML();
	}

	/*!
		helper function for adding new data providers

		this will be called from the service provider class for the
		reply of the "listMetadataFormats" query

		\param $data xml document
	*/
	function add_data_provider_listmetadataformats_parser($data) {
		if(!($xpath=new DOMXPath($data)))
			throw new OAIHarvesterException('Cannot create DOMXPath');
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);

		//Creating DomNodeList of Metadata
		$query = 'OAI20:ListMetadataFormats/OAI20:metadataFormat/OAI20:metadataNamespace';
		$domList = $xpath->query($query);

		// checking for multiple metadataFormats
		for($i=0;$i<$domList->length;$i++)
		{
			// Which metadata is listed?
			$metadataValue = $domList->item($i)->nodeValue;

			// only oas MetadataFormat is processed
			if((XMLNS_CTX == $metadataValue) || (XMLSCHEMA_CTX == $metadataValue))
			{
				$this->data['metadataprefix']=$xpath->query('OAI20:ListMetadataFormats/OAI20:metadataFormat/OAI20:metadataPrefix')->item($i)->nodeValue;
                                return;
			}
		}

		$this->_log("no compatible metadata found.");

/*		if(XMLNS_CTX ==
			($ns=$xpath->query($query)->item(0)->nodeValue)
			|| XMLSCHEMA_CTX ==
			($ns=$xpath->query($query)->item(0)->nodeValue)
		) {
			$this->data['metadataprefix']=$xpath->query('OAI20:ListMetadataFormats/OAI20:metadataFormat/OAI20:metadataPrefix')->item(0)->nodeValue;
		} else {
			$this->_log("skipping unsupported metadataFormat <$ns>");
*/	}


	/*!
		method doing the actual harvesting

		\param $dataprovidermanager. It includes all needed information: //VORMALS $dataproviderid
		\param $enforce_from specifies a "from" parameter for the OAI query
		\param $enforce_until specifies an "until" parameter for the OAI query
		\param $segment if specified, it is a DateInterval() value for the longest timespan to fetch in a single harvest (successive harvest)
	*/
	function harvest(dp_data $dataprovidermanager,$enforce_from=false,$enforce_until=false,$segment=false) {
		// read dataprovider information

                $dataprovidermanager->update();
                $dataprovidermanager->start_harvest();

		logger("Starting Harvest", 20);



                /* MG: 25.04.2014 Auskommentiert: dp_data/dataprovidermanager übernimmt diese Aufgabe
		$dataprovider=$this->db->_fetchValues("dataprovider_get", array($dataproviderid));
		if(!$dataprovider) {
			// following part was inserted, because for any reason sometimes the dataprovider ID could not be read from database

			// count of retries, and length in seconds until retry
			$diff = 300;
			$repeat = 3;
			for ($i=0;$i<=$repeat;$i++) {
				$times = $repeat-$i;
				$this->_log("Dataprovider not found for id <$dataproviderid>. Trying again in $diff seconds. Retries: $times.", 10);
				sleep($diff);
				$dataprovider=$this->db->_fetchValues("dataprovider_get", array($dataproviderid));
				if ($dataprovider)
					break;
			}

			if (!$dataprovider) {
				$time = date('Y-m-d H:i:s');
				throw new OAIHarvesterException("$time Dataprovider not found for id <$dataproviderid>." );
			}
		}*/

                //MG: Für Backwardscompatibility: dp_data can return an array with all
                //columns of dataprovider.
                $dataprovider = $dataprovidermanager->get_all();
		/*!
			initialize OAIServiceProvider instance
		*/
		$oaisp=new OAIServiceProvider($dataprovider['baseurl'], $this->logger);
		$oaisp->abort_callback=$this->abort_callback;

		// set up OAI query parameters from/until
		$utc_tz = new DateTimeZone('UTC');
		$from = false;
		if(!$enforce_from) {
			$from=new DateTime($dataprovider['youngestdatestamp'], $utc_tz);
		} else {
			$from=$enforce_from;
		}

		$until=false;

		if($dataprovider["granularity"]==OAIPMH2_GRANULARITY_SECONDS) {
			$format = 'Y-m-d\TH:i:s\Z';
		} else {
			$format = 'Y-m-d';
		}

		// look up youngest datestamp
		if($dataprovider["youngestdatestamp"]) {
			$this->youngestDatestamp=strtotime($dataprovider["youngestdatestamp"]);
		} else {
			$this->youngestDatestamp=0;
		}


		// the following might repeat if we are doing a segmented harvest
		do {
			$until = false;
			if($segment) {
				$until = clone $from;
				$until->add($segment);
				if($enforce_until) {
					// keep the upper limit in mind
					if($until->getTimestamp() > $enforce_until->getTimestamp()) {
						$until = $enforce_until;
					}
				}
			} elseif($enforce_until) {
				$until = $enforce_until;
			}

			$fromparam=$from->format($format);
			$untilparam=NULL;
			if($until) {
				$untilparam=$until->format($format);
			}

			// register harvest run in database
			$this->harvest_id = 0;
			$this->dataprovider_id = $dataprovidermanager->id; //$dataproviderid; //TODO: Wozu? Wird nirgendwo verwendet
			if(!$this->noop) {
				$this->db->_exec("harvest_insert",
					array(gmdate('Y-m-d\TH:i:s\Z', time()), NULL, OAS_SP_HARVEST_STATUS_RUNNING, $fromparam, $untilparam));
				$this->harvest_id = $this->db->_fetchValue("last_id");
			}

			try {
				//$con->beginTransaction();
				$params=array( 'metadataPrefix'=>$dataprovider["metadataprefix"] );
				if($fromparam) $params['from']=$fromparam;
				if($untilparam) $params['until']=$untilparam;

				// run query
				$oaisp->query_listrecords(array($this,'harvest_record_store'), $params);

				// well, everything went apparently fine.
				//$con->commit();

			} catch(OAIServiceProviderOAIErrorNoRecordsMatchException $e) {
				// pseudo-error: no (new) records delivered
				//$con->rollback();
				$this->_log('no new records.');

			} catch(Exception $e) {
				// general error
				//$con->rollback();
				if($e->getMessage()=='illegal OAI server output: No final empty resumptionToken') {
					// this is a special case, that is unfortunately quite common.
					// for now, we handle it as if everything was alright...
					$this->_log('warning: no final empty resumptionToken, OAI protocol violation. ignored.', 15);
				} else {

                    $dataprovidermanager->stop_harvest($oaisp->resTo['completeListSize'], OAS_SP_HARVEST_STATUS_DONE_ERROR);
					$this->_log($e->getMessage(), 5);
					if(!$this->noop) {
						$this->db->_exec("harvest_update_status", array(OAS_SP_HARVEST_STATUS_DONE_ERROR, $this->harvest_id));
						$this->db->_exec("harvestdata_update_status_for_harvestid", array(
							OAS_SP_HARVESTDATA_STATUS_HARVESTED_ERR, $this->harvest_id));
						$this->db->_exec("harvesterror_insert",
							array($this->harvest_id, gmdate('Y-m-d\TH:i:s\Z', time()), OAS_SP_HARVESTERROR_GENERAL,
								$e->getMessage()."\n".$e->getFile()."(".$e->getLine().")\n".$e->getTraceAsString()));
					}
					// let the error cascade
					throw $e;
				}
			}
			if(!$this->noop) {
				$this->db->_exec('harvest_update_timeend', array(gmdate('Y-m-d\TH:i:s\Z', time()), $this->harvest_id));
				$this->db->_exec("harvest_update_status", array(OAS_SP_HARVEST_STATUS_DONE_OK, $this->harvest_id));
				$this->db->_exec("harvestdata_update_status_for_harvestid", array(
					OAS_SP_HARVESTDATA_STATUS_HARVESTED, $this->harvest_id));
				$this->db->_exec('dataprovider_set_youngestdatestamp', array(gmdate('Y-m-d\TH:i:s\Z', $this->youngestDatestamp), $dataprovidermanager->id));
			}
			if($segment) {
				$from->add($segment);
				if($from->getTimestamp() > time()) {
					// the "from" parameter would be after now
					$segment = false; // stop loop
				}
				if($enforce_until && ($enforce_until->getTimestamp() > $from->getTimestamp())) {
					// the "from" parameter would be after the "until" limit
					$segment = false; // stop loop
				}
			}
		} while($segment);

                //End harvest: write to status table
                $dataprovidermanager->stop_harvest($oaisp->resTo['completeListSize']); //TODO: Does this work?
	}

	/*!
		callback method for storing information returned by query

		will be called by OAIServiceProvider instance for parsing
		harvest replies

		\param $data the XML document
	*/
	function harvest_record_store($data) {
		if(!($xpath=new DOMXPath($data)))
			throw new OAIHarvesterException('Cannot create DOMXPath');
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);
		$datestamps=$xpath->query("//OAI20:record/OAI20:header/OAI20:datestamp");
		foreach($datestamps as $dsnode) {
			$ds = strtotime($dsnode->nodeValue);
			if($ds > $this->youngestDatestamp) {
				$this->youngestDatestamp=$ds;
			}
		}
		if(!$this->noop) {
			$this->db->_exec('harvestdata_insert', array(
				gmdate('Y-m-d\TH:i:s\Z', time()), $this->harvest_id,
				OAS_SP_HARVESTDATA_STATUS_HARVESTED_TMP,
				0, 0, 0,
				$data->saveXML()));
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
