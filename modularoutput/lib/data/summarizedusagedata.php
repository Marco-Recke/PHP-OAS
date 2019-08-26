<?php
require_once(dirname(__FILE__).'/usagedata.php');

/**
*  Can fetch usage data disregarding different identifiers
*
*  @package output
*  @version 0.4
*/
class SummarizedUsageData extends UsageData
{
    /**
     * Fetches the usagedata from the rollup table depending on the period given
     * Here only total numbers are relevant, the usage data for the different
     * identifiers are summed up
     */
    public function fetchData()
    {
        $dateOutput = $this->determineDateOutput();
        $typesQuery = $this->determineTypesQuery();

        $query = "SELECT @identifier:=:identifier AS identifier, date";
        foreach ($this->types as $type) {
            $query .= ", SUM({$type}) as {$type}";
        }
        // start subquery
        $query .= " FROM (SELECT DISTINCT identifier,
            {$dateOutput} AS date {$typesQuery}
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
        ORDER BY date) AS data GROUP BY date";

        $stmt = $this->dbc->prepare($query);

        $stmt->execute(array('identifier' => $this->identifier, 'from' => $this->from, 'until' => $this->until));

        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($this->data)) {
            throw new DataEmptyException('No usage data found');
        }
    }

}?>