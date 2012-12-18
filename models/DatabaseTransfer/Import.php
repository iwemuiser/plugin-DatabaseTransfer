<?php
/**
 * DatabaseTransfer_Import - represents a table import event
 *
 * @version $Id$
 * @package DatabaseTransfer
 * @author Iwe Muiser
 * @copyright Meertens Institute 2012
 **/
class DatabaseTransfer_Import extends Omeka_Record_AbstractRecord
{

    const UNDO_IMPORT_LIMIT_PER_QUERY = 100;

    const QUEUED = 'queued';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const IN_PROGRESS_UNDO = 'undo_in_progress';
    const COMPLETED_UNDO = 'completed_undo';
    const ERROR = 'error';
    const STOPPED = 'stopped';
    const PAUSED = 'paused';

    public $original_dbname;

	public $db_name;
	public $db_user;
	public $db_pw;
	public $db_host;
	public $db_table;

    private $_importedCount = 0;
	private $_dbTable;
	private $_dbE;
	private $_stmt;
	
    public $table_position = 0;
    public $item_type_id;
    public $collection_id;
    public $owner_id;
    public $added;

    public $is_public;
    public $is_featured;
    public $skipped_row_count = 0;
    public $skipped_item_count = 0;
    public $status;
    public $serialized_column_maps;

	
    /**
     * Batch importing is not enabled by default.
     */
    private $_batchSize = 0;

    /**
     * An array of columnMaps, where each columnMap maps a column index number
     * (starting at 0) to an element, tag, and/or file.
     *
     * @var array
     */
    private $_columnMaps;
	
    public function setItemsArePublic($flag)
    {
        $booleanFilter = new Omeka_Filter_Boolean;
        $this->is_public = $booleanFilter->filter($flag);
    }

    public function setItemsAreFeatured($flag)
    {
        $booleanFilter = new Omeka_Filter_Boolean;
        $this->is_featured = $booleanFilter->filter($flag);
    }

    public function setCollectionId($id)
    {
        $this->collection_id = (int)$id;
    }

    public function setDbName($db_name)
    {
        $this->db_name = $db_name;
    }

    public function setDbUser($db_user)
    {
        $this->db_user = $db_user;
    }
    public function setDbPw($db_pw)
    {
        $this->db_pw = $db_pw;
    }
    public function setDbHost($db_host)
    {
        $this->db_host = $db_host;
    }
    public function setDbTable($db_table)
    {
        $this->db_table = $db_table;
    }

    public function setOriginalDbname($dbname)
    {
        $this->original_dbname = $dbname;
    }

    public function setItemTypeId($id)
    {
        $this->item_type_id = (int)$id;
    }

    public function setStatus($status)
    {
        $this->status = (string)$status;
    }

    public function setOwnerId($userId)
    {
        $this->owner_id = $userId;
    }

    private function _getOwner()
    {
        if (!$this->_owner) {
            $this->_owner = $this->getTable('User')->find($this->owner_id);
            if (!$this->_owner) {
                throw new UnexpectedValueException("Cannot run import for "
                    . "a user account that no longer exists.");
            }
        }
        return $this->_owner;
    }

    public function setColumnMaps($maps)
    {
        if ($maps instanceof DatabaseTransfer_ColumnMap_Set) {
            $mapSet = $maps;
        } else if (is_array($maps)) {
            $mapSet = new DatabaseTransfer_ColumnMap_Set($maps);
        } else {
            throw new InvalidArgumentException("Maps must be either an "
                . "array or an instance of DatabaseTransfer_ColumnMap_Set.");
        }
        $this->_columnMaps = $mapSet;
    }

    /**
     * Set the number of items to create before pausing the import.
     *
     * Used primarily for performance reasons, i.e. long-running imports may
     * time out or hog system resources in such a way that prevents other
     * imports from running.  When used in conjunction with Omeka_Job and
     * resume(), this can be used to spawn multiple sequential jobs for a given
     * import.
     */
    public function setBatchSize($size)
    {
        $this->_batchSize = (int)$size;
    }


    protected function beforeSave()
    {
        $this->serialized_column_maps = serialize($this->getColumnMaps());
    }

