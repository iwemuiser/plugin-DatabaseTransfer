<?php
/**
 * The DatabaseTransfer index controller class.
 *
 * @package DatabaseTransfer
 * @author Iwe Muiser
 * @copyright Meertens institute, 2012
 */
class DatabaseTransfer_IndexController extends Omeka_Controller_AbstractActionController
{
    protected $_browseRecordsPerPage = 30;

    private $_pluginConfig = array();

    public function init()
    {
#		$this = new DatabaseTransfer_IndexController(); // instantiate $object explicitely
		print "init";
		$this->_log("init: %time%, %memory%");
        $this->sessie = new Zend_Session_Namespace('DatabaseTransfer');
        $this->_helper->db->setDefaultModelName('DatabaseTransfer_Import');
#        $this->_modelClass = 'DatabaseTransfer_Import'; #no longer supported
		print "/init";
    }

    public function preDispatch() //When is this fired?
    {
        $this->view->navigation($this->_getNavigation());
    }


    private function _getMainForm()
    {
        require_once DATABASE_TRANSFER_DIRECTORY . '/forms/Main.php';
#        $csvConfig = $this->_getPluginConfig();
#        $form = new DatabaseTransfer_Form_Main($csvConfig);
        $form = new DatabaseTransfer_Form_Main();
        return $form;
    }

    private function _getChooseTableForm(){
		require_once DATABASE_TRANSFER_DIRECTORY . '/forms/ChooseTable.php';
		#$csvConfig = $this->_getPluginConfig();
		#$form = new DatabaseTransfer_Form_Main($csvConfig);
		$form = new DatabaseTransfer_Form_ChooseTable();
		return $form;
	}

    private function _getNavigation()
    {
        return new Zend_Navigation(array(
            array(
                'label' => 'Select database',
                'action' => 'index',
                'module' => 'database-transfer',
            ),
            array(
                'label' => 'Browse',
                'action' => 'browse',
                'module' => 'database-transfer',
            ),
        ));
    }


	
    public function indexAction() //this happens when a form is submitted
    {
		print "indexAction";
		$this->sessie->testvalue = "TEEEEEEEEEEEEEEEEEEEEEEEEXT";
#		print $this->random_var->sub;
#		echo "<H1>TEST PRINT 1</H1>";
        $form = $this->_getMainForm();
        $this->view->form = $form;

		$this->_log("indexAction: %time%, %memory%");

        if (!$this->getRequest()->isPost()) {
            return;
        }
        if (!$form->isValid($this->getRequest()->getPost())) {
            return;
        }

#        $delimiter = $form->getValue('column_delimiter');
		$db_name = $form->getValue('db_name');
		$db_user = $form->getValue('db_user');
		$db_pw = $form->getValue('db_pw');
		$db_host = $form->getValue('db_host');
		$db = new DatabaseTransfer_Db(array( //call database for checking
	    	'host'     => $db_host,
	    	'username' => $db_user,
	    	'password' => $db_pw,
	    	'dbname'   => $db_name));
	
#		$table = new DatabaseTransport_Table($db_host, $db_user, $db_pw, $db_name);
		#echo $db->getConnection()->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

		if (!$db->getConnection()) {
			$this->_helper->flashMessenger('Database could not be reached. ', "error");
#			return $this->flashError('Database could not be reached. ' . $db->getErrorString());
        }
		
		//setting a whole bunch of nice session variables
        $this->sessie->originalDbname = $db_name; //replaced
        $this->sessie->columnDelimiter = $delimiter;
		$this->sessie->dbName = $db_name;
		$this->sessie->dbUser = $db_user;
		$this->sessie->dbPw = $db_pw;
		$this->sessie->dbHost = $db_host;
        $this->sessie->itemTypeId = $form->getValue('item_type_id');
        $this->sessie->itemsArePublic = $form->getValue('items_are_public');
        $this->sessie->itemsAreFeatured =  $form->getValue('items_are_featured');
        $this->sessie->collectionId = $form->getValue('collection_id');
        $this->sessie->ownerId = $this->getInvokeArg('bootstrap')->currentuser->id; //get the user that imported the stories
		$this->sessie->db = $db;
		$this->sessie->tableNames = $db->listTables();
		print "/indexAction";
		
        $this->_helper->redirector->goto('choose-table'); //after all is ok: redirect to the next step
    }
    
