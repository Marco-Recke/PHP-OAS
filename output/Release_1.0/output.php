#!/usr/bin/env php
<?php

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/lib/counterxmlbuilder.php');
require_once(dirname(__FILE__).'/lib/ExcelWriterXML.php');
require_once(dirname(__FILE__).'/lib/writefunctions.php');



/**
 * A simple logger with output to the stdOut
 * 
 * @param $text text output
 * @param $status status output e.g. WARNING, ERROR
 * @param $verbose set true if messages should only be shown in verbose mode
 */
function stdOut($text, $status = "", $verbose = false) {

    global $verboseOutputMode;
    global $webOutputMode;
    
    //We don't want to output ANYTHING in webmode, except for the filename
    if($webOutputMode)
        return;
    
    if ((!$verboseOutputMode) && $verbose) {
        return;
    }
    $time = date('y-m-d H:i:s', time());
    echo "<" . $time . "> " . $status . " " . $text . "\n";
}


/**
 * Checks for folder and creates one recursive if not existing
 * 
 * @param $path the folder to create
 */
function createFolder($path) {
    if (!is_dir($path)) {
        // recursive creating of folder
        mkdir($path, 0777, true);
        if (is_dir($path)) {
            stdOut('Folder ' . $path . ' was created', "", true);
        }
        return true;
    }
    return false;
}


/**
 * Creates name of folder e.g. '/var/www/berlin/2013/01/'
 * 
 * @param $repositoryDirectory directory of repository
 * @param $from start date
 * @param $until end date
 * @return $folderName the built name of the folder
 */
function createFolderName($path, $repositoryDirectory, $until)
{
    $month=date('m',$until);
    $year=date('Y',$until);
    $folderName = $path . $repositoryDirectory . $year . '/' . $month . '/';
    return $folderName;
}


/**
 * Creates name of file e.g. '2013-01-01_2013-01-31.json'
 * 
 * @param $from start time
 * @param $until end time
 * @param $extension extension of file
 * @return $fileName the built name of the file
 */
function createFileName($from, $until, $extension)
{
    $fileName = date('Y-m-d', $from) . '_' . date('Y-m-d', $until) . $extension;
    return $fileName;
}


/**
 * Creates the whole path to a file e.g. '/var/www/berlin/2013/01/2013-01-01_2013-01-31.json'
 * 
 * @param  $from       start time
 * @param  $until      end time
 * @param  $extension  extension of file
 * @param  $folderName name of folder
 * @return the built name of the file
 */
function createFilePath($from, $until, $extension, $folderName){
    
    global $webOutputMode;
    if($webOutputMode)
        return 'php://stdout';
        
        
    $fileName = createFileName($from, $until, $extension);
    return $folderName . $fileName;
}


/**
 * Uses the linux tar command to archive the files in given folder if there are any
 * 
 * @param $folderName name of folder which is processed and written to
 * @param $fileName name of archive file
 */
function writeArchive($folderName, $fileName) {
    global $webOutputMode;
    if($webOutputMode)
        return false;
    
    
    if (file_exists($folderName)) {
        if (count(scandir($folderName) > 2)) {
            exec('tar -C ' . dirname($folderName) . ' -zcf ' . $folderName . $fileName . ' ' . basename($folderName));
        }
    }
}


/**
 * Checks if file is writable
 * 
 * @param  string $name path and name of file which is check
 * @return boolean
 */
function checkWritable($name)
{
    global $overwrite;
    global $webOutputMode;
    
    
    if (((file_exists($name) && $overwrite) || (!file_exists($name))) || $webOutputMode) {
        return true;
    }
    stdOut("File is not written because it already exists and overwrite is turned off.", "");
    return false;
}


/**
 * Calls database and returns the statistics of each identifier for a given period of time
 * 
 * @param $activeRep the array of the active Repository 
 * @param $from start time of the MYSQL query
 * @param $until end time of the MYSQL query
 * @return $resultStatistics the result of the MYSQL query
 */
