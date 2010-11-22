<?php
/**
* @version $Id$
* @package Abricos
* @subpackage Catalog
* @copyright Copyright (C) 2010 Abricos. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

$mod = new CatalogModule();
CMSRegistry::$instance->modules->Register($mod);

class CatalogModule extends CMSModule {
	
	/**
	 * Upload Status: 0 - session, 1 - id
	 *
	 * @var integer
	 */
	public $uploadStatus = 0;
	/**
	 * Upload идентификатор
	 * в зависимости от типа содержит в себе либо идентификатор редактируемого элемента, 
	 * либо сессию добавляемого элемента
	 *
	 * @var mixed
	 */
	public $uploadId = '';
	
	public $uploadElementId = 0;
	
	public $baseUrl = "";
	
	public $modManInfo = null;
	public $currentModMan = null;
	public $updateShemaModule = null;
	
	private $_manager;
	
	
	function __construct(){
		$this->version = "0.2.1"; 
		$this->name = "catalog";
		$this->takelink = "catalogbase";
		
		$this->permission = new CatalogPermission($this);
	}
	
	/**
	 * Получить менеджер
	 * 
	 * @return CatalogManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new CatalogManager($this);
		}
		return $this->_manager;
	}
	
	
	public function SetModuleManager($modname){
		$core = $this->registry;
		$this->currentModMan = $core->modules->GetModule($modname);
		$this->GetManager();
		CatalogQuery::PrefixSet($core->db, $this->currentModMan->catinfo['dbprefix']);
	}
	
	public function GetContentName(){
		$cname = 'index';
		$adress = $this->registry->adress;
		
		if ($adress->level >= 2){
			
			$this->SetModuleManager($adress->dir[1]);
			
			$p = $adress->dir[2];
			if ($p == 'upload'){
				$cname = "upload";
				$this->uploadStatus = $adress->dir[3] == 'id' ? 1 : 0; 
				$this->uploadId = $adress->dir[4];
			}
		}
		return $cname;
	}
	
	public static function FotoThumbInfoParse($info){
		if (empty($info)) return array("fh"=>"", "w"=>intval($arr1[0]), "h"=>intval($arr1[1]));
		 
		$arr = explode(":", $info);
		$arr1 = explode("x", $arr[1]);
		return array(
			"fh" => $arr[0],
			"w" => intval($arr1[0]),
			"h" => intval($arr1[1])
		);
	}
	
	public static function FotoThumbLink($fid, $w, $h, $fn){
		$arr = array();
		if ($w > 0) array_push($arr, "w_".$w);
		if ($h > 0) array_push($arr, "h_".$h);

		return "/filemanager/i/".$fid."/".implode("-", $arr)."/".$fn;
	}
	
	private function UpdateModMan(){
		if (is_null($this->modManInfo)){
			$db = $this->registry->db;
			$rows = CatalogQueryExt::ModuleManagerList($db);
			while (($row = $db->fetch_array($rows))){
				$this->modManInfo[$row['nm']] = $row;
			}
		}
	}

	/**
	 * Регистрация модуля "паразита"
	 *
	 * @param CMSModule $modman
	 */
	public function Register(CMSModule $modman){
		$this->currentModMan = $modman;
		$this->UpdateModMan();
		if (empty($this->modManInfo[$modman->name])){
			CatalogQueryExt::ModuleManagerAppend($this->registry->db, $modman);
			$this->modManInfo = null;
			$this->UpdateModMan();
		}
		// проверка версии
		$info = $this->modManInfo[$modman->name];

		$svers = $info['vs'];
		$cvers = $this->version;
		if ($svers == $cvers){ return; }
		
		$modInfo = array(
			'name' => $modman->name,
			'version' => $info['vs']
		);
		
		$this->updateShemaModule = new CMSUpdateManager($modman, $modInfo);
		
		require(CWD."/modules/catalog/includes/shema_mod.php");
		CatalogQueryExt::ModuleManagerUpdate($this->registry->db, $info['id'], $this->version);
		$this->updateShemaModule = null;
	}
	
}

/**
 * Роли пользователей каталога
 */
class CatalogAction {
	
	/** 
	 * Роль на чтение - чтение разделов каталога и его элементов 
	 * @var integer
	 */
	const VIEW			= 10;

	/**
	 * Роль оператора - изменение/добавление/удаление элементов каталога
	 * @var integer
	 */
	const WRITE			= 30;
	
	/**
	 * Роль админа - изменения структуры каталога
	 * @var integer
	 */
	const ADMIN			= 50;
}

class CatalogPermission extends CMSPermission {
	
	public function CatalogPermission(CatalogModule $module){
		$defRoles = array(
			new CMSRole(CatalogAction::VIEW, 1, User::UG_GUEST),
			new CMSRole(CatalogAction::VIEW, 1, User::UG_REGISTERED),
			new CMSRole(CatalogAction::VIEW, 1, User::UG_ADMIN),

			new CMSRole(CatalogAction::WRITE, 1, User::UG_ADMIN),
			new CMSRole(CatalogAction::ADMIN, 1, User::UG_ADMIN)
		);
		parent::CMSPermission($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			CatalogAction::VIEW => $this->CheckAction(CatalogAction::VIEW),
			CatalogAction::WRITE => $this->CheckAction(CatalogAction::WRITE), 
			CatalogAction::ADMIN => $this->CheckAction(CatalogAction::ADMIN) 
		);
	}
}

class CatalogQueryExt {
	public static function ModuleManagerUpdate(CMSDatabase $db, $modmanid, $version){
		$sql = "
			UPDATE ".$db->prefix."ctg_module
			SET
				version='".$version."'
			WHERE moduleid=".$modmanid."
		";
		$db->query_write($sql);
	}
	
	public static function ModuleManagerAppend(CMSDatabase $db, CMSModule $modman){
		$sql = "
			INSERT INTO ".$db->prefix."ctg_module
			(name, dbprefix, version) VALUES (
				'".$modman->name."',
				'".$modman->catinfo['dbprefix']."',
				'0.0.0'
			)
		";
		$db->query_write($sql);
	}
	
	public static function ModuleManagerList(CMSDatabase $db){
		$sql = "
			SELECT 
				moduleid as id,
				name as nm,
				dbprefix as pfx,
				version as vs
			FROM ".$db->prefix."ctg_module
		";
		return $db->query_read($sql);
	}
}

/*
class CMSCatalogMan {
	
	public static function ImageUpload($elementId, $files){
		
		$modCatalog = Brick::$modules->GetModule('catalog');
		$modMan = $modCatalog->currentModMan;
		$modFM = Brick::$modules->GetModule('filemanager');
		$db = CMSRegistry::$instance->db;
		CatalogQuery::PrefixSet($db, $modMan->catinfo['dbprefix']);
		$upload = $modFM->GetUpload();
		
		$arr = array();
		foreach ($files as $file){
			if (!file_exists($file)){ continue; } 
			
			$filename = basename($file);
			$tarr = explode('.', $filename);
			$ext = $tarr[count($tarr)-1];
			
			$errornum = $upload->UploadSystemFile($file, $filename, $ext, filesize($file));
			if (empty($errornum)){
				array_push($arr, $upload->lastUploadFileHash);
			}
		}
		if (empty($arr)){ return; }
		
		CatalogQuery::FotoAppend($db, $elementId, $arr);
	}
	
}
/**/


?>