<?php

// XML namespaces
define('XMLNS_CTX', 'info:ofi/fmt:xml:xsd:ctx');
define('XMLNS_SERVICE', 'info:ofi/fmt:xml:xsd:sch_svc');
define('XMLNS_SCHEMA', 'http://www.w3.org/2001/XMLSchema-instance');
define('XMLNS_OASA', 'http://dini.de/namespace/oas-admin');
define('XMLNS_OASI', 'http://dini.de/namespace/oas-info');
define('XMLNS_OASRI', 'http://dini.de/namespace/oas-requesterinfo');

// XML schema definitions
define('XMLSCHEMA_CTX', 'http://www.openurl.info/registry/docs/xsd/info:ofi/fmt:xml:xsd:ctx');

// Model, Table DataProvider, Col Granularity:
define('OAIPMH2_GRANULARITY_DAYS',1);
define('OAIPMH2_GRANULARITY_SECONDS',2);

// Model, Table Harvest, Col Status:
define('OAS_SP_HARVEST_STATUS_RUNNING',1);
define('OAS_SP_HARVEST_STATUS_DONE_OK',10);
define('OAS_SP_HARVEST_STATUS_DONE_ERROR',11);

// Model, Table HarvestError, Col Code:
define('OAS_SP_HARVESTERROR_GENERAL',1);

// Model, Table HarvestData, Col Status:
define('OAS_SP_HARVESTDATA_STATUS_HARVESTED', 0);
define('OAS_SP_HARVESTDATA_STATUS_HARVESTED_TMP', 10);
define('OAS_SP_HARVESTDATA_STATUS_HARVESTED_ERR', 20);
define('OAS_SP_HARVESTDATA_STATUS_PARSING', 1);
define('OAS_SP_HARVESTDATA_STATUS_DONE', 2);
define('OAS_SP_HARVESTDATA_STATUS_ERROR', 3);

// Calculate Status
define('OAS_SP_CALCULATE_STATUS_OK', 0);
define('OAS_SP_CALCULATE_STATUS_ERROR', 1);

// Aggregate Status
define('OAS_SP_AGGREGATE_STATUS_OK', 0);
define('OAS_SP_AGGREGATE_STATUS_ERROR', 1);


?>
