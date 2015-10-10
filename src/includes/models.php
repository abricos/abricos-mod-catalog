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
 * @property bool $elementCreateBaseTypeDisable Disable the creation of basic elements
 * @property bool $versionControl Version Control
 */
class CatalogConfig extends AbricosModel{
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
 * @property string $title Title
 * @property string $name Name
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
 */
class CatalogElementType extends AbricosItem {

    protected $_structModule = 'catalog';
    protected $_structName = 'ElementType';

    /**
     * Название
     *
     * @var string
     */
    public $title;
    /**
     * Название в множественном числе
     *
     * @var string
     */
    public $titleList;

    /**
     * Имя (используется в качестве идентификатора)
     *
     * @var string
     */
    public $name;

    public $tableName = "";

    /**
     * @var CatalogElementOptionList
     */
    public $options;

    public function __construct($d = array()){
        parent::__construct($d);
        $this->options = new CatalogElementOptionList();

        $this->tableName = "element";
        if ($this->id > 0){
            $this->tableName = "eltbl_".$this->name;
        }
    }

    public function ToAJAX(){
        $man = func_get_arg(0);
        $ret = parent::ToJSON();

        if ($this->options->Count() > 0){
            $ret->options = $this->options->ToAJAX($man);
        }
        return $ret;
    }
}

/**
 * Class CatalogElementTypeList
 */
class CatalogElementTypeList extends AbricosModelList {

    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param integer $id
     * @return CatalogElementType
     */
    /*
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
    /**/

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

    /**
     * @param integer $id
     * @return CatalogElementType
     */
    public function Get($id){
        return parent::Get($id);
    }

}

/**
 * Опция элемента
 */
class CatalogElementOption extends AbricosItem {
    protected $_structModule = 'catalog';
    protected $_structName = 'elementOption';

    public $elTypeId;
    public $type;
    public $size;
    public $groupid;
    public $title;
    public $name;
    public $param;
    public $currencyid;
    public $order;
    public $values;
}


class CatalogElementOptionList extends AbricosModelList {

    public function __construct(){
        parent::__construct();
        $this->isCheckDouble = true;
    }

    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param integer $id
     * @return CatalogElementOption
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    /**
     * @param integer $id
     * @return CatalogElementOption
     */
    public function Get($id){
        return parent::Get($id);
    }

    /**
     * @param CatalogElementOption $name
     */
    public function GetByName($name){

        $cnt = $this->Count();
        for ($i = 0; $i < $cnt; $i++){
            $item = $this->GetByIndex($i);
            if ($name == $item->name){
                return $item;
            }
        }
        return null;
    }

    public function ToAJAX(){
        $man = func_get_arg(0);

        $ret = array();
        for ($i = 0; $i < $this->Count(); $i++){
            $ret[] = $this->GetByIndex($i)->ToAJAX($man);
        }
        return $ret;
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