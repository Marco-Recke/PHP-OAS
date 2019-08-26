<?php
/*
   OAI Harvester

   Parser backend, parses harvested metadata blobs

   (c) 2012  Hans-Werner Hilse <hilse@sub.uni-goettingen.de>

*/

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/database_interface.php');

// check prerequesites
if(!extension_loaded('dom'))
	trigger_error('DOM extension not found.',E_USER_ERROR);

/*! we will throw this exception upon parsing errors */
class OAIParserException extends Exception {}
/*! we will throw this exception upon abort due to signal or user request */
class OAIParserAbortException extends Exception {}

/*!
	specialized parser for OAI PMH query replies

	this will fetch full reply documents from the database
	and parse them into OAI PMH record information and finally
	the contained data for the ContextObjects
*/
class OAIParser {
	/*! the database prefix */
	var $prefix=false;
	/*! callback name for a logger function */
	var $logger=false;
	/*! no operation flag, will not modify database if set to true */
	var $noop=false;
	/*! a callback function name used for checking whether we should abort harvesting */
	var $abort_callback=false;

	/*! a dataprovider manager object */
	var $dpmanager=false;
	/*! will hold file descriptor for profiling output file */
	var $profiling_fd=false;
	/*! will contain an array with profiling counters when profiling is active */
	var $profiling=false;
	/*! all ctxos processed in the whole parse process */
	var $all_ctxos=0;

	/*!
		prepares database connection and instance variables

		\param $db database interface
		\param $dpmanager the data provider manager object
		\param $logger a callback function name for a logging function
		\param $noop if true, we won't modify the database
		\param $profiling_fd a filedescriptor for profiling output (optional)
		\param $abort_callback is the name of a function that is called to determine if we should quit
	*/
	function __construct($db, $dpmanager, $logger=false, $noop=false, $profiling_fd=false, $abort_callback=false) {
		$this->prefix = $db->createPrefix($dpmanager->get_Id());
		$this->dpmanager=$dpmanager;
		$this->logger=$logger;
		$this->noop=$noop;
		$this->db=$db;
		if($profiling_fd !== false) {
			$this->profiling_fd=$profiling_fd;
			$this->profiling=array("replies"=>0, "records"=>0, "ctxos"=>0);
		}
		$this->abort_callback=$abort_callback;
	}

	/*!
		increment profile counter and output line to profiling output file

		\param $what the profiling item to increment the count for
	*/
	function profile($what) {
		if($this->profiling_fd) {
			$this->profiling[$what]++;
			$log = microtime(true);
			foreach($this->profiling as $datum) {
				$log.=" $datum";
			}
			fwrite($this->profiling_fd, $log."\n");
		}
	}

	/*!
		run as long as we have harvested data that is not yet parsed in the database

	*/
	function run() {
		$counter = 0;
		try {
			$this->dpmanager->start_parse();
			while($this->runOnce()) {
				$counter++;
			}
		} catch (Exception $e) {
			$this->dpmanager->stop_parse($this->all_ctxos,OAS_SP_HARVESTDATA_STATUS_ERROR);
		}
		$this->dpmanager->stop_parse($this->all_ctxos);
		return $counter;
	}

