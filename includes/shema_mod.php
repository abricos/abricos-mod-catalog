<?php
/**
* @version $Id$
* @package Abricos
* @subpackage Catalog
* @copyright Copyright (C) 2010 Abricos. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

$cms = CMSRegistry::$instance;
$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$modCatalog = $cms->modules->GetModule('catalog');
$updateManager = $modCatalog->updateShemaModule;
$db = CMSRegistry::$instance->db;
$pfx = $cms->db->prefix."ctg_".$updateManager->module->catinfo['dbprefix']."_";

if ($updateManager->isInstall()){
	
	// справочник
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."dict` (
		  `dictid` int(5) unsigned NOT NULL auto_increment,
		  `title` varchar(250) NOT NULL default '',
		  `name` varchar(250) NOT NULL default '',
		  `descript` text NOT NULL COMMENT 'Описание',
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY (`dictid`)
		)
	". $charset);
	
	// Элементы каталога
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."element` (
		  `elementid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор записи',
		  `catalogid` int(10) unsigned NOT NULL COMMENT 'Категория',
		  `eltypeid` int(5) unsigned NOT NULL COMMENT 'Тип элемента',
		  `title` VARCHAR(250) NOT NULL default 'Название',
		  `name` VARCHAR(250) NOT NULL default 'Имя',
		  
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY  (`elementid`)
		)
	". $charset);

	// тип элемента каталога
	// примечание: под каждый тип будет создана отдельная таблица с полями опций этого типа 
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eltype` (
		  `eltypeid` INT(5) UNSIGNED NOT NULL auto_increment,
		  `name` VARCHAR(250) NOT NULL default '',
		  `title` VARCHAR(250) NOT NULL default '',
		  `descript` text NOT NULL COMMENT 'Описание',
		  `fotouse` int(1) unsigned NOT NULL default '0' COMMENT 'В опциях элемента есть фотографии, по умолчанию - нет',
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY  (`eltypeid`)
		)
	". $charset);
	
	// группа опций типа элемента каталога
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eloptgroup` (
		  `eloptgroupid` int(5) unsigned NOT NULL auto_increment,
		  `parenteloptgroupid` int(5) unsigned NOT NULL default '0',
		  `eltypeid` int(5) unsigned NOT NULL default '0' COMMENT 'Тип элемента',
		  `title` varchar(250) NOT NULL default '',
		  `descript` text NOT NULL COMMENT 'Описание',
		  `ord` int(5) NOT NULL default '0' COMMENT 'Сортировка',
		  PRIMARY KEY (`eloptgroupid`)
		)
	". $charset);
	
	// опции типа элемента каталога
	// fieldtype - тип поля: 0-boolean, 1-число, 2-дробное, 3-строка, 4-список, 5-таблица, 6-мульти
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eloption` (
		  `eloptionid` int(5) unsigned NOT NULL auto_increment,
		  `eltypeid` int(5) unsigned NOT NULL default '0' COMMENT 'Тип элемента',
		  `eloptgroupid` int(5) unsigned NOT NULL default '0' COMMENT 'Группа',
		  `fieldtype` int(1) unsigned NOT NULL default '0' COMMENT 'Тип поля',
		  `param` text NOT NULL COMMENT 'Параметры опции в формате JSON',
		  `name` varchar(50) NOT NULL default 'имя поля',
		  `title` varchar(250) NOT NULL default '',
		  `descript` text NOT NULL COMMENT 'Описание',
		  `eltitlesource` int(1) NOT NULL default '0' COMMENT '1-элемент является составной частью названия элемента',
		  `ord` int(5) NOT NULL default '0' COMMENT 'Сортировка',
		  `disable` int(2) unsigned NOT NULL default '0' COMMENT 'Опция отключена',
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  PRIMARY KEY  (`eloptionid`)
		)
	". $charset);
	
	// Конфигурация каталога
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."catalogcfg` (
		  `catalogcfgid` INT(10) UNSIGNED NOT NULL auto_increment,
		  `level` INT(2) UNSIGNED NOT NULL default '0' COMMENT 'Уровень',
		  `leveltype` INT(1) UNSIGNED NOT NULL default '0' COMMENT 'Тип уровня: 0-динамический, 1-фиксированный',
		  `title` VARCHAR(250) NOT NULL default '',
		  `name` VARCHAR(250) NOT NULL default '',
		  `descript` TEXT NOT NULL COMMENT 'Описание',
		  `status` INT(1) UNSIGNED NOT NULL default '0' COMMENT 'Статус: 0-доступен, 1-закрыт',
		  PRIMARY KEY  (`catalogcfgid`)
		)
	". $charset);
	
	// Каталог
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."catalog` (
		  `catalogid` int(10) unsigned NOT NULL auto_increment,
		  `parentcatalogid` int(10) unsigned NOT NULL default '0' COMMENT 'ID родителя',
		  `name` varchar(250) NOT NULL default '',
		  `title` varchar(250) NOT NULL default '',
		  `descript` text NOT NULL COMMENT 'Описание',
		  `metatitle` varchar(250) NOT NULL default '' COMMENT 'Тег title',
		  `metakeys` varchar(250) NOT NULL default '' COMMENT 'Тег keywords',
		  `metadesc` varchar(250) NOT NULL default '' COMMENT 'Тег description',
		  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
		  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
		  `level` int(2) unsigned NOT NULL default '0' COMMENT 'Уровень вложений',
		  `ord` int(3) NOT NULL default '0' COMMENT 'Сортировка',
		  PRIMARY KEY  (`catalogid`)
		)
	".$charset);

		// картинки элементов
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."foto` (
		  `fotoid` int(10) unsigned NOT NULL auto_increment,
		  `elementid` int(10) unsigned NOT NULL COMMENT 'Идентификатор элемента',
		  `fileid` varchar(8) NOT NULL,
		  `ord` int(4) unsigned NOT NULL default '0' COMMENT 'Сортировка',
		  PRIMARY KEY (`fotoid`),
		  KEY `carid` (`elementid`)
		)
	". $charset);
	
	// Таблица сессий: необходима для хранение картинок, пока создается элемент 
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."session` (
		  `sessionid` int(10) unsigned NOT NULL auto_increment,
		  `session` varchar(32) NOT NULL,
		  `data` text NOT NULL,
		  PRIMARY KEY (`sessionid`)
		)
	". $charset);
}

if ($updateManager->isUpdate('0.2.2')){
	$db->query_write("
		ALTER TABLE `".$pfx."catalog` 
		ADD `imageid`  varchar(8) NOT NULL DEFAULT '' COMMENT 'Картника'"
	);
	
}

?>