<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class CatalogElement
 *
 * @property CatalogApp $app
 *
 * @property int $catalogid Catalog ID
 * @property int $elTypeId Element Type ID
 * @property int $userid User ID
 * @property string $title Title
 * @property string $name Name
 * @property array $values
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
class CatalogElement extends AbricosModel {

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

    public function GetValue($name){
        if (!isset($this->values->$name)){
            return;
        }
        return $this->values->$name;
    }

    public function SetValue($name, $value){
        $this->values->$name = $value;
    }
}

class CatalogElementList extends AbricosModelList {

    /**
     * @var CatalogELConfig
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

/**
 * Class CatalogELConfig
 *
 * @property string filterFields
 * @property CatalogELConfigFilterList $filters
 */
class CatalogELConfig extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'ELConfig';

    public function SetConfig($d){
        $this->filters = isset($d->filter) ? $d->filter : array();

        $aFFields = explode(',', $this->filterFields);

        for ($i = 0; $i < $this->filters->Count(); $i++){
            $filter = $this->filters->GetByIndex($i);
            $valid = false;
            for ($ii = 0; $ii < count($aFFields); $ii++){
                if ($filter->field === $aFFields[$ii]){
                    $valid = true;
                    break;
                }
            }
            $filter->isValid = $valid;
        }
    }
}

class CatalogELConfigList extends AbricosModelList {
    protected $_structModule = 'catalog';
    protected $_structName = 'ELConfigList';
    protected $_structData = 'elConfigList';
}

/**
 * Class CatalogELConfigFilter
 *
 * @property string $optionType
 * @property string $field
 * @property string $exp Expression, values '<,<=,=,>,>=,<>'
 * @property string $value
 */
class CatalogELConfigFilter extends AbricosModel {
    protected $_structModule = 'catalog';
    protected $_structName = 'ELConfigFilter';

    /**
     * @var bool
     */
    public $isValid = false;
}

/**
 * Class CatalogELConfigFilterList
 *
 * @method CatalogELConfigFilter Get($filterid)
 * @method CatalogELConfigFilter GetByIndex($index)
 */
class CatalogELConfigFilterList extends AbricosModelList {
    protected $_structModule = 'catalog';
    protected $_structName = 'ELConfigFilterList';
}

///////////////// TOD: remove old function

class old_CatalogELConfig extends AbricosItem {

    /**
     * Maximum element count on page
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

    public function __construct(){
        $this->elids = array();
        $this->elnames = array();
        $this->eltpids = array();

        $this->orders = new CatalogElementOrderOptionList();
        $this->extFields = new CatalogElementOptionList();
        $this->where = new CatalogElementFilterOptionList();
    }
}

class CatalogElementFilterOptionList extends AbricosModelList {

    public function __construct(){
        parent::__construct();
        $this->isCheckDouble = true;
    }

    /**
     * @param CatalogElementFilterOption $option
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
        parent::Add(new CatalogElementFilterOption($option, $isDesc));
    }

    /**
     * @return CatalogElementFilterOption
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}

class CatalogElementOrderOption extends AbricosItem {

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


?>