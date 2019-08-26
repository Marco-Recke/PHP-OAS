<?php
require_once 'config_params.php';

/**
 *
 * A abstract class to make validation of input variables more easy
 * and to build easy to use and reuse interfaces.
 *
 * Use "config_params.php" to create a possible input-schemas.
 *
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @subpackage input/params
 * @version 0.2
 */
abstract class params extends configparams{

    protected $mainparam = '';

    protected $errorOccured  = false;
    protected $errMsg        = false;


    /****************************
    *   private functions section
    *****************************/

    /**
    * Checks if parameter has a value/is mandatory. Ignores values which are mandatory, but extrapolatable.
    * (provided by params_cli/params_rest or other tranlaters)
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool false if mandatory and has no value; else true
    */
    private function checkMandatory($paramArrayKey,$paramArray){
        //stop checking, if mandatory isn't set
        if(empty($paramArray['mandatory']) || !empty($paramArray['extrapolatable'])){
            return true;
        }

        if(!isset($paramArray['value']) ) {
            $this->_ErrorIsMandatory($paramArrayKey);
            return false;
        } else {
            return true;
        }
    }

    /**
    * Checks if parameter is mandatory, and the extrapolate-function
    * was already active.
    * (provided by params_cli/params_rest or other tranlaters)
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool false if mandatory and has no value; else true
    */
    private function checkExtrapolatableParams($paramArrayKey,$paramArray){
        //stop checking, if mandatory isn't set
        if(!isset($paramArray['mandatory']) || !isset($paramArray['extrapolatable'])){
            return true;
        }

        //If value is mandatory and hasn't been extrapolated, something went wrong
        if($paramArray['mandatory'] === true && $paramArray['extrapolatable'] === true){
            if( !isset($paramArray['value']) ){
                $this->_ErrorIsMandatory($paramArrayKey);
                return false;
            }
        }else{
            return true;
        }
    }

    /**
    * Checks if parameter is set, and it's dependencies
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool true if dependencies are okay
    */
    private function checkNeeds($paramArrayKey,$paramArray){

        if(!$this->issetPossibleParamArray($paramArrayKey)){
            return true;
        }

        if(!isset($paramArray['needs'])){
            return true;
        }

        foreach ($paramArray['needs'] as $needed) {
            if(!(isset($this->possibleParams[$this->mainparam][$needed]['value']))){
                $this->_ErrorInSufficientParams($paramArrayKey);
                return false;
            }
        }

        return true;
    }


    /**
    * Checks if value of "oneItem" parameter is really one
    * of the items given in array 'items'
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool true if given value is okay
    */
    private function checkOneItem($paramArrayKey,$paramArray){
        foreach($paramArray['items'] as $possibleItem){
            if($possibleItem == $paramArray['value']){
                return true;
            }
        }

        $this->_ErrorNotOneOf($paramArrayKey);
        return false;
    }

    /**
     * Checks if date is smaller or equals the other date
     * @param $paramArrayKey name/index of internal param
     * @param $paramArray array which consists the param-config of $paramArrayKey
     * @return true if date 1 is smaller/equals date 2
     */
    private function checkDates($paramArrayKey,$paramArray)
    {
       if(!$this->issetPossibleParamArray($paramArrayKey)){
            return true;
        }

        if(!isset($paramArray['smallerorequal'])){
            return true;
        }

        $date1 = new DateTime($this->possibleParams[$this->mainparam][$paramArrayKey]['value']);
        $date2 = new DateTime($this->possibleParams[$this->mainparam][$paramArray['smallerorequal']]['value']);

        if($date1 > $date2){
            $this->_ErrorWrongDates($paramArrayKey);
            return false;
        }

        return true;
    }


    /**
    * Checks if value of "bool" is TRUE or FALSE
    *
    * @return bool true if given value is okay
    */
    private function checkBool($paramArrayKey,$paramArray){
        $val = strtoupper($paramArray['value']);
        return($val == "TRUE" || $val == "FALSE");
    }

