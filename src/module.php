<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CatalogModule
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

    /**
     * @var Ab_UpdateManager
     */
    public $updateShemaModule = null;

    private $_manager;

    function __construct(){
        CatalogModule::$instance = $this;
        $this->version = "0.3.2";
        $this->name = "catalog";
        $this->takelink = "catalogbase";
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
        $this->currentModMan = Abricos::$modules->GetModule($modname);
        $this->GetManager();
        CatalogQuery::PrefixSet(Abricos::$db, $this->currentModMan->catinfo['dbprefix']);
    }

    public function GetContentName(){
        $cname = '';
        $adress = Abricos::$adress;
        $dir = Abricos::$adress->dir;

        switch ($dir[1]){
            case 'uploadimg':
            case 'uploadoptfiles';
                return $dir[1];
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
        if (empty($info)){
            return array(
                "fh" => "",
                "w" => 0,
                "h" => 0
            );
        }

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
        if ($w > 0)
            $arr[] = "w_".$w;
        if ($h > 0)
            $arr[] = "h_".$h;

        $ret = "/filemanager/i/".$fid."/";
        if (count($arr) > 0){
            $ret = $ret.implode("-", $arr)."/";
        }

        return $ret.$fn;
    }

    private function UpdateModMan($isClear = false){
        if ($isClear){
            $this->modManInfo = null;
        }
        if (is_null($this->modManInfo)){
            $db = Abricos::$db;
            $rows = CatalogQueryExt::ModuleManagerList($db);
            while (($row = $db->fetch_array($rows))){
                $this->modManInfo[$row['name']] = $row;
            }
        }
    }

    private function _UpdateModuleDbStructure(Ab_Module $modman, Ab_ModuleInfo $modInfo){
        if ($modInfo->version === $this->version){
            return false;
        }

        $this->updateShemaModule = new Ab_UpdateManager($modman, $modInfo);
        require("setup/shema_mod.php");
        CatalogQueryExt::ModuleManagerUpdate(Abricos::$db, $modInfo->name, $this->version);

        return true;
    }

    private function _UpdateModuleDbLanguage(Ab_Module $modman, Ab_ModuleInfo $modInfo){
        if ($modInfo->languageVersion === $this->version){
            return false;
        }
        $this->updateShemaModule = new Ab_UpdateManager($modman, $modInfo);
        require("setup/shema_mod_lang.php");
        CatalogQueryExt::ModuleManagerUpdateLanguage(Abricos::$db, $modInfo->name, $this->version);

        return true;
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
            CatalogQueryExt::ModuleManagerAppend(Abricos::$db, $modman);
            $this->UpdateModMan(true);
        }

        $modInfo = new Ab_ModuleInfo($this->modManInfo[$modman->name]);

        $isUpdate = $this->_UpdateModuleDbStructure($modman, $modInfo);

        $this->_UpdateModuleDbLanguage($modman, $modInfo);

        if ($isUpdate){
            $this->updateShemaModule = new Ab_UpdateManager($modman, $modInfo);
            require("setup/shema_mod_after.php");
            CatalogQueryExt::ModuleManagerUpdate(Abricos::$db, $modInfo->name, $this->version);
        }

        $this->updateShemaModule = null;
    }

}

class CatalogQueryExt {
    public static function ModuleManagerUpdate(Ab_Database $db, $name, $version){
        $sql = "
			UPDATE ".$db->prefix."ctg_module
			SET version='".$version."'
			WHERE name='".bkstr($name)."'
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

    public static function ModuleManagerUpdateLanguage(Ab_Database $db, $name, $version){
        $findCol = false;
        $lngId = 'language_'.Abricos::$LNG;
        $sql = "SHOW COLUMNS FROM ".$db->prefix."ctg_module";
        $rows = $db->query_read($sql);
        while (($row = $db->fetch_array($rows))){
            if ($row['Field'] === $lngId){
                $findCol = true;
            }
        }
        if (!$findCol){
            $db->query_write("ALTER TABLE ".$db->prefix."ctg_module ADD ".$lngId." varchar(20) NOT NULL default '0.0'");
        }
        $db->query_write("
			UPDATE ".$db->prefix."ctg_module
			SET ".$lngId."='".bkstr($version)."'
			WHERE name='".bkstr($name)."'
		");
    }


    public static function ModuleManagerList(Ab_Database $db){
        $sql = "
			SELECT *
			FROM ".$db->prefix."ctg_module
		";
        return $db->query_read($sql);
    }
}

Abricos::ModuleRegister(new CatalogModule());

?>