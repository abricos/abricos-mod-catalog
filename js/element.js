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
	     {name: 'sys', files: ['form.js','data.js','container.js']},
	     {name: 'catalog', files: ['eleditor.js']}
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

	Brick.util.CSS.update(Brick.util.CSS['catalog']['element']);

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

	var ElementManagerWidget = function(container, catalogid, mmPrefix){
		this.init(container, catalogid, mmPrefix);
	};
	ElementManagerWidget.prototype = {
		init: function(container, catalogid, mmPrefix){
			this.container = Dom.get(container);
			this.catalogid = catalogid;
			this.mmPrefix = mmPrefix;

			if (!DATA[mmPrefix]){
				DATA[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
			
			buildTemplate(this, 'panel,table,elttable,eltrow,eltrowbase,row,rowdel');
			container.innerHTML = this._T['panel'];
			
			var ds = DATA[mmPrefix];
			this.tables = {
				'catelements': ds.get('catelements', true),
				'catalog': ds.get('catalog', true),
				'eltype': ds.get('eltype', true),
				'eloption': ds.get('eloption', true),
				'eloptgroup': ds.get('eloptgroup', true)
			};
			
			this.param = {'catid': catalogid}; 
			this.rows = this.tables['catelements'].getRows(this.param);

			ds.onComplete.subscribe(this.onElementUpdate, this, true);
			if (ds.isFill(this.tables)){
				this.render();
			}
		},
		onElementUpdate: function(type, args){
			var f = args[0];
			if (f.checkWithParam('catelements', this.param)){
				this.render();
			}
		},
		render: function(){
			var TM = this._TM, T = this._T, TId = this._TId;
			this.rows = this.tables['catelements'].getRows(this.param);
			// var catalog = this.tables['catalog'].getRows().getById(this.catalogid);
			
			TM.getEl('panel.catalog').innerHTML = pathTitle(this.catalogid, this.mmPrefix);
			var data, lst = "", i, s, di;
			
			lst += T['eltrowbase'];
			var rows = this.tables['eltype'].getRows();
			rows.foreach(function(row){
				di = row.cell;
				lst += TM.replace('eltrow', {'id': di['id'], 'tl': di['tl']});
			}, this);

			TM.getEl('panel.elttable').innerHTML = TM.replace('elttable', {'rows': lst});
			
			lst = "";
			this.rows.foreach(function(row){
				di = row.cell;
				var eltype = this.tables['eltype'].getRows().getById(di['eltid']);
				
				lst += TM.replace(di['dd']>0 ? 'rowdel' : 'row', {
					'id': di['elid'],
					'eltpnm': L.isNull(eltype) ? "" : eltype.cell['tl'],
					'tl': di['tl']
				});
			}, this);
			
			TM.getEl('panel.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		destroy: function(){
			DATA[this.mmPrefix].onComplete.unsubscribe(this.onElementUpdate, this, true);
		},
		onClick: function(el){
			var T = this._T, TId = this._TId;
			if (el.id == TId['panel']['rcclear']){
				this.recycleClear(); return true;
			}else {
				var prefix = el.id.replace(/([0-9]+$)/, '');
				var numid = el.id.replace(prefix, "");
	
				switch(prefix){
				case (TId['eltrow']['add']+'-'): 
				case (TId['eltrowbase']['add']+'-'): 
					API.showElementEditorPanel({
						'catalogid': this.catalogid,
						'eltypeid': numid,
						'mmPrefix': this.mmPrefix,
						'elementid': 0
					});
					return true;
				case (TId['row']['edit']+'-'): 
					var element = this.rows.getById(numid);
					//Brick.Catalog.Element.edit(this.catalogid, numid, element.cell['elid'], element.cell['eltid'], this.mmPrefix);
					API.showElementEditorPanel({
						'catalogid': this.catalogid,
						'eltypeid': element.cell['eltid'],
						'mmPrefix': this.mmPrefix,
						'elementid': numid
					});

					return true;
				case (TId['row']['remove']+'-'): this.remove(numid); return true;
				case (TId['rowdel']['restore']+'-'): this.restore(numid); return true;
				}
			}
			return false;
		},
		remove: function(id){
			var row = this.rows.getById(id).remove();
			this._query();
		},
		restore: function(id){
			var row = this.rows.getById(id).restore();
			this._query();
		},
		recycleClear: function(){
			this.tables['catelements'].recycleClear();
			this._query();
		},
		_query: function(){
			this.tables['catelements'].applyChanges();
			DATA[this.mmPrefix].request();
		}
	};
	
	NS.ElementManagerWidget = ElementManagerWidget;
	
};