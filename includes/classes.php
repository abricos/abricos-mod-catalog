<?php 
/**
 * @package Abricos
 * @subpackage EShop
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

/**
 * Раздел каталога
 */
class Catalog extends CatalogItem {
	
	const TP_BOOLEAN = 0;
	const TP_NUMBER = 1;
	const TP_DOUBLE = 2;
	const TP_STRING = 3;
	// const TP_LIST = 4;
	const TP_TABLE = 5;
	// const TP_MULTI = 6;
	const TP_TEXT = 7;
	// const TP_DICT = 8;
	// const TP_CHILDELEMENT = 9;
	
	/**
	 * @var Catalog
	 */
	public $parent = null;
	
	public $parentid;
	
	public $title;
	public $name;
	public $order;
	
	/**
	 * Идентификатор файла картинки менеджера файлов
	 * @var string
	 */
	public $foto;
	
	/**
	 * Расширение картинки (png, jpg и т.п.)
	 * @var string
	 */
	public $fotoExt;
	
	public $dateline;
	
	public $elementCount;
	
	/**
	 * @var CatalogList
	 */
	public $childs;
	
	/**
	 * @var CatalogDetail
	 */
	public $detail = null;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->parentid	= intval($d['pid']); 
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->foto		= strval($d['foto']);
		$this->fotoExt	= strval($d['fext']);
		$this->order	= intval($d['ord']);
		$this->elementCount = intval($d['ecnt']);
		 
		$this->childs = new CatalogList($this);
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = parent::ToAJAX();
		$ret->pid	= $this->parentid;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
		$ret->foto	= $this->foto;
		$ret->ord	= $this->order;
		$ret->ecnt	= $this->elementCount;
		
		$ret->dtl	= null;
		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX($man);
		}
		
		if ($this->childs->Count()>0){
			$ret->childs = $this->childs->ToAJAX($man);
		}
		return $ret;
	}
	
	public function FotoSrc($w=0, $h=0){
		
		if (empty($this->foto)){
			return "/images/empty.gif";
		}
		
		$arr = array();
		if ($w > 0) array_push($arr, "w_".$w);
		if ($h > 0) array_push($arr, "h_".$h);
		
		$ret = "/filemanager/i/".$this->foto."/";
		if (count($arr)>0){
			$ret = $ret.implode("-", $arr)."/";
		}
		$ret .= $this->name.".".$this->fotoExt;
		
		return $ret;
	}
	
	public function URI(){ return ""; }

}

/**
 * Подробная информация по разделу каталога
 */
class CatalogDetail {
	
	public $descript;
	public $metaTitle;
	public $metaKeys;
	public $metaDescript;

	public function __construct($d){
		$this->descript		= strval($d['dsc']);
		$this->metaTitle	= strval($d['mtl']);
		$this->metaKeys		= strval($d['mks']);
		$this->metaDescript	= strval($d['mdsc']);
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->dsc	= $this->descript;
		if ($man->IsAdminRole()){
			$ret->mtl	= $this->metaTitle;
			$ret->mks	= $this->metaKeys;
			$ret->mdsc	= $this->metaDescript;
		}
		return $ret;
	}
}

class CatalogList extends CatalogItemList {
	
	/**
	 * @var Catalog
	 */
	public $owner;
	
	public function __construct($cat){
		parent::__construct();
		
		$this->owner = $cat;
	}
	
	public function Add(Catalog $item){
		parent::Add($item);
		$item->parent = $this->owner;
	}
	

	/**
	 * Получить раздел каталога по индексу
	 * @param integer $index
	 * @return Catalog
	 */
	public function GetByIndex($index){
		return parent::GetByIndex($index);
	}
	
