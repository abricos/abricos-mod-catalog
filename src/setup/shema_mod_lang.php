<?php

$updateManager = CatalogModule::$instance->updateShemaModule;
$db = Abricos::$db;
$modPrefix = $updateManager->module->catinfo['dbprefix']."_";
$pfx = Abricos::$db->prefix."ctg_".$modPrefix;

if ($updateManager->isUpdateLanguage('0.3.2')){
	$db->query_write("
		ALTER TABLE ".$pfx."eltype
		ADD title_".Abricos::$LNG." varchar(250) NOT NULL default '' COMMENT 'Title',
		ADD titlelist_".Abricos::$LNG." varchar(250) NOT NULL default '' COMMENT 'Title list',
		ADD descript_".Abricos::$LNG." text NOT NULL COMMENT 'Description'
	");
}

?>