<?php
/*
 *	DatabaseTransfer_ColumnMap_Delimited maps multiple values to Omeka records based on a delimiter value
 *
 * @package DatabaseTransfer
 * @author Iwe Muiser
 * @copyright Meertens institute, 2012
*/

class DatabaseTransfer_ColumnMap_Delimited extends DatabaseTransfer_ColumnMap
{
    const DEFAULT_DELIMITER = ',';
	private $_delim = self::DEFAULT_DELIMITER;
	private $_elementId;
    private $_isHtml;

    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = DatabaseTransfer_ColumnMap::TARGET_TYPE_ELEMENT;
    }

    public function map($row, $result)
    {
        $rawTags = explode($this->_getDelimiter(), $row[$this->_columnName]);
        $trimmed = array_map('trim', $rawTags);
        $cleaned = array_diff($trimmed, array(''));
		$merge_set = array();
		foreach ($cleaned as $sub_item){
			$merge_set[] = array(
            	'element_id' => $this->_elementId,
            	'html' => 0,
            	'text' => $sub_item
        	);
		}
        $sub_items = array_merge($result, $merge_set);

        return $sub_items;
    }

	public function setDelimiter($delim){
		$this->_delim = $delim;
	} 

    private function _getDelimiter()
    {
        return $this->_delim;
    }
    public function setOptions($options)
    {
        $this->_elementId = $options['elementId'];
    }
}
