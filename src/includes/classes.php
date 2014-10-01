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
    public $menuDisable;
    public $listDisable;
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
        $this->menuDisable = intval($d['mdsb'])>0;
        $this->listDisable = intval($d['ldsb'])>0;
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
        $ret->mdsb	= $this->menuDisable;
        $ret->ldsb	= $this->listDisable;
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

class CatalogFile extends AbricosItem {
	public $name;
	public $counter;
	public $dateline;
	
	public function __construct($d){
		$this->id = strval($d['id']);
		$this->name = strval($d['fn']);
		$this->counter = intval($d['cnt']);
		$this->dateline = intval($d['dl']);
	}
	
	public function URL(){
		return "/filemanager/i/".$this->id."/".$this->name;
	}
}

class CatalogFileList extends AbricosList {
	
	/**
	 * @return CatalogFile
	 */
	public function Get($id){
		return parent::Get($id);
	}
}

class CatalogUser extends AbricosItem {
	public $userName;
	public $avatar;
	public $firstName;
	public $lastName;
	
	/**
	 * Почта пользователя
	 * 
	 * Для внутреннего использования
	 * @var string
	 */
	public $email;
	
	public function __construct($d){
		$this->id			= intval($d['uid'])>0 ? $d['uid'] : $d['id'];
		$this->userName		= strval($d['unm']);
		$this->avatar		= strval($d['avt']);
		$this->firstName	= strval($d['fnm']);
		$this->lastName		= strval($d['lnm']);
		$this->email		= strval($d['eml']);
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->unm = $this->userName;
		$ret->avt = $this->avatar;
		$ret->fnm = $this->firstName;
		$ret->lnm = $this->lastName;
		return $ret;
	}
	
	public function GetUserName(){
		if (!empty($this->firstName) && !empty($this->lastName)){
			return $this->firstName." ".$this->lastName;
		}
		return $this->userName;
	}
	
	public function URL(){
		$mod = Abricos::GetModule('uprofile');
		if (empty($mod)){ return "#"; }
		return '/uprofile/'.$this->id.'/';
	}
	
	private function Avatar($size){
		$url = empty($this->avatar) ?
		'/modules/uprofile/images/nofoto'.$size.'.gif' :
		'/filemanager/i/'.$this->avatar.'/w_'.$size.'-h_'.$size.'/avatar.gif';
		return '<img src="'.$url.'">';
	}
	
	public function Avatar24(){
		return $this->Avatar(24);
	}
	
	public function Avatar90(){
		return $this->Avatar(90);
	}
}

class CatalogUserList extends AbricosList {
	
	/**
	 * @return CatalogUser
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * @return CatalogUser
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
}

class CatalogElementType extends CatalogItem {

	/**
	 * Название
	 * @var string
	 */
	public $title;
	/**
	 * Название в множественном числе
	 * @var string
	 */
	public $titleList;
	
	/**
	 * Имя (используется в качестве идентификатора)
	 * @var string
	 */
	public $name;
	
	public $tableName = "";
	
	/**
	 * @var CatalogElementOptionList
	 */
	public $options;
	
