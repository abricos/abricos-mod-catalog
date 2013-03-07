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
	
	/**
	 * @var CatalogList
	 */
	public $childs;
	
	public function __construct($d){
		$this->id		= intval($d['id']);
		$this->parentid	= intval($d['pid']); 
		$this->title	= $d['tl']; 
		$this->name		= $d['nm'];
		 
		$this->childs = new CatalogList();
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id	= $this->id;
		$ret->pid	= $this->parentid;
		$ret->tl	= $this->title;
		$ret->nm	= $this->name;
		// $ret->childs	= 
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
		switch($d->do){
			case "cataloglist":
				return $this->CatalogListToAJAX();
		}
		return null;
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
			
			if ($cat->parentid == 0){
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
	
}

?>