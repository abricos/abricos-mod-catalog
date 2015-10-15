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
$pfx = $app->GetDBPrefix();

$langid = Abricos::$LNG;

if ($updateManager->isUpdateLanguage('0.3.2')){
    $db->query_write("
		ALTER TABLE ".$pfx."eltype
		ADD title_".$langid." varchar(250) NOT NULL default '' COMMENT 'Title',
		ADD titlelist_".$langid." varchar(250) NOT NULL default '' COMMENT 'Title list',
		ADD descript_".$langid." text NOT NULL COMMENT 'Description',
		ADD composite_".$langid." VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'The expression for the formation of the element name',
		ADD prefix_".$langid." VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'The prefix element title',
		ADD postfix_".$langid." VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'The postfix element title'
	");
}

?>