<?php

class DatabaseTransfer_ColumnMap_Set
{
    private $_maps = array();
    
    public function __construct(array $maps)
    {
        $this->_maps = $maps;
    }

    public function add(DatabaseTransfer_ColumnMap $map)
    {
        $this->_maps[] = $map;
    }

    public function map(array $row)
    {
        $allResults = array(
            DatabaseTransfer_ColumnMap::TARGET_TYPE_FILE => array(),
            DatabaseTransfer_ColumnMap::TARGET_TYPE_ELEMENT => array(),
            DatabaseTransfer_ColumnMap::TARGET_TYPE_TAG => array(),
            DatabaseTransfer_ColumnMap::METADATA_COLLECTION => null,
            DatabaseTransfer_ColumnMap::METADATA_FEATURED => null,
            DatabaseTransfer_ColumnMap::METADATA_ITEM_TYPE => null,
            DatabaseTransfer_ColumnMap::METADATA_PUBLIC => null
            
        );
        foreach ($this->_maps as $map) {
            $subset = $allResults[$map->getType()];
            $allResults[$map->getType()] = $map->map($row, $subset);
        }

        return $allResults;
    }
}
