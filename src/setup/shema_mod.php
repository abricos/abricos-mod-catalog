<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2012-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
Abricos::GetModule('catalog');
$updateManager = CatalogModule::$instance->updateShemaModule;
$db = Abricos::$db;
$modPrefix = $updateManager->module->catinfo['dbprefix']."_";
$pfx = Abricos::$db->prefix."ctg_".$modPrefix;

if ($updateManager->isInstall()){

    // Каталог
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."catalog (
			catalogid INT(10) UNSIGNED NOT NULL auto_increment,
			parentcatalogid INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID родителя',
			name varchar(250) NOT NULL DEFAULT '',
			title varchar(250) NOT NULL DEFAULT '',
			descript TEXT NOT NULL COMMENT 'Описание',
			metatitle varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег title',
			metakeys varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег keywords',
			metadesc varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег description',
            menudisable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-отключено из меню',
            listdisable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-отключено из списка',
			level INT(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Уровень вложений',
			ord INT(3) NOT NULL DEFAULT '0' COMMENT 'Сортировка',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата удаления',
			
			PRIMARY KEY  (catalogid),
			KEY deldate (deldate)
		)".$charset);

    // справочник
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."dict (
			dictid INT(5) UNSIGNED NOT NULL auto_increment,
			title varchar(250) NOT NULL DEFAULT '',
			name varchar(250) NOT NULL DEFAULT '',
			descript TEXT NOT NULL COMMENT 'Описание',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата удаления',
		
			PRIMARY KEY (dictid)
		)".$charset);

    // Элементы каталога
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."element (
			elementid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор записи',
			catalogid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Категория',
			eltypeid INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Тип элемента',
			
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор добавленной записи',
			ismoder TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-ожидает модерацию',
			
			title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название',
			name VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Имя',
			ord INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользовательская сортировка',

			version INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Версия записи',
			isarhversion TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-есть новая версия, этот помещен в архив',
			prevelementid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Предыдущая версия элемента',
			changelog TEXT NOT NULL COMMENT 'Список изменений',
			
			metatitle varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег title',
			metakeys varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег keywords',
			metadesc varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег description',

			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата добавления',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
		
			PRIMARY KEY  (elementid),
			KEY name (name),
			KEY catalogid (catalogid),
			KEY element (language, ismoder, isarhversion, deldate),
			KEY ord (ord)
		)".$charset);

    // тип элемента каталога
    // примечание: под каждый тип будет создана отдельная таблица с полями опций этого типа
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eltype (
			eltypeid INT(5) UNSIGNED NOT NULL auto_increment,
			name VARCHAR(250) NOT NULL DEFAULT '',
			title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название',
			titlelist VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название списка',
			descript TEXT NOT NULL COMMENT 'Описание',
			fotouse INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'В опциях элемента есть фотографии, по умолчанию - нет',

			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата удаления',
		
			PRIMARY KEY  (eltypeid),
			KEY eltype (language, deldate)
		)".$charset);

    // группа опций типа элемента каталога
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eloptgroup (
			eloptgroupid INT(5) UNSIGNED NOT NULL auto_increment,
			parenteloptgroupid INT(5) UNSIGNED NOT NULL DEFAULT '0',
			eltypeid INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Тип элемента',
			name varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя (идентификатор)',
			title varchar(250) NOT NULL DEFAULT '',
			descript TEXT NOT NULL COMMENT 'Описание',
			ord INT(5) NOT NULL DEFAULT '0' COMMENT 'Сортировка',
			issystem TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Системная группа',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			PRIMARY KEY (eloptgroupid)
		)".$charset);

    // опции типа элемента каталога
    // fieldtype - тип поля: 0-boolean, 1-число, 2-дробное, 3-строка, 4-список, 5-таблица, 6-мульти
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eloption (
			eloptionid INT(5) UNSIGNED NOT NULL auto_increment,
			eltypeid INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Тип элемента',
			eloptgroupid INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Группа',
			fieldtype INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Тип поля',
			fieldsize varchar(50) NOT NULL DEFAULT '' COMMENT 'Размер поля',
			currencyid INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Идентификатор валюты для денежного типа',
			param TEXT NOT NULL COMMENT 'Параметры опции',
			name varchar(50) NOT NULL DEFAULT 'имя поля',
			title varchar(250) NOT NULL DEFAULT '',
			descript TEXT NOT NULL COMMENT 'Описание',
			eltitlesource INT(1) NOT NULL DEFAULT '0' COMMENT '1-элемент является составной частью названия элемента',
			ord INT(5) NOT NULL DEFAULT '0' COMMENT 'Сортировка',
			issystem TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1-системная опция',
			disable TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1-опция отключена',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата удаления',
			
			PRIMARY KEY  (eloptionid),
			KEY eloption (language, deldate)
		)".$charset);

    // картинки элементов
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."foto (
			fotoid INT(10) UNSIGNED NOT NULL auto_increment,
			elementid INT(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			fileid varchar(8) NOT NULL,
			ord INT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сортировка',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления',
			PRIMARY KEY (fotoid),
			KEY elementid (elementid)
		)".$charset);

}

