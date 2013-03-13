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
class Catalog {
	
	const TP_BOOLEAN = 0;
	const TP_NUMBER = 1;
	const TP_DOUBLE = 2;
	const TP_STRING = 3;
	const TP_LIST = 4;
	const TP_TABLE = 5;
	const TP_MULTI = 6;
	const TP_TEXT = 7;
	const TP_DICT = 8;
	const TP_CHILDELEMENT = 9;
	
	
	public $id;
	public $parentid;
	
	public $title;
	public $name;
	public $order;
	public $imageid;
	
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
		$this->id		= intval($d['id']);
		$this->parentid	= intval($d['pid']); 
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->elementCount = intval($d['ecnt']);
		 
		$this->childs = new CatalogList();
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->pid	= $this->parentid;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
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

class CatalogList {
	private $_list = array();
	private $_map = array();
	
	public function __construct(){
		$this->_list = array();
		$this->_map = array();
	}

	public function Add(Catalog $item){
		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}
	
	public function Count(){
		return count($this->_list);
	}

	/**
	 * Получить раздел каталога по индексу
	 * @param integer $id
	 * @return Catalog
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}
	
	/**
	 * Получить раздел каталога по идентификатору
	 * @param integer $id
	 * @return Catalog
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
	}
	
	/**
	 * Поиск элемента по списку, включая поиск по дочерним элементам
	 * @param integer $id
	 * @return Catalog
	 */
	public function Find($id){
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

class CatalogElementType {
	public $id;
	public $title;
	public $name;
	
	public $tableName = "";
	
	/**
	 * @var CatalogElementTypeOptionList
	 */
	public $options;
	
	public function __construct($d = array()){
		$this->id		= intval($d['id']);
		$this->title	= strval($d['tl']); 
		$this->name		= strval($d['nm']);

		$this->options = new CatalogElementTypeOptionList();
		
		$this->tableName = "element";
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
class CatalogElementTypeList {
	private $_list = array();
	private $_map = array();

	public function __construct(){
		$this->_list = array();
	}

	public function Add(CatalogElementType $item){
		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}

	public function Count(){
		return count($this->_list);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementType
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}

	/**
	 * @param integer $id
	 * @return CatalogElementType
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
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
class CatalogElementTypeOption {
	
	public $id;
	public $elTypeId;
	public $type;
	public $title;
	public $name;
	
	public function __construct($d){
		$this->id		= intval($d['id']);
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


class CatalogElementTypeOptionList {
	private $_list = array();
	private $_map = array();

	public function __construct(){
		$this->_list = array();
	}

	public function Add(CatalogElementTypeOption $item){
		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}

	public function Count(){
		return count($this->_list);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementTypeOption
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}

	/**
	 * @param integer $id
	 * @return CatalogElementTypeOption
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
	}

	public function ToAJAX(CatalogModuleManager $man){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

class CatalogElement {
	public $id;
	public $catalogid;
	public $elTypeId;
	
	public $title;
	public $name;
	
	/**
	 * @var CatalogElementDetail
	 */
	public $detail = null;
	
	public function __construct($d){
		$this->id		= intval($d['id']);
		$this->catalogid = intval($d['catid']);
		$this->elTypeId = intval($d['tpid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
	}
	
	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catalogid;
		$ret->tpid	= $this->elTypeId;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		
		$ret->dtl	= null;
		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX($man);
		}
		
		return $ret;
	}	
}


/**
 * Подробная информация по элементу
 */
class CatalogElementDetail {
	
	public $metaTitle;
	public $metaKeys;
	public $metaDesc;

	public $optionsBase = array();
	public $optionsExt = array();
	public $fotos = array();

	public function __construct($d, $dOptBase, $fotos){
		$this->metaTitle = strval($d['mtl']);
		$this->metaKeys = strval($d['mks']);
		$this->metaDesc = strval($d['mdsc']);
		$this->optionsBase = $dOptBase;
		
		$this->fotos = $fotos;
	}

	public function ToAJAX(CatalogModuleManager $man){
		$ret = new stdClass();
		$ret->fotos	= $this->fotos;
		$ret->optb = $this->optionsBase;
		
		if ($man->IsAdminRole()){
			$ret->mtl = $this->metaTitle;
			$ret->mks = $this->metaKeys;
			$ret->mdsc = $this->metaDesc;
		}
		
		return $ret;
	}
}


class CatalogElementList {

	private $_list = array();
	private $_map = array();

	/**
	 * Всего таких записей в базе
	 * @var integer
	 */
	public $total;
	
	public function __construct(){
		$this->_list = array();
		$this->_map = array();
	}
	
	public function Add(CatalogElement $item){
		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}

	public function Count(){
		return count($this->_list);
	}

	/**
	 * @param integer $index
	 * @return CatalogElement
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}
	
	/**
	 * @param integer $id
	 * @return CatalogElement
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
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
			case "elementlist":
				return $this->ElementListToAJAX($d->catid);
			case "element":
				return $this->ElementToAJAX($d->elementid);
			case "elementsave":
				return $this->ElementSave($d->elementid, $d->savedata);
			case "elementtypelist":
				return $this->ElementTypeList();
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
	public function CatalogList (){
		if (!$this->IsViewRole()){ return false; }
		
		if (!empty($this->_cacheCatalogList)){
			return $this->_cacheCatalogList;
		}

		$list = array();
		$rows = CatalogDbQuery::CatalogList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){
			array_push($list, new Catalog($d));
		}
		
		$catList = new CatalogList();
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
		
		$cat = new Catalog($d);
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
	
	public function ElementList($catid){
		if (!$this->IsViewRole()){ return false; }
		
		$list = new CatalogElementList();
		
		$rows = CatalogDbQuery::ElementList($this->db, $this->pfx, $catid);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new CatalogElement($d));
		}
		
		return $list;
	}
	
	public function ElementListToAJAX($catid){
		$list = $this->ElementList($catid);
		
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->elements = $list->ToAJAX($this);
		return $ret;
	}
	
	public function Element($elementid){
		if (!$this->IsViewRole()){ return false; }
		
		$dbEl = CatalogDbQuery::Element($this->db, $this->pfx, $elementid);
		if (empty($dbEl)){ return null; }
		
		$element = new CatalogElement($dbEl);
		
		$elTypeList = $this->ElementTypeList();
		
		$elTypeBase = $elTypeList->Get(0);
		$dElType = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $elementid, $elTypeBase);
		
		$rows = CatalogDbQuery::ElementFotoList($this->db, $this->pfx, $elementid);
		$fotos = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($fotos, $row['f']);
		}
		
		$detail = new CatalogElementDetail($dbEl, $dElType, $fotos);
		
		$element->detail = $detail;
		
		return $element;
	}
	
	public function ElementToAJAX($elementid){
		$element = $this->Element($elementid);
		if (empty($element)){ return null; }
		
		$ret = new stdClass();
		$ret->element = $element->ToAJAX($this);
		
		return $ret;
	}
	
	public function ElementSave($elementid, $sd){
		if (!$this->IsAdminRole()){ return null; }
		
		$elementid = intval($elementid);
		
		if ($elementid == 0){ // добавление нового
			return null;
		}else{ // сохранение текущего
			
		}
		
		// обновление фоток
		CatalogDbQuery::ElementFotoUpdate($this->db, $this->pfx, $elementid, $sd->fotos);
		
		return $this->ElementToAJAX($elementid);
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
	
}

?>