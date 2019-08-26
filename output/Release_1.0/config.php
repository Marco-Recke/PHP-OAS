<?php
  $path="/var/www/";
  
  //Constants for Counter-XML
  define("VENDOR_NAME", "OA-Statistik");
  define("VENDOR_ID", "0");
  define("VENDOR_CONTACT_NAME", "Daniel Beucke");
  define("VENDOR_CONTACT_MAIL", "oas@dini.de");
  define("VENDOR_WEBSITEURL", "http://www.dini.de/projekte/oa-statistik/");
  define("VENDOR_LOGOURL", "");
  define("REPORT_VERSION", "4");

  // Pedocs
  $reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_sp_pae',
      'password' => 'oas_sp_pae',
      'database' => 'oas_sp_pae',
      'prefix' => 'paed_'
    ),
    'id' => 1,
	'internal_id' => 1,
    'key' => 'abc',  
    'dir' => 'pedocs/',
    'identifier' => 'oai:www.pedocs.de-opus:%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

  //GoeScholar
  $reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'goe_sp_scho',
      'password' => 'goe_sp_scho',
      'database' => 'goe_sp_scho',
      'prefix' => 'goe_'
    ),
    'id' => 2,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'goe_scho/',
    'identifier' => 'oai:goedoc.uni-goettingen.de:%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => false,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

 // Econstor
 $reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_econs',
      'password' => 'oas_econs',
      'database' => 'oas_econs',
      'prefix' => 'econ_'
    ),
    'id' => 3,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'econstor/',
    'identifier' => 'oai:econstor.eu%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

// PsyDok
$reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_sp_saar',
      'password' => 'oas_sp_saar',
      'database' => 'oas_sp_saar',
      'prefix' => 'saar_'
    ),
    'id' => 4,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'oas_saar/psydok/',
    'identifier' => 'oai:psydok%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

// SciDok
$reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_sp_saar',
      'password' => 'oas_sp_saar',
      'database' => 'oas_sp_saar',
      'prefix' => 'saar_'
    ),
    'id' => 5,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'oas_saar/scidok/',
    'identifier' => 'oai:scidok%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

// Universaar
$reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_sp_saar',
      'password' => 'oas_sp_saar',
      'database' => 'oas_sp_saar',
      'prefix' => 'saar_'
    ),
    'id' => 6,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'oas_saar/universaar/',
    'identifier' => 'oai:universaar%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

// VZG
$reps[] = array(
    'db' => array(
      'host' => 'localhost',
      'user' => 'oas_vzg',
      'password' => 'vzsp-oas',
      'database' => 'oas_sp_vzg',
      'prefix' => 'prod_'
    ),
    'id' => 7,
	'internal_id' => 1,
    'key' => 'abc',
    'dir' => 'vzg/',
    'identifier' => 'oai:%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'day',
    'allStandards' =>array(
      'counter',
      'counter_abstract',
      'robots',
      'robots_abstract',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    ),
  );

// Edoc HU Berlin
 $reps[] = array(
     'db' => array(
      'host' => 'localhost',
      'user' => 'oas_sp_edoc',
      'password' => 'oas_sp_edoc',
      'database' => 'oas_sp_edoc',
      'prefix' => 'edoc_'
    ),
     
    'id' => 8,
	'internal_id' => 1,
    'key'=>'abc',
    'dir' => 'berlin/',
    'identifier' => 'oai:HUBerlin.de:%',
    'format' => 'csv',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots',
                  'robots_abstrace',
    ),
    'targz' => true,
    'outputDay' => array(
      //daily output get an output everyday, define which day gets an output
      'day' => '-3 days',
      //weekly output gets an output for last week, define on which weekday the output is done (1 = monday, 7 = sunday)
      'week' => '3',
      //monthly output gets an output for last month, define on which day of month the output is done (1-28)
      'month' => '5',
      //yearly output gets an output from 1st of January till last month, define on which day of month the output is done (you should choose a day from 1 to 28)
      'year' => '5',
    )
  );

  // Example for XML - these Informations are necessary:

  //   'xmlInformation' => array(
  //     'Report' => array (
  //         'Name' => 'JR1',
  //     ),
  //     'Customer' => array (
  //         'Name' => 'DigiZeitschriften',
  //         'ID' => '',
  //         'WebSiteUrl' => 'http://www.digizeitschriften.de/'
  //     ),
  //     'ItemPlatform' => 'DigiZeitschriften',
  //     'ItemDataType' => 'Journal',
  //     'ItemPerformance_Category' => 'Requests',
  //     'ItemPerformance_Instance_MetricType' => array(
  //         'ft_total' => 'counter'
  //     ),
  //   );
  
?>
