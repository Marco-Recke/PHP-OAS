<?php
include('resources/constants.php');

$pageTitle    = DEFAULT_PAGE_TITLE . PAGE_TITLE_SEPARATOR . "Logged Out";
$pageCurrent  = "logout";

include(TEMPLATE_FOLDER . 'header.html');
include(TEMPLATE_FOLDER . 'topnav.html');
include(TEMPLATE_FOLDER . 'sidenav.html');

killSession();

?>

<!-- page content -->

<p>ausgeloggt...</p>

<!-- end page content -->

<?php
include(TEMPLATE_FOLDER . 'jsfiles.html');
?>

<script>logout();</script>

<?php
include(TEMPLATE_FOLDER . 'footer.html');
?>