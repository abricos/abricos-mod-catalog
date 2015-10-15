<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Менеджер каталога для управляющего модуля
 */
abstract class CatalogApp extends AbricosApplication {

    /**
     * Catalog datatable prefix
     *
     * @var string
     * @deprecated
     */
    private $pfx;

    public function __construct(Ab_ModuleManager $manager){
        parent::__construct($manager, array('catalog'));
    }

    protected function GetClasses(){
        return array(
            'Config' => 'CatalogConfig',
            'ELConfig' => 'CatalogELConfig',
            'ELConfigList' => 'CatalogELConfigList',
            'ELConfigFilter' => 'CatalogELConfigFilter',
            'ELConfigFilterList' => 'CatalogELConfigFilterList',
            'Catalog' => 'Catalog',
            'CatalogList' => 'CatalogList',
            'Element' => 'CatalogElement',
            'ElementList' => 'CatalogElementList',
            'ElementType' => 'CatalogElementType',
            'ElementTypeList' => 'CatalogElementTypeList',
            'ElementOption' => 'CatalogElementOption',
            'ElementOptionList' => 'CatalogElementOptionList'
        );
    }

    protected function GetStructures(){
        return 'Config,Catalog,Element,ElementType,ElementOption';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case "catalogList":
                return $this->CatalogListToJSON();
            case "element":
                return $this->ElementToJSON($d->elementid);
            case "elementSave":
                return $this->ElementSaveToJSON($d->elementData);
            case "elementRemove":
                return $this->ElementRemoveToJSON($d->elementid);
            case "elementList":
                return $this->ElementListToJSON($d->config);
            case "elementTypeList":
                return $this->ElementTypeListToJSON();
            case "config":
                return $this->ConfigToJSON();

            // old funcitons
            /*
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
            case "optiontablevaluesave":
                return $this->OptionTableValueSaveToAJAX($d->eltypeid, $d->optionid, $d->valueid, $d->value);
            case "optiontablevalueremove":
                return $this->OptionTableValueRemove($d->eltypeid, $d->optionid, $d->valueid);
            case "currencylist":
                return $this->CurrencyListToAJAX();
            case "currencysave":
                return $this->CurrencySaveToAJAX($d->currencyid, $d->savedata);
            case "currencyremove":
                return $this->CurrencyRemoveToAJAX($d->currencyid);
            /**/
        }

