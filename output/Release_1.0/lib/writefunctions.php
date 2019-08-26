<?php 

/**
 * Creates or modifies the Index 
 * 
 * @param $fileLocation the location and name of file
 * @param $from start time
 * @param $until end time
 * @param $count number of entries
 * @param $indexLocation the location of the index file
 * @param $urlPath the path of the location of the index file
 * @param $maxEntries max entries in index file
 */
function writeIndexFile($fileLocation, $from, $until, $count, $indexLocation, $urlPath) {
    global $maxIndexEntries;
    if (!isset($maxIndexEntries)) {
        $maxIndexEntries = 30;
        stdOut("Maximum number of Index Entries not given in config.php. Automatically set to $maxIndexEntries .");
    }

    $jsonString = @file_get_contents($indexLocation);
    if (!$jsonString) {
        // creating basic structure of json file
        $jsonString = '{"lastupdate": "", "entries": "", "from": "", "until": "", "FileList": [ ] }';
    }

    $jsonArray = json_decode($jsonString);

    $allEntries = 0;
    $dates = array();

    // setting actual time
    $timestamp = time();
    $formattedTime = date(DATE_ATOM,$timestamp);

    $oldEntry = false;
    $from = date('Y-m-d',$from);
    $until = date('Y-m-d',$until);

    // updating specific json data
    foreach ($jsonArray->FileList as $file) {
        // if file was written before, edit entry
        if ($urlPath == $file->url) {
            $file->md5 = md5_file($fileLocation);
            $file->size = filesize($fileLocation);
            $file->changed = $formattedTime;
            // substract entry count, which is subject to be overwritten, from global entries number
            //$jsonArray->entries = $jsonArray->entries - $file->entries;
            $file->entries = $count;
            $oldEntry = true;
        }
    }

    // else write new entry
    if(!$oldEntry) {
        $data->url = $urlPath;
        $data->md5 = md5_file($fileLocation);
        $data->from = $from;
        $data->until = $until;
        $data->size = filesize($fileLocation);
        $data->changed = $formattedTime;
        $data->entries = $count;

        $jsonArray->FileList[] = $data;

        // show only the last data (number is defined in config.php)
        $files = sizeof($jsonArray->FileList);
        if ($files > $maxIndexEntries) {
            $jsonArray->FileList = array_slice($jsonArray->FileList, $files-$maxIndexEntries, $files);
        }
    }

    foreach ($jsonArray->FileList as $file) {
        // count all entries
        $allEntries += $file->entries;
        // save all from and until dates from the entries to determine the oldest and newest later on
        $dates['from'][] = $file->from;
        $dates['until'][] = $file->until;
    }


    // updating common json data
    $jsonArray->lastupdate = $formattedTime;
    $jsonArray->entries = $allEntries;
    $jsonArray->from = min($dates['from']);
    $jsonArray->until = max($dates['until']);


    // $jsonArray->entries = $jsonArray->entries + $count;
    // if ($jsonArray->from > $from || $jsonArray->from=="")
    //     $jsonArray->from = $from;
    // if ($jsonArray->until < $until)
    //     $jsonArray->until= $until;

    $jsonString = json_encode($jsonArray);
    // removing unneeded escaping characters from json encoding
    $jsonString = str_replace("\/","/",$jsonString);
    
    $file = fopen($indexLocation, 'w');
    if (!$file)
        stdOut("Error opening or creating index file in path $indexLocation");
    if (fwrite($file, $jsonString))
        stdOut("Index file is written to $indexLocation");
    fclose($file);

}


function outputStatus($data,$format,$type,$statusFile)
{
    if ($type=='calculates') {
        while($row = mysql_fetch_array($data)) {
           $results[] = array(
              'time' => $row['time'],
              'from' => $row['from'],
              'until' => $row['until']
           );
        }
    }

    else if ($type=='harvests') {
        while($row = mysql_fetch_array($data)) {
           $results[] = array(
              'time' => $row['timestart'],
              'from' => $row['fromparam'],
              'until' => $row['timestart']
           );
        }
    }
    else {
        die('Wrong value given. Only "calculates" and "harvests" is allowed.');
    }
    switch ($format) {
        case 'json':
            $jsonString = json_encode($results);

            break;
        
        default:
            die('No or wrong format for Output given.');
    }
    $file = fopen($statusFile, 'w');
    if (!$file)
        stdOut("Error opening or creating index file in path $statusFile");
    if (fwrite($file, $jsonString))
        stdOut("Index file is written to $statusFile");
    fclose($file);
}



/**
 * Creates JSON output format
 * 
 * @param $activeRep the array of the active Repository
 * @param $fileLocation the location and name of file
 * @param $statistics MYSQL query result
 * @param $from start time
 * @param $until end time
 */
