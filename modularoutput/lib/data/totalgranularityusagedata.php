<?php
require_once(dirname(__FILE__).'/usagedata.php');

/**
*  Fetches usage data with total granularity and first access
*
*  This is an separate class from the UsageData class for other
*  granularities, because the given input dates are irrelevant
*  plus the date value represents here the first occurence
*
*  @package output
*  @version 0.2
*/
class TotalGranularityUsageData extends UsageData
{
    function __construct($db,$paramInstance,$logger)
    {
    	$paramInstance->setValue('from', '2000-01-01');
    	$paramInstance->setValue('until', 'yesterday');
    	parent::__construct($db,$paramInstance,$logger);
    }

    /**
     * Fetches the usagedata from the rollup table
     */
    public function fetchData()
    {
        $typesQuery = $this->determineTypesQuery();

        $noZerosQuery = $this->determineNoZerosQuery();


        $query = "SELECT DISTINCT identifier, date {$typesQuery}
            FROM
                {$this->prefix}Identifier AS I
            LEFT JOIN
                {$this->prefix}UsageData_total AS U
            ON
                I.id=U.identifierid
            WHERE
                identifier LIKE :identifier
            GROUP BY identifier
            HAVING {$noZerosQuery} > 0
            ORDER BY identifier";

        $stmt = $this->dbc->prepare($query);

        $stmt->execute(array('identifier' => $this->identifier));

        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($this->data)) {
            throw new DataEmptyException('No usage data found');
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
                        {$this->prefix}UsageData_total
                    WHERE
                        identifierid=U.identifierid
                    AND
                        servicetypeid={$serviceType}";
                $typesQuery .= $this->determineClassificationsQuery();
                $typesQuery .= "
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
}

?>