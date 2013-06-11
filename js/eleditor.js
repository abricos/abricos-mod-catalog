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
	     {name: 'sys', files: ['form.js','data.js','editor.js','container.js']}
    ]
};
Component.entryPoint = function(){
	var Dom = YAHOO.util.Dom,
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
	
	var ElementOptionEditor = function(mmPrefix, elRow, optRow){
		this.init(mmPrefix, elRow, optRow);
	};
	ElementOptionEditor.prototype = {
		buildTemplate: function(){ return ''; },
		init: function(mmPrefix, elRow, optRow){
			this.mmPrefix = mmPrefix;
			this.elRow = elRow;
			this.optRow = optRow;
			this.fieldName = 'fld_'+this.optRow.cell['nm'];
		},
		getValue: function(){
			return this.elRow.cell[this.fieldName] || '';
		},
		setValue: function(value){
			var d = {};
			d[this.fieldName] = value;
			this.elRow.update(d);
		},
		onLoad: function(){},
		onClick: function(el){ return false; },
		save: function(){},
		destroy: function(){}
	};
	NS.ElementOptionEditor = ElementOptionEditor;
	NS.ElementOptionEditors = {};
	
	var ElementOptionEditorBoolean = function(mmPrefix, elRow, optRow){
		ElementOptionEditorBoolean.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorBoolean, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow0');
			var di = this.optRow.cell;
			return this._TM.replace('editoptrow0', { 'id': di['nm'], 'title': di['tl'] });
		},
		getEl: function(){ return this._TM.getEl('editoptrow0.id'); },
		onLoad: function(){
			Brick.util.Form.setValue(this.getEl(), this.getValue());
		},
		save: function(){
			var value = Brick.util.Form.getValue(this.getEl());
			this.setValue(value);
		}
	});
	NS.ElementOptionEditors['0'] = ElementOptionEditorBoolean;
	
	var ElementOptionEditorString = function(mmPrefix, elRow, optRow){
		ElementOptionEditorString.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorString, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow1');
			var di = this.optRow.cell;
			return this._TM.replace('editoptrow1', { 'id': di['nm'], 'title': di['tl'] });
		},
		getEl: function(){ return this._TM.getEl('editoptrow1.id'); },
		onLoad: function(){
			this.getEl().value = this.getValue();
		},
		save: function(){
			var value = Brick.util.Form.getValue(this.getEl());
			this.setValue(value);
		}
	});
	NS.ElementOptionEditors['1'] = ElementOptionEditorString;
	NS.ElementOptionEditors['2'] = ElementOptionEditorString;
	NS.ElementOptionEditors['3'] = ElementOptionEditorString;

	var ElementOptionEditorList = function(mmPrefix, elRow, optRow){
		ElementOptionEditorList.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorList, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow4,seloptionrow');
			var di = this.optRow.cell;
			var prm = J.parse(di['prms']) || {};
			var TM = this._TM;
			return TM.replace('editoptrow4', { 
				'id': di['nm'], 
				'title': di['tl'],
				'list': (function(){
					var lst = '', list = prm['val'].split('\n');
					for (j=0;j<list.length;j++){
						lst += TM.replace('seloptionrow', {'id': j, 'tl': list[j]});
					}
					return lst;
				})()				
			});
		},
		getEl: function(){ return this._TM.getEl('editoptrow4.id'); },
		onLoad: function(){ this.getEl().value = this.getValue(); },
		save: function(){
			var value = Brick.util.Form.getValue(this.getEl());
			this.setValue(value);
		}
	});
	NS.ElementOptionEditors['4'] = ElementOptionEditorList;

	var ElementOptionEditorTable = function(mmPrefix, elRow, optRow){
		ElementOptionEditorTable.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorTable, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow5,seloptionrow');
			var di = this.optRow.cell,
				TM = this._TM,
				elTypeId = di['eltid'] * 1,
				elementTypeName = elTypeId > 0 ? 
						NS.data[this.mmPrefix].get('eltype').getRows().getById(elTypeId).cell['nm'] : '';
			
			this.elementTypeName = elementTypeName;

			var lst = '';
			NS.data[this.mmPrefix].get('eloptionfld').getRows({'eltpnm': elementTypeName, 'fldnm': this.optRow.cell['nm']}).foreach(function(row){
				lst += TM.replace('seloptionrow', {
					'id': row.cell['id'],
					'tl': row.cell['tl']
				});
			});
			return TM.replace('editoptrow5', { 
				'id': di['nm'], 
				'title': di['tl'],
				'list': lst				
			});
		},
		getEl: function(){ return this._TM.getEl('editoptrow5.id'); },
		onLoad: function(){ this.getEl().value = this.getValue(); },
		save: function(){
			var value = Brick.util.Form.getValue(this.getEl());
			this.setValue(value);
			
			var elAlt = this._TM.getEl('editoptrow5.alt');
			var newval = Brick.util.Form.getValue(elAlt);
			if (newval.length > 0){
				var ops = {};
				ops[this.fieldName+'-alt'] = newval;
				this.elRow.update(ops);
				NS.data[this.mmPrefix].get('eloptionfld').getRows({'eltpnm': this.elementTypeName, 'fldnm': this.optRow.cell['nm']}).clear();
			}
		}
	});
	NS.ElementOptionEditors['5'] = ElementOptionEditorTable;

	var ElementOptionEditorText = function(mmPrefix, elRow, optRow){
		ElementOptionEditorText.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorText, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow7');
			var di = this.optRow.cell;
			return this._TM.replace('editoptrow7', { 'id': di['nm'], 'title': di['tl'] });
		},
		onLoad: function(){
			var el = this._TM.getEl('editoptrow7.id'),
				Editor = Brick.widget.Editor;
			el.value = this.getValue();
			this._editor = new Editor(el, {'mode': Editor.MODE_VISUAL});
		},
		destroy: function(){
			this._editor.destroy();
		},
		save: function(){
			this.setValue(this._editor.getContent());
		}
	});
	NS.ElementOptionEditors['7'] = ElementOptionEditorText;
	
	var ElementOptionEditorChildEl = function(mmPrefix, elRow, optRow){
		ElementOptionEditorChildEl.superclass.constructor.call(this, mmPrefix, elRow, optRow);
	};
	YAHOO.extend(ElementOptionEditorChildEl, ElementOptionEditor, {
		buildTemplate: function(){
			buildTemplate(this, 'editoptrow9,table9,row9,row9wait');
			var di = this.optRow.cell;
			return this._TM.replace('editoptrow9', { 'id': di['nm'], 'title': di['tl'] });
		},
		onLoad: function(){
			var row = this.elRow,
				di = row.cell;
			this.isNew = row.isNew();
			
			if (this.isNew){
				this.linkList = {};
				this.linkLastId = 1;
				this.render();
			}else{
				var ds = NS.data[this.mmPrefix];
				this.tables = new Brick.mod.sys.TablesManager(ds, ['linkelements'], {'owner': this});
				this.linkParam = {'elid': di['elid'], 'optid': this.optRow.cell['id']};
				this.tables.setParam('linkelements', this.linkParam);
			}
		},
		onClick: function(el){
			var TId = this._TId;
			
			switch(el.id){
			case TId['editoptrow9']['badd']: this.showAppendElement(); return true;
			}
			
			var prefix = el.id.replace(/([a-z0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			switch(prefix){
			case (TId['row9']['bremove']+'-'): this.removeElement(numid); return true;
			}
			return false;
		},
		showAppendElement: function(){
			var __self = this;
			Brick.ff('catalog', 'element', function(){
			    API.showElementSelectPanel('eshop', function(element){
			        __self.appendElement(element);
			    });
			});
		},
		appendElement: function(element){
			if (this.isNew){
				this.linkList[this.linkLastId++] = {
					'elid': element['id'],
					'tl': element['tl']
				};
			}else{
				var table = this.tables.get('linkelements'),
					rows = table.getRows(this.linkParam),
					row = table.newRow();
				row.update({
					'elid': element['id'],
					'tl': element['tl']
				});
				rows.add(row);
			}
			this.render();
		},
		removeElement: function(numid){
			if (this.isNew){
				delete this.linkList[numid];
			}else{
				this.tables.get('linkelements').getRows(this.linkParam).getById(numid).remove();
			}
			this.render();
		},
		onDataLoadWait: function(tables){
			var TM = this._TM;
			TM.getEl('editoptrow9.table').innerHTML = TM.replace('table9', {'rows': this._T['row9wait']});
		},
		onDataLoadComplete: function(tables){
			this.tables = tables;
			this.render();
		},
		render: function(){
			var TM = this._TM, 
				lst = "";
			TM.getEl('editoptrow9.badd').style.display = '';
			
			var arr = {};
			if (this.isNew){
				arr = this.linkList;
			}else{
				this.tables.foreach('linkelements', function(row){
					if (row.isRemove()){ return; }
					arr[row.cell['id']] = row.cell;
				}, this.linkParam);
			}
			for (var n in arr){
				var di = arr[n];
				lst += TM.replace('row9', {
					'id': n,
					'tl': di['tl']
				});
			}
			TM.getEl('editoptrow9.table').innerHTML = TM.replace('table9', {'rows': lst});
		},
		save: function(){
			if (this.isNew){
				var a = [];
				for (var n in this.linkList){
					a[a.length] = this.linkList[n]['elid'];
				}
				var d = {};
				d[this.fieldName] = a.join(',');
				this.elRow.update(d);
			}else{
				this.tables.get('linkelements').getRows(this.linkParam).applyChanges();
			}
		}
	});
	NS.ElementOptionEditors['9'] = ElementOptionEditorChildEl;

	var ElementOptionEditorBuilder = function(mmPrefix, row, elTypeId){
		this.init(mmPrefix, row, elTypeId);
	};
	ElementOptionEditorBuilder.prototype = {
		init: function(mmPrefix, row, elTypeId){
			this.mmPrefix = mmPrefix;
			this.row = row;
			this.elTypeId = elTypeId || 0;
			this.elementTypeName = this.elTypeId > 0 ? 
					NS.data[this.mmPrefix].get('eltype').getRows().getById(this.elTypeId) : '';

			// список редакторов
			this._editors = {};
			
			this.widgets = {};

			buildTemplate(this, 'editoptrow0,editoptrow1,editoptrow4,editoptrow5,editoptrow6,editoptrow7,editoptrow9,seloptionrow,editoptrowcust');
		},
		buildTemplate: function(){
			
			var ds = NS.data[this.mmPrefix];
			var elTypeId = this.elTypeId;
			var eltype = ds.get('eltype').getRows().getById(elTypeId);
			
			var rows = this.rows = {
				'eloption': ds.get('eloption').getRows().filter({'eltid': elTypeId}),
				'eloptgroup': ds.get('eloptgroup').getRows().filter({'eltid': elTypeId})
			};
			
			// Построение опций элемента
			// участвующие в мульти не участвуют в общем списке
			rows['eloption'].filter({'fldtp': 6}).foreach(function(row){
				var di = row.cell;
				var prm = J.parse(di['prms']) || {};
				var list = prm['val'].split('\n');
				for (var j=0;j<list.length;j++){
					var row = rows['eloption'].get('nm', list[j]);
					if (row){ row['usedmulti'] = true; }
				}
			});
			var lst = '', __self = this;
			rows['eloption'].foreach(function(option){
				lst += __self.buildRow(option, false); 
			});
			return lst;
		},
		buildRow: function(option, child){
			if (!child && option['usedmulti']){ return ''; }
			var di = option.cell;
			var optType = di['fldtp'];
			if (!NS.ElementOptionEditors[optType]){ return ''; }

			var widget = new NS.ElementOptionEditors[optType](this.mmPrefix, this.row, option);
			this.widgets[di['id']] = widget;
			return widget.buildTemplate();
		},
		onLoad: function(){
			var wds = this.widgets;
			for (var i in wds){ wds[i].onLoad(); }
		},
		save: function(){
			var wds = this.widgets;
			for (var i in wds){ wds[i].save(); }
		},
		destroy: function(){
			var wds = this.widgets;
			for (var i in wds){ wds[i].destroy(); }
		},
		onClick: function(el){
			var wds = this.widgets;
			for (var i in wds){
				if (wds[i].onClick(el)){
					return true; 
				}
			}
			return false;
		}
	};
	
	// Редактор свободного элемента
	var ElementEditorPanel = function(mmPrefix, row, callback){

		this.tables = DATA[mmPrefix].tables;
		
		this.mmPrefix = mmPrefix;
		this.row = row;
		if (row.isNew()){
			row.cell['session'] = Math.round(((new Date()).getTime()/1000));
		}
		this.callback = callback;
		this.uploadWindow = null;
		ElementEditorPanel.activeEditor = this;
		
		ElementEditorPanel.superclass.constructor.call(this,{
			overflow: true,
			width: '790px',
			height: '400px'
		});
	};
	YAHOO.extend(ElementEditorPanel, Brick.widget.Dialog, {
		initTemplate: function(){
		
			buildTemplate(this, 'editor,fotoitem'); 

			var o = this.row.cell,
				ds = NS.data[this.mmPrefix],
				elementId = !this.row.isNew() ? o['elid'] : 0;
			
			var fotos = {};
			if (!this.row.isNew()){
				ds.get('fotos').getRows({'elid': elementId}).foreach(function(row){
					fotos[row.cell['fid']] = row.cell['fid'];  
				});
			}
			this.fotos = fotos;
			
			o['eltid'] = o['eltid'] * 1;
			this.optionsBase = new ElementOptionEditorBuilder(this.mmPrefix, this.row, 0);
			this.optionsType = o['eltid'] > 0 ?
				new ElementOptionEditorBuilder(this.mmPrefix, this.row, o['eltid']) : null;

			var eltype = ds.get('eltype').getRows().getById(o['eltid']);
			return this._TM.replace('editor', {
				'catalog': pathTitle(o['catid'], this.mmPrefix),
				// 'eltype': L.isNull(eltype) ? '' : eltype.cell['tl'],
				'eltype': L.isNull(eltype) ? 'Базовый' : eltype.cell['tl'],
				'options': this.optionsBase.buildTemplate() +
					(L.isNull(this.optionsType) ? '' : this.optionsType.buildTemplate()) 
			});
		},
		destroy: function(){
			this.optionsBase.destroy();
			ElementEditorPanel.activeEditor = null;
			ElementEditorPanel.superclass.destroy.call(this);
		},
		el: function(name){ return Dom.get(this._TId['editor'][name]); },
		elOnLoad: function(t, func){ func(t, tSetVar); },
		onLoad: function(){
			var element = this.row;
			
			this.optionsBase.onLoad();
			if (!L.isNull(this.optionsType)){
				this.optionsType.onLoad();
			}

			this.fotoRender();
			
			this.catalogWidget = new NS.old_CatalogSelectWidget(this._TM.getEl('editor.catalog'), this.mmPrefix);
			this.catalogWidget.setValue(element.cell['catid']);
			NS.data[this.mmPrefix].request();
		},
		onClick: function(el){
			var TId = this._TId;

			var arr = el.id.split('-');
			
			if (this.optionsBase.onClick(el)){ return true; }
			if (!L.isNull(this.optionsType)){
				if (this.optionsType.onClick(el)){ return true; }
			} 
			
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
			var options = {};

			var afotos = [];
			for (var fid in this.fotos){
				afotos[afotos.length] = fid;
			}
			options['fids'] = afotos.join(",");
			options['catid'] = this.catalogWidget.getValue();
			
			this.optionsBase.save();
			if (!L.isNull(this.optionsType)){
				this.optionsType.save();
			}

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
	 * API модуля
	 * @class API
	 */
	
	/**
	 * Редактировать элемент <br />
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
			var rows = tables['eloption'].getRows().filter({'fldtp': 5});
			
			rows.foreach(function(row){
				if (!tables['eloptionfld']){
					tables['eloptionfld'] = ds.get('eloptionfld', true);
				}

				var fElTId = row.cell['eltid'];
					elTypeName = fElTId > 0 ? tables['eltype'].getRows().getById(fElTId).cell['nm'] : '';
				
				tables['eloptionfld'].getRows({'eltpnm': elTypeName, 'fldnm': row.cell['nm']});
			});
			if (ds.isFill(tables)){
				showEditor();
			}else{
				ds.request(true, function(){
					showEditor();
				});
			}
		};
		if (ds.isFill(tables)){
			loadOFV();
		}else{
			ds.request(true, function(){
				loadOFV();
			});
		}
	};
	
};