    /**
    * Checks if value of "multipleItem" parameter really consists
    * of the items given in array 'items'. Value of these parameters
    * are csv.
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool true if given values are okay
    */
    private function checkMultipleItem($paramArrayKey,$paramArray){

        $values = explode(',',$paramArray['value']);

        foreach($values as $curVal){

            $found = false;
            foreach($paramArray['items'] as $possibleItem){
                if($possibleItem == $curVal){
                    $found = true;
                }
            }

            if(!$found){
                $this->_ErrorNotOneOf($paramArrayKey);
                return false;
            }
        }

        return true;
    }

    /**
    * Checks if value of given items is correct type configured in config_params.
    *
    * @param $paramArrayKey name/index of internal param
    * @param $paramArray array which consists the param-config of $paramArrayKey
    *
    * @return bool true if given values are okay
    */
    private function checkType($paramArrayKey,$paramArray){
        if(!$this->issetPossibleParamArray($paramArrayKey)){
            return true;
        }

        //Determine Type
        switch($paramArray['type']){
            case 'number'       : $ok =  is_numeric($paramArray['value']); break;
            case 'string'       : $ok =  is_string ($paramArray['value']); break;
            case 'date'         : $ok =  strtotime ($paramArray['value']); break;
            case 'bool'         : $ok =  $this->checkBool($paramArrayKey, $paramArray)          ; break;
            case 'oneitem'      : $ok =  $this->checkOneItem($paramArrayKey,$paramArray)        ; break; //todo: when "checkOneItem" throws error, dont throw another one with "FalseType"
            case 'multipleitem' : $ok =  $this->checkMultipleItem($paramArrayKey,$paramArray)   ; break;
            case 'any'          : $ok = true; break;
            default             : $ok = false; break;
        }

        //trigger error
        if(!$ok){ $this->_ErrorFalseType($paramArrayKey); }

        //Return if error occured
        return $ok;
    }

    /**
    * Checks if value of given items are valid
    *
    * @return bool true if given values are okay
    */
     private function _checkParams(){

        if(!isset($this->mainparam) || $this->mainparam == ''){
            $this->_ErrorMainParamNotFound();
            return false;
        }

        //Todo: maybe there is a better/more efficient way?
        $this->callback_AllVars('checkMandatory');
        $this->callback_AllVars('checkType');
        $this->callback_AllVars('checkNeeds');
        $this->callback_AllVars('checkDates');
    }

    /****************************
    *   overload functions section
    *****************************/
    //Overload me..!
    abstract protected function _determineMainParam();

    //Overload me..!
    abstract protected function _translateParams();

    //Overload me..!
    abstract protected function _extrapolateParams();

    /****************************
    *  protected functions section
    *****************************/

    //needs to be called in subclass function _determineMainParam()
    protected function _setMainParam($val){
        $this->mainparam = $val;
    }

    protected function _getMainParam(){
        return $this->mainparam;
    }

    //helper function. all variables will be called
    //@param $callbackname callback function; callback
    //       needs 2 params: ($paramArrayKey,$paramArray)
    protected function callback_AllVars($callbackname){
        foreach ($this->possibleParams[$this->mainparam] as $key => $value) {
            call_user_func(array($this,$callbackname),$key,$value);
        }

    }

    //helper function. gets the paramconfig of $index
    //@param $paramArrayKey name/index of internal param
    protected function getPossibleParamArray($paramArrayKey){
        return ($this->possibleParams[$this->mainparam][$paramArrayKey]);
    }

    /****************************
    *  public functions section
    *****************************/
    //Returns value of param
    //@param $paramArrayKey name/index of internal param
    public function getValue($paramArrayKey){
        
        if(!isset($this->possibleParams[$this->mainparam][$paramArrayKey])){
            return "";
        }
        
        $type = $this->possibleParams[$this->mainparam][$paramArrayKey]['type'];

        if($this->issetPossibleParamArray($paramArrayKey)){
            $val  = $this->possibleParams[$this->mainparam][$paramArrayKey]['value'];
        }else{
            $val = "";
        }

        if($type == 'bool'){
            return (strtoupper($val) == "TRUE");

        } elseif ($type == 'multipleitem') {
            return explode(',',$val);

        }

        return $val;
    }

