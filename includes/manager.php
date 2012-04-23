<?php
/**
 * @version $Id: manager.php 698 2010-09-06 12:37:21Z roosit $
 * @package Abricos
 * @subpackage Catalog
 * @copyright Copyright (C) 2010 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

/**
 * Менеджер каталога. Запрос основных функций осуществляется через этот класс.
 * 
 * <b>Внимание!</b> Перед тем, как использовать этот менеджер, необходимо 
 * установить префикс управляющего модуля. <br />
 * 
 * Например, для модуля EShop:
 * <pre> 
 *  CatalogQuery::PrefixSet(Abricos::$db, 'eshp');
 * </pre>
 */
class CatalogManager extends Ab_ModuleManager {
	
	/**
	 * Основной класс модуля
	 * 
	 * @var CatalogModule
	 */
	public $module = null;
	
	/**
	 * @var CatalogManager
	 */
	public static $instance = null;
	
	private $_disableRole = false;
	
	public function __construct(CatalogModule $module){
		parent::__construct($module);
		CatalogManager::$instance = $this;
	}
	
	/**
	 * Отключить проверку ролей перед выполением функций.
	 * <b>Внимание!</b> Не отключайте роли без явной необходимости дабы может пострадать безопасность. 
	 */
	public function DisableRole(){
		$this->_disableRole = true;
	}
	
	/**
	 * Проверка на роль администратора текущего пользователя
	 * @return boolean Если true, пользователь имеет роль администратора
	 */
	public function IsAdminRole(){
		if ($this->_disableRole){ return true; }
		return $this->IsRoleEnable(CatalogAction::ADMIN);
	}
	
	/**
	 * Проверка на роль оператора текущего пользователя
	 * @return boolean Если true, пользователь имеет роль оператора
	 */
	public function IsWriteRole(){
		if ($this->_disableRole){ return true; }
		return $this->IsRoleEnable(CatalogAction::WRITE);
	}
	
	/**
	 * Проверка на роль чтения каталога и его элементов текущего пользователя
	 * @return boolean Если true, пользователь имеет доступ на чтение каталога и его элементов
	 */
	public function IsViewRole(){
		if ($this->_disableRole){ return true; }
		return $this->IsRoleEnable(CatalogAction::VIEW);
	}
	
	/**
	 * Обработчик DataSet запросов. Внесение изменений в таблицы.
	 * 
	 * Вызов функции из includes/js_data.php
	 * 
	 * @param string $name запрашиваемая таблица
	 * @param object $rows параметры запроса
	 */
	public function DSProcess($name, $rows){
		$p = $rows->p;
		$db = $this->db;
		
		switch ($name){
			case 'catelements':
				foreach ($rows->r as $r){
					if ($r->f == 'd'){ $this->ElementRemove($r->d->id);
					}else if ($r->f == 'r'){ $this->ElementRestore($r->d->id); }
				}
				break;
			case 'catelement':
				foreach ($rows->r as $r){
					if ($r->f == 'a'){ $this->ElementAppend($r->d);
					}else if ($r->f == 'u'){ $this->ElementUpdate($r->d);
					}
				}
				break;
			case 'linkelements':
				foreach ($rows->r as $r){
					if ($r->f == 'a'){ 
						$this->LinkElementAppend($p->elid, $p->optid, $r->d->elid);
					}else if ($r->f == 'd'){ 
						$this->LinkElementRemove($p->elid, $p->optid, $r->d->elid);
					}
				}
				break;
			case 'catalog':
				foreach ($rows->r as $r){
					if ($r->f == 'a'){ $this->CatalogAppend($r->d);
					}else if ($r->f == 'u'){ $this->CatalogUpdate($r->d);
					}else if ($r->f == 'd'){ $this->CatalogRemove($r->d->id);
					}
				}
				break;
			case 'eltype':
				foreach ($rows->r as $r){
					if ($r->f == 'a'){			$this->ElementTypeAppend($r->d);
					}else if ($r->f == 'u'){	$this->ElementTypeUpdate($r->d);
					}else if ($r->f == 'd'){	$this->ElementTypeRemove($r->d->id);
					}else if ($r->f == 'r'){	$this->ElementTypeRestore($r->d->id);
					}
				}
				break;
			case 'eloption':
				foreach ($rows->r as $r){
					if ($r->f == 'd'){ $this->ElementOptionRemove($r->d->id);
					}else if ($r->f == 'r'){ $this->ElementOptionRestore($r->d->id); }
					
					if ($r->f == 'a'){ $this->ElementOptionAppendObj($r->d); }
					else if ($r->f == 'u'){ CatalogQuery::ElementOptionSave($db, $r->d); }
				}
				break;
		}
	}
	