	public function chooseTableAction(){ //this triggers when the choose-table form button is pushed
		print "chooseTableAction";
		$this->_log("chooseTableAction: %time%, %memory%");
        require_once DATABASE_TRANSFER_DIRECTORY . '/forms/ChooseTable.php';
        $form = new DatabaseTransfer_Form_ChooseTable(array(
            'tableNames' => $this->sessie->tableNames,
        ));
		$form->setTableNames($this->sessie->tableNames); 
        $this->view->form = $form;			//plant the form in the active view
        if (!$form->isValid($this->getRequest()->getPost())) {
            return;
        }
        if (!$this->getRequest()->isPost()) { #returns with certainty in Omeka 2.0
            return;
        }
		print "<PRE>";
		//fetch a bunch of variables and check if ok before going to the next step
		// Anything printed/echoed below this line is not showed
		$this->sessie->tableId = $form->getValue('table_id');
		$this->sessie->dbTable = $this->sessie->tableNames[$this->sessie->tableId]; //get the actual name of the table
		$table = new DatabaseTransfer_Table(array('name' => $this->sessie->dbTable, 'db' => $this->sessie->db));

		$this->sessie->columnNames = $table->getColumnNames();
		$this->sessie->columnExamples = $table->getColumnExampleAsArray();
		print "/chooseTableAction";		
#		exit(0);
		$this->_helper->redirector->goto('map-columns'); //redirect if everything is valid
	}

    public function mapColumnsAction(){
		print "<PRE>mapColumnsAction";
		print_r($this->sessie);
		$this->_log("mapColumnsAction: %time%, %memory%");
		$this->_sessionIsValid();
        if (!$this->_sessionIsValid()) { //check if all the necessary variables have a value
			print "<br>session is not valid for some reason<br>";
			$this->_helper->flashMessenger('Some false session variables were detected.', "error");
#			$this->flashFaillure('Some false session variables were detected.');
	        
#            return $this->_helper->redirector->goto('index');
        }
		print_r($this->sessie->dbTable);
        require_once DATABASE_TRANSFER_DIRECTORY . '/forms/Mapping.php';
        $form = new DatabaseTransfer_Form_Mapping(array(
            'itemTypeId' => $this->sessie->itemTypeId,
            'columnNames' => $this->sessie->columnNames,
            'columnExamples' => $this->sessie->columnExamples,
        ));
		$form->setColumnNames($this->sessie->columnNames);
		$form->setColumnExamples($this->sessie->columnExamples);
		$form->setItemTypeId($this->sessie->itemTypeId);
        $this->view->form = $form;
        if (!$this->getRequest()->isPost()){
			print "no post values";
            return;
        }
        if (!$form->isValid($this->getRequest()->getPost())) {
			print "no valid post values";
            return;
        }
#		exit(0);
		//No show from here (except if redirect is not triggered)
        $columnMaps = $form->getMappings(); //mappings from form
        if (count($columnMaps) == 0) {
			$this->_helper->flashMessenger('Please map at least one column to an element, file, or tag.', "error");
#            return $this->flashError('Please map at least one column to an '
#                . 'element, file, or tag.');
        }
		
		print "trying to create DatabaseTransfer_import";
		
#		$this->_log("attempting to create new DatabaseTransfer_Import: %time%, %memory%");
        $databaseTransfer = new DatabaseTransfer_Import(); //this is an omeka record that keeps track of the progress and imports the new item
		print "DatabaseTransfer_import instance created";
		
		$databaseTransfer->setDbTable($this->sessie->dbTable);
#		$this->_log("new DatabaseTransfer_Import loaded: %time%, %memory%");
		print "dbTable set<br>";
		//a loop to transfer session variables to the DatabaseTransfer_Import class
        foreach ($this->sessie->getIterator() as $key => $value) { 
            $setMethod = 'set' . ucwords($key);
            if (method_exists($databaseTransfer, $setMethod)) {
                $databaseTransfer->$setMethod($value);
            }
        }
		print "session variables transferred<br>";
        $databaseTransfer->setColumnMaps($columnMaps);
        $databaseTransfer->setStatus(DatabaseTransfer_Import::QUEUED); //setting the status to QUEUED
#        $databaseTransfer->forceSave(); //saving status of import in database.
        $databaseTransfer->save(); //saving status of import in database.
		print "status saved (works)<br>";
		
        $dbConfig = $this->_getPluginConfig();

        $jobDispatcher = Zend_Registry::get('job_dispatcher');		//get Omeka job dispatcher
		print "job dispatcher instantiated<br>";
        $jobDispatcher->setQueueName('imports');					//give a que name
		print "sending task<br>";
        $jobDispatcher->send('DatabaseTransfer_ImportTask',
				array(
	                'importId' => $databaseTransfer->id,
	                'memoryLimit' => @$dbConfig['memoryLimit'],
	                'batchSize' => @$dbConfig['batchSize'],
	            )
		);
		print "task sent<br>";
        $this->sessie->unsetAll();
		$this->_helper->flashMessenger("Successfully started the import. Reload this page for status updates.", 'success');
#        $this->flashSuccess('Successfully started the import. Reload this page for status updates.');
        $this->_helper->redirector->goto('browse');

    }
    
