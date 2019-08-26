<link rel="stylesheet" href="styles/usageData.css" type="text/css" media="screen" />
<?php
require 'config.php';
require 'classes/UsageDataModel.php';

if (array_key_exists('id', $_GET)) $id = htmlspecialchars($_GET['id']); else die('Kein Dokumentidentifier &uuml;bergeben.');
if (array_key_exists('title', $_GET)) $title = ' ' . htmlspecialchars($_GET['title']); else $title = '';

$Model = new UsageDataModel();
$return = $Model->getUsageData($id);
$count = $return['count'];

if (strlen($id) < 4) {
	echo "<p class=\"error\">Ung&uuml;ltiger Dokumentidentifier: &bdquo;$id&ldquo;</p>";
} else if ($count == 0) {
	echo "<p class=\"hint\">F&uuml;r $id sind keine Nutzungsdaten verf&uuml;gbar.</p>";
} else {
	echo $return['text'];
?>
<script src="scripts/raphael.js" type="text/javascript" charset="utf-8"></script>
<script src="scripts/chart.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
	$(document).ready(function() {
	
		var chart = new Chart('chart', $('#chartbox').width() - 8, 290, 'data', new Array('counter', 'ifabc')),
			days = <?php echo ($count >= 90 ? 90 : ($count >= 30 ? 30 : $count) ) ?>;
		
   		chart.draw(days);

		$(".chartbox .standards a").click(function(){
			// Toggle class "active" for menu elements
			$(".chartbox .standards a").removeClass("active");
	        $(this).addClass("active");

			// Show appropriate chart
			// chart = new Chart('chart', 'data', 'label', $(this).text().toLowerCase());
	        chart.draw(days, $(this).text().toLowerCase());
	    });

    	$(".chartbox .submenu p.period a").click(function(){
			// Toggle class "active" for submenu elements
			$(".chartbox .submenu p.period a").removeClass("active");
	        $(this).addClass("active");

	     	// Get number of days directly from HTML
	        days = $(this).text().split(' ', 2)[0];
			if (days == 'Alles') days = <?php echo $count ?>;
	        chart.draw(days);
		});

    	var linkText = null; 
		$(".chartbox .foot a.info").click(function(){
			if ($(".chartbox .foot p").is(':hidden')) {
				linkText = $(this).text();
				$(this).append(' ausblenden');				
			} else {
				$(this).text(linkText);
			}
			$(".chartbox .foot p").slideToggle('slow');
		});

		$('a[href^="http://"]').attr("target", "_blank");

	});
</script>

<div class="chartbox">
	<h1>Nutzungsstatistik f&uuml;r <?php echo $title ?></h1>
<!--	<a class="close" href="javascript:void(0);"></a>-->
	<div id="chart"></div>
	<div class="submenu">
		<p class="period">Zeitraum:
			<?php
			if ($count > 30) echo '<a ' . ($count < 90 ? 'class="active" ' : '') . 'href="javascript:void(0);">30 Tage</a>';
			if ($count > 90) echo '<a ' . ($count >= 90 ? 'class="active" ' : '') . 'href="javascript:void(0);">90 Tage</a>';
			if ($count > 365) echo '<a href="javascript:void(0);">365 Tage</a>';
			?>
			<a href="javascript:void(0);">Alles (<?php echo $count ?> Tage)</a>
		</p>
<!--		<p class="source">Datenquellen:<a href="javascript:void(0);">national*</a><a class="active" href="javascript:void(0);">international</a><a class="plus" href="javascript:void(0);">Verlage</a></p>-->
<!--		<p class="small">* Berlin, DNB, Göttingen, Saarbrücken, Stuttgart</p>-->
	</div>
	<div class="foot">
		<a class="info" href="javascript:void(0);">Weitere Informationen</a>
		<p>
			Die dargestellten Zahlen wurden seit August 2009 im Rahmen des Projektes
			<a href="http://www.dini.de/projekte/oa-statistik">Open-Access-Statistik (OA-S)</a> erhoben, dessen Ziel die
			Erfassung und Verarbeitung sich über verschiedenartige Repositorien erstreckender Nutzungsdaten und -statistiken ist.<br />
			Die Nutzungszahlen lassen sich gemäß der Standards
			<a href="http://www.projectcounter.org/index.html">COUNTER</a> (Voreinstellung),
			<a href="http://www.ifabc.org/">IFABC</a> oder
			<a href="http://logec.repec.org/">LogEc</a> darstellen,
			wobei letzterer nur monatsweise Granularität bietet.
		</p>
		<p class="standards">
			<a class="active" href="javascript:void(0);">COUNTER</a>
			<a href="javascript:void(0);">IFABC</a>
			LogEc (noch nicht verfügbar)
		</p>
	</div>
</div>
<?php } ?>
