#!/usr/bin/env php
<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/lib/oai-harvester.php');
require_once(dirname(__FILE__).'/lib/oai-parser.php');
require_once(dirname(__FILE__).'/lib/statistics.php');
require_once(dirname(__FILE__).'/lib/aggregateusagedata.php');
require_once(dirname(__FILE__).'/lib/datautils.php');
require_once(dirname(__FILE__).'/lib/robotinterface.php');
require_once(dirname(__FILE__).'/lib/dp_data.php');

$loglevel = 15;

/*!
	simple logger that will prefix log messages with current date
	and outputs to the stdout

	\param $what log string
	\param $level log level
*/
function logger($what, $level=0) {
	global $loglevel;
	if($level <= $loglevel) {
		echo date('Y-m-d H:i:s')." ".str_replace("\n", "\n\t", $what)."\n";
	}
}

/*!
	when we are catching TERM signals, we will make that transparent
	by setting this global:
*/
$is_killed = false;

/*!
	this function can be used to determine by running tasks whether
	they should be stopped
*/
function is_aborted() {
	global $is_killed;
	return $is_killed;
}

/*!
	signal handler

	\param $signo the signal number (see Linux OS documentation)
*/
function signal_handler($signo) {
	global $is_killed;
	logger("Got signal $signo, quitting, please wait (or kill again to force quit)", 0);
	pcntl_signal($signo, SIG_DFL);
	$is_killed = true;
}

function checkMandatoryOpt($opts,$opt)
{
	if(!isset($opts[$opt]))
			throw(new Exception('option "-'.$opt.'" is mandatory'));
}

if(!extension_loaded('pcntl')) {
	logger("pcntl extension not active, won't catch signals", 0);
} else {
	declare(ticks = 1);
	pcntl_signal(SIGINT, "signal_handler");
	pcntl_signal(SIGTERM, "signal_handler");
	pcntl_signal(SIGHUP, "signal_handler");
}

/*! Exception triggered when called with wrong arguments */
class WrongUsageException extends Exception {}

/*! information about the arguments we're called with */
$opts=getopt('A:c:u:i:I:D:f:R:b:nl:qvp:t:kw:Th:F:S:C:U:r:o');
/*! if set, nothing will be written to or deleted from the database */
$noop = false;
// set if the "-n" argument was present
if(isset($opts['n'])) {
	$noop=true;
}

// log level setting
if(isset($opts['l'])) {
	// explicitly set log level
	$loglevel = (int)$opts['l'];
} else if(isset($opts['q'])) {
	// quiet operations: only most important messages will be printed
	$loglevel = 10;
} else if(isset($opts['v'])) {
	// verbose: output every log message
	$loglevel = 100;
}

/*! database connection */
$db = new DatabaseInterface($config, isset($opts['i']) ? $opts['i'] : 0,'logger', $noop);

