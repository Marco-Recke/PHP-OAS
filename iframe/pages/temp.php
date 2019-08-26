<?php
$id = "";
if (isset($_GET['id'])) $id = htmlspecialchars($_GET['id']);
?>

<script src="scripts/jquery.js" type="text/javascript" charset="utf-8"></script>

<form id="search" action="javascript:void(0);">
	<fieldset>
		<label for="identifier">Identifier: </label>
		<input id="identifier" name="identifier" type="text" value="<?php echo $id ?>" />
		<input id="submit" type="submit" value="Nutzungsstatistik anzeigen" />
	</fieldset>
</form>

<div id="chartbox"><!-- Rendered chart goes here --></div>

<h3>Beispiel-Identifier</h3>
<p class="insert">gbv:goe_diss:244376042</p>
<p class="insert">gbv:goe_diss:330005553</p>
<p class="insert">http://webdoc.sub.gwdg.de/diss/2007/achten/</p>
<p class="insert">http://webdoc.sub.gwdg.de/diss/2009/gajic/</p>

<p>
Bitte beachten Sie, dass die Ladezeit aufgrund begrenzter
Rechenkapazität gegenwärtig bei bis zu 10 s liegen kann.
</p>

<script type="text/javascript">
$(document).ready(function() {
	
	// Submit button issues AJAX call
	$("#search").submit(function() {
		var id = $('#identifier').val();
		if (id > '') {
			$('#chartbox').html('<p>Einen Moment bitte...<br /><img src="images/gears.gif" width="64" height="47" alt="" /></p>');
			$.ajax({
				url: 'usageDataChart.php?id=' + id + '&title=' + id,
				cache: false,
				success: function(html){
					$("#chartbox").html(html);
				}
			});
		}
	});

	// Inserts clicked example identifier into input field
	$('p.insert').click(function() { 
		$('#identifier').val($(this).html());
	});
	
	<?php if ($id != "") echo '$("#search").submit();' ?>

});
</script>
