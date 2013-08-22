<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class CatalogDbQuery {

	public static function CatalogList(Ab_Database $db, $pfx){
		$sql = "
			(SELECT
				0 as id,
				-1 as pid,
				'' as nm,
				'' as tl,
				0 as dl,
				0 as lvl,
				0 as ord,
				'' as foto,
				'' as fext,
				(
					SELECT count(*) as cnt
					FROM ".$pfx."element e
					WHERE e.catalogid=0 AND e.deldate=0
					GROUP BY e.catalogid
				) as ecnt
			FROM ".$pfx."catalog cat
			LEFT JOIN ".$pfx."element e ON e.catalogid=0 AND e.deldate=0
			GROUP BY e.catalogid
			LIMIT 1)
							
			UNION
			
			(SELECT
				cat.catalogid as id,
				cat.parentcatalogid as pid,
				cat.name as nm,
				cat.title as tl,
				cat.dateline as dl,
				cat.level as lvl,
				cat.ord,
				cat.imageid as foto,
				IF (ISNULL(f.filehash), '', f.extension) as fext,
				(
					SELECT count(*) as cnt
					FROM ".$pfx."element e
					WHERE e.catalogid=cat.catalogid AND e.deldate=0
					GROUP BY e.catalogid
				) as ecnt
			FROM ".$pfx."catalog cat
			LEFT JOIN ".$db->prefix."fm_file f ON cat.imageid=f.filehash 
			WHERE cat.deldate=0)
			ORDER BY ord DESC, tl
		";
		return $db->query_read($sql);
	}
	
	
	public static function Catalog(Ab_Database $db, $pfx, $catid){
		if ($catid == 0){
			$sql = "
				SELECT
					0 as id,
					-1 as pid,
					'' as nm,
					'' as tl,
					0 as dl,
					0 as lvl,
					0 as ord, 
					count(*) as ecnt
				FROM ".$pfx."element e
				WHERE e.catalogid=0 AND e.deldate=0
			";
		}else{
			$sql = "
				SELECT
					cat.catalogid as id,
					cat.parentcatalogid as pid,
					cat.name as nm,
					cat.title as tl,
					cat.dateline as dl,
					cat.level as lvl,
					cat.ord,
					cat.imageid as foto,
					(
						SELECT count(*) as cnt
						FROM ".$pfx."element e
						WHERE e.catalogid=cat.catalogid AND e.deldate=0
						GROUP BY e.catalogid
					) as ecnt,
					
					cat.descript as dsc,
					cat.metatitle as mtl,
					cat.metakeys as mks,
					cat.metadesc as mdsc
					 
				FROM ".$pfx."catalog cat
				WHERE catalogid=".bkint($catid)." AND cat.deldate=0
				LIMIT 1
			";
		}
		return $db->query_first($sql);
	}
	
	public static function CatalogAppend(Ab_Database $db, $pfx, $d){
		$sql = "
			INSERT INTO ".$pfx."catalog
				(parentcatalogid, title, name, imageid, descript, metatitle, metakeys, metadesc, ord, dateline) VALUES (
				".bkint($d->pid).",
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->foto)."',
				'".bkstr($d->dsc)."',
				'".bkstr($d->mtl)."',
				'".bkstr($d->mks)."',
				'".bkstr($d->mdsc)."',
				".bkint($d->ord).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function CatalogUpdate(Ab_Database $db, $pfx, $catid, $d){
		$sql = "
			UPDATE ".$pfx."catalog
			SET
				parentcatalogid=".bkint($d->pid).",
				title='".bkstr($d->tl)."',
				name='".bkstr($d->nm)."',
				imageid='".bkstr($d->foto)."',
				descript='".bkstr($d->dsc)."',
				metatitle='".bkstr($d->mtl)."',
				metakeys='".bkstr($d->mks)."',
				metadesc='".bkstr($d->mdsc)."',
				ord=".bkint($d->ord)."
			WHERE catalogid=".bkint($catid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function CatalogRemove(Ab_Database $db, $pfx, $catid){
		$sql = "
			UPDATE ".$pfx."catalog
			SET deldate=".TIMENOW."
			WHERE catalogid=".bkint($catid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function ElementList(Ab_Database $db, $pfx, CatalogElementListConfig $cfg){
		
		$wCats = array();
		foreach ($cfg->catids as $catid){
			array_push($wCats, "e.catalogid=".bkint($catid));
		}

		$wEls = array();
		foreach ($cfg->elids as $elid){
			array_push($wEls, "e.elementid=".bkint($elid));
		}
		
		$orders = "";
		$cnt = $cfg->orders->Count();
		for ($i=0; $i<$cnt; $i++){
			$ord = $cfg->orders->GetByIndex($i);
			if ($ord->option->elTypeId > 0){ continue; }

			if ($ord->zeroDesc){
				$fld = "fld_".$ord->option->name;
				$orders .= ", IF(".$fld.">0, 0, 1), ".$fld;
				
			}else{
				$orders .= ", fld_".$ord->option->name;
				if ($ord->isDesc){
					$orders .= " DESC";
				}
			}
			
			// TODO: добавить сортировку по типу поля - таблица	
		}
		
		$extFields = "";
		$cnt = $cfg->extFields->Count();
		for ($i=0; $i<$cnt; $i++){
			$option = $cfg->extFields->GetByIndex($i);
			if ($option->elTypeId > 0){ continue; }

			$extFields .= ", e.fld_".$option->name;
		}
		
		$wExt = array();
		$cnt = $cfg->where->Count();
		for ($i=0; $i<$cnt; $i++){
			$ord = $cfg->where->GetByIndex($i);
			
			if ($ord->option->elTypeId > 0){ continue; }
			
			array_push($wExt, "e.fld_".$ord->option->name."".$ord->exp);
		}
		
		$sql = "
			SELECT
				e.elementid as id,
				e.catalogid as catid,
				e.eltypeid as tpid,
				e.title as tl,
				e.name as nm,
				e.ord as ord,
				(
					SELECT f.fileid
					FROM ".$pfx."foto f
					WHERE f.elementid=e.elementid
					ORDER BY ord
					LIMIT 1
				) as foto
				".$extFields."
			FROM ".$pfx."element e
			WHERE e.deldate=0 
		";
		
		$isWhere = false;
		if (count($wCats) > 0){
			$sql .= "
				AND (".implode(" OR ", $wCats).")
			";
			$isWhere = true;
		}
		if (count($wEls) > 0){
			$sql .= "
				AND (".implode(" OR ", $wEls).")
			";
			$isWhere = true;
		}
		if (count($wExt) > 0){
			$sql .= "
				AND (".implode(" OR ", $wExt).")
			";
			$isWhere = true;
		}
		
		if (!$isWhere){ return null; }
		
		$sql .= "
		 	ORDER BY ord DESC".$orders.", e.dateline
		";
		
		if ($cfg->limit > 0){
			$from = $cfg->limit * (max(1, $cfg->page) - 1);
			$sql .= "
				LIMIT ".$from.", ".$cfg->limit."
			";
		}
		return $db->query_read($sql);
	}
	
	public static function ElementListCount(Ab_Database $db, $pfx, CatalogElementListConfig $cfg){
		$wCats = array();
		foreach ($cfg->catids as $catid){
			array_push($wCats, "e.catalogid=".bkint($catid));
		}
		$wEls = array();
		foreach ($cfg->elids as $elid){
			array_push($wEls, "e.elementid=".bkint($elid));
		}
		
		if (count($wCats) == 0 && count($wEls) == 0){ return 0; }
		
		$sql = "
			SELECT count(*) as cnt
			FROM ".$pfx."element e
			WHERE e.deldate=0 
		";
		
		$isWhere = false;
		if (count($wCats) > 0){
			$sql .= "
				AND (".implode(" OR ", $wCats).")
			";
			$isWhere = true;
		}
		if (count($wEls) > 0){
			$sql .= "
				AND (".implode(" OR ", $wEls).")
			";
			$isWhere = true;
		}
		
		
		$row = $db->query_first($sql);
		return intval($row['cnt']);
	}
	
	public static function Element(Ab_Database $db, $pfx, $elementid){
		$sql = "
			SELECT
				e.elementid as id,
				e.catalogid as catid,
				e.eltypeid as tpid,
				e.title as tl,
				e.name as nm,
				e.metatitle as mtl,
				e.metakeys as mks,
				e.metadesc as mdsc,
				e.ord as ord,
				(
					SELECT f.fileid
					FROM ".$pfx."foto f
					WHERE f.elementid=e.elementid
					ORDER BY ord
					LIMIT 1
				) as foto
			FROM ".$pfx."element e
			WHERE e.elementid=".bkint($elementid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementDetail(Ab_Database $db, $pfx, $elid, CatalogElementType $elType){
		$options = $elType->options;
		$fields = array();
		for ($i=0; $i<$options->Count(); $i++){
			$option = $options->GetByIndex($i);
			array_push($fields, "e.fld_".$option->name." as `".$option->name."`");
		}
		$sql = "
			SELECT
				e.elementid as id
				".(count($fields)>0?",".implode(",", $fields):"")."
			FROM ".$pfx.$elType->tableName." e
			WHERE e.elementid=".bkint($elid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementAppend(Ab_Database $db, $pfx, $d){
		$sql = "
			INSERT INTO ".$pfx."element
			(catalogid, eltypeid, title, name, metatitle, metakeys, metadesc, ord, dateline) VALUES (
				".bkint($d->catid).",
				".bkint($d->tpid).",
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->mtl)."',
				'".bkstr($d->mks)."',
				'".bkstr($d->mdsc)."',
				".bkint($d->ord).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementUpdate(Ab_Database $db, $pfx, $elid, $d){
		$sql = "
			UPDATE ".$pfx."element
			SET
				catalogid=".bkint($d->catid).",
				eltypeid=".bkint($d->tpid).",
				title='".bkstr($d->tl)."',
				name='".bkstr($d->nm)."',
				metatitle='".bkstr($d->mtl)."',
				metakeys='".bkstr($d->mks)."',
				metadesc='".bkstr($d->mdsc)."',
				ord=".bkint($d->ord)."
			WHERE elementid=".bkint($elid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function ElementRemove(Ab_Database $db, $pfx, $elid){
		$sql = "
			UPDATE ".$pfx."element
			SET deldate=".TIMENOW."
			WHERE elementid=".bkint($elid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}

	public static function ElementListRemoveByCatId(Ab_Database $db, $pfx, $catid){
		$sql = "
			UPDATE ".$pfx."element
			SET deldate=".TIMENOW."
			WHERE catalogid=".bkint($catid)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementDetailUpdate(Ab_Database $db, $pfx, $elid, CatalogElementType $elType, $d){
		$options = $elType->options;
		if ($options->Count() == 0){ return; }
		
		$insfld = array(); $insval = array(); $upd = array();
		
		$utm = Abricos::TextParser();
		$utmf = Abricos::TextParser(true);
		
		for ($i=0; $i<$options->Count(); $i++){
			$option = $options->GetByIndex($i);
			$name = $option->name;
			
			$val = $d->$name;
			
			switch($option->type){
				case Catalog::TP_BOOLEAN:
					$val = empty($val) ? 0 : 1;
					break;
				case Catalog::TP_NUMBER:
					$val = bkint($val);
					break;
				case Catalog::TP_DOUBLE:
					$val = doubleval($val);
					break;
				case Catalog::TP_STRING:
					$val = "'".bkstr($utmf->Parser($val))."'";
					break;
				case Catalog::TP_TABLE:
					$val = bkint($val);
					break;
				case Catalog::TP_TEXT:
					$val = "'".bkstr($utm->Parser($val))."'";
					break;
				default: 
					$val = bkstr($val);
					 break;
			}
			array_push($insfld, "fld_".$name);
			array_push($insval, $val);
			array_push($upd, "fld_".$name."=". $val);
		}
		
		$sql = "
			INSERT INTO ".$pfx.$elType->tableName."
			(elementid, ".implode(", ", $insfld).") VALUES (
				".bkint($elid).",
				".implode(", ", $insval)."
			)ON DUPLICATE KEY UPDATE
				".implode(", ", $upd)."
		";
		$db->query_write($sql);
	}

	public static function ElementOrderUpdate(Ab_Database $db, $pfx, $elid, $order){
		$sql = "
			UPDATE ".$pfx."element
			SET ord=".bkint($order)."
			WHERE elementid=".bkint($elid)."
			LIMIT 1
		";
		$db->query_write($sql);
		
	}
	
	public static function TableList(Ab_Database $db){
		$sql = "
			SHOW TABLES FROM ".$db->database."
		";
		return $db->query_read($sql);
	}
	
	public static function ElementTypeTableName($pfx, $name){
		if (empty($name)){
			return $pfx."element";
		}
		return $pfx."eltbl_".$name;
	}
	
	public static function ElementTypeTableCreate(Ab_Database $db, $tableName){
		$sql = "
			CREATE TABLE IF NOT EXISTS `".$tableName."` (
				`elementid` int(10) unsigned NOT NULL,
				PRIMARY KEY  (`elementid`)
			) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableChange(Ab_Database $db, $oldTableName, $newTableName){
		$sql = "
			RENAME TABLE `".$oldTableName."` TO `".$newTableName."`
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableRemove(Ab_Database $db, $tableName){
		$sql = "
			DROP TABLE IF EXISTS `".$tableName."`
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeAppend(Ab_Database $db, $pfx, $d){
		$sql = "
			INSERT INTO ".$pfx."eltype
				(name, title, descript) VALUES (
				'".bkstr($d->nm)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->dsc)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementTypeUpdate(Ab_Database $db, $pfx, $elTypeId, $d){
		$sql = "
			UPDATE ".$pfx."eltype
			SET
				name='".bkstr($d->nm)."',
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."'
			WHERE eltypeid=".bkint($elTypeId)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeRemove(Ab_Database $db, $pfx, $elTypeId){
		$sql = "
			DELETE FROM ".$pfx."eltype
			WHERE eltypeid=".bkint($elTypeId)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeList(Ab_Database $db, $pfx){
		$sql = "
			SELECT
				eltypeid as id,
				title as tl,
				name as nm,
				descript as dsc
			FROM ".$pfx."eltype t
			WHERE t.deldate=0
		";
		return $db->query_read($sql);
	}
	
	public static function ElementOptionRemove(Ab_Database $db, $pfx, $optionid){
		$sql = "
			DELETE FROM ".$pfx."eloption
			WHERE eloptionid=".bkint($optionid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionFieldRemove(Ab_Database $db, $pfx, CatalogElementType $elType, CatalogElementTypeOption $option){
		if ($option->type == Catalog::TP_TABLE){
			$tableName = $pfx."eltbl_".$elType->name."_fld_".$option->name;
			$sql = "DROP TABLE IF EXISTS `".$tableName."`";
			$db->query_write($sql);
			return;
		}
		
		$tableName = CatalogDbQuery::ElementTypeTableName($pfx, $elType->name);
		$sql = "
			ALTER TABLE `".$tableName."` DROP `fld_".$option->name."`
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionAppend(Ab_Database $db, $pfx, $d){
		$sql = "
			INSERT INTO ".$pfx."eloption
			(eltypeid, fieldtype, fieldsize, name, title, descript, dateline) VALUES (
				".bkint($d->tpid).",
				".bkint($d->tp).",
				'".bkstr($d->sz)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->tl)."',
				'".bkstr($d->dsc)."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementOptionUpdate(Ab_Database $db, $pfx, $optionid, $d){
		$sql = "
			UPDATE ".$pfx."eloption
			SET name='".bkstr($d->nm)."',
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."'
			WHERE eloptionid=".bkint($optionid)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionFieldUpdate(Ab_Database $db, $pfx, CatalogElementType $elType, $tableName, CatalogElementTypeOption $oldOption, $d){
		$optionName = bkstr($d->nm);
		
		if ($d->tp == Catalog::TP_TABLE){
			$tableName = $pfx."eltbl_".$elType->name."_fld_".$d->nm;
			$oldTableName = $pfx."eltbl_".$elType->name."_fld_".$oldOption;
			
			$sql = "
				RENAME TABLE ".$oldTableName." TO ".$tableName."
			";
			$db->query_write($sql);
			return;
		}
		$sql = "
			ALTER TABLE ".$tableName." CHANGE `fld_".$oldOption->name."` `fld_".$optionName."`
		";
		
		switch($oldOption->type){
			case Catalog::TP_BOOLEAN:
				$sql .= "INT(1) UNSIGNED NOT NULL DEFAULT 0";
				break;
			case Catalog::TP_NUMBER:
				$sql .= "INT(".$oldOption->size.") NOT NULL DEFAULT 0";
				break;
			case Catalog::TP_DOUBLE:
				$sql .= "DOUBLE(".$oldOption->size.") NOT NULL DEFAULT 0";
				break;
			case Catalog::TP_STRING:
				$sql .= "VARCHAR(".$oldOption->size.") NOT NULL DEFAULT ''";
				break;
			case Catalog::TP_TEXT:
				$sql .= "TEXT NOT NULL ";
				break;
		}
		$db->query_write($sql);
	}
	
	public static function ElementOptionFieldCreate(Ab_Database $db, $pfx, CatalogElementType $elType, $tableName, $d){
		$optionName = bkstr($d->nm);
		
		if ($d->tp == Catalog::TP_TABLE){
			$fldTableName = $pfx."eltbl_".$elType->name."_fld_".$d->nm;
			
			$sql = "
				CREATE TABLE IF NOT EXISTS `".$fldTableName."` (
					`".$optionName."id` int(10) unsigned NOT NULL auto_increment,
					`title` varchar(250) NOT NULL default '',
					PRIMARY KEY  (`".$optionName."id`)
				) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
			";
			$db->query_write($sql);
		}
		
		$sql = "ALTER TABLE ".$tableName." ADD `fld_".$optionName."` ";
		switch($d->tp){
		case Catalog::TP_BOOLEAN:
			$sql .= "INT(1) UNSIGNED NOT NULL DEFAULT 0";
			break;
		case Catalog::TP_NUMBER:
			$sql .= "INT(".$d->sz.") NOT NULL DEFAULT 0";
			break;
		case Catalog::TP_DOUBLE:
			$sql .= "DOUBLE(".$d->sz.") NOT NULL DEFAULT 0";
			break;
		case Catalog::TP_STRING:
			$sql .= "VARCHAR(".$d->sz.") NOT NULL DEFAULT ''";
			break;
		case Catalog::TP_TEXT:
			$sql .= "TEXT NOT NULL ";
			break;
		case Catalog::TP_TABLE:
			$sql .= "INT(10) NOT NULL DEFAULT 0";
			break;
		}
		$sql .= " COMMENT '".bkstr($d->tl)."'";
		$db->query_write($sql);
	}
	
	public static function ElementOptionTableCreate(Ab_Database $db, $elTypeTableName, $optionName){
		$optionName = bkstr($optionName);
		$tableName = $elTypeTableName."_fld_".$optionName;
		
		$sql = "
			CREATE TABLE IF NOT EXISTS `".$tableName."` (
				`".$optionName."id` int(10) unsigned NOT NULL auto_increment,
				`title` varchar(250) NOT NULL default '',
				 PRIMARY KEY  (`".$optionName."id`)
			) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeOptionList(Ab_Database $db, $pfx){
		$sql = "
			SELECT
				eloptionid as id,
				eltypeid as tpid,
				fieldtype as tp,
				fieldsize as sz,
				name as nm,
				title as tl,
				descript as dsc,
				ord as ord,
				
				eloptgroupid as gpid,
				param as prms,
				eltitlesource as ets,
				disable as dsb
			FROM ".$pfx."eloption
			WHERE deldate=0
			ORDER BY tpid, eloptgroupid, ord
		";
		return $db->query_read($sql);
	}
	
	public static function OptionTableValueList(Ab_Database $db, $pfx, $tpName, $optName){
		$tbl = $pfx."eltbl_".$tpName."_fld_".$optName;
		$sql = "
			SELECT
				".$optName."id as id,
				title as tl
			FROM ".$tbl."
			ORDER BY title
		";
		return $db->query_read($sql);
	}
	
	public static function OptionTableValueAppend(Ab_Database $db, $pfx, $tpName, $optName, $value){
		$tbl = $pfx."eltbl_".$tpName."_fld_".$optName;
		$sql = "
			INSERT INTO ".$tbl." (title) VALUES (
				'".bkstr($value)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function OptionTableValueUpdate(Ab_Database $db, $pfx, $tpName, $optName, $valueid, $value){
		$tbl = $pfx."eltbl_".$tpName."_fld_".$optName;
		$sql = "
			UPDATE ".$tbl." 
			SET title='".bkstr($value)."'
			WHERE ".$optName."id=".bkint($valueid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function OptionTableValueRemove(Ab_Database $db, $pfx, $tpName, $optName, $valueid){
		$tbl = $pfx."eltbl_".$tpName."_fld_".$optName;
		$sql = "
			DELETE FROM ".$tbl."
			WHERE ".$optName."id=".bkint($valueid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function FotoAddToBuffer(Ab_Database $db, $pfx, $fhash){
		$sql = "
			INSERT INTO ".$pfx."foto (fileid) VALUES (
				'".bkstr($fhash)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function ElementFotoList(Ab_Database $db, $pfx, $elementid){
		$sql = "
			SELECT
				fm.filehash as id,
				fm.filehash as f,
				fm.filename as nm,
				fm.extension as ext,
				fm.filesize as sz,
				fm.imgwidth as w,
				fm.imgheight as h
			FROM ".$pfx."foto f
			INNER JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
			WHERE f.elementid=".bkint($elementid)."
			ORDER BY f.ord
		";
		return $db->query_read($sql);		
	}
	
	public static function FotoRemoveFromBuffer(Ab_Database $db, $pfx, $foto){
		$sql = "
			DELETE FROM ".$pfx."foto WHERE fileid='".$foto."'
		";
		$db->query_write($sql);
	}
	
	public static function ElementFotoUpdate(Ab_Database $db, $pfx, $elementid, $fotos){

		// пометить все текущие фотки элемента на потенциальную зачистку (удаление)
		$sql = "
			UPDATE ".$pfx."foto
			SET elementid=0
			WHERE elementid=".bkint($elementid)."
		";
		$db->query_write($sql);
		
		// добавить новые/существующие фотки
		$vals = array();
		for ($i=0;$i<count($fotos);$i++){
			array_push($vals, "(".bkint($elementid).", '".bkstr($fotos[$i])."', ".$i.")");
		}
		
		if (count($vals) == 0){ return; }
		
		$sql = "
			INSERT INTO ".$pfx."foto (elementid, fileid, ord) VALUES
			".implode(", ", $vals)."
		";
		$db->query_write($sql);
		
		// отменить зачистку по существующим
		$sql = "
			SELECT 
				fileid as f, 
				count(*) as cnt
			FROM ".$pfx."foto
			GROUP BY fileid
		";
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			if ($row['cnt'] > 1){
				$sql = "
					DELETE FROM ".$pfx."foto
					WHERE elementid=0 AND fileid='".$row['f']."'
				";
				$db->query_write($sql);
			}
		}
	}
}



?>