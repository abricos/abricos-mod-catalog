<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$modname = Abricos::$adress->dir[2];
$optionid = Abricos::$adress->dir[3];

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
    "modname" => $modname,
    "optionid" => $optionid
));

$uPrm = $man->cManager->OptionFileUploadCheck($optionid);
if (empty($uPrm)){
    return;
}

Brick::$builder->LoadBrickS('filemanager', 'accinfo', $brick, array(
    "p" => array(
        "hideuserspace" => true,
        "fextobj" => $uPrm->fTypes
    )
));

// TODO: передать параметры ограничение на кол-во и т.п. в окно загрузчика

if (Abricos::$adress->dir[4] !== "go"){
    return;
}

$resa = array();
for ($i = 0; $i < 10; $i++){

    $uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('file'.$i);

    // $uploadFile->maxImageWidth = 1022;
    // $uploadFile->maxImageHeight = 1022;
    // $uploadFile->ignoreFileSize = true;
    // $uploadFile->isOnlyImage = true;
    // $uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);
    $uploadFile->outUserProfile = true;
    $uploadFile->ignoreUploadRole = true;
    $uploadFile->cfgFileExtensions = $uPrm->fTypes;

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

    $man->cManager->OptionFileAddToBuffer($uPrm->option, $res->fhash, $res->fname);
}

$brick->param->var['result'] = json_encode($resa);
