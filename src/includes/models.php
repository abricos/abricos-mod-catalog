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
 * @property CatalogElementOptionList $options
 */
class CatalogElementType extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'ElementType';

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
 * @property array $values
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