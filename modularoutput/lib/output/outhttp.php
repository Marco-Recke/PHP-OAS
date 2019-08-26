<?php
require_once(dirname(__FILE__).'/out.php');
require_once(dirname(__FILE__).'/statuscodes.php');

/**
 * Class for writing the given content to the network
 *
 * @package modularoutput
 * @subpackage output/out
 * @version 0.2
 */
class outhttp extends out {
    private $httpStatusCode = 200;
    private $allowedMethods = array('GET');

    public function write() {
        $this->sendHeaders();
        // enable compression if the requestor accepts it
        // if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
        // above if statement should not be necessary as ob_gzhandler should handle
        // this by its own
        ob_start("ob_gzhandler");
        echo $this->content;
    }

    public function setAllowedHTTPMethods(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    public function setHttpStatusCode($httpStatusCode)
    {
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    private function sendHeaders()
    {
        header(StatusCodes::getHeaderMessage($this->httpStatusCode));
        // tell the requestor which requests are allowed
        header('Access-Control-Allow-Methods: '. implode(",", $this->allowedMethods));
        // no caching
        // header("Pragma: public");
        // header("Expires: 0");
        // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // header("Cache-Control: private",false);
        switch($this->contentType){
            case "json":
                header('Content-Type: application/json');
                break;

            case "xml":
                header('Content-Type: text/xml');
                break;

            case "csv":
                 header('Content-Type: text/csv');
                break;

            case "html":
                header('Content-Type: text/html');
                break;

            default:
                header("Content-Type: application/octet-stream");
                break;
        }
    }
}

?>
