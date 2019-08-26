<?php
require_once(dirname(__FILE__).'/dataformat.php');

/**
 * Graph Export Class
 * 
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @package modularoutput
 * @version 0.1
 */
class GraphFormat2 extends DataFormat
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

		$scriptPath = 'lib/format/Chart.js';
		$legendScriptPath = 'lib/format/legend.js';
		$width = 700;
		$height = 300;
		$fillColor = array(
			'counter' => 'rgba(220,220,220,0.5)',
			'counter_abstract' => 'rgba(151,187,205,0.5)',
			'robots' => 'rgba(120,157,185,0.5)',
			'robots_abstract' => 'rgba(80,112,130,0.5)'
			);
		$strokeColor = array(
			'counter' => 'rgba(220,220,220,1)',
			'counter_abstract' => 'rgba(151,187,205,1)',
			'robots' => 'rgba(120,157,185,1)',
			'robots_abstract' => 'rgba(80,112,130,1)'
			);

		// set color for each type
		foreach ($types as $type) {
			$typeArray[$type]['fillColor'] = $fillColor[$type];
			$typeArray[$type]['strokeColor'] = $strokeColor[$type];
			$typeArray[$type]['title'] = $type;
		}

		// prepare JSON data for script
		foreach ($data as $fields) {
			$labelArray[] = $fields['date'];
			foreach ($types as $type) {
				$typeArray[$type]['data'][] = $fields[$type];
			}
		}
		$typeArray = array_values($typeArray);
		$dataArray['labels'] = $labelArray;
		$dataArray['datasets'] = $typeArray;


	    $this->formattedData = '
	    <!DOCTYPE html>
			<html>
			<head>
				<title></title>
				<script src="'.$scriptPath.'"></script>
				<script src="'.$legendScriptPath.'"></script>
					<style>
						#fork {
						    position: absolute;
						    top: 0;
						    right: 0;
						    border: 0;
						}

						.legend {
						    width: 10em;
						    border: none;
						}

						.legend .title {
						    display: block;
						    margin: 0.5em;
						    border-style: solid;
						    border-width: 0 0 0 1em;
						    padding: 0 0.3em;
						}
					</style>
			</head>
			<body>
				<canvas id="chart" width="'.$width.'" height="'.$height.'"></canvas>
				<div id="lineLegend"></div>
				<script>
				    var data =' 
				    . json_encode($dataArray) .
				    '
				    var chartCanvas = document.getElementById("chart").getContext("2d");
				    var myNewChart = new Chart(chartCanvas).Bar(data);
				    legend(document.getElementById("lineLegend"), data);
				</script>

			</body>
			</html>';

		return $this->formattedData;
	}
}

?>