<?php
/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2010 Abricos. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = CMSRegistry::$instance->modules->updateManager; 
$db = CMSRegistry::$instance->db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."ctg_module` (
		  `moduleid` int(3) unsigned NOT NULL auto_increment,
		  `name` VARCHAR(50) NOT NULL default '',
		  `dbprefix` VARCHAR(10) NOT NULL default '',
		  `version` VARCHAR(10) NOT NULL default '0.0.0',
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY  (`moduleid`)
		)
	". $charset);
}

if ($updateManager->isUpdate('0.2.1')){
	
	CMSRegistry::$instance->modules->GetModule('catalog')->permission->Install();
	
}

?>