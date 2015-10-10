<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$updateManager = CatalogApp::$updateShemaModule;

/** @var CatalogApp $app */
$app = $updateManager->module->GetManager()->GetApp();

$db = $app->db;
$pfx = $app->Config()->dbPrefix;

if ($updateManager->isUpdateLanguage('0.3.2')){
    $db->query_write("
		ALTER TABLE ".$pfx."eltype
		ADD title_".Abricos::$LNG." varchar(250) NOT NULL default '' COMMENT 'Title',
		ADD titlelist_".Abricos::$LNG." varchar(250) NOT NULL default '' COMMENT 'Title list',
		ADD descript_".Abricos::$LNG." text NOT NULL COMMENT 'Description'
	");
}

?>