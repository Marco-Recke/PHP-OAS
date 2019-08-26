<?php

/*
 * A param-converter module for "output". This converts SUSHI
 * parameters.
 *
 * Use "config_params.php" to create a possible input-schemas.
 */

/**
 * Description of params_rest
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for SUB Göttingen
 * @package output
 * @subpackage input/params
 * @version 0.1
 */
require_once 'params.php';

class params_sushi extends params {
    private $soapRequest = "";
    private $database;
    private $logger;

    //Individual constructor
   function __construct(Database $db, $soaprequestXML, $logger) {

       $this->soapRequest = $soaprequestXML;
       $this->database    = $db;
       $this->logger      = $logger;

       //Now add some SUSHI Specific variables

       //The name of the requestor. The Requestor ID is our password
       $this->possibleParams['stat']['RequestorName'] = array(
                                                              'type'            => 'string',
                                                              'example'         => 'OAS-Test',
                                                              'mandatory'       => true
                                                            );
       $this->possibleParams['stat']['RequestorMail'] = array(
                                                              'type'            => 'string',
                                                              'example'         => 'mail@oas.de',
                                                              'mandatory'       => true
                                                            );

       $this->possibleParams['stat']['CustomerName'] = array(
                                                              'type'            => 'string',
                                                              'example'         => 'VZG',
                                                              'mandatory'       => false
                                                            );

       $this->possibleParams['stat']['ReportType'] = array(
                                                              'type'            => 'oneitem',
                                                              'items'           => array('JR1'),
                                                              'example'         => 'JR1',
                                                              'mandatory'       => true,
                                                              'customException' => new ParameterChildExceptionReportNotSupported("Report Type not supported.",3)
                                                            );

       $this->possibleParams['stat']['ReportRequestID'] = array(
                                                              'type'            => 'string',
                                                              'example'         => '1',
                                                              'mandatory'       => true
                                                            );
       $this->possibleParams['stat']['ReportRequestDate'] = array(
                                                              'type'            => 'date',
                                                              'example'    => '2013-02-05',
                                                              'mandatory'       => true
                                                            );

       $this->possibleParams['stat']['ReportRequestDate']['customException'] = new ParameterChildExceptionInvalidDate("Invalid Date Arguments", 1);


       /*Important: Call parents constructor AFTER
         additional parameters are set! */
       parent::__construct();
   }

    protected function _determineMainParam(){
        $this->_setMainParam("stat");//SUSHI is only for statistics
    }


    //Needs to be filled by "extends"
    protected function _translateParams(){
        $opts = $this->soapRequest;

        //SUSHI-Specific field (see contructor)
        if(isset($opts['Requestor']['Name'])){
            $this->setValue('RequestorName',$opts['Requestor']['Name']);
        }

        if(isset($opts['Requestor']['Email'])){
            $this->setValue('RequestorMail',$opts['Requestor']['Email']);
        }

        //This is the ID of the REQUESTOR, which is the password in our database
        if(isset($opts['Requestor']['ID'])){
            $this->setValue('password',$opts['Requestor']['ID']); //Add value
        }

        //ID of DATAPROVIDER (= CUSTOMER)
        if(isset($opts['CustomerReference']['ID'])){
            $this->setValue('id',$opts['CustomerReference']['ID']);
        }

        //Optional: Name of requested DATAPROVIDER (= CUSTOMER)
        if(isset($opts['CustomerReference']['Name'])){
            $this->setValue('CustomerName',$opts['CustomerReference']['Name']);
        }

         if(isset($opts['ID'])){
            $this->setValue('ReportRequestID',$opts['ID']);
         }

         if(isset($opts['Created'])){
            $this->setValue('ReportRequestDate',$opts['Created']);
         }

        //Range FROM
        if(isset($opts['ReportDefinition']['Filters']['UsageDateRange']['Begin'])){
            $this->setValue('from',$opts['ReportDefinition']['Filters']['UsageDateRange']['Begin']);
        }

        //Range UNTIL
        if(isset($opts['ReportDefinition']['Filters']['UsageDateRange']['End'])){
            $this->setValue('until',$opts['ReportDefinition']['Filters']['UsageDateRange']['End']);
        }

        //Additional SUSHI parameter
        if(isset($opts['ReportDefinition']['Name'])){
            $this->setValue('ReportType',$opts['ReportDefinition']['Name']);
        }

        //Translate "ReportDefinition" und "ReportRelease" into internal-protocol-names
        switch ($this->getValue('ReportType')){
            case "JR1":
                $this->setValue('format','counterxml');
                $this->setValue('granularity','month');

                //TODO: Is Content fix?
                $this->setValue('content','counter,counter_abstract,robots,robots_abstract');
            break;
        }

        //Additional SUSHI parameter
        if(isset($opts['ReportDefinition']['Release'])){
            $this->setValue('formatVersion',$opts['ReportDefinition']['Release']);

            //Hack to prevent other releases
            if($this->getValue('formatVersion') != 4){
                $this->_Error(new ParameterChildExceptionReportVersion("Report version not supported. Release 4 is available.",3));
            }
        }
    }

//TODO Anpassen an HTTP User statt "Passwort", siehe params_rest
    protected function _extrapolateParams(){
        $stmt = $this->database->dbc->prepare("SELECT * FROM DataProvider WHERE id=:id");
        $stmt->execute(array('id' => $this->getValue('id')));

        //Fetch associative array
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        //Copy all dataprovider-stuff into params-object
        $this->setValue('dataprovider', $data);

        //Nothing found or wrong password?
//        if(empty($data) || ($data['out_password'] != $this->getValue('password')) ) {
//            //$this->_ErrorExtrapolateFailed("Unknown user/password");
//            $this->_Error(new ParameterChildExceptionWrongUserCredentials("Unknown RequestorID (password)/ or CustomerReference ID (repository ID)", 150));
//        }

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

//
//                //Report type
//        if(isset($opts['ReportDefinition']['Name'])){
//
//            $this->setValue('format','counterxml');
//            $this->setValue('granularity','month');
//
//            //FORMAT TODO: Formatstrings? //REPORTART! z.B. "Report 1 (J1)"
//            //if($opts['ReportDefinition']['Name'] == "Report 1 (J1)"){
//            //
//            //}
//        }


    }


    public static function getHelpText(){
     return '';
    }

}

    //Errors
    class ParameterChildExceptionWrongUserCredentials  extends ParameterChildException{};
    class ParameterChildExceptionReportNotSupported    extends ParameterChildException{};
    class ParameterChildExceptionReportVersion    extends ParameterChildException{};
    class ParameterChildExceptionInvalidDate    extends ParameterChildException{};

?>
