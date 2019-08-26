<?php
require_once(dirname(__FILE__).'/dataformat.php');

/**
 * Json Export Class
 *
 * @package output
 * @version 0.2
 */
class JsonFormat extends DataFormat
{

	function __construct($paramInstance,$dataObject)
	{
		parent::__construct($paramInstance,$dataObject);
		$this->contentType = 'json';
	}

	/**
	 * Creates json data depending on the data object
	 *
	 * @param $dataObject  the object holding the data
	 */
	public function formatData()
	{
		if ($this->dataObject instanceof UsageData && ($this->paramInstance->getValue('jsonheader'))) {
			$this->formatUsageData();
		}
		else {
			$this->formatArrayData();
		}
	}

	/**
	 * Encodes the given array to a json string
	 *
	 * @param $data  the given array
	 * @return  the formatted data
	 */
	private function formatArrayData()
	{
		$json = json_encode($this->dataObject->getData(), JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

	    return $this->formattedData = $json;
	}

	/**
	 * Encodes the given data with additional informations to a json string
	 *
	 * @param $data  	the given array
	 * @param $from  	the start date
	 * @param $until 	the end data
	 * @return  the formatted data
	 */
	private function formatUsageData()
	{
		// preparing the array data
		$expandedData = array();
		$expandedData['from'] = $this->dataObject->getFrom();
		$expandedData['until'] = $this->dataObject->getUntil();
		$expandedData['granularity'] = $this->dataObject->getGranularity();
		if ($classifications = $this->dataObject->getClassifications()) {
			if ($classifications[0] != 'all') {
				$expandedData['classifications'] = $this->dataObject->getClassifications();
			}
		}
		if ($this->dataObject->getInformationalData()) {
			$expandedData['informational'] = $this->dataObject->getInformationalData();
		}
		$data = $this->dataObject->getData();
		$expandedData['entrydef'] = array_keys($data[0]); // entry definitions
		$expandedData['entries'] = $data; // the data

		$json = json_encode($expandedData, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

	    return $this->formattedData = $json;
	}
}
?>