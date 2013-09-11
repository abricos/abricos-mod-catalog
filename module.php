<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class CatalogModule extends Ab_Module {
	
	/**
	 * @var CatalogModule
	 */
	public static $instance = null;
	
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
		CatalogModule::$instance = $this;
		$this->version = "0.2.5.3";
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
		$cname = '';
		$adress = $this->registry->adress;
		$dir = Abricos::$adress->dir;

		if ($dir[1] == 'uploadimg'){
			return 'uploadimg';
		}
		
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
		
		$ret = "/filemanager/i/".$fid."/";
		if (count($arr)>0){
			$ret = $ret.implode("-", $arr)."/";
		}

		return $ret.$fn;
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
	 * Регистрация модуля
	 *
	 * @param Ab_Module $modman
	 */
	public function Register(Ab_Module $modman){
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
		
		$this->updateShemaModule = new Ab_UpdateManager($modman, $modInfo);
		require("includes/shema_mod.php");
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
	public static function ModuleManagerUpdate(Ab_Database $db, $modmanid, $version){
		$sql = "
			UPDATE ".$db->prefix."ctg_module
			SET
				version='".$version."'
			WHERE moduleid=".$modmanid."
		";
		$db->query_write($sql);
	}
	
	public static function ModuleManagerAppend(Ab_Database $db, Ab_Module $modman){
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
	
	public static function ModuleManagerList(Ab_Database $db){
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

Abricos::ModuleRegister(new CatalogModule());

?>