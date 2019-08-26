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
	<div id="container">
		<div id="container-header">
			<div id="container-name"></div>
			<div id="container-slogan">Demoseite</div>
		</div>
	
		<div id="container-eyecatcher">
			<div id="container-navigation">
				<ul id="navigation">
					<li<?php if ($page == 'start') echo ' class="active"'?>><a href="?p=start">
						Startseite
					</a></li><li<?php if ($page == 'stats') echo ' class="active"'?>><a href="?p=stats">
						Nutzungsstatistik
					</a></li>
				</ul>
			</div>
			<img src="images/logo.png" alt="OAS-Logo" />
		</div>
		<div id="container-content">

                    <!-- CONTENT BEGIN -->

                    <div id="content">
                            <?php 
                            if ($page > '') {
                                    if (file_exists("pages/$page.php")) {
                                            include "pages/$page.php";
                                    } else {
                                            echo "404";
                                    }
                            } else { ?>
                                    <p>404</p>
                            <?php }	?>
                    </div>

                    <!-- CONTENT END -->

			<div id="border">
			</div>
	
		</div>
	
		<div id="container-footer">
			<div id="footer">
				<div id="footer-copyright">
					<a href="mailto:oas@gbv.de?subject=OA-Statistik Demoseite">Kontakt</a> |
					<a href="http://www.sub.uni-goettingen.de/0_impressum.html.de">Impressum</a></div>
				<div id="footer-meta">
					<a href="http://validator.w3.org/check?uri=referer">XHTML</a> |
					<a href="http://jigsaw.w3.org/css-validator/check/referer">CSS</a>
				</div>
			</div>
		</div>		
	</div>
</body>
</html>
