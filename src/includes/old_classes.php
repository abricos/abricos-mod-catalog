<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


class CatalogCurrency extends CatalogItem {
    public $isDefault;
    public $title;
    public $codeStr;
    public $codeNum;
    public $rateVal;
    public $rateDate;
    public $prefix;
    public $postfix;
    public $ord;
    public $dateline;

    public function __construct($d){
        parent::__construct($d);

        $this->isDefault = isset($d['isdefault']) ? $d['isdefault'] > 0 : false;
        $this->title = isset($d['title']) ? strval($d['title']) : "";
        $this->codeStr = isset($d['codestr']) ? strval($d['codestr']) : "";
        $this->codeNum = isset($d['codenum']) ? intval($d['codenum']) : 0;
        $this->rateVal = isset($d['rateval']) ? doubleval($d['rateval']) : 0;
        $this->rateDate = isset($d['ratedate']) ? intval($d['ratedate']) : 0;
        $this->prefix = isset($d['prefix']) ? strval($d['prefix']) : "";
        $this->postfix = isset($d['postfix']) ? strval($d['postfix']) : "";
        $this->ord = isset($d['ord']) ? intval($d['ord']) : 0;
        $this->dateline = isset($d['dateline']) ? intval($d['dateline']) : 0;
    }

    public function ToAJAX(){
        $man = null;
        if (func_num_args() > 0){
            $man = func_get_arg(0);
        }

        $ret = parent::ToAJAX();
        $ret->isdefault = $this->isDefault;
        $ret->title = $this->title;
        $ret->codestr = $this->codeStr;
        $ret->codenum = $this->codeNum;
        $ret->rateval = $this->rateVal;
        $ret->ratedate = $this->rateDate;
        $ret->prefix = $this->prefix;
        $ret->postfix = $this->postfix;
        if (!empty($man) && $man->isAdminRole()){
            $ret->ord = $this->ord;
            $ret->dateline = $this->dateline;
        }
        return $ret;
    }
}

class CatalogCurrencyList extends AbricosModelList {

    /**
     * @return CatalogCurrency
     */
    public function Get($id){
        return parent::Get($id);
    }

    /**
     * @return CatalogCurrency
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}


class CatalogFile extends AbricosItem {
    public $name;
    public $counter;
    public $dateline;

    public function __construct($d){
        $this->id = strval($d['id']);
        $this->name = strval($d['fn']);
        $this->counter = intval($d['cnt']);
        $this->dateline = intval($d['dl']);
    }

    public function URL(){
        return "/filemanager/i/".$this->id."/".$this->name;
    }
}

class CatalogFileList extends AbricosList {

    /**
     * @return CatalogFile
     */
    public function Get($id){
        return parent::Get($id);
    }
}


/**
 * Class CatalogElementTypeList
 */
class old_CatalogElementTypeList extends AbricosModelList {

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

class CatalogElementOptionGroup extends CatalogItem {

    public $elTypeId;
    public $title;
    public $name;

    public function __construct($d){
        parent::__construct($d);
        $this->elTypeId = intval($d['tpid']);
        $this->title = strval($d['tl']);
        $this->name = strval($d['nm']);
    }

    public function ToAJAX(){
        $ret = new stdClass();
        $ret->id = $this->id;
        $ret->tpid = $this->elTypeId;
        $ret->tl = $this->title;
        $ret->nm = $this->name;
        return $ret;
    }

}

class CatalogElementOptionGroupList extends AbricosModelList {

    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param integer $id
     * @return CatalogElementOptionGroup
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    /**
     * @param integer $id
     * @return CatalogElementOptionGroup
     */
    public function Get($id){
        return parent::Get($id);
    }

    /**
     * @param CatalogElementOptionGroup $name
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

/**
 * Опция элемента
 */
class old_CatalogElementOption extends CatalogItem {
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


class old_CatalogElementOptionList extends AbricosModelList {

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

/**
 * Class CatalogElement
 *
 * @property int $catalogid Catalog ID
 * @property int $elTypeId Element Type ID
 * @property int $userid User ID
 * @property string $title Title
 * @property string $name Name
 * @property string $foto Image ID of filemanager module
 * @property string $fotoExt Image Extension
 *
 * @property string $version System Version of Element
 * @property int $pElementId Prev Version Element ID
 * @property string $changelog
 *
 * @property int $order
 * @property bool $isModer
 * @property int $dateline Create Date
 * @property int $upddate Update Date
 *
 * @property string $metaTitle
 * @property string $metaKeys
 * @property string $metaDescript
 *
 */
class old_CatalogElement extends AbricosModel {

