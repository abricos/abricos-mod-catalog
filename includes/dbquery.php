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
				'root' as nm,
				'Root' as tl,
				0 as dl,
				0 as lvl,
				0 as ord,
				'' as img,
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
				cat.ord as ord,
				cat.imageid as img,
				(
					SELECT count(*) as cnt
					FROM ".$pfx."element e
					WHERE e.catalogid=cat.catalogid AND e.deldate=0
					GROUP BY e.catalogid
				) as ecnt
			FROM ".$pfx."catalog cat
			WHERE cat.deldate=0
			ORDER BY cat.ord DESC, cat.title)
		";
		return $db->query_read($sql);
	}
	
	
	public static function Catalog(Ab_Database $db, $pfx, $catid){
		if ($catid == 0){
			$sql = "
				SELECT count(*) as ecnt
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
					cat.ord as ord,
					cat.imageid as img,
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
	
	public static function ElementList(Ab_Database $db, $pfx, $catid){
		$sql = "
			SELECT
				e.elementid as id,
				e.catalogid as catid,
				e.eltypeid as eltpid,
				e.title as tl,
				e.name as nm
			FROM ".$pfx."element e
			INNER JOIN ".$pfx."catalog cat ON cat.catalogid=e.catalogid
			WHERE e.deldate=0 AND e.catalogid=".bkint($catid)." AND cat.deldate=0
		";
		return $db->query_read($sql);
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
	
	public static function ElementTypeOptionList(Ab_Database $db, $pfx){
		$sql = "
			SELECT
				eloptionid as id,
				eltypeid as eltpid,
				fieldtype as tp,
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
			ORDER BY eltpid, eloptgroupid, ord
		";
		return $db->query_read($sql);
	}
	
	
}

/*
 * TODO: Старая версия запросов - на удаление
 */

/**
 * Термины:
 * Element - свободный элемент.
 * ElementType - тип элемента. Для каждого типа элемента создается своя таблица хранения дополнительных опций элемента.
 * ElementOption - опция элемента
 * Catalog - раздел в каталоге.
 * CatalogElement - элемент в каталоге (в таблице element связывает раздел каталога со свободным элементом). 
 */
class CatalogQuery {
	
	public static function CatalogList(Ab_Database $db, $extFields = ""){
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
	public static function Element(Ab_Database $db, $elementId, $retarray = false, $elTypeId = -1){
		if ($elTypeId < 0){
			$sql = "
				SELECT eltypeid as eltid
				FROM ".CatalogQuery::$PFX."element
				WHERE elementid=".bkint($elementId)."
				LIMIT 1
			";
			$row = $db->query_first($sql);
			$elTypeId = $row['eltid'];
		}
		
		$fields = array();
		$rows = CatalogQuery::ElementOptionListByType($db, 0, true);
		foreach ($rows as $row){
			if ($row['fldtp'] == 9){ continue; }
			array_push($fields, "a.fld_".$row['nm']);
		}

		$fotosRows = CatalogQuery::FotoList($db, $elementId);
		$fotoIds = array();
		while (($row = $db->fetch_array($fotosRows))){
			array_push($fotoIds, $row['fid']);
		}
		
		if ($elTypeId > 0){
			$eltype = CatalogQuery::ElementTypeById($db, $elTypeId);
			$elTableName = CatalogQuery::BuildElementTableName($eltype['nm']);
			
			$rows = CatalogQuery::ElementOptionListByType($db, $elTypeId, true);
			foreach ($rows as $row){
				if ($row['fldtp'] == 9){ continue; }
				array_push($fields, "b.fld_".$row['nm']);
			}
		}
		$sfields = !empty($fields) ? ",".implode(",", $fields) : "";
		
		$sql = "
			SELECT 
				a.elementid as id,
				a.elementid as elid,
				a.catalogid as catid,
				a.eltypeid as eltid
				".$sfields.",
				'".implode(",", $fotoIds)."' as fids
			FROM ".CatalogQuery::$PFX."element a
		";
		if ($elTypeId > 0){
			$sql .= "
				LEFT JOIN ".$elTableName." b ON a.elementid=b.elementid
			";
		}
		$sql .= "
			WHERE a.elementid=".bkint($elementId)."
			LIMIT 1
		";
		if ($retarray){
			return $db->query_first($sql);
		}
		return $db->query_read($sql);
	}

	/**
	 * Получить список элементов в каталоге
	 *
	 * @param Ab_Database $db
	 * @param Integer|array $catalogId
	 * @return resource
	 */
	public static function ElementList(Ab_Database $db, $catalogId, $page = 1, $limit = 10, $custWhere = '', $custOrder = '', $overFields = ''){
		$from = $limit * (max(1, $page) - 1);
		
		if (!is_array($catalogId)){
			$catalogId = array($catalogId);
		}
		$arr = array();
		foreach ($catalogId as $cid){
			if ($cid < 0){
				array_push($arr, "1=1");
				
				if (empty($custOrder)){
					$custOrder = " catalogid, title";
				}
				
			}else{
				array_push($arr, "catalogid=".bkint($cid));
			}
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
	
	public static function ElementCount(Ab_Database $db, $catalogId, $custWhere = ''){
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
			WHERE ".bkstr(empty($custWhere) ? implode(" OR ", $arr) : $custWhere)."
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
	 * @param Ab_Database $db
	 * @param object $data данные
	 */
	public static function ElementAppend(Ab_Database $db, $data){
		
		// первым делом добавить базовый элемент
		$dobj = CatalogQuery::ElementBuildVars($db, $data, 0);
		$sfields = ""; $svalues = ""; 
		$fields = $dobj->fields; $values = $dobj->values;
		
		if (!empty($fields)){
			$sfields = ",".implode(",", $fields);
			$svalues = ",".implode(",", $values);
		}
		
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."element
			(catalogid, eltypeid, title, name, dateline ".$sfields.") VALUES (
				".bkint($data->catid).",
				".bkint($data->eltid).", 
				'".bkstr($dobj->stitle)."', 
				'".bkstr($dobj->sname)."', 
				".TIMENOW." 
				".$svalues."
			)
		";
		$db->query_write($sql);
		$elementid = $db->insert_id();
		
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
		
		$elTypeId = intval($data->eltid);
		if ($elTypeId == 0){
			return $elementid;
		}
		
		// добавление ссылок на элементы базового типа
		foreach ($dobj->childs as $key => $value){
			$arr = explode(",", $value);
			foreach ($arr as $selid){
				CatalogQuery::LinkElementAppend($db, $elementid, $key, $selid);
			}
		}
		
		// добавить дополнительные поля, если тип элемента не базовый
		$dobj = CatalogQuery::ElementBuildVars($db, $data, $elTypeId);
		$sfields = ""; $svalues = ""; 
		$fields = $dobj->fields; $values = $dobj->values;
		
		// добавление ссылок на элементы
		foreach ($dobj->childs as $key => $value){
			$arr = explode(",", $value);
			foreach ($arr as $selid){
				CatalogQuery::LinkElementAppend($db, $elementid, $key, $selid);
			}
		}
		
		
		if (!empty($fields)){
			$sfields = ",".implode(",", $fields);
			$svalues = ",".implode(",", $values);
		}
		$sql = "
			INSERT INTO `".$dobj->table."` 
			(elementid ".$sfields.") VALUES (
				".$elementid."
				".$svalues."
			)
		";
		$db->query_write($sql);
		
		return $elementid;
	}
	
	public static function ElementUpdate(Ab_Database $db, $data, $fullUpdate = true){
		
		$dobj = CatalogQuery::ElementBuildVars($db, $data, 0);
		$fields = $dobj->fields; $values = $dobj->values;
		
		$objVars = get_object_vars($data);
		
		$sset = "";
		if (!empty($fields)){
			$set = array();
			for ($i=0; $i<count($fields); $i++){
				array_push($set, $fields[$i]."=".$values[$i]);
			}
			$sset=implode(",", $set);
		}
		
		$utmf = Abricos::TextParser(true);
		
		$dobj->stitle = $utmf->Parser($dobj->stitle);
		
		$sFU = $fullUpdate ? "
			catalogid=".bkint($data->catid).",
			name='".bkstr($dobj->sname)."',
			title='".bkstr($dobj->stitle)."'
		" : " name=name ";
		
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element
			SET
				".$sFU."
				".(!empty($sset) ? ',' : '')."
				".$sset."
			WHERE elementid=".bkint($data->elid)." 
		";
		$db->query_write($sql);
		
		if ($fullUpdate){
			CatalogQuery::FotosSync($db, $data->elid, $data->fids);
		}
		
		$elTypeId = intval($data->eltid);
		if ($elTypeId < 1){ return; }
		
		// обновить дополнительные поля, если тип элемента не базовый
		$dobj = CatalogQuery::ElementBuildVars($db, $data, $elTypeId);
		$fields = $dobj->fields; $values = $dobj->values;
		
		$objVars = get_object_vars($data);
		
		$sset = "";
		if (empty($fields)){
			return;
		}
		$set = array();
		for ($i=0; $i<count($fields); $i++){
			array_push($set, $fields[$i]."=".$values[$i]);
		}
		$sset=implode(",", $set);
		
		$sql = "
			UPDATE `".$dobj->table."` 
			SET ".$sset."
			WHERE elementid=".bkint($data->elid)." 
		";
		$db->query_write($sql);
	}

	public static function ElementRemove(Ab_Database $db, $elementId){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element
			SET deldate=".TIMENOW."
			WHERE  elementid=".bkint($elementId)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRestore(Ab_Database $db, $elementId){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element SET deldate=0 WHERE  elementid=".bkint($elementId)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRemoveAll(Ab_Database $db, $catalogid){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."element
			SET deldate=".TIMENOW."
			WHERE  catalogid=".bkint($catalogid)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementRecycleClear(Ab_Database $db){
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
					WHERE elementid=".$row['elid']."
				";
				$db->query_write($sql);
			}
		}
		$sql = "DELETE FROM ".CatalogQuery::$PFX."element WHERE deldate>0";
		$db->query_write($sql);
	}
	
	public static function LinkElementList(Ab_Database $db, $elementId, $optionId){
		$sql = "
			SELECT 
				l.linkid as id,
				l.childid as elid,
				l.ord as ord,
				e.name as nm,
				e.title as tl 
			FROM ".CatalogQuery::$PFX."link l
			LEFT JOIN ".CatalogQuery::$PFX."element e ON l.childid=e.elementid
			WHERE l.elementid=".bkint($elementId)." AND optionid=".bkint($optionId)."
		";
		return $db->query_read($sql);
	}
	
	public static function LinkElementAppend(Ab_Database $db, $elementId, $optionId, $childid){
		$sql = "
			INSERT IGNORE INTO ".CatalogQuery::$PFX."link
			(elementid, optionid, childid) VALUES (
				'".bkint($elementId)."',
				'".bkint($optionId)."',
				'".bkint($childid)."'
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function LinkElementRemove(Ab_Database $db, $elementId, $optionId, $childid){
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."link
			WHERE elementid='".bkint($elementId)."' AND optionid='".bkint($optionId)."' AND childid='".bkint($childid)."'
		";
		$db->query_write($sql);
	}
	
	public static function SessionAppend(Ab_Database $db, $sessionid, $data){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."session
			(session, data) VALUES (
				'".addslashes($sessionid)."',
				'".addslashes($data)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function SessionRemove(Ab_Database $db, $sessionid){
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."session
			WHERE session='".addslashes($sessionid)."'
		";
		$db->query_write($sql);
	}
	
	public static function Session(Ab_Database $db, $sessionid){
		$sql = "
			SELECT *
			FROM ".CatalogQuery::$PFX."session
			WHERE session = '".$sessionid."'
		";
		return $db->query_read($sql);
	}
	
	public static function Foto(Ab_Database $db, $fotoid){
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
	public static function FotoRemove(Ab_Database $db, $fotoid){
		$foto = CatalogQuery::Foto($db, $fotoid);
		$sql = "
			DELETE FROM ".CatalogQuery::$PFX."foto
			WHERE fotoid=".bkint($fotoid)."
		";
		$db->query_write($sql);
		
		Abricos::GetModule('filemanager')->GetFileManager();
		CMSQFileManager::FileDelete($db, $foto['fileid']);
	}

	/**
	 * Удаление всех фотографий элемента
	 */
	public static function FotoListRemove(Ab_Database $db, $elementid){
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
		
		Abricos::GetModule('filemanager')->GetManager();
		CMSQFileManager::FilesDelete($db, $files);
	}
	
	public static function FotosSync(Ab_Database $db, $elementid, $sfids){
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
	
	public static function FotoAppend(Ab_Database $db, $elementid, $fileids){
		if (empty($fileids)){ return; }
		$arr = array();
		$i = 0;
		foreach ($fileids as $fileid){
			array_push($arr, "(
				".intval($elementid).",
				'".addslashes($fileid)."',
				".($i++)."
			)");
		}
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."foto
			(elementid, fileid, ord) VALUES 
			".implode($arr, ',')."
		";
		$db->query_write($sql);
	}
	
	public static function FotoList(Ab_Database $db, $elementid) {
		$sql = "
			SELECT 
				fotoid as id,
				elementid as elid,
				fileid as fid,
				ord
			FROM ".CatalogQuery::$PFX."foto
			WHERE elementid=".intval($elementid)."
			ORDER BY ord
		";
		return $db->query_read($sql);
	}
	
	public static function FotoFirst(Ab_Database $db, $elementid){
		$sql = "
			SELECT 
				fotoid as id,
				elementid as elid,
				fileid as fid,
				ord
			FROM ".CatalogQuery::$PFX."foto
			WHERE elementid=".intval($elementid)."
			ORDER BY ord
			LIMIT 1 
		";
		return $db->query_first($sql);
	}
	
	/**
	 * Получить информацию по первой фото товара и при этом проверить, есть ли уже 
	 * сформированная превьюшка на эту картинку.
	 * 
	 * @param Ab_Database $db
	 * @param integer $eltypeid
	 * @param integer $elementid
	 * @param integer $width
	 * @param integer $height
	 * @param integer $limit ограничение, если 0 (по умолчанию) то ограничений нет
	 */
	public static function FotoListThumb(Ab_Database $db, $elementid, $width, $height, $limit = 0){
		$sql = "
			SELECT 
				f.fotoid as id,
				f.elementid as elid,
				f.fileid as fid,
				f.ord,
				s.filename as fn,
				s.extension as ext,
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
			ORDER BY f.ord
			".($limit > 0 ? ("LIMIT ".$limit) : "")."
		";
		return $db->query_read($sql);
	}


	public static $PFX = "";
	public static function PrefixSet(Ab_Database $db, $mmPrefix){
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
	const OPTIONTYPE_CHILDELEMENT = 9;
	
	private static function ElementBuildVars(Ab_Database $db, $data, $elTypeId){
		$eltype = CatalogQuery::ElementTypeById($db, $elTypeId);
		
		$rows = CatalogQuery::ElementOptionListByType($db, $elTypeId);
		
		$ret = new stdClass();
		$ret->idfield = $eltype['nm']."id";
		$ret->table = CatalogQuery::BuildElementTableName($eltype['nm']);
		
		$fields = array(); $values = array(); $elnamevals = array(); $childs = array();
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
						
						// необходимо проверить наличие дубликата
						$trow =  CatalogQuery::ElementOptionFieldTableValueByTitle($db, $eltype['nm'], $row['nm'], $data->$fdbnamealt);
						if (!empty($trow)){
							$data->$fdbname = $trow['id'];
						}else{
							$data->$fdbname = CatalogQuery::ElementOptionFieldTableAppendValue($db, $eltype['nm'], $row['nm'], $data->$fdbnamealt);
						}
					}
					array_push($fields, $fdbname);
					array_push($values, bkint($data->$fdbname));
					break;
				case CatalogQuery::OPTIONTYPE_CHILDELEMENT:
					$childs[$row['id']] = $data->$fdbname;
					break;
			}
			if (!empty($row['ets'])){
				if($row['fldtp'] == CatalogQuery::OPTIONTYPE_TABLE){
					$row = CatalogQuery::ElementOptionFieldTableValue($db, $eltype['nm'],  $row['nm'], $data->$fdbname);
					if(!empty($row['tl'])){
						array_push($elnamevals, $row['tl']);
					}
				}else{
					if (!empty($data->$fdbname)){
						array_push($elnamevals, $data->$fdbname);
					}
				}
			}
		}
		$ret->fields = $fields;
		$ret->values = $values;
		$ret->childs = $childs;
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
	public static function CatalogRemove(Ab_Database $db, $catalogid){
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
	
	public static function CatalogUpdate(Ab_Database $db, $data){
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
				parentcatalogid=".bkint($data->pid).",
				imageid='".bkstr($data->img)."'
			WHERE catalogid=".bkint($data->id)."
		";
		$db->query_write($sql);
	}
	
	public static function CatalogAppend(Ab_Database $db, $data){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."catalog
			(parentcatalogid, title, name, descript, metatitle, metadesc, metakeys, ord, imageid) VALUES
			(
				'".bkint($data->pid)."',
				'".bkstr($data->tl)."',
				'".bkstr($data->nm)."',
				'".bkstr($data->dsc)."',
				'".bkstr($data->ktl)."',
				'".bkstr($data->kdsc)."',
				'".bkstr($data->kwds)."',
				'".bkint($data->ord)."',
				'".bkstr($data->img)."'
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
		ord as ord,
		imageid as img
	";
	
	public static function Catalog(Ab_Database $db, $catalogid){
		$sql = "
			SELECT
				".CatalogQuery::FIELD_CATALOGLIST." 
			FROM ".CatalogQuery::$PFX."catalog
			WHERE catalogid=".bkint($catalogid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function CatalogListByParentId(Ab_Database $db, $parentCatalogId){
		$sql = "
			SELECT
				".CatalogQuery::FIELD_CATALOGLIST." 
			FROM ".CatalogQuery::$PFX."catalog
			WHERE parentcatalogid=".bkint($parentCatalogId)."
			ORDER BY title
		";
		return $db->query_read($sql);
	}
	
	public static function ElementOptGroupAppend(Ab_Database $db, $elementTypeId, $title, $descript){
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
	
	public static function ElementOptGroupList(Ab_Database $db){
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
	
	public static function ElementOptionRemove(Ab_Database $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eloption
			SET 
				deldate=".TIMENOW."
			WHERE eloptionid=".bkint($id)."
		";
		$db->query_write($sql);
	}

	public static function ElementOptionRestore(Ab_Database $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eloption
			SET deldate=0
			WHERE eloptionid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementOptionSave(Ab_Database $db, $data){
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
	
	public static function ElementOptionRecycleClear(Ab_Database $db){
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
	
	/**
	 * Добавить опцию элемента в определенный тип
	 * 
	 * @param Ab_Database $db
	 * @param unknown_type $data
	 * @param unknown_type $prms
	 */
	public static function ElementOptionAppend(Ab_Database $db, $data, $prms){
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
			case CatalogQuery::OPTIONTYPE_CHILDELEMENT:
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
	
	public static function ElementOptionFieldTableCreate(Ab_Database $db, $eltypename, $fieldname){
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
	
	public static function ElementOptionFieldTableAppendValue(Ab_Database $db, $eltypename, $fieldname, $value){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname); 
		$sql = "INSERT INTO `".$tablename."` (title) VALUES ('".bkstr($value)."')";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function ElementOptionFieldTableValue(Ab_Database $db, $eltypename, $fieldname, $id){
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
	
	public static function ElementOptionFieldTableValueByTitle(Ab_Database $db, $eltypename, $fieldname, $title){
		$tablename = CatalogQuery::BuilElementOptionFieldTable($eltypename, $fieldname);
		$sql = "
			SELECT
				".$fieldname."id as id,
				title as tl
			FROM ".$tablename."
			WHERE title='".bkstr($title)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementOptionFieldTableList(Ab_Database $db, $eltypename, $fieldname){
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

	public static function ElementOptionByName(Ab_Database $db, $elementTypeId, $name){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTOPTION."
			FROM ".CatalogQuery::$PFX."eloption
			WHERE name='".bkstr($name)."' AND eltypeid=".bkint($elementTypeId)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	private static $_elementOptionListByType = array();
	
	/**
	 * Список опций элемента конкретного типа
	 *
	 * @param Ab_Database $db
	 * @param integer $elTypeId идентификатор типа элемента
	 * @param boolean $retarray вернуть массив
	 * @return resource || array
	 */
	public static function ElementOptionListByType(Ab_Database $db, $elTypeId, $retarray = false){
		$sql = "
			SELECT 
				".CatalogQuery::FIELD_ELEMENTOPTION."
			FROM ".CatalogQuery::$PFX."eloption
			WHERE eltypeid=".bkint($elTypeId)."
			ORDER BY eloptgroupid, ord
		";
		if (!$retarray){
			return $db->query_read($sql);
		}
		if (is_array(CatalogQuery::$_elementOptionListByType[$elTypeId])){
			return CatalogQuery::$_elementOptionListByType[$elTypeId];
		}
		$ret = array();
		$rows = $db->query_read($sql);
		while (($row = $db->fetch_array($rows))){
			array_push($ret, $row);
		}
		CatalogQuery::$_elementOptionListByType[$elTypeId] = $ret;
		return CatalogQuery::$_elementOptionListByType[$elTypeId];
	}
	
	public static function ElementOptionList(Ab_Database $db, $elTypeId = -1, $fieldType = -1){
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
	
	public static function TableList(Ab_Database $db){
		$sql = "
			SHOW TABLES FROM ".$db->database."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Очистка корзины и удаление всех связанных с удаляемыми записями обьекты
	 *
	 * @param Ab_Database $db
	 */
	public static function ElementTypeRecycleClear(Ab_Database $db){
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
	 * @param Ab_Database $db
	 * @param Integer $id
	 */
	public static function ElementTypeRemove(Ab_Database $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
			SET deldate=".TIMENOW."
			WHERE eltypeid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeRestore(Ab_Database $db, $id){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
			SET deldate=0
			WHERE eltypeid=".bkint($id)."
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableRemove(Ab_Database $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name);  
		$sql = "DROP TABLE `".$tablename."`";
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableCreate(Ab_Database $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name);  
		$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";

		$sql = "
			CREATE TABLE IF NOT EXISTS `".$tablename."` (
			`elementid` int(10) unsigned NOT NULL,
			PRIMARY KEY  (`elementid`)
		)".$charset;
		$db->query_write($sql);
	}
	
	public static function ElementTypeTableFieldList(Ab_Database $db, $name){
		$tablename = CatalogQuery::BuildElementTableName($name); 
		$sql = "SHOW COLUMNS FROM ".$tablename;
		return $db->query_read($sql);
	}

	public static function ElementTypeAppend(Ab_Database $db, $obj){
		$sql = "
			INSERT INTO ".CatalogQuery::$PFX."eltype
			(name, title, descript) VALUES
			(
				'".bkstr($obj->nm)."',
				'".bkstr($obj->tl)."',
				'".bkstr($obj->dsc)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function ElementTypeUpdate(Ab_Database $db, $obj){
		$sql = "
			UPDATE ".CatalogQuery::$PFX."eltype
			SET title='".bkstr($obj->tl)."',
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
	
	private static $_elementTypeById = array();
	
	public static function ElementTypeById(Ab_Database $db, $id){
		$id = bkint($id);
		if ($id == 0){ return array(); }
		
		if (!empty(CatalogQuery::$_elementTypeById[$id])){
			return CatalogQuery::$_elementTypeById[$id];
		}
		
		$sql = "
			SELECT
				eltypeid as id,
				title as tl,
				name as nm,
				descript as dsc,
				deldate as dd
			FROM ".CatalogQuery::$PFX."eltype
			WHERE eltypeid=".bkint($id)."
			LIMIT 1
		";
		CatalogQuery::$_elementTypeById[$id] = $db->query_first($sql); 
		return CatalogQuery::$_elementTypeById[$id];
	}
	
	public static function ElementTypeByName(Ab_Database $db, $name){
		$sql = "
			SELECT 
				eltypeid as id,
				title as tl,
				name as nm,
				descript as dsc,
				deldate as dd
			FROM ".CatalogQuery::$PFX."eltype
			WHERE name='".bkstr($name)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ElementTypeList(Ab_Database $db){
		$sql = "
			SELECT 
				eltypeid as id,
				title as tl,
				name as nm,
				descript as dsc,
				deldate as dd
			FROM ".CatalogQuery::$PFX."eltype
		";
		return $db->query_read($sql);
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