function createJson($chosenStandards, $fileLocation, $statistics, $from, $until) {
    $file = fopen($fileLocation, 'w');
    if (!$file)
        return false;

    //json header
    $fileheader = '
    {
      "from": "' . date('Y-m-d',$from) . '",
      "to": "' . date('Y-m-d', $until) . '",
    ';
    $fileheader .= '"entrydef": ["identifier", "date"';
    foreach ($chosenStandards as $Standard) {
        $fileheader .= ", ";
        $fileheader .= "\"" . $Standard . "\"";
    }
    $fileheader .= "],\n" . '    "entries": [ 
';
    fwrite($file, $fileheader);
    $return = "";

    //json datablocks
    $firstloop = true;
    $count = 0;
    while ($stats = mysql_fetch_assoc($statistics)) {
        if ($firstloop) {
            $firstloop = false;
        } else {
            $return .= ",\n";
        }

        //block Description
        $return .= "        {\"identifier\": \"" . $stats['identifier'] . "\", \"date\": \"" . $stats['timeframe'] . "\"";

        //Content
        foreach ($chosenStandards as $Standard) {
            // prevent invalid output if there are no entries in database
            if (!isset($stats[$Standard]))
                $stats[$Standard] = 0;
            $return .= ", \"" . $Standard . "\": " . $stats[$Standard];
        }

        //close datablock
        $return .= "}";
        $count++;
    }

    //close Json
    $return .= '         ] 
    }';

    if (fwrite($file, $return))
        stdOut("File is written to " . $fileLocation, "");
    fclose($file);
    return $count;
}


/**
 * Creates CSV output format
 * Start and end date are not shown in CSV
 * 
 * @param $activeRep the array of the active Repository
 * @param $fileLocation the location and name of file
 * @param $statistics MYSQL query result
 * @param $from start time
 * @param $until end time
 */
function createCsv($chosenStandards, $fileLocation, $statistics) {
   $file = fopen($fileLocation, 'w');
    if (!$file)
        return false;

    //CSV header
    $fileheader = 'date;identifier';
    
    foreach ($chosenStandards as $Standard) {
        $fileheader .= ";";
        $fileheader .= $Standard;
    }
    $fileheader .= "\n";
     
    fwrite($file, $fileheader);
    $return = "";

    //CSV datablocks
    $firstloop = true;
    $count = 0;
    while ($stats = mysql_fetch_assoc($statistics)) {
        if ($firstloop) {
            $firstloop = false;
        } else {
            $return .= "\n";
        }

        //block Description
        $return .= $stats['timeframe'] . ";" . $stats['identifier'];

        //content
        foreach ($chosenStandards as $Standard) {
            // prevent invalid output if there are no entries in database
            if (!isset($stats[$Standard]))
                $stats[$Standard] = 0;
            $return .= ";" . $stats[$Standard];
        }
        $count++;
    }

    if (fwrite($file, $return))
        stdOut("File is written to " . $fileLocation, "");
    fclose($file);
    return $count; 
}


/**
 * Creates Counter.org-compliant XML output format
 * Counter XML support more than one Report and Customer in one XML output. The possibility is given in the class, but not implented here, hence the array of 1 with 'Report' and 'Customer'.
 * 
 * @param $activeRep the array of the active Repository
 * @param $fileLocation the location and name of file
 * @param $statistics MYSQL query result
 * @param $from start time
 * @param $until end time
 */
