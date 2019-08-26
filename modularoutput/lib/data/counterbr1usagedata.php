<?php
require_once(dirname(__FILE__).'/counterusagedata.php');

/**
*  Can fetch usage data for COUNTER BR1
*
*  @package output
*  @version 0.1
*/
class CounterBR1UsageData extends CounterUsageData
{
    function __construct($db,$paramInstance,$logger)
    {
        parent::__construct($db,$paramInstance,$logger);
        $this->reportName   = 'BR1';
        $this->reportTitle  = 'Book Report 1';
    }

//TODO Usage Data fetching anpassen an Besonderheiten, erweitern
    /**
     * Fetches the usagedata from the rollup table for Counter BR1
     */
    public function fetchData()
    {
        $dateOutput = $this->determineDateOutput();
        $typesQuery = $this->determineTypesQuery();

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
        ORDER BY identifier,date";

        $stmt = $this->dbc->prepare($query);

        $stmt->execute(array('identifier' => $this->identifier, 'from' => $this->from, 'until' => $this->until));

        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($this->data)) {
            throw new DataEmptyException('No usage data found');
        }
    }

}?>