if ($updateManager->isUpdate('0.2.2')){
    $db->query_write("
		ALTER TABLE ".$pfx."catalog 
		ADD imageid  varchar(8) NOT NULL DEFAULT '' COMMENT 'Картника'");
}

if ($updateManager->isUpdate('0.2.5.1') && !$updateManager->isInstall()){

    $db->query_write("
		ALTER TABLE ".$pfx."element 
		ADD ord INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользовательская сортировка',
			
		ADD metatitle varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег title',
		ADD metakeys varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег keywords',
		ADD metadesc varchar(250) NOT NULL DEFAULT '' COMMENT 'Тег description',
			
		ADD KEY element (catalogid, deldate),
		ADD KEY ord (ord)
	");

    $db->query_write("
		ALTER TABLE ".$pfx."catalog
		ADD KEY deldate (deldate)
	");

    $db->query_write("
		ALTER TABLE ".$pfx."eloption
		ADD issystem TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1-системная опция'
	");

}

if ($updateManager->isUpdate('0.2.5.2') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."eloption
		ADD fieldsize varchar(50) NOT NULL DEFAULT '' COMMENT 'Размер поля'
	");
}

if ($updateManager->isUpdate('0.2.5.2')){
    $rows = $db->query_read("SELECT * FROM ".$pfx."eloption");

    while (($row = $db->fetch_array($rows))){
        $d = json_decode($row['param']);
        $d->size = isset($d->size) ? $d->size : "";
        $db->query_write("
			UPDATE ".$pfx."eloption
			SET fieldsize='".$d->size."'
			WHERE eloptionid=".$row['eloptionid']."
		");
    }
}

if ($updateManager->isUpdate('0.2.5.3') && !$updateManager->isInstall()){

    // группирование опций элемента
    $db->query_write("
		ALTER TABLE ".$pfx."eloptgroup
		ADD name varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя (идентификатор)',
		ADD issystem TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Системная группа'
	");

}

if ($updateManager->isUpdate('0.2.6') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."catalog
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		DROP INDEX deldate,
		ADD INDEX catalog (language, deldate)
	");
    $db->query_write("UPDATE ".$pfx."catalog SET language='ru' ");

    $db->query_write("
		ALTER TABLE ".$pfx."eltype
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		ADD INDEX eltype (language, deldate)
	");
    $db->query_write("UPDATE ".$pfx."eltype SET language='ru' ");

    $db->query_write("
		ALTER TABLE ".$pfx."eloptgroup
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE ".$pfx."eloptgroup SET language='ru' ");

    $db->query_write("
		ALTER TABLE ".$pfx."eloption
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		ADD INDEX eloption (language, deldate)
	");
    $db->query_write("UPDATE ".$pfx."eloption SET language='ru' ");

    // добавлен автор элемента, модерация элемента, версионность элементов
    $db->query_write("
		ALTER TABLE ".$pfx."element
		ADD userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор добавленной записи',
		ADD ismoder TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-ожидает модерацию',

		ADD isarhversion TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-есть новая версия, этот помещен в архив',
		ADD version INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Версия записи',
		ADD prevelementid INT(10) UNSIGNED NOT NULL COMMENT 'Предыдущая версия элемента',
		ADD changelog TEXT NOT NULL COMMENT 'Список изменений',
			
		ADD upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',

		ADD INDEX name (name),
		ADD INDEX catalogid (catalogid),
		DROP INDEX element,
		ADD INDEX element (language, ismoder, isarhversion, deldate)
	");
    $db->query_write("
		UPDATE ".$pfx."element
		SET upddate=dateline, language='ru'
	");

    // добавлен автор элемента, модерация элемента, версионность элементов
    $db->query_write("
		ALTER TABLE ".$pfx."foto
		ADD dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'дата добавления'
	");

    $db->query_write("DROP TABLE IF EXISTS ".$pfx."link");
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."catalogcfg");
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."session");
}

if ($updateManager->isUpdate('0.2.6')){

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eldepends (
			eldependsid INT(10) UNSIGNED NOT NULL auto_increment,
			eloptionid INT(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			elementid INT(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			elementdepid INT(10) UNSIGNED NOT NULL COMMENT 'Ссылается на элемент - id',
			ord INT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сортировка',

			PRIMARY KEY (eldependsid),
			KEY dep (eloptionid, elementid) 
		)".$charset);

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."eldependsname (
			eldependsnameid INT(10) UNSIGNED NOT NULL auto_increment,
			eloptionid INT(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			elementid INT(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			elementdepname varchar(250) NOT NULL COMMENT 'Ссылается на элемент - имя',
			ord INT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сортировка',
	
			PRIMARY KEY (eldependsnameid),
			KEY dep (eloptionid, elementid)
		)".$charset);

    // файлы элементов по опциям
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."file (
			fileid INT(10) UNSIGNED NOT NULL auto_increment,
			eloptionid INT(5) UNSIGNED NOT NULL COMMENT 'Идентификатор опции элемента',
			elementid INT(10) UNSIGNED NOT NULL COMMENT 'Идентификатор элемента',
			userid INT(10) UNSIGNED NOT NULL COMMENT 'Пользователь',
			filehash varchar(8) NOT NULL COMMENT 'Идентификатор файла',
			filename varchar(250) NOT NULL COMMENT 'Имя файла',
			ord INT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сортировка',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Дата добавления',
			PRIMARY KEY (fileid),
			KEY elementid (elementid), 
			KEY file (eloptionid, elementid) 
		)".$charset);

}

if ($updateManager->isUpdate('0.2.7.1') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."eltype
		ADD titlelist VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Название списка'
	");
    $db->query_write("UPDATE ".$pfx."eltype SET titlelist=title ");
}

if ($updateManager->isUpdate('0.2.9') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."catalog
		ADD menudisable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-отключено из меню',
		ADD listdisable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-отключено из списка'
	");
}

if ($updateManager->isUpdate('0.3.0')){

    // Список идентификаторов на другие элементы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."currency (
			currencyid INT(10) UNSIGNED NOT NULL auto_increment,

			isDEFAULT TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Цифровой код',

			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Название',

			codestr varchar(3) NOT NULL DEFAULT '' COMMENT 'Код',
			codenum INT(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Цифровой код',

			rateval double(10,6) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Текущий курс',
			ratedate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата курса',

			prefix varchar(20) NOT NULL DEFAULT '' COMMENT 'Префикс',
			postfix varchar(20) NOT NULL DEFAULT '' COMMENT 'Постфикс',

			ord INT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Сортировка',

			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

			PRIMARY KEY (currencyid),
            KEY currency (deldate, language)

		)".$charset);

    CatalogDbQuery::CurrencyAppend($db, $pfx, array(
        "isdefault" => true,
        "title" => "Российский рубль",
        "codestr" => "RUR",
        "codenum" => 810,
        "rateval" => 1,
        "ratedate" => TIMENOW,
        "prefix" => "",
        "postfix" => "руб.",
        "ord" => 0,
    ));
}

if ($updateManager->isUpdate('0.3.1') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."eloption
		ADD currencyid INT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Идентификатор валюты для денежного типа'
	");
}

if ($updateManager->isUpdate('0.3.2') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."element 
		CHANGE catalogid catalogid INT(10) UNSIGNED NOT NULL DEFAULT 0,
		CHANGE eltypeid eltypeid INT(5) UNSIGNED NOT NULL DEFAULT 0,
		CHANGE prevelementid prevelementid INT(10) UNSIGNED NOT NULL DEFAULT 0 
	");
}