function callDatabase($activeRep, $from, $until) {
    // database connection 
    $connection = mysql_connect($activeRep['db']['host'], $activeRep['db']['user'], $activeRep['db']['password']);
    if (!$connection) {
        die('No connection to the MySQL Server: ' . mysql_error());
    }
    $database_connect = mysql_select_db($activeRep['db']['database']);
    if (!$database_connect) {
        die ('No connection to the database '. $activeRep['db']['database'] . ': ' . mysql_error());
    }

    switch ($activeRep['period']) {
        case 'day':
            $timePeriod = "date=U.date";
            $dateOutput = " date";
            break;
        case 'week':
            $timePeriod = "weekofyear(date)=weekofyear(U.date)";
            $dateOutput =" DATE_FORMAT(date, 'Week %u %Y')";
            break;
        case 'month':
            $timePeriod = "month(date)=month(U.date)";
            $dateOutput =" DATE_FORMAT(date, '%b %Y')";
            break;
        case 'year':
            $timePeriod= "year(date)=year(U.date)";
            $dateOutput = " DATE_FORMAT(date, '%Y')";
            break;
        default:
            stdOut("Wrong period given: Only 'day', 'week', month' and 'year' is allowed.", "ERROR");
            break;
    }

    $standardsQuery = '';

    // check which data should get an output and fetch only those data from database.
    foreach ($activeRep['allStandards'] as $Standards) {
        switch ($Standards) {
            case 'counter':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='fulltext'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_fulltext = @mysql_fetch_object($res);
                if ($servicetype_fulltext) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(counter),0) FROM {$activeRep['db']['prefix']}UsageData
                            WHERE identifierid=U.identifierid
                            AND servicetypeid=$servicetype_fulltext->id
                            AND " . $timePeriod . "
                    ) AS counter ";
                }
                break; 
            case 'counter_abstract':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='abstract'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_abstract = @mysql_fetch_object($res);
                if ($servicetype_abstract) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(counter),0) FROM {$activeRep['db']['prefix']}UsageData
                        WHERE identifierid=U.identifierid
                        AND servicetypeid=$servicetype_abstract->id
                        AND " . $timePeriod . "
                    ) AS counter_abstract ";
                }
                break; 
            case 'robots':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='fulltext'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_ALL = @mysql_fetch_object($res);
                if ($servicetype_ALL) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(robots),0) FROM {$activeRep['db']['prefix']}UsageData
                            WHERE identifierid=U.identifierid
                            AND servicetypeid=$servicetype_ALL->id
                            AND " . $timePeriod . "
                    ) AS robots ";
                }
                break;
            case 'robots_abstract':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='abstract'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_abstract = @mysql_fetch_object($res);
                if ($servicetype_abstract) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(robots),0) FROM {$activeRep['db']['prefix']}UsageData
                            WHERE identifierid=U.identifierid
                            AND servicetypeid=$servicetype_abstract->id
                            AND " . $timePeriod . "
                    ) AS robots_abstract ";
                }
                break;
            case 'ifabc':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='ALL'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_ALL = @mysql_fetch_object($res);
                if ($servicetype_ALL) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(ifabc),0) FROM {$activeRep['db']['prefix']}UsageData
                            WHERE identifierid=U.identifierid
                            AND servicetypeid=$servicetype_ALL->id
                            AND " . $timePeriod . "
                    ) AS ifabc ";
                }
                break;
            case 'logec':
                $res = mysql_query("SELECT id FROM {$activeRep['db']['prefix']}ServiceType WHERE servicetype='ALL'");
                if (!$res) {
                    die('MySQL Query Error: ' . mysql_error());
                }
                $servicetype_ALL = @mysql_fetch_object($res);
                if ($servicetype_ALL) {
                    $standardsQuery .= ", (SELECT IFNULL(SUM(logec),0) FROM {$activeRep['db']['prefix']}UsageData
                            WHERE identifierid=U.identifierid
                            AND servicetypeid=$servicetype_ALL->id
                            AND " . $timePeriod . "
                    ) AS logec ";
                }
                break;
        }
    }

    stdOut("Fetching data from database for timespan $from - $until for identifier " . $activeRep['identifier'] . "...","", true);
    $statistics = 
        "SELECT
                DISTINCT identifier," . $dateOutput . " AS timeframe " . $standardsQuery . 
        "FROM
                {$activeRep['db']['prefix']}Identifier AS I
        LEFT JOIN
                {$activeRep['db']['prefix']}UsageData AS U
        ON
                I.id=U.identifierid
        WHERE
                identifier LIKE '" . mysql_real_escape_string($activeRep['identifier']) . "'
                AND date>='" . mysql_real_escape_string($from) . "'
                AND date<='" . mysql_real_escape_string($until) . "'
                AND ((counter+robots)!=0)
        GROUP BY identifier, timeframe;";

    $resultStatistics = mysql_query($statistics);
    if (!$resultStatistics) {
        die('MySQL Query Error: ' . mysql_error());
    }
    return $resultStatistics;
}


