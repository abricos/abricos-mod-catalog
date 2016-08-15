<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
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

    public function __construct(CatalogModule $module){
        parent::__construct($module);
    }
}