    //Returns value of param
    //@param $paramArrayKey name/index of internal param
    public function setValue($paramArrayKey,$val){
            if(isset($this->possibleParams[$this->mainparam][$paramArrayKey])){
                return $this->possibleParams[$this->mainparam][$paramArrayKey]['value'] = $val;
            }else{
                return false;
            }
    }

    //Returns array of params which have value
    //@param $paramArrayKey name/index of internal param
    public function getUsedValues(){

        $ret = array();
        foreach($this->possibleParams[$this->mainparam] as $paramArrayKey => $instance){
            if(isset($instance['value'])) {
                $ret[] = $paramArrayKey;
            }
        }

        return $ret;
    }

    //Returns associative array of params and their values
    public function getParams(){

        $ret = array();
        foreach($this->possibleParams[$this->mainparam] as $paramArrayKey => $instance){
            if(isset($instance['value'])) {
                // safe multiple items in an array
                if($instance['type'] == 'multipleitem') {
                        $ret[$paramArrayKey] = explode(',',$instance['value']);
                } else {
                    $ret[$paramArrayKey] = $instance['value'];
                }
            }
        }

        return $ret;
    }

    public function getMainParam()
    {
        return $this->mainparam;
    }

    //Returns if param has value
    //@param $paramArrayKey name/index of internal param
    public function issetPossibleParamArray($paramArrayKey){
        return (isset($this->possibleParams[$this->mainparam][$paramArrayKey]['value']));
    }

    //Returns readable string-table with all parameters and their values AFTER initialisation.
    public function explainParameterTable(){
        $usedParams = $this->getUsedValues();

        $mask = "|%9.9s |%-50.50s |\n";
        $table = "\n".sprintf($mask, 'Param', 'Value');
        $table .= sprintf($mask, str_repeat("-", 8), str_repeat("-", 49));
        foreach($usedParams as $paramArrayKey){
            $val = $this->possibleParams[$this->mainparam][$paramArrayKey]['value'];

            if(is_object($val) || is_array($val)){
                $table .= sprintf($mask, $paramArrayKey, 'complex object');
            }else{
                $table .= sprintf($mask, $paramArrayKey, $val);
            }
        }

        return(($table));
    }

    //Constructor, instance of params_cli/params_rest
    function __construct() {
        date_default_timezone_set('UTC');

        $this->_determineMainParam();

        $this->_translateParams();
        $this->_checkParams();

        $this->_extrapolateParams();
        $this->callback_AllVars('checkExtrapolatableParams');
    }



//ErrorSection
/***************************
*   Error handling section
*****************************/


  //Publics
  public function isError(){
      return $this->errorOccured;
  }

  public function getErrorMsg(){
      return $this->errMsg;
  }

  // Internal errors
  protected function _Error($exception, $altexception = "") {
      if($altexception != ""){
          $exception = $altexception;
      }

       $this->errorOccured = true;
       $this->errMsg.="ERROR (PARAMS)#". $exception->getCode() . ": " .$exception->getMessage() ."\n";

       throw $exception;
   }

  private function _ErrorFalseType($paramArrayKey) {

       $paramArray = $this->getPossibleParamArray($paramArrayKey);

       if(isset($paramArray['customException'])){
           $customException = $paramArray['customException'];
       }else{
           $customException = "";
       }

       $this->_Error(new ParameterBaseExceptionFalseType(
                "False type! '". $paramArrayKey. "' has to be a ".$paramArray['type'].
                ". (e.g. ".$paramArray['example'].")", 1 ),
               $customException

       );

  }

