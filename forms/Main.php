<?php

/**
 * The form on csv-import/index/index.
 *
 * @package DatabaseTransfer
 * @author Iwe Muiser
 * @copyright Meertens institute, 2012
 */
class DatabaseTransfer_Form_Main extends Omeka_Form
{
	private $_db_host = "127.0.0.1";
	private $_db_user = "root";
	private $_db_pw = "";
	private $_db_name = "";

    private $_columnDelimiter = ',';
    private $_fileDestinationDir;
    private $_maxFileSize;

    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'databasetransfer');
        $this->setMethod('post');
		
		$this->_addDatabaseElements(); //add the elements for the database settings
		
		//Set the item type that the imported items have to belong to
        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        $values = array('' => 'Select Item Type') + $values;
        $this->addElement('select', 'item_type_id', array(
            'label' => 'Select Item Type',
            'multiOptions' => $values,
        ));


    	$this->addElement('checkbox', 'choosetable', array('value'=>true,  'hidden'=>true));

		//Set the collection that the imported items will belong to
        $values = get_db()->getTable('Collection')->findPairsForSelectForm();
        $values = array('' => 'Select Collection') + $values;
        $this->addElement('select', 'collection_id', array(
            'label' => 'Select Collection',
            'multiOptions' => $values,
        ));
		// public / featured?
        $this->addElement('checkbox', 'items_are_public', array(
            'label' => 'Make All Items Public?',
        ));
        $this->addElement('checkbox', 'items_are_featured', array(
            'label' => 'Feature All Items?',
        ));

		//next button!
        $this->addElement('submit', 'submit', array(
            'label' => 'Choose table ->',
            'class' => 'submit submit-medium',
        ));
    }

    public function isValid($post)
    {
        // Too much POST data, return with an error.
        if (empty($post) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = $this->getMaxFileSize()->toString();
            $this->csv_file->addError(
                "The file you have uploaded exceeds the maximum file size "
                . "allowed by the server. Please upload a file smaller "
                . "than $maxSize.");
            return false;
        }

        return parent::isValid($post);
    }

    private function _addDatabaseElements(){
	    $this->addElement('text', 'db_host', array(
            'label' => 'Fill out the hostname of the db (127.0.0.1)',
            'description' => "Enter the IP address of the host if you have a database present somewhere that you want to connect to.",
            'value' => $this->_db_host,
            'required' => true,
            'size' => '24',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                "Hostname must be filled in correctly.",
                      )),
                ),
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 7,
                    'max' => 200,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            "Hostname must be X.X.X.X",
                        Zend_Validate_StringLength::TOO_LONG =>
                            "Come on... it can't be this long.",
                    ),
                )),
            ),
        ));
		$this->addElement('text', 'db_user', array(
            'label' => 'Fill out the user name of the db (root?)',
            'value' => $this->_db_user,
            'required' => true,
            'size' => '24',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                "User name must be filled in correctly.",
                      )),
                ),
            ),
        ));
		$this->addElement('password', 'db_pw', array(
            'label' => 'Fill out the password of the user.',
            'value' => $this->_db_pw,
            'required' => true,
            'size' => '24',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                "Password must be filled in correctly.",
                      )),
                ),
            ),
        ));
		$this->addElement('text', 'db_name', array(
            'label' => 'Fill out the name of the database',
            'value' => $this->_db_name,
            'required' => true,
            'size' => '24',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                "Database name must be filled in correctly.",
                      )),
                ),
            ),
        ));
		
	}


    public function setDbUser($db_user)
    {
        $this->_db_user = $db_user;
    }
    public function setDbPw($db_pw)
    {
        $this->_db_pw = $db_pw;
    }
    public function setDbHost($db_host)
    {
        $this->_db_host = $db_host;
    }
    public function setDbName($db_name)
    {
        $this->_db_name = $db_name;
    }
    public function setColumnDelimiter($delimiter)
    {
        $this->_columnDelimiter = $delimiter;
    }

    private function _getSizeMeasure($size)
    {
        if (!preg_match('/(\d+)([BKMGT]?)/', $size, $matches)) {
            return false;
        }
        $sizeType = Zend_Measure_Binary::BYTE;
        // Why reimplement this?  Seems pointless, but no PHP API.
        $sizeTypes = array(
            'B' => Zend_Measure_Binary::BYTE,
            'K' => Zend_Measure_Binary::KILOBYTE,
            'M' => Zend_Measure_Binary::MEGABYTE,
            'G' => Zend_Measure_Binary::GIGABYTE,
            'T' => Zend_Measure_Binary::TERABYTE,
        );
        if (array_key_exists($matches[2], $sizeTypes)) {
            $sizeType = $sizeTypes[$matches[2]];
        }

        $measure = new Zend_Measure_Binary($matches[1], $sizeType);
        return $measure;
    }
}
