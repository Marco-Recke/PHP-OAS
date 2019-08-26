<?php 
/**
* Translates exceptions to http status codes
*
* @author Matthias Hitzler (hitzler@gbv.de) for VZG Göttingen
* @version 0.1
*/
class StatusCodeTranslator
{
	static private $statusCodeTranslate = array(
		// No Content
		204 => array(
			'DataEmptyException',
		),
		// Bad Request
		400 => array(
			'ParameterException',
		),
		// Unauthorized
		401=>array(),
		// Payment Required
		402=>array(),
		//Forbidden
		403=>array(
			'WrongPasswordException',
			'WrongIdException',
                        'ParameterChildExceptionInconsistentValues',
                        'ParameterChildExceptionWrongUserCredentials'
		),
		// Not Found
		404=>array(),
		// Method Not Allowed
		405=>array(
			'WrongRequestException',
		),
		// Not Acceptable
		406=>array(),
		// Proxy Authentication Required
		407=>array(),
		// Request Timeout
		408=>array(),
		// Conflict
		409=>array(),
		//Gone
		410=>array(),
		// Length Required		
		411=>array(),
		// Precondition Failed
		412=>array(),
		// Request Entity Too Large
		413=>array(),
		// Request-URI Too Long
		414=>array(),
		// Unsupported Media Type
		415=>array(),
		// Requested Range Not Satisfiable
		416=>array(),
		// Expectation Failed
		417=>array(),

		// Internal Server Error
		500=>array(
			'PDOException',
		),
		// Not Implemented
		501=>array(
			'DatafetchingXMLNotValidException',
			'UserNotConfigured'),
		// Bad Gateway
		502=>array(),
		// Service Unavailable
		503=>array(),
		// Gateway Timeout		
		504=>array(),
		// HTTP Version Not Supported
		505=>array()
	);
        
        static private $SUSHIstatusCodeTranslate = array(
                //Service not available
                1000 => array('UserNotConfigured'),
		// Requestor Not Authorized to Access Service
		2000 => array('WrongIdException'),
                // Requestor Not Authorized to Access Usage for Institution
                2010 => array('WrongPasswordException', 'ParameterChildExceptionWrongUserCredentials'),
                //No Usage Data available for Requested Dates
                3030 => array('DataEmptyException'),
		
                //Report Version Not Supported
                3000 => array('FormatNotSupported','ParameterChildExceptionReportNotSupported'),
                3010 => array('ParameterChildExceptionReportVersion'),
                // Invalid Date Arguments // TODO...WTF..?
		3020 => array('ParameterException')
         );
        
        public static function translateSushiException($e)
	{
		foreach (self::$SUSHIstatusCodeTranslate as $code=>$exceptions) {
			foreach ($exceptions as $exception) {		
				if ($e instanceof $exception) {
					return $code;
				}
			}
		}
		// fallback for all other exceptions: 500 Internal Server Error
		return 1000;
	}
        
        public static function translateSushiExceptionSeverity($e){
            $errNo = self::translateSushiException($e);
            
            if($errNo == 0)
                return "Info";
                
            if($errNo <= 999)
                return "Warning";
            
            if($errNo <= 1999)
                return "Fatal";
            
            if($errNo <= 3039)
                return "Error";
            
           if($errNo == 4000)
                return "Warning";
           
           //FALLBACK
           return "Fatal";
        }


        

	public static function translateException($e)
	{
		foreach (self::$statusCodeTranslate as $code=>$exceptions) {
			foreach ($exceptions as $exception) {		
				if ($e instanceof $exception) {
					return $code;
				}
			}
		}
		// fallback for all other exceptions: 500 Internal Server Error
		return 500;
	}
}