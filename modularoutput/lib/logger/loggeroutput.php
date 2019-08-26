<?php
/**
 * Provides an output for a seperate logger class
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @version 0.1
 */
abstract class LoggerOutput
{
	// the maximum log level
	private $maximumLogLevel;
	// the minimum log level
	private $minimumLogLevel;

	function __construct()
	{
		// setting minimum and maximum log level to default values
		$this->setMaximumLogLevel(4);
		$this->setMinimumLogLevel(0);
	}

	abstract public function writeLogEntry($message, $logLevel);

	/**
	 * Sets the maximum log level, only messages with the same log level or below are displayed
	 *
	 * @param $logLevel the maximum log level
	 */
	public function setMaximumLogLevel($logLevel)
	{
		$this->maximumLogLevel = $logLevel;
	}

	/**
	 * Sets the minimum log level, only messages with the same log level or above are displayed
	 *
	 * @param $logLevel the minimum log level
	 */
	public function setMinimumLogLevel($logLevel)
	{
		$this->minimumLogLevel = $logLevel;
	}

	/**
	 * Checks if a log level is acceptable
	 *
	 * @param $logLevel the log level which is checked
	 */
	protected function checkLogLevel($logLevel)
	{
		return ($logLevel >= $this->minimumLogLevel && $logLevel <= $this->maximumLogLevel);
	}
}