/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2010 Abricos. All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/**
 * @module Catalog
 * @namespace Brick.mod.catalog
 */
var Component = new Brick.Component();
Component.requires = {
	yahoo: ['json'],
	mod:[
	     {name: 'sys', files: ['form.js','data.js','editor.js','container.js']},
	     {name: 'catalog', files: ['catalog.js']}
    ]
};
Component.entryPoint = function(){
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		J = YAHOO.lang.JSON;
	
	var NS = this.namespace,
		TMG = this.template;
	
	var API = NS.API;

	NS.data = NS.data || {};
	var DATA = NS.data;
	
	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};
	
	var pathTitle = function(catalogid, mmPrefix){
		var get = function(id){
			var row = NS.data[mmPrefix].get('catalog').getRows().getById(id);
			if (L.isNull(row)){ return ''; }
			var d = row.cell;
			var ret = d['tl'];
			if (d['pid']>0){
				ret = get(d['pid'])+' / '+ret;
			}
			return ret;
		};
		return get(catalogid);
	};
	
	// Редактор свободного элемента
	var ElementEditorPanel = function(mmPrefix, row, callback){

		this.tables = DATA[mmPrefix].tables;
		
		// список редакторов
		this._editors = {};
		
		this.mmPrefix = mmPrefix;
		this.row = row;
		if (row.isNew()){
			row.cell['session'] = Math.round(((new Date()).getTime()/1000));
		}
		this.callback = callback;
		this.uploadWindow = null;
		ElementEditorPanel.activeEditor = this;

		
		ElementEditorPanel.superclass.constructor.call(this,{
			modal: true, fixedcenter: true,
			overflow: true,
			width: '790px',
			height: '400px'
		});
	};
	YAHOO.extend(ElementEditorPanel, Brick.widget.Panel, {
		initTemplate: function(){
		
			buildTemplate(this, 'editor,editoptrowonload,editoptrowcust,editoptrow0,editoptrow1,editoptrow4,editoptrow5,editoptrow6,editoptrow7,seloptionrow,fotoitem'); 

			var o = this.row.cell;
			var ds = DATA[this.mmPrefix];
			var catElementId = 0;
			var elementId = 0;
			if (!this.row.isNew()){
				catElementId = this.row.id;
				elementId = o['elid'];
			}

			var eltype = ds.get('eltype').getRows().getById(o['eltid']);
			this.elementType = !L.isNull(eltype) ? eltype : {
				'cell': {'id': '0', 'tl': '', 'nm': '' }
			};
			o['eltid'] = o['eltid'] || '0';
			var rows = this.rows = {
				'eloption': ds.get('eloption').getRows().filter({'eltid': o['eltid']}),
				'eloptgroup': ds.get('eloptgroup').getRows().filter({'eltid': o['eltid']})
			};

			var fotos = {};
			if (!this.row.isNew()){
				ds.get('fotos').getRows({'elid': elementId}).foreach(function(row){
					fotos[row.cell['fid']] = row.cell['fid'];  
				});
			}
			this.fotos = fotos;

			// Построение опций элемента
			// участвующие в мульти не участвуют в общем списке
			var rowsMulti = this.rows['eloption'].filter({'fldtp': 6});
			rowsMulti.foreach(function(row){
				var di = row.cell;
				var prm = J.parse(di['prms']) || {};
				var list = prm['val'].split('\n');
				for (var j=0;j<list.length;j++){
					var row = rows['eloption'].get('nm', list[j]);
					if (row){ row['usedmulti'] = true; }
				}
			}, this);
			var __self = this;
			return this._TM.replace('editor', {
				'catalog': pathTitle(o['catid'], this.mmPrefix),
				'eltype': L.isNull(eltype) ? '' : eltype.cell['tl'],
				'options': (function(){
					var lst = '';
					rows['eloption'].foreach(function(row){
						lst += __self.buildRow(row, false); 
					});
					return lst;
				})()
			});
		},
		destroy: function(){
			ElementEditorPanel.activeEditor = null;
			ElementEditorPanel.superclass.destroy.call(this);
		},
		el: function(name){ return Dom.get(this._TId['editor'][name]); },
		elOnLoad: function(t, func){ func(t, tSetVar); },
		buildRow: function(row, child){
			if (!child && row['usedmulti']){ return ""; }

			var ds = DATA[this.mmPrefix];
			Brick.namespace('Catalog.Element.temp');
			
			var TM = this._TM, T = this._T, TId = this._TId;

			var di = row.cell;
			var i, lst = "", s, prm, list, j, lists, ss, tt;
			prm = J.parse(di['prms']) || {};

			switch (di['fldtp']){
			case '0': s = T['editoptrow0']; break;
			case '1': case '2': case '3':  s = T['editoptrow1']; break;
			case '4':
				s = TM.replace('editoptrow4', {
					'list': (function(){
						var list = prm['val'].split('\n'), lst = '';
						for (j=0;j<list.length;j++){
							lst += TM.replace('seloptionrow', {
								'id': j, 'tl': list[j]
							});
						}
						return lst;
					})()
				});
				break;
			case '5':
				var lst = '';
				ds.get('eloptionfld').getRows({'eltpnm': this.elementType.cell['nm'], 'fldnm': di['nm']}).foreach(function(row){
					lst += TM.replace('seloptionrow', {
						'id': row.cell['id'],
						'tl': row.cell['tl']
					});
				});
				s = TM.replace('editoptrow5', {'list': lst});
				break;
			case '6':
				s = T['editoptrow6'];
				list = prm['val'].split('\n');
				var rows = this.rows['eloption'], lst = '';
				for (j=0;j<list.length;j++){
					var row = rows.get('nm', list[j]);
					if (row){
						lists += this.buildRow(row, true);
					}
				}					
				s = TM.replace('editoptrow6', {'list': lst})
				break;
			case '7': s = T['editoptrow7']; break;
			default: s = ''; break;
			}
			var tSetVar = Brick.util.Template.setProperty;
			s = tSetVar(s, 'id', di['nm']);
			s = tSetVar(s, 'title', di['tl']);

			return s;
		},
		onLoad: function(){
			var TId = this._TId;
			var rows = this.rows['eloption'];
			var element = this.row;
			rows.foreach(function(row){
				var di = row.cell;
				switch(di['fldtp']){
				case '0': case '1': case '2': case '3': case '4': case '5': case '8':
					var el = Dom.get(TId['_global']['opt']+'-'+di['nm']);
					Brick.util.Form.setValue(el, element.cell['fld_'+di['nm']]);
					break;
				case '7': // прикрепляем визуальный редактор к текстовому полю
					var edId = TId['_global']['opt']+'-'+di['nm'];
					var el = Dom.get(edId);
					var Editor = Brick.widget.Editor;
					this._editors[edId] = new Editor(el, {
						width: '600px', height: '450px', 'mode': Editor.MODE_VISUAL
					});
					Brick.util.Form.setValue(el, element.cell['fld_'+di['nm']]);
					break;
				}
			}, this);
			this.fotoRender();
			
			this.catalogWidget = new NS.CatalogSelectWidget(this._TM.getEl('editor.catalog'), this.mmPrefix);
			this.catalogWidget.setValue(element.cell['catid']);
		},
		onClose: function(){
			// убиваем визуальный редактор
			for (var nn in this._editors){
				this._editors[nn].destroy();
			}
		},
		onClick: function(el){
			var TId = this._TId;

			var arr = el.id.split('-');
			
			if (arr[0] == TId['fotoitem']['id']){
				this.imageRemove(arr[1]);
				return true;
			}
			
			var tp = TId['editor']; 
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bsave']: this.save(); return true;
			case tp['imgload']:
				this.imageUpload();
				break;
			}
			return false;
		},
		save: function(){
			var TId = this._TId;

			var element = this.row;
			var rows = this.rows['eloption'];
			var options = {};
			rows.foreach(function(row){
				var di = row.cell;
				
				switch(di['fldtp']){
				case '0': case '1': case '2': case '3': case '4': case '5': case '8':
					var el = Dom.get(TId['_global']['opt']+'-'+di['nm']);
					options['fld_'+di['nm']] = Brick.util.Form.getValue(el);
					break;
				case '7':
					// Получаем данные из визуального редактора
					var edId = TId['_global']['opt']+'-'+di['nm'];
					var el = Dom.get(edId);
					options['fld_'+di['nm']] = this._editors[edId].getContent();
					break;
				}
				if (di['fldtp']=='5'){
					var newval = Brick.util.Form.getValue(Dom.get(TId['_global']['opt']+'-'+di['nm']+'-alt'));
					if (newval.length > 0){
						options['fld_'+di['nm']+'-alt'] = newval;
						this.tables['eloptionfld'].getRows({'eltpnm': this.elementType.cell['nm'], 'fldnm': di['nm']}).clear();
					}
				}
			}, this);

			var afotos = [];
			for (var fid in this.fotos){
				afotos[afotos.length] = fid;
			}
			options['fids'] = afotos.join(",");
			options['catid'] = this.catalogWidget.getValue();

			this.row.update(options);
			
			if (!this.row.isNew()){
				DATA[this.mmPrefix].get('fotos').getRows({'elid': this.row.cell['elid']}).clear();
			}
			
			this.callback();
			this.close();
		},
		imageUpload: function(){
			if (!L.isNull(this.uploadWindow) && !this.uploadWindow.closed){
				this.uploadWindow.focus();
			}else{
				var element = this.row;
				
				var url = '/catalogbase/'+this.mmPrefix+'/upload/';
				if (!element.isNew()){
					url += 'id/'+ element.cell['elid'] + '/';
				} else {
					url += 'sess/'+ element.cell['session'] + '/';
				}
				this.uploadWindow = window.open(
					url, 'catalogimage',	
					'statusbar=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=480,height=270' 
				); 
			}
		},
		imageUploadComplete: function(data){
			var fotos = {};
			for (var i=0;i<data.length;i++){
				fotos[data[i]] = data[i];
			}
			this.fotos = fotos;
			this.fotoRender();
		},
		imageRemove: function(fotoid){
			var fotos = {};
			for (var id in this.fotos){
				if (fotoid != id){ fotos[id] = this.fotos[id]; }
			}
			this.fotos = fotos;
			this.fotoRender();
		},
		fotoRender: function(){
			var TM = this._TM, lst = "";
			for(var fid in this.fotos){
				lst += TM.replace('fotoitem', {'id': fid}); 
			}
			var flist = this.el("fotolist");
			flist.innerHTML = lst;
		}
	});	
	NS.ElementEditorPanel = ElementEditorPanel;
	NS.ElementEditorPanel.activeEditor = null;
	
	
	/**
	 * 
	 * API модуля
	 * 
	 * @class API
	 */
	
	/**
	 * Редактировать элемент<br />
	 * 
	 * Пример вызова функции: 
	 * <pre>
	 *  Brick.f('catalog', 'element', 'showElementEditorPanel', {
	 *    'catalogid': 1, // идентификатор раздела в каталоге
	 *    'eltypeid': 1, // идентификатор типа элемента
	 *    'elementid': 0, // идентификатор элемента
	 *    'mmPrefix': 'eshop'  //  префикс управляющего модуля
	 *  });
	 * </pre>
	 * 
	 * @method showElementTypeManagerWidget
	 * @static
	 * @param {Object} config Объект параметров
	 */
	API.showElementEditorPanel = function(config){
		config = L.merge({
			'catalogid': 0, 'eltypeid': 0, 'mmPrefix': '', 'elementid': 0
		}, config || {});
		
		var catalogid = config.catalogid*1, 
			eltypeid = config.eltypeid*1, 
			mmPrefix = config.mmPrefix,
			elementid = config.elementid*1;

		if (!DATA[mmPrefix]){
			DATA[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
		}
		var ds = DATA[mmPrefix];
		
		var tables = {
			'fotos': ds.get('fotos', true), // фотографии
			'catelement': ds.get('catelement', true),
			'catalog': ds.get('catalog', true),
			'eltype': ds.get('eltype', true),
			'eloption': ds.get('eloption', true),
			'eloptgroup': ds.get('eloptgroup', true)
		};
		var elementRow = tables['catelement'].newRow();
		if (elementid > 0){
			tables['fotos'].getRows({'elid':elementid});
			tables['catelement'].getRows({'id': elementid});
		}else{
			elementRow.update({
				'catid': catalogid,
				'eltid': eltypeid
			});
		}
		
		var showEditor = function(){
			if (elementid){
				elementRow = tables['catelement'].getRows({'id': elementid}).getByIndex(0);
			}

			// все необходимые таблицы подгружены, пора открывать редактор
			new NS.ElementEditorPanel(mmPrefix, elementRow, function(){
				var catelements = ds.get('catelements'); 
				if (catelements){
					catelements.getRows({'catid': catalogid}).clear();
					catelements.getRows({'catid': elementRow.cell['catid']}).clear();
					catelements.applyChanges();
				}
				
				var catelement = ds.get('catelement', true);
				if (elementRow.isNew()){
					catelement.getRows().add(elementRow);
				}
				catelement.applyChanges();
				ds.request();
			});
		};
		
		var loadOFV = function(){
			var elType = tables['eltype'].getRows().getById(eltypeid);
			var elTypeName = eltypeid > 0 ? elType.cell['nm'] : '';
			var rows = tables['eloption'].getRows().filter({'eltid': eltypeid, 'fldtp': 5});
			rows.foreach(function(row){
				if (!tables['eloptionfld']){
					tables['eloptionfld'] = ds.get('eloptionfld', true);
				}
				tables['eloptionfld'].getRows({'eltpnm': elTypeName, 'fldnm': row.cell['nm']});
			});
			if (ds.isFill(tables)){
				showEditor();
			}else{
				ds.request(true, function(){
					showEditor();
				});
			}
		}
		if (ds.isFill(tables)){
			loadOFV();
		}else{
			ds.request(true, function(){
				loadOFV();
			});
		}
	};
	
};