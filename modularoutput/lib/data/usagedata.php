<?php
require_once(dirname(__FILE__).'/data.php');
require_once(dirname(__FILE__).'/datetimehelper.php');

class UsageDataException extends DataException {}
class UsageDataWrongPeriodException extends UsageDataException {}

/**
*  Usagedata Class
*
*  @package output
*  @version 0.3
*/
class UsageData extends Data
{
	// the database connection
	protected $dbc;
	// the database
	protected $db;
    // the id of the data provider
    protected $id;
  	// the identifier
  	protected $identifier;
  	// the type of data to look for(e.g. counter or robots)
  	protected $types;
    // the classification (e.g. none or administrative)
    protected $classifications = array();
  	// the start value
  	protected $from;
  	// the end value
  	protected $until;
    // the period
    protected $period;
    // the logger object
    protected $logger;
    // the prefix of the database tables
    protected $prefix = '';
    // the params
    protected $paramInstance;
    // further informations which can be retrieved via the corresponding method
    protected $informationalData = false;

    // the different types are defined by their servicetype and their ...type
    protected $typeDefinition = array(
            'counter' => array('servicetype' => 'fulltext', 'type' => 'counter'),
            'counter_abstract' => array('servicetype' => 'abstract', 'type' => 'counter'),
            'robots' => array('servicetype' => 'fulltext', 'type' => 'robots'),
            'robots_abstract' => array('servicetype' => 'abstract', 'type' => 'robots')
        );

    function __construct($db, $paramInstance, $logger)
    {
        $this->setDatabase($db);
        $this->paramInstance = $paramInstance;
        $this->id = $paramInstance->getValue('id');
        $this->prefix = $this->createPrefix($this->id);

        if ($this->paramInstance->issetPossibleParamArray('identifier'))
            $this->identifier = $paramInstance->getValue('identifier');

        if ($this->paramInstance->issetPossibleParamArray('granularity'))
            $this->period = $paramInstance->getValue('granularity');

        if ($this->paramInstance->issetPossibleParamArray('from'))
            $this->setFromToFitPeriod($paramInstance->getValue('from'));

        if ($this->paramInstance->issetPossibleParamArray('until'))
            $this->setUntilToFitPeriod($paramInstance->getValue('until'));

        if ($this->paramInstance->issetPossibleParamArray('content'))
            $this->types = $paramInstance->getValue('content');

        if ($this->paramInstance->issetPossibleParamArray('classification'))
            $this->classifications = $paramInstance->getValue('classification');

        $this->logger = $logger;
    }

    /**
     * Fetches the usagedata from the rollup table depending on the granularity given
     */
    public function fetchData()
    {
        if ($this->paramInstance->getValue('informational'))
            $this->retrieveInformationalData();

        $dateOutput = $this->determineDateOutput();

        $typesQuery = $this->determineTypesQuery();

        $noZerosQuery = $this->determineNoZerosQuery();

        $query = "SELECT
                DISTINCT identifier, {$dateOutput} AS date {$typesQuery}
            FROM
                {$this->prefix}Identifier AS I
            LEFT JOIN
                {$this->prefix}UsageData_{$this->period} AS U
            ON
                I.id=U.identifierid
            WHERE
                identifier LIKE :identifier
                AND date>=:from
                AND date<=:until
        	HAVING {$noZerosQuery} > 0
        	ORDER BY identifier,date";

        $stmt = $this->dbc->prepare($query);

        $stmt->execute(array('identifier' => $this->identifier, 'from' => $this->from, 'until' => $this->until));

        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //No data found, and fuzzy-search
        if (empty($this->data) && !$this->paramInstance->getValue('isexactsearch')) {
            throw new DataEmptyException('No usage data found');
        }

        //No data found, and fuzzy-search
        if (empty($this->data) && $this->paramInstance->getValue('isexactsearch') && !$this->paramInstance->getValue('addemptyrecords')) {
            throw new DataEmptyException('No usage data found');
        }


        if (empty($this->data) && $this->paramInstance->getValue('isexactsearch')) {
            $stmt = $this->dbc->prepare("SELECT id FROM {$this->prefix}Identifier WHERE identifier = :identifier LIMIT 1");
            $stmt->execute(array('identifier' => $this->identifier));

            $this->data = $stmt->fetchColumn();

            if($this->data == FALSE){
                throw new DataEmptyException('The identifier does not exist.');
            }

            $this->data = array(array(
                'identifier' => $this->identifier,
                'date'       =>$this->getFrom()
            ));

            foreach ($this->types as $type) {
                $this->data[0][$type] = "0";
            }
        }


        // add empty records if needed
        if ($this->paramInstance->getValue('addemptyrecords')) {
            $this->addEmptyRecords();
        }
    }
    /**
     * determines the mysql date_format for the corresponding period
     * @return
     */
    protected function determineDateOutput() {
        switch ($this->period) {
            case 'day':
                return "date";
                break;
            case 'week':
                return "DATE_FORMAT(date, '%Y-W%u')";
                break;
            case 'month':
                return "DATE_FORMAT(date, '%Y-%m')";
                break;
            case 'year':
                return "DATE_FORMAT(date, '%Y')";
                break;
            default:
                throw new UsageDataWrongPeriodException("Wrong period given: Only 'day', 'week', month' and 'year' is allowed.");
        }
    }

