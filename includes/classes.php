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
class CatalogInfo {
	
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
	
	public function __construct($d){
		$this->id		= intval($d['id']);
		$this->parentid	= intval($d['pid']); 
		$this->title	= $d['tl']; 
		$this->name		= $d['nm'];
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
		
		if ($this->childs->Count()>0){
			$ret->childs = $this->childs->ToAJAX();
		}
		return $ret;
	}
}

class CatalogList {
	private $_list = array();
	private $_map = array();
	
	public function __construct(){
		$this->_list = array();
	}

	public function Add(CatalogInfo $item){
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
	 * @return CatalogInfo
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}
	
	/**
	 * Получить раздел каталога по идентификатору
	 * @param integer $id
	 * @return CatalogInfo
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
	}
	
	/**
	 * Поиск элемента по списку, включая поиск по дочерним элементам
	 * @param integer $id
	 * @return CatalogInfo
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
	
	/**
	 * @var CatalogElementTypeOptionList
	 */
	public $options;
	
	public function __construct($d = array()){
		$this->id		= intval($d['id']);
		$this->title	= $d['tl']; 
		$this->name		= $d['nm'];

		$this->options = new CatalogElementTypeOptionList();
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

class CatalogElementInfo {
	public $id = 0;
	public $catelogid = 0;
	
	public $title;
	public $name;
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
			array_push($list, new CatalogInfo($d));
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
	
}

?>