<?php
/*
	DATABASE CONFIGURATION
 */
	$config['db']=array(
		'host' => 'localhost',
		'database' => 'oas_all',
		'user' => 'oas_all',
		'password' => 'oas_all',
	);

/*
	OUTCLI CONFIGURATION
 */
	$config['outcli']=array(
	'path' => '/var/www/files/');

/*
	REST CONFIUGRATION
 */
	$config['rest']=array(
		'allowedMethods' => array('GET'),
		'superHTTPUser' => 'OAS_SP');

/*
	LOGGER CONFIGURATION
	multiple logger outputs can be used, e.g. one output logs only errors,
	one everything
 */

	/* REST loggers */

	$config['loggers'][] = array(
		'useIn' => 'rest',
		'path' => '/home/oas/logs/rest_errors',
		'minLevel' => 0,
		'maxLevel' => 2,
	);

	$config['loggers'][] = array(
		'useIn' => 'rest',
		'path' => '/home/oas/logs/rest',
		'minLevel' => 0,
		'maxLevel' => 4,
	);


	/* outcli loggers */

	$config['loggers'][] = array(
		'useIn' => 'outcli',
		'path' => '/home/oas/logs/outcli_errors',
		'minLevel' => 0,
		'maxLevel' => 2,
	);

	$config['loggers'][] = array(
		'useIn' => 'outcli',
		'path' => '/home/oas/logs/outcli',
		'minLevel' => 0,
		'maxLevel' => 4,
	);


	/* sushi loggers */

	$config['loggers'][] = array(
		'useIn' => 'sushi',
		'path' => '/home/oas/logs/sushi_errors',
		'minLevel' => 0,
		'maxLevel' => 2,
	);

	$config['loggers'][] = array(
		'useIn' => 'sushi',
		'path' => '/home/oas/logs/sushi',
		'minLevel' => 0,
		'maxLevel' => 4,
	);
?>