<?php
/**
 *
 */
class DatabaseTransfer_ImportTask extends Omeka_Job_AbstractJob
{
    private $_importId;
    private $_method = 'start'; //name for method of Import
    private $_memoryLimit;
    private $_batchSize;

    public function perform()
    {
        if ($this->_memoryLimit) {
            ini_set('memory_limit', $this->_memoryLimit);
        }
        if (!($import = $this->_getImport())) { //this is where the import programming is retrieved again from the database
			echo "IMPORT:<br>".$import."<br><br>";
            return;
        }

        $import->setBatchSize($this->_batchSize);
        call_user_func(array($import, $this->_method));

        if ($import->isQueued()) {
            $this->_dispatcher->setQueueName('imports');
            $this->_dispatcher->send(__CLASS__, 
                array(
                    'importId' => $import->id, 
                    'memoryLimit' => "1024M", #problems!!!!
                    'method' => 'resume',
#                    'batchSize' => $this->_batchSize,
                )
            );
        }
	}

    public function setBatchSize($size)
    {
        $this->_batchSize = (int)$size;
    }

    public function setMemoryLimit($limit)
    {
        $this->_memoryLimit = $limit;
    }

    public function setImportId($id)
    {
        $this->_importId = $id;
    }

    public function setMethod($name)
    {
        $this->_method = $name;
    }

    private function _getImport() //get the import programming based on ID
    {
        return $this->_db->getTable('DatabaseTransfer_Import')->find($this->_importId);
    }
}
