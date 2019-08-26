<?php
// code aufgeräumt, POST entfernt, wir reagieren nur auf GET, POST hat im REST Kontext eine andere Bedeutung

require_once 'params.php';
require_once dirname(__FILE__).'/../../config.php';

/**
 * A param-converter module for "output". This converts HTTP-Rest
 * parameters.
 *
 * Use "config_params.php" to create a possible input-schema.
 *
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @subpackage input/params
 * @version 0.1
 */
class params_rest extends params {
    //internal translator
    private $translator = array(
        'id'                => 'id',
        'from'              => 'from',
        'until'             => 'until',
        'format'            => 'format',
        'identifier'        => 'identifier',
        'content'           => 'content',
        // 'describe'          => 'describe',
        'granularity'       => 'granularity',
        'classification'    => 'classification',
        // 'total'             => 'total',
        'addemptyrecords'   => 'addemptyrecords',
        'informational'     => 'informational',
        'summarized'        => 'summarized',
        'jsonheader'        => 'jsonheader',
        // 'rows'              => 'rows',
        'verbose'           => 'verbose',
        // 'nodownload'        => 'nodownload',
        // 'marcomode'         => 'marcomode'
    );

    private $db;

    function __construct(Database $db){
        $this->db = $db;

        // Now call Default constructor
        parent::__construct();
    }

    // Helper function
    protected function HTTP_getParams(){
       return $_GET;
    }

    protected function _determineMainParam(){
        $opts = $this->HTTP_getParams();

        // no mainparam
        if(!isset($opts['do'])){
            $this->_ErrorMainParamNotFound();
            return false;
        }

        // wrong mainparam
        if(!array_key_exists($opts['do'],$this->possibleParams)) {
            $this->_ErrorMainParamNotFound();
            return false;
        }

        // sets mainparam
        $this->_setMainParam($opts['do']);
    }

    protected function _translateParams(){
        $opts = $this->HTTP_getParams();

        foreach ($opts as $key => $value) {
            if ($key == 'do') //SKIP "do" parameter, because thats the mainparam
                continue;

            //If there is something that we don't understand;
            //the translation has failed
            if(!isset($this->translator[$key])){
                $this->_ErrorTranslationFailed($key);
                continue;
            }

            //Add value
            $this->setValue($this->translator[$key],$value);
        }
    }

    protected function _extrapolateParams(){
        //Extrapolate optional values

        global $config;

        // check for super-user
        if ($_SERVER['PHP_AUTH_USER'] == $config['rest']['superHTTPUser']) {
            // when in superuser/admin-mode and no id is set we use id=0 internally for possible
            // repository-independent actions
            if (!$this->issetPossibleParamArray('id')) {
                $data['id'] = 0;
                $data['default_identifier'] = '';
            } else {
                // if an id is set we take the repository-specific data
                $stmt = $this->db->dbc->prepare("SELECT * FROM DataProvider WHERE id=:id");
                $stmt->execute(array('id' => $this->getValue('id')));
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $stmt = $this->db->dbc->prepare("SELECT * FROM DataProvider WHERE httpuser=:user");
            $stmt->execute(array('user' => $_SERVER['PHP_AUTH_USER']));

            //Fetch associative array
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        //Copy all dataprovider-stuff into params-object
        $this->setValue('dataprovider', $data);

        //Fallback, because noone uses this at this moment
        $this->setValue('formatVersion', 4);

        //Nothing found?
        if(empty($data)) {
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
        //Set granularity to day by default
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


        $this->setValue('id', $data['id']);
    }

    public static function getHelpText(){
     return '';
    }
}

?>
