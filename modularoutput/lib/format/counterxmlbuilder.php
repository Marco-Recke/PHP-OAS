<?php
/**
 * Counter XML conform XML builder
 *
 * @author Matthias Hitzler <hitzler@sub.uni-goettingen.de> for SUB Göttingen
 * @author Hans-Werner Hilse <hilse@sub.uni-goettingen.de> for SUB Göttingen
 * @package modularoutput
 * @version 0.1
 */

//require_once(dirname(__FILE__).'../myxmlwriter.php');

define('XMLNS_COUNTER', 'http://www.niso.org/schemas/counter');
define('XMLNS_SCHEMA', 'http://www.w3.org/2001/XMLSchema');
define('XMLSCHEMALOC_COUNTER', 'http://www.niso.org/schemas/sushi/counter4_0.xsd');

class CounterXMLBuilder extends MyXmlWriter {
    var $output_count=0;
    var $first_time=false;
    var $last_time=false;

    /**
     * Starts a new context objects container
     * @param $starttime optional, just for downward compatibility
     * @param $endtime optional, just for downward compatibility
     * @param $count optional, just for downward compatibility
     */
    function start($starttime=false, $endtime=false, $count=false) {
		$this->openMemory();
		//$this->startDocument('1.0', 'UTF-8');
		$this->startElement('Report');//,XMLNS_COUNTER);
		//$this->writeAttributeNS('xsi','schemaLocation',XMLNS_SCHEMA,XMLNS_COUNTER.' '.XMLSCHEMALOC_COUNTER);
    }

    /**
     * Resets the container
     */
    function reset() {
		$this->openMemory();
		$this->output_count=0;
		$this->first_time=false;
		$this->last_time=false;
    }



