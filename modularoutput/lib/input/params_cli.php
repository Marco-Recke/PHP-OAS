<?php

/*
 * A param-converter module for "output". This converts CLI
 * parameters.
 *
 * Use "config_params.php" to create a possible input-schemas.
 */

/**
 * Description of params_cli
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @subpackage input/params
 * @version 0.1
 */

require_once 'params.php';

class params_cli extends params {


    private $translator = array(
        'i' => 'id',
        // 'k' => 'password',
        'f' => 'from',
        'u' => 'until',
        'e' => 'format',
        'I' => 'identifier',
        'c' => 'content',
        // 'd' => 'describe',
        // 'r' => 'rows',
        'g' => 'granularity',
        'C'  => 'classification',
        // 't' => 'total',
        'a' => 'addemptyrecords',
        'v' => 'verbose',
        'o' => 'overwrite',
        'n' => 'informational',
        's' => 'summarized',
        'j' => 'jsonheader'
    );

    private $optconfig = 'i:I:f:u:e:vg:ohzc:d:nshC:';

     private $db;
     function __construct(Database $db){
        $this->db = $db;

        //Now call Default constructor
        parent::__construct();
     }


    //This function has to call "$this->_setMainParam".
    //Possible mainparams can be found in the config
    protected function _determineMainParam(){
        $opts = getopt($this->optconfig);

        if(!isset($opts['d'])){
            $this->_ErrorMainParamNotFound();
            return false;
        }

        if(!array_key_exists($opts['d'],$this->possibleParams)) {
            $this->_ErrorMainParamNotFound();
            return false;
        }

        //important
        $this->_setMainParam($opts['d']);
    }


    //This function has to call "$this->setValue".
    //For example, this converter uses a array called "$this->translator"
    //to provide informations about how a CLI-parameter complies to
    //the given parameter-config (config_params.php)

    //If this function fails somehow, it should call $this->_ErrorTranslationFailed().
    protected function _translateParams(){
        $opts = getopt($this->optconfig);

        //If HELP is requested, just show help and DIE.
        if(isset($opts['h']) || isset($opts['H'])){
            die($this->getHelpText());
        }

        foreach ($opts as $key => $value) {
            if ($key == 'd') //SKIP "d" parameter, because thats the mainparam
                continue;

            if(!isset($this->translator[$key])){
                $this->_ErrorTranslationFailed($key);
                continue;
            }
            if($this->possibleParams[$this->mainparam][$this->translator[$key]]['type'] == "bool") {
                $this->setValue($this->translator[$key],'TRUE');
            } else {
                $this->setValue($this->translator[$key],$value);
            }
        }


    }

    protected function _extrapolateParams(){
        //Extrapolate optional values
        $stmt = $this->db->dbc->prepare("SELECT * FROM DataProvider WHERE id=:id");
        $stmt->execute(array('id' => $this->getValue('id')));

        //Fetch associative array
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        //Copy all dataprovider-stuff into params-object
        $this->setValue('dataprovider', $data);

        //Fallback, because noone uses this at this moment
        $this->setValue('formatVersion', 4);

        //Nothing found or wrong password?
        if(empty($data) ) {
            $this->_ErrorExtrapolateFailed("No DataProvider integrated for the user.");
        }

        //Set Identifier if not set yet
        if (!$this->issetPossibleParamArray('identifier')) {
            $this->setValue('identifier', $data['default_identifier']);
        }

        //Set exactsearch flag
        if(substr($this->getValue('identifier'), -1) == "%"){
            $this->setValue('isexactsearch',"false");
        }else{
            $this->setValue('isexactsearch',"true");
        }

        // Defaults:

        if (!$this->issetPossibleParamArray('granularity')) {
            $this->setValue('granularity', 'day');
        }

        //Set content to counter by default
        if (!$this->issetPossibleParamArray('content')) {
            $this->setValue('content', 'counter');
        }

        //Set content to all classifications by default
        if (!$this->issetPossibleParamArray('classification')) {
            $this->setValue('classification', 'all');
        }

        //Set from by default
        if (!$this->issetPossibleParamArray('from')) {
            $this->setValue('from', '-3 days');
        }

        //Set until by default
        if (!$this->issetPossibleParamArray('until')) {
            $this->setValue('until', '-3 days');
        }

        // set jsonheader to default
        if (!$this->issetPossibleParamArray('jsonheader')) {
            $this->setValue('jsonheader', 'true');
        }
    }

    //This function just returns a text. If the main-program decides
    //to show the help text, this could be useful.
    public static function getHelpText(){
     return 'Options:

    -i <ID>
        id of repository

    -d <datatype>:
        the data which is returned

        basic
            the basic OAS output
        status
            some status data about the repository
        robots
            the robot list

    -I <identifier>
        identifier which will get an output (should end with a "%", unless you want an output for a single identifier")

    -f <YYYY-MM-DD>
        start time of output

    -u <YYYY-MM-DD>
        end time of output

    -e <format>
        define format of output

    -g:
        the count will be done for the specified period
        command:

        day
            output listed for each day
        week
            output listed for each day
        month
            output listed for one month
        year
            output listed for one year
        total
            output listed for all the time

    -c:
        the content which should be incorporated (use commas for more than one standard)
        e.g. -c counter or -c counter,robots,counter_abstract

        counter
            counter fulltext statistics
        counter_abstract
            counter abstract statistics
        robots
            robots fulltext statistics
        robots_abstract
            robots abstracts statistics

    -C:
        differentiates between access types (default: all) (use commas for more than one classification)
        e.g. -c all or -c institutional,administrative

        all
            all following types
        none
            accesses without a classification
        administrative
            administrative accesses
        institutional
            institutional accesses

    -o:
        toggle overwrite on

    -v:
        verbose mode

    -h:
        this' . "\n";
    }

}

?>
