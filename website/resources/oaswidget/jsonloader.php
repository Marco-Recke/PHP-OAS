<?php
/**
 * JSON- Downloader. Downloads JSON from OA- Statistics REST- Server and hides
 * user credentials.
 */

require_once("./generateparams.php");

// the additional parameters are necessary for variable repositories as used on the website
$additionalParameter = "";
if(substr($id, -1) == "%") {
    $additionalParameter .= "&summarized=true";
}

if(isset($rep)) {
    $additionalParameter .= "&id=" . $rep;
}

$URL = "https://" . $login . $config['apiurl'] . "/index.php?".
        "do=basic&".
        "format=json&".
        "addemptyrecords=true&".
        "granularity=$granularity&".
        "from=$from&".
        "until=$until&".
        "content=".$content."&".
        "identifier=$id" . "&".
        "informational=true&".
        $additionalParameter;

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
header('Content-Type: application/json');
echo $data;