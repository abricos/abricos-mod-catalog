<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$modname = Abricos::$adress->dir[2];
$optionid = Abricos::$adress->dir[3];

$mod = Abricos::GetModule($modname);

if (empty($mod)){ return; }

$man = $mod->GetManager();

if (empty($man) || empty($man->cManager)){ return; }

if (!$man->IsWriteRole()){ return; }

$modFM = Abricos::GetModule('filemanager');
if (empty($modFM)){ return; }


$brick = Brick::$builder->brick;
$var = &$brick->param->var;

$elTypeList = $man->cManager->ElementTypeList();
$option = $elTypeList->GetOptionById($optionid);
$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"modname" => $modname,
	"optionid" => $optionid
));

if (empty($option) || $option->type != Catalog::TP_FILES){
	return;
}

$aFTypes = array();
$aOPrms = explode(";", $option->param);
for ($i=0;$i<count($aOPrms);$i++){
	$aExp = explode("=", $aOPrms[$i]);
	switch (strtolower(trim($aExp[0]))){
	case 'ftypes':
		
		$aft = explode(",", $aExp[1]);
		for ($ii=0;$ii<count($aft);$ii++){
			$af = explode(":", $aft[$ii]);
			$aFTypes[$af[0]] = array(
				'maxsize' => $af[1]
			);
		}
		break;
	}
}

if (Abricos::$adress->dir[4] !== "go"){
	return;
}


$resa = array();

for ($i=0; $i<10; $i++){

	$uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('file'.$i);

	// $uploadFile->maxImageWidth = 1022;
	// $uploadFile->maxImageHeight = 1022;
	// $uploadFile->ignoreFileSize = true;
	// $uploadFile->isOnlyImage = true;
	// $uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);
	$uploadFile->outUserProfile = true;
	$uploadFile->ignoreUploadRole = true;
	$uploadFile->cfgFileExtension = $aFTypes;

	$error = $uploadFile->Upload();
	
	if ($i > 0 && $error == UploadError::FILE_NOT_FOUND){ continue; }
	
	$res = new stdClass();
	$res->error = $error;
	$res->fname = $uploadFile->fileName;
	$res->fhash = $uploadFile->uploadFileHash;
	
	array_push($resa, $res);
	
	if ($error > 0){ continue; }
	
	$man->cManager->FileAddToBuffer($res->fhash);
}

$brick->param->var['result'] = json_encode($resa);


?>