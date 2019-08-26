<?php
require_once(dirname(__FILE__).'/dataformat.php');

/**
 * CSV Export Class
 * 
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @package modularoutput
 * @version 0.2
 */
class CsvFormat extends DataFormat
{
	function __construct($paramInstance, $dataObject)
	{
		parent::__construct($paramInstance, $dataObject);
		$this->contentType = 'csv';
	}

	/**
	 * Creates csv data
	 *
	 * @return $csv the csv formatted data
	 */
	function formatData()
	{
		$data = $this->dataObject->getData();
	    // adding the array keys to the data
	    $captions = array_keys($data[0]);
	    array_unshift($data,$captions);

	    // save all output in buffer
	  	ob_start();

	  	// using phps own csv file writer function with output to the buffer
		$fp = fopen("php://output", 'w');

		foreach ($data as $fields) {
                    fputcsv($fp, $fields,";");
		}
		fclose($fp);

		$this->formattedData = ob_get_contents();
		ob_get_clean();
		// return the buffer
		return $this->formattedData;
	}
}
?>