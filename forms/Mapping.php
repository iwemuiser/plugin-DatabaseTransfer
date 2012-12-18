<?php

/**
 * The form on csv-import/index/map-columns.
 *
 * @package DatabaseTransfer
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 */
class DatabaseTransfer_Form_Mapping extends Omeka_Form
{
    private $_itemTypeId;
    private $_columnNames = array();
    private $_columnExamples = array();


	/** Stolen by Iwe
	 * @return array
	 */
	function database_transfer_get_elements_by_element_set_name($itemTypeId)
	{
	    $params = $itemTypeId ? array('item_type_id' => $itemTypeId)
	                          : array('exclude_item_type' => true);
	    return get_db()->getTable('Element')->findPairsForSelectForm($params);
	}
	
    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'databasetransfer-mapping');
        $this->setMethod('post'); 

        $elementsByElementSetName = $this->database_transfer_get_elements_by_element_set_name($this->_itemTypeId);
        $elementsByElementSetName = array('' => 'Select Below') + $elementsByElementSetName;

	
        foreach ($this->_columnNames as $index => $colName) {
            $rowSubForm = new Zend_Form_SubForm();
            $rowSubForm->addElement('select',
                'element',
                array(
                    'class' => 'map-element',
                    'multiOptions' => $elementsByElementSetName,
                )
            );
            $rowSubForm->addElement('checkbox', 'html');
            $rowSubForm->addElement('checkbox', 'tags');
            $rowSubForm->addElement('checkbox', 'file');
	        $rowSubForm->addElement('text', 'column_delimiter', array(
	            'label' => 'Choose Column Delimiter',
	            'description' => "A single character that will be used to "
	                . "separate columns in the file to gain repeated elements.",
	            'value' => "",
	            'required' => false,
	            'size' => '1',
	            'validators' => array(
	                array('validator' => 'StringLength', 'options' => array(
	                    'min' => 1,
	                    'max' => 1,
	                    'messages' => array(
	                        Zend_Validate_StringLength::TOO_SHORT =>
	                            "Column delimiter must be one character long.",
	                        Zend_Validate_StringLength::TOO_LONG =>
	                            "Column delimiter must be one character long.",
	                    ),
	                )),
	            ),
	        ));
            $this->_setSubFormDecorators($rowSubForm);
            $this->addSubForm($rowSubForm, "row$index");
        }
//Additional row for user value
		$rowSubForm = new Zend_Form_SubForm();
		$rowSubForm->addElement('text', 'additional', array(
            'label' => 'Additional Element value for each imported Item',
            'value' => "",
            'required' => false,
            'size' => '25',
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 1,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            "Column delimiter must be one character long.",
                        Zend_Validate_StringLength::TOO_LONG =>
                            "Column delimiter must be one character long.",
                    ),
                )),
            ),
        ));
        $rowSubForm->addElement('select',
            'element',
            array(
                'class' => 'map-element',
                'multiOptions' => $elementsByElementSetName,
            )
        );
        $rowSubForm->addElement('checkbox', 'html');
        $this->_setSubFormDecorators($rowSubForm);
        $this->addSubForm($rowSubForm, "additional");
		
        $this->addElement('submit', 'submit',
            array('label' => 'Import Database Table',
                  'class' => 'submit submit-medium'));
    }

    public function loadDefaultDecorators()
    {
        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'index/map-columns-form.php',
                'itemTypeId' => $this->_itemTypeId,
                'form' => $this,
                'columnExamples' => $this->_columnExamples,
                'columnNames' => $this->_columnNames,
            )),
        ));
    }

    public function setColumnNames($columnNames)
    {
        $this->_columnNames = $columnNames;
    }

    public function setColumnExamples($columnExamples)
    {
        $this->_columnExamples = $columnExamples;
    }

    public function setItemTypeId($itemTypeId)
    {
        $this->_itemTypeId = $itemTypeId;
    }

    public function getMappings()
    {
        $columnMaps = array();
        foreach ($this->_columnNames as $key => $colName) {
            if ($map = $this->getColumnMap($key, $colName)) {
                $columnMaps[] = $map;
            }
        }
        return $columnMaps;
    }

    private function isDelimitedMapped($index)
    {
		$isDelimited = (strlen($this->getSubForm("row$index")->column_delimiter->getValue()) !== 0);
		if ($isDelimited){
        	return array("delim" => $this->getSubForm("row$index")->column_delimiter->getValue(), "id" => $this->_getRowValue($index, 'element'));
		}
		return false;
    }

    private function isTagMapped($index)
    {
        return $this->getSubForm("row$index")->tags->isChecked();
    }

    private function isFileMapped($index)
    {
        return $this->getSubForm("row$index")->file->isChecked();
    }

    private function getMappedElementId($index)
    {
        return $this->_getRowValue($index, 'element');
    }

    private function _getRowValue($row, $name)
    {
        return $this->getSubForm("row$row")->$name->getValue();
    }

    private function _setSubFormDecorators($subForm)
    {
        // Get rid of the fieldset tag that wraps subforms by default.
        $subForm->setDecorators(array(
            'FormElements',
        ));

        // Each subform is a row in the table.
        foreach ($subForm->getElements() as $el) {
            $el->setDecorators(array(
                array('decorator' => 'ViewHelper'),
                array('decorator' => 'HtmlTag',
                      'options' => array('tag' => 'td')),
            ));
        }
    }

    /**
     * @internal It's unclear whether the original behavior allowed a row to 
     * represent a tag, a file, and an HTML element text at the same time.  If 
     * so, that behavior is weird and buggy and it's going away until deemed 
     * otherwise.
     */
    private function getColumnMap($index, $columnName)
    {
        $columnMap = null;
        if ($this->isTagMapped($index)) {
           $columnMap = new DatabaseTransfer_ColumnMap_Tag($columnName);

        } else if ($settings = $this->isDelimitedMapped($index)) {
            $columnMap = new DatabaseTransfer_ColumnMap_Delimited($columnName);
			$columnMap->setDelimiter($settings["delim"]);
			$columnMap->setOptions(array('elementId' => $settings["id"]));
			
        } else if ($this->isFileMapped($index)) {
            $columnMap = new DatabaseTransfer_ColumnMap_File($columnName);

        } else if ($elementId = $this->getMappedElementId($index)) {
            $columnMap = new DatabaseTransfer_ColumnMap_Element($columnName);
            $columnMap->setOptions(array('elementId' => $elementId,
                                         'isHtml' => $this->_getRowValue($index, 'html')));
        }
        return $columnMap;
    }
}
