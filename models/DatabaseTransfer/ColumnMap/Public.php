<?php
class DatabaseTransfer_ColumnMap_Public extends DatabaseTransfer_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = DatabaseTransfer_ColumnMap::METADATA_PUBLIC;
    }

    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}