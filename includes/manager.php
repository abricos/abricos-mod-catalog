<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';

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
	
	/**
	 * Отключить проверку ролей перед выполением функций
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

}
?>