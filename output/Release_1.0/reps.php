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


  $reps[] = array(
    'id' => 1,
    'dir' => 'econstor/',
    'identifiers' => 'oai:econstor.eu%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
    ),
    'targz' => true
  );
  
  $reps[] = array(
    'id' => 2,
    'dir' => 'qucosa/',
    'identifiers' => 'urn:nbn:de:swb%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',      
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
    ),
    'targz' => true
  );

  $reps[] = array(
    'id' => 3,
    'dir' => 'DSpace/',
    'identifiers' => 'oai:goedoc.uni-goettingen.de%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
    ),
    'targz' => true
  );
  
  $reps[] = array(
    'id' => 4,
    'dir' => 'pub_Bielefeld/',
    'identifiers' => 'oai:pub.ub.uni-bielefeld.de%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
    ),
    'targz' => true
  );

  $reps[] = array(
    'id' => 12,
    'dir' => 'berlin/',
    'identifiers' => 'oai:HUBerlin.de:%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
    ),
    'targz' => true
  );


  $reps[] = array(
    'id' => 10,
    'dir' => 'demo_dp/',
    'identifiers' => 'http://goedoc.uni-goettingen.de/goescholar/handle%',
    'format' => 'json',
    'period' => 'day',
    'time-per-file' => 'week',
    'allStandards' =>array(
                  'counter',
                  'counter_abstract',
                  'robots'
      ),
    'targz' => true
    );

  $reps[] = array(
    'id' => 11,
    'dir' => 'digizeit/',
    'identifiers' => 'digizeitschriften.de:%',
    'format' => 'counterxml',
    'period' => 'month',
    'time-per-file' => 'year',
    'allStandards' =>array(
                  'counter',
                  'robots'
    ),
    'targz' => false,
    //necessary for xml:
    'xmlInformation' => array(
      'Report' => array (
          'Name' => 'JR1',
      ),
      'Customer' => array (
          'Name' => 'DigiZeitschriften',
          'ID' => '',
          'WebSiteUrl' => 'http://www.digizeitschriften.de/'
      ),
      'ItemPlatform' => 'DigiZeitschriften',
      'ItemDataType' => 'Journal',
      'ItemPerformance_Category' => 'Requests',
      'ItemPerformance_Instance_MetricType' => array(
          'ft_total' => 'counter'
      ),
    ),
  );
  
  $reps[] = array(
    'id' => 13,
    'dir' => 'univerlag/',
    'identifiers' => 'http://webdoc.sub.gwdg.de/univerlag/%',
    'period' => 'day',
    'time-per-file' => 'month',
    'format' => 'csv',
    'allStandards' =>array(
                  'counter',     
                  'robots'
    ),
    'targz' => false,
    'xmlInformation' => array(
      'Report' => array (
          'Name' => 'JR1',
      ),
      'Customer' => array (
          'Name' => 'Universitätsverlag Göttingen',
          'ID' => '',
          'WebSiteUrl' => 'http://www.univerlag.uni-goettingen.de/'
      ),
      'ItemPlatform' => 'Universitätsverlag Göttingen',
      'ItemDataType' => 'Book',
      'ItemPerformance_Category' => 'Requests',
      'ItemPerformance_Instance_MetricType' => array(
          'ft_total' => 'counter'
      ),
    ),
  );
?>