    /**
     * Adds a report to the container
     * @param $data xml data to add
     */
    function add_reports($data) {
    	//Report
		foreach($data['Report'] as $report) {
			$this->startElementNS(NULL,'Report',XMLNS_COUNTER);
                        //$this->writeAttribute("xmlns", XMLNS_COUNTER);

				//optional
				if(!empty($report['Created'])) {
					$this->writeAttribute('Created',$report['Created']);
				}
				$this->writeAttribute('ID',$report['ID']);
				//optional
				if(!empty($report['Version'])) {
					$this->writeAttribute('Version',$report['Version']);
				}
				//optional
				if(!empty($report['Name'])) {
					$this->writeAttribute('Name',$report['Name']);
				}
				//optional
				if(!empty($report['Title'])) {
					$this->writeAttribute('Title',$report['Title']);
				}

			//Vendor
			$this->startElementNS(NULL,'Vendor',XMLNS_COUNTER);
				//optional
				if(!empty($report['Vendor']['Name'])) {
					$this->writeElementNS(NULL,'Name',XMLNS_COUNTER,$report['Vendor']['Name']);
				}
	            $this->writeElementNS(NULL,'ID',XMLNS_COUNTER,$report['Vendor']['ID']);
            	//optional
            	if(!empty($report['Vendor']['Contact'])) {
	            	foreach($report['Vendor']['Contact'] as $vendor_contact) {
		            	$this->startElementNS(NULL,'Contact',XMLNS_COUNTER);
							if(!empty($vendor_contact['Contact'])) {
								$this->writeElementNS(NULL,'Contact',XMLNS_COUNTER,$vendor_contact['Contact']);
							}
							if(!empty($vendor_contact['E-mail'])) {
								$this->writeElementNS(NULL,'E-mail',XMLNS_COUNTER,$vendor_contact['E-mail']);
							}
						$this->endElement(); //Contact
						}
				}
				//optional
				if(!empty($report['Vendor']['WebSiteUrl'])) {
					$this->writeElementNS(NULL,'WebSiteUrl',XMLNS_COUNTER,$report['Vendor']['WebSiteUrl']);
				}
				//optional
				if(!empty($report['Vendor']['LogoUrl'])) {
					$this->writeElementNS(NULL,'LogoUrl',XMLNS_COUNTER,$report['Vendor']['LogoUrl']);
				}
	        $this->endElement(); // Vendor

	        //Customer
	        foreach($report['Customer'] as $customer) {
		        $this->startElementNS(NULL,'Customer',XMLNS_COUNTER);
		        	//optional
		        	if(!empty($customer['Name'])) {
		        		$this->writeElementNS(NULL,'Name',XMLNS_COUNTER,$customer['Name']);
		        	}
		        	$this->writeElementNS(NULL,'ID',XMLNS_COUNTER,$customer['ID']);
		        	//optional
		        	if(!empty($customer['Contact'])) {
			        	foreach($customer['Contact'] as $customer_contact) {
		            		$this->startElementNS(NULL,'Contact',XMLNS_COUNTER);
								if(!empty($customer_contact['Contact'])) {
									$this->writeElementNS(NULL,'Contact',XMLNS_COUNTER,$customer_contact['Contact']);
								}
								if(!empty($customer_contact['E-mail'])) {
									$this->writeElementNS(NULL,'E-mail',XMLNS_COUNTER,$customer_contact['E-mail']);
								}
							$this->endElement();
						}
					}
					//optional
					if(!empty($customer['WebSiteUrl'])) {
		        		$this->writeElementNS(NULL,'WebSiteUrl',XMLNS_COUNTER,$customer['WebSiteUrl']);
		        	}
		        	//optional
		        	if(!empty($customer['LogoUrl'])) {
		        		$this->writeElementNS(NULL,'LogoUrl',XMLNS_COUNTER,$customer['LogoUrl']);
		        	}

		        //optional
		        if(!empty($customer['Consortium'])) {
		        	//optional
		        	if(!empty($customer['Consortium']['Code'])) {
		        		$this->writeElementNS(NULL,'Code',XMLNS_COUNTER,$customer['Consortium']['Code']);
		        	}
		        	$this->writeElementNS(NULL,'WellKnownName',XMLNS_COUNTER,$customer['Consortium']['WellKnownName']);
		        }

		        //optional
		        if(!empty($customer['InstitutionalIdentifier'])) {
			        foreach($customer['InstitutionalIdentifier'] as $institutionalidentifier) {
			        	$this->writeElementNS(NULL,'Type',XMLNS_COUNTER,$institutionalidentifier['Type']);
			        	$this->writeElementNS(NULL,'Value',XMLNS_COUNTER,$institutionalidentifier['Value']);
			        }
			    }

				//ReportItems
		    	foreach($customer['ReportItems'] as $reportitem) {
			    	$this->startElementNS(NULL,'ReportItems',XMLNS_COUNTER);
			    		//optional
			    		if(!empty($customer['ItemIdentifier'])) {
				    		foreach($reportitem['ItemIdentifier'] as $itemidentifier) {
			        			$this->writeElementNS(NULL,'Type',XMLNS_COUNTER,$itemidentifier['Type']);
			        			$this->writeElementNS(NULL,'Value',XMLNS_COUNTER,$itemidentifier['Value']);
			        		}
			        	}
			    		$this->writeElementNS(NULL,'ItemPlatform',XMLNS_COUNTER,$reportitem['ItemPlatform']);
			    		//optional
			    		if(!empty($reportitem['ItemPublisher'])) {
							$this->writeElementNS(NULL,'ItemPublisher',XMLNS_COUNTER,$reportitem['ItemPublisher']);
						}
						$this->writeElementNS(NULL,'ItemName',XMLNS_COUNTER,$reportitem['ItemName']);
						$this->writeElementNS(NULL,'ItemDataType',XMLNS_COUNTER,$reportitem['ItemDataType']);

						foreach($reportitem['ItemPerformance'] as $item) {
							//optional attribute
							if (!empty($item['PubYr'])) {
								$this->writeAttribute('PubYr',$item['PubYr']);
							}
							//optional attribute
							if (!empty($item['PubYrFrom'])) {
								$this->writeAttribute('PubYrFrom',$item['PubYrFrom']);
							}
							//optional attribute
							if (!empty($item['PubYrTo'])) {
								$this->writeAttribute('PubYrTo',$item['PubYrTo']);
							}
							$this->startElementNS(NULL,'ItemPerformance',XMLNS_COUNTER);
							$this->startElementNS(NULL,'Period',XMLNS_COUNTER);
								$this->writeElementNS(NULL,'Begin',XMLNS_COUNTER,$item['Period']['Begin']);
								$this->writeElementNS(NULL,'End',XMLNS_COUNTER,$item['Period']['End']);
							$this->endElement(); //Period
							$this->writeElementNS(NULL,'Category',XMLNS_COUNTER,$item['Category']);
							foreach($item['Instance'] as $instance) {
								$this->startElementNS(NULL,'Instance',XMLNS_COUNTER);
									$this->writeElementNS(NULL,'MetricType',XMLNS_COUNTER,$instance['MetricType']);
									$this->writeElementNS(NULL,'Count',XMLNS_COUNTER,$instance['Count']);
								$this->endElement();
							}
						$this->endElement(); //ItemPerformance
						}
					$this->endElement(); //ReportItems
					}
				$this->endElement(); //Customer
			}
			$this->endElement(); //Report
		}
    }

    /**
     * Closes the container
     */
    function done() {
		$this->endElement(); // Reports
    }
}