	public function __construct($d = array()){
		parent::__construct($d);

		$this->title	= strval($d['tl']);
		$this->titleList = strval($d['tls']);
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
		$ret->tls	= $this->titleList;
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
    public $order;
	
	public function __construct($d){
		parent::__construct($d);
		$this->elTypeId = intval($d['tpid']);
		$this->type		= intval($d['tp']);
		$this->size		= strval($d['sz']);
		$this->groupid	= intval($d['gid']);
		$this->title	= strval($d['tl']);
		$this->name		= strval($d['nm']);
		$this->param	= strval($d['prm']);
        $this->order    = intval($d['ord']);
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
        $ret->ord		= $this->order;
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
	 * Идентификатор пользователя добавивший элемент
	 * @var integer
	 */
	public $userid = 0;
	
	/**
	 * Дата добавления
	 * @var integer
	 */
	public $dateline = 0;
	/**
	 * Дата обновления
	 * @var integer
	 */
	public $upddate = 0;
	
	/**
	 * True - ожидает модерацию
	 * @var boolean
	 */
	public $isModer = false;
	
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
		$this->userid	= intval($d['uid']);
		$this->dateline	= intval($d['dl']);
		$this->upddate	= intval($d['upd']);
		$this->isModer	= intval($d['mdr'])>0;
		
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
		$ret->uid		= $this->userid;
		$ret->tpid		= $this->elTypeId;
		$ret->tl		= $this->title;
		$ret->nm		= $this->name;
		$ret->ord		= $this->order;
		$ret->foto		= $this->foto;
		$ret->mdr		= $this->isModer ? 1 : 0;
		
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
	
	/**
	 * Системная версия элемента
	 * @var integer
	 */
	public $version;
	
	/**
	 * Идентификатор элемента предыдущей версии
	 * @var integer
	 */
	public $pElementId;
	public $changeLog;

	public function __construct($d, $dOptBase, $dOptPers, $fotos, CatalogFotoList $fotoList){
		$this->metaTitle = strval($d['mtl']);
		$this->metaKeys = strval($d['mks']);
		$this->metaDesc = strval($d['mdsc']);
		
		$this->version = intval($d['v']);
		$this->pElementId = intval($d['pelid']);
		$this->changeLog = strval($d['chlg']);
		
		$this->optionsBase = $dOptBase;
		$this->optionsPers = $dOptPers;
		
		$this->fotos = $fotos;
		$this->fotoList = $fotoList;
	}

	public function ToAJAX(){
		$man = func_get_arg(0);
		
		$ret = new stdClass();
		$ret->chlg = $this->changeLog;
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

class CatalogElementChangeLog extends AbricosItem {
	
	/**
	 * Идентификатор элемента предыдущей версии
	 * @var integer
	 */
	public $pvElementId;
	
	/**
	 * Служебная версия элемента
	 * @var integer
	 */
	public $version;
	
	/**
	 * Дополнительные значения опций
	 * @var array
	 */
	public $ext;
	
	/**
	 * Дата публикации
	 * @var integer
	 */
	public $dateline;
	
	/**
	 * Список изменений
	 * @var string
	 */
	public $log;
	
	public function __construct($d){
		parent::__construct($d);
		$this->pvElementId = intval($d['pid']);
		$this->version = intval($d['v']);
		$this->dateline = intval($d['dl']);
		$this->log = strval($d['chlg']);
		$this->ext = array();
	}
}

class CatalogElementChangeLogList extends AbricosList {
	
	/**
	 * @return CatalogElementChangeLog
	 */
	public function Get($id){
		return parent::Get($id);
	}
	
	/**
	 * @return CatalogElementChangeLog
	 */
	public function GetByIndex($i){
		return parent::GetByIndex($i);
	}
}

/**
 * Фото элемента каталога
 */
class CatalogFoto extends AbricosItem {
	
	public $elementid;
	public $filehash;
	public $name;
	public $extension;
	public $filesize;
	public $width;
	public $height;
	
	public function __construct($d){
		parent::__construct($d);
		
		$this->filehash = $d['f'];
		$this->elementid = intval($d['elid']);
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
	
	private $_groups = array();
	
	/**
	 * @return CatalogFoto
	 */
	public function GetByIndex($i){return parent::GetByIndex($i);}
	
	public function Add($item){
		parent::Add($item);
		
		$elid = $item->elementid;
		if (!is_array($this->_groups[$elid])){
			$this->_groups[$elid] = array();
		}
		array_push($this->_groups[$elid], $item);
	}
	
	/**
	 * Получить все фото элемента
	 * 
	 * @param integer $elid
	 * @return array
	 */
	public function GetGroup($elid){
		if (empty($this->_groups[$elid])){ return array(); }
		return $this->_groups[$elid];
	}
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
	 * Идентификаторы типов элемента
	 * @var array
	 */
	public $eltpids;
	
	public function __construct($catids = null){
		if (is_null($catids)){
			$catids = array();
		}else if (!is_array($catids)){
			$catids = array($catids);
		}
		$this->catids = $catids;
		$this->elids = array();
		$this->elnames = array();
		$this->eltpids = array();
		
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

class CatalogStatisticElement extends AbricosItem {
	public $catid;
	public $elTypeId;
	public $count;
	
	public function __construct($d){
		if (empty($d['id'])){
			$this->id = $d['catid'].'-'.$d['tpid'];
		}else{
			$this->id = $d['id'];
		}
		
		$this->catid = intval($d['catid']);
		$this->elTypeId = intval($d['tpid']);
		$this->count = intval($d['cnt']);
	}
}

class CatalogStatisticElementList extends AbricosList {
	/**
	 * Кол-во элементов в каталоге
	 * @var array
	 */
	public $catalogCounter;
	/**
	 * Кол-во элементов по типу
	 * @var array
	 */
	public $elTypeCounter;
	
	public function __construct(){
		parent::__construct();
		$this->catalogCounter = array();
		$this->elTypeCounter = array();
	}
	
	public function Add($item){
		parent::Add($item);
		if (empty($this->catalogCounter[$item->catid])){
			$this->catalogCounter[$item->catid] = $item->count;
		}else{
			$this->catalogCounter[$item->catid] = $this->catalogCounter[$item->catid]+$item->count;			
		}
		
		if (empty($this->elTypeCounter[$item->elTypeId])){
			$this->elTypeCounter[$item->elTypeId] = $item->count;
		}else{
			$this->elTypeCounter[$item->elTypeId] = $this->elTypeCounter[$item->elTypeId]+$item->count;			
		}
		
	}
}

require_once 'classesman.php';

?>