    protected function afterDelete()
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }
    }

    public function isError()
    {
        return $this->status == self::ERROR;
    }

    public function isStopped()
    {
        return $this->status == self::STOPPED;
    }

    public function isQueued()
    {
        return $this->status == self::QUEUED;
    }

    public function isFinished()
    {
        return $this->status == self::COMPLETED;
    }

    public function isUndone()
    {
        return $this->status == self::COMPLETED_UNDO;
    }

    /**
     * Imports the csv file.  This function can only be run once.
     * To import the same csv file, you will have to
     * create another instance of CsvImport_Import and run start
     *
     * @return boolean true if the import is successful, else false
     */
    public function start() //started by ImportTask process (ran in indexController)
    {
		print "Started import at: %time%";
        $this->_log("Started import at: %time%");
        $this->status = self::IN_PROGRESS;
#        $this->forceSave();
        $this->save();
        $this->_importLoop($this->table_position);
        return !$this->isError();
    }

    public function finish()
    {
        if ($this->isFinished()) {
            $this->_log("Cannot finish an import that is already finished.");
            return false;
        }

        $this->_log("Finished importing $this->_importedCount items (skipped "
            . "$this->skipped_row_count rows).", Zend_Log::INFO);
        $this->status = self::COMPLETED;
#        $this->forceSave();
        $this->save();
        return true;
    }

    /**
     * Stop the import.
     * Sets status flag to 'stopped';
     */
    public function stop()
    {
        // Anything besides 'in progress' signifies a finished import.
        if ($this->status != self::IN_PROGRESS) {
            return false;
        }

        $logMsg = "Stopping import due to error";
        if ($error = error_get_last()) {
            $logMsg .= ": " . $error['message'];
        } else {
            $logMsg .= '.';
        }
        $this->_log($logMsg);
        $this->status = self::STOPPED;
#        $this->forceSave();
        $this->save();
    }

    public function queue()
    {
        if ($this->status != self::IN_PROGRESS) {
            $this->_log("Cannot pause an import that is not in progress.");
            return false;
        }

        $this->status = self::QUEUED;
#        $this->forceSave();
        $this->save();
    }

    public function resume()
    {
        if (!$this->isQueued()) {
            $this->_log("Cannot resume an import that has not been paused.");
            return false;
        }
        $this->_log("Resumed import at: %time%");
        $this->status = self::IN_PROGRESS;
#        $this->forceSave();
        $this->save();

        $this->_importLoop($this->table_position);
        return !$this->isError();
	}

	public function getDbE(){
		$this->_dbE = new DatabaseTransfer_Db(array( //call database for checking
	    	'host'     => $this->db_host,
	    	'username' => $this->db_user,
	    	'password' => $this->db_pw,
	    	'dbname'   => $this->db_name));
		return $this->_dbE;
	}

    public function getDbTable() //Previously getCsvFile()
    {
        if (empty($this->_dbTable)) {
			$this->_dbTable = new DatabaseTransfer_Table(array('name' => $this->db_table, 'db' => $this->getDbE()));
        }
		$this->_log("Table loaded: Memory usage: %memory%");
        return $this->_dbTable;
    }

	public function fetchAllRows(){
		$sql = 'SELECT * FROM ' . $this->db_table;
		$this->_log("Select statement: " . $sql . " Memory usage: %memory%");
		$this->_stmt = $this->getDbE()->prepare($sql);
		$this->_stmt->execute();
		$this->_log("Select statement executed: Memory usage: %memory%");
		return $this->_stmt;
	}

	public function fetchAllRows2(){
		$this->_rowSet = $this->getDbTable();		
		$this->_dbTable->query('SELECT * FROM '. $this->_db_table);
		$this->_log("SELECT * executed. Memory usage: %memory%");
		return $this->_rowSet;
	}
	
	private function _importLoop($startAt = null)
    {
		$this->_log("Import Loop started: %time%");
        register_shutdown_function(array($this, 'stop'));
        $itemMetadata = array(
            'public'         => $this->is_public,
            'featured'       => $this->is_featured,
            'item_type_id'   => $this->item_type_id,
            'collection_id'  => $this->collection_id,
            'tag_entity'     => $this->_getOwner()->Entity,
        );

        $maps = $this->getColumnMaps();
        $this->_log("Columns Mapped: Memory usage: %memory%");
		$this->fetchAllRows(); //load rowset
		
        $this->_log("Iterator loaded: Memory usage: %memory%");
        if ($startAt) {
            $rows->seek($startAt);
        }
        $this->_log("Item import loop started at: %time%");
        while ($row = $this->_stmt->fetch()) {
			$this->_log("input procedure loop: %time%, %memory%");
            try {
                if ($item = $this->_addItemFromRow($row, $itemMetadata, $maps)) { //actual input in DB
                    release_object($item);
                } else {
                    $this->skipped_item_count++;
                }
            } catch (Omeka_Job_Worker_InterruptException $e) {
                // Interruptions usually indicate that we should resume from
                // the last stopping position.
                return $this->queue();
            } catch (Exception $e) {
                $this->status = self::ERROR;
		#        $this->forceSave();
		        $this->save();
                $this->_log($e, Zend_Log::ERR);
                throw $e;
            }
        }
        return $this->finish();
    }

    // adds an item based on the row data
    // returns inserted Item
    private function _addItemFromRow($row, $itemMetadata, $maps)
    {
        $result = $maps->map($row);
        $fileUrls = $result[DatabaseTransfer_ColumnMap::TARGET_TYPE_FILE];
        $elementTexts = $result[DatabaseTransfer_ColumnMap::TARGET_TYPE_ELEMENT];
        $tags = $result[DatabaseTransfer_ColumnMap::TARGET_TYPE_TAG];

        //If this is coming from CSV Report, bring in the itemmetadata coming from the report

        if(!is_null($result[DatabaseTransfer_ColumnMap::METADATA_COLLECTION])) {
            $itemMetadata['collection_id'] = $result[DatabaseTransfer_ColumnMap::METADATA_COLLECTION];
        }
        if(!is_null($result[DatabaseTransfer_ColumnMap::METADATA_PUBLIC])) {
            $itemMetadata['public'] = $result[DatabaseTransfer_ColumnMap::METADATA_PUBLIC];
        }
        if(!is_null($result[DatabaseTransfer_ColumnMap::METADATA_FEATURED])) {
            $itemMetadata['featured'] = $result[DatabaseTransfer_ColumnMap::METADATA_FEATURED];
        }
        
        if(!empty($result[DatabaseTransfer_ColumnMap::METADATA_ITEM_TYPE])) {
            $itemMetadata['item_type_name'] = $result[DatabaseTransfer_ColumnMap::METADATA_ITEM_TYPE];
        }

        try {
			//The actual insertion of data to the Omeka database!
            $item = insert_item(array_merge(array('tags' => $tags), $itemMetadata), $elementTexts); 
        } catch (Omeka_Validator_Exception $e) {
            $this->_log($e, Zend_Log::ERR);
            return false;
        }
        foreach($fileUrls[0] as $url) {
            try {
                $file = insert_files_for_item($item,
                    'Url', $url,
                    array(
                        'ignore_invalid_files' => false,
                    )
                );

            } catch (Omeka_File_Ingest_InvalidException $e) {
                $msg = "Error occurred when attempting to ingest the "
                     . "following URL as a file: '$url': "
                     . $e->getMessage();
                $this->_log($msg, Zend_Log::INFO);
                $item->delete();
                return false;
            }
            release_object($file);
        }

        // Makes it easy to unimport the item later.
        $this->recordImportedItemId($item->id);
        return $item;
    }

    private function recordImportedItemId($itemId)
    {
        $tableImportedItem = new DatabaseTransfer_ImportedItem();
        $tableImportedItem->setArray(array('import_id' => $this->id, 'item_id' =>
            $itemId));
#        $tableImportedItem->forceSave();
        $tableImportedItem->save();
        $this->_importedCount++;
    }

    public function getColumnMaps()
    {
        if($this->_columnMaps === null) {
            $columnMaps = unserialize($this->serialized_column_maps);
            if (!($columnMaps instanceof DatabaseTransfer_ColumnMap_Set)) {
                throw new UnexpectedValueException("Column maps must be "
                    . "an instance of DatabaseTransfer_ColumnMap_Set. Instead, the "
                    . "following was given: " . var_export($columnMaps, true));
            }
            $this->_columnMaps = $columnMaps;
        }

        return $this->_columnMaps;
    }

    public function undo()
    {
        $this->status = self::IN_PROGRESS_UNDO;
#        $this->forceSave();
        $this->save();

        $db = $this->getDb();
        $searchSql = "SELECT item_id FROM $db->DatabaseTransfer_ImportedItem"
                   . " WHERE import_id = " . (int)$this->id
                   . " LIMIT " . self::UNDO_IMPORT_LIMIT_PER_QUERY;
        $it = $this->getTable('Item');

		print $searchSql;
		
       	while ($itemIds = $db->fetchCol($searchSql)) {
            $inClause = 'IN (' . join(', ', $itemIds) . ')';
#			print $it->getSelect()->where("id $inClause");
#			exit();
            $items = $it->fetchObjects($it->getSelect()->where("id $inClause"));
            foreach ($items as $item) {
                $item->delete();
                release_object($item);
            }
            $db->delete($db->DatabaseTransfer_ImportedItem, "item_id $inClause");
        }

        $this->status = self::COMPLETED_UNDO;
#        $this->forceSave();
        $this->save();
    }

    // returns the number of items currently imported.  if a user undoes an
    // import, it decreases the count to show the number of items left to
    // unimport
    public function getImportedItemCount()
    {
        $iit = $this->getTable('DatabaseTransfer_ImportedItem');
        $sql = $iit->getSelectForCount()->where('`import_id` = ?');
        $importedItemCount = $this->getDb()->fetchOne($sql, array($this->id));
        return $importedItemCount;
    }

    public function getProgress()
    {
        $importedItemCount = $this->getImportedItemCount();
        $info = array(
            'Imported' => $importedItemCount,
            'Skipped Rows' => $this->skipped_row_count,
            'Skipped Items' => $this->skipped_item_count,
        );
        $progress = '';
        foreach ($info as $key => $value) {
            $progress[] = $key . ': ' . $value;
        }
        return implode(' / ', $progress);
    }

#	$acl = Omeka_Context::getInstance()->acl;
#	becomes
#	$acl = Zend_Registry::get('bootstrap')->getResource('Acl');
	
    private function _log($msg, $priority = Zend_Log::DEBUG)
    {
#        if ($logger = Omeka_Context::getInstance()->getLogger()) { #changed in omeka 2.0
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
}
