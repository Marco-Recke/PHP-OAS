<?php
require_once(dirname(__FILE__).'/errorhandler.php');
/**
* Error Handling class for file output
*
* @package modularoutput
* @version 0.2
*/
class ErrorHandlerFile extends ErrorHandler
{

    /**
     * Logs and aborts program
     *
     * @param $logger   the logger instance
     * @param $e        the exception
     */
    public static function handleError($logger,$e)
    {
        $logger->log($e->getMessage(),1);
        die();
    }
}
