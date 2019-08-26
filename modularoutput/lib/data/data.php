<?php

class DataException extends Exception {}
class DataEmptyException extends DataException {}


/**
* class for implementing different data classes
*/
abstract class Data
{
	protected $data;

	public function getData()
	{
		return $this->data;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Creates the prefix for the tables where the data objects get their data from
	 * @param  $id the id of the specific dataprovider
	 * @return the prefix
	 */
	public function createPrefix($id)
	{
		return $id . '_';
	}
        
        public function getDataType(){
            return(get_class($this));
        }
}