    /**
     * prepare query string for fetching only the data regarding the type (counter etc)
     * @return the mysql query for fetching the data regarding the type
     */
    protected function determineTypesQuery() {
        $typesQuery = '';
        foreach ($this->types as $key => $type) {
            $serviceType = $this->getServiceType($type);
            if ($serviceType) {
                $typesQuery .= ", (SELECT IFNULL(SUM({$this->typeDefinition[$type]['type']}),0)
                FROM
                    {$this->prefix}UsageData_{$this->period}
                WHERE
                    identifierid=U.identifierid
                AND
                    servicetypeid={$serviceType}";
            $typesQuery .= $this->determineClassificationsQuery();
            $typesQuery .= " AND
                    date=U.date
                ) AS {$type} ";
            } else {
                // remove type from given types as it is not found in database
                // and it should not be used for further processing (e.g. in determineNoZerosQuery())
                unset($this->types[$key]);
                if (empty($this->types)) {
                    // if no types left, there is no usage data to be found
                    throw new DataEmptyException('No usage data found.');
                }
            }
        }
        return $typesQuery;
    }

    /**
     * prepare subquery string for fetching only the data regarding the classification (admistrative etc)
     * @return the subquery for fetching the data regarding the classification
     */
    protected function determineClassificationsQuery() {
        $classificationQuery = '';
        $classificationsCount = sizeof($this->classifications);
        for($i=0; $i<$classificationsCount;$i++){
            // when all classifications are used, do not query this at all for faster perfomance
            if ($this->classifications[$i] == 'all') {
                return "";
            }
            $classificationId = $this->getClassificationId($this->classifications[$i]);

            if ($i==0) {
                // open classification query on first iteration
                $classificationQuery .= " AND (";
            }
            $classificationQuery .= "classificationid={$classificationId}";
            if ($i < $classificationsCount -1) {
                // combine the classification
                $classificationQuery .= " OR ";
            } else {
                // close classification query on last iteration
                $classificationQuery .= ")";
            }
        }
        return $classificationQuery;
    }

    /**
     * part of the query to avoid zero-values
     * @return the part of a query to avoid zero-values
     */
    protected function determineNoZerosQuery() {
        $noZerosQuery = '';
        foreach ($this->types as $type) {
            $noZerosQuery .= "+ {$type}";
        }
        return $noZerosQuery;
    }

    /**
     * Add empty records if missing for the given time
     * Time period is determined by the from and until parameters from the data object
     *
     * @param $period
     */
    public function addEmptyRecords()
    {
        // get data and types from data object

        $lastIdentifier = '';
        $from           = new DateTime($this->from);
        $until          = new DateTime($this->until);
        $dateIterator   = clone $from;
        $interval       = new DateInterval(DateTimeHelper::$periodInterval[$this->period]);
        $firstRun        = true;
        $newData        = array();

        foreach ($this->data as $row) {
            if ($firstRun) {
                $lastIdentifier = $row['identifier'];
                $firstRun = false;
            }
            // check for new identifier
            if (!($row['identifier'] == $lastIdentifier)) {
                    // create concluding empty records for last identifier (if necessary)
                    $emptyRecords = $this->createEmptyRecords($dateIterator, $until, $interval, $lastIdentifier, $this->period);

                    // merging the array step by step, as for our case this is way faster than php's build-in array_merge()
                    foreach($emptyRecords as $emptyRecord) {
                        $newData[] = $emptyRecord;
                    }
                    // initialize/reset date iterator
                    $dateIterator = clone $from;
                    $lastIdentifier = $row['identifier'];
            }
            $referenceDate = $dateIterator->format(DateTimeHelper::$periodFormat[$this->period]);
            // check if date exists in data or if empty records needs to be added
            if (!($row['date'] == $referenceDate)) {
                // we got one or more missing date(s), add empty records
                $emptyRecords = $this->createEmptyRecords($dateIterator, new DateTime($row['date']), $interval, $lastIdentifier, $this->period);

                // array merging
                foreach($emptyRecords as $emptyRecord) {
                    $newData[] = $emptyRecord;
                }
            }
            // add the actual row to the new data array
            $newData[] = $row;
            $dateIterator->add($interval);
        }
        // concluding empty records
        $emptyRecords = $this->createEmptyRecords($dateIterator, $until, $interval, $lastIdentifier, $this->period);
        foreach($emptyRecords as $emptyRecord) {
            $newData[] = $emptyRecord;
        }
        $this->data = $newData;
    }

    /**
     * Fill in the missing records between the last given date of the last identifier and the limit date
     *
     * @param $start        the DateTime object
     * @param $limit        the DateTime object marking the date limit up to which empty records will be created
     * @param $interval     the DateTimeInterval Object
     * @param $identifier   the identifier which gets an empty record
     * @param $period       the time period
     */
    private function createEmptyRecords($start, $limit, $interval, $identifier, $period)

