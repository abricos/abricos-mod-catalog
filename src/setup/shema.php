<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."ctg_module (
		  moduleid int(3) unsigned NOT NULL auto_increment,
		  name VARCHAR(50) NOT NULL default '',
		  dbprefix VARCHAR(10) NOT NULL default '',
		  version VARCHAR(10) NOT NULL default '0.0.0',
		  dateline int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  deldate int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY  (moduleid)
		)
	".$charset);
}

?>