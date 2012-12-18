<?php
/**
 * COinS
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

if (!defined('DATABASE_TRANSFER_DIRECTORY')) {
    define('DATABASE_TRANSFER_DIRECTORY', dirname(__FILE__));
}

/**
 * The DatabaseTransfer plugin.
 * 
 * @package Omeka\Plugins\DatabaseTransfer
 */
class DatabaseTransferPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
		'install',
		'uninstall',
        'initialize',
        'public_items_show',
        'admin_items_show',
        'public_items_browse',
        'admin_items_browse',
    );

	/**
	 * @var array Filters for the plugin.
	 */
	protected $_filters = array(
		'admin_navigation_main'
	);
    
	public function hookInstall()
	{
		$db = $this->_db;
	   // create imported items table for keeping track of imports
		$sql = "CREATE TABLE IF NOT EXISTS `{$db->prefix}database_transfer_imported_items` (
			`id` int(10) unsigned NOT NULL auto_increment,
			`item_id` int(10) unsigned NOT NULL,
			`import_id` int(10) unsigned NOT NULL,       
			PRIMARY KEY  (`id`),
			KEY (`import_id`),
			UNIQUE (`item_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	    $db->query($sql);
		$sql2 = "CREATE TABLE IF NOT EXISTS `{$db->prefix}database_transfer_imports` (
			`id` int(10) unsigned NOT NULL auto_increment,
			`item_type_id` int(10) unsigned NOT NULL,
			`collection_id` int(10) unsigned NOT NULL,       
			`owner_id` int unsigned NOT NULL,
			`delimiter` varchar(1) collate utf8_unicode_ci NOT NULL,
			`original_dbname` text collate utf8_unicode_ci NOT NULL,
			`db_name` text collate utf8_unicode_ci NOT NULL,
			`db_user` text collate utf8_unicode_ci NOT NULL,
			`db_pw` text collate utf8_unicode_ci NOT NULL,
			`db_host` text collate utf8_unicode_ci NOT NULL,
			`db_table` text collate utf8_unicode_ci NOT NULL,
			#	       `file_path` text collate utf8_unicode_ci NOT NULL,
			`table_position` bigint unsigned NOT NULL,
			`status` varchar(255) collate utf8_unicode_ci,
			`skipped_row_count` int(10) unsigned NOT NULL,
			`skipped_item_count` int(10) unsigned NOT NULL,
			`is_public` tinyint(1) default '0',
			`is_featured` tinyint(1) default '0',
			`serialized_column_maps` text collate utf8_unicode_ci NOT NULL,
			`added` timestamp NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$db->query($sql2);
	}
	
	
	function hookUninstall()
	{
		// drop the tables
		$db = $this->_db;
		$sql = "DROP TABLE IF EXISTS `{$db->prefix}database_transfer_imported_items`";
		$db->query($sql);
		$sql = "DROP TABLE IF EXISTS `{$db->prefix}database_transfer_imports`";
		$db->query($sql);
	}

	function hookDefineAcl($acl)
	{
	    // only allow super users and admins to import csv files
		$acl->loadResourceList(array('DatabaseTransfer_Index' => array(
			'index',
			'map-columns', 
	#		'undo-import', 
	#		'clear-history', 
			'browse'
		)));
	   	$acl->deny(null, 'DatabaseTransfer_Index', array('show', 'add', 'edit', 'delete'));
	   	$acl->deny('admin', 'DatabaseTransfer_Index');
	}

	function filterAdminNavigationMain($nav)
	{
		/*Adds a link to the admin navigation tab*/
        $nav[] = array(
            'label' => __('Database Transfer'),
            'uri' => url('database-transfer'),
#            'resource' => 'DatabaseTransfer_Index',
#            'privilege' => 'browse'
        );
		return $nav;
	}
	
    /**
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        // Add the view helper directory to the stack.
    }
    
    /**
     * Print out the DT span on the public items show page.
     */
    public function hookPublicItemsShow()
    {
    }
    
    /**
     * Print out the DT span on the admin items show page.
     */
    public function hookAdminItemsShow()
    {
    }
    
    /**
     * Print out the DT span on the public items browse page.
     */
    public function hookPublicItemsBrowse()
    {
    }
    
    /**
     * Print out the DT span on the admin items browse page.
     */
    public function hookAdminItemsBrowse()
    {
    }
}
