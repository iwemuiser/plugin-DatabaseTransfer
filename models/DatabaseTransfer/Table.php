<?php

class DatabaseTransfer_Table extends Zend_Db_Table_Abstract
{
	
	private $_info;
	
    public function getColumnNames() 
    {
        $_info = $this->info();
		return $_info["cols"];  
    }

    public function getColumnExamples() 
    {
        return $this->_getRandomExample();
    }

    public function getColumnExampleAsArray() 
    {
        $select  = $this->select()->limit(5000, 0); //get 5 first items from db
		$rows = $this->fetchAll($select);
		$rows = $rows->toArray();
		$onrblEdada = rand(0,count($rows));
		return $rows[$onrblEdada]; //return a random item from the set
    }

    public function getColumnAllAsArray() 
    {
        $select  = $this->select();
		$rows = $this->fetchAll($select);
		$rows = $rows->toArray();
		return $rows; 
    }

	private function _getRandomExample(){
		$_example = $this->find("*");
		print gettype($_example);
		return $_example;
	}
}
