<?php
// FOR DEBUGGING PURPOSES
error_reporting(E_ALL);
ini_set('display_errors', 'on');

date_default_timezone_set('UTC'); // stupid PHP

require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/lib/data/database.php');
require_once(dirname(__FILE__).'/lib/logger/logger.php');
require_once(dirname(__FILE__).'/lib/myxmlwriter.php');
require_once(dirname(__FILE__).'/lib/logger/fileloggeroutput.php');
require_once(dirname(__FILE__).'/lib/logger/screenloggeroutput.php');
require_once(dirname(__FILE__).'/lib/input/params_cli.php');
require_once(dirname(__FILE__).'/lib/data/datafactory.php');
require_once(dirname(__FILE__).'/lib/format/formatfactory.php');
require_once(dirname(__FILE__).'/lib/output/outfile.php');
require_once(dirname(__FILE__).'/lib/output/errorhandlerfile.php');

$logger = new Logger();
$screenLogger = new ScreenLoggerOutput();
$logger->setOutput($screenLogger);

foreach ($config['loggers'] as $loggerConfig) {
	if ($loggerConfig['useIn'] == 'outcli') {
		$loggerOutput = new FileLoggerOutput($loggerConfig['path']);
		$loggerOutput->setMaximumLogLevel($loggerConfig['maxLevel']);
		$loggerOutput->setMinimumLogLevel($loggerConfig['minLevel']);
		$logger->setOutput($loggerOutput);
	}
}

// $logger->setOutput($fileLogger);

// create database connection / database details in config file
try {
	$logger->log('Setting up new database connection', 4);
	$db = new Database($config['db']);
} catch (Exception $e) {
    ErrorHandlerFile::handleError($logger,$e);
}
try {
	// input
	$paramInstance = new params_cli($db);
} catch (Exception $e) {
    //If an error occurs in constructor of params_cli,
    //we can assume an input error. Show Helptext.
    $logger->log(params_cli::getHelpText(),4);
    ErrorHandlerFile::handleError($logger,$e);
}

try{
    $logger->log("Parameter: ".$paramInstance->explainParameterTable(),4);

	// fetches the corresponding data for the params given
    $dataObject = DataFactory::createDataObject($db,$paramInstance,$logger);
    $dataObject->fetchData();

	// formats data
	$formatObject = FormatFactory::createFormatObject($paramInstance,$dataObject);
	$formattedData = $formatObject->getFormattedData();
	$contentType = $formatObject->getContentType();

	// prepare output
	$logger->log('Starting new file output in format ' . $contentType, 4);
	$output = new outfile($contentType);
	$output->setContent($formattedData);

	$fileName = $formatObject->getFileName();

	// folders are named after the id and the identifier
	if ($dataObject instanceof UsageData) {
		$fileName = FileProcessing::createFilePath($config['outcli']['path'],$paramInstance->getValue('id').'/'.
		preg_replace('/[^a-zA-Z0-9_.-]/', '', $paramInstance->getValue('identifier')),
		$fileName, $dataObject->getFrom());
	} else {
		$fileName = FileProcessing::createFilePath($config['outcli']['path'],$paramInstance->getValue('id'), $fileName);
	}


    $output->setFileName($fileName);
	$logger->log('Filename ' . $fileName . ' is set', 4);
	$output->setOverwrite($paramInstance->getValue('overwrite'));

	// output
	$logger->log('Writes output to ' . $fileName, 4);
	$output->write();
} catch (Exception $e) {
	ErrorHandlerFile::handleError($logger,$e);
}
?>