{        $types = $this->getTypes();

        $dataSet = array();
        while ($start < $limit) {
            $newEmptyRecord = array(
                'identifier' => $identifier,
                'date' => $start->format(DateTimeHelper::$periodFormat[$period])
            );
            foreach ($types as $type) {
                $newEmptyRecord[$type] = 0;
            }
            $dataSet[] = $newEmptyRecord;
            $start->add($interval);
        }
        return $dataSet;
    }

    /**
     * Fetches the first-access and total-access for the identifier and parse it to
     * to the informationalData
     */
    private function retrieveInformationalData()
    {
    	if ($this->paramInstance->getValue('isexactsearch')) {
    		$info = new TotalGranularityUsageData($this->db, $this->paramInstance, $this->logger);
    		$info->fetchData();
    		$data = $info->getData();
    		$this->informationalData = array();
    		//since this is an exact search only one dataset is returned
    		$this->informationalData['first-access'] = $data[0]['date'];
    		$types = $this->getTypes();
   	    	foreach ($types as $type) {
	            $this->informationalData['total-accesses'][$type] = $data[0][$type];
	        }
    	}
    }

    function array_group_by(array $arr, callable $key_selector) {
      $result = array();
      foreach ($arr as $i) {
        $key = call_user_func($key_selector, $i);
        $result[$key][] = $i;
      }
      return $result;
    }

    public function getInformationalData()
    {
    	return $this->informationalData;
    }

	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}

	public function getIdentifier()
	{
		return $this->identifier;
	}

	public function setTypes(array $types)
	{
        $this->types = $types;
	}

	public function getTypes()
	{
		return $this->types;
	}

    public function setClassifications(array $classifications)
    {
        $this->classifications = $classifications;
    }

    public function getClassifications()
    {
        return $this->classifications;
    }

    public function setAllTypes()
    {
        foreach (array_keys($this->typeDefinition) as $type) {
            $this->types[] = $type;
        }
    }

	public function setFromToFitPeriod($from)
	{
        $this->from = DateTimeHelper::getStartDateOfPeriod($from,$this->getGranularity())->format('Y-m-d');
	}

    public function setFrom($from) {
        $this->from = $from;
    }

	public function getFrom()
	{
		return $this->from;
	}

	public function setUntilToFitPeriod($until)
	{
        $this->until = DateTimeHelper::getEndDateOfPeriod($until,$this->getGranularity())->format('Y-m-d');
	}

    public function setUntil($until) {
        $this->until = $until;
    }

	public function getUntil()
	{
		return $this->until;
	}

	public function setDatabase($db)
	{
		$this->db = $db;
		$this->dbc = $db->dbc;
	}

	public function getDatabase()
	{
		return $this->dbc;
	}

	public function getDataCount()
	{
		count($this->data);
	}

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

	public function getDistinctIdentifiers()
	{
		foreach ($this->data as $row) {
			$identifiers[] = $row['identifier'];
		}
		return (array_unique($identifiers));
	}

    public function setGranularity($period)
    {
        $this->period = $period;
    }

    public function getGranularity()
    {
        return $this->period;
    }

    public function getTypeDefinition()
    {
        return $this->typeDefinition;
    }

    protected function getServiceType($type)
    {
        $stmtServicetype = $this->dbc->prepare("SELECT id FROM {$this->prefix}ServiceType WHERE servicetype=:servicetype");
        $stmtServicetype->execute(array('servicetype' => $this->typeDefinition[$type]['servicetype']));
        if ($servicetype = $stmtServicetype->fetchColumn()) {
            return $servicetype;
        } else {
            $this->logger->log("No entry for servicetype {$this->typeDefinition[$type]['servicetype']} in database. Ignored.",4);
            return false;
        }
    }

    protected function getClassificationId($classification)
    {
        $stmtClassification = $this->dbc->prepare("SELECT id FROM {$this->prefix}Classification WHERE classification=:classification");
        $stmtClassification->execute(array('classification' => $classification));
        if ($classificationId = $stmtClassification->fetchColumn()) {
            return $classificationId;
        } else {
            $this->logger->log("No entry for classification {$this->typeDefinition[$type]['servicetype']} in database. Ignored.",4);
            return false;
        }
    }
}


?>