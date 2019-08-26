<?php
// require_once(dirname(__FILE__).'/counter.php');
require_once(dirname(__FILE__).'/vendor/ExcelWriterXML.php');
require_once(dirname(__FILE__).'/constants.php');


// TODO: ANPASSEN an neue Data-Klassen
// am besten mehrere Counterexcelformat klassen bilden etwa: CounterJR1Excelformat, CounterBR1ExcelFormat, etc.
// CounterExcelFormat als Parent-class kann gemeinsames übernehmen, etwa den oberen Bereich (Prüfen, was überall gleich ist)

/**
 * COUNTER compliant XML Export Class
 *
 * @author Matthias Hitzler <hitzler@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @version 0.1
 */
class CounterExcelFormat extends DataFormat
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

        $from  = $this->dataObject->getFrom();
        $until = $this->dataObject->getUntil();
        $xmlMetadata = $this->dataObject->getAdditionalInformation('xmlInformation');
        $data['Report'][0]['Customer'][0] = $this->dataObject->getCustomerInfo();


        //  start new spreadsheet
        $xml = new ExcelWriterXML();

        // meta data
        $xml->docTitle('Counter Report');
        $xml->docAuthor('OA-Statistik');

        // styles
        $format1 = $xml->addStyle('StyleHeader');
        $format1->fontBold();
        $format1->border();
        $format2 = $xml->addStyle('StyleHeader2');
        $format2->fontBold();
        $format2->bgColor('95b3d7');
        $format2->border();
        $format2->alignWraptext();
        $format3 = $xml->addStyle('StyleHeader3');
        $format3->fontBold();
        $format3->bgColor('fabf8f');
        $format3->border();
        $format4 = $xml->addStyle('default');
        $format4->border();

        $reportName = $this->dataObject->getReportName;
        $reportName = $this->dataObject->getReportTitle() .
                    '(R' . $this->dataObject->getVersion() . ')';
        // add Sheet
        $sheet1 = $xml->addSheet($reportName);

        $sheet1->columnWidth(1,$width = 182);
        $sheet1->columnWidth(5,$width = 182);

        // write cells which are the same within JR1 and BR1
        $sheet1->writeString(2,1,$xmlMetadata['Customer']['Name'],'StyleHeader');
        $sheet1->writeString(3,1,$xmlMetadata['Customer']['ID'],'StyleHeader');
        $sheet1->writeString(4,1,'Period covered by Report:','StyleHeader');
        $sheet1->writeString(5,1,$from. " to " . $until,'StyleHeader');
        $sheet1->writeString(6,1,'Date run:','StyleHeader');
        $sheet1->writeString(7,1,date('Y-m-d'),'StyleHeader');
        $sheet1->writeString(8,2,'Publisher','StyleHeader2');
        $sheet1->writeString(8,3,'Platform','StyleHeader2');
        $sheet1->writeString(8,5,'Proprietary Identifier','StyleHeader2');
        $sheet1->writeString(8,8,'Reporting Period Total','StyleHeader2');
        $sheet1->writeString(9,2,'','StyleHeader3');
        $sheet1->writeString(9,3,$xmlMetadata['ItemPlatform'],'StyleHeader3');
        for ($a=4;$a<8;$a++) {
            $sheet1->writeString(9,$a,'','StyleHeader3');
        }

        switch ($reportName) {
            case 'JR1':
                $sheet1->writeString(1,1,'Journal Report 1 (R' . REPORT_VERSION . ')','StyleHeader');
                $sheet1->writeString(1,2,'Number of Successful Full-Text Article Requests by Month and Journal','StyleHeader');
                $sheet1->writeString(8,1,'Journal','StyleHeader2');
                $sheet1->writeString(8,4,'Journal DOI','StyleHeader2');
                $sheet1->writeString(8,6,'Print ISSN','StyleHeader2');
                $sheet1->writeString(8,7,'Online ISSN','StyleHeader2');
                $sheet1->writeString(8,9,'Reporting Period HTML','StyleHeader2');
                $sheet1->writeString(8,10,'Reporting Period PDF','StyleHeader2');
                $sheet1->writeString(9,1,'Total for all journals','StyleHeader3');
                $startingColumn = 11;
                break;

            case 'BR1':
                $sheet1->writeString(1,1,'Book Report 1 (R' . REPORT_VERSION . ')','StyleHeader');
                $sheet1->writeString(1,2,'Number of Successful Title Requests by Month and Title','StyleHeader');
                $sheet1->writeString(8,1,'','StyleHeader2');
                $sheet1->writeString(8,4,'Book DOI','StyleHeader2');
                $sheet1->writeString(8,6,'ISBN','StyleHeader2');
                $sheet1->writeString(8,7,'Online ISSN','StyleHeader2');
                $sheet1->writeString(9,1,'Total for all titles','StyleHeader3');
                $startingColumn = 9;
                break;

            default:
                stdOut("No or wrong COUNTER report name given.", "ERROR");
                break;
        }

        // write month headers
        $maxMonthColumn = $startingColumn;
        do {
            $sheet1->writeString(8,$maxMonthColumn,date('M-Y',strtotime($from)),'StyleHeader2');
            $months[] = array (
                'time' => strtotime($from),
                'column' => $maxMonthColumn
            );
            $from = strtotime('next month',strtotime($from));
            $maxMonthColumn++;
        } while ($from <= $until);

        // variable initializing
        $row=9;
        $previousIdentifier = '';

        // fill spreadsheet with statistics from database
        foreach ($this->dataObject->getData() as $stats) {
            // check for new identifier
            if (!($stats['identifier'] == $previousIdentifier)) {

                // start new Row
                $row++;

                for ($i=$startingColumn;$i<$maxMonthColumn;$i++) {
                    $sheet1->writeNumber($row,$i,0,'default');
                }
                // sum up all data from one journal/book
                $sheet1->writeString($row,1,'','default');
                $sheet1->writeString($row,2,'','default');
                $sheet1->writeString($row,3,$xmlMetadata['ItemPlatform'],'default');
                $sheet1->writeString($row,4,'','default');
                $sheet1->writeString($row,5,$stats['identifier'],'default');
                $sheet1->writeString($row,6,'','default');
                $sheet1->writeString($row,7,'','default');
            }

            // write counter statistics in the month column
            foreach ($months as $month) {
                if (strtotime($stats['date']) == strtotime($month['time'])) {
                    $sheet1->writeNumber($row,$month['column'],$stats['counter'],'default');
                }
            }

            //sum up all requests from one journal/book
            $sheet1->writeFormula('Number',$row,8,'=SUM(R' . $row . 'C' . $startingColumn . ':R' . $row . 'C10000)','default');
            $previousIdentifier = $stats['identifier'];
        }

        // sum up row and write in row:9, starting with column:9
        $actualColumn=9;
        do {
            $sheet1->writeFormula('Number',9,$actualColumn,'=SUM(R10C' . $actualColumn . ':R65512C' . $actualColumn .')', 'StyleHeader3');
            $actualColumn++;
        } while ($actualColumn<$maxMonthColumn);

        // sum up all data from all journals/books
        $sheet1->writeFormula('Number',9,8,'=SUM(R9c' . $startingColumn . ':R9C10000)', 'StyleHeader3');
        $xmldata = $xml->writeData();

        return $this->formattedData = $xmldata;
	}
}
?>