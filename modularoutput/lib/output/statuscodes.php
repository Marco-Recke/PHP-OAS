<?php
/**
 * Class to provide a simple access to all standard https status codes 
 *
 * See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for further informations
 * 
 * @author Matthias Hitzler (hitzler@gbv.de) for VZG Göttingen
 * @version 0.1
 */
class StatusCodes {
	private static $httpVersion = 'HTTP/1.1';

	private static $statusCodes = array(
		// Informational 1xx
		100=>'100 Continue',
		101=>'101 Switching Protocols',
		102=>'102 Processing',
		// Successful 2xx
		200=>'200 OK',
		201=>'201 Created',
		202=>'202 Accepted',
		203=>'203 Non-Authoritative Information',
		204=>'204 No Content',
		205=>'205 Reset Content',
		206=>'206 Partial Content',
		// Redirection 3xx
		300=>'300 Multiple Choices',
		301=>'301 Moved Permanently',
		302=>'302 Found',
		303=>'303 See Other',
		304=>'304 Not Modified',
		305=>'305 Use Proxy',
		307=>'307 Temporary Redirect',
		// Client Error 4xx
		400=>'400 Bad Request',
		401=>'401 Unauthorized',
		402=>'402 Payment Required',
		403=>'403 Forbidden',
		404=>'404 Not Found',
		405=>'405 Method Not Allowed',
		406=>'406 Not Acceptable',
		407=>'407 Proxy Authentication Required',
		408=>'408 Request Timeout',
		409=>'409 Conflict',
		410=>'410 Gone',
		411=>'411 Length Required',
		412=>'412 Precondition Failed',
		413=>'413 Request Entity Too Large',
		414=>'414 Request-URI Too Long',
		415=>'415 Unsupported Media Type',
		416=>'416 Requested Range Not Satisfiable',
		417=>'417 Expectation Failed',
		// Server Error 5xx
		500=>'500 Internal Server Error',
		501=>'501 Not Implemented',
		502=>'502 Bad Gateway',
		503=>'503 Service Unavailable',
		504=>'504 Gateway Timeout',
		505=>'505 HTTP Version Not Supported'
		);


	/**
	 * Gets the status message
	 *
	 * @param 	the status code
	 * @return 	the status code message
	 */
	public static function getMessage($code)
	{
		return self::$statusCodes[$code];
	}

	/**
	 * Gets the status message formatted to send it directly as header
	 *
	 * @param 	the status code
	 * @return 	the status code message
	 */
	public static function getHeaderMessage($code)
	{
		return self::$httpVersion . ' ' . self::$statusCodes[$code];
	}
}

?>