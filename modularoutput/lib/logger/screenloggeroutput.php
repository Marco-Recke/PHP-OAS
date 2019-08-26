<?php 
require_once(dirname(__FILE__).'/loggeroutput.php');

/**
 * A screen output for a seperate logger class
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @version 0.1
 */
class ScreenLoggerOutput extends LoggerOutput
{
	/**
	 * Writes a log entry dependent on the log level
	 *
	 * @param $message 	the message which is logged
	 * @param $logLevel the log level which is checked
	 */
	public function writeLogEntry($message, $logLevel)
	{
		if ($this->checkLogLevel($logLevel)) {
			echo $message;
		}
	}
}