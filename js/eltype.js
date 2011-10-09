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
	mod:[
	     {name: 'catalog', files: ['eloption.js']}
	    ]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace,
		TMG = this.template,
		API = NS.API;
	
	var BC = Brick.util.Connection;
	
	NS.data = NS.data || {};
	var DATA = NS.data;

	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};
	
	/**
	 * Виджет.<br>
	 * 
	 * @class ElementTypeManagerWidget
	 * @constructor
	 * @param {HTMLObject | String} container Контейнер
	 * @param {String} mmPrefix Префикс управляющего модуля
	 */
	var ElementTypeManagerWidget = function(container, mmPrefix){
		this.init(container, mmPrefix);
	};
	ElementTypeManagerWidget.prototype = {
		init: function(container, mmPrefix){
			this.mmPrefix = mmPrefix;
			
			buildTemplate(this, 'panel,table,row,rowdel,rowwait');
			
			if (!NS.data[mmPrefix]){
				NS.data[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
			
			Dom.get(container).innerHTML = this._T['panel'];
			
			var ds = NS.data[mmPrefix];
			this.tables = new Brick.mod.sys.TablesManager(ds, ['eltype','eloption','eloptgroup'], {'owner': this});
			var __self = this;
			E.on(container, 'click', function(e){
				if (__self.onClick(E.getTarget(e))){ E.stopEvent(e); }
			});
		},
		destroy: function(){
			this.tables.destroy();
		},
		onDataLoadWait: function(tables){
			var TM = this._TM;
			TM.getEl('panel.table').innerHTML = TM.replace('table', {'rows': this._T['rowwait']});
		},
		onDataLoadComplete: function(tables){
			this.tables = tables;
			this.render();
		},
		render: function(){
			var TM = this._TM, T = this._T, TId = this._TId,
				lst = "";
			this.tables.get('eltype').getRows().foreach(function(row){
				var di = row.cell;
				lst += TM.replace(di['dd']>0 ? 'rowdel' :'row', {
					'id': di['id'],
					'tl': di['tl'],
					'dsc': di['dsc']
				}); 
			});
			TM.getEl('panel.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		onClick: function(el){
			var TId = this._TId;
			if (this.activeOption){
				if (this.activeOption.onClick(el)){ return true; }
			}
			
			if (el.id == TId['panel']['badd']){
				this.editType(0);
				return true;
			}else if (el.id == TId['panel']['rcclear']){
				this.recyclerClear();
				return true;
			}else{
				var prefix = el.id.replace(/([0-9]+$)/, '');
				var numid = el.id.replace(prefix, "");
				
				switch(prefix){
				case (TId['row']['edit']+'-'): this.editType(numid); return true;
				case (TId['row']['conf']+'-'):
				case (TId['table']['conf']+'-'):
					this.showOption(numid);
					return true;
				case (TId['row']['remove']+'-'):
					this.remove(numid);
					return true;
				case (TId['rowdel']['restore']+'-'):
					this.restore(numid);
					return true;
				}
			}
			return false;
		},
		editType: function(eltypeid){
			eltypeid = eltypeid*1 || 0;
			
			var tables = this.tables,
				table = tables.get('eltype'),
				rows = table.getRows(),
				row = eltypeid == 0 ?
					table.newRow() :
					table.getRows().getById(eltypeid);
			
			new ElementTypeEditorPanel(row, this.mmPrefix, function(){
				if (row.isNew()){
					rows.add(this.row);
				}
				table.applyChanges();
				tables.request();
			});
		},
		showOption: function(id){
			if (this.activeOption){
				if (this.activeOption.eltypeid == id){
					return;
				}else{
					this.activeOption.destroy();
				}
			}
			this.activeOption = new NS.ElementOptionsWidget(Dom.get(this._TId['panel']['optpanel']), id, this.mmPrefix);
			DATA[this.mmPrefix].request();
		},
		remove: function(id){ this._query({'act': 'remove', 'id': id}); },
		restore: function(id){ this._query({'act': 'restore', 'id': id}); },
		recyclerClear: function(){ this._query({'act': 'rcclear'}); },
		_query: function(o){
			var ds = this.tables.ds;
			ds.get('eloption').clear();
			ds.get('eloptgroup').clear();
			
			var table = ds.get('eltype'),
				rows = table.getRows(),
				row = rows.getById(o.id);
			
			if (o.act == 'remove'){
				row.remove();
			}else if (o.act == 'rcclear'){
				table.recycleClear();
			}else if (o.act == 'restore'){
				row.restore();
			}
			table.applyChanges();
			ds.request();
		}
	};
	NS.ElementTypeManagerWidget = ElementTypeManagerWidget;
	
	/**
	 * Панель. Редактор типа элемента
	 * 
	 * @class ElementTypeEditorPanel
	 * @constructor
	 * @param {DataRow} row Строка из таблицы <b>eltype</b>
	 * @param {String} mmPrefix Префикс управляющего модуля
	 */	
	var ElementTypeEditorPanel = function(row, mmPrefix, callback){
		this.mmPrefix = mmPrefix;
		this.row = row;
		this.callback = callback;
		ElementTypeEditorPanel.superclass.constructor.call(this);
	};
	YAHOO.extend(ElementTypeEditorPanel, Brick.widget.Dialog, {
		initTemplate: function(){
			buildTemplate(this, 'editor');
			return this._TM.replace('editor');
		},
		onLoad: function(){
			var o = this.row.cell;
			
			var elName = this.el('name'); 
			this.setelv('name', o['nm']);
			this.setelv('title', o['tl']);
			this.setelv('descript', o['dsc']);
			
			if (!this.row.isNew()){
				this._TM.getEl('editor.name').disabled = "disabled";
			}
		},
		el: function(name){ return Dom.get(this._TId['editor'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		nameTranslite: function(){
			var el = this.el('name');
			var title = this.el('title');
			if (!el.value && title.value){
				el.value = Brick.util.Translite.ruen(title.value);
			}
		},
		onClick: function(el){
			var tp = this._TId['editor']; 
			switch(el.id){
			case tp['name']:
				this.nameTranslite();
				return true;
			case tp['bcancel']: this.close(); return true;
			case tp['bsave']: this.save(); return true;
			}
		},
		onClose: function(){
			if (this.row.isNew()){
				DATA[this.mmPrefix].get('eltype').getRows().remove(this.row);
			}
		},
		save: function(){
			this.nameTranslite();
			
			this.row.update({
				'nm': this.elv('name'),
				'tl': this.elv('title'),
				'dsc': this.elv('descript')
			});
			if (L.isFunction(this.callback)){
				this.callback();
			}
			this.close();
		}
	});
	NS.ElementTypeEditorPanel = ElementTypeEditorPanel;

	/**
	 * 
	 * API модуля
	 * 
	 * @class API
	 */
	
	/**
	 * Отобразить панель редактора - тип элемента<br />
	 * <br />
	 * Пример вызова функции: 
	 * <pre>
	 *  Brick.f('catalog', 'eltype', 'showElementTypeEditorPanel', {
	 *    'row': row,
	 *    'mmPrefix': 'eshop'
	 *  });
	 * </pre>
	 * 
	 * @method showElementTypeEditorPanel
	 * @static
	 * @param {Object} config Объект параметров, где: row - DataRow, mmPrefix - префикс управляющего модуля
	 */

	API.showElementTypeEditorPanel = function(config){
		new ElementTypeEditorPanel(config.row, config.mmPrefix);
	};

	/**
	 * Отобразить виджет - список типов элемента<br />
	 * 
	 * Пример вызова функции: 
	 * <pre>
	 *  Brick.f('catalog', 'eltype', 'showElementTypeManagerWidget', {
	 *    'container': container,
	 *    'mmPrefix': 'eshop'
	 *  });
	 * </pre>
	 * 
	 * @method showElementTypeManagerWidget
	 * @static
	 * @param {Object} config Объект параметров, где: container - HTMLElement, mmPrefix - префикс управляющего модуля
	 */
	API.showElementTypeManagerWidget = function(config){
		var widget = new ElementTypeManagerWidget(config.container, config.mmPrefix);
		DATA[config.mmPrefix].request();
		return widget;
	};

};