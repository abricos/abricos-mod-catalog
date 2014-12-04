<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
Abricos::GetModule('catalog');
$updateManager = CatalogModule::$instance->updateShemaModule;
$db = Abricos::$db;
$modPrefix = $updateManager->module->catinfo['dbprefix']."_";
$pfx = Abricos::$db->prefix."ctg_".$modPrefix;

if ($updateManager->isInstall()) {

    // Каталог
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."catalog` (
			`catalogid` int(10) UNSIGNED NOT NULL auto_increment,
			`parentcatalogid` int(10) UNSIGNED NOT NULL default '0' COMMENT 'ID родителя',
			`name` varchar(250) NOT NULL default '',
			`title` varchar(250) NOT NULL default '',
			`descript` text NOT NULL COMMENT 'Описание',
			`metatitle` varchar(250) NOT NULL default '' COMMENT 'Тег title',
			`metakeys` varchar(250) NOT NULL default '' COMMENT 'Тег keywords',
			`metadesc` varchar(250) NOT NULL default '' COMMENT 'Тег description',
            `menudisable` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT '1-отключено из меню',
            `listdisable` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT '1-отключено из списка',
			`level` int(2) UNSIGNED NOT NULL default '0' COMMENT 'Уровень вложений',
			`ord` int(3) NOT NULL default '0' COMMENT 'Сортировка',
			
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления',
			`deldate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата удаления',
			
			PRIMARY KEY  (`catalogid`),
			KEY `deldate` (`deldate`)
		)".$charset);

    // справочник
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."dict` (
			`dictid` int(5) UNSIGNED NOT NULL auto_increment,
			`title` varchar(250) NOT NULL default '',
			`name` varchar(250) NOT NULL default '',
			`descript` text NOT NULL COMMENT 'Описание',
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления',
			`deldate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата удаления',
		
			PRIMARY KEY (`dictid`)
		)".$charset);

    // Элементы каталога
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."element` (
			`elementid` int(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор записи',
			`catalogid` int(10) UNSIGNED NOT NULL COMMENT 'Категория',
			`eltypeid` int(5) UNSIGNED NOT NULL COMMENT 'Тип элемента',
			
			`userid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор добавленной записи',
			`ismoder` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-ожидает модерацию',
			
			`title` VARCHAR(250) NOT NULL default 'Название',
			`name` VARCHAR(250) NOT NULL default 'Имя',
			`ord` int(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользовательская сортировка',

			`version` int(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Версия записи',
			`isarhversion` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-есть новая версия, этот помещен в архив',
			`prevelementid` int(10) UNSIGNED NOT NULL COMMENT 'Предыдущая версия элемента',
			`changelog` text NOT NULL COMMENT 'Список изменений',
			
			`metatitle` varchar(250) NOT NULL default '' COMMENT 'Тег title',
			`metakeys` varchar(250) NOT NULL default '' COMMENT 'Тег keywords',
			`metadesc` varchar(250) NOT NULL default '' COMMENT 'Тег description',

			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата добавления',
			`upddate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата обновления',
			`deldate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата удаления',
		
			PRIMARY KEY  (`elementid`),
			KEY `name` (`name`),
			KEY `catalogid` (`catalogid`),
			KEY `element` (`language`, `ismoder`, `isarhversion`, `deldate`),
			KEY `ord` (`ord`)
		)".$charset);

    // тип элемента каталога
    // примечание: под каждый тип будет создана отдельная таблица с полями опций этого типа
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eltype` (
			`eltypeid` INT(5) UNSIGNED NOT NULL auto_increment,
			`name` VARCHAR(250) NOT NULL default '',
			`title` VARCHAR(250) NOT NULL default '' COMMENT 'Название',
			`titlelist` VARCHAR(250) NOT NULL default '' COMMENT 'Название списка',
			`descript` text NOT NULL COMMENT 'Описание',
			`fotouse` int(1) UNSIGNED NOT NULL default '0' COMMENT 'В опциях элемента есть фотографии, по умолчанию - нет',

			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления',
			`deldate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата удаления',
		
			PRIMARY KEY  (`eltypeid`),
			KEY `eltype` (`language`, `deldate`)
		)".$charset);

    // группа опций типа элемента каталога
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eloptgroup` (
			`eloptgroupid` int(5) UNSIGNED NOT NULL auto_increment,
			`parenteloptgroupid` int(5) UNSIGNED NOT NULL default '0',
			`eltypeid` int(5) UNSIGNED NOT NULL default '0' COMMENT 'Тип элемента',
			`name` varchar(50) NOT NULL default '' COMMENT 'Имя (идентификатор)',
			`title` varchar(250) NOT NULL default '',
			`descript` text NOT NULL COMMENT 'Описание',
			`ord` int(5) NOT NULL default '0' COMMENT 'Сортировка',
			`issystem` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT 'Системная группа',
			
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			PRIMARY KEY (`eloptgroupid`)
		)".$charset);

    // опции типа элемента каталога
    // fieldtype - тип поля: 0-boolean, 1-число, 2-дробное, 3-строка, 4-список, 5-таблица, 6-мульти
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eloption` (
			`eloptionid` int(5) UNSIGNED NOT NULL auto_increment,
			`eltypeid` int(5) UNSIGNED NOT NULL default '0' COMMENT 'Тип элемента',
			`eloptgroupid` int(5) UNSIGNED NOT NULL default '0' COMMENT 'Группа',
			`fieldtype` int(1) UNSIGNED NOT NULL default '0' COMMENT 'Тип поля',
			`fieldsize` varchar(50) NOT NULL default '' COMMENT 'Размер поля',
			`param` text NOT NULL COMMENT 'Параметры опции',
			`name` varchar(50) NOT NULL default 'имя поля',
			`title` varchar(250) NOT NULL default '',
			`descript` text NOT NULL COMMENT 'Описание',
			`eltitlesource` int(1) NOT NULL default '0' COMMENT '1-элемент является составной частью названия элемента',
			`ord` int(5) NOT NULL default '0' COMMENT 'Сортировка',
			`issystem` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-системная опция',
			`disable` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-опция отключена',
			
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления',
			`deldate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата удаления',
			
			PRIMARY KEY  (`eloptionid`),
			KEY `eloption` (`language`, `deldate`)
		)".$charset);

    // картинки элементов
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."foto` (
			`fotoid` int(10) UNSIGNED NOT NULL auto_increment,
			`elementid` int(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			`fileid` varchar(8) NOT NULL,
			`ord` int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления',
			PRIMARY KEY (`fotoid`),
			KEY `elementid` (`elementid`)
		)".$charset);

}

if ($updateManager->isUpdate('0.2.2')) {
    $db->query_write("
		ALTER TABLE `".$pfx."catalog` 
		ADD `imageid`  varchar(8) NOT NULL DEFAULT '' COMMENT 'Картника'");
}

if ($updateManager->isUpdate('0.2.5.1') && !$updateManager->isInstall()) {

    $db->query_write("
		ALTER TABLE `".$pfx."element` 
		ADD `ord` int(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользовательская сортировка',
			
		ADD `metatitle` varchar(250) NOT NULL default '' COMMENT 'Тег title',
		ADD `metakeys` varchar(250) NOT NULL default '' COMMENT 'Тег keywords',
		ADD `metadesc` varchar(250) NOT NULL default '' COMMENT 'Тег description',
			
		ADD KEY `element` (`catalogid`, `deldate`),
		ADD KEY `ord` (`ord`)
	");

    $db->query_write("
		ALTER TABLE `".$pfx."catalog`
		ADD KEY `deldate` (`deldate`)
	");

    $db->query_write("
		ALTER TABLE `".$pfx."eloption`
		ADD `issystem` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-системная опция'
	");

}

if ($updateManager->isUpdate('0.2.5.2') && !$updateManager->isInstall()) {
    $db->query_write("
		ALTER TABLE `".$pfx."eloption`
		ADD `fieldsize` varchar(50) NOT NULL default '' COMMENT 'Размер поля'
	");
}
if ($updateManager->isUpdate('0.2.5.2')) {
    $rows = $db->query_read("SELECT * FROM `".$pfx."eloption`");

    while (($row = $db->fetch_array($rows))) {
        $d = json_decode($row['param']);
        $db->query_write("
			UPDATE `".$pfx."eloption`
			SET fieldsize='".$d->size."'
			WHERE eloptionid=".$row['eloptionid']."
		");
    }
}

if ($updateManager->isUpdate('0.2.5.3') && !$updateManager->isInstall()) {

    // группирование опций элемента
    $db->query_write("
		ALTER TABLE `".$pfx."eloptgroup`
		ADD `name` varchar(50) NOT NULL default '' COMMENT 'Имя (идентификатор)',
		ADD `issystem` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT 'Системная группа'
	");

}

if ($updateManager->isUpdate('0.2.6') && !$updateManager->isInstall()) {
    $db->query_write("
		ALTER TABLE `".$pfx."catalog`
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		DROP INDEX `deldate`,
		ADD INDEX `catalog` (`language`, `deldate`)
	");
    $db->query_write("UPDATE `".$pfx."catalog` SET language='ru' ");

    $db->query_write("
		ALTER TABLE `".$pfx."eltype`
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		ADD INDEX `eltype` (`language`, `deldate`)
	");
    $db->query_write("UPDATE `".$pfx."eltype` SET language='ru' ");

    $db->query_write("
		ALTER TABLE `".$pfx."eloptgroup`
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE `".$pfx."eloptgroup` SET language='ru' ");

    $db->query_write("
		ALTER TABLE `".$pfx."eloption`
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		ADD INDEX `eloption` (`language`, `deldate`)
	");
    $db->query_write("UPDATE `".$pfx."eloption` SET language='ru' ");

    // добавлен автор элемента, модерация элемента, версионность элементов
    $db->query_write("
		ALTER TABLE `".$pfx."element`
		ADD userid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор добавленной записи',
		ADD `ismoder` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-ожидает модерацию',

		ADD `isarhversion` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-есть новая версия, этот помещен в архив',
		ADD `version` int(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Версия записи',
		ADD `prevelementid` int(10) UNSIGNED NOT NULL COMMENT 'Предыдущая версия элемента',
		ADD `changelog` text NOT NULL COMMENT 'Список изменений',
			
		ADD `upddate` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата обновления',
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',

		ADD INDEX `name` (`name`),
		ADD INDEX `catalogid` (`catalogid`),
		DROP INDEX `element`,
		ADD INDEX `element` (`language`, `ismoder`, `isarhversion`, `deldate`)
	");
    $db->query_write("
		UPDATE `".$pfx."element`
		SET upddate=dateline, language='ru'
	");

    // добавлен автор элемента, модерация элемента, версионность элементов
    $db->query_write("
		ALTER TABLE `".$pfx."foto`
		ADD `dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'дата добавления'
	");

    $db->query_write("DROP TABLE IF EXISTS `".$pfx."link`");
    $db->query_write("DROP TABLE IF EXISTS `".$pfx."catalogcfg`");
    $db->query_write("DROP TABLE IF EXISTS `".$pfx."session`");
}

if ($updateManager->isUpdate('0.2.6')) {

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eldepends` (
			`eldependsid` int(10) UNSIGNED NOT NULL auto_increment,
			`eloptionid` int(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			`elementid` int(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			`elementdepid` int(10) UNSIGNED NOT NULL COMMENT 'Ссылается на элемент - id',
			`ord` int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',

			PRIMARY KEY (`eldependsid`),
			KEY `dep` (`eloptionid`, `elementid`) 
		)".$charset);

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."eldependsname` (
			`eldependsnameid` int(10) UNSIGNED NOT NULL auto_increment,
			`eloptionid` int(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			`elementid` int(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			`elementdepname` varchar(250) NOT NULL COMMENT 'Ссылается на элемент - имя',
			`ord` int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',
	
			PRIMARY KEY (`eldependsnameid`),
			KEY `dep` (`eloptionid`, `elementid`)
		)".$charset);

    // файлы элементов по опциям
    $db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."file` (
			`fileid` int(10) UNSIGNED NOT NULL auto_increment,
			`eloptionid` int(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			`elementid` int(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			`userid` int(10) UNSIGNED NOT NULL COMMENT 'Пользователь',
			`filehash` varchar(8) NOT NULL COMMENT 'Идентификатор файла',
			`filename` varchar(250) NOT NULL COMMENT 'Имя файла',
			`ord` int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата добавления',
			PRIMARY KEY (`fileid`),
			KEY `elementid` (`elementid`), 
			KEY `file` (`eloptionid`, `elementid`) 
		)".$charset);

}

if ($updateManager->isUpdate('0.2.7.1') && !$updateManager->isInstall()) {
    $db->query_write("
		ALTER TABLE `".$pfx."eltype`
		ADD `titlelist` VARCHAR(250) NOT NULL default '' COMMENT 'Название списка'
	");
    $db->query_write("UPDATE `".$pfx."eltype` SET titlelist=title ");
}

if ($updateManager->isUpdate('0.2.9') && !$updateManager->isInstall()) {
    $db->query_write("
		ALTER TABLE `".$pfx."catalog`
		ADD `menudisable` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT '1-отключено из меню',
		ADD `listdisable` tinyint(1) UNSIGNED NOT NULL default 0 COMMENT '1-отключено из списка'
	");
}

if ($updateManager->isUpdate('0.3.0')) {

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."currency (
			currencyid int(10) UNSIGNED NOT NULL auto_increment,

			isdefault tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Цифровой код',

			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Название',

			codestr varchar(3) NOT NULL DEFAULT '' COMMENT 'Код',
			codenum int(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Цифровой код',

			rateval double(10,6) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Текущий курс',
			ratedate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата курса',

			prefix varchar(20) NOT NULL DEFAULT '' COMMENT 'Префикс',
			postfix varchar(20) NOT NULL DEFAULT '' COMMENT 'Постфикс',

			ord int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',

			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',

			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			deldate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

			PRIMARY KEY (currencyid),
            KEY currency (deldate, language)

		)".$charset);

    require_once 'dbquery.php';

    CatalogDbQuery::CurrencyAppend($db, $pfx, array(
        "isdefault" => true,
        "title" => "Российский рубль",
        "codestr" => "RUR",
        "codenum" => 810,
        "rateval" => 0,
        "ratedate" => 0,
        "prefix" => "",
        "postfix" => "руб.",
        "ord" => 0,
    ));


}

?>