	/*!
		do a single parse run

		\return true when we successfully parsed data, false otherwise
	*/
	function runOnce(){
		// this is the return value and will be set to true if we
		// successfully processed data
		$processed=false;
		// in order to ensure that we're the only process working on
		// a specific set of data, we're locking the table here:
		$this->db->dbc->exec("LOCK TABLES ".$this->prefix."HarvestData WRITE");

		$id=$this->db->_fetchValue("harvestdata_findone_status", array(OAS_SP_HARVESTDATA_STATUS_HARVESTED));

		if($id) {
			if(!$this->noop)
				$this->db->_exec("harvestdata_update_status", array(OAS_SP_HARVESTDATA_STATUS_PARSING, $id));
		}

		// don't forget to unlock what we've locked before:
		$this->db->dbc->exec("UNLOCK TABLES");

		if($id) {
			// do actual parsing
			$harvestdata=$this->db->_fetchValues("harvestdata_get", array($id));
			try {
				$duration = time();
				$info = $this->reply_parser($harvestdata);
				$this->all_ctxos += $info['ctxos'];
				$duration = time() - $duration;
				$this->profile("replies");
				if(!$this->noop) {
					$this->db->_exec("harvestdata_update_status", array(OAS_SP_HARVESTDATA_STATUS_DONE, $id));
					$this->db->_exec("harvestdata_update_statistics", array(
						$info['records'], $info['ctxos'], $duration, $id));
				}
				$processed=true;
			} catch(Exception $e) {
				if(!$this->noop) {
					$this->db->_exec("harvestdata_update_status", array(OAS_SP_HARVESTDATA_STATUS_ERROR, $id));
					$this->db->_exec("parseerror_insert", array(
						gmdate('Y-m-d H:i:s'),
						$harvestdata["harvestid"],
						$id,
						$e->getMessage()."\nin: ".$e->getFile()."(".$e->getLine()."), stack:\n".$e->getTraceAsString()));
				}
				throw $e;
			}
		}
		return $processed;
	}

	/*!
		this will parse a XML data BLOB that contains the response from an OAI data provider

		\param $harvestdata a row from the HarvestData table as associative array
		\return a table with statistical information about the processing
	*/
	function reply_parser($harvestdata) {
		$this->_log("parsing harvested data", 20);
		$data=DOMDocument::loadXML($harvestdata["data"]);
		if(!$data)
			throw new OAIParserException('Error parsing XML data');

		if(!($xpath=new DOMXPath($data)))
			throw new OAIParserException('Cannot create DOMXPath');
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);

		$records=$xpath->query('//OAI20:record');
		if(!count($records))
			throw new OAIParserException('Cannot find any records in reply');

		$info = array('records'=>0, 'ctxos'=>0);
		foreach($records as $recordnode) {
			if($this->abort_callback && call_user_func($this->abort_callback)) {
				// we were killed in the meantime, so abort
				throw new OAIParserAbortException('aborted by signal or user request');
			}

			$info['records']++;
			$info['ctxos'] += $this->record_parser($data, $recordnode, $harvestdata["harvestid"], $harvestdata['id']);
		}
		return $info;
	}

	/*!
		parse a record node in a DOM

		\param $doc DOM document with a full OAI PMH reply
		\param $record the DOM node of the record we should parse
		\param $harvestid the ID of the harvest the reply belongs to
		\param $harvestdataid the ID of the reply
		\return number of parsed context objects within the record
	*/
	function record_parser($doc, $record, $harvestid, $harvestdataid) {
		$harvest = $this->db->_fetchValues("harvest_get", array($harvestid));

		if(!($xpath=new DOMXPath($doc)))
			throw new OAIParserException('Cannot create DOMXPath');
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);
		$xpath->registerNamespace('CTX',XMLNS_CTX);

		if(!($oai_id=$xpath->query("OAI20:header/OAI20:identifier", $record)->item(0)->nodeValue))
			throw new OAIParserException('no identifier found in OAI-PMH record header');
		if(!($datestamp=$xpath->query("OAI20:header/OAI20:datestamp", $record)->item(0)->nodeValue))
			throw new OAIParserException('no datestamp found in OAI-PMH record header');

