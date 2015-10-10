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

    function __construct(){
        CatalogModule::$instance = $this;
        $this->version = "0.3.2";
        $this->name = "catalog";
        $this->takelink = "catalogbase";
    }

    private $_manager;

    /**
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
}

Abricos::ModuleRegister(new CatalogModule());

?>