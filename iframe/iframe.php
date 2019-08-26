<?php
// error_reporting(E_ALL);
date_default_timezone_set('Europe/Berlin');

$page = 'start';
if (isset($_GET['p'])) $page = urlencode($_GET['p']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>OA-Statistik Demoseite</title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
	<meta name="Robots" content="noindex, nofollow" />
	<link rel="stylesheet" href="styles/styles.css" type="text/css" media="screen" />
</head>

<body>
			
<?php
$id = "";
if (isset($_GET['id'])) $id = htmlspecialchars($_GET['id']);
?>
<script src="scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<div id="chartbox"><?php  require "usageDataChart.php"  ?></div>

</html>
