<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__).'/out.php');


define('SUSHI_XMLNS_COUNTER', "http://www.niso.org/schemas/sushi/counter");
define('SUSHI_XMLXSD_COUNTER', "http://www.niso.org/schemas/sushi/counter_sushi4_0.xsd");

define('SUSHI_XMLNS_SUSHI', "http://www.niso.org/schemas/sushi");
define('SUSHI_XMLNS_XSI', "http://www.w3.org/2001/XMLSchema-instance");

//define('SUSHI_XMLNS_SUSHI_ERROR', "http://www.niso.org/schemas/sushi/1_5")

/**
 * Description of outsushi
 *
 * @author giesmann
 */
class outsushi extends out {
    protected $inputArray;
    protected $xmlwriter;


    protected function startSushiEnvelope() {        
        //init
        $this->xmlwriter->openMemory();
        $this->xmlwriter->startElementNS(NULL, "ReportResponse", NULL);
            
            //MAIN ATTRIBUTES
            $this->xmlwriter->writeAttribute('ID', $this->inputArray['ID']); 
            $this->xmlwriter->writeAttribute("xsi:schemaLocation", SUSHI_XMLNS_COUNTER . "\n" .SUSHI_XMLXSD_COUNTER);
    
            //Additional ATTRIBUTES (of "ReportResponse"
            $this->xmlwriter->writeAttribute('Created',  date('Y-m-d\TH:i:s')); 
            $this->xmlwriter->writeAttribute("xmlns", SUSHI_XMLNS_COUNTER);
            $this->xmlwriter->writeAttribute("xmlns:s",  SUSHI_XMLNS_SUSHI);
            $this->xmlwriter->writeAttribute("xmlns:xsi", SUSHI_XMLNS_XSI);
    }
    
    
    //Creates the message. Needs to be called AFTER the initialisation
    //of the envelope header (startSushiEnvelope())
    protected function createMessage(){
            
            //Requestor Node
            $this->xmlwriter->startElementNS("s", "Requestor", NULL);
              $this->xmlwriter->writeElementNS("s", "ID", NULL, $this->inputArray['Requestor']['ID']);
              $this->xmlwriter->writeElementNS("s", "Name", NULL, $this->inputArray['Requestor']['Name']);
              $this->xmlwriter->writeElementNS("s", "Email", NULL, $this->inputArray['Requestor']['Email']);
            $this->xmlwriter->endElement(); //Requestor Node
            
            //Customer Reference Node
            $this->xmlwriter->startElementNS("s", "CustomerReference", NULL);
              $this->xmlwriter->writeElementNS("s", "ID", NULL, $this->inputArray['CustomerReference']['ID']);
              $this->xmlwriter->writeElementNS("s", "Name", NULL, $this->inputArray['CustomerReference']['Name']);
            $this->xmlwriter->endElement(); //Customer Reference Node
            
            //ReportDefinition Node
            $this->xmlwriter->startElementNS("s", "ReportDefinition");
            
            //ATTRIBUTES
            $this->xmlwriter->writeAttribute('Name',$this->inputArray['ReportDefinition']['Name']);
            $this->xmlwriter->writeAttribute('Release',$this->inputArray['ReportDefinition']['Release']);
             
              //Filters Node
              $this->xmlwriter->startElementNS("s", "Filters");
            
                //UsageDataRange Node
                $this->xmlwriter->startElementNS("s", "UsageDateRange");
                  $this->xmlwriter->writeElementNS("s", "Begin", NULL, $this->inputArray['ReportDefinition']['Filters']['UsageDateRange']['Begin']);
                  $this->xmlwriter->writeElementNS("s", "End", NULL, $this->inputArray['ReportDefinition']['Filters']['UsageDateRange']['End']);
                $this->xmlwriter->endElement(); //UsageDataRange  
              
               $this->xmlwriter->endElement(); //Filters
              
              $this->xmlwriter->endElement(); //ReportDefinition
              
              
        
    }
    
    
    protected function createErrorMessage($errNo, $errSeverity, $errMessage, $URL, $Data){
        //Exception Node
            $this->xmlwriter->startElementNS("s", "Exception", NULL);
              $this->xmlwriter->writeAttribute('Created',  date('Y-m-d\TH:i:s')); 
              
              $this->xmlwriter->writeElementNS("s", "Number", NULL, $errNo);
              $this->xmlwriter->writeElementNS("s", "Severity", NULL, $errSeverity);
              $this->xmlwriter->writeElementNS("s", "Message", NULL, $errMessage);
              $this->xmlwriter->writeElementNS("s", "HelpUrl", NULL, $URL);
              
              if($Data != ""){
                $this->xmlwriter->writeElementNS("s", "Data", NULL, $Data);
              }
                
             $this->xmlwriter->endElement(); //Exception Node
              
    }
    
    
    protected function endSushiEnvelope(){
        $this->xmlwriter->endElement(); //ReportResponse
    }
    
    
   //Individual constructor
   function __construct($contenttype,$inputArray) {
       parent::__construct($contenttype);
       $this->inputArray   = $inputArray;
       $this->xmlwriter      = new MyXmlWriter();
   }
    
   //Writes a normal message
    function write() {
        $this->startSushiEnvelope();
        $this->createMessage();
        
        //CONTENT OF REPORT
        $this->xmlwriter->appendRawXML($this->content); //see $this->content in out.php
        
        $this->endSushiEnvelope();
        
        return $this->xmlwriter->outputMemory();
    }
    
    function writeError($errNo, $errSeverity, $Message, $URL, $Data){
        $this->startSushiEnvelope();
        $this->createErrorMessage($errNo, $errSeverity, $Message, $URL, $Data);
        $this->createMessage();
        $this->endSushiEnvelope();
        
        return $this->xmlwriter->outputMemory();
        
    }
    
}
