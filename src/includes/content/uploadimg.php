<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$dir = Abricos::$adress->dir;

$modname = $dir[2];
$mod = Abricos::GetModule($modname);

if (empty($mod)){
    return;
}

$man = $mod->GetManager();

if (empty($man) || empty($man->cManager)){
    return;
}

if (!$man->IsWriteRole()){
    return;
}

$modFM = Abricos::GetModule('filemanager');
if (empty($modFM)){
    return;
}


$brick = Brick::$builder->brick;
$var = &$brick->param->var;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "modname" => $modname
));

$act = isset($dir[3]) ? $dir[3] : "";
if ($act !== "go"){
    return;
}

$resa = array();

for ($i = 0; $i < 10; $i++){

    $uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('image'.$i);

    $uploadFile->maxImageWidth = 1022;
    $uploadFile->maxImageHeight = 1022;
    $uploadFile->ignoreFileSize = true;
    $uploadFile->isOnlyImage = true;
    // $uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);
    $uploadFile->outUserProfile = true;
    $error = $uploadFile->Upload();

    if ($i > 0 && $error == UploadError::FILE_NOT_FOUND){
        continue;
    }

    $res = new stdClass();
    $res->error = $error;
    $res->fname = $uploadFile->fileName;
    $res->fhash = $uploadFile->uploadFileHash;

    $resa[] = $res;

    if ($error > 0){
        continue;
    }

    $man->cManager->FotoAddToBuffer($res->fhash);
}

$brick->param->var['result'] = json_encode($resa);

?>