//		$tpostfix = gmdate('_Y_m', strtotime($datestamp));
		$tpostfix = '';

		// first check if we already have this record in our database:
		$rec = $this->db->_fetchValues("harvestrecord_by_recordid", array($oai_id));
		if($rec) {
			if(strtotime($rec["recordtimestamp"]) >= strtotime($datestamp)) {
				// we already parsed this record
				$this->_log("skipping oai record <$oai_id> since we have parsed it before", 20);
				return;
			} else {
				// the version we got is newer than the one we have seen before
				// so delete existing data first
				$this->_log("replacing existent oai record <$oai_id> with newer version", 20);
				$this->db->deleteRecord($rec["id"]);
			}
		}

		if(!isset($this->db->queries['harvestrecord_insert_'.$tpostfix])) {
			$this->db->dbc->exec('
				CREATE TABLE IF NOT EXISTS `'.$this->prefix.'HarvestRecord'.$tpostfix.'` (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					harvestid BIGINT(20) NOT NULL,
					harvestdataid BIGINT(20) NOT NULL,
					ctxos INTEGER(11) NOT NULL,
					recordtimestamp datetime NOT NULL,
					recordid BLOB,
					primary key (id),
					key harvestid (harvestid),
					key harvestdataid (harvestdataid),
					key recordid (recordid(128))
				) ENGINE=MyISAM');
			$this->db->queries['harvestrecord_insert_'.$tpostfix] = $this->db->dbc->prepare("
				INSERT INTO `".$this->prefix."HarvestRecord".$tpostfix."`
					(harvestid, harvestdataid, ctxos, recordtimestamp, recordid)
					VALUES (?,?,?,?,?)");
		}

		$ctxos=$xpath->query("OAI20:metadata/CTX:context-objects/CTX:context-object", $record);
		if ($ctxos->length == 0) {
			unset($ctxos);
			$ctxos=$xpath->query("OAI20:metadata/CTX:context-object", $record);

			if($ctxos->length > 1)
				$this->_log("No context-objects-node! ".$ctxos->length." > 1! OAI-PMH protocol violation.  Ignored. <$oai_id>", 50);
		}

		$this->_log("reading ".$ctxos->length." context objects for oai record <$oai_id>", 20);

		// save record information
		$this->db->_exec("harvestrecord_insert_".$tpostfix, array(
				$harvestid,
				$harvestdataid,
				$ctxos->length,
				$datestamp,
				$oai_id
		));

		$recordid = $this->db->_fetchValue("last_id");

		$ctxo = 0;
		foreach($ctxos as $ctxo_node) {
			if($this->abort_callback && call_user_func($this->abort_callback)) {
				// we were killed in the meantime, so abort
				throw new OAIParserAbortException('aborted by signal or user request');
			}
			$this->parse_ctxo($doc, $ctxo_node, $recordid, $ctxo++);
			$this->profile("ctxos");
		}
		$this->profile("records");
		return $ctxos->length;
	}

	/*!
		write context object data to the database

		\param $data contains a table with the context object's data
	*/
	function write_ctxo($data) {
		/*
			if for whatever reason there were no HTTP referers
			(referring-entity in context-object lingo)
			make sure there is a fake one, an empty string.
		*/
		if(count($data['identifiers']['referring-entity']) == 0)
			$data['identifiers']['referring-entity'][0] = '';

		/*
			this prepares segmented tables

			if the burden on a single table ever gets to big, an option
			would be to create tables for each year, month or even week
			or day. take the commented out approach using table postfixes
			as an example. the implementation is not complete:
			the counting process in lib/statistics.php will need to be
			adapted to look into the right tables

			if you ever implement such an approach, remember that when
			doing the counting, you will possibly have to look into two tables
			at once (!!!, i.e. using a combined table, read MySQL documentation!)
			because some data you need to look at (i.e. the last 1800 seconds
			of the previous day for IFABC's multi-click timespan)
			will be in another table on the border between two time intervals

			for now, the commented out table postfixes are just
			a hint towards a possible implementation.
		*/
		//$tpostfix = gmdate('_Y_m', strtotime($data['ts']));
		$tpostfix = '';
		// set up prepared statement for record insertion if not yet done
		if(!isset($this->db->queries['harvestctxo_insert_'.$tpostfix])) {
			// check if the table is already there
			$this->db->dbc->exec('
				CREATE TABLE IF NOT EXISTS `'.$this->prefix.'HarvestCtxO'.$tpostfix.'` (
					status TINYINT(4) NOT NULL,
					recordid BIGINT(20) NOT NULL,
					ctxo INTEGER(11) NOT NULL,
					parsetimestamp datetime NOT NULL,
					ctxotimestamp datetime NOT NULL,
					httpstatus INTEGER(11),
					reqip BINARY(32) NOT NULL,
					cclass BINARY(32) NOT NULL,
					classificationid INTEGER(11) NOT NULL,
					formatid INTEGER(11) NOT NULL,
					servicetypeid INTEGER(11) NOT NULL,
					serviceid INTEGER(11) NOT NULL,
					size BIGINT(20) NOT NULL,
					documentsize BIGINT(20) NOT NULL,
					referent BLOB,
					useragent BLOB,
					referringentity BLOB,
					hostname BLOB,
					key ctxotimestamp (ctxotimestamp),
					key referent (referent(128)),
					key recordid (recordid)
				) ENGINE=MyISAM');
			$this->db->queries['harvestctxo_insert_'.$tpostfix] = $this->db->dbc->prepare("
				INSERT DELAYED INTO `".$this->prefix."HarvestCtxO".$tpostfix."` 
				(status,recordid,ctxo,
				 parsetimestamp,ctxotimestamp,httpstatus,reqip,cclass,
				 classificationid,formatid,
				 servicetypeid,serviceid,size,documentsize,referent,useragent,
				 referringentity,hostname)
				VALUES (?,?,?,?,?,?,UNHEX(?),UNHEX(?),?,?,?,?,?,?,?,?,?,?)
				");
		}

		// store context objects
		$service = $this->db->_lookup("service", $data["service"]);
		$format = $this->db->_lookup("format", $data["format"]);
		$classification = $this->db->_lookup("classification", $data["classification"]);
		//$useragent = $this->db->_lookup("useragent",$data["useragent"]);

		/*
			the following test was added, because there are issues where misconfiguration in the logfile-parser lead to missing service types.
			addititional information are given, so you check which datasets caused this problems
		 */
		if(!isset($data['service_types'])) {
			$this->_log("WARNING: no service type set for recordid: " . $data['recordid'] . ", ctxo: "  . $data['ctxo'] . ", ctxotimestamp: " . $data['ts'] .
				". dataset is NOT written. there is a problem with the logfileparser configuration.", 11);
		} else {
			foreach($data['identifiers']['referring-entity'] as $referringentity) {
				foreach($data['identifiers']['referent'] as $referent) {
					foreach($data['service_types'] as $type) {
						$servicetype = $this->db->_lookup("servicetype", $type);
						$this->db->_exec("harvestctxo_insert_".$tpostfix, array(
							0,
							$data['recordid'],
							$data['ctxo'],
							gmdate('Y-m-d H:i:s'),
							$data['ts'],
							$data['statuscode'],
							$data['req_ip'],
							$data['cclass'],
							$classification,
							$format,
							$servicetype,
							$service,
							$data['size'],
							$data['documentsize'],
							$referent,
							//$useragent,
							$data['useragent'],
							$referringentity,
							$data['hostname']
							));
					}
				}
			}
		}
	}

	/*!
		parse a single context object

		\param $doc DOM document containing the full OAI PMH query reply
		\param $ctxo_node the DOM node resembling the context object container element
		\param $recordid the id of the surrounding record node
		\param $ctxo_count the position within the surrounding record node
	*/
	function parse_ctxo($doc, $ctxo_node, $recordid, $ctxo_count) {
		// initialize XPath context
		if(!($xpath=new DOMXPath($doc)))
			throw new OAIParserException('Cannot create DOMXPath');
		// register XML namespaces we will use in the XPath queries
		$xpath->registerNamespace('CTX',XMLNS_CTX);
		$xpath->registerNamespace('SERVICE',XMLNS_SERVICE);
		$xpath->registerNamespace('OASA',XMLNS_OASA);
		$xpath->registerNamespace('OASI',XMLNS_OASI);
		$xpath->registerNamespace('OASRI',XMLNS_OASRI);

		$this->_log("found CtxO....", 25);

		// a single context object is identified by the OAI record id *plus* its position in that record (starting at 0)
		$ctxo=array('recordid'=>$recordid, 'ctxo'=>$ctxo_count);

		// parse context object timestamp, i.e. timestamp of the usage event
		if(!($ts=$xpath->query('@timestamp',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no timestamp found in context-object');
		$ctxo['ts']=$ts;

		/*
			Parse the OA-S specific "oa-statistics" block in the
			"administration" block of the context object
		*/
		if(!($oas_status_code=$xpath->query('CTX:administration/OASI:oa-statistics/OASI:status_code',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no status_code found in context-object');
		$ctxo['statuscode']=$oas_status_code;

		$nodes=$xpath->query('CTX:administration/OASI:oa-statistics/OASI:size',$ctxo_node);
		if(!$nodes->length) {
			$this->_log("no size found in context-object, assuming to be 0", 14);
			$ctxo['size']=$size=0;
		} else {
			$ctxo['size']=$size=$nodes->item(0)->nodeValue;
		}

		$nodes=$xpath->query('CTX:administration/OASI:oa-statistics/OASI:document_size',$ctxo_node);
		if(!$nodes->length) {
			$this->_log("no document_size found in context-object, assuming to be =size", 14);
			$ctxo['documentsize']=$size;
		} else {
			$ctxo['documentsize']=$nodes->item(0)->nodeValue;
		}

		if(!($ctxo['format']=$xpath->query('CTX:administration/OASI:oa-statistics/OASI:format',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no format found in context-object');

		if(!($ctxo['service']=$xpath->query('CTX:administration/OASI:oa-statistics/OASI:service',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no service found in context-object');

		/*
			Parse the "requester" block of the context object
		*/
		$hostname_nodes=$xpath->query('CTX:requester/CTX:metadata-by-val/CTX:metadata/OASRI:requesterinfo/OASRI:hostname',$ctxo_node);
		$ctxo['hostname']=$hostname_nodes->length?($hostname_nodes->item(0)->nodeValue):'';

		$useragent_nodes=$xpath->query('CTX:requester/CTX:metadata-by-val/CTX:metadata/OASRI:requesterinfo/OASRI:user-agent',$ctxo_node);
		$ctxo['useragent']=$useragent_nodes->length?($useragent_nodes->item(0)->nodeValue):'';

		if(!($ctxo['req_ip']=$xpath->query('CTX:requester/CTX:metadata-by-val/CTX:metadata/OASRI:requesterinfo/OASRI:hashed-ip',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no hashed-ip found in context-object');

		if(!($ctxo['cclass']=$xpath->query('CTX:requester/CTX:metadata-by-val/CTX:metadata/OASRI:requesterinfo/OASRI:hashed-c',$ctxo_node)->item(0)->nodeValue))
			throw new OAIParserException('no hashed-c found in context-object');

		$nodes=$xpath->query('CTX:requester/CTX:metadata-by-val/CTX:metadata/OASRI:requesterinfo/OASRI:classification',$ctxo_node);
		if(!$nodes->length) {
			$this->_log("no classification found in context-object, ignoring", 21);
			$ctxo['classification']='none';
		} else {
			$ctxo['classification']=$nodes->item(0)->nodeValue;
		}

		/*
			ServiceType=ALL not used anymore. Should be not issue, as one can do a query to fetch the data regardless its servicetype
		*/
		// $ctxo['service_types']=array('ALL');
		$sts=$xpath->query('CTX:service-type/CTX:metadata-by-val/CTX:metadata/*',$ctxo_node);

		foreach($sts as $st) {
			$ctxo['service_types'][]=$st->localName;
		}

		/*
			Parse Identifiers
		*/
		$ctxo['identifiers']=array(
			'referent'=>array(),
			'referring-entity'=>array(),
			'resolver'=>array(),
			'referrer'=>array());
		foreach(array_keys($ctxo['identifiers']) as $item) {
			$identifiers=$xpath->query('CTX:'.$item.'/CTX:identifier',$ctxo_node);
			foreach($identifiers as $id_node) {
				$ctxo['identifiers'][$item][]=$id_node->nodeValue;
			}
		}

		// write data to database
		$this->write_ctxo($ctxo);
		// clean up (do we need this?!?)
		unset($ctxo);
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