//initialise dataprovidermanager
$dpmanager = new dp_data($db, 'logger');
if(isset($opts['i'])){
    //Load if id is specified.
    $dpmanager->loadbyID($opts['i']);
}
try {
	switch(@$opts['c']) {

	case 'aggregate':
		// aggregate statistics
		checkMandatoryOpt($opts,'i');
		$aggr = new AggregateUsageData($db, $dpmanager, 'logger', $noop, 'is_aborted');
		$aggr->from = @$opts['f'];
		$aggr->until = @$opts['u'];
		$aggr->run();
		break;
	case 'add-dp':
		// add a new data provider
		checkMandatoryOpt($opts,'u');
        checkMandatoryOpt($opts,'I');
        $dpmanager->add_data_provider($opts['u'], $opts['I']
        	,isset($opts['U']) ? $opts['U'] : "",isset($opts['w']) ? $opts['w'] : "");
		//$oaih=new OAIHarvester($db, 'logger', $noop, 'is_aborted');
		//$oaih->add_data_provider($opts['u']);
		break;

	case 'calculate':
		// calculate statistics
		checkMandatoryOpt($opts,'i');
		$stat = new Statistics($db, $dpmanager, 'logger', $noop, 'is_aborted', isset($opts['T']), isset($opts['r']) ? $opts['r'] : 'current', !isset($opts['o']));
		$stat->filter = @$opts['I'];
		$stat->from = @$opts['f'];
		$stat->until = @$opts['u'];
		$stat->run();
		break;
	case 'del-dp':
		// deletes a data provider
		if(isset($opts['u'])) {
			$db->deleteDataproviderURL($opts['u']);
		} elseif(isset($opts['i'])) {
			$db->deleteDataprovider($opts['i']);
		} else {
			throw(new WrongUsageException());
		}
		break;

	case 'add-robot':
		// add one or multiple robots to the database
		checkMandatoryOpt($opts,'S');
		if(isset($opts['R']) || isset($opts['F'])) {
			$rob = new RobotInterface($db, 'logger');
			$rob->setSource($opts['S']);
			if (isset($opts['C'])) {
				$rob->setComment($opts['C']);
			}
			if(isset($opts['R'])) {
				// add one robot
				$rob->addRobot($opts['R']);
			} else if (isset($opts['F'])) {
				// add a list of robots from a text file
				$rob->addRobotsFromFile($opts['F']);
			}
		}
		else {
			throw(new WrongUsageException());
		}
		break;

	case 'del-robot':
		$rob = new RobotInterface($db, 'logger');
		if(isset($opts['R'])) {
			$rob->deleteRobot($opts['R']);
		} else if(isset($opts['S'])) {
			$rob->deleteRobotsFromSource($opts['S']);
		} else {
			throw(new WrongUsageException());
		}
		break;

	case 'release-robotlist':
		$rob = new RobotInterface($db, 'logger');
		$rob->releaseRobotList();
		break;

	case 'info-robotlists':
		$du = new DataUtils($db, 'logger', $noop);
		$du->infoRobotsLists();
		break;

	case 'export-robotlist':
		$rob = new RobotInterface($db, 'logger');
		echo $rob->exportRobotList();
		break;

	case 'edit-dp':
		if(!isset($opts['i']))
			throw(new Exception('option "-i" is mandatory'));

		checkMandatoryOpt($opts,'i');
		if(isset($opts['U']))
			$dpmanager->set_httpuser($opts['U']);
		if(isset($opts['I']))
			$dpmanager->set_defaultIdentifier($opts['I']);
		if(isset($opts['w']))
			$dpmanager->set_websiteURL($opts['w']);
		break;

	case 'update-dp':
		checkMandatoryOpt($opts,'i');

		break;

	case 'info-dp':
		// outputs some information about data providers
		$du = new DataUtils($db, 'logger', $noop);
		if(isset($opts['i'])) {
			$du->infoDataProvider($opts['i']);
		} else {
			$du->infoDataProviderAll();
		}
		break;
	case 'harvest':
		// does a harvest run
		checkMandatoryOpt($opts,'i');
		$oaih=new OAIHarvester($db, 'logger', $noop, 'is_aborted');
		// check whether we are doing a segmented harvest
		$segment = false;
		if(isset($opts['t'])) {
			$segment=new DateInterval($opts['t']);
		}
		// check whether we should loop forever
		$loop = false;
		if(isset($opts['w'])) {
			$wait_up_to=new DateInterval($opts['w']);
			$loop = true;
		}
		// check if harvest parameters are given on the command line
		// possible parameters are "from" / "-f" and "until" / "-u"
		$enforce_from=false;
		if(isset($opts['f'])) {
			$enforce_from= new DateTime($opts['f']);
		}
		$enforce_until=false;
		if(isset($opts['u'])) {
			$enforce_until= new DateTime($opts['u']);
			// when an until parameter is set, we won't loop
			// since that would not make much sense, we have
			// all data at that point.
			$loop=false;
		}
		// start the harvest at a given time
		if(isset($opts['A'])) {
			$wait_for=strtotime($opts['A']) - time();
			if($wait_for < 0) {
				$wait_for += 24*60*60; // 1 day
			}
			if($wait_for > 0) {
				logger("waiting ".$wait_for." seconds before starting harvest", 10);
				sleep($wait_for);
				if(is_aborted()) {
					die();
				}
			}
		}
		do {
			$time_start=new DateTime();
			//$oaih->harvest($opts['i'],$enforce_from,$enforce_until,$segment);
                        $oaih->harvest($dpmanager,$enforce_from,$enforce_until,$segment);
			$enforce_from=false; // must be false after first loop
			$time_end=new DateTime();
			if($loop) {
				$time_next=$time_start;
				$time_next->add($wait_up_to);
				logger("next harvest was scheduled for ".$time_next->format('Y-m-d H:i:s'),10);
				$tdiff=$time_next->getTimestamp() - time();
				if($tdiff <= 0) {
					logger("starting right away.",10);
				} else {
					logger("waiting ".$tdiff." seconds.");
					sleep($tdiff);
				}
				if(is_aborted()) {
					$loop=false;
				}
			}
		} while($loop);
		break;
	case 'info-harvest':
		checkMandatoryOpt($opts,'i');
		// show information about harvests
		$du = new DataUtils($db, 'logger',$noop);
		if(isset($opts['h'])) {
			$du->infoHarvest($opts['h']);
		} else {
			$from = 0;
			if(isset($opts['f'])) {
				$from = strtotime($opts['f']);
			}
			$du->listHarvests($from);
		}
		break;
	case 'info-harvestdata':
		// show information about the database containing
		// the harvested data, i.e. the query replies
		checkMandatoryOpt($opts,'i');
		$du = new DataUtils($db, 'logger',$noop);
		$du->infoHarvestData();
		break;

	case 'info-robots':
		// show information about the robots which will be filtered
		$du = new DataUtils($db, 'logger',$noop);
		if(isset($opts['r']))
			$du->infoRobots($opts['r']);
		else
			$du->infoRobots();
		break;

	case 'info-robothits':
		// show information about the robots which will be filtered
		$du = new DataUtils($db, 'logger',$noop);
		$du->infoRobotHits();
		break;

	case 'info-robotstxthits':
		// output information about useragents which are not filtered yet but
		// accessed the robots.txt file
		$du = new DataUtils($db, 'logger',$noop);
		$du->infoRobotstxtHits();
		break;

	case 'parse':
		// parse data that is yet unprocessed, fill context object table
		checkMandatoryOpt($opts,'i');
		// check if we stay awake and wait for new records to process
		$keeprunning = isset($opts['k']);

		// set up profiling if "-p" parameter was specified
		$profile = false;
		if(isset($opts['p'])) $profile = fopen($opts['p'], 'w');
		$parser = new OAIParser($db, $dpmanager,'logger', $noop, $profile,"is_aborted");
		do {
			$parser->run();
			if($keeprunning) {
				logger("no more relevant HarvestData, waiting 60sec", 15);
				sleep(60); // wait 60secs before looking again for new datasets
				if(is_aborted()) {
					$keeprunning=false;
				}
			}
		} while($keeprunning);
		if($profile) fclose($profile);
		break;
	case 'cleanup-harvestdata':
		// clean up database
		checkMandatoryOpt($opts,'i');
		$du = new DataUtils($db, 'logger',$noop);
		$du->cleanupHarvestData();
		break;
	case 'cleanup-harvestdata-temp':
		// clean up database
		checkMandatoryOpt($opts,'i');
		$du = new DataUtils($db, 'logger',$noop);
		$du->cleanupHarvestDataTemp();
		break;
	case 'cleanup-ctxos':
		// clean up database
		checkMandatoryOpt($opts,'i');
		$du = new DataUtils($db, 'logger',$noop);
		checkMandatoryOpt($opts,'f');
		$du->cleanupCtxOs($opts['f']);
		break;
	case 'cleanup-records':
		// clean up database
		checkMandatoryOpt($opts,'i');
		$du = new DataUtils($db, 'logger',$noop);
		$du->cleanupRecords();
		break;

	case 'adm-blank':
        checkMandatoryOpt($opts,'i');

        $dpmanager = new dp_data($db, 'logger', $noop);
        $dpmanager->resetDP($opts['i']);

		break;

	case 'adm-create':
		$datautils = new DataUtils($db);
        $datautils->createDatabase();
		break;
	default:
		throw new WrongUsageException();
	}
} catch(WrongUsageException $e) {
	echo 'USAGE:

	php -f service-provider.php -c COMMAND [OPTIONS]

	COMMAND is one of:

	add-dp:
	  adds a data provider that is going to be harvested,
	  mandatory option is "-u <BaseURL>" and -I <default Identifier%>
	  an http user for the API can be added with "-U <username>"
	  and the website of the repository with "-w <websitename>"

	info-dp:
	  lists information about data providers (including their ID!)
	  accepts the option "-i <ID>" if you want to show information
	  about a single data provider rather than all

	edit-dp:
	  edits the data provider with the given ID "-i <ID>" (mandatory)
	  the default identifier can by added/changed with "-I <identifier>"
	  an http user for the API can be added/changed with "-U <username>"
	  and the website of the repository with "-w <websitename>"

	del-dp:
	  removes a data provider from the database
	  needs either option "-u <BaseURL>" or "-i <ID>" to specify
	  the data provider to be removed from the database

	harvest:
	  starts a harvest run for a certain or all data providers.
	  you must specify a single data provider by giving the
	  "-i <ID>" option.
	  you can also enforce a "from" parameter for the OAI request
	  by giving the "-f <datestamp>" option.
	  likewise, a the "until" parameter for the OAI request can
	  be set with the "-u <datestamp>" option.
	  if you specify the "-n" option, the harvest will be a dry-run
	  without any writes to the database.
	  if you specify the "-w <timespan>" option, the process will keep
	  running and after a harvest, it will wait for the remainder of
	  a configured time span (if any left) and start the next
	  harvest in a loop. The time span must be expressed in the
	  format expected by PHPs DateInterval class constructure.
	  E.g. for 5 minutes this would be "-w PT5M"
	  if you specify the "-t <timespan>" option, a harvest will be
	  done sequentially for "from"/"until" combination of a maximum
	  length of <time>. This value is again expressed in the format
	  that the DateInterval class expects.
	  lastly, if you specify the "-A <time>" option, the process
	  will wait until the specified time stamp before taking any
	  action. If you want to harvest at 0:30 (UTC), that would for
	  example be specified like this: "-A 0:30"

	info-harvest:
	  output information about individual harvests
	  (give ID via option -h) or alternatively list harvest
	  information for harvests started after a certain point in
	  time (-f option)
	  mandatory option is "-i <ID>"

	info-harvestdata:
	  display some statistics
	  mandatory option is "-i <ID>"

	cleanup-harvestdata:
	  will delete all stored OAI replies that were parsed
	  and will *also* delete all stored OAI replies which were
	  marked as being from an incomplete harvest.
	  This command can be savely called to clean up the
	  database at any time, however, a cron job is suggested.
	  mandatory option is "-i <ID>"

	cleanup-harvestdata-temp:
	  will delete all temporary data. Warning: do not use this while
	  still harvesting! you better do not automatize this.
	  mandatory option is "-i <ID>"

	parse:
	  starts a parsing run.
	  give "-k" option for keeping it running in an endless loop
	  you can start more than one parsing process if you like to.
	  you muss restrict parsing to records of a single
	  data provider. use the "-i <ID>" option in order to do that.

	cleanup-ctxos:
	  this expects a datestamp with the "-f" option. That datestamp
	  is used to find all context object information stored up to
	  (and including) that date - and deletes these, if they were
	  part of a calculation already.
	  the deletion must be restricted to the information
	  from a specific data provider, identified by the "-i <ID>"
	  option.
	  For example: "-f \'2011-12-31 23:59:59\' -i 5" will delete
	  all data up to the last second of 2011 that is stored for
	  the data provider identified by ID no 5.

	cleanup-records:
	  this is a maintenance task that will remove information about
	  harvested OAI-PMH records that do not have any corresponding
	  context object information stored. That can be because either
	  it was deleted (e.g. by the cleanup-ctxos command above) or
	  because there was no data in the record in the first place.
	  mandatory option is "-i <ID>"

	calculate:
	  calculate statistics. you can restrict what is looked at
	  by using the following options:
	  "-f <date>": start date
	  "-u <date>": end date
	  "-I <identifier>": a single identifier or an expression
	    that is understood by MySQLs LIKE operator, e.g. for a
	    certain identifier prefix it would be "-I oai:%"
	  You can also add "-T" flag to output information about
	  the actual counting.
	  mandatory option is "-i <ID>"
	  You can use "-r <RobotListVersionNumber>" to use a different
	  robot list instead of the current one (also: "-r unreleased"
	  uses the list with edited but not released robots).
	  You can use the "-o" flag to overwrite Usage Data for a given 
	  day even if there are no Context Objects

	aggregate:
	  aggregates the statistics in rollup tables . you can define
	  the time period that is aggregated by using the following
	  options:
	  "-f <date>": start date
	  "-u <date>": end date
	  If not set it looks up the earliest date available and
	  aggregates up to yesterday.
	  mandatory option is "-i <ID>"

'. /*
	adm-blank:
	  DANGER, DANGER: it clears the database, WITHOUT asking questions
	  you should probably remove this from the script when using
	  in production environments (but it\'s nice to do tests with!)
*/
'	adm-create:
	  this creates the tables that the Service Provider needs.
	  Make sure the database user has sufficient access rights.

	add-robot:
	  this changes the "unreleased" robot list not the current one,
	  you can do a calculate with this one, but by default the last
	  released list is used.
	  adds a robot or a bunch of robots from a text file.
	  Run with "-R <robot regex>" to add a robot
	  or with "-F <file>" to add robots from a text file (each line
	  = 1 robot)
	  "-S <source>": the source of the robot(s) (mandatory)
	  optional:
	  "-C <comment>": a comment which will be saved with the robot(s)

	del-robot:
	  deletes a robot or a bunch of robots from the database (unreleased
	  list). Run with "-R <robot regex"> to delete the specific robot
	  or with "-S <source>" to delete all robots from this source

	release-robotlist:
	  after editing the robot list (new robots or deletions),
	  you can release this list. It gets a new version number and
	  it used by default in the calculate.
	  Only do this when you are sure you want to release a new robot
	  list used for future calculations.
	  Once a version is released you can not change this list, you have
	  to release a new version.

	info-robots:
	  output a list with all robots in the list
	  "-r <version>": the robot list version (optional)

	info-robotlists:
	  output an overview of versions of robot-lists

	export-robotlist:
	  output a line broken list with all robot user agents in current list.
	  pipe this if you want an export of the current robot list for sharing

	info-robothits:
	  output a list with robot accesses

	OPTIONS known globally:
	  -l <NUM>
		set log level to <NUM>
	  -q
		be quiet (only serious errors are logged)
	  -v
		be verbose


	EXAMPLES:

	add a new data provider (last two parameters are optional):

		./service-provider.php -c add-dp -u http://some.dataprovider.de/ -I someprefix:% -U somehttpuser -w websiteofrepository

	list known data providers:

		./service-provider.php -c info-dp

	harvest a data provider, verbose debug output, in a loop while
	waiting the remainder of a 5 minute interval between calls, and
	separating a single harvest into steps of 2 days:

		./service-provider.php -c harvest -v -i 5 -w PT5M -t P2D

	parse all successfully harvested data in an endless loop:

		./service-provider.php -c parse -i 5 -k

	parse data from data provider ID number 5 until all according
	data is parsed:

		./service-provider.php -c parse -i 5

	do the counting for a given set of identifiers and a given
	range of days:

		./service-provider.php -c calculate -i 5 -I "oai:econstor.eu:%" -f 2012-05-01 -u 2012-05-31

	do the counting for yesterday:

		./service-provider.php -c calculate -i 5 -I "oai:econstor.eu:%" -f yesterday -u yesterday

	do the counting for the last two days:

		./service-provider.php -c calculate -i 5 -I "oai:econstor.eu:%" -f "-2 days" -u "-1 day"

	aggegrate the statistics to rollup tables

		./service-provider.php -c aggregate -i 5 -f "-2 days" -u "-1 day"
';
	die();
} catch(Exception $e) {
	throw $e;
}