    private function _getPluginConfig()
    {
        if (!$this->_pluginConfig) {
            $config = $this->getInvokeArg('bootstrap')->config->plugins;
            if ($config && isset($config->DatabaseTransfer)) {
                $this->_pluginConfig = $config->DatabaseTransfer->toArray();
            }
            if (!array_key_exists('fileDestination', $this->_pluginConfig)) { //meh
                $this->_pluginConfig['fileDestination'] =
                    Zend_Registry::get('storage')->getTempDir();
            }
        }
        return $this->_pluginConfig;
    }

    private function _sessionIsValid()
    {
#		foreach ($this->sessie as $index => $value) {
#		    print_r($index);
#			print " - ";
#			print_r($value);
#			print "__________<br>";
#		}
        $requiredKeys = array('itemsArePublic', 'itemsAreFeatured', 'collectionId', 'itemTypeId', 'ownerId');
        foreach ($requiredKeys as $key) {
            if (!isset($this->sessie->$key)) {
				print "key missing!";
                return false;
            }
        }
        return true;
    }

    public function undoImportAction()
    {
#        $databaseTransfer = $this->findById();
#HAS TO BECOME SOMETHING LIKE:		
		$databaseTransfer = $this->_helper->db->findById();

        $databaseTransfer->status = DatabaseTransfer_Import::QUEUED;
        $databaseTransfer->save();

        $jobDispatcher = Zend_Registry::get('job_dispatcher');
        $jobDispatcher->setQueueName('imports');
        $jobDispatcher->send('DatabaseTransfer_ImportTask', array('importId' => $databaseTransfer->id, 'method' => 'undo'));
		$this->_helper->flashMessenger('Successfully started to undo the import. Reload this page for status updates.', "success");
#        $this->flashSuccess('Successfully started to undo the import. Reload this page for status updates.');
        $this->_helper->redirector->goto('browse');
    }
    
    public function clearHistoryAction()
    {
        #$databaseTransfer = $this->findById();
		$databaseTransfer = $this->_helper->db->findById();
        if ($databaseTransfer->status ==
            DatabaseTransfer_Import::COMPLETED_UNDO
        ) {
            $databaseTransfer->delete();
			$this->_helper->flashMessenger("Successfully cleared the history of the import.", "success");
#            $this->flashSuccess("Successfully cleared the history of the import.");
        }
        $this->_helper->redirector->goto('browse');
    }
    

    private function _log($msg, $priority = Zend_Log::DEBUG)
    {
#        if ($logger = Omeka_Context::getInstance()->getLogger()) {
		if ($logger = Zend_Registry::get('bootstrap')->getResource('Logger')) {
            if (strpos($msg, '%time%') !== false) {
                $msg = str_replace('%time%', Zend_Date::now()->toString(), $msg);
            }
            if (strpos($msg, '%memory%') !== false) {
                $msg = str_replace('%memory%', memory_get_usage(), $msg);
            }
            $logger->log('[DatabaseTransfer] ' . $msg, $priority);
        }
    }

    protected function _getDeleteSuccessMessage($record)
    {
        return __('The import "%s" has been deleted.', $record->original_dbname);
    }

}
