<?php
require_once(dirname(__FILE__).'/filehandler.php');

/**
* Extended file handler specific to the modularoutput package
*
* @package modularoutput
* @version 0.1
*/
class FileProcessing extends FileHandler
{
   	/**
    * Creates name of a file dynamically
    *
    * @return the name of the file
    */
    public static function createDynamicFileName($contentType)
    {
      return "dynamic".$contentType."_". strtotime('now') ."_.".$contentType;
    }

    /**
    * Creates a filename from the from and until values and the content type
    *
    * @return the name of the file without an extension
    */
    public static function createFileNameFromDates($from, $until, $contentType)
    {
        return $from . '_' . $until . '.' . $contentType;
    }

    /**
    * Creates a filename from the the given word and the timecode
    *
    * @return the name of the file without an extension
    */
    public static function createFileNameFromWord($word, $contentType)
    {
        return $word . "_" . time() . "." . $contentType;
    }

    /**
	 * Creates path to file including directory
	 *
	 * @return the path
 	*/
	public static function createFilePath($path,$repositoryDirectory,$fileName,$date=false)
	{
        $folderName = $path . $repositoryDirectory . '/';

        if ($date) {
            $month=date('m',strtotime($date));
            $year=date('Y',strtotime($date));
            $folderName .= $year . '/' . $month . '/';
        }
    	return $folderName . $fileName;
	}
}