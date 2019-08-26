<?php
/**
 * Class for harvesting Dublin Core Metadata, extracting identifier metadata
 * and updating a database accordingly.
 * 
 * @author Hans-Werner Hilse <hilse@sub.uni-goettingen.de> for SUB Göttingen
 * @author Marc Giesmann <giesmann@gbv.de> for Verbundzentrale Göttingen
 * @package data-provider
 * @subpackage logfile-parser
 * @version 0.2
 */

require_once(dirname(__FILE__) . '/oai-harvester.php');

class IdentifierHarvester extends OAIHarvester {
    
        //Current state of oai-pmh server. This saves all the identify data for later usage
        var $oaipmh_identifydata = array();
    

	/**
	 * Prepares database connectio0n
	 */
 	function prepare() {
		// open database
		$this->dbh=new PDO('sqlite:'.$this->config['db_identifier']);
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// create table if it doesn't exist yet: (ugly exception catching due to old sqlite version)
		try {
			$this->dbh->query('CREATE TABLE data (
					oaiid,
					datestamp,
					assigned_identifier
				    );');
			$this->dbh->query('CREATE INDEX oaiindex ON data (oaiid);');
		} catch(PDOException $e) {
			// ignore, most probably table/index exists
		}
                
                
                //Call "Identify" request to determine granularity of OAI-PMH protocol
                $oaisp=new OAIServiceProvider($this->config['oai_server'], $this->logger);
		
		try {
			
			$oaisp->query_identify(array($this,'identify_server'));
		} catch(OAIServiceProviderOAIErrorNoRecordsMatchException $e) {
			$this->log('no new records.');
		}

	}

        
        
