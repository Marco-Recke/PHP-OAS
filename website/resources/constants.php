<?php

// attention: define all absolute and relative URLs with closing '/'

define('BASE_URL_WITHOUTHTTP', 'oase.gbv.de/');
define('API_PATH_RELATIVE', 'api/alpha/');
define('BASE_URL','https://' . BASE_URL_WITHOUTHTTP);
define('API_PATH',BASE_URL . API_PATH_RELATIVE);
define('API_CONFIG_PATH', '../' . API_PATH_RELATIVE . 'config.php');
define('TEMPLATE_FOLDER','resources/templates/');

define('GRAPHPROVIDER_URL','graphprovider_sp/');
define('APIDOC_URL','../api/alpha/docs/');

define('DEFAULT_PAGE_TITLE','Open-Access-Statistik');
define('PAGE_TITLE_SEPARATOR',' | ');
define('META_AUTHOR','VZG / Open-Access-Statistik');
define('META_DESCRIPTION','Interne Website für Data-Provider');
define('CONTACT_MAIL','oas@gbv.de');
define('VZG_PATH','https://www.gbv.de/Verbundzentrale');

?>