/**
 * Calls the different output format functions depending on $activeRep['format']
 * 
 * @param $activeRep the array of the active Repository
 * @param $from start time
 * @param $until end time
 */
function exportData($activeRep, $from, $until) {
    global $path;
    $fileWasWritten = false;

    $statistics = callDatabase ($activeRep, date('Y-m-d', $from), date('Y-m-d', $until));
    if(mysql_num_rows($statistics) != 0) {
        $folderName = createFolderName($path, $activeRep['dir'], $until);
        createFolder($folderName);

        switch($activeRep['format']){
            case 'json':
                $pathName = createFilePath($from, $until, '.json', $folderName);
                if(checkWritable($pathName)) {
                    if ($count = createJson($activeRep['allStandards'], $pathName, $statistics, $from, $until))
                        $fileWasWritten = true;
                }
                break;
            case 'csv':
                $pathName = createFilePath($from, $until, '.csv', $folderName);
                if(checkWritable($pathName)) {
                    if ($count = createCsv($activeRep['allStandards'], $pathName, $statistics, $from, $until))
                        $fileWasWritten = true;
                }                
                break;
            case 'counterxml':
                $pathName = createFilePath($from, $until, '_counter.xml', $folderName);
                if(checkWritable($pathName)) {
                    if ($count = createCounterXml($activeRep['allStandards'], $pathName, $statistics, $from, $until))
                        $fileWasWritten = true;
                }
                break;
            case 'excelxml':
                $pathName = createFilePath($from, $until, '_counter.xml', $folderName);
                
                 if (($activeRep['period'] != 'month') 
                    || (($activeRep['time-per-file'] != 'year') 
                    && ($activeRep['time-per-file']!= 'all'))) {
                    StdOut("Counter.org Excel XML can only be written for the count of one month and a timespan of a year or the whole timespan. Is now set automatically.", "WARNING");
                    $activeRep['period'] = 'month';
                    $activeRep['time-per-file'] = 'year';
                }
                
                if(checkWritable($pathName)) {
                    if (createExcelXml($activeRep, $pathName, $statistics, $from, $until))
                        $fileWasWritten = true;
                    }
                break;
                
            default:
                stdOut("Wrong format given on ID " . $activeRep['id'] . ".","ERROR");
                return false;
        }  
        
        //write index file
        if ($fileWasWritten) {
            $indexLocation = $path . $activeRep['dir']. "index.json";

            $folderNameWithoutPrefix = createFolderName("",$activeRep['dir'], $until);
            $pathWithoutPrefix = createFilePath($from, $until, "." . $activeRep['format'], $folderNameWithoutPrefix);
            writeIndexFile($pathName, $from, $until, $count, $indexLocation, $pathWithoutPrefix); 
        }

       
    } else {
        stdOut("Database empty, no output for this time.","", true);
        return false;
    };
}


//PROGRAM MAIN ENTRY


// fetching server time,
$startTimestamp = time(); 
$endTimestamp = $startTimestamp;

// assigning the array of repositories to activeReps
$activeReps = $reps; 

$id=false;
$verboseOutputMode = false;
$overwrite = false;
$webOutputMode = false;

$opts = getopt('i:I:f:u:e:vp:t:ohwzs:k:');

// toggle verbose mode 
if (isset($opts['v'])) {
    $verboseOutputMode = true;
}

// toggle web mode 
if (isset($opts['w'])) {
    $webOutputMode = true;
}

// set id 
if (isset($opts['i'])) {
    foreach($reps as $rep) {
        if ($opts['i'] == $rep['id']) {
            $activeReps = array();
            $activeReps[0] = $rep;
            stdOut("ID " . $activeReps[0]['id'] . " gets an output", "", true);
            $id = true;
            break;
        }
    }
    if (!$id) { 
            stdOut("No such ID", "ERROR");
            exit();
    }
}

// set identifier
if (isset($opts['I'])) {
    $activeReps[0]['identifier'] = $opts['I'];
    stdOut("Identifier is set to " . $opts['I']);
}