    protected $_structModule = 'catalog';
    protected $_structName = 'Element';

    // public $ext = array(); // TODO: extension release

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
 * Подробная информация по элементу
 */
class old_CatalogElementDetail {

    public $metaTitle;
    public $metaKeys;
    public $metaDesc;

    public $optionsBase;
    public $optionsPers;

    /**
     * Идентификаторы картинок
     *
     * @var array
     */
    public $fotos;

    /**
     * Список картинок - подробный
     *
     * @var CatalogFotoList
     */
    public $fotoList;


    public function __construct($d, $dOptBase, $dOptPers, $fotos, CatalogFotoList $fotoList){
        $this->metaTitle = strval($d['mtl']);
        $this->metaKeys = strval($d['mks']);
        $this->metaDesc = strval($d['mdsc']);

        $this->version = intval($d['v']);
        $this->pElementId = intval($d['pelid']);
        $this->changeLog = strval($d['chlg']);

        $this->optionsBase = $dOptBase;
        $this->optionsPers = $dOptPers;

        $this->fotos = $fotos;
        $this->fotoList = $fotoList;
    }

    public function ToAJAX(){
        $man = func_get_arg(0);

        $ret = new stdClass();
        $ret->chlg = $this->changeLog;
        $ret->fotos = $this->fotos;
        $ret->optb = $this->optionsBase;
        $ret->optp = $this->optionsPers;

        // if ($man->IsAdminRole()){
        $ret->mtl = $this->metaTitle;
        $ret->mks = $this->metaKeys;
        $ret->mdsc = $this->metaDesc;
        // }

        return $ret;
    }
}

class CatalogElementList extends AbricosModelList {

    /**
     * @var CatalogElementListConfig
     */
    public $cfg = null;

    /**
     * Всего таких записей в базе
     *
     * @var integer
     */
    public $total;

    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param integer $i
     * @return CatalogElement
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    /**
     * @param integer $id
     * @return CatalogElement
     */
    public function Get($id){
        return parent::Get($id);
    }

    public function ToAJAX(){
        $man = func_get_arg(0);

        $list = array();
        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $list[] = $this->GetByIndex($i)->ToAJAX($man);
        }

        $ret = new stdClass();
        $ret->list = $list;
        $ret->total = $this->total;

        return $ret;
    }
}

class CatalogElementChangeLog extends AbricosItem {

    /**
     * Идентификатор элемента предыдущей версии
     *
     * @var integer
     */
    public $pvElementId;

    /**
     * Служебная версия элемента
     *
     * @var integer
     */
    public $version;

    /**
     * Дополнительные значения опций
     *
     * @var array
     */
    public $ext;

    /**
     * Дата публикации
     *
     * @var integer
     */
    public $dateline;

    /**
     * Список изменений
     *
     * @var string
     */
    public $log;

    public function __construct($d){
        parent::__construct($d);
        $this->pvElementId = intval($d['pid']);
        $this->version = intval($d['v']);
        $this->dateline = intval($d['dl']);
        $this->log = strval($d['chlg']);
        $this->ext = array();
    }
}

class CatalogElementChangeLogList extends AbricosList {

    /**
     * @return CatalogElementChangeLog
     */
    public function Get($id){
        return parent::Get($id);
    }

    /**
     * @return CatalogElementChangeLog
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}

/**
 * Фото элемента каталога
 */
class CatalogFoto extends AbricosItem {

    public $elementid;
    public $filehash;
    public $name;
    public $extension;
    public $filesize;
    public $width;
    public $height;

    public function __construct($d){
        parent::__construct($d);

        $this->filehash = $d['f'];
        $this->elementid = intval($d['elid']);
        $this->name = $d['nm'];
        $this->extension = $d['ext'];
        $this->filesize = $d['sz'];
        $this->width = $d['w'];
        $this->height = $d['h'];
    }

