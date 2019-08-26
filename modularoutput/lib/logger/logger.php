<?php
/**
 * A logger working with different log levels and different output modules
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @version 0.1
 */
class Logger {
	// object(s) which are needed to actually output the log somewhere (e.g. file or screen)
	private $outputObjects = array();

	// the log levels
	private $logLevels = array( 
		0 => 'UNKNOWN',	// unknown error
		1 => 'FATAL', 	// an unhandleable error that results in a program crash
		2 => 'ERROR',	// a handleable error condition
		3 => 'WARNING',	// a warning
		4 => 'INFO',	// generic (useful) information about system operation
		5 => 'DEBUG',	// low-level information for developer
		);

	/**
	 * Creates log message and calls output module(s) to output this message
	 *
	 * @param $message 	the logmessage
	 * @param $logLevel the loglevel of the message
	 */
	public function log($message, $logLevel)
	{
		// no putput if no output modules are set
		if (empty($this->outputObjects)) {
			return false;
		}

		// if the log message consists of an array, it can be echoed as well
		// (should only be used for debugging purposes, as it uses output buffering
		// which is not optimal performance-wise)
		if (is_array($message)) {
			if ($logLevel<5) {
				$this->log("An array is logged in a non-debug log message. This should be avoided
					as buffering a var_dump() is not optimal performance-wise.",3);
			}
			ob_start();
			var_dump($message);
			$message = ob_get_clean();
		}

		// set final log message with log level and actual time
		$logMessage = date('y-m-d H:i:s') . ' ' . $this->logLevels[$logLevel] .  ': ' . $message . "\n";

		// output this message for each output module
		foreach ($this->outputObjects as $outputObject) {
			$outputObject->writeLogEntry($logMessage, $logLevel);
		}
	}

	/**
	 * Setter for the output modules, can be called multiple times to set multiple outputs
	 *
	 * @param $outputObject an instance of an output class
	 */
	public function setOutput($outputObject)
	{
		$this->outputObjects[] = $outputObject;
	}
}

?>