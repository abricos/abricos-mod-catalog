<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CatalogConfig
 *
 * @property bool $dbPrefix
 * @property bool $elementNameChange Allow to change the name of the element
 * @property bool $elementNameUnique Element Name is unique
 * @property bool $elementBaseTypeDisable Disable the creation of basic elements
 * @property bool $versionControl Version Control
 */
class CatalogConfig extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'Config';
}

/**
 * Class CatalogType
 */
class CatalogType {

    /**
     * Логический тип
     *
     * @var integer
     */
    const TP_BOOLEAN = 0;
    /**
     * Целое число
     *
     * @var integer
     */
    const TP_NUMBER = 1;
    /**
     * Число с плавающей точкой
     *
     * @var integer
     */
    const TP_DOUBLE = 2;
    /**
     * Строка
     *
     * @var integer
     */
    const TP_STRING = 3;
    // const TP_LIST = 4;
    /**
     * Табличный список
     *
     * @var integer
     */
    const TP_TABLE = 5;
    // const TP_MULTI = 6;
    /**
     * Текст
     *
     * @var integer
     */
    const TP_TEXT = 7;
    // const TP_DICT = 8;
    // const TP_CHILDELEMENT = 9;
    /**
     * Список на другие элементы (поле содержит кеш идентификаторов через запятую)
     *
     * @var integer
     */
    const TP_ELDEPENDS = 9;
    /**
     * Список на другие элементы (поле содержит кеш имен через запятую)
     *
     * @var unknown_type
     */
    const TP_ELDEPENDSNAME = 10;
    /**
     * Набор файлов
     *
     * @var integer
     */
    const TP_FILES = 11;
    /**
     * Валюта
     *
     * @var integer
     */
    const TP_CURRENCY = 12;
}


/**
 * Раздел каталога
 *
 * @property int $parentid Parent Catalog ID
 * @property string $name Name
 * @property string $title Title
 * @property string $foto Image ID of filemanager module
 * @property string $fotoExt Image Extension
 * @property boolean $menuDisable
 * @property boolean $listDisable
 * @property int $order
 * @property int $dateline Create Date
 * @property int $elementCount Count of Element in this catalog
 *
 * @property string $descript
 * @property string $metaTitle
 * @property string $metaKeys
 * @property string $metaDescript
 *
 */
class Catalog extends AbricosModel {

    protected $_structModule = 'catalog';
    protected $_structName = 'Catalog';

    public function FotoSrc($w = 0, $h = 0){
        if (empty($this->foto)){
            return "/images/empty.gif";
        }

        $arr = array();
        if ($w > 0)
            $arr[] = "w_".$w;
        if ($h > 0)
            $arr[] = "h_".$h;

        $ret = "/filemanager/i/".$this->foto."/";
        if (count($arr) > 0){
            $ret = $ret.implode("-", $arr)."/";
        }
        $ret .= $this->name.".".$this->fotoExt;

        return $ret;
    }

    public function URI(){
        return "";
    }
}


/**
 * Class CatalogList
 *
 * @method Catalog GetByIndex(int $index)
 * @method Catalog Get(int $catalogid)
 */
class CatalogList extends AbricosModelList {
}


/**
 * Class CatalogElementType
 *
 * @property AbricosMultiLangValue $title
 * @property AbricosMultiLangValue $titles
 * @property AbricosMultiLangValue $descript
 * @property string $name
 * @property string $composite The expression for the formation of names
 * @property CatalogElementOptionList $options
 */
class CatalogElementType extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'ElementType';

    public $_cacheCompositeVars;

    public static function GetTableName(CatalogApp $app, CatalogElementType $elType){
        $name = $elType->name;
        if (empty($name)){
            return $app->GetDBPrefix()."element";
        }
        return $app->GetDBPrefix()."eltbl_".$name;
    }
}

/**
 * Class CatalogElementTypeList
 *
 * @method CatalogElementType Get($id)
 * @method CatalogElementType GetByIndex($index)
 */
class CatalogElementTypeList extends AbricosModelList {

