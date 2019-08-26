<?php
require_once(dirname(__FILE__).'/../filehandling/fileprocessing.php');

/**
 * Abstract Class for formatting data to different specifications
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @package modularoutput 
 * @version 0.1
 */
abstract class DataFormat
{
	// the content type
	protected $contentType;

	// the formatted data
	protected $formattedData;

	// the params object with potential informations how to format
	protected $paramInstance;

	// the data object which will be formatted
	protected $dataObject;

	public function __construct($paramInstance,$dataObject)
	{
		$this->paramInstance = $paramInstance;
		$this->dataObject = $dataObject;
	}

	public function getContentType()
	{
		return $this->contentType;
	}

	public function getFormattedData()
	{
		if (!$this->formattedData) {
			$this->formatData($this->dataObject);
		}
		return $this->formattedData;
	}

	/**
	 * Returns filename depending on the data type
	 */
	public function getFileName()
	{
		if ($this->dataObject instanceof UsageData) {
			return FileProcessing::createFileNameFromDates($this->dataObject->getFrom(),$this->dataObject->getUntil(),$this->getContentType());	
		} else {
			return FileProcessing::createFileNameFromWord($this->dataObject->getDataType(),$this->getContentType());
		}
	}
}

?>