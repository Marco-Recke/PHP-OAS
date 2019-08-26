<?php
/**
 * JSON- Downloader. Downloads JSON from OA- Statistics REST- Server and hides
 * user credentials.
 *
 * @author Paul Borchert <paul.borchert@gbv.de>
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');

require_once("./generateparams.php");

$URL = "https://".$login."oase.gbv.de/api/v1/index.php?".
        "do=basic&".
        "format=".$formatExtension."&".
        "addemptyrecords=true&".
        "granularity=$granularity&".
        "from=$from&".
        "until=$until&".
        "content=".$content."&".
        "identifier=$id" . "&".
        "informational=true";  

$data = @file_get_contents($URL, null, stream_context_create(array(
    'http' => array(
        'ignore_errors'    => true,
        'protocol_version' => 1.1,
        'header'           => array(
            'Connection: close'
        ),
    ),
)));


header($http_response_header[0]);
switch ($formatExtension) {
    case "xml":
        header('Content-Type: application/xml');
        break;
    case "csv":
        header('Content-Type: text/csv');
        break;
    default:
        header('Content-Type: application/json');
}
echo $data;
