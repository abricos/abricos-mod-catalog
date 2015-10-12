<?php
/**
 * @package Abricos
 * @subpackage Catalog
 * @copyright 2009-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CatalogQuery
 */
class CatalogQuery {

    public static function ModuleManagerList(Ab_Database $db){
        $sql = "
			SELECT *
			FROM ".$db->prefix."ctg_module
		";
        return $db->query_read($sql);
    }

    public static function ModuleManagerUpdate(CatalogApp $app, $version){
        $db = $app->db;
        $name = $app->manager->module->name;

        $sql = "
			UPDATE ".$db->prefix."ctg_module
			SET version='".bkstr($version)."'
			WHERE name='".bkstr($name)."'
		";
        $db->query_write($sql);
    }

    public static function ModuleManagerAppend(CatalogApp $app){
        $sql = "
			INSERT INTO ".$app->db->prefix."ctg_module
			(name, dbprefix, version) VALUES (
				'".bkstr($app->manager->module->name)."',
				'".$app->Config()->dbPrefix."',
				'0.0.0'
			)
		";
        $app->db->query_write($sql);
    }

    public static function ModuleManagerUpdateLanguage(CatalogApp $app, $version){
        $db = $app->db;
        $name = $app->manager->module->name;
        $findCol = false;
        $lngId = 'language_'.Abricos::$LNG;
        $sql = "SHOW COLUMNS FROM ".$db->prefix."ctg_module";
        $rows = $db->query_read($sql);
        while (($row = $db->fetch_array($rows))){
            if ($row['Field'] === $lngId){
                $findCol = true;
            }
        }
        if (!$findCol){
            $db->query_write("ALTER TABLE ".$db->prefix."ctg_module ADD ".$lngId." varchar(20) NOT NULL default '0.0'");
        }
        $db->query_write("
			UPDATE ".$db->prefix."ctg_module
			SET ".$lngId."='".bkstr($version)."'
			WHERE name='".bkstr($name)."'
		");
    }


    const FILECLEARTIME = 86400;

    /**
     * @param CatalogApp $app
     */
    public static function CatalogList($app){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();

        $sql = "
			(SELECT
				0 as id,
				-1 as pid,
				'' as nm,
				'' as tl,
				0 as dl,
				0 as lvl,
				0 as mdsb,
				0 as ldsb,
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
				cat.menudisable as mdsb,
				cat.listdisable as ldsb,
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
			WHERE cat.deldate=0
			)
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
					0 as mdsb,
					0 as ldsb,
					0 as ord,
					count(*) as ecnt
				FROM ".$pfx."element e
				WHERE e.catalogid=0 AND e.deldate=0
			";
        } else {
            $sql = "
				SELECT
					cat.catalogid as id,
					cat.parentcatalogid as pid,
					cat.name as nm,
					cat.title as tl,
					cat.dateline as dl,
					cat.level as lvl,
					cat.menudisable as mdsb,
					cat.listdisable as ldsb,
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
				(parentcatalogid, title, name, imageid, descript, metatitle, metakeys, metadesc, menudisable, listdisable, ord, dateline) VALUES (
				".bkint($d->pid).",
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->foto)."',
				'".bkstr($d->dsc)."',
				'".bkstr($d->mtl)."',
				'".bkstr($d->mks)."',
				'".bkstr($d->mdsc)."',
				".bkint($d->mdsb).",
				".bkint($d->ldsb).",
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
				menudisable=".bkint($d->mdsb).",
				listdisable=".bkint($d->ldsb).",
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

    public static function ElementChangeLogListByName(Ab_Database $db, $pfx, $elname, CatalogElementOptionList $extOptions){
        $extFields = "";
        $cnt = $extOptions->Count();
        for ($i = 0; $i < $cnt; $i++){
            $option = $extOptions->GetByIndex($i);
            if ($option->elTypeId > 0){
                continue;
            }

            $extFields .= ", e.fld_".$option->name;
        }

        $sql = "
			SELECT
				e.elementid as id,
				e.prevelementid as pid,
				e.dateline as dl,
				e.version as v,
				e.changelog as chlg
				".$extFields."
			FROM ".$pfx."element e
			WHERE e.ismoder=0 AND e.name='".bkstr($elname)."' AND e.deldate=0
			ORDER BY e.version DESC
		";
        return $db->query_read($sql);
    }

    public static function ElementChangeLogList(Ab_Database $db, $pfx, CatalogElementOptionList $extOptions){
        $extFields = "";
        $cnt = $extOptions->Count();
        for ($i = 0; $i < $cnt; $i++){
            $option = $extOptions->GetByIndex($i);
            if ($option->elTypeId > 0){
                continue;
            }

            $extFields .= ", e.fld_".$option->name;
        }

        $sql = "
			SELECT
				e.elementid as id,
				e.prevelementid as pid,
				e.dateline as dl,
				e.version as v,
				e.changelog as chlg
				".$extFields."
			FROM ".$pfx."element e
			WHERE e.ismoder=0 AND e.deldate=0
			ORDER BY e.dateline DESC, e.version DESC
			LIMIT 100
		";
        return $db->query_read($sql);
    }

    /**
     * @param CatalogApp $app
     * @param CatalogELConfig $config
     */
    public static function ElementList($app, $config){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();
        $extFields = "";

        $sql = "
			SELECT
				e.elementid as id,
				e.catalogid as catid,
				e.eltypeid as tpid,
				e.title as tl,
				e.name as nm,
				e.ord as ord,
				e.userid as uid,
				e.dateline as dl,
				e.upddate as upd,

				e.version as v,
				e.prevelementid as pelid,
				e.changelog as chlg,

				e.ismoder as mdr,

				(
					SELECT CONCAT(f.fileid,'/',fm.extension)
					FROM ".$pfx."foto f
					LEFT JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
					WHERE f.elementid=e.elementid
					ORDER BY ord
					LIMIT 1
				) as foto
				".$extFields."
			FROM ".$pfx."element e
			WHERE (e.ismoder=0 OR
					".($app->IsAdminRole() ? "e.ismoder=1" : "(e.ismoder=1 AND userid=".bkint(Abricos::$user->id).")")."
				)
				AND e.isarhversion=0
				AND e.deldate=0
		";

        return $db->query_read($sql);
    }

    // TODO: remove
    public static function old_ElementList(Ab_Database $db, $pfx, $userid, $isAdmin, CatalogElementListConfig $cfg){

        $wCats = array();
        foreach ($cfg->catids as $catid){
            $wCats[] = "e.catalogid=".bkint($catid);
        }

        $wEls = array();
        foreach ($cfg->elids as $elid){
            if ($elid == 0){
                continue;
            }
            $wEls[] = "e.elementid=".bkint($elid);
        }
        foreach ($cfg->elnames as $elname){
            if (empty($elname)){
                continue;
            }
            $wEls[] = "e.name='".bkstr($elname)."'";
        }
        if (count($cfg->eltpids) > 0){
            foreach ($cfg->eltpids as $eltpid){
                $wEls[] = "e.eltypeid=".bkint($eltpid);
            }
        }

        $orders = "";
        $cnt = $cfg->orders->Count();
        for ($i = 0; $i < $cnt; $i++){
            $ord = $cfg->orders->GetByIndex($i);
            if ($ord->option->elTypeId > 0){
                continue;
            }

            if ($ord->zeroDesc){
                $fld = "fld_".$ord->option->name;
                $orders .= ", IF(".$fld.">0, 0, 1), ".$fld;

            } else {
                $orders .= ", fld_".$ord->option->name;
                if ($ord->isDesc){
                    $orders .= " DESC";
                }
            }

            // TODO: добавить сортировку по типу поля - таблица
        }

        $extFields = "";
        $cnt = $cfg->extFields->Count();
        for ($i = 0; $i < $cnt; $i++){
            $option = $cfg->extFields->GetByIndex($i);
            if ($option->elTypeId > 0){
                continue;
            }

            $extFields .= ", e.fld_".$option->name;
        }

        $wExt = array();
        $cnt = $cfg->where->Count();
        for ($i = 0; $i < $cnt; $i++){
            $ord = $cfg->where->GetByIndex($i);

            if ($ord->option->elTypeId > 0){
                continue;
            }

            $wExt[] = "e.fld_".$ord->option->name."".$ord->exp;
        }

        $sql = "
			SELECT
				e.elementid as id,
				e.catalogid as catid,
				e.eltypeid as tpid,
				e.title as tl,
				e.name as nm,
				e.ord as ord,
				e.userid as uid,
				e.dateline as dl,
				e.upddate as upd,
				
				e.version as v,
				e.prevelementid as pelid,
				e.changelog as chlg,
				
				e.ismoder as mdr,
				
				(
					SELECT CONCAT(f.fileid,'/',fm.extension)
					FROM ".$pfx."foto f
					LEFT JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
					WHERE f.elementid=e.elementid
					ORDER BY ord
					LIMIT 1
				) as foto
				".$extFields."
			FROM ".$pfx."element e
			WHERE (e.ismoder=0 OR 
					".($isAdmin ? "e.ismoder=1" : "(e.ismoder=1 AND userid=".bkint($userid).")")."
				) 
				AND e.isarhversion=0 
				AND e.deldate=0
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

        if (!$isWhere){
            return null;
        }

        $sql .= "
		 	ORDER BY mdr DESC, ord DESC".$orders.", e.dateline
		";

        if ($cfg->limit > 0){
            $from = $cfg->limit * (max(1, $cfg->page) - 1);
            $sql .= "
				LIMIT ".$from.", ".$cfg->limit."
			";
        }
        return $db->query_read($sql);
    }

    public static function ElementListCount(Ab_Database $db, $pfx, $userid, $isAdmin, CatalogElementListConfig $cfg){
        $wCats = array();
        foreach ($cfg->catids as $catid){
            $wCats[] = "e.catalogid=".bkint($catid);
        }
        $wEls = array();
        foreach ($cfg->elids as $elid){
            $wEls[] = "e.elementid=".bkint($elid);
        }

        if (count($wCats) == 0 && count($wEls) == 0){
            return 0;
        }

        $sql = "
			SELECT count(*) as cnt
			FROM ".$pfx."element e
			WHERE (e.ismoder=0 OR 
					".($isAdmin ? "e.ismoder=1" : "(e.ismoder=1 AND userid=".bkint($userid).")")."
				) 
				AND e.isarhversion=0 
				AND e.deldate=0
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
				e.userid as uid,
				e.dateline as dl,
				e.upddate as upd,
				
				e.version as v,
				e.prevelementid as pelid,
				e.changelog as chlg,
				e.ismoder as mdr,
				
				(
					SELECT CONCAT(f.fileid,'/',fm.extension)
					FROM ".$pfx."foto f
					LEFT JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
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

    public static function ElementByName(Ab_Database $db, $pfx, $userid, $isAdmin, $name){
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
				e.userid as uid,
				e.dateline as dl,
				e.upddate as upd,
				
				e.version as v,
				e.prevelementid as pelid,
				e.changelog as chlg,
				
				e.ismoder as mdr,
				
				(
					SELECT CONCAT(f.fileid,'/',fm.extension)
					FROM ".$pfx."foto f
					LEFT JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
					WHERE f.elementid=e.elementid
					ORDER BY ord
					LIMIT 1
				) as foto
			FROM ".$pfx."element e
			WHERE (e.ismoder=0 OR 
					".($isAdmin ? "e.ismoder=1" : "(e.ismoder=1 AND userid=".bkint($userid).")")."
				) 
				AND e.isarhversion=0 AND e.name='".bkstr($name)."'
				AND e.deldate=0
			LIMIT 1
		";
        return $db->query_first($sql);
    }


    public static function ElementDetail(Ab_Database $db, $pfx, $elid, CatalogElementType $elType){
        $options = $elType->options;
        $fields = array();
        for ($i = 0; $i < $options->Count(); $i++){
            $option = $options->GetByIndex($i);
            $fields[] = "e.fld_".$option->name." as ".$option->name."";
        }
        $sql = "
			SELECT
				e.elementid as id
				".(count($fields) > 0 ? ",".implode(",", $fields) : "")."
			FROM ".$pfx.$elType->tableName." e
			WHERE e.elementid=".bkint($elid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function ElementAppend(Ab_Database $db, $pfx, $userid, $isOperator, $d){
        $sql = "
			INSERT INTO ".$pfx."element
			(catalogid, eltypeid, userid, title, name, metatitle, metakeys, metadesc, ord, 
				version, prevelementid, changelog, ismoder, dateline) VALUES (
				".bkint($d->catid).",
				".bkint($d->tpid).",
				".bkint($userid).",
				'".bkstr($d->tl)."',
				'".bkstr($d->nm)."',
				'".bkstr($d->mtl)."',
				'".bkstr($d->mks)."',
				'".bkstr($d->mdsc)."',
				".bkint($d->ord).",
				".bkint($d->v).",
				".bkint($d->pelid).",
				'".bkstr($d->chlg)."',
				".($isOperator ? 1 : 0).",
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
				changelog='".bkstr($d->chlg)."',
				ord=".bkint($d->ord).",
				upddate=".TIMENOW."
			WHERE elementid=".bkint($elid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function ElementToArhive(Ab_Database $db, $pfx, $elid){
        $sql = "
			UPDATE ".$pfx."element
			SET isarhversion=1
			WHERE elementid=".bkint($elid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function ElementModer(Ab_Database $db, $pfx, $elid){
        $sql = "
			UPDATE ".$pfx."element
			SET ismoder=0
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

    /**
     * Обновить значение опций элемента
     *
     * @param Ab_Database $db
     * @param string $pfx
     * @param integer $elid
     * @param CatalogElementType $elType
     * @param object $d
     */
    public static function ElementDetailUpdate(Ab_Database $db, $pfx, $userid, $isAdmin, $elid, CatalogElementType $elType, $d){
        $options = $elType->options;
        if ($options->Count() == 0){
            return;
        }

        $insfld = array();
        $insval = array();
        $upd = array();

        $utm = Abricos::TextParser();
        $utmf = Abricos::TextParser(true);

        foreach ($d as $optName => $val){
            $option = $options->GetByName($optName);
            if (empty($option)){
                continue;
            }

            switch ($option->type){
                case CatalogType::TP_BOOLEAN:
                    $val = empty($val) ? 0 : 1;
                    break;
                case CatalogType::TP_NUMBER:
                    $val = bkint($val);
                    break;
                case CatalogType::TP_DOUBLE:
                case CatalogType::TP_CURRENCY:
                    $val = doubleval($val);
                    break;
                case CatalogType::TP_STRING:
                    $val = "'".bkstr($utmf->Parser($val))."'";
                    break;
                case CatalogType::TP_TABLE:
                    $val = bkint($val);
                    break;
                case CatalogType::TP_TEXT:
                    $val = "'".bkstr($utm->Parser($val))."'";
                    break;
                case CatalogType::TP_ELDEPENDS:
                    $cfg = new CatalogElementListConfig();
                    $cfg->elids = explode(",", $val);
                    $rows = CatalogQuery::ElementList($db, $pfx, $userid, $isAdmin, $cfg);
                    $aIds = array();
                    while (($d = $db->fetch_array($rows))){
                        $aIds[] = $d['id'];
                    }
                    $val = "'".implode(",", $aIds)."'";
                    break;
                case CatalogType::TP_ELDEPENDSNAME:
                    $cfg = new CatalogElementListConfig();
                    $cfg->elnames = explode(",", $val);
                    $rows = CatalogQuery::ElementList($db, $pfx, $userid, $isAdmin, $cfg);
                    $aNames = array();
                    while (($d = $db->fetch_array($rows))){
                        $aNames[] = $d['nm'];
                    }
                    $val = "'".implode(",", $aNames)."'";
                    break;
                case CatalogType::TP_FILES:
                    $aFiles = CatalogQuery::ElementDetailOptionFilesUpdate($db, $pfx, $elid, $option, $val);

                    $val = "'".implode(",", $aFiles)."'";
                    break;
                default:
                    $val = bkstr($val);
                    break;
            }
            $insfld[] = "fld_".$optName;
            $insval[] = $val;
            $upd[] = "fld_".$optName."=".$val;
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

    public static function ElementDetailOptionFilesUpdate(Ab_Database $db, $pfx, $elid, CatalogElementOption $option, $files){
        $aFiles = explode(",", trim($files));
        if (count($aFiles) == 0){
            return;
        }

        // все имеющиеся файлы пометить как временные
        $sql = "
			UPDATE ".$pfx."file
			SET elementid=0,
				dateline=".TIMENOW."
			WHERE elementid=".bkint($elid)." AND eloptionid=".bkint($option->id)."
		";
        $db->query_write($sql);

        // сохранить в список файлов только те файлы, которые выгружены только через этот модуль
        $aWhere = array();
        for ($i = 0; $i < count($aFiles); $i++){
            $afi = explode(":", $aFiles[$i]);
            $aWhere[] = " filehash='".$afi[0]."' ";
        }
        $sql = "
			UPDATE ".$pfx."file
			SET elementid=".bkint($elid)."
			WHERE elementid=0 AND eloptionid=".bkint($option->id)." AND (".implode(" OR ", $aWhere).")
		";
        $db->query_write($sql);

        // новый список файлов
        $sql = "
			SELECT 
				filehash as fh,
				filename as fn
			FROM ".$pfx."file
			WHERE elementid=".bkint($elid)." AND eloptionid=".bkint($option->id)."
		";
        $rows = $db->query_read($sql);

        $nfList = array();
        while (($row = $db->fetch_array($rows))){
            $nfList[] = $row['fh'].":".$row['fn'];
        }
        return $nfList;
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

    public static function ElementTypeTableCreate(CatalogApp $app, CatalogElementType $elType){
        $tableName = CatalogElementType::GetTableName($app, $elType);
        $db = $app->db;
        $sql = "
			CREATE TABLE IF NOT EXISTS ".$tableName." (
				elementid int(10) unsigned NOT NULL,
				PRIMARY KEY (elementid)
			) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
		";
        $db->query_write($sql);
    }

    public static function ElementTypeTableChange(Ab_Database $db, $oldTableName, $newTableName){
        $sql = "
			RENAME TABLE ".$oldTableName." TO ".$newTableName."
		";
        $db->query_write($sql);
    }

    public static function ElementTypeTableRemove(Ab_Database $db, $tableName){
        $sql = "
			DROP TABLE IF EXISTS ".$tableName."
		";
        $db->query_write($sql);
    }

    public static function ElementTypeAppend(CatalogApp $app, CatalogElementType $elType){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();
        $sql = "
			INSERT INTO ".$pfx."eltype
				(name, title_".Abricos::$LNG.", titlelist_".Abricos::$LNG.", descript_".Abricos::$LNG.", dateline) VALUES (
				'".bkstr($elType->name)."',
				'".bkstr($elType->title->Get())."',
				'".bkstr($elType->titles->Get())."',
				'".bkstr($elType->descript->Get())."',
				".TIMENOW."
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
				titlelist='".bkstr($d->tls)."',
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

    public static function ElementTypeList(CatalogApp $app){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();
        $sql = "
			SELECT t.eltypeid as id, t.*
			FROM ".$pfx."eltype t
			WHERE t.deldate=0
		";
        return $db->query_read($sql);
    }

    public static function ElementOptionGroupList(Ab_Database $db, $pfx){
        $sql = "
			SELECT
				eloptgroupid as id,
				title as tl,
				name as nm,
				eltypeid as tpid
			FROM ".$pfx."eloptgroup
			ORDER BY ord DESC, title
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

    public static function ElementOptionFieldRemove(Ab_Database $db, $pfx, CatalogElementType $elType, CatalogElementOption $option){
        if ($option->type == CatalogType::TP_TABLE){
            $tableName = $pfx."eltbl_".$elType->name."_fld_".$option->name;
            $db->query_write("DROP TABLE IF EXISTS ".$tableName);
            return;
        }

        $tableName = CatalogQuery::ElementTypeTableName($pfx, $elType->name);
        $sql = "
			ALTER TABLE ".$tableName." DROP fld_".$option->name."
		";
        $db->query_write($sql);
    }

    public static function ElementOptionAppend(CatalogApp $app, CatalogElementOption $option){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();
        $sql = "
			INSERT INTO ".$pfx."eloption
			(eltypeid, fieldtype, fieldsize, eloptgroupid, name, title, descript, currencyid, ord, dateline) VALUES (
				".bkint($option->elTypeId).",
				".bkint($option->type).",
				'".bkstr($option->size)."',
				".bkint($option->groupid).",
				'".bkstr($option->name)."',
				'".bkstr($option->title->Get())."',
				'".bkstr($option->descript->Get())."',
				".bkint($option->currencyid).",
				".bkint($option->order).",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function ElementOptionUpdate(Ab_Database $db, $pfx, $optionid, $d){
        $sql = "
			UPDATE ".$pfx."eloption
			SET eloptgroupid=".bkint($d->gid).",
				name='".bkstr($d->nm)."',
				title='".bkstr($d->tl)."',
				descript='".bkstr($d->dsc)."',
				currencyid=".bkint($d->crcid).",
				ord=".bkint($d->ord).",
				param='".bkstr($d->prm)."'
			WHERE eloptionid=".bkint($optionid)."
		";
        $db->query_write($sql);
    }

    public static function ElementOptionTypeUpdate(Ab_Database $db, $pfx, $optionid, $d){
        $sql = "
			UPDATE ".$pfx."eloption
			SET fieldtype=".bkint($d->tp)."
			WHERE eloptionid=".bkint($optionid)."
		";
        $db->query_write($sql);
    }

    public static function ElementOptionFieldUpdate(Ab_Database $db, $pfx, CatalogElementType $elType, $tableName, CatalogElementOption $oldOption, $d){
        $optionName = bkstr($d->nm);

        if ($d->tp == CatalogType::TP_TABLE){
            $tableName = $pfx."eltbl_".$elType->name."_fld_".$d->nm;
            $oldTableName = $pfx."eltbl_".$elType->name."_fld_".$oldOption;

            $sql = "
				RENAME TABLE ".$oldTableName." TO ".$tableName."
			";
            $db->query_write($sql);
            return;
        }
        $sql = "
			ALTER TABLE ".$tableName." CHANGE fld_".$oldOption->name." fld_".$optionName."
		";

        switch ($oldOption->type){
            case CatalogType::TP_BOOLEAN:
                $sql .= "INT(1) UNSIGNED NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_NUMBER:
                $sql .= "INT(".$oldOption->size.") NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_DOUBLE:
            case CatalogType::TP_CURRENCY:
                $sql .= "DOUBLE(".$oldOption->size.") NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_STRING:
                $sql .= "VARCHAR(".$oldOption->size.") NOT NULL DEFAULT ''";
                break;
            case CatalogType::TP_TABLE:
                $sql .= "INT(10) NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_TEXT:
            case CatalogType::TP_ELDEPENDS:
            case CatalogType::TP_ELDEPENDSNAME:
            case CatalogType::TP_FILES:
                $sql .= "TEXT NOT NULL ";
                break;
        }
        $db->query_write($sql);
    }

    public static function ElementOptionFieldCreate(CatalogApp $app, CatalogElementType $elType, CatalogElementOption $option){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();

        if ($option->type === CatalogType::TP_TABLE){

            $fldTableName = CatalogElementOption::GetTableName($app, $elType, $option);

            $sql = "
				CREATE TABLE IF NOT EXISTS ".$fldTableName." (
					".$option->name."id int(10) unsigned NOT NULL auto_increment,
					title varchar(250) NOT NULL default '',
					PRIMARY KEY  (".$option->name."id)
				) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
			";
            $db->query_write($sql);
        }

        $tableName = CatalogElementType::GetTableName($app, $elType);

        $sql = "ALTER TABLE ".$tableName." ADD fld_".$option->name." ";
        switch ($option->type){
            case CatalogType::TP_BOOLEAN:
                $sql .= "INT(1) UNSIGNED NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_NUMBER:
                $sql .= "INT(".$option->size.") NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_DOUBLE:
            case CatalogType::TP_CURRENCY:
                $sql .= "DOUBLE(".$option->size.") NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_STRING:
                $sql .= "VARCHAR(".$option->size.") NOT NULL DEFAULT ''";
                break;
            case CatalogType::TP_TEXT:
                $sql .= "TEXT NOT NULL ";
                break;
            case CatalogType::TP_TABLE:
                $sql .= "INT(10) NOT NULL DEFAULT 0";
                break;
            case CatalogType::TP_ELDEPENDS:
                // содержит в себе кеш идентификаторов элементов  через запятую
                // основная таблица зависимых модулей - eldepends
                $sql .= "TEXT NOT NULL ";
                break;
            case CatalogType::TP_ELDEPENDSNAME:
                // содержит в себе кеш имен элементов через запятую
                // основная таблица зависимых модулей - eldependsname
                $sql .= "TEXT NOT NULL ";
                break;
            case CatalogType::TP_FILES:
                // содержит в себе кеш идентификаторов файлов через запятую
                // основная таблица зависимых модулей - file
                $sql .= "TEXT NOT NULL ";
                break;
        }
        $sql .= " COMMENT '".bkstr($option->title->Get())."'";
        $db->query_write($sql);
    }

    public static function ElementOptionTableCreate(Ab_Database $db, $elTypeTableName, $optionName){
        $optionName = bkstr($optionName);
        $tableName = $elTypeTableName."_fld_".$optionName;

        $sql = "
			CREATE TABLE IF NOT EXISTS ".$tableName." (
				".$optionName."id int(10) unsigned NOT NULL auto_increment,
				title varchar(250) NOT NULL default '',
				 PRIMARY KEY  (".$optionName."id)
			) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
		";
        $db->query_write($sql);
    }

    public static function ElementOptionList(CatalogApp $app){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();
        $sql = "
			SELECT o.eloptionid as id, o.*
			FROM ".$pfx."eloption o
			WHERE o.deldate=0
			ORDER BY o.eltypeid, o.ord DESC, o.title
		";
        return $db->query_read($sql);
    }

    public static function OptionTableValueList(CatalogApp $app, $tpName, $optName){
        $db = $app->db;
        $pfx = $app->GetDBPrefix();

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
			INSERT INTO ".$pfx."foto (fileid, dateline) VALUES (
				'".bkstr($fhash)."',
				".TIMENOW."
			)
		";
        $db->query_write($sql);
    }

    public static function FotoFreeFromBufferList(Ab_Database $db, $pfx){
        $sql = "
			SELECT
				fotoid as id, 
				fileid as fh
			FROM ".$pfx."foto
			WHERE elementid=0 AND dateline<".(TIMENOW - CatalogQuery::FILECLEARTIME)."
		";
        return $db->query_read($sql);
    }

    public static function FotoFreeListClear(Ab_Database $db, $pfx){
        $sql = "
			DELETE FROM ".$pfx."foto
			WHERE elementid=0 AND dateline<".(TIMENOW - CatalogQuery::FILECLEARTIME)."
		";
        return $db->query_read($sql);
    }

    public static function OptionFileAddToBuffer(Ab_Database $db, $pfx, $userid, $optionid, $fhash, $fname){
        $sql = "
			INSERT INTO ".$pfx."file (userid, eloptionid, filehash, filename, dateline) VALUES (
				".bkint($userid).",
				".bkint($optionid).",
				'".bkstr($fhash)."',
				'".bkstr($fname)."',
				".TIMENOW."
			)
		";
        $db->query_write($sql);
    }

    public static function OptionFileFreeFromBufferList(Ab_Database $db, $pfx){
        $sql = "
			SELECT
				fileid as id,
				filehash as fh
			FROM ".$pfx."file
			WHERE elementid=0 AND dateline<".(TIMENOW - CatalogQuery::FILECLEARTIME)."
		";
        return $db->query_read($sql);
    }

    public static function OptionFileFreeListClear(Ab_Database $db, $pfx){
        $sql = "
			DELETE FROM ".$pfx."file
			WHERE elementid=0 AND dateline<".(TIMENOW - CatalogQuery::FILECLEARTIME)."
		";
        return $db->query_read($sql);
    }

    /**
     * Получить список картинок элемента
     *
     * В качестве параметра $elid может быть идентификатор элемента или массив
     * идентификаторов
     *
     * @param Ab_Database $db
     * @param string $pfx
     * @param integer|array $elid
     */
    public static function ElementFotoList(Ab_Database $db, $pfx, $elids){
        if (!is_array($elids)){
            $elid = array($elids);
        }
        if (count($elids) == 0){
            return null;
        }

        $aWh = array();
        foreach ($elids as $elid){
            $aWh[] = "f.elementid=".bkint($elid)."";
        }

        $sql = "
			SELECT
				fm.filehash as id,
				f.elementid as elid,
				fm.filehash as f,
				fm.filename as nm,
				fm.extension as ext,
				fm.filesize as sz,
				fm.imgwidth as w,
				fm.imgheight as h
			FROM ".$pfx."foto f
			INNER JOIN ".$db->prefix."fm_file fm ON f.fileid=fm.filehash
			WHERE ".implode(" OR ", $aWh)."
			ORDER BY f.elementid, f.ord
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
        for ($i = 0; $i < count($fotos); $i++){
            $vals[] = "(".bkint($elementid).", '".bkstr($fotos[$i])."', ".$i.")";
        }

        if (count($vals) == 0){
            return;
        }

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

    public static function SearchAutoComplete(Ab_Database $db, $pfx, $query, $eFField = '', $eFValue = 0){
        $sql = "
			SELECT
				catalogid as id, 
 				title as tl,
 				'c' as tp
 			FROM ".$pfx."catalog
 			WHERE deldate=0 AND title LIKE '%".bkstr($query)."%'
 			LIMIT 3
 			
 			UNION

 			SELECT
 				elementid as id,
 				title as tl,
 				'e' as tp
 			FROM ".$pfx."element e
 			WHERE deldate=0 AND title LIKE '%".bkstr($query)."%'
		";
        if ($eFValue > 0){
            $sql .= "
				AND fld_".bkstr($eFField)."=".bkint($eFValue)."
			";
        }
        $sql .= "
 			LIMIT 17
		";
        return $db->query_read($sql);
    }

    public static function Search(Ab_Database $db, $pfx, $query, $eFField = '', $eFValue = 0){
        $sql = "";

        if (!empty($query)){
            $sql .= "
				SELECT
					catalogid as id,
					title as tl,
					'c' as tp
				FROM ".$pfx."catalog
				WHERE deldate=0 AND title LIKE '%".bkstr($query)."%'
				LIMIT 3
			
				UNION
			";
        }

        $sql .= "
			SELECT
				elementid as id,
				title as tl,
				'e' as tp
			FROM ".$pfx."element e
			WHERE deldate=0 AND title LIKE '%".bkstr($query)."%'
		";
        if ($eFValue > 0){
            $sql .= "
				AND fld_".bkstr($eFField)."=".bkint($eFValue)."
			";
        }

        $sql .= "
			LIMIT 47
		";

        return $db->query_read($sql);
    }

    public static function UserRatingSQLExt(Ab_Database $db){
        $urt = new stdClass();
        $urt->fld = "";
        $urt->tbl = "";
        $userid = Abricos::$user->id;

        $modURating = Abricos::GetModule("urating");

        if (!empty($modURating) && $userid > 0){
            $urt->fld .= "
				,IF(ISNULL(urt.reputation), 0, urt.reputation) as rep,
				IF(ISNULL(urt.skill), 0, urt.skill) as rtg
			";
            $urt->tbl .= "
				LEFT JOIN ".$db->prefix."urating_user urt ON u.userid=urt.userid
			";
        }

        return $urt;
    }


    public static function UserList(Ab_Database $db, $uids){
        if (count($uids) == 0){
            return null;
        }

        $urt = CatalogQuery::UserRatingSQLExt($db);

        $aWh = array();
        foreach ($uids as $uid){
            $aWh[] = "u.userid=".bkint($uid);
        }

        $sql = "
			SELECT
				u.userid as id,
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm,
				u.email as eml
				".$urt->fld."
			FROM ".$db->prefix."user u
			".$urt->tbl."
			WHERE ".implode(" OR ", $aWh)."
		";
        return $db->query_read($sql);
    }

    public static function ElementOptionFileList(Ab_Database $db, $pfx, $elids){
        if (count($elids) == 0){
            return null;
        }

        $aWh = array();
        foreach ($elids as $elid){
            $aWh[] = "ef.elementid=".bkint($elid);
        }
        $sql = "
			SELECT 
				ef.filehash as id,
				ef.filename as fn,
				f.counter as cnt,
				f.filesize as sz,
				f.dateline as dl
			FROM ".$pfx."file ef
			INNER JOIN ".$db->prefix."fm_file f ON f.filehash=ef.filehash
			WHERE ".implode(" OR ", $aWh)."
		";
        return $db->query_read($sql);
    }

    public static function StatisticElementList(Ab_Database $db, $pfx){
        $sql = "
			SELECT 
				e.catalogid as catid,
				e.eltypeid as tpid,
				count(*) as cnt
			FROM ".$pfx."element e
			WHERE e.ismoder=0
				AND e.isarhversion=0 
				AND e.deldate=0
			GROUP BY e.catalogid, e.eltypeid
		";
        return $db->query_read($sql);
    }

    /* * * * * * * * * * * Currency * * * * * * * * * */

    public static function CurrencyList(Ab_Database $db, $pfx){
        $sql = "
			SELECT currencyid as id, c.*
			FROM ".$pfx."currency c
			WHERE c.deldate=0
		";
        return $db->query_read($sql);
    }

    public static function CurrencyAppend(Ab_Database $db, $pfx, $d){
        $d = array_to_object($d);
        $sql = "
			INSERT INTO ".$pfx."currency
				(isdefault, title, codestr, codenum, rateval, ratedate, prefix, postfix, ord, dateline) VALUES (
				".intval($d->isdefault).",
				'".bkstr($d->title)."',
				'".bkstr($d->codestr)."',
				".intval($d->codenum).",
				".doubleval($d->rateval).",
				".intval($d->ratedate).",
				'".bkstr($d->prefix)."',
				'".bkstr($d->postfix)."',
				".intval($d->ord).",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function CurrencyUpdate(Ab_Database $db, $pfx, $d){
        if ($d->isdefault){
            $sql = "
                UPDATE ".$pfx."currency
                SET isdefault=0
                WHERE currencyid<>".bkint($d->id)."
            ";
            $db->query_write($sql);
        }

        $sql = "
			UPDATE ".$pfx."currency
			SET
			    isdefault=".intval($d->isdefault).",
			    title='".bkstr($d->title)."',
			    codestr='".bkstr($d->codestr)."',
			    codenum=".intval($d->codenum).",
			    rateval=".doubleval($d->rateval).",
			    ratedate=".intval($d->ratedate).",
			    prefix='".bkstr($d->prefix)."',
			    postfix='".bkstr($d->postfix)."'
			WHERE currencyid=".bkint($d->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function CurrencyRemove(Ab_Database $db, $pfx, $currencyId){
        $sql = "
			DELETE FROM ".$pfx."currency
			WHERE currencyid=".bkint($currencyId)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

}

?>