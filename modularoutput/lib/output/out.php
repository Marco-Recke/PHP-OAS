<?php
/**
 * Baseclass for a possible output.  * 
 * 
 * is abstract, so don't try to instanciate this one
 *
 * @package modularoutput
 * @subpackage output/out
 * @version 0.2
 */
abstract class out {
    protected $content;
    protected $fileName;
    
    protected $contentType;
    
    protected $errOccured = false;
    protected $errMsg     = false;
    
    protected $contentTypes = array('json','xml','csv','html');
    
    function __construct($contentType) {
        $this->setContentType($contentType);
    }
    
    public function setContent($data){
        if (empty($data)) {
          $this->_ErrorDataEmpty();
          return false;
        }
        $this->content = $data;
    }
    
    public function getContent(){
        return $this->content;
    }
    
    public function setContentType($ctype){
        $found = false;
        
        foreach($this->contentTypes as $c){
            if($c == $ctype){
              $this->contentType = $c;
              $found = true;
            }
        }
        
        if(!$found){
            $this->_ErrorUnknownContentType($ctype);
            return false;
        }
        
        return true;
    }
    
    
    public function getContentType(){
        return $this->contentType;
    }

    public function setFileName($fileName)
    {
      $this->fileName = $fileName;
    }

    public function getFileName()
    {
      return $this->fileName;
    }


    
    /****************************
    *   abstract functions section
    *****************************/
    // abstract function startWriting();
    // abstract function endWriting();
    abstract function write();
    

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
      private function _Error($errorcode,$message) {
           $this->errOccured = true;
           $this->errMsg.="ERROR (OUT) #". $errorcode. ": " .$message."\n"; 
      }
      
      
      //error Messages
      private function _ErrorUnknownContentType($ctype) {
       $this->_Error(01, "False content type! '". $ctype. "' unknown!" ); }
      
      private function _ErrorWritingFailed($e) {
       $this->_Error(02, "Writing went wrong: ".$e->getMessage()) ;}

      private function _ErrorDataEmpty() {
        $this->_Error(03, "Data is empty");}
      
      private function _ErrorStartWritingFailed() {
       $this->_Error(10, "StartWriting failed!") ;}
      
      private function _ErrorEndWritingFailed() {
       $this->_Error(11, "Endwriting failed" ) ;}
    
} //class

?>