    public function Link($w = 0, $h = 0){
        $arr = array();
        if ($w > 0)
            $arr[] = "w_".$w;
        if ($h > 0)
            $arr[] = "h_".$h;

        $ret = "/filemanager/i/".$this->filehash."/";
        if (count($arr) > 0){
            $ret = $ret.implode("-", $arr)."/";
        }
        return $ret.$this->name;
    }
}

class CatalogFotoList extends AbricosList {

    private $_groups = array();

    /**
     * @return CatalogFoto
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    public function Add($item){
        parent::Add($item);

        $elid = $item->elementid;
        if (!isset($this->_groups[$elid])){
            $this->_groups[$elid] = array();
        }
        $this->_groups[$elid][] = $item;
    }

    /**
     * Получить все фото элемента
     *
     * @param integer $elid
     * @return array
     */
    public function GetGroup($elid){
        if (empty($this->_groups[$elid])){
            return array();
        }
        return $this->_groups[$elid];
    }
}

/**
 * Параметры списка
 */
class old_CatalogElementListConfig {

    /**
     * Количество на странице. 0 - все элементы
     *
     * @var integer
     */
    public $limit = 0;

    /**
     * Страница
     *
     * @var integer
     */
    public $page = 1;

    /**
     * Сортировать список
     *
     * @var CatalogElementOrderOptionList
     */
    public $orders;

    /**
     * Дополнительные поля в списке
     *
     * @var CatalogElementOptionList
     */
    public $extFields;

    public $where;

    /**
     * Идентификаторы каталогов
     *
     * @var array
     */
    public $catids;

    /**
     * Идентификаторы элементов
     *
     * @var array
     */
    public $elids;

    /**
     * Имена элементов
     *
     * @var array
     */
    public $elnames;

    /**
     * Идентификаторы типов элемента
     *
     * @var array
     */
    public $eltpids;

    public function __construct($catids = null){
        if (is_null($catids)){
            $catids = array();
        } else if (!is_array($catids)){
            $catids = array($catids);
        }
        $this->catids = $catids;
        $this->elids = array();
        $this->elnames = array();
        $this->eltpids = array();

        $this->orders = new CatalogElementOrderOptionList();
        $this->extFields = new CatalogElementOptionList();
        $this->where = new CatalogElementWhereOptionList();
    }

}

class CatalogElementWhereOption extends CatalogItem {

    /**
     * @var CatalogElementOption
     */
    public $option;

    /**
     * Условие (напрмер '>0')
     *
     * @var string
     */
    public $exp = "";

    public function __construct(CatalogElementOption $option, $exp){
        $this->id = $option->id;
        $this->option = $option;
        $this->exp = $exp;
    }
}

class CatalogElementWhereOptionList extends AbricosModelList {

    public function __construct(){
        parent::__construct();
        $this->isCheckDouble = true;
    }

    /**
     * @param CatalogElementWhereOption $option
     */
    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param CatalogElementOption $option
     * @param boolean $isDesc
     */
    public function AddByOption($option, $isDesc = false){
        if (empty($option)){
            return;
        }
        parent::Add(new CatalogElementWhereOption($option, $isDesc));
    }

    /**
     * @return CatalogElementWhereOption
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}

class CatalogElementOrderOption extends CatalogItem {

    /**
     * @var CatalogElementOption
     */
    public $option;

    /**
     * Сортировка по убыванию
     *
     * @var boolean
     */
    public $isDesc = false;

    /**
     * Пустые значения убирать в конец списка
     *
     * @var boolean
     */
    public $zeroDesc = false;

    public function __construct(CatalogElementOption $option, $isDesc = false){
        $this->id = $option->id;
        $this->option = $option;
        $this->isDesc = $isDesc;
    }
}

class CatalogElementOrderOptionList extends AbricosModelList {

    public function __construct(){
        parent::__construct();
        $this->isCheckDouble = true;
    }

    public function Add($item){
        parent::Add($item);
    }

    /**
     * @param CatalogElementOption $option
     * @param boolean $isDesc
     *
     * @return CatalogElementOrderOption
     */
    public function AddByOption($option, $isDesc = false){
        if (empty($option)){
            return;
        }
        $order = new CatalogElementOrderOption($option, $isDesc);
        parent::Add($order);

        return $order;
    }

    /**
     * @return CatalogElementOrderOption
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}

class CatalogItem extends AbricosItem {
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

?>