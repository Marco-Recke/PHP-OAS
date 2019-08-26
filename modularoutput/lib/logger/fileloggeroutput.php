<?php
require_once(dirname(__FILE__).'/loggeroutput.php');
require_once(dirname(__FILE__).'/../filehandling/fileprocessing.php');

/**
 * A file output for a seperate logger class
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @version 0.1
 */
class FileLoggerOutput extends LoggerOutput
{
	// the file name
	private $fileName = false;
 	// the file handler class
	private $fileHandler;

	/**
	 * Sets or creates a file name dependent on the argument and opens the file
	 *
	 * @param $fileNameOrStub a filename or a base for a filename
	 */
	function __construct($fileNameOrStub = 'log')
	{
		parent::__construct();

		if (is_file($fileNameOrStub)) {
			$this->setFileName($fileNameOrStub);
		} else {
			$this->createFileName($fileNameOrStub);
		}

		// new file handler instance is created and file is opened for appending
		$this->fileHandler = new FileProcessing($this->fileName);
		$this->fileHandler->openFile('a');
	}

	/**
	 * Writes a log entry dependent on the log level
	 *
	 * @param $message 	the message which is logged
	 * @param $logLevel the log level which is checked
	 */
	public function writeLogEntry($message, $logLevel)
	{
		if ($this->checkLogLevel($logLevel)) {
			$this->fileHandler->write($message);
		}
	}

	public function setFileName($fileName)
	{
		$this->fileName = $fileName;
	}

	/**
	 * Creates a filename with the given name and the actual time
	 *
	 * @param $stub the base of the filename
	 */
	private function createFileName($stub)
	{
		$this->fileName = $stub . "_" . date('Y-m-d') . '.log';
	}

	/**
	 * Closes the file when instance is destroyed
	 */
	public function __destruct()
	{
		$this->fileHandler->closeFile();
	}
}