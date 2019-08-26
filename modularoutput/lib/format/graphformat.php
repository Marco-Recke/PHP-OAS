<?php
require_once(dirname(__FILE__).'/dataformat.php');

/**
 * Graph Export Class
 * 
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @package modularoutput
 * @version 0.1
 */
class GraphFormat extends DataFormat
{
	function __construct($dataObject)
	{
		parent::__construct($dataObject);
		$this->contentType = 'html';
	}

	/**
	 * Creates graphical data
	 * 
	 */
	function formatData()
	{
		$data = $this->dataObject->getData();
		$types = $this->dataObject->getTypes();
		$identifier = $this->dataObject->getIdentifier();

		$highchartPath = 'http://code.highcharts.com/adapters/standalone-framework.js';

		foreach ($types as $type) {
			$typeArray[$type]['name'] = $type;
		}

		// prepare JSON data for script
		foreach ($data as $fields) {
			$labelArray[] = $fields['date'];
			foreach ($types as $type) {
				$typeArray[$type]['data'][] = intval($fields[$type]);
			}
		}
		$typeArray = array_values($typeArray);
		$title = "Statistiken für $identifier";
		$yAxisTitle = "Aufrufe";
		

		$dataArray['chart']['type'] = 'column';
		$dataArray['title']['text'] = $title;
		$dataArray['xAxis']['categories'] = $labelArray;
		$dataArray['yAxis']['min'] = 0;
		$dataArray['yAxis']['allowDecimals'] = false;
		$dataArray['yAxis']['title']['text'] = $yAxisTitle;
		$dataArray['tooltip']['headerFormat'] = '<span style="font-size:10px">{point.key}</span><table>';
		$dataArray['tooltip']['pointFormat'] = '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' .
                    '<td style="padding:0"><b>{point.y:f}</b></td></tr>';
		$dataArray['tooltip']['footerFormat'] = '</table>';
		$dataArray['tooltip']['shared'] = true;
		$dataArray['tooltip']['useHTML'] = true;
		$dataArray['plotOptions']['column']['pointPadding'] = 0.2;
		$dataArray['plotOptions']['column']['borderWidth'] = 0;
		$dataArray['series'] = $typeArray;
		$dataArray['credits'] = false;



	    $this->formattedData = '
	    <!DOCTYPE html>
			<html>
			<head>
				<title></title>

				<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
				<script src="http://code.highcharts.com/highcharts.js"></script>
				<script>
					$(function () { 
    					$("#container").highcharts('.json_encode($dataArray).');
    				});
				</script>
			</head>
			<body>
				<div id="container" style="width:700px; height:400px;"></div>

				
			</body>
			</html>';

		return $this->formattedData;
	}
}

?>