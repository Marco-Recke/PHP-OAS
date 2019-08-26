<?php
require_once(dirname(__FILE__).'/errorhandler.php');
require_once(dirname(__FILE__).'/outhttp.php');

/**
* Error Handling class for file output
*
* @package modularoutput
* @version 0.2
*/
class ErrorHandlerHttp extends ErrorHandler
{

    /**
     * errorHandler for HTTP Requests
     * Logs and sends http status codes and a json formatted error message to the REST interface and aborts program
     *
     * @param logger    $logger logger instance
     * @param exception $e      the exception
     */
    public static function handleError($logger,$e)
    {
        // log error
        $logger->log($e->getMessage(),1);

        $failureOutput = new outhttp('json');

        //retrieve error message
        $errMsg = self::getErrorMessage($e);

        //Set HTTP Status
        $httpStatus = StatusCodeTranslator::translateException($e);
        $failureOutput->setHttpStatusCode($httpStatus);

        //Send to client
        $failureOutput->setContent($errMsg);
        $failureOutput->write();
        
        //log sent error
        $logger->log('HTTP status code: ' . $httpStatus . '. Content: ' . $failureOutput->getContent(),1);      

        die();
    }

    private static function getErrorMessage($e){        
        $httpStatus = StatusCodeTranslator::translateException($e);
        
        //If HTTP Status is 500 or above, we have a strange, unknown failure. In this case we want to prevent
        //the original message to be seen by the user.
        if($httpStatus == 500){
            $message = "Internal Server Error. Please contact the administrator.";
        } else {
            $message = $e->getMessage();
        }
        
        
        $errorArray = array(
                'success' => "false",
                'error' => array(
                        'code' => $e->getCode(),
                        'message' => $message)
        );
        return json_encode($errorArray);
    }
}
