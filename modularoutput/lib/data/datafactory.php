<?php

require_once(dirname(__FILE__).'/statusdata.php');
require_once(dirname(__FILE__).'/robotsdata.php');
require_once(dirname(__FILE__).'/usagedata.php');
require_once(dirname(__FILE__).'/summarizedusagedata.php');
require_once(dirname(__FILE__).'/totalgranularityusagedata.php');
require_once(dirname(__FILE__).'/summarizedtotalgranularityusagedata.php');
require_once(dirname(__FILE__).'/counterjr1usagedata.php');
require_once(dirname(__FILE__).'/counterbr1usagedata.php');

class DataNotSupported extends Exception{}

/**
 * Factory class to coordinate the different data classes
 */
class DataFactory
{
    /**
     * Creates and returns a new data instance
     *
     * @param  $db a database connection instance
     * @param  $paramInstance parameters
     * @param  $logger a logger
     * @return a new data instance
     */
    public static function createDataObject($db,$paramInstance,$logger) {
        $dataType = $paramInstance->getMainParam();
        switch($dataType) {

            case "status":
                return new StatusData($db, $paramInstance, $logger);

            case "basic":
            	if ($paramInstance->getValue('granularity') == 'total') {
                    // as granularity 'total' is a different pair of shoes than other granularities
                    // we use own subclasses for these usage-datas
                    if ($paramInstance->getValue('summarized')) {
                        return new SummarizedTotalGranularityUsageData($db,$paramInstance,$logger);
                    } else {
                        return new TotalGranularityUsageData($db,$paramInstance,$logger);
                    }
            	} else {
                    if ($paramInstance->getValue('summarized')) {
                        return new SummarizedUsageData($db,$paramInstance,$logger);
                    } else {
                        return new UsageData($db,$paramInstance,$logger);
                    }
                }

            case "robots":
                return new RobotsData($db, $paramInstance, $logger);

            case "JR1":
                return new CounterJR1UsageData($db,$paramInstance,$logger);

            case "BR1":
                return new CounterBR1UsageData($db,$paramInstance,$logger);

            default:
                throw new DataNotSupported('Data type not implemented');
        }
    }
}