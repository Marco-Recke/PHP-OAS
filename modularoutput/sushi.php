<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
date_default_timezone_set('UTC'); // stupid PHP

require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/lib/logger/logger.php');
require_once(dirname(__FILE__).'/lib/myxmlwriter.php');
require_once(dirname(__FILE__).'/lib/logger/fileloggeroutput.php');
require_once(dirname(__FILE__).'/lib/logger/screenloggeroutput.php');
require_once(dirname(__FILE__).'/lib/input/params_sushi.php');
require_once(dirname(__FILE__).'/lib/data/database.php');
//require_once(dirname(__FILE__).'/lib/data/datafetcher.php');
require_once(dirname(__FILE__).'/lib/data/datafactory.php');
require_once(dirname(__FILE__).'/lib/format/formatfactory.php');
require_once(dirname(__FILE__).'/lib/output/outhttp.php');
require_once(dirname(__FILE__).'/lib/output/outsushi.php');
require_once(dirname(__FILE__).'/lib/output/statuscodetranslator.php');

//require_once(dirname(__FILE__).'/lib/output/errorhandlerfile.php');

class WrongRequestException     extends Exception {}

//Create global logger
$logger = new Logger();

foreach ($config['loggers'] as $loggerConfig) {
    if ($loggerConfig['useIn'] == 'sushi') {
        $loggerOutput = new FileLoggerOutput($loggerConfig['path']);
        $loggerOutput->setMaximumLogLevel($loggerConfig['maxLevel']);
        $loggerOutput->setMinimumLogLevel($loggerConfig['minLevel']);
        $logger->setOutput($loggerOutput);
    }
}


/**
 * 
 * errorHandler for HTTP Requests
 * Logs and sends http status codes and a json formatted error message to the REST interface and aborts program
 * TODO: Not sure, if this should be a generalized function? Does it belong here?
 * 
 * @param logger $logger logger instance
 * @param exception $e  the exception
 * @param string $format format: csv/excelxml/counterxml/json
 */
function errorHandler($logger,$e,$format, $inputArray)
{
    
    $logger->log("1", 0);
    
       $soapErrNo       = StatusCodeTranslator::translateSushiException($e);
       $soapErrSeverity = StatusCodeTranslator::translateSushiExceptionSeverity($e); 
       $soapErrMsg      = $e->getMessage();
       $logger->log("1", 0);
       $out = new outsushi("xml", $inputArray);
       $logger->log("1", 0);
       $fullSoapMsg = $out->writeError($soapErrNo, $soapErrSeverity, $soapErrMsg, "http://www.niso.org/apps/group_public/download.php/10253/Z39-93-2013_SUSHI.pdf", "");
        $logger->log("1", 0);
        //log
        $logger->log($fullSoapMsg,1);
	$logger->log("SUSHI" . '-response was sent. SUSHI status code: ' . $soapErrNo . '. Content: ' . $errMsg,1);
	
        
        return new SoapVar ( $fullSoapMsg , XSD_ANYXML);
}


 //helper function to convert a stdClass to an array
function objectToArray($d) {
    if(is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if(is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }else{
        // Return array
        return $d;
    }
}


//THIS FUNCTION WILL BE CALLED FROM MAIN ENTRY FOR EVERY SUSHI REQUEST; THIS
//IS THE "MAIN" FUNCTION. MAIN ENTRY IS LOCATED UNDER THIS FUNCTION!
function GetReport($input){
    global $logger;
    global $config;
    
    $format = "counterxml"; //stupid, but needed for fallback
    $logger->log("Starting SUSHI output...", 0);
    $inputarray = objectToArray($input);
    
    // create database connection / database details in config file
    try {
            $logger->log('Setting up new database connection', 4);
            $db = new Database($config['db']);
    } catch (Exception $e) {
        return(errorHandler($logger, $e, $format, $inputarray));
    }
    
    try{
            $paramInstance = new params_sushi($db, $inputarray,$logger);
    }catch(Exception $e){
        return(errorHandler($logger, $e, $format, $inputarray));
    }
    
    //TRY Block for inconsistens or strange incoming messages
    try {
        $logger->log("Parameter: ".$paramInstance->explainParameterTable(),4);
        
        $format    = $paramInstance->getValue('format');
        $contentVersion = $paramInstance->getValue('formatVersion');
        
        $logger->log("Requested Format: $format V.$contentVersion!", 0);
        
        // fetches the corresponding data for the params given
        $dataObject = DataFactory::createDataObject($db,$paramInstance,$logger);
        $dataObject->fetchUsageData();
        
//        // fetches data
//        $dataFetcher = new DataFetcher($db,$logger,$paramInstance);
//        $dataObject  = $dataFetcher->returnDataObject();
//        
        // formats data
        $formatObject  = FormatFactory::createFormatObject($format,$dataObject);
        $formattedData = $formatObject->getFormattedData();
        
        // prepare output
        $sushiout = new outsushi('xml',$inputarray);
        
        $sushiout->setContent($formattedData);
        $formattedData = $sushiout->write(); //Prepend SUSHI Envelop!
        
        //Sending
        $logger->log('Writing SUSHI output in format ' . $contentType, 4);
        return new SoapVar ( $formattedData , XSD_ANYXML);
    } catch (Exception $e) {
            
            //return errormessage
            return errorHandler($logger,$e,$format,$inputarray);
    }
    
}

// *************************
//MAIN ENTRY POINT
// *************************
$soapsrv = new SoapServer("./lib/input/wsdl/counter_sushi4_0.wsdl");
$soapsrv->addFunction("GetReport");
$soapsrv->handle();

?>
