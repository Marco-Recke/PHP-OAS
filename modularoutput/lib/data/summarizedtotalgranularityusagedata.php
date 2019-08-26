<?php
require_once(dirname(__FILE__).'/totalgranularityusagedata.php');

/**
*  Fetches summarized usage data with total granularity and first access
*
*  @package output
*  @version 0.2
*/
class SummarizedTotalGranularityUsageData extends TotalGranularityUsageData
{
    /**
     * Fetches the summarized usagedata from the rollup table
     */
    public function fetchData()
    {
        $typesQuery = $this->determineTypesQuery();

        $query = "SELECT @identifier:=:identifier AS identifier, MIN(date) as date";
        foreach ($this->types as $type) {
            $query .= ", SUM({$type}) as {$type}";
        }
        $query .= " FROM (SELECT DISTINCT identifier,
                date {$typesQuery}
            FROM
                {$this->prefix}Identifier AS I
            LEFT JOIN
                {$this->prefix}UsageData_total AS U
            ON
                I.id=U.identifierid
            WHERE
                identifier LIKE :identifier GROUP BY identifier) AS data";
        $stmt = $this->dbc->prepare($query);

        $stmt->execute(array('identifier' => $this->identifier));

        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($this->data)) {
            throw new DataEmptyException('No usage data found');
        }
    }
}

?>