    /**
     * @param string $name
     * @return CatalogElementType
     */
    public function GetByName($name){
        for ($i = 0; $i < $this->Count(); $i++){
            $elType = $this->GetByIndex($i);
            if ($elType->name == $name){
                return $elType;
            }
        }
        return null;
    }

    /**
     * Получить опцию элемента по идентификатору
     *
     * @param integer $optionid
     * @return CatalogElementOption
     */
    public function GetOptionById($optionid){
        for ($i = 0; $i < $this->Count(); $i++){
            $elType = $this->GetByIndex($i);
            $option = $elType->options->Get($optionid);
            if (!empty($option)){
                return $option;
            }
        }
        return null;
    }

    private $_cacheOptions = array();

    /**
     * @param int $elTypeId
     * @return CatalogElementOptionList
     */
    public function GetFullOptionList($elTypeId){
        if (isset($this->_cacheOptions[$elTypeId])){
            return $this->_cacheOptions[$elTypeId];
        }

        if ($elTypeId === 0){
            return $this->_cacheOptions[$elTypeId] = $this->Get(0)->options;
        }

        /** @var CatalogElementOptionList $list */
        $list = $this->app->InstanceClass('ElementOptionList');

        $elType = $this->Get(0);
        while ($elType){
            $count = $elType->options->Count();
            for ($i = 0; $i < $count; $i++){
                $list->Add($elType->options->GetByIndex($i));
            }

            if ($elType->id === $elTypeId){
                break;
            }
            $elType = $this->Get($elTypeId);
        }
        return $this->_cacheOptions[$elTypeId] = $list;
    }
}

/**
 * Class CatalogElementOption
 *
 * @property int $elTypeId
 * @property int $type Option Type (CatalogType::TP_*)
 * @property int $size Size in db field
 * @property string $name Name
 * @property AbricosMultiLangValue $title Title
 * @property AbricosMultiLangValue $descript Description
 * @property int $currencyid
 * @property object $values
 * @property int $groupid
 * @property int $order
 */
class CatalogElementOption extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'ElementOption';

    public static function DataFix(CatalogElementOption $option){
        switch ($option->type){
            case CatalogType::TP_BOOLEAN:
                $option->size = 1;
                return;
            case CatalogType::TP_NUMBER:
                $option->size = min(max(intval($option->size), 1), 10);
                break;
            case CatalogType::TP_STRING:
                $option->size = min(max(intval($option->size), 2), 255);
                break;
            case CatalogType::TP_DOUBLE:
            case CatalogType::TP_CURRENCY:
                $asz = explode(",", $option->size);
                $asz[0] = min(max(intval($asz[0]), 3), 10);
                $asz[1] = min(max(intval($asz[1]), 0), 5);
                $option->size = $asz[0].",".$asz[1];
                return;
            case CatalogType::TP_TEXT:
            case CatalogType::TP_ELDEPENDS:
            case CatalogType::TP_ELDEPENDSNAME:
            case CatalogType::TP_FILES:
                $option->size = 0;
                break;
        }
    }

    public static function GetTableName(CatalogApp $app, CatalogElementType $elType, CatalogElementOption $option){
        $tableName = CatalogElementType::GetTableName($app, $elType);
        return $tableName."_fld_".$option->name;
    }

    protected static $_utm;
    protected static $_utmf;

    /**
     * @return Ab_UserText
     */
    protected static function GetTextParser($isFull){
        if ($isFull){
            if (!empty(CatalogElementOption::$_utmf)){
                return CatalogElementOption::$_utmf;
            }
            CatalogElementOption::$_utmf = Abricos::TextParser(true);
            return CatalogElementOption::$_utmf;
        } else {
            if (!empty(CatalogElementOption::$_utm)){
                return CatalogElementOption::$_utm;
            }
            CatalogElementOption::$_utm = Abricos::TextParser();
            return CatalogElementOption::$_utm;
        }
    }

    public function FixElementValue($element){
        $name = $this->name;
        $val = isset($element->values->$name) ? $element->values->$name : null;

        switch ($this->type){
            case CatalogType::TP_BOOLEAN:
                $val = empty($val) ? 0 : 1;
                break;
            case CatalogType::TP_NUMBER:
                $val = bkint($val);
                break;
            case CatalogType::TP_DOUBLE:
            case CatalogType::TP_CURRENCY:
                $val = doubleval($val);
                break;
            case CatalogType::TP_STRING:
                $val = CatalogElementOption::GetTextParser(true)->Parser($val);
                break;
            case CatalogType::TP_TEXT:
                $val = CatalogElementOption::GetTextParser()->Parser($val);
                break;
            case CatalogType::TP_TABLE:
                $val = bkint($val);
                break;

            // TODO: release
            /*
            case CatalogType::TP_ELDEPENDS:
                $cfg = new CatalogElementListConfig();
                $cfg->elids = explode(",", $val);
                $rows = CatalogQuery::ElementList($db, $pfx, $userid, $isAdmin, $cfg);
                $aIds = array();
                while (($d = $db->fetch_array($rows))){
                    $aIds[] = $d['id'];
                }
                $val = "'".implode(",", $aIds)."'";
                break;
            case CatalogType::TP_ELDEPENDSNAME:
                $cfg = new CatalogElementListConfig();
                $cfg->elnames = explode(",", $val);
                $rows = CatalogQuery::ElementList($db, $pfx, $userid, $isAdmin, $cfg);
                $aNames = array();
                while (($d = $db->fetch_array($rows))){
                    $aNames[] = $d['nm'];
                }
                $val = "'".implode(",", $aNames)."'";
                break;
            case CatalogType::TP_FILES:
                $aFiles = CatalogQuery::ElementDetailOptionFilesUpdate($db, $pfx, $elid, $option, $val);

                $val = "'".implode(",", $aFiles)."'";
                break;
            /**/
            default:
                $val = strval($val);
                break;
        }

        $element->values->$name = $val;
        return $val;
    }

    /**
     * @param CatalogElement $element
     */
    public function GetElementValue($element){
        $name = $this->name;
        return isset($element->values->$name) ? $element->values->$name : null;
    }
}

