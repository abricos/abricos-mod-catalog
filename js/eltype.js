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
	     {name: 'sys', files: ['form.js','data.js']},
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
			
			buildTemplate(this, 'panel,table,row,rowdel');
			
			if (!DATA[mmPrefix]){
				DATA[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
			
			Dom.get(container).innerHTML = this._T['panel'];
			
			var ds = DATA[mmPrefix];
			this.tables = {
				'eltype': ds.get('eltype', true),
				'eloption': ds.get('eloption', true),
				'eloptgroup': ds.get('eloptgroup', true)
			};

			ds.onComplete.subscribe(this.onDSUpdate, this, true);
			if (ds.isFill(this.tables)){
				this.render();
			}
			
			var __self = this;
			E.on(container, 'click', function(e){
				if (__self.onClick(E.getTarget(e))){ E.stopEvent(e); }
			});
		},
		onDSUpdate: function(type, args){
			if (args[0].check(['eltype'])){ this.render(); }
		},
		destroy: function(){
			 DATA[this.mmPrefix].onComplete.unsubscribe(this.onDSUpdate, this);
		},
		onClick: function(el){
			var TId = this._TId;
			if (this.activeOption){
				if (this.activeOption.onClick(el)){ return true; }
			}
			
			if (el.id == TId['panel']['badd']){
				this.add();
				return true;
			}else if (el.id == TId['panel']['rcclear']){
				this.recyclerClear();
				return true;
			}else{
				var prefix = el.id.replace(/([0-9]+$)/, '');
				var numid = el.id.replace(prefix, "");
				
				switch(prefix){
				case (TId['row']['edit']+'-'):
					this.editById(numid);
					return true;
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
		add: function(){
			var row = this.tables['eltype'].newRow();
			this.edit(row);
		},
		editById: function(id){
			var eltype = this.tables['eltype'].getRows().getById(id);
			this.activeEditor = new ElementTypeEditorPanel(eltype, this.mmPrefix);
		},
		edit: function(row){
			this.activeEditor = new ElementTypeEditorPanel(row, this.mmPrefix);
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
		remove: function(id){ this._query({'act': 'remove', 'data':{'id': id}}); },
		restore: function(id){ this._query({'act': 'restore', 'data':{'id': id}}); },
		recyclerClear: function(){ this._query({'act': 'rcclear'}); },
		_query: function(o){
			var rtbl = [];
			if (o['act'] == 'remove' || o['act'] == 'restore'){
				rtbl = ['eltype'];
			}else{
				rtbl = ['eltype', 'eloption', 'eloptgroup'];
			}
			var ds = DATA[this.mmPrefix];
			
			ds.setReloadFlag(rtbl);
			
			var dict = ds.loader.getJSON();
			
			if (!L.isNull(dict)){
				o = L.merge(dict, o);
			}
			var __self = this;
			BC.sendCommand('catalog', 'js_eltype', { json: o });
		},
		render: function(){
			var TM = this._TM, T = this._T, TId = this._TId,
				lst = "";
			this.tables['eltype'].getRows().foreach(function(row){
				var di = row.cell;
				lst += TM.replace(di['dd']>0 ? 'rowdel' :'row', {
					'id': di['id'],
					'tl': di['tl'],
					'dsc': di['dsc']
				}); 
			});
			TM.getEl('panel.table').innerHTML = TM.replace('table', {'rows': lst});
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
	var ElementTypeEditorPanel = function(row, mmPrefix){
		this.mmPrefix = mmPrefix;
		this.row = row;
		ElementTypeEditorPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(ElementTypeEditorPanel, Brick.widget.Panel, {
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
			this.setelv('foto', o['foto']);
			
			if (!this.row.isNew()){
				name.disabled = "disabled";
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
				'dsc': this.elv('descript'),
				'foto': this.elv('foto')
			});
			var ds = DATA[this.mmPrefix];
			if (this.row.isNew()){
				ds.get('eltype').getRows().add(this.row);
			}
			ds.get('eltype').applyChanges();
			ds.request();
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
	}

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
	}

};