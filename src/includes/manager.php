<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CatalogManager
 *
 * @property CatalogModule $module
 */
class CatalogManager extends Ab_ModuleManager {

    public function AppClassesRequire(){
        require_once 'models.php';
        require_once 'dbquery.php';
        require_once 'app.php';
    }
}

?>