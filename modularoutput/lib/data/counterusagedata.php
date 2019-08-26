<?php
require_once(dirname(__FILE__).'/usagedata.php');

/**
*  Abstract class for implementing the data fetching for COUNTER Reports
*
*  @package output
*  @version 0.4
*/
abstract class CounterUsageData extends UsageData
{
    // the COUNTER version number
    protected $version;

    // the COUNTER report name (e.g. JR1)
    protected $reportName;

    // the COUNTER report name (e.g. Journal Report 1)
    protected $reportTitle;

    // informations about the requesting customer
    protected $customer;

    function __construct($db, $paramInstance, $logger)
    {
        parent::__construct($db, $paramInstance, $logger);

        // COUNTER reports are always done month-wise
        $this->setGranularity('month');

        // when no from or until value is set, COUNTER specifications say:
        // "If no start or end month is specified by a customer, the default reporting period is the Current
        // Calendar Year-to-Date"
        if (!$this->paramInstance->issetPossibleParamArray('from'))
            $this->setFrom(DateTimeHelper::getStartDateOfPeriod(date('Y-m-d'),'year')->format('Y-m-d'));
        else
            $this->SetFromToFitPeriod($this->paramInstance->getValue('from'));

        if (!$this->paramInstance->issetPossibleParamArray('until'))
            $this->setUntil(DateTimeHelper::getEndDateOfPeriod(date('Y-m-d'),'month')->format('Y-m-d'));
        else
            $this->SetUntilToFitPeriod($this->paramInstance->getValue('until'));

        // informations about the customer
        $this->customer = $this->paramInstance->getValue('dataprovider');

        // COUNTER reports are always only fulltext aware
        $this->setTypes(array('counter'));

        // The requested COUNTER version
        $this->version = $this->paramInstance->getValue('formatVersion');
    }

    /**
     * Returns a set of COUNTER relevant informations about the customer
     * @return the relevant informations as an array
     */
    public function getCustomerInfo()
    {
        return array(
            'Name' => $this->customer['repositoryname'],
            'ID'   => $this->customer['id'],
            'Contact'    => array(
                        0 => array(
                             // 'Contact'=> $this->customer['contact'],
                             'E-Mail' => $this->customer['email'],
                         )
                        ),
            'WebSiteUrl'    => $this->customer['websiteurl'],
            // 'LogoUrl'        =>
        );
    }

    public function getVersion() {
        return $this->version;
    }

    public function getReportName() {
        return $this->reportName;
    }

    public function getReportTitle() {
        return $this->reportTitle;
    }
}
?>