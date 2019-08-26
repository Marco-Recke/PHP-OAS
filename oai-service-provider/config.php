<?php
// we need this to set timezone to UTC everywhere
date_default_timezone_set("UTC");

/*! configuration for the Service Provider */
$config=array(
	// database configuration
	'dsn' => 'mysql:host=localhost;dbname=oas_hh',
	'user' => 'oas_hh',
	'password' => 'oas_hh',
	// 'tp'=>'prod_', // table prefix
);

?>