function createCounterXml($activeRep, $fileLocation, $statistics, $from, $until) {    
    // prepare array for counterxmlbuild with vendor informations as given in reps.php
    $data['Report'][0] = $activeRep['xmlInformation']['Report'];
    
    // prepare array for counterxmlbuild with vendor informations as given in reps.php 
    $data = array(
        'Report' => array (
            0 => array(
                'ID' => 'ID01',
                'Vendor' => array(
                    'Name' => VENDOR_NAME,
                    'ID' => VENDOR_ID,
                    'WebSiteUrl' => VENDOR_WEBSITEURL
                    ),
            ),
        ),
    );
    $data['Report'][0]['Customer'][0] = $activeRep['xmlInformation']['Customer'];
    $data['Report'][0]['Created'] = date('Y-m-d H:i:s');

    $j=0;
    $y=0;
    $previousIdentifier = '';
    $count = 0;


    while ($stats = mysql_fetch_assoc($statistics)) {
        
        // check for new identifier -> start new ReportItem
        if (!($stats['identifier'] == $previousIdentifier)) {
            // next Reportitem
            $j++; 
            // reset ItemPerfomance
            $y=0; 

            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPlatform'] = $activeRep['xmlInformation']['ItemPlatform'];
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemDataType'] = $activeRep['xmlInformation']['ItemDataType'];
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemName'] = $stats['identifier'];
        }

        $firstDayOfMonth = mktime(0, 0, 0, date('m',strtotime($stats['timeframe'])), 01, date("y",$from));
        $lastDayOfMonth = strtotime('last day of this month', $firstDayOfMonth);
        
        //$lastDayOfMonth = mktime(0, 0, 0, date('m',$firstDayOfMonth), date("t",$firstDayOfMonth), date("y",$firstDayOfMonth));
        $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Period']['Begin'] = date('Y-m-d', $firstDayOfMonth);
        $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Period']['End'] = date('Y-m-d', $lastDayOfMonth);
        $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Category'] = $activeRep['xmlInformation']['ItemPerformance_Category'];

        // check for different counts
        $x=0;
        foreach ($activeRep['xmlInformation']['ItemPerformance_Instance_MetricType'] as $name => $calc) {
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Instance'][$x]['MetricType'] = $name;
            $data['Report'][0]['Customer'][0]['ReportItems'][$j]['ItemPerformance'][$y]['Instance'][$x]['Count'] = $stats[$calc];
            // next Instance
            $x++; 
        }

        //next ItemPerformance
        $y++; 
        $previousIdentifier = $stats['identifier'];
        $count++;
    }

    $file = fopen($fileLocation, 'w');
    if (!$file)
        return false;
    $IndentString = "\t";
    $counterxmlbuild = new CounterXMLBuilder();
    $counterxmlbuild->setIndentString($IndentString);
    $counterxmlbuild->start();
    $counterxmlbuild->add_reports($data);
    $counterxmlbuild->done();
    $return = $counterxmlbuild->outputMemory();
    if (fwrite($file, $return))
        stdOut("File is written to " . $fileLocation, "");
    fclose($file);
    return $count; 
}

/**
 * Creates Counter.org Journal Report 1/ Book Report 1 SpreadsheetML (Excel readable) output format
 * 
 * @param $activeRep the array of the active Repository
 * @param $fileLocation the location and name of file
 * @param $statistics MYSQL query result
 * @param $from start time
 * @param $until end time
 */
function createExcelXml($activeRep, $fileName, $statistics, $from, $until) {   
    
    $count = 0;

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

    
    $reportName = $activeRep['xmlInformation']['Report']['Name'];
    // add Sheet  
    $sheet1 = $xml->addSheet($reportName);

    $sheet1->columnWidth(1,$width = 182);
    $sheet1->columnWidth(5,$width = 182);

    // write cells which are the same within JR1 and BR2
    $sheet1->writeString(2,1,$activeRep['xmlInformation']['Customer']['Name'],'StyleHeader');
    $sheet1->writeString(3,1,$activeRep['xmlInformation']['Customer']['ID'],'StyleHeader');
    $sheet1->writeString(4,1,'Period covered by Report:','StyleHeader');
    $sheet1->writeString(5,1,date('Y-m-d',$from). " to " . date('Y-m-d',$until),'StyleHeader');
    $sheet1->writeString(6,1,'Date run:','StyleHeader');
    $sheet1->writeString(7,1,date('Y-m-d'),'StyleHeader');
    $sheet1->writeString(8,2,'Publisher','StyleHeader2');
    $sheet1->writeString(8,3,'Platform','StyleHeader2');
    $sheet1->writeString(8,5,'Proprietary Identifier','StyleHeader2');
    $sheet1->writeString(8,8,'Reporting Period Total','StyleHeader2');
    $sheet1->writeString(9,2,'','StyleHeader3');
    $sheet1->writeString(9,3,$activeRep['xmlInformation']['ItemPlatform'],'StyleHeader3');
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
        $sheet1->writeString(8,$maxMonthColumn,date('M-Y',$from),'StyleHeader2');
        $months[] = array ( 
            'time' => date('M-Y',$from),
            'column' => $maxMonthColumn
        );
        $from = strtotime('next month',$from);
        $maxMonthColumn++;
    } while ($from <= $until);

    // variable initializing
    $row=9;    
    $previousIdentifier = '';

    // fill spreadsheet with statistics from database  
    while ($stats = mysql_fetch_assoc($statistics)) {
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
            $sheet1->writeString($row,3,$activeRep['xmlInformation']['ItemPlatform'],'default');
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
        $count++;
    }

    // sum up row and write in row:9, starting with column:9
    $actualColumn=9;
    do {
        $sheet1->writeFormula('Number',9,$actualColumn,'=SUM(R10C' . $actualColumn . ':R65512C' . $actualColumn .')', 'StyleHeader3');
        $actualColumn++;
    } while ($actualColumn<$maxMonthColumn);

    // sum up all data from all journals/books
    $sheet1->writeFormula('Number',9,8,'=SUM(R9c' . $startingColumn . ':R9C10000)', 'StyleHeader3');

    $xml->overwriteFile();
    if ($xml->writeData($fileName))
        stdOut("File is written to " . $fileName, "");;
    return $count;
}

?>