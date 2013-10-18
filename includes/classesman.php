<?php 
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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
	
	/**
	 * Идентификатор текущего пользователя
	 * @var integer
	 */
	private $userid;
	
	public $CatalogClass			= Catalog;
	public $CatalogListClass		= CatalogList;
	
	public $CatalogElementClass		= CatalogElement;
	public $CatalogElementListClass = CatalogElementList;

	/**
	 * Разрешить изменять имя элемента
	 * @var boolean
	 */
	public $cfgElementNameChange = false;
	
	/**
	 * Имя элемента является уникальным
	 * 
	 * @var boolean
	 */
	public $cfgElementNameUnique = false;
	
	/**
	 * Отключить создание базовых элементов
	 * @var boolean
	 */
	public $cfgElementCreateBaseTypeDisable = false;
	
	/**
	 * Контроль версии элементов
	 * 
	 * По умолчанию - отключен
	 * @var boolean
	 */
	public $cfgVersionControl = false;
	
	public function __construct($dbPrefix){
		$this->db = Abricos::$db;
		$this->pfx = $this->db->prefix."ctg_".$dbPrefix."_";
		$this->userid = Abricos::$user->id;
	}
	
	/**
	 * Роль администратора
	 * 
	 * Полный доступ
	 */
	public function IsAdminRole(){ return false; }

	/**
	 * Роль модератора
	 * 
	 * Может: 
	 * редактировать каталог, его элементы, 
	 * подверждать новые элементы созданные оператором
	 * 
	 * Не может:
	 * редактировать опции элемента
	 */
	public function IsModeratorRole(){ return false; }
	
	/**
	 * Роль оператора
	 * 
	 * Может создавать элементы каталога, но они должны 
	 * пройти подверждение модератором
	 */
	public function IsOperatorRole(){ return false; }
	
	/**
	 * Пользователь имеет только роль оператора
	 */
	public function IsOperatorOnlyRole(){
		if (!$this->IsOperatorRole()){ return false; }
		
		return !$this->IsModeratorRole() && !$this->IsAdminRole();
	}
	
	/**
	 * Роль авторизованного пользователя
	 */
	public function IsWriteRole(){ return false; }
	
	/**
	 * Роль на просмотр
	 * @return boolean
	 */
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
			case "elementidbyname":
				return $this->ElementIdByNameToAJAX($d->elname);
			case "elementsave":
				return $this->ElementSaveToAJAX($d->elementid, $d->savedata);
			case "elementmoder":
				return $this->ElementModerToAJAX($d->elementid);
			case "elementremove":
				return $this->ElementRemove($d->elementid);
			case "elementtypesave":
				return $this->ElementTypeSaveToAJAX($d->eltypeid, $d->savedata);
			case "elementtyperemove":
				return $this->ElementTypeRemoveToAJAX($d->eltypeid);
			case "elementoptionsave":
				return $this->ElementOptionSaveToAJAX($d->optionid, $d->savedata);
			case "elementoptionremove":
				return $this->ElementOptionRemoveToAJAX($d->eltypeid, $d->optionid);
			case "elementtypelist":
				return $this->ElementTypeList();
			case "optiontablevaluesave":
				return $this->OptionTableValueSaveToAJAX($d->eltypeid, $d->optionid, $d->valueid, $d->value);
			case "optiontablevalueremove":
				return $this->OptionTableValueRemove($d->eltypeid, $d->optionid, $d->valueid);
		}
		return null;
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){
				$ids1[$row[$fnids1]] = $row[$fnids1];
			}
			if (is_array($ids2)){
				$ids2[$row[$fnids2]] = $row[$fnids2];
			}
			if (is_array($ids3)){
				$ids3[$row[$fnids3]] = $row[$fnids3];
			}
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
	public function ParamToObject($o){
		if (is_array($o)){
			$ret = new stdClass();
			foreach($o as $key => $value){
				$ret->$key = $value;
			}
			return $ret;
		}else if (!is_object($o)){
			return new stdClass();
		}
		return $o;
	}
	
	public function CatalogInitDataToAJAX(){
		if (!$this->IsViewRole()){ return false; }
		
		$ret = new stdClass();
		
		$ajaxCatalogs = $this->CatalogListToAJAX();
		$ret->catalogs = $ajaxCatalogs->catalogs;
		
		$ajaxElTypes = $this->ElementTypeListToAJAX();
		$ret->eltypes = $ajaxElTypes->eltypes;
		
		$ajaxOptGroups = $this->ElementOptionGroupListToAJAX();
		$ret->eloptgroups = $ajaxOptGroups->eloptgroups;
		
		return $ret;
	}
	
	private $_cacheCatalogList;
	/**
	 * Древовидный список категорий каталога
	 * @param boolean $clearCache
	 * @return CatalogList
	 */
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
	
	private function CatalogListLineFill(CatalogList $catListLine, CatalogList $catList){
		for ($i=0;$i<$catList->Count();$i++){
			$cat = $catList->GetByIndex($i);
			$catListLine->Add($cat, true);
			$this->CatalogListLineFill($catListLine, $cat->childs);
		}
	}
	
	private $_cacheCatalogListLine;
	
	/**
	 * Линейный список категорий каталога
	 * 
	 * @return CatalogList
	 */
	public function CatalogListLine($clearCache = false){
		if ($clearCache){
			$this->_cacheCatalogListLine = null;
		}
		if (!empty($this->_cacheCatalogListLine)){
			return $this->_cacheCatalogListLine;
		}
		$catListLine = new CatalogList(null);
		
		$catList = $this->CatalogList();
		
		$this->CatalogListLineFill($catListLine, $catList);
		
		$this->_cacheCatalogListLine = $catListLine;
		
		return $catListLine;
	}
	
	/**
	 * Список категорий каталога по набору идентификаторов
	 * 
	 * @param mixed $catids
	 * @return CatalogList
	 */
	public function CatalogListByIds($catids){
		if (is_string($catids)){
			$catids = explode(",", $catids);
		}
		$retCatList = new CatalogList(null);
		$catList = $this->CatalogList();
		for ($i=0;$i<count($catids);$i++){
			$cat = $catList->Find($catids[$i]);
			if (!empty($cat)){
				$retCatList->Add($cat, true);
			}
		}
		return $retCatList;
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
		
		$d = $this->ParamToObject($d);
	
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
		
		$rows = CatalogDbQuery::ElementList($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $cfg);
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
		$list->total = CatalogDbQuery::ElementListCount($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $cfg);
		
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
	 * Заполнить элемент деталями
	 * 
	 * @param CatalogElement $element
	 */
	public function ElementDetailFill($element, $dbEl){
		$elTypeList = $this->ElementTypeList();
		
		$tpBase = $elTypeList->Get(0);
		$dbOptionsBase = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $element->id, $tpBase);
		
		$dbOptionsPers = array();
		if ($element->elTypeId > 0){
			$tpPers = $elTypeList->Get($element->elTypeId);
			if (!empty($tpPers)){
				$dbOptionsPers = CatalogDbQuery::ElementDetail($this->db, $this->pfx, $element->id, $tpPers);
			}
		}
		
		$fotos = array();
		$fotoList = $this->ElementFotoList($element);
		for ($i=0; $i<$fotoList->Count(); $i++){
			$foto = $fotoList->GetByIndex($i);
			array_push($fotos, $foto->filehash);
		}
		$detail = new CatalogElementDetail($dbEl, $dbOptionsBase, $dbOptionsPers, $fotos, $fotoList);
		
		$element->detail = $detail;
	}
	
	private $_cacheElementByName = null;
	
	
	/**
	 * Список фотографий элемента|элементов
	 * 
	 * @param CatalogElementList|CataloElement $data
	 * @return CatalogFotoList
	 */
	public function ElementFotoList($data){
		if (!$this->IsViewRole()){ return null; }
		
		$elids = array();
		if ($data instanceof CatalogElementList){
			for ($i=0;$i<$data->Count();$i++){
				$el = $data->GetByIndex($i);
				array_push($elids, $el->id);
			}
		}else if ($data instanceof CatalogElement){
			array_push($elids, $data->id);
		}

		$fotoList = new CatalogFotoList();
		
		$rows = CatalogDbQuery::ElementFotoList($this->db, $this->pfx, $elids);
		while (($d = $this->db->fetch_array($rows))){
			$fotoList->Add(new CatalogFoto($d));
		}
		return $fotoList;
	}
	
	/**
	 * Получить элемент по имени
	 * 
	 * @param string $name имя элемента
	 * @return CatalogElement
	 */
	public function ElementByName($name, $clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		if ($clearCache || !is_array($this->_cacheElementByName)){
			$this->_cacheElementByName = array();
		}
		if (!empty($this->_cacheElementByName[$name])){
			return $this->_cacheElementByName[$name];
		}

		$dbEl = CatalogDbQuery::ElementByName($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $name);
		if (empty($dbEl)){ return null; }
		
		$element = new $this->CatalogElementClass($dbEl);
		
		$this->ElementDetailFill($element, $dbEl);
		
		$this->_cacheElementByName[$name] = $element;
		
		return $element;
	}
	
	/**
	 * Список измнений элемента
	 * 
	 * @param string $name
	 * @param string $sExtOptions дополнительные опции элементов базового типа
	 * @return CatalogElementChangeLog
	 */
	public function ElementChangeLogListByName($name, $sExtOptions){
		if (!$this->IsViewRole()){ return null; }
		
		$elTypeList = $this->ElementTypeList();
		$elTypeBase = $elTypeList->Get(0);
		$optionList = new CatalogElementOptionList();
		$aExtOptions = explode(",", $sExtOptions);
		foreach ($aExtOptions as $optName){
			$option = $elTypeBase->options->GetByName($optName);
			if (empty($option)){ continue; }
			$optionList->Add($option);
		}
		
		$list = new CatalogElementChangeLogList();
		$rows = CatalogDbQuery::ElementChangeLogListByName($this->db, $this->pfx, $name, $optionList);
		while (($d = $this->db->fetch_array($rows))){
			$chLog = new CatalogElementChangeLog($d);
			
			for ($i=0;$i<$optionList->Count();$i++){
				$option = $optionList->GetByIndex($i);
				$chLog->ext[$option->name] = $d['fld_'.$option->name];
			}
			$list->Add($chLog);
		}
		
		return $list;
	}
	
	/**
	 * Список изменений по всем элементам
	 * 
	 * @param string $sExtOptions дополнительные опции элементов базового типа
	 */
	public function ElementChangeLogList($sExtOptions){
		if (!$this->IsViewRole()){ return null; }
		
		$elTypeList = $this->ElementTypeList();
		$elTypeBase = $elTypeList->Get(0);
		$optionList = new CatalogElementOptionList();
		$aExtOptions = explode(",", $sExtOptions);
		foreach ($aExtOptions as $optName){
			$option = $elTypeBase->options->GetByName($optName);
			if (empty($option)){ continue; }
			$optionList->Add($option);
		}
		
		$list = new CatalogElementChangeLogList();
		$rows = CatalogDbQuery::ElementChangeLogList($this->db, $this->pfx, $optionList);
		while (($d = $this->db->fetch_array($rows))){
			$chLog = new CatalogElementChangeLog($d);
				
			for ($i=0;$i<$optionList->Count();$i++){
				$option = $optionList->GetByIndex($i);
				$chLog->ext[$option->name] = $d['fld_'.$option->name];
			}
			$list->Add($chLog);
		}
		
		return $list;
	}
	
	private $_cacheElementById = null;
	
	/**
	 * Получить элемент по идентификатор
	 * 
	 * @param integer $elid идентифиатор элемента
	 * @return CatalogElement
	 */
	public function Element($elid, $clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		if ($clearCache || !is_array($this->_cacheElementById)){
			$this->_cacheElementById = array();
		}
		if (!empty($this->_cacheElementById[$elid])){
			return $this->_cacheElementById[$elid];
		}
		
		$dbEl = CatalogDbQuery::Element($this->db, $this->pfx, $elid);
		if (empty($dbEl)){ return null; }
		
		$element = new $this->CatalogElementClass($dbEl);
		
		$this->ElementDetailFill($element, $dbEl);
		
		$this->_cacheElementById[$elid] = $element;
		
		return $element;
	}
	
	public function ElementToAJAX($elid, $clearCache = false){
		$element = $this->Element($elid, $clearCache);
		if (empty($element)){ return null; }
		
		$ret = new stdClass();
		$ret->element = $element->ToAJAX($this);
		
		return $ret;
	}
	
	public function ElementIdByNameToAJAX($elname){
		$element = $this->ElementByName($elname);
		if (empty($element)){ return null; }

		$ret = new stdClass();
		$ret->elementid	= $element->id;
		$ret->userid	= $element->userid;
		$ret->ismoder	= $element->isModer ? 1 : 0;
		
		return $ret;
	}
	
	/**
	 * Создать/сохранение элемент каталога
	 * 
	 * Если $elid=0, то создать новый элемент
	 * 
	 * В качестве параметра $d необходимо передать именованный массив
	 * или объект с полями:
	 * catid - идентификатор каталога,
	 * tpid - идентификатор типа элемента,
	 * tl - название элемента,
	 * nm - имя элемента,
	 * mtl - МЕТА-тег TITLE,
	 * mks - МЕТА-тег KEYS,
	 * mdsc - МЕТА-тег DESCRIPTION,
	 * ord - сортировка,
	 * values - опции элемента в виде именованного массива или объекта в качестве
	 * полей которого используется идентификатор типа элемента,
	 * fotos - массив идентификатор фотографий менеджера файлов
	 * 
	 * @param integer $elid идентификатор элемента
	 * @param array|object $d
	 */
	public function ElementSave($elid, $d){
		if (!$this->IsOperatorRole()){ return null; }
		
		$d = $this->ParamToObject($d);
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		$elid		= intval($elid);
		$d->catid	= intval($d->catid);
		$d->tpid	= intval($d->tpid);
		$d->tl		= $utmf->Parser($d->tl);
		
		if (!$this->cfgElementNameChange || empty($d->nm)){
			$d->nm	= translateruen($d->tl);
		}else{
			$d->nm	= translateruen($d->nm);
		}
		
		$d->mtl		= $utmf->Parser($d->mtl);
		$d->mks		= $utmf->Parser($d->mks);
		$d->mdsc	= $utmf->Parser($d->mdsc);
		
		$d->ord		= intval($d->ord);
		
		$utmChLog = Abricos::TextParser(true);
		
		// TODO: временное решение в лоб
		$utmfLog = Abricos::TextParser(true);
		$utmfLog->jevix->cfgSetAutoBrMode(true);
		$d->chlg = $utmfLog->Parser($d->chlg);
		$d->chlg = str_replace("<br/>",'', $d->chlg);
		
		/*
		$d->chlg = str_replace("\r\n",'[--!rn!--]', $d->chlg);
		$d->chlg = str_replace("\n",'[--!n!--]', $d->chlg);
		$d->chlg = $utmf->Parser($d->chlg);
		$d->chlg = str_replace('[--!rn!--]', "\r\n", $d->chlg);
		$d->chlg = str_replace('[--!n!--]', "\n", $d->chlg);
		/**/
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($d->tpid);
		if (empty($elType)){ return null; }
		
		if ($elid == 0 && $d->tpid == 0 && $this->cfgElementCreateBaseTypeDisable){
			// создание базовых элементов отключено
			return null;
		}
		
		$isNewElementByOperator = false;

		if ($elid == 0){ // добавление нового элемента
			
			$d->v = 1;
			$d->pelid = 0;
			
			if ($this->cfgVersionControl){ // проверка существующей версии
				$curEl = $this->ElementByName($d->nm); // элемент текущей версии
				if (!empty($curEl)){

					if ($this->IsOperatorOnlyRole() && ($curEl->userid != $this->userid || $curEl->isModer)){
						// оператору можно добавлять новые версии только своим элементам
						// и если эти элементы уже прошли проверку модератором
						return null;
					}
					
					$d->pelid = $curEl->id; // новому элементу ссылку на старый
					$d->v = $curEl->detail->version+1; // увеличить номер версии нового элемента

					if ($this->IsAdminRole()){ // Оператор может добавить только на модерацию
						// текущую версию переместить в архив
						CatalogDbQuery::ElementToArhive($this->db, $this->pfx, $curEl->id);
					}
				}
			}
			
			$elid = CatalogDbQuery::ElementAppend($this->db, $this->pfx, $this->userid, $this->IsOperatorOnlyRole(), $d);
			if (empty($elid)){ return null; }
			
			if ($this->IsOperatorOnlyRole()){
				$isNewElementByOperator = true;
			}
			
		}else{ // сохранение текущего элемента
			
			$el = $this->Element($elid);
			if (empty($el)){ return null; }
			
			if ($this->IsOperatorOnlyRole() && ($curEl->userid != $this->userid || !$curEl->isModer)){
				// оператору не принадлежит этот элемент или элемент уже прошел модерацию
				return null;
			}
			
			if ($this->cfgElementNameUnique){
				// имя элемента уникальное, поэтому изменять его нельзя
				$d->nm = $el->name;
			}
			
			CatalogDbQuery::ElementUpdate($this->db, $this->pfx, $elid, $d);
		}
		
		if (!empty($d->values)){
			foreach($d->values as $tpid => $opts){
				$elType = $elTypeList->Get($tpid);
				CatalogDbQuery::ElementDetailUpdate($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $elid, $elType, $opts);
			}
		}
		
		// обновление фоток
		CatalogDbQuery::ElementFotoUpdate($this->db, $this->pfx, $elid, $d->fotos);
		
		$this->OptionFileBufferClear();
		$this->FotoBufferClear();
		
		if ($isNewElementByOperator){
			$this->OnElementAppendByOperator($elid);
		}

		return $elid;
	}
	
	/**
	 * Событие на доабвление нового элемента оператором
	 * @param integer $elementid
	 */
	protected function OnElementAppendByOperator($elementid){ }
	
	public function ElementSaveToAJAX($elid, $d){
		$elid = $this->ElementSave($elid, $d);
		
		if (empty($elid)){ return null; }
		
		return $this->ElementToAJAX($elid, true);
	}
	
	public function ElementModer($elid){
		if (!$this->IsAdminRole()){ return null; }
		
		$el = $this->Element($elid);
		
		if (empty($el) || !$el->isModer){ return null; }
		
		if ($this->cfgVersionControl){
			CatalogDbQuery::ElementToArhive($this->db, $this->pfx, $el->detail->pElementId);
			CatalogDbQuery::ElementModer($this->db, $this->pfx, $elid);
		}
		
		$this->OnElementModer($elid);
		
		return $elid;
	}
	
	/**
	 * Событие на подверждение модератором нового элемента
	 * @param integer $elementid
	 */
	protected function OnElementModer($elementid){ }
	
	public function ElementModerToAJAX($elid){
		$elid = $this->ElementModer($elid);

		if (empty($elid)){ return null; }
		
		return $this->ElementToAJAX($elid, true);
	}
	
	public function ElementRemove($elid){
		if (!$this->IsOperatorRole()){ return null; }
		
		$el = $this->Element($elid);
		
		if ($this->IsOperatorOnlyRole()){
			
			if ($el->userid != $this->userid || !$el->isModer){
				// оператор может удалять только свои записи
				// не прошедшии модерацию
				return null;
			}
		}
		
		CatalogDbQuery::ElementRemove($this->db, $this->pfx, $elid);
		
		return true;
	}
	
	private function TableCheck($tname){
		$rows = CatalogDbQuery::TableList($this->db);
		
		while (($row = $this->db->fetch_array($rows, Ab_Database::DBARRAY_NUM))){
			if ($row[0] == $tname){
				return true;
			}
		}
		return false;
	}
	
	public function ElementTypeTableName($name){
		if (empty($name)){
			return $this->pfx."element";
		}
		return $this->pfx."eltbl_".$name;
	}
	
	/**
	 * Создать/сохранить тип элемента
	 * 
	 * Если $elTypeId=0, то создать тип элемента
	 * 
	 * В качестве параметра $d необходимо передать именованный массив 
	 * или объект с полями:
	 * nm - уникальное имя латиницей,
	 * tl - название,
	 * dsc - описание
	 * 
	 * @param integer $elTypeId
	 * @param array|object $d данные типа элемента
	 */
	public function ElementTypeSave($elTypeId, $d){
		if (!$this->IsAdminRole()){ return null; }
		
		$d = $this->ParamToObject($d);
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		$eltTypeId	= intval($elTypeId);
		$d->tl		= $utmf->Parser($d->tl);
		if (empty($d->tls)){
			$d->tls = $d->tl;
		}
		$d->tls		= $utmf->Parser($d->tls);
		$d->nm		= strtolower(translateruen($d->nm));
		$d->dsc		= $utm->Parser($d->dsc);
		
		$tableName = $this->ElementTypeTableName($d->nm);
		
		if (empty($d->tl) || empty($d->nm)){ return null; }
		
		$typeList = $this->ElementTypeList();

		$checkElType = $typeList->GetByName($d->nm);
		
		if ($eltTypeId == 0){
			if (!empty($checkElType)){ return null; } // нельзя создать типы с одинаковыми именами

			if ($this->TableCheck($tableName)){
				return null; // уже есть такая таблица
			}

			CatalogDbQuery::ElementTypeTableCreate($this->db, $tableName);
			$elTypeId = CatalogDbQuery::ElementTypeAppend($this->db, $this->pfx, $d);
		}else{
			
			$checkElType = $typeList->GetByName($d->nm);
			if (!$this->TableCheck($tableName)){
				return null; // такого быть не должно. hacker?
			}
			
			if ($checkElType->name != $d->nm){ // попытка сменить имя таблицы
				if ($this->TableCheck($tableName)){
					return null; // уже есть такая таблица
				}
				
				$oldTableName = $this->ElementTypeTableName($checkElType->name);
				
				CatalogDbQuery::ElementTypeTableChange($this->db, $oldTableName, $tableName);
			}
			CatalogDbQuery::ElementTypeUpdate($this->db, $this->pfx, $elTypeId, $d);
		}
		
		return $elTypeId;
	}
	
	public function ElementTypeSaveToAJAX($elTypeId, $d){
		$this->ElementTypeSave($elTypeId, $d);
		
		$this->_cacheElementTypeList = null;
		
		return $this->ElementTypeListToAJAX();
	}
	
	public function ElementTypeRemove($elTypeId){
		if (!$this->IsAdminRole()){ return null; }
		
		if ($elTypeId == 0){ return null; } // нельзя удалить базовый тип
		
		$typeList = $this->ElementTypeList();
		$elType = $typeList->Get($elTypeId);
		
		$cnt = $elType->options->Count();
		for ($i=0; $i<$cnt; $i++){
			$option = $elType->options->GetByIndex($i);
			$this->ElementOptionRemove($elTypeId, $option->id);
		}
		
		$tableName = $this->ElementTypeTableName($elType->name);
		
		CatalogDbQuery::ElementTypeTableRemove($this->db, $tableName);
		CatalogDbQuery::ElementTypeRemove($this->db, $this->pfx, $elTypeId);
	}
	
	public function ElementTypeRemoveToAJAX($elTypeId){
		$this->ElementTypeRemove($elTypeId);
		return $this->ElementTypeListToAJAX(true);
	}
	
	private $_cacheElementTypeList;
	/**
	 * @return CatalogElementTypeList
	 */
	public function ElementTypeList($clearCache = false){
		if (!$this->IsViewRole()){ return false; }
		
		if ($clearCache){
			$this->_cacheElementTypeList = null;
		}
		
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
		
		$rows = CatalogDbQuery::ElementOptionList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){

			if (empty($curType) || $curType->id != $d['tpid']){
				$curType = $list->Get($d['tpid']);
			}

			if ($d['tp'] == Catalog::TP_TABLE){
				
				$option = new CatalogElementOptionTable($d);
				
				$rtbs = CatalogDbQuery::OptionTableValueList($this->db, $this->pfx, $curType->name, $option->name);
				$option->values = $this->ToArrayId($rtbs);
			}else{
				$option = new CatalogElementOption($d);
			}
			
			if (empty($curType)){ 
				continue; // гипотетически такое невозможно
			}
			$curType->options->Add($option);
		}
		return $list;
	}
	
	public function ElementTypeListToAJAX($clearCache = false){
		$list = $this->ElementTypeList($clearCache);
		
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->eltypes = $list->ToAJAX($this);
		return $ret;
	}
	
	private $_cacheElementOptGroupList;
	
	/**
	 * @param boolean $clearCache
	 * @return CatalogElementOptionGroup
	 */
	public function ElementOptionGroupList($clearCache = false){
		if (!$this->IsViewRole()){ return false; }
		
		if ($clearCache){
			$this->_cacheElementOptGroupList = null;
		}
		
		if (!empty($this->_cacheElementOptGroupList)){
			return _cacheElementOptGroupList;
		}
		
		$list = new CatalogElementOptionGroupList();
		$rows = CatalogDbQuery::ElementOptionGroupList($this->db, $this->pfx);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new CatalogElementOptionGroup($d));
		}
		return $list;
	}
	
	public function ElementOptionGroupListToAJAX($clearCache = false){
		$list = $this->ElementOptionGroupList($clearCache);
	
		if (empty($list)){ return null; }
	
		$ret = new stdClass();
		$ret->eloptgroups = $list->ToAJAX($this);
		return $ret;
	}
	
	/**
	 * В процессе добавления фото к элементу/каталогу идентификатор файла 
	 * помещается в буфер. Если в течении времени, фото так и не было прикреплено 
	 * к чему либо, он удаляется 
	 * @param string $fhash
	 */
	public function FotoAddToBuffer($fhash){
		if (!$this->IsAdminRole()){ return false; }
		
		CatalogDbQuery::FotoAddToBuffer($this->db, $this->pfx, $fhash);
		
		$this->FotoBufferClear();
	}
	
	/**
	 * Удалить временные фото из буфера
	 */
	public function FotoBufferClear(){
		$mod = Abricos::GetModule('filemanager');
		if (empty($mod)){ return; }
		$mod->GetManager();
		$fm = FileManager::$instance;
		$fm->RolesDisable();
		
		$rows = CatalogDbQuery::FotoFreeFromBufferList($this->db, $this->pfx);
		while (($row = $this->db->fetch_array($rows))){
			$fm->FileRemove($row['fh']);
		}
		$fm->RolesEnable();
		
		CatalogDbQuery::FotoFreeListClear($this->db, $this->pfx);
	}
	
	/**
	 * Есть ли возможность выгрузки файла для запись в значении опции элемента
	 *  
	 * @param integer $optionid
	 */
	public function OptionFileUploadCheck($optionid){
		if (!$this->IsAdminRole()){ return false; }
		
		$elTypeList = $this->ElementTypeList();
		$option = $elTypeList->GetOptionById($optionid);
		
		if (empty($option) || $option->type != Catalog::TP_FILES){
			return null;
		}
		
		$aFTypes = array();
		$aOPrms = explode(";", $option->param);
		for ($i=0;$i<count($aOPrms);$i++){
			$aExp = explode("=", $aOPrms[$i]);
			switch (strtolower(trim($aExp[0]))){
			case 'ftypes':
	
				$aft = explode(",", $aExp[1]);
				for ($ii=0;$ii<count($aft);$ii++){
					$af = explode(":", $aft[$ii]);
					$aFTypes[$af[0]] = array(
						'maxsize' => $af[1]
					);
				}
				break;
			}
		}
		$ret = new stdClass();
		$ret->fTypes = $aFTypes;
		$ret->option = $option;
		return $ret;
	}
	
	/**
	 * Поместить идентификатор файла в буфер
	 * 
	 * Метод вызывает content_uploadoptfiles.php
	 * 
	 * @param CatalogElementOption $option
	 * @param string $fhash идентификатор файла
	 * @param string $fname имя файла
	 */
	public function OptionFileAddToBuffer($option, $fhash, $fname){
		if (!$this->IsAdminRole()){ return false; }
		
		CatalogDbQuery::OptionFileAddToBuffer($this->db, $this->pfx, $this->userid, $option->id, $fhash, $fname);
		$this->OptionFileBufferClear();
	}
	
	public function OptionFileBufferClear(){
		$mod = Abricos::GetModule('filemanager');
		if (empty($mod)){ return; }
		$mod->GetManager();
		$fm = FileManager::$instance;
		$fm->RolesDisable();
	
		$rows = CatalogDbQuery::OptionFileFreeFromBufferList($this->db, $this->pfx);
		while (($row = $this->db->fetch_array($rows))){
			$fm->FileRemove($row['fh']);
		}
		$fm->RolesEnable();
		CatalogDbQuery::OptionFileFreeListClear($this->db, $this->pfx);
	}
	
	
	private function ElementOptionDataFix($d){
		switch($d->tp){
			case Catalog::TP_BOOLEAN:
				$d->sz = 1;
				break;
			case Catalog::TP_NUMBER:
				$d->sz = min(max(intval($d->sz), 1), 10);
				break;
			case Catalog::TP_STRING:
				$d->sz = min(max(intval($d->sz), 2), 255);
				break;
			case Catalog::TP_DOUBLE:
				$asz = explode(",", $d->sz);
				$asz[0] = min(max(intval($asz[0]), 3), 10);
				$asz[1] = min(max(intval($asz[1]), 0), 5);
				$d->sz = $asz[0].",".$asz[1];
				break;
			case Catalog::TP_TEXT:
			case Catalog::TP_ELDEPENDS:
			case Catalog::TP_ELDEPENDSNAME:
			case Catalog::TP_FILES:
				$d->sz = 0;
				break;
		}
		return $d;
	}
	
	/**
	 * Сохранение опции элемента
	 * 
	 * @param integer $optionid идентификатор опции, если 0, то новая опция
	 * @param mixed $d
	 */
	public function ElementOptionSave($optionid, $d){
		if (!$this->IsAdminRole()){ return null; }
		
		$d = $this->ParamToObject($d);

		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		$optionid = intval($optionid);
		$elTypeId = intval($d->tpid);
		
		$d->tl = $utmf->Parser($d->tl);
		$d->nm = strtolower(translateruen($d->nm));
		$d->tp = intval($d->tp);
		$d->dsc = $utm->Parser($d->dsc);

		if (empty($d->tl) || empty($d->nm)){ return null; }
		
		$typeList = $this->ElementTypeList();
		$elType = $typeList->Get($elTypeId);
		
		if (empty($elType)){ return null; }
		
		$d = $this->ElementOptionDataFix($d);
		
		$tableName = $this->ElementTypeTableName($elType->name);
		
		if ($optionid == 0){
			$checkOption = $elType->options->GetByName($d->nm);
			if (!empty($checkOption)){ // такая опция уже есть
				return null; // нельзя добавить опции с одинаковым именем
			}
			$optionid = CatalogDbQuery::ElementOptionAppend($this->db, $this->pfx, $d);
			CatalogDbQuery::ElementOptionFieldCreate($this->db, $this->pfx, $elType, $tableName, $d);
		}else{
			$checkOption = $elType->options->Get($optionid);
			if (empty($checkOption)){ return null; }
				
			if ($checkOption->name != $d->nm){ // попытка изменить имя
				$newCheckOption = $elType->options->GetByName($d->nm);
				if (!empty($newCheckOption)){ // уже есть опция с таким именем
					return;
				}
				CatalogDbQuery::ElementOptionFieldUpdate($this->db, $this->pfx, $elType, $tableName, $checkOption, $d);
			}
			CatalogDbQuery::ElementOptionUpdate($this->db, $this->pfx, $optionid, $d);
		}
		return $optionid;
	}
	
	public function ElementOptionSaveToAJAX($optionid, $d){
		$optionid = $this->ElementOptionSave($optionid, $d);
		
		if (empty($optionid)){ return null; }
		
		return $this->ElementTypeListToAJAX(true);
	}
	
	public function ElementOptionRemove($elTypeId, $optionid){
		if (!$this->IsAdminRole()){ return null; }
		
		$typeList = $this->ElementTypeList();
		$elType = $typeList->Get($elTypeId);
		
		if (empty($elType)){ return null; }
		
		$option = $elType->options->Get($optionid);
		if (empty($option)){ return null; }
		
		CatalogDbQuery::ElementOptionRemove($this->db, $this->pfx, $optionid);
		
		CatalogDbQuery::ElementOptionFieldRemove($this->db, $this->pfx, $elType, $option);
	}
	
	public function ElementOptionRemoveToAJAX($elTypeId, $optionid){
		$this->ElementOptionRemove($elTypeId, $optionid);
		
		return $this->ElementTypeListToAJAX(true);
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
		
		return $valueid;
	}
	
	public function OptionTableValueSaveToAJAX($eltypeid, $optionid, $valueid, $value){
		$valueid = $this->OptionTableValueSave($eltypeid, $optionid, $valueid, $value);
		
		if (empty($valueid)){ return null; }
		
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($eltypeid);
		$option = $elType->options->Get($optionid);
		
		$rtbs = CatalogDbQuery::OptionTableValueList($this->db, $this->pfx, $elType->name, $option->name);
		$option->values = $this->ToArrayId($rtbs);
		
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
		$option->values = $this->ToArrayId($rtbs);
	
		$ret = new stdClass();
		$ret->values = $option->values;
	
		return $ret;
	}
	
	public function SearchOptionCheck($eFField = '', $eFValue = ''){
		$optValueId = 0;
		
		if (empty($eFField) || empty($eFValue)){
			return 0;
		}
		$elTypeBase = $this->ElementTypeList()->GetByIndex(0);
		$option = $elTypeBase->options->GetByName($eFField);
		if (!empty($option) && $option->type == Catalog::TP_TABLE && !empty($option->values[$eFValue])){
			$optValueId = intval($eFValue);
		}
		return $optValueId;
	}
	
	/**
	 * Получить список возможных вариантов для автозаполнения в поиске
	 * @param string $query
	 */
	public function SearchAutoComplete($query, $eFField = '', $eFValue = ''){
		$ret = array();
		
		if (!$this->IsViewRole()){ return $ret; }
		if (strlen($query) < 2){ return $ret; }
		
		$eFValue = $this->SearchOptionCheck($eFField, $eFValue);
		
		$rows = CatalogDbQuery::SearchAutoComplete($this->db, $this->pfx, $query, $eFField, $eFValue);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row['tl']);
		}
		return $ret;
	}
	
	public function Search($query, $eFField = '', $eFValue = ''){
		$ret = array();
		if (!$this->IsViewRole()){ return $ret; }
		
		$eFValue = $this->SearchOptionCheck($eFField, $eFValue);
		
		$rows = CatalogDbQuery::Search($this->db, $this->pfx, $query, $eFField, $eFValue);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
		}
		return $ret;
	}
	
	/**
	 * Получить список пользователей
	 * 
	 * @param CatalogElementList|CataloElement $data
	 * @return CatalogUserList
	 */
	public function UserList($data){
		$users = new CatalogUserList();
		if (!$this->IsViewRole()){ return $users; }
		
		$uids = array();
		$ucids = array();
		if ($data instanceof CatalogElementList){
			for ($i=0;$i<$data->Count();$i++){
				$el = $data->GetByIndex($i);
				if ($ucids[$el->userid]){ continue; }
				$ucids[$el->userid] = true;
				array_push($uids, $el->userid);
			}
		}else if ($data instanceof CatalogElement){
			array_push($uids, $data->userid);
		}
		
		$rows = CatalogDbQuery::UserList($this->db, $uids);
		while (($d = $this->db->fetch_array($rows))){
			$user = new CatalogUser($d);
			$users->Add($user);
		}
		return $users;
	}
	
	/**
	 * Автор элемента каталога
	 * @param CatalogElement $element
	 * @return CatalogElement
	 */
	public function UserByElement(CatalogElement $element){
		$users = $this->UserList($element);
		return $users->GetByIndex(0);
	}
	
	/**
	 * Получить список файлов 
	 * 
	 * Параметр $data может принимать значение: 
	 * 		CatalogElementList - список элементов,
	 * 		CataloElement - элемент
	 * 
	 * @param CatalogElementList|CataloElement $data
	 * @return CatalogFileList
	 */
	public function ElementOptionFileList($data){
		$files = new CatalogFileList();
		if (!$this->IsViewRole()){ return $files; }
		
		$elids = array();
		$elcids = array();
		if ($data instanceof CatalogElementList){
			for ($i=0;$i<$data->Count();$i++){
				$el = $data->GetByIndex($i);
				if ($elcids[$el->id]){
					continue;
				}
				$elcids[$el->id] = true;
				array_push($elids, $el->id);
			}
		}else if ($data instanceof CatalogElement){
			array_push($elids, $data->id);
		}
		
		$rows = CatalogDbQuery::ElementOptionFileList($this->db, $this->pfx, $elids);
		while (($d = $this->db->fetch_array($rows))){
			$file = new CatalogFile($d);
			$files->Add($file);
		}
		return $files;
	}
	
	
	private $_cacheStatElList = null;
	
	/**
	 * Статистика элементов в каталоге по типам
	 * 
	 * @param boolean $clearCache
	 * @return CatalogStatisticElementList
	 */
	public function StatisticElementList($clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		if ($clearCache){
			$this->_cacheStatElList = null;			
		}
		
		if (!empty($this->_cacheStatElList)){
			return $this->_cacheStatElList;
		}
		
		$list = new CatalogStatisticElementList();
		$rows = CatalogDbQuery::StatisticElementList($this->db, $this->pfx);
		$i = 0;
		while (($d = $this->db->fetch_array($rows))){
			$d['id'] = $i+1;
			$item = new CatalogStatisticElement($d);
			$list->Add($item);
		}
		$this->_cacheStatElList = $list;
		return $list;
	}
	
}

?>