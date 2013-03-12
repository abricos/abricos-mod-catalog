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
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->pid	= $this->parentid;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
		$ret->ecnt	= $this->elementCount;
		
		$ret->dtl	= null;
		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX();
		}
		
		if ($this->childs->Count()>0){
			$ret->childs = $this->childs->ToAJAX();
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
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->dsc	= $this->descript;
		$ret->mtl	= $this->metaTitle;
		$ret->mks	= $this->metaKeys;
		$ret->mdsc	= $this->metaDescript;
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
	
	public function ToAJAX(){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX());
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
		$this->title	= $d['tl']; 
		$this->name		= $d['nm'];

		$this->options = new CatalogElementTypeOptionList();
		
		$this->tableName = "element";
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
	
		if ($this->options->Count()>0){
			$ret->options = $this->options->ToAJAX();
		}
		return $ret;
	}
}


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

	public function ToAJAX(){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX());
		}
		return $ret;
	}
}

class CatalogElementTypeOption {
	public $id;
	public $elTypeId;
	public $type;
	public $title;
	public $name;
	
	public function __construct($d){
		$this->id		= intval($d['id']);
		$this->elTypeId = intval($d['eltpid']);
		$this->type		= intval($d['tp']);
		$this->title	= $d['tl'];
		$this->name		= $d['nm'];
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->eltpid	= $this->elTypeId;
		$ret->tp		= $this->tp;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
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

	public function ToAJAX(){
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX());
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
		$this->elTypeId = intval($d['eltpid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->catid		= $this->catalogid;
		$ret->eltpid	= $this->elTypeId;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		
		$ret->dtl	= null;
		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX();
		}
		
		return $ret;
	}	
}


/**
 * Подробная информация по элементу
 */
class CatalogElementDetail {

	public $optionsBase = array();
	public $optionsExt = array();
	public $images = array();

	public function __construct($optionsBase){
		$this->optionsBase = $optionsBase;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->imgs	= $this->images;
		$ret->optb = $this->optionsBase;
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

	public function ToAJAX(){
		$list = array();
		$count = $this->Count();
		for ($i=0; $i<$count; $i++){
			array_push($list, $this->GetByIndex($i)->ToAJAX());
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
	
	public function IsAdminole(){ return false; }
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
		$ret->catalogs = $list->ToAJAX();
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
		$ret->catalog = $cat->ToAJAX();
		
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
		$ret->elements = $list->ToAJAX();
		return $ret;
	}
	
	public function Element($elementid){
		if (!$this->IsViewRole()){ return false; }
		
		$d = CatalogDbQuery::Element($this->db, $this->pfx, $elementid);
		if (empty($d)){ return null; }
		
		
		$element = new CatalogElement($d);
		
		
		$elTypeList = $this->ElementTypeList();
		
		$elTypeBase = $elTypeList->Get(0);
		$d = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $elementid, $elTypeBase);
		$detail = new CatalogElementDetail($d);
		
		// $elType = $elTypeList->Get($element->elTypeId);
		$element->detail = $detail;
		
		return $element;
	}
	
	public function ElementToAJAX($elementid){
		$element = $this->Element($elementid);
		if (empty($element)){ return null; }
		
		$ret = new stdClass();
		$ret->element = $element->ToAJAX();
		
		return $ret;
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
			
			$option = new CatalogElementTypeOption($d);
			if (empty($curType) || $curType->id != $option->elTypeId){
				$curType = $list->Get($option->elTypeId);
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
		$ret->eltypes = $list->ToAJAX();
		return $ret;
	}
	
	public function FotoAddToBuffer($fhash){
		if (!$this->IsWriteRole()){ return false; }
		
		CatalogDbQuery::FotoAddToBuffer($this->db, $this->pfx, $fhash);
	}
	
}

?>