// set start timestamp 
if (isset($opts['f'])) {
    $startTimestamp = strtotime($opts['f']);
    if (!$startTimestamp) {
        stdOut("Wrong format of starttime given. Try YYYY-MM-DD", "ERROR");
        exit();
    } 
    if ($startTimestamp > time()) {
        stdOut("Starttime lies in future.", "ERROR");
        exit();
    }
    stdOut("Start date is: " . date('Y-m-d', $startTimestamp), "", true);
}

// set end timestamp 
if (isset($opts['u'])) {
    $endTimestamp = strtotime($opts['u']);
    if (!$endTimestamp) {
        stdOut("Wrong format of endtime given. Try YYYY-MM-DD", "ERROR");
        exit();
    } 
    if ($endTimestamp >= time()) {
        stdOut("Endtime lies in future. New Endtime: Today.", "WARNING");
        $endTimestamp = time();
    }
    if ($endTimestamp < $startTimestamp) {
        stdOut("Endtime is before Starttime.", "ERROR");
        exit();
    }
    stdOut("End date is: " . date('Y-m-d', $endTimestamp), "",true);
}

// overwrite format stated in config.php 
if (isset($opts['e'])) {
    if ($id) {
        $activeReps[0]['format'] = $opts['e']; // format is overwritten
        stdOut("Format is: " . $activeReps[0]['format'], "",true);       
    }
    else {
        stdOut("New format can only be assigned to one specific ID.", "ERROR");
        exit();
    }
    
}

// overwrite period stated in config.php 
if (isset($opts['p'])) { 
    if ($id) {
        $activeReps[0]['period'] = $opts['p'];
        stdOut("Period is: " . $activeReps[0]['period'], "",true);
    }
    else {
        stdOut("New period can only be assigned to one specific ID.", "ERROR");
        exit();
    }
}

// overwrite time-per-file stated in config.php 
if (isset($opts['t'])) {
    if ($id) {
        $activeReps[0]['time-per-file'] = $opts['t'];
        stdOut("Time-per-file is: " . $activeReps[0]['time-per-file'], "",true);
    }
    else {
        stdOut("New time-per-file can only be assigned to one specific ID.", "ERROR");
        exit();
    }
}

// targz toggle
if (isset($opts['z']) && (!$webOutputMode)) {
    $activeReps[0]['targz'] = true;
    stdOut("A zipped container will be created for finished months", "",true);
}

// overwrite standards stated in config.php 
if (isset($opts['s'])) {
    unset($activeReps[0]['allStandards']);
    if (!is_array($opts['s']))
        $opts['s'] = array($opts['s']);
    foreach ($opts['s'] as $key => $standard)
      if(in_array($standard,array("counter","counter_abstract","robots","robots_abstract","logec","ifabc"))) {
        $activeReps[0]['allStandards'] = $opts['s'];
        stdOut("Standard that will get an output: " . $standard);
    } else
    {
        stdOut("Standard not found: " . $standard);
        unset($activeReps[0]['allStandards'][$key]);
    }
}


if (isset($opts['k'])) {
    if ($webOutputMode && ($opts['k'] != $activeReps[0]['key'])) {
        die('Wrong Security Key! Access not allowed.');
    }
}



if (!isset($opts['k']) && ($webOutputMode)) {
    die('Missing Security Key! Access not allowed.');
}


// set to overwrite existing files
if (isset($opts['o'])) {
    $overwrite = true;
}

