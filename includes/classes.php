<?php 
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

/**
 * Раздел каталога
 */
class Catalog extends CatalogItem {
	
	/**
	 * Логический тип
	 * @var integer
	 */
	const TP_BOOLEAN = 0;
	/**
	 * Целое число
	 * @var integer
	 */
	const TP_NUMBER = 1;
	/**
	 * Число с плавающей точкой 
	 * @var integer
	 */
	const TP_DOUBLE = 2;
	/**
	 * Строка
	 * @var integer
	 */
	const TP_STRING = 3;
	// const TP_LIST = 4;
	/**
	 * Табличный список
	 * @var integer
	 */
	const TP_TABLE = 5;
	// const TP_MULTI = 6;
	/**
	 * Текст
	 * @var integer
	 */
	const TP_TEXT = 7;
	// const TP_DICT = 8;
	// const TP_CHILDELEMENT = 9;
	/**
	 * Список на другие элементы (поле содержит кеш идентификаторов через запятую)
	 * @var integer
	 */
	const TP_ELDEPENDS = 9;
	/**
	 * Список на другие элементы (поле содержит кеш имен через запятую)
	 * @var unknown_type
	 */
	const TP_ELDEPENDSNAME = 10;
	/**
	 * Набор файлов
	 * @var integer
	 */
	const TP_FILES = 11;
	
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
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
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
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
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
	
