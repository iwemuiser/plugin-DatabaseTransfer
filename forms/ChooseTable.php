<?php
/**
 * The form on choosing a table from the specified DB
 *
 * @package DatabaseTransfer
 * @author Iwe Muiser
 * @copyright Meertens institute 2012
 */

class DatabaseTransfer_Form_ChooseTable extends Omeka_Form
{
    private $_tableNames = array();
	private $_aString;

    public function init()
    {
		parent::init();
        $this->setAttrib('id', 'databasetransfer-choosetable');
        $this->setMethod('post');
		
		$values = get_db()->getTable('Collection')->findPairsForSelectForm();

		$this->addElement('select', 'table_id', array(
		    'label' => 'Select Table Name',
		    'multiOptions' => $this->_tableNames,
		));
		
		//Set the collection that the imported items will belong to

		//next button!
        $this->addElement('submit', 'submit', array(
            'label' => 'Assign column names ->',
            'class' => 'submit submit-medium',
        ));
    }

    public function setTableNames($tableNames)
    {
        $this->_tableNames = $tableNames;
    }
    
	public function getTableNames()
    {
        return $this->_tableNames;
    }
}