	/**
	 * Обработчик DataSet запросов. Получение значений таблиц.
	 * 
	 * Вызов функции из includes/js_data.php
	 * 
	 * @param string $name запрашиваемая таблица
	 * @param object $rows параметры запроса
	 */
	public function DSGetData($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'catelement': return $this->Element($p->id);
			case 'catelements': return $this->ElementList($p->catid, 1, 500);
			case 'linkelements': return $this->LinkElementList($p->elid, $p->optid);
			case 'catalog': return $this->CatalogList();
			case 'fotos': return $this->FotoList($p->elid);
			case 'eltype': return $this->ElementTypeList();
			case 'eloption': return $this->ElementOptionList();
			case 'eloptionfld': return $this->ElementOptionFieldTableList($p->eltpnm, $p->fldnm);
			case 'eloptgroup': return $this->ElementOptionGroupList();
		}
		return null;
	}
	
	/**
	 * Обработчик AJAX запросов
	 * @param Object $d данные запроса
	 * @return mixed
	 */
	public function AJAX($d){
		switch($d->do){
			// case "finduser": return $this->FindUser($d->firstname, $d->lastname, $d->username, true);
		}
		return null;
	}
	
	/**
	 * Получить список разделов в каталоге
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @return resource идентификатор указателя на результат в БД
	 */
	public function CatalogList($extFields = ""){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::CatalogList($this->db, $extFields);
	}
	
	/**
	 * Добавить раздел в каталог 
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param object $d Объект данных. Значение полей объекта:
	 * pid - идентификатор родителя, 
	 * tl - заголовок, nm - имя латиницей, dsc - описание, ord - сортировка, 
	 * ktl - мета тег TITLE, kdsc - мета тег DESCRIPTION, kwds - KEYWORDS
	 */
	public function CatalogAppend($d){
		if (!$this->IsAdminRole()){ return; }
		if (empty($d->nm)){
			$d->nm = translateruen($d->tl);
		}
		return CatalogQuery::CatalogAppend($this->db, $d);
	}
	
	/**
	 * Обновить раздел в каталоге
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param object $d Объект данных. Значение полей объекта: tl - заголовок, 
	 * nm - имя латиницей, dsc - описание, ord - сортировка, ktl - мета тег TITLE, 
	 * kdsc - мета тег DESCRIPTION, kwds - KEYWORDS
	 */
	public function CatalogUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::CatalogUpdate($this->db, $d);
	}
	
	/**
	 * Удалить элемент (Роль администратора)
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param integer $catalogId Идентификатор раздела
	 */
	public function CatalogRemove($catalogId){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::CatalogRemove($this->db, $catalogId);
	}
	
	/**
	 * Получить элемент из каталога
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param integer $elementid Идентификатор элемента
	 * @param boolean $retarray Опционально true - вернуть элемент в виде массива, иначе указатель на запись в БД
	 * @return mixed resource | array
	 */
	public function Element($elementid, $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::Element($this->db, $elementid, $retarray);
	}
	
	/**
	 * Получить список элементов в разделе каталога 
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param integer $catalogId Идентификатор раздела в каталоге
	 * @param integer $page Optional номер страницы, по умолчанию 1
	 * @param integer $limit Optional кол-во записей на странице, по умолчанию 10
	 * @param string $custWhere Optional тонкая настройка параметра WHERE в SQL запросе, Например: 'fld_akc > 0'
	 * @param string $custOrder Optional тонкая настройка параметра ORDER BY в SQL запросе, Например: 'fld_ord DESC, fld_sklad DESC, dateline DESC'
	 * @param string $overFields Optional тонкая настройка дополнительных полей в SQL запросе, Например: 'fld_price as pc, fld_desc as dsc'
	 */
	public function ElementList($catalogId, $page = 1, $limit = 10, $custWhere = '', $custOrder = '', $overFields = ''){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementList($this->db, $catalogId, $page, $limit, $custWhere, $custOrder, $overFields);
	}
	
	/**
	 * Кол-во элементов в разделе каталога
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param integer $catalogId Идентификатор каталога
	 * @return integer
	 */
	public function ElementCount($catalogId, $custWhere = ''){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementCount($this->db, $catalogId, $custWhere);
	}
	
	/**
	 * Переместить элемент в корзину
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::WRITE}
	 * 
	 * @param integer $elementid Идентификатор элемента
	 */
	public function ElementRemove($elementid){
		if (!$this->IsWriteRole()){ return; }
		CatalogQuery::ElementRemove($this->db, $elementid);
	}
	
	/**
	 * Восстановить элемент из корзины
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::WRITE}
	 * 
	 * @param integer $elementid Идентификатор элемента
	 */
	public function ElementRestore($elementid){
		if (!$this->IsWriteRole()){ return; }
		CatalogQuery::ElementRestore($this->db, $elementid);
	}

	/**
	 * Очистить корзину удаленных элементов
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::WRITE}
	 */
	public function ElementRecycleClear(){
		if (!$this->IsWriteRole()){ return; }
		CatalogQuery::ElementRecycleClear($this->db);
	}
	
	/**
	 * Добавить элемент в каталог
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::WRITE}
	 * 
	 * @param object $d данные элемента
	 */
	public function ElementAppend($d){
		if (!$this->IsWriteRole()){ return; }
		return CatalogQuery::ElementAppend($this->db, $d);
	}
	
	/**
	 * Обновить элемент в каталоге
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::WRITE}
	 * 
	 * @param object $d данные элемента
	 */
	public function ElementUpdate($d, $fullUpdate = true){
		if (!$this->IsWriteRole()){ return; }
		return CatalogQuery::ElementUpdate($this->db, $d, $fullUpdate);
	}
	
	public function LinkElementList($elementid, $optionid){
		if (!$this->IsViewRole()){ return; }
		return CatalogQuery::LinkElementList($this->db, $elementid, $optionid);
	}
	
	public function LinkElementAppend($elementid, $optionid, $childid){
		if (!$this->IsWriteRole()){ return; }
		return CatalogQuery::LinkElementAppend($this->db, $elementid, $optionid, $childid);
	}
	
	public function LinkElementRemove($elementid, $optionid, $childid){
		if (!$this->IsWriteRole()){ return; }
		return CatalogQuery::LinkElementRemove($this->db, $elementid, $optionid, $childid);
	}
	
	/**
	 * Получить список фотографий элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param integer $elementid Идентификатор элемента
	 */
	public function FotoList($elementid){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::FotoList($this->db, $elementid);
	}
	
	/**
	 * Получить список типов элементов
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 */
	public function ElementTypeList(){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementTypeList($this->db);
	}
	
	private $_elementTypeList = null;
	
	/**
	 * Получить список типов элементов в виде массива
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 */
	public function ElementTypeListArray(){
		if (!is_null($this->_elementTypeList)){
			return $this->_elementTypeList;
		}
		$ret = array();
		$rows = $this->ElementTypeList();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row['id']] = $row;
		}
		$this->_elementTypeList = $ret;
		return $this->_elementTypeList;
	}

	/**
	 * Добавить новый тип элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param Object $d
	 */
	public function ElementTypeAppend($d){
		if (!$this->IsAdminRole()){ return; }
		// Добавление типа элемента:
		// 1 - проверка возможности;
		// 2 - создание таблицы, в которой будут храниться элементы типа
		// 3 - добавление записи в таблицу типов элемента каталога
		$db = $this->db;
		$d->nm = translateruen($d->nm);
		$row = CatalogQuery::ElementTypeByName($db, $d->nm);
		
		if (empty($row)){ 
			$tablefind = false;
			$rows = CatalogQuery::TableList($db);
			while (($row = $db->fetch_array($rows, Ab_Database::DBARRAY_NUM))){
				if ($row[0] == ($db->prefix."ctg_eltbl_".$d->nm)){
					$tablefind = true;
					break;
				}
			}
			if (!$tablefind){
				CatalogQuery::ElementTypeTableCreate($db, $d->nm);
				CatalogQuery::ElementTypeAppend($db, $d);
			}
		}
	}
	
	/**
	 * Обновить значения типа элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param Object $d
	 */
	public function ElementTypeUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementTypeUpdate($this->db, $d);
	}
	
	public function ElementTypeRemove($eltypeid){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementTypeRemove($this->db, $eltypeid);
	}
	
	public function ElementTypeRestore($eltypeid){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementTypeRestore($this->db, $eltypeid);
	}
	
	public function ElementTypeRecycleClear(){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementTypeRecycleClear($this->db);
	}
	
	/**
	 * Получить список полей элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * @param Integer $elTypeId
	 * @param Integer $fieldType
	 */
	public function ElementOptionList($elTypeId = -1, $fieldType = -1){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementOptionList($this->db, $elTypeId = -1, $fieldType = -1);
	}
	
	private $_elementOptionList = null;
	
	public function ElementOptionListArray(){
		if (!is_null($this->_elementOptionList)){
			return $this->_elementOptionList;
		}
		$ret = array();
		$rows = $this->ElementOptionList();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row['id']] = $row;
		}
		$this->_elementOptionList = $ret;
		return $this->_elementOptionList;
	}

	/**
	 * Получить список полей элемента определенного типа элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param integer $elTypeId Идентификатор типа элемента
	 * @param boolean $retarray Если True вернуть массив и закешировать его
	 */
	public function ElementOptionListByType($elTypeId, $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementOptionListByType($this->db, $elTypeId, $retarray);
	}
	
	/**
	 * Получить запись по идентификатору из таблицы, поле элемента которого имеет тип ТАБЛИЦА
	 *  
	 * Доступ: роль пользователя {@link CatalogAction::VIEW}
	 * 
	 * @param string $eltypename
	 * @param string $fieldname
	 * @param integer $id
	 */
	public function ElementOptionFieldTableValue($eltypename, $fieldname, $id){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementOptionFieldTableValue($this->db, $eltypename, $fieldname, $id);
	}
	
	/**
	 * Удалить тип элемента в корзину
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param integer $optionid
	 */
	public function ElementOptionRemove($optionid){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementOptionRemove($this->db, $optionid);
	}

	/**
	 * Восстановить тип элемента из корзины 
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param integer $optionid
	 */
	public function ElementOptionRestore($optionid){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementOptionRestore($this->db, $optionid); 
	}
	
	/**
	 * Очистить корзину удаленных типов элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 */
	public function ElementOptionRecycleClear(){
		if (!$this->IsAdminRole()){ return; }
		CatalogQuery::ElementOptionRecycleClear($this->db);
	}
	
	/**
	 * Добавить поле в определенный тип элемента
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param integer $elTypeId
	 * @param integer $groupId
	 * @param CatalogOptionType $type
	 * @param string $name
	 * @param string $title
	 * @param string $descript
	 * @param boolean $useForTitle
	 * @param array $prms
	 */
	public function ElementOptionAppend($elTypeId, $groupId, $type, $name, 
			$title, $descript, $useForTitle, $prms){
		
		$obj = new stdClass();
		$obj->eltid = $elTypeId;
		$obj->grp = $groupId;
		$obj->fldtp = $type;
		$obj->prms = $prms;
		$obj->nm = $name;
		$obj->tl = $title;
		$obj->dsc = $descript;
		$obj->ets = $useForTitle;
		
		return $this->ElementOptionAppendObj($obj);
	}
	
	/**
	 * Добавить поле в определенный тип элемента
	 * 
	 * В качестве параметра использовать объект
	 * 
	 * Доступ: роль пользователя {@link CatalogAction::ADMIN}
	 * 
	 * @param Object $d
	 */
	public function ElementOptionAppendObj($d){
		if (!$this->IsAdminRole()){ return; }
		$d->nm = strtolower(translateruen($d->nm));
		if (empty($d->grp) && !empty($d->grpalt)){
			$d->grp = CatalogQuery::ElementOptGroupAppend($this->db, $d->eltid, $d->grpalt, '');
		}
		$db = Abricos::$db;
		$error = false;
		$prms = json_decode($d->prms);
		$d->fldtp = intval($d->fldtp);
		switch($d->fldtp){
			case CatalogQuery::OPTIONTYPE_BOOLEAN: $prms->def = intval($prms->def) > 0 ? 1 : 0; break;
			case CatalogQuery::OPTIONTYPE_NUMBER:
				$prms->size = intval($prms->size);
				$prms->def = intval($prms->def);
				if ($prms->size < 1 || $prms->size > 10){
					$error = true;
				}
				break;
			case CatalogQuery::OPTIONTYPE_DOUBLE: break;
			case CatalogQuery::OPTIONTYPE_STRING: $prms->size = intval($prms->size); break;
			case CatalogQuery::OPTIONTYPE_MULTI: break;
		}
		if (!$error){
			// информация типа элемента каталога
			$eltype = CatalogQuery::ElementTypeById($db, $d->eltid);
			$d->eltypenm = $eltype['nm'];
			// чтение имени поля из таблицы типа элемента
			$fieldfind = false;
			$rowsfl = CatalogQuery::ElementTypeTableFieldList($db, $eltype['nm']);
			while (($row = $db->fetch_array($rowsfl))){
				if ($row['field'] == "fld_".$d->nm){
					$fieldfind = true;
					break;
				}
			}
			// есть ли это имя опции в таблице опций eloption
			if (!$fieldfind){ 
				$row = CatalogQuery::ElementOptionByName($db, $d->eltid, $d->nm);
				if (!empty($row)){
					$fieldfind = true;
				}
			}
			
			// если это опция - тип таблица
			if (!$fieldfind && $d->fldtp == CatalogQuery::OPTIONTYPE_TABLE){
				$rows = CatalogQuery::TableList($db);
				while (($row = $db->fetch_array($rows, Ab_Database::DBARRAY_NUM))){
					if ($row[0] == ($db->prefix."ctg_eltbl_".$eltype['nm']."_fld_".$d->nm)){
						$fieldfind = true;
						break;
					}
				}
			}
			
			if (!$fieldfind){ // опция не найдена.
				// создание поля в таблице элемента, добавление опции в таблицу опций 
				CatalogQuery::ElementOptionAppend($db, $d, $prms);
			}
		}
	}
	
	public function ElementOptionUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		$d->nm = strtolower(translateruen($d->nm));
		
		if (empty($d->grp) && !empty($d->grpalt)){
			$d->grp = CatalogQuery::ElementOptGroupAppend($this->db, $d->eltid, $d->grpalt, '');
		}
		CatalogQuery::ElementOptionSave($this->db, $d);
	}
	
	public function ElementOptionFieldTableList($elTypeName, $fieldName){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementOptionFieldTableList($this->db, $elTypeName, $fieldName);
	}
	
	public function ElementOptionGroupList(){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::ElementOptGroupList($this->db);
	}
	
	public function FotoListThumb($elementid, $width, $height, $limit = 0){
		if (!$this->IsViewRole()){ return null; }
		return CatalogQuery::FotoListThumb($this->db, $elementid, $width, $height, $limit);
	}
}

/**
 * Типы полей элемента
 *
 */
class CatalogOptionType {
	const BOOLEAN = 0;
	const NUMBER = 1;
	const DOUBLE = 2;
	const STRING = 3;
	const LISTTYPE = 4;
	const TABLE = 5;
	const MULTI = 6;
	const TEXT = 7;
	const DICT = 8;
}

?>