  private function _ErrorInSufficientParams($paramArrayKey) {

       $paramArray = $this->getPossibleParamArray($paramArrayKey);

       if(isset($paramArray['customException'])){
           $customException = $paramArray['customException'];
       }else{
           $customException = "";
       }

       $varsneeded = "";

       foreach($paramArray['needs'] as $var)
       {$varsneeded.="'".$var."';";}

       $this->_Error(new ParameterBaseExceptionInsufficienParameters(
               "inSufficientParams! (Param '". $paramArrayKey. "' needs ".$varsneeded = trim($varsneeded,';').')',2),
               $customException
       );
  }

  private function _ErrorWrongDates($paramArrayKey)
  {
       $paramArray = $this->getPossibleParamArray($paramArrayKey);

       if(isset($paramArray['customException'])){
           $customException = $paramArray['customException'];
       }else{
           $customException = "";
       }

       $this->_Error(new ParameterBaseExceptionWrongDates(
               "Wrong dates! '". $paramArrayKey . "'' must be smaller or equal '" . $paramArray['smallerorequal'] . "'",2),
               $customException
       );
  }

  private function _ErrorNotOneOf($paramArrayKey) {

       $paramArray = $this->getPossibleParamArray($paramArrayKey);

       if(isset($paramArray['customException'])){
           $customException = $paramArray['customException'];
       }else{
           $customException = "";
       }


       $varsneeded = "";

       foreach($paramArray['items'] as $var)
       {$varsneeded.="'".$var."';";}

       $this->_Error(new ParameterBaseExceptionNotOneOf(
               "notOneOf! (Param '". $paramArrayKey. "' has to be one of ".trim($varsneeded,';').')',3),
               $customException
       );
  }

  private function _ErrorIsMandatory($paramArrayKey) {
      $paramArray = $this->getPossibleParamArray($paramArrayKey);
      if(isset($paramArray['customException'])){
           $customException = $paramArray['customException'];
       }else{
           $customException = "";
       }

       $this->_Error(new ParameterBaseExceptionIsMandatory(
               "isMandatory! (Param '". $paramArrayKey. "' is mandatory but hasn't been declared!",4),
               $customException
       );
  }


   /***************************
   *   Error handling sections for overloaded classes
   *****************************/

   protected function _ErrorTranslationFailed($item) {
       $this->_Error(new ParameterChildExceptionTranslationFailed(
               "Fatal: translation of $item failed!",100)
       );
  }

   protected function _ErrorMainParamNotFound() {
       $this->_Error(new ParameterChildExceptionMainParamError(
               "Fatal: translation of 'mainparam' failed!",101)
       );
  }

  protected function _ErrorInconsistentValues($message) {
       $this->_Error(new ParameterChildExceptionInconsistentValues(
               "Fatal: " . $message,102)
       );
  }

  protected function _ErrorExtrapolateFailed($message) {
       $this->_Error(new ParameterChildExceptionExtrapolateFailed(
               "Fatal: " . $message,103)
       );
  }

}//class

//Error classes
  // Internal errors
  /*! basic exception class interface*/
class ParameterException extends Exception {
    function __construct($message='', $err) {
        parent::__construct("Parameter exception #".$err.": ".$message, $err);
    }

}//ParameterException

//Errors within params.php baseclass
class ParameterBaseException extends ParameterException {}

class ParameterBaseExceptionFalseType             extends ParameterBaseException{}
class ParameterBaseExceptionInsufficienParameters extends ParameterBaseException{}
class ParameterBaseExceptionNotOneOf              extends ParameterBaseException{}
class ParameterBaseExceptionIsMandatory           extends ParameterBaseException{}
class ParameterBaseExceptionWrongDates            extends ParameterBaseException{}

//errors within childclasses of params.php (like params_cli.php)
class ParameterChildException extends ParameterException{}

class ParameterChildExceptionTranslationFailed  extends ParameterChildException{}
class ParameterChildExceptionMainParamError     extends ParameterChildException{}
class ParameterChildExceptionInconsistentValues extends ParameterChildException{}
class ParameterChildExceptionExtrapolateFailed  extends ParameterChildException{}
?>