        return null;
    }

    /**
     * Роль администратора
     *
     * Полный доступ
     */
    public function IsAdminRole(){
        return false;
    }

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
    public function IsModeratorRole(){
        return false;
    }

    /**
     * Роль оператора
     *
     * Может создавать элементы каталога, но они должны
     * пройти подверждение модератором
     */
    public function IsOperatorRole(){
        return false;
    }

    /**
     * Пользователь имеет только роль оператора
     */
    public function IsOperatorOnlyRole(){
        if (!$this->IsOperatorRole()){
            return false;
        }

        return !$this->IsModeratorRole() && !$this->IsAdminRole();
    }

    /**
     * Роль авторизованного пользователя
     */
    public function IsWriteRole(){
        return false;
    }

    /**
     * Роль на просмотр
     *
     * @return boolean
     */
    public function IsViewRole(){
        return false;
    }

    private static $_ownerAppList = null;

    private static function OwnerAppList($isClear = false){
        if ($isClear){
            CatalogApp::$_ownerAppList = null;
        }
        if (!is_null(CatalogApp::$_ownerAppList)){
            return CatalogApp::$_ownerAppList;
        }
        CatalogApp::$_ownerAppList = array();
        $db = Abricos::$db;
        $rows = CatalogQuery::ModuleManagerList($db);
        while (($row = $db->fetch_array($rows))){
            CatalogApp::$_ownerAppList[$row['name']] = $row;
        }
        return CatalogApp::$_ownerAppList;
    }

    /**
     * @var Ab_UpdateManager
     */
    public static $updateShemaModule;

    private static function OwnerAppDbUpdate(CatalogApp $app, Ab_ModuleInfo $modInfo){
        $module = $app->manager->module;
        $catalogModule = Abricos::GetModule('catalog');
        if ($modInfo->version === $catalogModule->version){
            return false;
        }

        CatalogApp::$updateShemaModule = new Ab_UpdateManager($module, $modInfo);
        $catalogModule->ScriptRequire("setup/shema_mod.php");
        CatalogQuery::ModuleManagerUpdate($app, $catalogModule->version);

        return true;
    }

    private static function OwnerAppDbLanguageUpdate(CatalogApp $app, Ab_ModuleInfo $modInfo){
        $module = $app->manager->module;
        $catalogModule = Abricos::GetModule('catalog');
        if ($modInfo->languageVersion === $catalogModule->version){
            return false;
        }
        CatalogApp::$updateShemaModule = new Ab_UpdateManager($module, $modInfo);

        $catalogModule->ScriptRequire("setup/shema_mod_lang.php");
        CatalogQuery::ModuleManagerUpdateLanguage($app, $catalogModule->version);

        return true;
    }

    private $_isSetup;

    public function Setup(){
        if ($this->_isSetup){
            return;
        }
        $this->_isSetup = true;

        $module = $this->manager->module;
        $name = $module->name;

        $ownerAppList = CatalogApp::OwnerAppList();
        if (!isset($ownerAppList[$name])){
            CatalogQuery::ModuleManagerAppend($this);
            $ownerAppList = CatalogApp::OwnerAppList(true);
        }
        $modInfo = new Ab_ModuleInfo($ownerAppList[$name]);

        $isUpdate = CatalogApp::OwnerAppDbUpdate($this, $modInfo);
        CatalogApp::OwnerAppDbLanguageUpdate($this, $modInfo);

        $catalogModule = Abricos::GetModule('catalog');

        if ($isUpdate){
            CatalogApp::$updateShemaModule = new Ab_UpdateManager($module, $modInfo);
            $catalogModule->ScriptRequire("setup/shema_mod_after.php");
            CatalogQuery::ModuleManagerUpdate($this, $catalogModule->version);
        }

        CatalogApp::$updateShemaModule = null;
    }

    protected $_cache = array();

    public function CacheClear(){
        $this->_cache = array();
    }

    public function CatalogListToJSON(){
        $ret = $this->CatalogList();
        return $this->ResultToJSON('catalogList', $ret);
    }

    public function CatalogList(){
        if (isset($this->_cache['CatalogList'])){
            return $this->_cache['CatalogList'];
        }

        if (!$this->IsViewRole()){
            return 403;
        }

        /** @var CatalogList $list */
        $list = $this->InstanceClass('CatalogList');

        $root = $this->InstanceClass('ElementType', array(
            'id' => 0
        ));

        $list->Add($root);

        $rows = CatalogQuery::CatalogList($this);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Catalog', $d));
        }

        return $this->_cache['CatalogList'] = $list;
    }

    public function ElementToJSON($elementid){
        $ret = $this->Element($elementid);
        return $this->ResultToJSON('element', $ret);
    }

    public function Element($elementid){
        $elementid = intval($elementid);
        if (!isset($this->_cache['Element'])){
            $this->_cache['Element'] = array();
        }
        if (isset($this->_cache['Element'][$elementid])){
            return $this->_cache['Element'][$elementid];
        }
        if (!$this->IsViewRole()){
            return 403;
        }

        $d = CatalogQuery::Element($this, $elementid);
        if (empty($d)){
            return 404;
        }

        /** @var CatalogElement $element */
        $element = $this->InstanceClass('Element', $d);

        $this->ElementOptionsDataFill($element);

        return $element;
    }

    /**
     * @param CatalogElement $element
     */
    protected function ElementOptionsDataFill($element){
        $elTypeList = $this->ElementTypeList();
        $optionsData = CatalogQuery::ElementOptionsData($this, $elTypeList, $element);
        $element->values = $optionsData;
    }

    public function ElementRemoveToJSON($elementid){
        $ret = $this->ElementRemove($elementid);
        return $this->ResultToJSON('elementRemove', $ret);
    }

    public function ElementRemove($elementid){
        if (!$this->IsAdminRole()){
            return 403;
        }

        $element = $this->Element($elementid);
        if (is_integer($element)){
            return $element;
        }
        // TODO: Release remove for Moderator Element

        CatalogQuery::ElementRemove($this, $elementid);
        $this->CacheClear();

        $ret = new stdClass();
        $ret->elementid = $elementid;
        return $ret;
    }

    public function ElementSaveToJSON($d){
        $ret = $this->ElementSave($d);
        if (is_integer($ret)){
            return $this->ResultToJSON('elementSave', $ret);
        }

        return $this->ImplodeJSON(array(
            $this->ResultToJSON('elementSave', $ret),
            $this->ElementToJSON($ret->elementid)
        ));
    }

    /**
     * @param CatalogElement $element
     */
    private function ElementTitleComposite($element){
        if ($element->elTypeId === 0){
            return;
        }
        $elType = $this->ElementTypeList()->Get($element->elTypeId);
        $composite = $elType->composite;
        if ($composite === ''){
            return;
        }

        $vars = $elType->_cacheCompositeVars;
        if (!is_array($vars)){
            $vars = array();
            preg_match_all("/\{v#([0-9a-zA-Z_.]+)\}/", $composite, $vars);

            if (!is_array($vars) || !isset($vars[0]) || !is_array($vars[0])){
                return;
            }
            $elType->_cacheCompositeVars = $vars;
        }
        $count = count($vars[0]);
        if ($count === 0){
            return;
        }
        for ($i = 0; $i < $count; $i++){
            $composite = str_replace($vars[0][$i], $element->GetValue($vars[1][$i]), $composite);
        }

        $element->title = $composite;
    }

    public function ElementSave($d){
        if (!$this->IsAdminRole()){
            return 403;
        }

        $d->id = intval($d->id);

        /** @var CatalogElement $element */
        $element = $this->InstanceClass('Element', $d);

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($element->elTypeId);

        $catalogList = $this->CatalogList();
        $catalog = $catalogList->Get($element->catalogid);

        if (empty($elType) || empty($catalog)){
            return 400;
        }

        $this->ElementTitleComposite($element);
        $config = $this->Config();
        if ($d->id === 0){
            if ($config->elementBaseTypeDisable && $elType->id === 0){
                return 400;
            }

            if ($config->versionControl){

            }

            $element->id = CatalogQuery::ElementAppend($this, $element);
        } else {

        }
        CatalogQuery::ElementOptionsUpdate($this, $elTypeList, $element);

        $ret = new stdClass();
        $ret->elementid = $element->id;
        return $ret;
    }

    /**
     * @var CatalogELConfigList
     */
    private $_elsConfigs;

    protected function GetElementListConfig($d){
        if (!is_object($d)){
            $d = new stdClass();
        }
        $d->id = isset($d->id) ? $d->id : 'default';

        if (empty($this->_elsConfigs)){
            $this->_elsConfigs = $this->InstanceClass('ELConfigList');
        }

        /** @var CatalogELConfig $config */
        $config = $this->_elsConfigs->Get($d->id);
        if (empty($config)){
            return null;
        }
        $config->SetConfig($d);
        return $config;
    }

    public function ElementListToJSON($config){
        $ret = $this->ElementList($config);
        return $this->ResultToJSON('elementList', $ret);
    }

    /**
     * @param mixed $configData
     * @return CatalogElementList|int
     */
    public function ElementList($configData){
        if (!$this->IsViewRole()){
            return 403;
        }
        $config = $this->GetElementListConfig($configData);
        if (empty($config)){
            return 500;
        }

        $list = $this->InstanceClass('ElementList');
        $rows = CatalogQuery::ElementList($this, $config);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Element', $d));
        }
        return $list;
    }

    public function GetDBPrefix(){
        $config = $this->Config();
        return $this->db->prefix."ctg_".$config->dbPrefix."_";
    }

    public function ConfigToJSON(){
        $ret = $this->IsViewRole() ? $this->Config() : 403;
        return $this->ResultToJSON('config', $ret);
    }

    /**
     * @var CatalogConfig
     */
    private $_config;

    /**
     * @return CatalogConfig|int
     */
    public function Config(){
        if (!empty($this->_config)){
            return $this->_config;
        }

        /** @var CatalogConfig $config */
        $config = $this->InstanceClass('Config');
        $this->Configure($config);

        return $this->_config = $config;
    }

    /**
     * @param CatalogConfig $config
     */
    protected abstract function Configure($config);

    /* * * * * * * * * OLD FUNCTION TODO: REMOVE * * * * * * */

    private $_cacheCatalogList;

    /**
     * Древовидный список категорий каталога
     *
     * @param boolean $clearCache
     * @return CatalogList
     */
    public function oldCatalogList($clearCache = false){
        if (!$this->IsViewRole()){
            return false;
        }

        if ($clearCache){
            $this->_cacheCatalogList = null;
        }

        if (!empty($this->_cacheCatalogList)){
            return $this->_cacheCatalogList;
        }

        $list = array();
        $rows = CatalogQuery::CatalogList($this->db, $this->pfx);
        while (($d = $this->db->fetch_array($rows))){
            $list[] = new $this->CatalogClass($d);
        }

        if (count($list) == 0){
            $list[] = new $this->CatalogClass(array(
                "id" => 0,
                "pid" => -1,
                "nm" => "root",
                "tl" => "Root"
            ));
        }

        $catList = new CatalogList(null);
        $count = count($list);
        for ($i = 0; $i < $count; $i++){
            $cat = $list[$i];

            if ($cat->id == 0){
                $catList->Add($cat);
            } else {
                for ($ii = 0; $ii < $count; $ii++){
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
        for ($i = 0; $i < $catList->Count(); $i++){
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
        for ($i = 0; $i < count($catids); $i++){
            $cat = $catList->Find($catids[$i]);
            if (!empty($cat)){
                $retCatList->Add($cat, true);
            }
        }
        return $retCatList;
    }

    public function CatalogListToAJAX(){
        $list = $this->CatalogList();

        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->catalogs = $list->ToAJAX($this);
        return $ret;
    }

    private $_cacheCatalog = array();

    /**
     * @param integer $catid
     * @return Catalog
     */
    public function Catalog($catid, $clearCache = false){
        if (!$this->IsViewRole()){
            return null;
        }

        if ($clearCache){
            $this->_cacheCatalog = array();
        }
        if (isset($this->_cacheCatalog[$catid])){
            return $this->_cacheCatalog[$catid];
        }

        $d = CatalogQuery::Catalog($this->db, $this->pfx, $catid);
        if (empty($d)){
            return null;
        }

        $catList = $this->CatalogList();
        $cat = $catList->Find($catid);
        // $cat = new $this->CatalogClass($d);
        $cat->detail = new CatalogDetail($d);

        $this->_cacheCatalog[$catid] = $cat;

        return $cat;
    }

    public function CatalogToAJAX($catid, $isElementList = false){
        $cat = $this->Catalog($catid);

        if (empty($cat)){
            return null;
        }

        $ret = new stdClass();
        $ret->catalog = $cat->ToAJAX($this);

        if ($isElementList){
            $retEls = $this->ElementListToAJAX($catid);
            $ret->elements = $retEls->elements;
        }
        return $ret;
    }

    public function CatalogSave($catid, $d){
        if (!$this->IsAdminRole()){
            return null;
        }

        $d = array_to_object($d);

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        $catid = intval($catid);
        $d->pid = isset($d->pid) ? intval($d->pid) : 0;
        $d->tl = isset($d->tl) ? $utmf->Parser($d->tl) : "";
        $d->nm = translateruen($d->tl);
        $d->dsc = isset($d->dsc) ? $utm->Parser($d->dsc) : "";

        $d->mtl = isset($d->mtl) ? $utmf->Parser($d->mtl) : "";
        $d->mks = isset($d->mks) ? $utmf->Parser($d->mks) : "";
        $d->mdsc = isset($d->mdsc) ? $utmf->Parser($d->mdsc) : "";

        $d->ord = isset($d->ord) ? intval($d->ord) : 0;
        $d->mdsb = isset($d->mdsb) ? intval($d->mdsb) : 0;
        $d->ldsb = isset($d->ldsb) ? intval($d->ldsb) : 0;

        if ($catid == 0){ // добавление нового

            $catid = CatalogQuery::CatalogAppend($this->db, $this->pfx, $d);
            if (empty($catid)){
                return null;
            }

            $this->_cacheCatalogList = null;
        } else { // сохранение текущего
            CatalogQuery::CatalogUpdate($this->db, $this->pfx, $catid, $d);
        }

        CatalogQuery::FotoRemoveFromBuffer($this->db, $this->pfx, $d->foto);

        return $catid;
    }

    public function CatalogSaveToAJAX($catid, $d){
        $catid = $this->CatalogSave($catid, $d);

        if (empty($catid)){
            return null;
        }

        return $this->CatalogToAJAX($catid);
    }

    public function CatalogRemove($catid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $cat = $this->Catalog($catid);

        $count = $cat->childs->Count();
        for ($i = 0; $i < $count; $i++){
            $ccat = $cat->childs->GetByIndex($i);

            $this->CatalogRemove($ccat->id);
        }

        CatalogQuery::ElementListRemoveByCatId($this->db, $this->pfx, $catid);
        CatalogQuery::CatalogRemove($this->db, $this->pfx, $catid);

        return true;
    }

    public function old_ElementList($param){
        if (!$this->IsViewRole()){
            return false;
        }
        // TODO: develop cache
        $cfg = null;
        $catid = 0;
        if (is_object($param)){
            $cfg = $param;
        } else {
            $cfg = new CatalogElementListConfig($param);
        }

        $cfg->typeList = $this->ElementTypeList();

        $list = new $this->CatalogElementListClass();
        $list->cfg = $cfg;

        $rows = CatalogQuery::ElementList($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $cfg);
        while (($d = $this->db->fetch_array($rows))){

            $cnt = $cfg->extFields->Count();
            if ($cnt > 0){
                $d['ext'] = array();
                for ($i = 0; $i < $cnt; $i++){
                    $opt = $cfg->extFields->GetByIndex($i);
                    $d['ext'][$opt->name] = $d['fld_'.$opt->name];
                }
            }

            $list->Add(new $this->CatalogElementClass($d));
        }
        $list->total = CatalogQuery::ElementListCount($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $cfg);

        return $list;
    }

    public function ElementListToAJAX($param){
        $list = $this->ElementList($param);

        if (empty($list)){
            return null;
        }

        $ret = new stdClass();
        $ret->elements = $list->ToAJAX($this);
        return $ret;
    }

    public function ElementListOrderSave($catid, $orders){
        if (!$this->IsAdminRole()){
            return null;
        }

        $list = $this->ElementList($catid);

        for ($i = 0; $i < $list->Count(); $i++){
            $el = $list->GetByIndex($i);
            $elid = $el->id;
            $order = $orders->$elid;

            CatalogQuery::ElementOrderUpdate($this->db, $this->pfx, $el->id, $order);
        }

        return $this->ElementListToAJAX($catid);
    }

    /**
     * Заполнить элемент деталями
     *
     * @param CatalogElement $element
     */
    public function old_ElementDetailFill($element, $dbEl){
        $elTypeList = $this->ElementTypeList();

        $tpBase = $elTypeList->Get(0);
        $dbOptionsBase = CatalogQuery::ElementDetail($this->db, $this->pfx, $element->id, $tpBase);

        $dbOptionsPers = array();
        if ($element->elTypeId > 0){
            $tpPers = $elTypeList->Get($element->elTypeId);
            if (!empty($tpPers)){
                $dbOptionsPers = CatalogQuery::ElementDetail($this->db, $this->pfx, $element->id, $tpPers);
            }
        }

        $fotos = array();
        $fotoList = $this->ElementFotoList($element);
        for ($i = 0; $i < $fotoList->Count(); $i++){
            $foto = $fotoList->GetByIndex($i);
            $fotos[] = $foto->filehash;
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
        if (!$this->IsViewRole()){
            return null;
        }

        $elids = array();
        if ($data instanceof CatalogElementList){
            for ($i = 0; $i < $data->Count(); $i++){
                $el = $data->GetByIndex($i);
                $elids[] = $el->id;
            }
        } else if ($data instanceof CatalogElement){
            $elids[] = $data->id;
        }

        $fotoList = new CatalogFotoList();

        $rows = CatalogQuery::ElementFotoList($this->db, $this->pfx, $elids);
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
        if (!$this->IsViewRole()){
            return null;
        }

        if ($clearCache || !is_array($this->_cacheElementByName)){
            $this->_cacheElementByName = array();
        }
        if (!empty($this->_cacheElementByName[$name])){
            return $this->_cacheElementByName[$name];
        }

        $dbEl = CatalogQuery::ElementByName($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $name);
        if (empty($dbEl)){
            return null;
        }

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
        if (!$this->IsViewRole()){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elTypeBase = $elTypeList->Get(0);
        $optionList = new CatalogElementOptionList();
        $aExtOptions = explode(",", $sExtOptions);
        foreach ($aExtOptions as $optName){
            $option = $elTypeBase->options->GetByName($optName);
            if (empty($option)){
                continue;
            }
            $optionList->Add($option);
        }

        $list = new CatalogElementChangeLogList();
        $rows = CatalogQuery::ElementChangeLogListByName($this->db, $this->pfx, $name, $optionList);
        while (($d = $this->db->fetch_array($rows))){
            $chLog = new CatalogElementChangeLog($d);

            for ($i = 0; $i < $optionList->Count(); $i++){
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
        if (!$this->IsViewRole()){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elTypeBase = $elTypeList->Get(0);
        $optionList = new CatalogElementOptionList();
        $aExtOptions = explode(",", $sExtOptions);
        foreach ($aExtOptions as $optName){
            $option = $elTypeBase->options->GetByName($optName);
            if (empty($option)){
                continue;
            }
            $optionList->Add($option);
        }

        $list = new CatalogElementChangeLogList();
        $rows = CatalogQuery::ElementChangeLogList($this->db, $this->pfx, $optionList);
        while (($d = $this->db->fetch_array($rows))){
            $chLog = new CatalogElementChangeLog($d);

            for ($i = 0; $i < $optionList->Count(); $i++){
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
    public function old_Element($elid, $clearCache = false){

        $element = new $this->CatalogElementClass($dbEl);

        $this->ElementDetailFill($element, $dbEl);

        $this->_cacheElementById[$elid] = $element;

        return $element;
    }

    public function ElementIdByNameToAJAX($elname){
        $element = $this->ElementByName($elname);
        if (empty($element)){
            return null;
        }

        $ret = new stdClass();
        $ret->elementid = $element->id;
        $ret->userid = $element->userid;
        $ret->ismoder = $element->isModer ? 1 : 0;

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
    public function old_ElementSave($elid, $d){
        if (!$this->IsOperatorRole()){
            return null;
        }

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        $elid = intval($elid);
        $d->tpid = isset($d->tpid) ? intval($d->tpid) : 0;
        $d->tl = isset($d->tl) ? $utmf->Parser($d->tl) : "";

        if (!$this->cfgElementNameChange || empty($d->nm)){
            $d->nm = translateruen($d->tl);
        } else {
            $d->nm = translateruen($d->nm);
        }

        $d->mtl = isset($d->mtl) ? $utmf->Parser($d->mtl) : "";
        $d->mks = isset($d->mks) ? $utmf->Parser($d->mks) : "";
        $d->mdsc = isset($d->mdsc) ? $utmf->Parser($d->mdsc) : "";

        $d->ord = isset($d->ord) ? intval($d->ord) : 0;

        $utmChLog = Abricos::TextParser(true);

        // TODO: временное решение в лоб
        $utmfLog = Abricos::TextParser(true);
        $utmfLog->jevix->cfgSetAutoBrMode(true);
        $d->chlg = isset($d->chlg) ? $utmfLog->Parser($d->chlg) : "";
        $d->chlg = str_replace("<br/>", '', $d->chlg);

        /*
        $d->chlg = str_replace("\r\n",'[--!rn!--]', $d->chlg);
        $d->chlg = str_replace("\n",'[--!n!--]', $d->chlg);
        $d->chlg = $utmf->Parser($d->chlg);
        $d->chlg = str_replace('[--!rn!--]', "\r\n", $d->chlg);
        $d->chlg = str_replace('[--!n!--]', "\n", $d->chlg);
        /**/
        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($d->tpid);
        if (empty($elType)){
            return null;
        }

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
                    $d->v = $curEl->detail->version + 1; // увеличить номер версии нового элемента

                    if ($this->IsAdminRole()){ // Оператор может добавить только на модерацию
                        // текущую версию переместить в архив
                        CatalogQuery::ElementToArhive($this->db, $this->pfx, $curEl->id);
                    }
                }
            }

            $elid = CatalogQuery::ElementAppend($this->db, $this->pfx, $this->userid, $this->IsOperatorOnlyRole(), $d);
            if (empty($elid)){
                return null;
            }

            if ($this->IsOperatorOnlyRole()){
                $isNewElementByOperator = true;
            }

        } else { // сохранение текущего элемента

            $el = $this->Element($elid);
            if (empty($el)){
                return null;
            }

            if ($this->IsOperatorOnlyRole() && ($el->userid != $this->userid || !$el->isModer)){
                // оператору не принадлежит этот элемент или элемент уже прошел модерацию
                return null;
            }

            if ($this->cfgElementNameUnique){
                // имя элемента уникальное, поэтому изменять его нельзя
                $d->nm = $el->name;
            }

            CatalogQuery::ElementUpdate($this->db, $this->pfx, $elid, $d);
        }

        if (!empty($d->values)){
            foreach ($d->values as $tpid => $opts){
                $elType = $elTypeList->Get($tpid);
                CatalogQuery::ElementDetailUpdate($this->db, $this->pfx, $this->userid, $this->IsAdminRole(), $elid, $elType, $opts);
            }
        }

        // обновление фоток
        CatalogQuery::ElementFotoUpdate($this->db, $this->pfx, $elid, $d->fotos);

        $this->OptionFileBufferClear();
        $this->FotoBufferClear();

        if ($isNewElementByOperator){
            $this->OnElementAppendByOperator($elid);
        }

        return $elid;
    }

    /**
     * Событие на доабвление нового элемента оператором
     *
     * @param integer $elementid
     */
    protected function OnElementAppendByOperator($elementid){
    }

    public function ElementModer($elid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $el = $this->Element($elid);

        if (empty($el) || !$el->isModer){
            return null;
        }

        if ($this->cfgVersionControl){
            CatalogQuery::ElementToArhive($this->db, $this->pfx, $el->detail->pElementId);
            CatalogQuery::ElementModer($this->db, $this->pfx, $elid);
        }

        $this->OnElementModer($elid);

        return $elid;
    }

    private function TableCheck($tname){
        $rows = CatalogQuery::TableList($this->db);

        while (($row = $this->db->fetch_array($rows, Ab_Database::DBARRAY_NUM))){
            if ($row[0] == $tname){
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $d
     * @return int|null
     */
    public function ElementTypeSave($d){
        if (!$this->IsAdminRole()){
            return 403;
        }

        /** @var CatalogElementType $elType */
        $elType = $this->InstanceClass('ElementType', $d);
        $elType->name = strtolower(translateruen($elType->name));

        $utmf = Abricos::TextParser(true);

        $title = $elType->title;
        $title->Set($utmf->Parser($title->Get()));
        $sTitle = $title->Get();

        if ($sTitle === '' || $elType->name === ''){
            return 400;
        }

        $titles = $elType->titles;
        $sTitles = $titles->Get();
        if (empty($sTitles)){
            $titles->Set($sTitle);
        } else {
            $titles->Set($utmf->Parser($sTitles));
        }

        $utm = Abricos::TextParser();

        $descript = $elType->descript;
        $descript->Set($utm->Parser($descript->Get()));

        $elTypeList = $this->ElementTypeList();

        if ($elType->id === 0){
            $checkElType = $elTypeList->GetByName($elType->name);
            if (!empty($checkElType)){
                return 400;
            }

            CatalogQuery::ElementTypeTableCreate($this, $elType);
            $elTypeId = CatalogQuery::ElementTypeAppend($this, $elType);
        } else {

            $checkElType = $typeList->GetByName($d->nm);
            if (!$this->TableCheck($tableName)){
                return null; // такого быть не должно. hacker?
            }

            if ($checkElType->name != $d->nm){ // попытка сменить имя таблицы
                if ($this->TableCheck($tableName)){
                    return null; // уже есть такая таблица
                }

                $oldTableName = $this->ElementTypeTableName($checkElType->name);

                CatalogQuery::ElementTypeTableChange($this->db, $oldTableName, $tableName);
            }
            CatalogQuery::ElementTypeUpdate($this->db, $this->pfx, $elTypeId, $d);
        }

        $this->CacheClear();

        $ret = new stdClass();
        $ret->elTypeId = $elTypeId;
        return $ret;
    }

    public function ElementTypeRemove($elTypeId){
        if (!$this->IsAdminRole()){
            return null;
        }

        if ($elTypeId == 0){
            return null;
        } // нельзя удалить базовый тип

        $typeList = $this->ElementTypeList();
        $elType = $typeList->Get($elTypeId);

        $cnt = $elType->options->Count();
        for ($i = 0; $i < $cnt; $i++){
            $option = $elType->options->GetByIndex($i);
            $this->ElementOptionRemove($elTypeId, $option->id);
        }

        $tableName = $this->ElementTypeTableName($elType->name);

        CatalogQuery::ElementTypeTableRemove($this->db, $tableName);
        CatalogQuery::ElementTypeRemove($this->db, $this->pfx, $elTypeId);
    }

    public function ElementTypeRemoveToAJAX($elTypeId){
        $this->ElementTypeRemove($elTypeId);
        return $this->ElementTypeListToJSON(true);
    }

    public function ElementTypeListToJSON(){
        $ret = $this->ElementTypeList();
        return $this->ResultToJSON('elementTypeList', $ret);
    }

    /**
     * @return CatalogElementTypeList|int
     */
    public function ElementTypeList(){
        if (isset($this->_cache['ElementTypeList'])){
            return $this->_cache['ElementTypeList'];
        }

        if (!$this->IsViewRole()){
            return 403;
        }

        /** @var CatalogElementTypeList $list */
        $list = $this->InstanceClass('ElementTypeList');
        /** @var CatalogElementType $curType */
        $curType = $this->InstanceClass('ElementType', array(
            'id' => 0,
            'name' => ''
        ));

        $list->Add($curType);

        $rows = CatalogQuery::ElementTypeList($this);
        while (($d = $this->db->fetch_array($rows))){
            $item = $this->InstanceClass('ElementType', $d);
            $list->Add($item);
        }

        $rows = CatalogQuery::ElementOptionList($this);
        while (($d = $this->db->fetch_array($rows))){

            /** @var CatalogElementOption $option */
            $option = $this->InstanceClass('ElementOption', $d);

            if (empty($curType) || $curType->id !== $option->elTypeId){
                $curType = $list->Get($option->elTypeId);
            }

            if (empty($curType)){
                continue;
            }

            if ($option->type === CatalogType::TP_TABLE){
                $rtbs = CatalogQuery::OptionTableValueList($this, $curType->name, $option->name);
                $option->values = $this->ToArrayId($rtbs);
            }

            $curType->options->Add($option);
        }

        return $this->_cache['ElementTypeList'] = $list;
    }

    private $_cacheElementOptGroupList;

    /**
     * @param boolean $clearCache
     * @return CatalogElementOptionGroup
     */
    public function ElementOptionGroupList($clearCache = false){
        if (!$this->IsViewRole()){
            return false;
        }

        if ($clearCache){
            $this->_cacheElementOptGroupList = null;
        }

        if (!empty($this->_cacheElementOptGroupList)){
            return $this->_cacheElementOptGroupList;
        }

        $list = new CatalogElementOptionGroupList();
        $rows = CatalogQuery::ElementOptionGroupList($this->db, $this->pfx);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new CatalogElementOptionGroup($d));
        }
        $this->_cacheElementOptGroupList = $list;
        return $list;
    }

    /**
     * В процессе добавления фото к элементу/каталогу идентификатор файла
     * помещается в буфер. Если в течении времени, фото так и не было прикреплено
     * к чему либо, он удаляется
     *
     * @param string $fhash
     */
    public function FotoAddToBuffer($fhash){
        if (!$this->IsAdminRole()){
            return false;
        }

        CatalogQuery::FotoAddToBuffer($this->db, $this->pfx, $fhash);

        $this->FotoBufferClear();
    }

    /**
     * Удалить временные фото из буфера
     */
    public function FotoBufferClear(){
        $mod = Abricos::GetModule('filemanager');
        if (empty($mod)){
            return;
        }
        $mod->GetManager();
        $fm = FileManager::$instance;
        $fm->RolesDisable();

        $rows = CatalogQuery::FotoFreeFromBufferList($this->db, $this->pfx);
        while (($row = $this->db->fetch_array($rows))){
            $fm->FileRemove($row['fh']);
        }
        $fm->RolesEnable();

        CatalogQuery::FotoFreeListClear($this->db, $this->pfx);
    }

    /**
     * Есть ли возможность выгрузки файла для запись в значении опции элемента
     *
     * @param integer $optionid
     */
    public function OptionFileUploadCheck($optionid){
        if (!$this->IsAdminRole()){
            return false;
        }

        $elTypeList = $this->ElementTypeList();
        $option = $elTypeList->GetOptionById($optionid);

        if (empty($option) || $option->type != CatalogType::TP_FILES){
            return null;
        }

        $aFTypes = array();
        $aOPrms = explode(";", $option->param);
        for ($i = 0; $i < count($aOPrms); $i++){
            $aExp = explode("=", $aOPrms[$i]);
            switch (strtolower(trim($aExp[0]))){
                case 'ftypes':

                    $aft = explode(",", $aExp[1]);
                    for ($ii = 0; $ii < count($aft); $ii++){
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
        if (!$this->IsAdminRole()){
            return false;
        }

        CatalogQuery::OptionFileAddToBuffer($this->db, $this->pfx, $this->userid, $option->id, $fhash, $fname);
        $this->OptionFileBufferClear();
    }

    public function OptionFileBufferClear(){
        $mod = Abricos::GetModule('filemanager');
        if (empty($mod)){
            return;
        }
        $mod->GetManager();
        $fm = FileManager::$instance;
        $fm->RolesDisable();

        $rows = CatalogQuery::OptionFileFreeFromBufferList($this->db, $this->pfx);
        while (($row = $this->db->fetch_array($rows))){
            $fm->FileRemove($row['fh']);
        }
        $fm->RolesEnable();
        CatalogQuery::OptionFileFreeListClear($this->db, $this->pfx);
    }


    /**
     * Сохранение опции элемента
     *
     * @param integer $optionid идентификатор опции, если 0, то новая опция
     * @param mixed $d
     */
    public function ElementOptionSave($d){
        if (!$this->IsAdminRole()){
            return 403;
        }

        /** @var CatalogElementOption $option */
        $option = $this->InstanceClass('ElementOption', $d);

        $utmf = Abricos::TextParser(true);

        $title = $option->title;
        $title->Set($utmf->Parser($title->Get()));

        $option->name = strtolower(translateruen($option->name));

        $sTitle = $title->Get();
        if ($sTitle === '' || $option->name === ''){
            return 400;
        }

        $utm = Abricos::TextParser();
        $descript = $option->descript;
        $descript->Set($utm->Parser($descript->Get()));

        $typeList = $this->ElementTypeList();
        $elType = $typeList->Get($option->elTypeId);

        if (empty($elType)){
            return 400;
        }

        CatalogElementOption::DataFix($option);

        if ($option->id === 0){
            $checkOption = $elType->options->GetByName($option->name);
            if (!empty($checkOption)){
                return 400;
            }
            $optionid = CatalogQuery::ElementOptionAppend($this, $option);
            CatalogQuery::ElementOptionFieldCreate($this, $elType, $option);
        } else {
            $checkOption = $elType->options->Get($optionid);
            if (empty($checkOption)){
                return null;
            }

            if ($checkOption->name != $d->nm){ // попытка изменить имя
                $newCheckOption = $elType->options->GetByName($d->nm);
                if (!empty($newCheckOption)){ // уже есть опция с таким именем
                    return;
                }
                CatalogQuery::ElementOptionFieldUpdate($this->db, $this->pfx, $elType, $tableName, $checkOption, $d);
            }
            CatalogQuery::ElementOptionUpdate($this->db, $this->pfx, $optionid, $d);

            if ($checkOption->type != $d->tp &&
                ($checkOption->type == CatalogType::TP_CURRENCY || $checkOption->type == CatalogType::TP_DOUBLE) &&
                ($d->tp == CatalogType::TP_CURRENCY || $d->tp == CatalogType::TP_DOUBLE)
            ){ // попытка изменить тип поля
                // пока можно менять DOUBLE <=> CURRENCY
                CatalogQuery::ElementOptionTypeUpdate($this->db, $this->pfx, $optionid, $d);
            }
        }

        $ret = new stdClass();
        $ret->optionid = $optionid;
        return $ret;
    }

    public function ElementOptionRemove($elTypeId, $optionid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $typeList = $this->ElementTypeList();
        $elType = $typeList->Get($elTypeId);

        if (empty($elType)){
            return null;
        }

        $option = $elType->options->Get($optionid);
        if (empty($option)){
            return null;
        }

        CatalogQuery::ElementOptionRemove($this->db, $this->pfx, $optionid);

        CatalogQuery::ElementOptionFieldRemove($this->db, $this->pfx, $elType, $option);
    }

    public function OptionTableValueSave($eltypeid, $optionid, $valueid, $value){
        if (!$this->IsAdminRole()){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($eltypeid);

        if (empty($elType)){
            return null;
        }

        $option = $elType->options->Get($optionid);
        if (empty($option)){
            return null;
        }

        if ($option->type != CatalogType::TP_TABLE){
            return null;
        }

        $utmf = Abricos::TextParser(true);
        $value = $utmf->Parser($value);

        if ($valueid == 0){
            $valueid = CatalogQuery::OptionTableValueAppend($this->db, $this->pfx, $elType->name, $option->name, $value);
        } else {
            CatalogQuery::OptionTableValueUpdate($this->db, $this->pfx, $elType->name, $option->name, $valueid, $value);
        }

        return $valueid;
    }

    public function OptionTableValueSaveToAJAX($eltypeid, $optionid, $valueid, $value){
        $valueid = $this->OptionTableValueSave($eltypeid, $optionid, $valueid, $value);

        if (empty($valueid)){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($eltypeid);
        $option = $elType->options->Get($optionid);

        $rtbs = CatalogQuery::OptionTableValueList($this->db, $this->pfx, $elType->name, $option->name);
        $option->values = $this->ToArrayId($rtbs);

        $ret = new stdClass();
        $ret->values = $option->values;
        $ret->valueid = $valueid;

        return $ret;
    }

    public function OptionTableValueRemove($eltypeid, $optionid, $valueid){
        if (!$this->IsAdminRole()){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($eltypeid);

        if (empty($elType)){
            return null;
        }

        $option = $elType->options->Get($optionid);
        if (empty($option)){
            return null;
        }

        if ($option->type != CatalogType::TP_TABLE){
            return null;
        }

        CatalogQuery::OptionTableValueRemove($this->db, $this->pfx, $elType->name, $option->name, $valueid);

        $rtbs = CatalogQuery::OptionTableValueList($this->db, $this->pfx, $elType->name, $option->name);
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
        if (!empty($option) && $option->type == CatalogType::TP_TABLE && !empty($option->values[$eFValue])){
            $optValueId = intval($eFValue);
        }
        return $optValueId;
    }

    /**
     * Получить список возможных вариантов для автозаполнения в поиске
     *
     * @param string $query
     */
    public function SearchAutoComplete($query, $eFField = '', $eFValue = ''){
        $ret = array();

        if (!$this->IsViewRole()){
            return $ret;
        }
        if (strlen($query) < 2){
            return $ret;
        }

        $eFValue = $this->SearchOptionCheck($eFField, $eFValue);

        $rows = CatalogQuery::SearchAutoComplete($this->db, $this->pfx, $query, $eFField, $eFValue);
        while (($row = $this->db->fetch_array($rows))){
            $ret[] = $row['tl'];
        }
        return $ret;
    }

    public function Search($query, $eFField = '', $eFValue = ''){
        $ret = array();
        if (!$this->IsViewRole()){
            return $ret;
        }

        $eFValue = $this->SearchOptionCheck($eFField, $eFValue);

        $rows = CatalogQuery::Search($this->db, $this->pfx, $query, $eFField, $eFValue);
        while (($row = $this->db->fetch_array($rows))){
            $ret[] = $row;
        }
        return $ret;
    }


    /**
     * Получить список файлов
     *
     * Параметр $data может принимать значение:
     *        CatalogElementList - список элементов,
     *        CataloElement - элемент
     *
     * @param CatalogElementList|CataloElement $data
     * @return CatalogFileList
     */
    public function ElementOptionFileList($data){
        $files = new CatalogFileList();
        if (!$this->IsViewRole()){
            return $files;
        }

        $elids = array();
        $elcids = array();
        if ($data instanceof CatalogElementList){
            for ($i = 0; $i < $data->Count(); $i++){
                $el = $data->GetByIndex($i);
                if (isset($elcids[$el->id]) && $elcids[$el->id]){
                    continue;
                }
                $elcids[$el->id] = true;
                $elids[] = $el->id;
            }
        } else if ($data instanceof CatalogElement){
            $elids[] = $data->id;
        }

        $rows = CatalogQuery::ElementOptionFileList($this->db, $this->pfx, $elids);
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
        if (!$this->IsViewRole()){
            return null;
        }

        if ($clearCache){
            $this->_cacheStatElList = null;
        }

        if (!empty($this->_cacheStatElList)){
            return $this->_cacheStatElList;
        }

        $list = new CatalogStatisticElementList();
        $rows = CatalogQuery::StatisticElementList($this->db, $this->pfx);
        $i = 0;
        while (($d = $this->db->fetch_array($rows))){
            $d['id'] = $i + 1;
            $item = new CatalogStatisticElement($d);
            $list->Add($item);
        }
        $this->_cacheStatElList = $list;
        return $list;
    }


    private $_cacheCurrencyList = null;

    /**
     * @return CatalogCurrencyList
     */
    public function CurrencyList($clearCache = false){
        if (!$this->IsViewRole()){
            return false;
        }

        if ($clearCache){
            $this->_cacheCurrencyList = null;
        }

        if (!is_null($this->_cacheCurrencyList)){
            return $this->_cacheCurrencyList;
        }

        $list = new CatalogCurrencyList();

        $rows = CatalogQuery::CurrencyList($this->db, $this->pfx);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new CatalogCurrency($d));
        }

        $this->_cacheCurrencyList = $list;

        return $list;
    }

    public function CurrencySave($currencyId, $d){
        if (!$this->IsAdminRole()){
            return null;
        }

        $d = array_to_object($d);

        $utmf = Abricos::TextParser(true);

        $currencyId = intval($currencyId);
        $d->title = $utmf->Parser($d->title);
        $d->codestr = $utmf->Parser($d->codestr);
        $d->codenum = intval($d->codenum);
        $d->prefix = $utmf->Parser($d->prefix);
        $d->postfix = $utmf->Parser($d->postfix);

        $d->rateval = doubleval($d->rateval);
        $d->ratedate = TIMENOW;

        $d->ord = 0;

        if ($currencyId == 0){
            $currencyId = CatalogQuery::CurrencyAppend($this->db, $this->pfx, $d);
        } else {
            CatalogQuery::CurrencyUpdate($this->db, $this->pfx, $d);
        }

        return $currencyId;
    }

    public function CurrencyRemove($currencyId){
        if (!$this->IsAdminRole()){
            return null;
        }

        CatalogQuery::CurrencyRemove($this->db, $this->pfx, $currencyId);
    }

    public function CurrencyRemoveToAJAX($currencyId){
        $this->CurrencyRemove($currencyId);
        return $this->CurrencyListToAJAX(true);
    }

    public function CurrencyDefaultToAJAX(){
        $currency = $this->CurrencyDefault();
        $ret = new stdClass();
        $ret->currency = $currency->ToAJAX($this);
        return $ret;
    }

    private $_cacheCurrencyDefault;

    /**
     * @return CatalogCurrency
     */
    public function CurrencyDefault(){
        if (isset($this->_cacheCurrencyDefault)){
            return $this->_cacheCurrencyDefault;
        }
        $list = $this->CurrencyList();
        if (empty($list) || $list->Count() == 0){
            $this->_cacheCurrencyDefault = new CatalogCurrency(array());
            return $this->_cacheCurrencyDefault;
        }

        for ($i = 0; $i < $list->Count(); $i++){
            $currency = $list->GetByIndex($i);
            if ($currency->isDefault){
                $this->_cacheCurrencyDefault = $currency;
                return $currency;
            }
        }
        $this->_cacheCurrencyDefault = $list->GetByIndex(0);
        return $this->_cacheCurrencyDefault;
    }
}

?>