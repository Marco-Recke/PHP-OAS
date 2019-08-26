<?php
// require_once(dirname(__FILE__).'/counter.php');
require_once(dirname(__FILE__).'/counterxmlbuilder.php');
require_once(dirname(__FILE__).'/constants.php');


/**
 * COUNTER compliant XML Format Class
 *
 * @package modularoutput
 * @version 0.2
 */
class CounterXmlFormat extends DataFormat
{


    /**
     * Creates COUNTER compliant XML data
     *
     * Note: Counter XML support more than one Report and Customer in one XML output.
     * The possibility is given in the class, but not implented here, hence the array of 1 with 'Report' and 'Customer'.
     *
     * @return the xml formatted data
     */
    public function formatData(){
        $from = $this->dataObject->getFrom();

        // prepare array for counterxmlbuild with vendor informations
        $data = array(
            'Report' => array (
                0 => array(
                    // Report attributes
                    'Created'   => date('Y-m-d\TH:i:s'),
                    'ID'        => 'ID01', //TODO
                    'Version'   => $this->dataObject->getVersion(),
                    'Name'      => $this->dataObject->getReportName(),
                    'Title'     => $this->dataObject->getReportTitle(),
                    // Vendor node
                    'Vendor' => array(
                        'Name' => VENDOR_NAME,
                        'ID' => VENDOR_ID,
                        'Contact' => array(
                            0 => array(
                            'Contact' => VENDOR_CONTACT_NAME,
                            'E-mail' => VENDOR_CONTACT_MAIL
                            ),
                        ),
                        'WebSiteUrl' => VENDOR_WEBSITEURL,
                        'LogoUrl' => VENDOR_LOGOURL,
                        ),
                ),
            ),
        );

        // prepare with customer informations
        $data['Report'][0]['Customer'][0] = $this->dataObject->getCustomerInfo();

        $j=0; // ReportItem
        $y=0; // ItemPerfomance
        $previousIdentifier = '';
        $count = 0;

        foreach ($this->dataObject->getData() as $row) {
            if (!($row['identifier'] == $previousIdentifier)) {
                // next Reportitem
                $j++;
                // reset ItemPerfomance
                $y=0;

                $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPlatform'] = 'TODO'; //TODO
                $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemDataType'] = 'TODO'; //TODO
                $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemName'] = $row['identifier'];
            }

            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Period']['Begin'] = date('Y-m-d', strtotime($row['date']));
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Period']['End'] = date('Y-m-d', strtotime('last day of this month', strtotime($row['date'])));
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Category'] = 'TODO'; //TODO

            // check for different counts
            $x=0; // Instance

            // TODO: implement/translate format for different metric types
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Instance'][$x]['MetricType'] = 'ft_total';
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Instance'][$x]['Count'] = $row['counter'];

            $y++; //next ItemPerformance
            $previousIdentifier = $row['identifier'];
            $count++;
        }

        $counterxmlbuild = new CounterXMLBuilder();
        $counterxmlbuild->setIndentString("\t");
        $counterxmlbuild->start();
        $counterxmlbuild->add_reports($data);
        $counterxmlbuild->done();
        return $this->formattedData = $counterxmlbuild->outputMemory();
    }
}
?>