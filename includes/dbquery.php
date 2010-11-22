<?php
/**
 * @version $Id: dbquery.php 687 2010-08-25 10:00:21Z roosit $
 * @package Abricos
 * @subpackage Catalog
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

/**
 * Термины:
 * Element - свободный элемент (один и тот же элемент может содержаться в нескольких разделов каталога, поэтому элемент назвается свободным).
 * ElementType - тип свободного элемента. Для каждого типа элемента создается своя таблица хранения элементов.
 * ElementOption - опция свободного элемента
 * Catalog - раздел в каталоге.
 * CatalogElement - элемент в каталоге (в таблице element связывает раздел каталога со свободным элементом). 
 */
class CatalogQuery {
	
	public static function CatalogList(CMSDatabase $db, $extFields = ""){
		$sql = "
			SELECT
				".CatalogQuery::FIELD_CATALOGLIST." 
			FROM ".CatalogQuery::$PFX."catalog
			ORDER BY ord DESC, title
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Получить элемент каталога. 
	 * 
	 * @var int $elementId идентификатор элемента в таблице элементов каталога (element)
	 */
	public static function Element(CMSDatabase $db, $elementId, $retarray = false){
		$sql = "
			SELECT eltypeid as eltid
			FROM ".CatalogQuery::$PFX."element
			WHERE elementid=".bkint($elementId)."
			LIMIT 1
		";
		$row = $db->query_first($sql);
		$elTypeId = $row['eltid'];

		$rows = CatalogQuery::ElementOptionListByType($db, $elTypeId);
		$fields = array();
		$minprefix = $elTypeId > 0 ? "b." : "a.";
		while (($row = $db->fetch_array($rows))){
			array_push($fields, $minprefix."fld_".$row['nm']);
		}
		$sfields = !empty($fields) ? ",".implode(",", $fields) : "";

		$fotosRows = CatalogQuery::FotoList($db, $elementId);
		$fotoIds = array();
		while (($row = $db->fetch_array($fotosRows))){
			array_push($fotoIds, $row['fid']);
		}
		
		if ($elTypeId > 0){
			$eltype = CatalogQuery::ElementTypeById($db, $elTypeId);
			$elTableName = CatalogQuery::BuildElementTableName($eltype['nm']);
			
			$sql = "
				SELECT 
					a.elementid as id,
					a.elementid as elid,
					a.catalogid as catid,
					a.eltypeid as eltid
					".$sfields.",
					'".implode(",", $fotoIds)."' as fids
				FROM ".CatalogQuery::$PFX."element a
				LEFT JOIN ".$elTableName." b ON a.globalelementid=b.".$eltype['nm']."id
				WHERE elementid=".bkint($elementId)."
				LIMIT 1
			";
		}else{
			$sql = "
				SELECT 
					a.elementid as id,
					a.elementid as elid,
					a.catalogid as catid,
					a.eltypeid as eltid
					".$sfields.",
					'".implode(",", $fotoIds)."' as fids
				FROM ".CatalogQuery::$PFX."element a
				WHERE elementid=".bkint($elementId)."
				LIMIT 1
			";
		}
		if ($retarray){
			return $db->query_first($sql);
		}
		return $db->query_read($sql);
	}

	/**
	 * Получить список элементов в каталоге
	 *
	 * @param CMSDatabase $db
	 * @param Integer|array $catalogId
	 * @return resource
	 */
	public static function ElementList(CMSDatabase $db, $catalogId, $page = 1, $limit = 10, $custWhere = '', $custOrder = '', $overFields = ''){
		$from = $limit * (max(1, $page) - 1);
		
		if (!is_array($catalogId)){
			$catalogId = array($catalogId);
		}
		$arr = array();
		foreach ($catalogId as $cid){
			array_push($arr, "catalogid=".bkint($cid));
		}
		$sql = "
			SELECT
				elementid as id,
				elementid as elid,
				catalogid as catid,
				eltypeid as eltid,
				title as tl,
				name as nm,
				deldate as dd
				".(!empty($overFields) ? ", ".$overFields : "")."
			FROM ".CatalogQuery::$PFX."element
			WHERE ".bkstr(empty($custWhere) ? implode(" OR ", $arr) : $custWhere)."
			ORDER BY ".bkstr(empty($custOrder) ? "dateline DESC" : $custOrder)."
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function ElementCount(CMSDatabase $db, $catalogId){
		if (!is_array($catalogId)){
			$catalogId = array($catalogId);
		}
		$arr = array();
		foreach ($catalogId as $cid){
			array_push($arr, "catalogid=".bkint($cid));
		}
		$sql = "
			SELECT
				count(elementid) as cnt
			FROM ".CatalogQuery::$PFX."element
			WHERE ".implode(" OR ", $arr)."
			LIMIT 1
		";
		$row = $db->query_first($sql);
		return $row['cnt'];
	}

	/**
	 * Добавить элемент в каталог:
	 * 1) добавить элемент в свою таблицу элементов (ctg_eltbl_[имя типа элемента]);
	 * 2) добавить элемент в таблицу элементов каталога (ctg_element)
	 *
	 * @param CMSDatabase $db
	 * @param object $data данные
	 */
	public static function ElementAppend(CMSDatabase $db, $data){
		$elementTypeId = intval($data->eltid);
		$dobj = CatalogQuery::ElementBuildVars($db, $data);
		
		$sfields = ""; $svalues = ""; 
		$fields = $dobj->fields;
		$values = $dobj->values;
		
		if (!empty($fields)){
			$sfields = ",".implode(",", $fields);
			$svalues = ",".implode(",", $values);
		}
		if ($elementTypeId > 0){
			/*
			$sql = "
				INSERT INTO ".CatalogQuery::$PFX."element
				(catalogid, eltypeid, title, name, dateline) VALUES (
					".bkint($data->id).",
					".bkint($data->catid).", 
					".bkint($data->eltid).", 
					'".bkstr($dobj->stitle)."', 
					'".bkstr($dobj->sname)."', 
					".TIMENOW." 
				)
			";
			$db->query_write($sql);
			$elementid = $db->insert_id();
			/**/
			echo('error CatalogElementAppend'); exit;			
			$sql = "
				INSERT INTO `".$dobj->table."` 
				(dateline ".$sfields.") VALUES (
					".TIMENOW."
					".$svalues."
				)
			";

		}else{
			$sql = "
				INSERT INTO ".CatalogQuery::$PFX."element
				(catalogid, eltypeid, title, name, dateline ".$sfields.") VALUES (
					".bkint($data->catid).",0, 
					'".bkstr($dobj->stitle)."', 
					'".bkstr($dobj->sname)."', 
					".TIMENOW." 
					".$svalues."
				)
			";
			$db->query_write($sql);
			$elementid = $db->insert_id();
		}
		
		// добавление фото
		$rows = CatalogQuery::Session($db, $data->session);
		$ids = array();
		while (($row = $db->fetch_array($rows))){
			$arr = json_decode($row['data']);
			foreach ($arr as $id){
				array_push($ids, $id);
			}
		}
		CatalogQuery::SessionRemove($db, $data->session);
		CatalogQuery::FotoAppend($db, $elementid, $ids);
		
		CatalogQuery::FotosSync($db, $elementid, $data->fids);
		
		return $elementid;
	}
	
	public static function ElementUpdate(CMSDatabase $db, $data, $fullUpdate = true){
		
		$elementTypeId = intval($data->eltid);
		
		$dobj = CatalogQuery::ElementBuildVars($db, $data);
		$fields = $dobj->fields;
		$values = $dobj->values;
		
		$objVars = get_object_vars($data);
		
		$sset = "";
		if (!empty($fields)){
			$set = array();
			for ($i=0; $i<count($fields); $i++){
				array_push($set, $fields[$i]."=".$values[$i]);
			}
			$sset=implode(",", $set);
		}
		$sFU = $fullUpdate ? "
			catalogid=".bkint($data->catid).",
			name='".bkstr($dobj->sname)."',
			title='".bkstr($dobj->stitle)."'
		" : "
			name=name
		";
		
		if ($elementTypeId > 0){
			$sql = "
				UPDATE `".$dobj->table."` 
				SET upddate=".TIMENOW." 
					".(!empty($sset) ? ',' : '')."
					".$sset."
				WHERE ".$dobj->idfield."=".bkint($data->elid)." 
			";
			$db->query_write($sql);
	
			$sql = "
				UPDATE ".CatalogQuery::$PFX."element
				SET
					".$sFU."
				WHERE elementid=".bkint($data->elid)." 
			";
		}else{
			$sql = "
				UPDATE ".CatalogQuery::$PFX."element
				SET
					".$sFU."
					".(!empty($sset) ? ',' : '')."
					".$sset."
				WHERE elementid=".bkint($data->elid)." 
			";
		}
		$db->query_write($sql);
		
		if ($fullUpdate){
			CatalogQuery::FotosSync($db, $data->elid, $data->fids);
		}
	}

	public static function ElementRemove(CMSDatabase $db, $elementId){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element
			SET deldate=".TIMENOW."
			WHERE  elementid=".bkint($elementId)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRestore(CMSDatabase $db, $elementId){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element SET deldate=0 WHERE  elementid=".bkint($elementId)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRemoveAll(CMSDatabase $db, $catalogid){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element
			SET deldate=".TIMENOW."
			WHERE  catalogid=".bkint($catalogid)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRecycleClear(CMSDatabase $db){
		$sql = "
			SELECT a.elementid as elid, b.name as nm, a.eltypeid as eltid
			FROM ".CatalogQuery::$PFX."element a
			LEFT JOIN ".CatalogQuery::$PFX."eltype b ON a.eltypeid=b.eltypeid
			WHERE a.deldate>0
		";
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			CatalogQuery::FotoListRemove($db, $row['elid']);
			if ($row['eltid'] > 0){
				$elTableName = CatalogQuery::BuildElementTableName($row['nm']);
				$sql = "
					DELETE FROM ".$elTableName."
					WHERE ".$row['nm']."id=".$row['elid']."
				";
				$db->query_write($sql);
			}
		}
		$sql = "DELETE FROM ".CatalogQuery::$PFX."element WHERE deldate>0";
		$db->query_write($sql);
	}
	
	public static function SessionAppend(CMSDatabase $db, $sessionid, $data){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."session
			(session, data) VALUES (
				'".addslashes($sessionid)."',
				'".addslashes($data)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function SessionRemove(CMSDatabase $db, $sessionid){
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."session
			WHERE session='".addslashes($sessionid)."'
		";
		$db->query_write($sql);
	}
	
	public static function Session(CMSDatabase $db, $sessionid){
		$sql = "
			SELECT *
			FROM ".CatalogQuery::$PFX."session
			WHERE session = '".$sessionid."'
		";
		return $db->query_read($sql);
	}
	
	public static function Foto(CMSDatabase $db, $fotoid){
		$sql = "
			SELECT *
			FROM ".CatalogQuery::$PFX."foto
			WHERE fotoid=".bkint($fotoid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	/**
	 * Удаление фотографии
	 */
	public static function FotoRemove(CMSDatabase $db, $fotoid){
		$foto = CatalogQuery::Foto($db, $fotoid);
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."foto
			WHERE fotoid=".bkint($fotoid)."
		";
		$db->query_write($sql);
		
		CMSRegistry::$instance->modules->GetModule('filemanager')->GetFileManager();
		CMSQFileManager::FileDelete($db, $foto['fileid']);
	}

	/**
	 * Удаление всех фотографий элемента
	 */
	public static function FotoListRemove(CMSDatabase $db, $elementid){
		$files = array();
		
		$rows = CatalogQuery::FotoList($db, $elementid);
		while (($row = $db->fetch_array($rows))){
			array_push($files, $row['fid']);
		}
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."foto
			WHERE elementid=".intval($elementid)."  
		";
		$db->query_write($sql);
		
		CMSRegistry::$instance->modules->GetModule('filemanager')->GetManager();
		CMSQFileManager::FilesDelete($db, $files);
	}
	
	public static function FotosSync(CMSDatabase $db, $elementid, $sfids){
		$afotos = explode(",", $sfids);
		$rows = CatalogQuery::FotoList($db, $elementid);
		while (($row = $db->fetch_array($rows))){
			$find = false;
			foreach($afotos as $fid){
				if ($fid == $row['fid']){
					$find = true;
					break;
				}
			}
			if (!$find){
				CatalogQuery::FotoRemove($db, $row['id']);
			}
		}
	}
	
	public static function FotoAppend(CMSDatabase $db, $elementid, $fileids){
		if (empty($fileids)){ return; }
		$arr = array();
		foreach ($fileids as $fileid){
			array_push($arr, "(
				".intval($elementid).",
				'".addslashes($fileid)."'
			)");
		}
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."foto
			(elementid, fileid) VALUES 
			".implode($arr, ',')."
		";
		$db->query_write($sql);
	}
	
	public static function FotoList(CMSDatabase $db, $elementid) {
		$sql = "
			SELECT 
				fotoid as id,
				elementid as elid,
				fileid as fid,
				ord
			FROM ".CatalogQuery::$PFX."foto
			WHERE elementid=".intval($elementid)."
			ORDER BY fotoid 
		";
		return $db->query_read($sql);
	}
	
	public static function FotoFirst(CMSDatabase $db, $elementid){
		$sql = "
			SELECT 
				fotoid as id,
				elementid as elid,
				fileid as fid,
				ord
			FROM ".CatalogQuery::$PFX."foto
			WHERE elementid=".intval($elementid)."
			ORDER BY fotoid
			LIMIT 1 
		";
		return $db->query_first($sql);
	}
	
	/**
	 * Получить информацию по первой фото товара и при этом проверить, есть ли уже 
	 * сформированная превьюшка на эту картинку.
	 * 
	 * @param CMSDatabase $db
	 * @param integer $eltypeid
	 * @param integer $elementid
	 * @param integer $width
	 * @param integer $height
	 * @param integer $limit ограничение, если 0 (по умолчанию) то ограничений нет
	 */
	public static function FotoListThumb(CMSDatabase $db, $elementid, $width, $height, $limit = 0){
		$sql = "
			SELECT 
				f.fotoid as id,
				f.elementid as elid,
				f.fileid as fid,
				f.ord,
				s.filename as fn,
				(
					SELECT CONCAT(t.filehashdst,':',ss.imgwidth,'x',ss.imgheight) as info
					FROM ".$db->prefix."fm_imgprev t
					INNER JOIN ".$db->prefix."fm_file ss ON t.filehashdst = ss.filehash
					WHERE t.filehashsrc=s.filehash AND 
						t.width=".intval($width)." AND 
						t.height=".intval($height)."
					LIMIT 1
				) thumb
			FROM ".CatalogQuery::$PFX."foto f
			INNER JOIN ".$db->prefix."fm_file s ON f.fileid = s.filehash
			WHERE f.elementid=".intval($elementid)."
			".($limit > 0 ? ("LIMIT ".$limit) : "")."
		";
		return $db->query_read($sql);
	}


	
	
	
	
	public static $PFX = "";
	public static function PrefixSet(CMSDatabase $db, $mmPrefix){
		CatalogQuery::$PFX = $db->prefix."ctg_".$mmPrefix."_";
	}
	
	const OPTIONTYPE_BOOLEAN = 0;
	const OPTIONTYPE_NUMBER = 1;
	const OPTIONTYPE_DOUBLE = 2;
	const OPTIONTYPE_STRING = 3;
	const OPTIONTYPE_LIST = 4;
	const OPTIONTYPE_TABLE = 5;
	const OPTIONTYPE_MULTI = 6;
	const OPTIONTYPE_TEXT = 7;
	const OPTIONTYPE_DICT = 8;
	
	
	
	private static function ElementBuildVars(CMSDatabase $db, $data){
		$eltype = CatalogQuery::ElementTypeById($db, $data->eltid);
		
		$rows = CatalogQuery::ElementOptionListByType($db, $data->eltid);
		
		$ret = new stdClass();
		$ret->idfield = $eltype['nm']."id";
		$ret->table = CatalogQuery::BuildElementTableName($eltype['nm']);
		
		$fields = array(); $values = array(); $elnamevals = array();
		$objVars = get_object_vars($data);
		// формирование списка полей и их значений
		while (($row = $db->fetch_array($rows))){
			$fdbname = "fld_".$row['nm'];
			
			if (!isset($objVars[$fdbname])){
				continue;
			}
			switch ($row['fldtp']){
				case CatalogQuery::OPTIONTYPE_BOOLEAN:
				case CatalogQuery::OPTIONTYPE_NUMBER:
				case CatalogQuery::OPTIONTYPE_LIST:
					array_push($fields, $fdbname);
					array_push($values, bkint($data->$fdbname));
					break;
				case CatalogQuery::OPTIONTYPE_STRING:
				case CatalogQuery::OPTIONTYPE_TEXT:
				case CatalogQuery::OPTIONTYPE_DOUBLE:
					array_push($fields, $fdbname);
					array_push($values, "'".bkstr($data->$fdbname)."'");
					break;
				case CatalogQuery::OPTIONTYPE_TABLE:
					$data->$fdbname = bkint($data->$fdbname);
					$fdbnamealt = $fdbname."-alt"; 
					if (empty($data->$fdbname) && !empty($data->$fdbnamealt)){
						$data->$fdbname = CatalogQuery::ElementOptionFieldTableAppendValue($db, $eltype['nm'], $row['nm'], $data->$fdbnamealt);
					}
					array_push($fields, $fdbname);
					array_push($values, bkint($data->$fdbname));
					break;
			}
			if (!empty($row['ets'])){
				if($row['fldtp'] == CatalogQuery::OPTIONTYPE_TABLE){
					$row = CatalogQuery::ElementOptionFieldTableValue($db, $eltype['nm'],  $row['nm'], $data->$fdbname);
					array_push($elnamevals, $row['tl']);
				}else{
					array_push($elnamevals, $data->$fdbname);
				}
			}
		}
		$ret->fields = $fields;
		$ret->values = $values;
		$ret->stitle = "";
		$ret->sname = "";
		
		if (!empty($elnamevals)){
			$ret->stitle = implode(", ", $elnamevals);
			$ret->sname = translateruen($ret->stitle);
		}
		
		return $ret;
	}
	
	/**
	 * Удаление каталога и его элементов
	 */
	public static function CatalogRemove(CMSDatabase $db, $catalogid){
		$catalog = CatalogQuery::Catalog($db, $catalogid);
		if (empty($catalog)){ return; }
		
		$rows = CatalogQuery::CatalogListByParentId($db, $catalogid);
		while (($row = $db->fetch_array($rows))){
			CatalogQuery::CatalogRemove($db, $row['id']);
		}
		
		CatalogQuery::ElementRemoveAll($db, $catalogid);
		CatalogQuery::ElementRecycleClear($db);
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."catalog
			WHERE catalogid=".bkint($catalogid)."
		";
		$db->query_write($sql);
	}
	
	public static function CatalogUpdate(CMSDatabase $db, $data){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."catalog
			SET 
				title='".bkstr($data->tl)."',
				name='".bkstr($data->nm)."', 
				descript='".bkstr($data->dsc)."',
				metatitle='".bkstr($data->ktl)."',
				metadesc='".bkstr($data->kdsc)."',
				metakeys='".bkstr($data->kwds)."',
				ord=".bkint($data->ord).",
				parentcatalogid=".bkint($data->pid)."
			WHERE catalogid=".bkint($data->id)."
		";
		$db->query_write($sql);
	}
	
	public static function CatalogAppend(CMSDatabase $db, $data){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."catalog
			(parentcatalogid, title, name, descript, metatitle, metadesc, metakeys, ord) VALUES
			(
				'".bkint($data->pid)."',
				'".bkstr($data->tl)."',
				'".bkstr($data->nm)."',
				'".bkstr($data->dsc)."',
				'".bkstr($data->ktl)."',
				'".bkstr($data->kdsc)."',
				'".bkstr($data->kwds)."',
				'".bkint($data->ord)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	const FIELD_CATALOGLIST = "
		catalogid as id,
		parentcatalogid as pid,
		name as nm,
		title as tl,
		descript as dsc,
		metatitle as ktl,
		metadesc as kdsc,
		metakeys as kwds,
		dateline as dl,
		deldate as dd,
		level as lvl,
		ord as ord
	";
	
	public static function Catalog(CMSDatabase $db, $catalogid){
		$sql = "
			SELECT
				".CatalogQuery::FIELD_CATALOGLIST." 
			FROM ".CatalogQuery::$PFX."catalog
			WHERE catalogid=".bkint($catalogid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function CatalogListByParentId(CMSDatabase $db, $parentCatalogId){
		$sql = "
			SELECT
				".CatalogQuery::FIELD_CATALOGLIST." 
			FROM ".CatalogQuery::$PFX."catalog
			WHERE parentcatalogid=".bkint($parentCatalogId)."
			ORDER BY title
		";
		return $db->query_read($sql);
	}
	
	public static function ElementOptGroupAppend(CMSDatabase $db, $elementTypeId, $title, $descript){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."eloptgroup
			(eltypeid, title, descript) VALUES (
				".bkint($elementTypeId).",
				'".bkstr($title)."',
				'".bkstr($descript)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementOptGroupList(CMSDatabase $db){
		$sql = "
			SELECT
				eloptgroupid as id,
				parenteloptgroupid as pid,
				eltypeid as elid,
				title as tl,
				descript as dsc
			FROM ".CatalogQuery::$PFX."eloptgroup
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Имена полей в запросах SELECT таблицы ctg_eloption
	 *
	 */
	const FIELD_ELEMENTOPTION = "
		eloptionid as id,
		eltypeid as eltid,
		eloptgroupid as grp,
		fieldtype as fldtp,
		param as prms,
		name as nm,
		title as tl,
		eltitlesource as ets,
		descript as dsc,
		ord as ord,
		disable as dsb,
		deldate as dd
	";
	
	public static function ElementOptionRemove(CMSDatabase $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eloption
			SET 
				deldate=".TIMENOW."
			WHERE eloptionid=".bkint($id)."
		";
		$db->query_write($sql);
	}

	public static function ElementOptionRestore(CMSDatabase $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eloption
			SET deldate=0
			WHERE eloptionid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionSave(CMSDatabase $db, $data){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eloption
			SET 
				title='".bkstr($data->tl)."', 
				descript='".bkstr($data->dsc)."',
				param='".bkstr($data->prms)."',
				eltitlesource='".bkstr($data->ets)."'
			WHERE eloptionid=".bkint($data->id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionRecycleClear(CMSDatabase $db){
		$sql = "
			SELECT
				a.fieldtype as fldtp,
				a.name as nm, 
				b.name as eltnm
			FROM ".CatalogQuery::$PFX."eloption a
			LEFT JOIN ".CatalogQuery::$PFX."eltype b ON a.eltypeid=b.eltypeid
			WHERE a.deldate>0 
		";
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			if ($row['fldtp'] == CatalogQuery::OPTIONTYPE_TABLE){
				$tablename = CatalogQuery::BuilElementOptionFieldTable($row['eltnm'], $row['nm']);
				$db->query_write("DROP TABLE `".$tablename."`");
			}
			// удаление поля из таблицы элементов данного типа
			$sql = "
				ALTER TABLE `".CatalogQuery::BuildElementTableName($row['eltnm'])."` DROP `fld_".$row['nm']."`
			";
			$db->query_write($sql);
		}
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."eloption
			WHERE deldate>0
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionAppend(CMSDatabase $db, $data, $prms){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."eloption
			(eltypeid, eloptgroupid, fieldtype, param, name, title, eltitlesource, descript, dateline) VALUES
			(
				".bkint($data->eltid).",
				".bkint($data->grp).",
				".bkint($data->fldtp).",
				'".bkstr($data->prms)."',
				'".bkstr($data->nm)."',
				'".bkstr($data->tl)."',
				'".bkint($data->ets)."',
				'".bkstr($data->dsc)."', 
				".TIMENOW."
			)
		";
		$db->query_write($sql);


		$table = CatalogQuery::BuildElementTableName($data->eltypenm); 
		
		$sql = "ALTER TABLE ".$table." ADD `fld_".$data->nm."` ";
		switch ($data->fldtp){
			case CatalogQuery::OPTIONTYPE_BOOLEAN:
				$sql .= "INT(1) UNSIGNED NOT NULL DEFAULT '".$prms->def."'";
				break;
			case CatalogQuery::OPTIONTYPE_NUMBER:
				$sql .= "INT(".$prms->size.") NOT NULL DEFAULT '".$prms->def."'";
				break;
			case CatalogQuery::OPTIONTYPE_DOUBLE:
				$sql .= "DOUBLE(".$prms->size.") NOT NULL DEFAULT '".$prms->def."'";
				break;
			case CatalogQuery::OPTIONTYPE_STRING:
				$sql .= "VARCHAR(".$prms->size.") NOT NULL DEFAULT '".bkstr($prms->def)."'";
				break;
			case CatalogQuery::OPTIONTYPE_LIST:
				$sql .= "INT(4) NOT NULL DEFAULT '0'";
				break;
			case CatalogQuery::OPTIONTYPE_TABLE:
				$sql .= "INT(10) UNSIGNED NOT NULL DEFAULT '0'";
				CatalogQuery::ElementOptionFieldTableCreate($db, $data->eltypenm, $data->nm);
				break;
			case CatalogQuery::OPTIONTYPE_MULTI:
				return;
			case CatalogQuery::OPTIONTYPE_TEXT:
				$sql .= "TEXT NOT NULL ";
				break;
			default:
				return;
		}
		$sql .= " COMMENT '".bkstr($data->tl)."'";
		
		$db->query_write($sql);
	}
	
	public static function ElementOptionFieldTableCreate(CMSDatabase $db, $eltypename, $fieldname){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname); 
		$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
		$sql = "
			CREATE TABLE IF NOT EXISTS `".$tablename."` (
			  `".$fieldname."id` int(10) unsigned NOT NULL auto_increment,
			  `title` varchar(250) NOT NULL default '',
			  PRIMARY KEY  (`".$fieldname."id`)
		)".$charset;
		$db->query_write($sql);
	}
	
	public static function ElementOptionFieldTableAppendValue(CMSDatabase $db, $eltypename, $fieldname, $value){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname); 
		$sql = "INSERT INTO `".$tablename."` (title) VALUES ('".bkstr($value)."')";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementOptionFieldTableValue(CMSDatabase $db, $eltypename, $fieldname, $id){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname);
		$sql = "
			SELECT 
				".$fieldname."id as id,
				title as tl
			FROM ".$tablename."
			WHERE ".$fieldname."id=".bkint($id)."
			LIMIT 1
		"; 
		return $db->query_first($sql); 
	}
	
	public static function ElementOptionFieldTableList(CMSDatabase $db, $eltypename, $fieldname){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname);
		$sql = "
			SELECT 
				".$fieldname."id as id,
				title as tl
			FROM ".$tablename."
			ORDER BY title
		"; 
		return $db->query_read($sql);
	}

	public static function ElementOptionByName(CMSDatabase $db, $elementTypeId, $name){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTOPTION."
			FROM ".CatalogQuery::$PFX."eloption
			WHERE name='".bkstr($name)."' AND eltypeid=".bkint($elementTypeId)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	/**
	 * Список опций элемента конкретного типа
	 *
	 * @param CMSDatabase $db
	 * @param integer $elTypeId идентификатор типа элемента
	 * @return resource
	 */
	public static function ElementOptionListByType(CMSDatabase $db, $elTypeId){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTOPTION."
			FROM ".CatalogQuery::$PFX."eloption
			WHERE eltypeid=".bkint($elTypeId)."
			ORDER BY eloptgroupid, ord
		";
		return $db->query_read($sql);
	}
	
	public static function ElementOptionList(CMSDatabase $db, $elTypeId = -1, $fieldType = -1){
		$where = array();
		if ($elTypeId > -1){
			array_push($where, "eltypeid=".bkint($elTypeId));
		} 
		if ($fieldType > -1){
			array_push($where, "fieldtype=".bkint($fieldType));
		}
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTOPTION."
			FROM ".CatalogQuery::$PFX."eloption
			".(count($where) > 0 ? implode(" AND ", $where) : "")."
			ORDER BY eloptgroupid, ord
		";
		return $db->query_read($sql);
	}
	
	public static function TableList(CMSDatabase $db){
		$sql = "
			SHOW TABLES FROM ".$db->database."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Очистка корзины и удаление всех связанных с удаляемыми записями обьекты
	 *
	 * @param CMSDatabase $db
	 */
	public static function ElementTypeRecycleClear(CMSDatabase $db){
		$sql = "
			SELECT name as nm
			FROM ".CatalogQuery::$PFX."eltype
			WHERE deldate>0
		";
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			CatalogQuery::ElementTypeTableRemove($db, $row['nm']);
		}
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."eltype
			WHERE deldate>0
		";
		$db->query_write($sql);
	}

	/**
	 * Удаление записи типа элемента в корзину
	 *
	 * @param CMSDatabase $db
	 * @param Integer $id
	 */
	public static function ElementTypeRemove(CMSDatabase $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
			SET deldate=".TIMENOW."
			WHERE eltypeid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeRestore(CMSDatabase $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
			SET deldate=0
			WHERE eltypeid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableRemove(CMSDatabase $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name);  
		$sql = "DROP TABLE `".$tablename."`";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableCreate(CMSDatabase $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name);  
		$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";

		$sql = "
			CREATE TABLE IF NOT EXISTS `".$tablename."` (
			  `".$name."id` int(10) unsigned NOT NULL auto_increment,
			  `dateline` int(10) unsigned NOT NULL default '0' COMMENT 'дата добавления',
			  `upddate` int(10) unsigned NOT NULL default '0' COMMENT 'дата обновления',
			  `deldate` int(10) unsigned NOT NULL default '0' COMMENT 'дата удаления',
			  PRIMARY KEY  (`".$name."id`)
		)".$charset;
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableFieldList(CMSDatabase $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name); 
		$sql = "SHOW COLUMNS FROM ".$tablename;
		return $db->query_read($sql);
	}

	public static function ElementTypeAppend(CMSDatabase $db, $obj){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."eltype
			(name, title, descript, fotouse) VALUES
			(
				'".bkstr($obj->nm)."',
				'".bkstr($obj->tl)."',
				'".bkstr($obj->dsc)."',
				'".bkint($obj->foto)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeUpdate(CMSDatabase $db, $obj){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
				SET 
					title='".bkstr($obj->tl)."',
					descript='".bkstr($obj->dsc)."'
			WHERE eltypeid=".bkint($obj->id)."
		";
		$db->query_write($sql);
	}
	
	const FIELD_ELEMENTTYPE = "
		eltypeid as id,
		title as tl,
		name as nm,
		descript as dsc,
		fotouse as foto,
		deldate as dd
	";
	
	public static function ElementTypeById(CMSDatabase $db, $id){
		$id = bkint($id);
		if ($id == 0){ return array(); }
		$sql = "
			SELECT
				".CatalogQuery::FIELD_ELEMENTTYPE." 
			FROM ".CatalogQuery::$PFX."eltype
			WHERE eltypeid=".bkint($id)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementTypeByName(CMSDatabase $db, $name){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTTYPE." 
			FROM ".CatalogQuery::$PFX."eltype
			WHERE name='".bkstr($name)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementTypeList(CMSDatabase $db){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTTYPE." 
			FROM ".CatalogQuery::$PFX."eltype
		";
		return $db->query_read($sql);
	}
	

	public static function BuildDictionaryTable($dictionaryName){
		return CatalogQuery::$PFX."dict_".$dictionaryName;
	}
	
	public static function BuildElementTableName($elementName){
		if (empty($elementName)){
			return CatalogQuery::$PFX."element";
		}
		return CatalogQuery::$PFX."eltbl_".$elementName;
	}
	
	public static function BuilElementOptionFieldTable($elementName, $fieldName){
		return CatalogQuery::$PFX."eltbl_".$elementName."_fld_".$fieldName;
	}
	
}


?>