// show options 
if (isset($opts['h'])) {
    echo 'Options:
    
    -i <ID>
        only ID gets an output. 
       
    -I <identifier>
        identifier which will get an output (should end with a "%", unless you want an output for a single identifier")

    -f <YYYY-MM-DD>
        start time of output
      
    -u <YYYY-MM-DD>
        end time of output 
          
    -e <format>
        define format of output (specific id is needed)

    -p: 
        the count will be done for the specified period (specific id is needed)
        command:    
        
        day
            output listed for each day

        week
            output listed for each day            

        month
            output listed for one month

        year
            output listed for one year

    -t: 
        the time period listed in one file (specific id is needed)
        command:    
        
        day
            one day

        week
            one week            

        month
            one month

        year
            one year

        all
            whole timespan 

    -s:
        the standards which should be incorporated (use command multiple times for more than one standard)
        e.g. -s counter -s counter_abstract

        counter
            counter fulltext statistics 
        counter_abstract
            counter abstract statistics
        robots
            robots fulltext statistics
        robots_abstract
            robots abstracts statistics
        (ifabc)
            not fully implemented
        (logec)
            not fully implemented

    -o:
        toggle overwrite on

    -z:
        create a tar zip for finished month
        command:

        true
            A zipped container will be created
        false 
            No zipped container will be created
            
    -w:
        web-mode. Outputs everything to console.

    -v:
        verbose mode
    
    -h:
        this' . "\n";
        exit();
}

// Check for day and write data to files accordingly. When started with manual timestamp it is repeated until today.
stdOut("Starting Output...");

do {
    foreach ($activeReps as $activeRep) {        
        switch ($activeRep['time-per-file']) {
            case 'day':
                // daily output 
                $from = strtotime($activeRep['outputDay']['day'],$startTimestamp);
                $until = strtotime($activeRep['outputDay']['day'],$startTimestamp);
                exportData($activeRep, $from, $until);

                // create archive for the last month
                if ((date('d', $from)==1) && ($activeRep['targz'] !== false)) {
                    $firstDayOfLastMonth = strtotime('first day of last month', $from);
                    $lastDayOfLastMonth = strtotime('last day of last month', $from);
                    $folderName = createFolderName($path, $activeRep['dir'], $lastDayOfLastMonth);
                    $archiveName = createFileName($firstDayOfLastMonth, $lastDayOfLastMonth,'.tar.gz');
                    writeArchive($folderName, $archiveName);
                }
                break;
            case 'week':
                // output for a week
                if (date('N', $startTimestamp)==$activeRep['outputDay']['week']) { 
                    $from = strtotime('-2 monday',$startTimestamp);
                    $until = strtotime ('sunday last week',$startTimestamp);
                    // on a monthly allowance do 2 outputs: one until the last day of the last month, one from the first day of the timestamp month
                    if (date('m', $until)<>date('m', $from)) {
                        $firstDayOfLastMonth = strtotime('first day of last month', $until);
                        $lastDayOfLastMonth = strtotime('last day of last month', $until);
                        $firstDayOfThisMonth = strtotime('first day of this month', $until);
                        exportData($activeRep, $from, $lastDayOfLastMonth);                        

                        // create archive for the last month
                        if ($activeRep['targz'] !== false) {
                            $folderName = createFolderName($path, $activeRep['dir'], $lastDayOfLastMonth);
                            $archiveName = createFileName($firstDayOfLastMonth, $lastDayOfLastMonth,'.tar.gz');
                            writeArchive($folderName, $archiveName);
                        }

                        $from = $firstDayOfThisMonth;              
                    }
                    exportData($activeRep, $from, $until);
                }
                break;
            case 'month':
                // output for a month
                if (date('d', $startTimestamp)==$activeRep['outputDay']['month']) { 
                    $from = strtotime('first day of last month', $startTimestamp);
                    $until = strtotime('last day of last month', $startTimestamp);
                    exportData($activeRep, $from, $until);

                    // create archive for the last month
                    if ($activeRep['targz'] !== false) {
                        $folderName = createFolderName($path, $activeRep['dir'], $until);
                        $archiveName = createFileName($from, $until,'.tar.gz');
                        writeArchive($folderName, $archiveName);
                    }
                }
                break;
            case 'year':
                // output from 1st of January until end of last month
                if (date('d', $startTimestamp)==$activeRep['outputDay']['month']) {
                    $from = strtotime(date('Y-01-01', strtotime('-1 month', $startTimestamp)));
                    $until = strtotime('last day of last month', $startTimestamp);
                    exportData($activeRep, $from, $until);

                     // create archive for the last month
                    if ($activeRep['targz'] !== false) {
                        $folderName = createFolderName($path, $activeRep['dir'], $until);
                        $archiveName = createFileName($from, $until,'.tar.gz');
                        writeArchive($folderName, $archiveName);
                    }
                }
                break;
            case 'all':
                $from = $startTimestamp;
                $until = $endTimestamp;
                exportData($activeRep, $from, $until);
                // only one run
                exit(); 
            default:
                stdOut("Wrong period given: Only 'day', 'week', month', 'year' and 'all' is allowed.", "ERROR");
                break;
        }
    }
    $startTimestamp = strtotime('+1 day',$startTimestamp);
} while ($startTimestamp < $endTimestamp);

?>