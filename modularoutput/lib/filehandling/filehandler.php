<?php

Class FileHandlerException extends Exception {};
Class FileHandlerOverwrittenForbiddenException extends FileHandlerException {};
Class FileHandlerDirectoryNotCreatedException extends FileHandlerException {};

/**
 * Class for general file handling
 *
 * @author Matthias Hitzler <hitzler@gbv.de> for VZG Göttingen
 * @version 1.0
 */
class FileHandler
{
	// is set if file exists
	private $exists = false;

	// the name of the file
	private $fileName;

	// the filehandler for the file operations
	private $file;

	public function __construct($fileName)
	{
		$this->fileName = $fileName;
		if (file_exists($this->fileName)) {
			$this->exists = true;
		}
	}


	// --- file handling operations ---

	public function openFile($openAttribute)
	{
		if (!$this->file = fopen($this->fileName, $openAttribute)) {
			throw new Exception('File cannot be opened');
		}
	}

	public function closeFile()
	{
		if (!fclose($this->file)) {
			throw new Exception('File cannot be closed');
		}
	}


	// --- content reading method ---

	/**
	 * Gets content from file
	 *
	 * @return the content of the file
	 */
	public function getContent()
	{
		if ($this->exists)
			return false;

		$this->openFile('rb');
		$fileContent = $this->readFile();
		$this->closeFile();
		return $fileContent;
	}


	// ---- file modifying methods ---

	/**
	 * Appends content to open file
	 * Creates file if not existing
	 *
	 * @param $content  the content which should be written to a file
	 */
	public function write($content)
	{
		$length = strlen($content);
		return fwrite($this->file, $content, $length);
	}

	/**
	 * Opens/Creates file, writes content and closes file
	 * Can create the file path
	 *
	 * @param $content 				the content to write
	 * @param $directoryCreation  	toggle for directory creation of the filepath
	 * @param $overwrite			toggle for overwriting
	 */
	public function writeFile($content,$directoryCreation = false, $overwrite = false)
	{
		$retValue = false;
		// if file exists but overwrite is switched off
		if (file_exists($this->fileName) && !$overwrite) {
    		throw new FileHandlerOverwrittenForbiddenException("File is not written because it already exists and overwrite is turned off.");
		}
		if ($directoryCreation) {
			$this->createFolder();
		}
		$this->openFile('w');
		$retValue = $this->write($content);
		$this->closeFile();
		return $retValue;
	}


	// --- archiving methods ---

	/**
	 * Archives all files in the given folder
	 *
	 * @param $archiveName  the name of the archive
	 */
	public function writeArchive($archiveName) {
	    $directory = dirname($this->fileName);
	    if (!file_exists($directory)) {
	    	//stdOut('Path is not existing: ' . $parts['dirname'], "", true); // durch Logging ersetzen
	    	return false;
	    }
	    // if (count(scandir($directory) > 2)) {
	    $archive = new PharData($archiveName);
	    try {
	    	$archive->buildFromDirectory($directory);
	    } catch (PharDataException $e) {
	    	throw $e;
	    }
	    //}
	}


	// --- internal methods ---

	protected function readFile()
	{
		$content = '';
		while (!feof($this->file)) {
			$content .= fread($this->file, 8192);
		}
		return $content;
	}

	protected function createFolder()
	{
		if (!$directory = dirname($this->fileName)) {
			//stdOut('No directory given in the filename ' . $this->fileName, "", true); // durch Logging ersetzen
			return false;
		}

		if (!file_exists($directory)) {
			if (!mkdir($directory, 0777, true)) {
				throw new FileHandlerDirectoryNotCreatedException("Directory could not be created");
			return false;
			}
			//stdOut('Folder ' . $directory . ' was created', "", true); // durch Logging ersetzen
			return true;
		}
		//stdOut('Folder ' . $directory . ' already existing', "", true); // durch Logging ersetzen
		return false;
	}
}
?>