	public function Add($item){
		
		$notChangeParent = func_num_args()>1 ? func_get_arg(1) : false;
		
		parent::Add($item);
		if (!$notChangeParent){
			$item->parent = $this->owner;
		}
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
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
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
	 * @var CatalogElementOptionList
	 */
	public $options;
	
	public function __construct($d = array()){
		parent::__construct($d);

		$this->title	= strval($d['tl']); 
		$this->name		= strval($d['nm']);

		$this->options = new CatalogElementOptionList();
		
		$this->tableName = "element";
		if ($this->id > 0){
			$this->tableName = "eltbl_".$this->name;
		}
	}
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
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

	public function Add($item){
		parent::Add($item);
	}

	/**
	 * @param integer $id
	 * @return CatalogElementType
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}

	/**
	 * @param string $name
	 * @return CatalogElementType
	 */
	public function GetByName($name){
		for ($i=0;$i<$this->Count();$i++){
			$elType = $this->GetByIndex($i);
			if ($elType->name == $name){
				return $elType;
			}
		}
		return null;
	}
	
	/**
	 * Получить опцию элемента по идентификатору
	 * @param integer $optionid
	 * @return CatalogElementOption
	 */
	public function GetOptionById($optionid){
		for ($i=0;$i<$this->Count();$i++){
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

	public function ToAJAX(){
		$man = func_get_arg(0);
		
		$ret = array();
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

class CatalogElementOptionGroup extends CatalogItem {
	
	public $elTypeId;
	public $title;
	public $name;

	public function __construct($d){
		parent::__construct($d);
		$this->elTypeId = intval($d['tpid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
	}

	public function ToAJAX(){
		$man = func_get_arg(0);
		
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->tpid		= $this->elTypeId;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		return $ret;
	}
	
}

class CatalogElementOptionGroupList extends CatalogItemList {

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
		for ($i=0; $i<$cnt; $i++){
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
		for ($i=0; $i<$this->Count(); $i++){
			array_push($ret, $this->GetByIndex($i)->ToAJAX($man));
		}
		return $ret;
	}
}

/**
 * Опция элемента
 */
class CatalogElementOption extends CatalogItem {
	
	public $elTypeId;
	public $type;
	public $size;
	public $groupid;
	public $title;
	public $name;
	public $param;
	
	public function __construct($d){
		parent::__construct($d);
		$this->elTypeId = intval($d['tpid']);
		$this->type		= intval($d['tp']);
		$this->size		= strval($d['sz']);
		$this->groupid	= intval($d['gid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->param	= strval($d['prm']);
	}
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->tpid		= $this->elTypeId;
		$ret->tp		= $this->type;
		$ret->sz		= $this->size;
		$ret->gid		= $this->groupid;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		$ret->prm		= $this->param;
		return $ret;
	}
}

/**
 * Опция - тип поля таблица
 */
class CatalogElementOptionTable extends CatalogElementOption {
	
	public $values = array();
	
	public function __construct($d){
		parent::__construct($d);
	}
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
		$ret = parent::ToAJAX($man);
		$ret->values = $this->values;
		return $ret;
	}
}

class CatalogElementOptionList extends CatalogItemList {
	
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
		for ($i=0; $i<$cnt; $i++){
			$item = $this->GetByIndex($i);
			if ($name == $item->name){ return $item; }
		}
		return null;
	}

	public function ToAJAX(){
		$man = func_get_arg(0);
		
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
	public $fotoExt;
	
	public $ext = array();
	
	/**
	 * @var CatalogElementDetail
	 */
	public $detail = null;
	
	/**
	 * @param array $d
	 * @param CatalogElementOptionList $extFields
	 */
	public function __construct($d){
		parent::__construct($d);
		
		$this->catid = intval($d['catid']);
		$this->elTypeId = intval($d['tpid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->order	= intval($d['ord']);
		
		$afoto 			= explode("/", strval($d['foto']));
		$this->foto		= $afoto[0];
		$this->fotoExt	= $afoto[1];
		
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
		$ret .= $this->name.".".$this->fotoExt;
	
		return $ret;
	}
	
	public function ToAJAX(){
		$man = func_get_arg(0);
		
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

	public function ToAJAX(){
		$man = func_get_arg(0);
		
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
	 * @var CatalogElementOptionList
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
	 * Имена элементов
	 * @var array
	 */
	public $elnames;
	
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
		$this->elnames = array();
		
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
	 * @var string
	 */
	public $exp = "";

	public function __construct(CatalogElementOption $option, $exp){
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
	 * @param CatalogElementOption $option
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
	 * @var CatalogElementOption
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
	 
	public function __construct(CatalogElementOption $option, $isDesc = false){
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

	public function Add($item){
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
	
	public function __construct($dbPrefix){
		$this->db = CatalogManager::$instance->db;
		$this->pfx = $this->db->prefix."ctg_".$dbPrefix."_";
		$this->userid = Abricos::$user->id;
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
	 * Заполнить элемент деталями
	 * 
	 * @param CatalogElement $element
	 */
	public function ElementDetailFill($element){
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
		
		$rows = CatalogDbQuery::ElementFotoList($this->db, $this->pfx, $element->id);
		$fotos = array();
		$fotoList = new CatalogFotoList();
		while (($row = $this->db->fetch_array($rows))){
			array_push($fotos, $row['f']);
			$fotoList->Add(new CatalogFoto($row));
		}
		
		$detail = new CatalogElementDetail($dbEl, $dbOptionsBase, $dbOptionsPers, $fotos, $fotoList);
		
		$element->detail = $detail;
	}
	
	private $_cacheElementByName = null;
	
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

		$dbEl = CatalogDbQuery::ElementByName($this->db, $this->pfx, $name);
		if (empty($dbEl)){ return null; }
		
		$element = new $this->CatalogElementClass($dbEl);
		
		$this->ElementDetailFill($element);
		
		$this->_cacheElementByName[$name] = $element;
		
		return $element;
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
		
		$this->ElementDetailFill($element);
		
		$this->_cacheElementById[$elid] = $element;
		
		return $element;
	}
	
	public function ElementToAJAX($elid){
		$element = $this->Element($elid);
		if (empty($element)){ return null; }
		
		$ret = new stdClass();
		$ret->element = $element->ToAJAX($this);
		
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
		if (!$this->IsAdminRole()){ return null; }
		
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
		
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($d->tpid);
		if (empty($elType)){ return null; }
		
		if ($elid == 0 && $d->tpid == 0 && $this->cfgElementCreateBaseTypeDisable){
			// создание базовых элементов отключено
			return null;
		}
		
		$isNew = false;
		if ($elid == 0){ // добавление нового
			
			$isNew = true;
			$elid = CatalogDbQuery::ElementAppend($this->db, $this->pfx, $this->userid, $d);
			if (empty($elid)){ return null; }
			
		}else{ // сохранение текущего
			CatalogDbQuery::ElementUpdate($this->db, $this->pfx, $elid, $d);
		}
		
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
				CatalogDbQuery::ElementTypeUpdate($this->db, $this->pfx, $elTypeId, $d);
			}
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
				$option->values = CatalogManager::$instance->ToArrayId($rtbs);
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
		if (!$this->IsWriteRole()){ return false; }
		
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
		if (!$this->IsWriteRole()){ return false; }
		
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
		if (!$this->IsWriteRole()){ return false; }
		
		CatalogDbQuery::OptionFileAddToBuffer($this->db, $this->pfx, $this->userid, $option->id, $fhash, $fname);
		$this->OptionFileBufferClear();
	}
	
	public function OptionFileBufferClear(){
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
	
}

?>