        /*!	helper function for identifiying attributes of OAI-PMH server.
		Everytime the identifier-harvester prepares, it will try to identify
         *      the server first, to make sure to have the same granularity

		\param $data xml document
	*/
	function identify_server($data) {
		if(!($xpath=new DOMXPath($data)))
			throw new OAIHarvesterException('Cannot create DOMXPath');
                
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);
		if('2.0'!==($protocolVersion=$xpath->query('/OAI20:Identify/OAI20:protocolVersion')->item(0)->nodeValue))
			throw new OAIHarvesterException('Server does not support OAI-PMH 2.0, but rather specifies version '.$protocolVersion);
		if(!($this->oaipmh_identifydata['repositoryname']=$xpath->query('/OAI20:Identify/OAI20:repositoryName')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing repositoryName');
		if(!($this->oaipmh_identifydata['baseurl']=$xpath->query('/OAI20:Identify/OAI20:baseURL')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing baseURL');
		if(!($this->oaipmh_identifydata['earliestdatestamp']=$xpath->query('/OAI20:Identify/OAI20:earliestDatestamp')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing earliestDatestamp');
		if(!($this->oaipmh_identifydata['email']=$xpath->query('/OAI20:Identify/OAI20:adminEmail')->item(0)->nodeValue)) {

			// TEMP FIX for incomplete data from Sb:
			//throw new OAIHarvesterException('Missing adminEmail');
			$this->oaipmh_identifydata['email']='noone@example.org';

		}
		if(!($granularity=$xpath->query('/OAI20:Identify/OAI20:granularity')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing granularity');
		if('YYYY-MM-DDThh:mm:ssZ' == $granularity) {
			$this->oaipmh_identifydata['granularity']= 'Y-m-d\TH:i:s\Z';
		} else if('YYYY-MM-DD' == $granularity) {
			$this->oaipmh_identifydata['granularity']= 'Y-m-d';
		}else{
                    throw new OAIHarvesterException('Nonconform granularity: '. $granularity . ". OAI-PMH specifies 'YYYY-MM-DD' and 'YYYY-MM-DDThh:mm:ssZ' as possible granularities.");
                }
	}
        
        
	/**
	 * Processes read records
	 * @param $data the xml data to process
	 */
	function record_reader($data) {
		// prepare insert and delete statements:
		$ins_stm = $this->dbh->prepare('INSERT INTO data (oaiid, datestamp, assigned_identifier) VALUES (?, ?, ?);');
		$del_stm = $this->dbh->prepare('DELETE FROM data WHERE oaiid=?;');
		
		if(!($xpath=new DOMXPath($data)))
			throw new OAIHarvesterException('Cannot create DOMXPath');
		$xpath->registerNamespace('OAI20',OAIServiceProvider::OAI20_XML_NAMESPACE);
		$xpath->registerNamespace('OAIDC','http://www.openarchives.org/OAI/2.0/oai_dc/');
		$xpath->registerNamespace('DC','http://purl.org/dc/elements/1.1/');

		if(!($oai_identifier=$xpath->query('//OAI20:record/OAI20:header/OAI20:identifier')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing identifier in record header');

		if(!($oai_datestamp=$xpath->query('//OAI20:record/OAI20:header/OAI20:datestamp')->item(0)->nodeValue))
			throw new OAIHarvesterException('Missing datestamp in record header');
		
		date_default_timezone_set('UTC'); // stupid PHP
		$timestamp = strtotime($oai_datestamp);
		$this->_log("Reading record <$oai_identifier>, datestamp <$oai_datestamp> (=$timestamp)");
		
		$this->dbh->beginTransaction();
		$del_stm->execute(array($oai_identifier)); // delete existent records for this oaiid
		
		// now write new entries for each found identifier:
		foreach($xpath->query('//OAI20:record/OAI20:metadata/OAIDC:dc/DC:identifier') as $identifier_node) {
			$dc_identifier=$orig_id=trim($identifier_node->nodeValue);
			$this->_log("Found DC:Identifier <$dc_identifier>");
			if(false!==strpos($dc_identifier,' ')) {
				// identifier contains spaces, so it possibly is some other kind of metadata
				$this->_log("ignored identifier, since it doesn't seem to be a technical one.");
				continue;
			} elseif(preg_match('/^[0-9]{4}-[0-9]{3}[0-9X]/',$dc_identifier)) {
				// identifier is an ISSN (we guess so....)
				$dc_identifier='urn:issn:'.$dc_identifier;  // RFC3044
			} elseif(preg_match('/^([0-9]-?){9}[0-9X]/',$dc_identifier)) {
				// identifier is an ISBN-10 (we think...)
				$dc_identifier='urn:isbn:'.$dc_identifier;  // RFC3187
			} elseif(preg_match('/^([0-9]-?){12}[0-9]/',$dc_identifier)) {
				// identifier is an ISBN-13 (probably)
				$dc_identifier='urn:isbn:'.$dc_identifier;  // RFC3187
			} elseif(preg_match('/^10.[0-9]+\/[^\/].*/',$dc_identifier)) {
				// identifier is a DOI (as it seems)
				$dc_identifier='doi:'.$dc_identifier;       // doi: is a registered URI scheme
			} elseif(!preg_match('/^[0-9a-zA-Z]+:.*/',$dc_identifier)) {
				// identifier is not a URI, so we ignore it for now
				$this->_log('identifier is not a URI, ignoring...');
				continue;
			}
			if($dc_identifier!=$orig_id)
				$this->_log("Transformed identifier to <$dc_identifier>");

			$ins_stm->execute(array($oai_identifier, $timestamp, $dc_identifier));
		}

		$this->dbh->commit();
	}
	
	/**
	 * Reads the last datestamp from database
	 * @see logfile-parser/lib/identifiers/lib/OAIHarvester#get_last_datestamp()
	 * @return string last datestamp, with the specified granularity the server
         *          requests.
	 */
	function get_last_datestamp() {
		$stmt=$this->dbh->prepare('SELECT MAX(datestamp) FROM data;');
		$stmt->execute();
		$ltd=(int)$stmt->fetchColumn();
                return gmdate($this->oaipmh_identifydata['granularity'],$ltd);
		#return gmdate('Y-m-d\TH:i:s\Z',$ltd);
	}
}
