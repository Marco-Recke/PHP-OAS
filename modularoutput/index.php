<?php
// FOR DEBUGGING PURPOSES
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');

date_default_timezone_set('UTC');


require_once (dirname(__FILE__).'/lib/exceptionthrower.php');
ExceptionThrower::Start();


require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/lib/data/database.php');
require_once(dirname(__FILE__).'/lib/logger/logger.php');
require_once(dirname(__FILE__).'/lib/myxmlwriter.php');
require_once(dirname(__FILE__).'/lib/logger/fileloggeroutput.php');
// require_once(dirname(__FILE__).'/lib/logger/screenloggeroutput.php');
require_once(dirname(__FILE__).'/lib/input/params_rest.php');
require_once(dirname(__FILE__).'/lib/data/datafactory.php');
require_once(dirname(__FILE__).'/lib/format/formatfactory.php');
require_once(dirname(__FILE__).'/lib/output/outhttp.php');
require_once(dirname(__FILE__).'/lib/output/statuscodetranslator.php');
require_once(dirname(__FILE__).'/lib/output/errorhandlerhttp.php');

class WrongRequestException extends Exception {}

// ***************************
// MAIN entry point
// ***************************

$logger = new Logger();

foreach ($config['loggers'] as $loggerConfig) {
	if ($loggerConfig['useIn'] == 'rest') {
		$loggerOutput = new FileLoggerOutput($loggerConfig['path']);
		$loggerOutput->setMaximumLogLevel($loggerConfig['maxLevel']);
		$loggerOutput->setMinimumLogLevel($loggerConfig['minLevel']);
		$logger->setOutput($loggerOutput);
	}
}

// $fileLogger = new FileLoggerOutput(dirname(__FILE__).'/logs/rest');
// $fileLogger = new FileLoggerOutput($restLogPath);
// $logger->setOutput($fileLogger);
// $format = "json"; //initialisation. Stupid but necessary, if params fails.

try {
	if (!(in_array($_SERVER['REQUEST_METHOD'], $config['rest']['allowedMethods']))) {
		throw new WrongRequestException('Wrong request method: '. $_SERVER['REQUEST_METHOD']);
	}
	$logger->log('New Connection:' .
		' User: ' 			. $_SERVER['PHP_AUTH_USER'] .
		' URI: ' 			. $_SERVER['REQUEST_URI'], 4);

	// create database connection / database details in config file
	$logger->log('Setting up new database connection', 4);
	$db = new Database($config['db']);

	// input
	$paramInstance = new params_rest($db);

    $logger->log("Parameter:".$paramInstance->explainParameterTable(),4);
	$format = $paramInstance->getValue('format');

    // fetches the corresponding data for the params given
    $dataObject = DataFactory::createDataObject($db,$paramInstance,$logger);

    // allow the 'superHTTPUser' as defined in config file information over ALL status data
    if ($dataObject instanceof StatusData && $_SERVER['PHP_AUTH_USER'] == $config['rest']['superHTTPUser']) {
    	$dataObject->setGodMode(true);
    }
    $dataObject->fetchData();

	// formats data
	$formatObject = FormatFactory::createFormatObject($paramInstance,$dataObject);
	$formattedData = $formatObject->getFormattedData();
	$contentType = $formatObject->getContentType();

	// prepare output
	$logger->log('Starting new http output in format ' . $format, 4);
	$output = new outhttp($contentType);
	$output->setContent($formattedData);
	$fileName = $formatObject->getFileName();
	$output->setFileName($fileName);
	$output->setAllowedHTTPMethods($config['rest']['allowedMethods']);
	$logger->log('Filename ' . $fileName . ' is set', 4);


	// output
	$logger->log('Writes output to REST interface', 4);
	$output->write();
} catch (Exception $e) {
	ErrorHandlerHttp::handleError($logger,$e);
}

?>