/**
 * Class CatalogElementOptionList
 *
 * @method CatalogElementOption Get($optionid)
 * @method CatalogElementOption GetByIndex($index)
 */
class CatalogElementOptionList extends AbricosModelList {

    protected $isCheckDouble = true;

    /**
     * @param string $name
     * @return CatalogElementOption
     */
    public function GetByName($name){
        $cnt = $this->Count();
        for ($i = 0; $i < $cnt; $i++){
            $item = $this->GetByIndex($i);
            if ($name === $item->name){
                return $item;
            }
        }
        return null;
    }
}


class CatalogStatisticElement extends AbricosItem {
    public $catid;
    public $elTypeId;
    public $count;

    public function __construct($d){
        if (empty($d['id'])){
            $this->id = $d['catid'].'-'.$d['tpid'];
        } else {
            $this->id = $d['id'];
        }

        $this->catid = intval($d['catid']);
        $this->elTypeId = intval($d['tpid']);
        $this->count = intval($d['cnt']);
    }
}

class CatalogStatisticElementList extends AbricosList {
    /**
     * Кол-во элементов в каталоге
     *
     * @var array
     */
    public $catalogCounter;
    /**
     * Кол-во элементов по типу
     *
     * @var array
     */
    public $elTypeCounter;

    public function __construct(){
        parent::__construct();
        $this->catalogCounter = array();
        $this->elTypeCounter = array();
    }

    public function Add($item){
        parent::Add($item);
        if (empty($this->catalogCounter[$item->catid])){
            $this->catalogCounter[$item->catid] = $item->count;
        } else {
            $this->catalogCounter[$item->catid] = $this->catalogCounter[$item->catid] + $item->count;
        }

        if (empty($this->elTypeCounter[$item->elTypeId])){
            $this->elTypeCounter[$item->elTypeId] = $item->count;
        } else {
            $this->elTypeCounter[$item->elTypeId] = $this->elTypeCounter[$item->elTypeId] + $item->count;
        }

    }
}

require_once 'modelElement.php';

?>