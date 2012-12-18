<?php
class DatabaseTransfer_ColumnMap_Featured extends DatabaseTransfer_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = DatabaseTransfer_ColumnMap::METADATA_FEATURED;
    }

    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}