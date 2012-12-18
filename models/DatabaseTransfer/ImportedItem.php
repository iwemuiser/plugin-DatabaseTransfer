<?php
/**
 * DatabaseTransfer_ImportedItem - represents an imported item for a specific table 
 * import event
 * 
 * @version $Id$ 
 * @package DatabaseTransfer
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class DatabaseTransfer_ImportedItem extends Omeka_Record_AbstractRecord
{
    public $import_id;
    public $item_id;

    public function getItemId()
    {
        return $this->item_id;
    }

    public function getImportId()
    {
        return $this->import_id;
    }
}
