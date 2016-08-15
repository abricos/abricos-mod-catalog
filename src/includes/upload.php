<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$modCatalog = Abricos::GetModule('catalog');
$modMan = $modCatalog->currentModMan;
$modFM = Abricos::GetModule('filemanager');
$manager = $modMan->GetCatalogManager();
$fmManager = $modFM->GetManager();

if (!$manager->isWriteRole()){
    return;
}
if (!$fmManager->IsFileUploadRole()){
    return;
}

$brick = Brick::$builder->brick;
$brick->param->var['url'] = Abricos::$adress->requestURI;

$p_act = Abricos::CleanGPC('p', 'act', TYPE_STR);
if ($p_act != "upload"){
    return;
}

$arr = array();
$errors = array();
for ($i = 0; $i < 6; $i++){

    $uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('file'.$i);
    // $uploadFile->maxImageWidth = 1024;
    // $uploadFile->maxImageHeight = 768;
    $uploadFile->ignoreFileSize = true;
    $uploadFile->isOnlyImage = true;
    $uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);

    $errornum = $uploadFile->Upload();
    if (empty($errornum)){
        $arr[] = $uploadFile->uploadFileHash;
    } else {
        $errors[] = array(
            "fhash" => $uploadFile->uploadFileHash,
            "fname" => $uploadFile->fileName
        );
    }
}
if (empty($arr)){
    return;
}

$json = json_encode($arr); // массив идентификаторов загруженных файлов
$newarr = array();

$uploadId = $modCatalog->uploadId;

if ($modCatalog->uploadStatus == 0){
    // Элемент в процессе добавления, поэтому формируем список загруженных файлов и
    // складываем их в кеш
    CatalogQuery::SessionAppend(Abricos::$db, $uploadId, $json);
    $rows = CatalogQuery::Session(Abricos::$db, $uploadId);
    while (($row = Abricos::$db->fetch_array($rows))){
        $tarr = json_decode($row['data']);
        foreach ($tarr as $ta){
            $newarr[] = $ta;
        }
    }
} else {
    CatalogQuery::FotoAppend(Abricos::$db, $uploadId, $arr);

    $rows = CatalogQuery::FotoList(Abricos::$db, $uploadId);
    while (($row = Abricos::$db->fetch_array($rows))){
        $newarr[] = $row['fid'];
    }
}
$json = json_encode($newarr);

$brick->param->var['command'] =
    str_replace("#data#", $json, $brick->param->var['ok']);
