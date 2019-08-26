<?php
require_once(dirname(__FILE__).'/out.php');
require_once(dirname(__FILE__).'/../filehandling/fileprocessing.php');

/**
 * Class for writing the given content to a file
 *
 * @author Matthias Hitzler (hitzler@gbv.de) for VZG Göttingen
 * @package modularoutput
 * @subpackage output/out
 * @version 0.2
 */
class outfile extends out{

    /**
     * Writes the content to the file
     */
    public function write()
    {
        $fileHandler = new FileProcessing($this->fileName);
        $fileHandler->writeFile($this->content, true, $this->overwrite);
    }

    public function setOverwrite($overwrite)
    {
        $this->overwrite = $overwrite;
    }
}

?>