	/**
	 * Получить раздел каталога по идентификатору
	 * @param integer $id
	 * @return Catalog
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * Поиск элемента по списку, включая поиск по дочерним элементам
	 * @param integer $id
	 * @return Catalog
	 */
	public function Find($id){
		$id=intval($id);
		$item = $this->Get($id);
		if (!empty($item)){ return $item; }
		
		$count = $this->Count();
		for ($i=0;$i<$count;$i++){
			$item = $this->GetByIndex($i)->childs->Find($id);
			if (!empty($item)){ return $item; }
		}
		
		return null;
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

class CatalogElementType extends CatalogItem {

	public $title;
	public $name;
	
	public $tableName = "";
	
	/**
	 * @var CatalogElementTypeOptionList
	 */
	public $options;
	
	public function __construct($d = array()){
		parent::__construct($d);

		$this->title	= strval($d['tl']); 
		$this->name		= strval($d['nm']);

		$this->options = new CatalogElementTypeOptionList();
		
		$this->tableName = "element";
		if ($this->id > 0){
			$this->tableName = "eltbl_".$this->name;
		}
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
	
		if ($this->options->Count()>0){
			$ret->options = $this->options->ToAJAX($man);
		}
		return $ret;
	}
}

/**
 * Тип элемента
 */
class CatalogElementTypeList extends CatalogItemList {

	public function Add(CatalogElementType $item){
		parent::Add($item);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementType
	 */
	public function GetByIndex($index){
		return parent::GetByIndex($index);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementType
	 */
	public function Get($id){
		return parent::Get($id);
	}

	public function ToAJAX(CatalogModuleManager $man){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

/**
 * Опция элемента
 */
class CatalogElementTypeOption extends CatalogItem {
	
	public $elTypeId;
	public $type;
	public $title;
	public $name;
	
	public function __construct($d){
		parent::__construct($d);
		$this->elTypeId = intval($d['tpid']);
		$this->type		= intval($d['tp']);
		$this->title	= $d['tl'];
		$this->name		= $d['nm'];
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->tpid		= $this->elTypeId;
		$ret->tp		= $this->type;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		return $ret;
	}
}

/**
 * Опция - тип поля таблица
 */
class CatalogElementTypeOptionTable extends CatalogElementTypeOption {
	
	public $values = array();
	
	public function __construct($d){
		parent::__construct($d);
	}
	
	public function ToAJAX($man){
		$ret = parent::ToAJAX($man);
		$ret->values = $this->values;
		return $ret;
	}
}


class CatalogElementTypeOptionList extends CatalogItemList {
	
	public function __construct(){
		parent::__construct();
		$this->isCheckDouble = true;
	}

	public function Add(CatalogElementTypeOption $item = null){
		parent::Add($item);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementTypeOption
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementTypeOption
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * @param CatalogElementTypeOption $name
	 */
	public function GetByName($name){
		
		$cnt = $this->Count();
		for ($i=0; $i<$cnt; $i++){
			$item = $this->GetByIndex($i);
			if ($name == $item->name){ return $item; }
		}
		return null;
	}

	public function ToAJAX(CatalogModuleManager $man){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

class CatalogElement extends CatalogItem {

	public $catid;
	public $elTypeId;
	
	public $title;
	public $name;
	
	public $order;
	
	public $foto;
	
	public $ext = array();
	
	/**
	 * @var CatalogElementDetail
	 */
	public $detail = null;
	
	/**
	 * @param array $d
	 * @param CatalogElementTypeOptionList $extFields
	 */
	public function __construct($d){
		parent::__construct($d);
		
		$this->catid = intval($d['catid']);
		$this->elTypeId = intval($d['tpid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->order	= intval($d['ord']);
		$this->foto		= strval($d['foto']);
		
		if (is_array($d['ext'])){
			$this->ext = $d['ext'];
		}
	}
	
	public function FotoSrc($w=0, $h=0){
	
		if (empty($this->foto)){
			return "/images/empty.gif";
		}
	
		$arr = array();
		if ($w > 0) array_push($arr, "w_".$w);
		if ($h > 0) array_push($arr, "h_".$h);
	
		$ret = "/filemanager/i/".$this->foto."/";
		if (count($arr)>0){
			$ret = $ret.implode("-", $arr)."/";
		}
		$ret .= $this->name; // .".".$this->fotoExt;
	
		return $ret;
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catid;
		$ret->tpid		= $this->elTypeId;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		$ret->ord		= $this->order;
		$ret->foto		= $this->foto;
		
		$ret->dtl	= null;
		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX($man);
		}
		
		if (count($this->ext)>0){
			$ret->ext = $this->ext;
		}
		
		return $ret;
	}
	
	public function URI(){ return ""; }
}


/**
 * Подробная информация по элементу
 */
class CatalogElementDetail {
	
	public $metaTitle;
	public $metaKeys;
	public $metaDesc;

	public $optionsBase;
	public $optionsPers;
	
	/**
	 * Идентификаторы картинок
	 * @var array
	 */
	public $fotos;
	
	/**
	 * Список картинок - подробный
	 * @var CatalogFotoList
	 */
	public $fotoList;

	public function __construct($d, $dOptBase, $dOptPers, $fotos, CatalogFotoList $fotoList){
		$this->metaTitle = strval($d['mtl']);
		$this->metaKeys = strval($d['mks']);
		$this->metaDesc = strval($d['mdsc']);
		$this->optionsBase = $dOptBase;
		$this->optionsPers = $dOptPers;
		
		$this->fotos = $fotos;
		$this->fotoList = $fotoList;
	}

	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->fotos	= $this->fotos;
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

class CatalogElementList extends CatalogItemList {

	/**
	 * @var CatalogElementListConfig
	 */
	public $cfg = null;
	
	/**
	 * Всего таких записей в базе
	 * @var integer
	 */
	public $total;
	
	public function Add(CatalogElement $item){
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

	public function ToAJAX(CatalogModuleManager $man){
		$list = array();
		$count = $this->Count();
		for ($i=0; $i<$count; $i++){
			array_push($list, $this->GetByIndex($i)->ToAJAX($man));
		}
		
		$ret = new stdClass();
		$ret->list = $list;
		$ret->total = $this->total;
	
		return $ret;
	}
}

/**
 * Фото элемента каталога
 */
class CatalogFoto extends AbricosItem {
	
	public $filehash;
	public $name;
	public $extension;
	public $filesize;
	public $width;
	public $height;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->filehash = $d['f'];
		$this->name = $d['nm'];
		$this->extension = $d['ext'];
		$this->filesize = $d['sz'];
		$this->width = $d['w'];
		$this->height = $d['h'];
	}
	
	public function Link($w=0,$h=0){
		$arr = array();
		if ($w > 0) array_push($arr, "w_".$w);
		if ($h > 0) array_push($arr, "h_".$h);
		
		$ret = "/filemanager/i/".$this->filehash."/";
		if (count($arr)>0){
			$ret = $ret.implode("-", $arr)."/";
		}
		return $ret.$this->name;
	}
}

class CatalogFotoList extends AbricosList {
	
	/**
	 * @return CatalogFoto
	 */
	public function GetByIndex($i){return parent::GetByIndex($i);}
}

/**
 * Параметры списка
 */
class CatalogElementListConfig {
	
	/**
	 * Количество на странице. 0 - все элементы
	 * @var integer
	 */
	public $limit = 0;
	
	/**
	 * Страница
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
	 * @var CatalogElementTypeOptionList
	 */
	public $extFields;
	
	public $where;
	
	/**
	 * Идентификаторы каталогов
	 * @var array
	 */
	public $catids;
	
	/**
	 * Идентификаторы элементов
	 * @var array
	 */
	public $elids;
	
	/**
	 * @var CatalogElementTypeList
	 */
	public $typeList;
	
	public function __construct($catids = null){
		if (is_null($catids)){
			$catids = array();
		}else if (!is_array($catids)){
			$catids = array($catids);
		}
		$this->catids = $catids;
		$this->elids = array();
		
		$this->orders = new CatalogElementOrderOptionList();
		$this->extFields = new CatalogElementTypeOptionList();
		$this->where = new CatalogElementWhereOptionList();
	}
	
}

class CatalogElementWhereOption extends CatalogItem {

	/**
	 * @var CatalogElementTypeOption
	 */
	public $option;

	/**
	 * Условие (напрмер '>0')
	 * @var string
	 */
	public $exp = "";

	public function __construct(CatalogElementTypeOption $option, $exp){
		$this->id = $option->id;
		$this->option = $option;
		$this->exp = $exp;
	}
}

class CatalogElementWhereOptionList extends CatalogItemList {

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
	 * @param CatalogElementTypeOption $option
	 * @param boolean $isDesc
	 */
	public function AddByOption($option, $isDesc = false){
		if (empty($option)){ return; }
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
	 * @var CatalogElementTypeOption
	 */
	public $option;

	/**
	 * Сортировка по убыванию
	 * @var boolean
	 */
	public $isDesc = false;
	
	/**
	 * Пустые значения убирать в конец списка
	 * @var boolean
	 */
	public $zeroDesc = false;
	 
	public function __construct(CatalogElementTypeOption $option, $isDesc = false){
		$this->id = $option->id;
		$this->option = $option;
		$this->isDesc = $isDesc;
	}
}

class CatalogElementOrderOptionList extends CatalogItemList {
	
	public function __construct(){
		parent::__construct();
		$this->isCheckDouble = true;
	}
	
	public function Add(CatalogElementOrderOption $item){
		parent::Add($item);
	}
	
	/**
	 * @param CatalogElementTypeOption $option
	 * @param boolean $isDesc
	 * 
	 * @return CatalogElementOrderOption
	 */
	public function AddByOption($option, $isDesc = false){
		if (empty($option)){ return; }
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

class CatalogItem {
	public $id;

	public function __construct($d){
		$this->id = intval($d['id']);
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		return $ret;
	}
}

class CatalogItemList {

	protected $_list = array();
	protected $_map = array();
	
	protected $isCheckDouble = false;

	public function __construct(){
		$this->_list = array();
		$this->_map = array();
	}

	public function Add(CatalogItem $item = null){
		if (empty($item)){ return; }
		
		if ($this->isCheckDouble){
			$checkItem = $this->Get($item->id);
			if (!empty($checkItem)){ return; }
		}
		
		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}

	public function Count(){
		return count($this->_list);
	}

	/**
	 * @param integer $index
	 * @return CatalogItem
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}

	/**
	 * @param integer $id
	 * @return CatalogItem
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
	}

	public function ToAJAX(){
		$list = array();
		$count = $this->Count();
		for ($i=0; $i<$count; $i++){
			array_push($list, $this->GetByIndex($i)->ToAJAX());
		}

		$ret = new stdClass();
		$ret->list = $list;

		return $ret;
	}
}

/**
 * Менеджер каталога для управляющего модуля
 */
class CatalogModuleManager {
	
	/**
	 * @var Ab_Database $db
	 */
	public $db;
	
	/**
	 * Префикс таблиц модуля
	 * @var string
	 */
	private $pfx;
	
	public $CatalogClass			= Catalog;
	public $CatalogListClass		= CatalogList;
	
	public $CatalogElementClass		= CatalogElement;
	public $CatalogElementListClass = CatalogElementList;

	public function __construct($dbPrefix){
		$this->db = CatalogManager::$instance->db;
		$this->pfx = $this->db->prefix."ctg_".$dbPrefix."_";
	}
	
	public function IsAdminRole(){ return false; }
	public function IsWriteRole(){ return false; }
	public function IsViewRole(){ return false; }
	
	
	public function AJAX($d){
		// TODO: идея объеденять запросы в один
		// например $d->do = "cataloglist|elementtypelist"
		switch($d->do){
			case "cataloginitdata":
				return $this->CatalogInitDataToAJAX();
			case "cataloglist":
				return $this->CatalogListToAJAX();
			case "catalog":
				return $this->CatalogToAJAX($d->catid, $d->elementlist);
			case "catalogsave":
				return $this->CatalogSaveToAJAX($d->catid, $d->savedata);
			case "catalogremove":
				return $this->CatalogRemove($d->catid);
			case "elementlist":
				return $this->ElementListToAJAX($d->catid);
			case "elementlistordersave":
				return $this->ElementListOrderSave($d->catid, $d->orders);
			case "element":
				return $this->ElementToAJAX($d->elementid);
			case "elementsave":
				return $this->ElementSaveToAJAX($d->elementid, $d->savedata);
			case "elementremove":
				return $this->ElementRemove($d->elementid);
			case "elementtypelist":
				return $this->ElementTypeList();
			case "optiontablevaluesave":
				return $this->OptionTableValueSave($d->eltypeid, $d->optionid, $d->valueid, $d->value);
			case "optiontablevalueremove":
				return $this->OptionTableValueRemove($d->eltypeid, $d->optionid, $d->valueid);
		}
		return null;
	}
	
	public function CatalogInitDataToAJAX(){
		if (!$this->IsViewRole()){ return false; }
		
		$ret = new stdClass();
		
		$ajaxCatalogs = $this->CatalogListToAJAX();
		$ret->catalogs = $ajaxCatalogs->catalogs;
		
		$ajaxElTypes = $this->ElementTypeListToAJAX();
		$ret->eltypes = $ajaxElTypes->eltypes;
		
		return $ret;
	}
	
	private $_cacheCatalogList;
	public function CatalogList ($clearCache = false){
		if (!$this->IsViewRole()){ return false; }
		
		if ($clearCache){
			$this->_cacheCatalogList = null;
		}
		
		if (!empty($this->_cacheCatalogList)){
			return $this->_cacheCatalogList;
		}
		
		$list = array();
		$rows = CatalogDbQuery::CatalogList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){
			array_push($list, new $this->CatalogClass($d));
		}
		
		if (count($list) == 0){
			array_push($list, new $this->CatalogClass(array(
				"id" => 0,
				"pid" => -1,
				"nm" => "root",
				"tl" => "Root"
			)));
		}
		
		$catList = new CatalogList(null);
		$count = count($list);
		for ($i=0; $i<$count; $i++){
			$cat = $list[$i];
			
			if ($cat->id == 0){
				$catList->Add($cat);
			}else{
				for ($ii=0; $ii<$count; $ii++){
					$pcat = $list[$ii];
					
					if ($pcat->id == $cat->parentid){
						$pcat->childs->Add($cat);
						break;
					}
				}
			}
		}
		$this->_cacheCatalogList = $catList;
		return $catList;
	}
	
	public function CatalogListToAJAX(){
		$list = $this->CatalogList();

		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->catalogs = $list->ToAJAX($this);
		return $ret;
	}

	/**
	 * @param integer $catid
	 * @return Catalog
	 */
	public function Catalog($catid){
		if (!$this->IsViewRole()){ return false; }
		
		$d = CatalogDbQuery::Catalog($this->db, $this->pfx, $catid);
		if (empty($d)){ return null; }
		
		$catList = $this->CatalogList();
		$cat = $catList->Find($catid);
		// $cat = new $this->CatalogClass($d);
		$cat->detail = new CatalogDetail($d);
		
		return $cat;
	}
	
	public function CatalogToAJAX($catid, $isElementList = false){
		$cat = $this->Catalog($catid);
		
		if (empty($cat)){ return null; }
		
		$ret = new stdClass();
		$ret->catalog = $cat->ToAJAX($this);
		
		if ($isElementList){
			$retEls = $this->ElementListToAJAX($catid);
			$ret->elements = $retEls->elements; 
		}
		return $ret;
	}
	
	public function CatalogSave($catid, $d){
		if (!$this->IsAdminRole()){ return null; }
	
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
	
		$catid		= intval($catid);
		$d->pid		= intval($d->pid);
		$d->tl		= $utmf->Parser($d->tl);
		$d->nm		= translateruen($d->tl);
		$d->dsc		= $utm->Parser($d->dsc);
		
		$d->mtl		= $utmf->Parser($d->mtl);
		$d->mks		= $utmf->Parser($d->mks);
		$d->mdsc	= $utmf->Parser($d->mdsc);
	
		$d->ord		= intval($d->ord);
	
		$isNew = false;
		if ($catid == 0){ // добавление нового
				
			$isNew = true;
			$catid = CatalogDbQuery::CatalogAppend($this->db, $this->pfx, $d);
			if (empty($catid)){ return null; }
			
			$this->_cacheCatalogList = null;
		}else{ // сохранение текущего
			CatalogDbQuery::CatalogUpdate($this->db, $this->pfx, $catid, $d);
		}
	
		CatalogDbQuery::FotoRemoveFromBuffer($this->db, $this->pfx, $d->foto);
	
		return $catid;
	}
	
	public function CatalogSaveToAJAX($catid, $d){
		$catid = $this->CatalogSave($catid, $d);
		
		if (empty($catid)){ return null; }
		
		return $this->CatalogToAJAX($catid);
	}
	
	public function CatalogRemove($catid){
		if (!$this->IsAdminRole()){ return null; }
		
		$cat = $this->Catalog($catid);
		
		$count = $cat->childs->Count();
		for ($i=0;$i<$count;$i++){
			$ccat = $cat->childs->GetByIndex($i);
			
			$this->CatalogRemove($ccat->id);
		}
		
		CatalogDbQuery::ElementListRemoveByCatId($this->db, $this->pfx, $catid);
		CatalogDbQuery::CatalogRemove($this->db, $this->pfx, $catid);
		
		return true;
	}
	
	public function ElementList($param){
		if (!$this->IsViewRole()){ return false; }

		$cfg = null; $catid = 0;
		if (is_object($param)){
			$cfg = $param;
		}else {
			$cfg = new CatalogElementListConfig($param);
		}
		
		$cfg->typeList = $this->ElementTypeList();
		
		$list = new $this->CatalogElementListClass();
		$list->cfg = $cfg;
		
		$rows = CatalogDbQuery::ElementList($this->db, $this->pfx, $cfg);
		while (($d = $this->db->fetch_array($rows))){
			
			$cnt = $cfg->extFields->Count();
			if ($cnt > 0){
				$d['ext'] = array();
				for ($i=0; $i<$cnt; $i++){
					$opt = $cfg->extFields->GetByIndex($i);
					$d['ext'][$opt->name] = $d['fld_'.$opt->name];
				}
			}
			
			$list->Add(new $this->CatalogElementClass($d));
		}
		$list->total = CatalogDbQuery::ElementListCount($this->db, $this->pfx, $cfg);
		
		return $list;
	}
	
	public function ElementListToAJAX($param){
		$list = $this->ElementList($param);
		
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->elements = $list->ToAJAX($this);
		return $ret;
	}
	
	public function ElementListOrderSave($catid, $orders){
		if (!$this->IsAdminRole()){ return null; }
		
		$list = $this->ElementList($catid);
		
		for ($i=0;$i<$list->Count();$i++){
			$el = $list->GetByIndex($i);
			$elid = $el->id;
			$order = $orders->$elid;
			
			CatalogDbQuery::ElementOrderUpdate($this->db, $this->pfx, $el->id, $order);
		}
		
		return $this->ElementListToAJAX($catid);
	}
	
	/**
	 * @param integer $elid
	 * @return CatalogElement
	 */
	public function Element($elid){
		if (!$this->IsViewRole()){ return false; }
		
		$dbEl = CatalogDbQuery::Element($this->db, $this->pfx, $elid);
		if (empty($dbEl)){ return null; }
		
		$element = new $this->CatalogElementClass($dbEl);
		
		$elTypeList = $this->ElementTypeList();
		
		$tpBase = $elTypeList->Get(0);
		$dbOptionsBase = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $elid, $tpBase);

		$dbOptionsPers = array();
		if ($element->elTypeId > 0){
			$tpPers = $elTypeList->Get($element->elTypeId);
			if (!empty($tpPers)){
				$dbOptionsPers = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $elid, $tpPers);
			}
		}
		
		$rows = CatalogDbQuery::ElementFotoList($this->db, $this->pfx, $elid);
		$fotos = array();
		$fotoList = new CatalogFotoList();
		while (($row = $this->db->fetch_array($rows))){
			array_push($fotos, $row['f']);
			$fotoList->Add(new CatalogFoto($row));
		}
		
		$detail = new CatalogElementDetail($dbEl, $dbOptionsBase, $dbOptionsPers, $fotos, $fotoList);
		
		$element->detail = $detail;
		
		return $element;
	}
	
	public function ElementToAJAX($elid){
		$element = $this->Element($elid);
		if (empty($element)){ return null; }
		
		$ret = new stdClass();
		$ret->element = $element->ToAJAX($this);
		
		return $ret;
	}
	
	public function ElementSave($elid, $d){
		if (!$this->IsAdminRole()){ return null; }
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		$elid		= intval($elid);
		$d->catid	= intval($d->catid);
		$d->tpid	= intval($d->tpid);
		$d->tl		= $utmf->Parser($d->tl);
		$d->nm		= translateruen($d->tl);
		
		$d->mtl		= $utmf->Parser($d->mtl);
		$d->mks		= $utmf->Parser($d->mks);
		$d->mdsc	= $utmf->Parser($d->mdsc);
		
		$d->ord		= intval($d->ord);
		
		$isNew = false;
		if ($elid == 0){ // добавление нового
			
			$isNew = true;
			$elid = CatalogDbQuery::ElementAppend($this->db, $this->pfx, $d);
			if (empty($elid)){ return null; }
			
		}else{ // сохранение текущего
			CatalogDbQuery::ElementUpdate($this->db, $this->pfx, $elid, $d);
		}
		
		$elTypeList = $this->ElementTypeList();
		
		if (!empty($d->values)){
			foreach($d->values as $tpid => $opts){
				$elType = $elTypeList->Get($tpid);
				CatalogDbQuery::ElementDetailUpdate($this->db, $this->pfx, $elid, $elType, $opts);
			}
		}
		
		// обновление фоток
		CatalogDbQuery::ElementFotoUpdate($this->db, $this->pfx, $elid, $d->fotos);

		return $elid;
	}
	
	public function ElementSaveToAJAX($elid, $d){
		$elid = $this->ElementSave($elid, $d);
		
		if (empty($elid)){ return null; }
		
		return $this->ElementToAJAX($elid);
	}
	
	public function ElementRemove($elid){
		if (!$this->IsAdminRole()){ return null; }
		
		CatalogDbQuery::ElementRemove($this->db, $this->pfx, $elid);
		
		return true;
	}
	
	private $_cacheElementTypeList;
	/**
	 * @return CatalogElementTypeList
	 */
	public function ElementTypeList(){
		if (!$this->IsViewRole()){ return false; }
		
		if (!empty($this->_cacheElementTypeList)){
			return $this->_cacheElementTypeList;
		}
		
		$list = new CatalogElementTypeList();
		$curType = new CatalogElementType();
		$list->Add($curType);

		$rows = CatalogDbQuery::ElementTypeList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new CatalogElementType($d));
		}
		
		$rows = CatalogDbQuery::ElementTypeOptionList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){

			if (empty($curType) || $curType->id != $d['tpid']){
				$curType = $list->Get($d['tpid']);
			}

			if ($d['tp'] == Catalog::TP_TABLE){
				
				$option = new CatalogElementTypeOptionTable($d);
				
				$rtbs = CatalogDbQuery::OptionTableValueList($this->db, $this->pfx, $curType->name, $option->name);
				$option->values = CatalogManager::$instance->ToArrayId($rtbs);
			}else{
				$option = new CatalogElementTypeOption($d);
			}
			
			if (empty($curType)){ 
				continue; // гипотетически такое невозможно
			}
			$curType->options->Add($option);
		}
		return $list;
	}
	
	public function ElementTypeListToAJAX(){
		$list = $this->ElementTypeList();
		
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->eltypes = $list->ToAJAX($this);
		return $ret;
	}
	
	public function FotoAddToBuffer($fhash){
		if (!$this->IsWriteRole()){ return false; }
		
		CatalogDbQuery::FotoAddToBuffer($this->db, $this->pfx, $fhash);
	}
	
	public function OptionTableValueSave($eltypeid, $optionid, $valueid, $value){
		if (!$this->IsAdminRole()){ return null; }
		
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($eltypeid);
		
		if (empty($elType)){ return null; }
		
		$option = $elType->options->Get($optionid);
		if (empty($option)){ return null; }

		if ($option->type != Catalog::TP_TABLE){ return null; }
		
		$utmf = Abricos::TextParser(true);
		$value = $utmf->Parser($value);
		
		if ($valueid == 0){
			$valueid = CatalogDbQuery::OptionTableValueAppend($this->db, $this->pfx, $elType->name, $option->name, $value);
		}else{
			CatalogDbQuery::OptionTableValueUpdate($this->db, $this->pfx, $elType->name, $option->name, $valueid, $value);
		}
		
		$rtbs = CatalogDbQuery::OptionTableValueList($this->db, $this->pfx, $elType->name, $option->name);
		$option->values = CatalogManager::$instance->ToArrayId($rtbs);
		
		$ret = new stdClass();
		$ret->values = $option->values;
		$ret->valueid = $valueid;
		
		return $ret;
	}

	public function OptionTableValueRemove($eltypeid, $optionid, $valueid){
		if (!$this->IsAdminRole()){ return null; }
	
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($eltypeid);
	
		if (empty($elType)){ return null; }
	
		$option = $elType->options->Get($optionid);
		if (empty($option)){ return null; }
	
		if ($option->type != Catalog::TP_TABLE){ return null; }
	
		CatalogDbQuery::OptionTableValueRemove($this->db, $this->pfx, $elType->name, $option->name, $valueid);
	
		$rtbs = CatalogDbQuery::OptionTableValueList($this->db, $this->pfx, $elType->name, $option->name);
		$option->values = CatalogManager::$instance->ToArrayId($rtbs);
	
		$ret = new stdClass();
		$ret->values = $option->values;
	
		return $ret;
	}
	
}

?>