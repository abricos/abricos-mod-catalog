<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$updateManager = CatalogModule::$instance->updateShemaModule;
$db = Abricos::$db;
$modPrefix = $updateManager->module->catinfo['dbprefix']."_";
$pfx = Abricos::$db->prefix."ctg_".$modPrefix;

if ($updateManager->isUpdate('0.3.2') && !$updateManager->isInstall()){
    $db->query_write("
		UPDATE ".$pfx."eltype
		SET
		    title_".Abricos::$LNG."=title,
		    titlelist_".Abricos::$LNG."=titlelist,
		    descript_".Abricos::$LNG."=descript
	");

    $db->query_write("
		ALTER TABLE ".$pfx."eltype
		DROP title,
		DROP titlelist,
		DROP descript,
		DROP language
	");

}

?>