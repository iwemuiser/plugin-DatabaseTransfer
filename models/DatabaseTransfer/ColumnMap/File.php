<?php

class DatabaseTransfer_ColumnMap_File extends DatabaseTransfer_ColumnMap
{
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = DatabaseTransfer_ColumnMap::TARGET_TYPE_FILE;
    }

    public function map($row, $result)
    {
        $urlString = trim($row[$this->_columnName]);
        if ($urlString) {
            $urls = explode(',', $urlString);
            $result[] = $urls;
        }
        return $result;
    }
}
