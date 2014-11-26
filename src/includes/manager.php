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

    public function __construct(CatalogModule $module) {
        parent::__construct($module);
    }
}

?>