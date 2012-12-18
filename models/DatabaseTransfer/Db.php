<?php
/*
*	Stolen UTF-8 database connection extension
*	from: Bastiaans Blog
*/


class DatabaseTransfer_Db extends Zend_Db_Adapter_Pdo_Mysql {  
  
   protected function _connect() {  
       // if we already have a PDO object, no need to re-connect.  
       if ($this->_connection)  
            return;  
  
        parent::_connect();  
  
        $this->query('SET NAMES utf8');  
        $this->query('SET CHARACTER SET utf8');  
    }  
}

?>