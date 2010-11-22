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
	     {name: 'sys', files: ['form.js','data.js']},
	     {name: 'catalog', files: ['element.js']}
	    ]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace,
		TMG = this.template;
	
	var API = NS.API;

	Brick.util.CSS.update(Brick.util.CSS['catalog']['catalog']);

	if (!NS.data){ NS.data = {}; }
	
	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};
	
	var ManagerWidget = function(container, mmPrefix){
		this.init(container, mmPrefix);
	};
	ManagerWidget.prototype = {
		init: function(container, mmPrefix){
			this.mmPrefix = mmPrefix;
			
			buildTemplate(this, 'widget,list,item,itemwait,bcatadd,bcatempty');
			var TM = this._TM, T = this._T, TId = this._TId;

			container.innerHTML = T['widget'];
			var __self = this;
			E.on(container, 'click', function(e){
				if (__self.onClick(E.getTarget(e), e)){ E.stopEvent(e); }
			});

			if (!NS.data[mmPrefix]){
				NS.data[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
			var ds = NS.data[mmPrefix];
			this.tables = {
				'catalog': 		ds.get('catalog', true),
				'eltype': 		ds.get('eltype', true),
				'eloption': 	ds.get('eloption', true),
				'eloptgroup':	ds.get('eloptgroup', true)
			};
			
			ds.onStart.subscribe(this.dsEvent, this, true);
			ds.onComplete.subscribe(this.dsEvent, this, true);
			ds.isFill(this.tables) ? this.render() : this.renderWait();
		},
		dsEvent: function(type, args){
			if (args[0].checkWithParam('catalog')){
				type == 'onComplete' ? this.render() : this.renderWait(); 
			}
		},
		destroy: function(){
			var ds = NS.data[this.mmPrefix]
			ds.onComplete.unsubscribe(this.dsEvent);
			ds.onStart.unsubscribe(this.dsEvent);
		},
		render: function(){
			var lst = '';
			this.tables['catalog'].getRows().filter({'pid': 0}).foreach(function(row){
				lst += this.buildrow(row, 0);
			}, this);
			this._TM.getEl('widget.list').innerHTML = lst;
		},
		renderWait: function(){
			this._TM.getEl('widget.list').innerHTML = this._T['itemwait'];
		},
		buildrow: function(row, level){
			var TM = this._TM, T = this._T, lst = '', di = row.cell;
			this.tables['catalog'].getRows().filter({'pid': di['id']}).foreach(function(chrow){
				lst += this.buildrow(chrow, level+1);
			}, this);
			
			return TM.replace('item', {
				'badd': T['bcatadd'],
				'id': di['id'],
				'level': level,
				'tl': di['tl'],
				'child': lst.length > 0 ? TM.replace('list', {'list': lst}) : ''
			}); 
		},
		onClick: function(el){
			var TId = this._TId;
			if (this.activeElements){
				if (this.activeElements.onClick(el)){return true;}
			}
			switch(el.id){
			case TId['widget']['bview']: this.showElements(0); return true;
			case TId['widget']['badd']: this.add(0); return true;
			}

			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			switch(prefix){
			case (TId['item']['bview']+'-'): this.showElements(numid); return true;
			case (TId['bcatadd']['badd']+'-'): this.add(numid); return true;
			case (TId['item']['bedit']+'-'): this.edit(numid); return true;
			case (TId['item']['bremove']+'-'): this.remove(numid); return true;
			}
			return false;
		},
		showElements: function(catalogid){
			if (this.activeElements){
				if (this.activeElements.catalogid == catalogid){
					return;
				}else{
					this.activeElements.destroy();
				}
			}
			this.activeElements = new NS.ElementManagerWidget(this._TM.getEl('widget.elements'), catalogid, this.mmPrefix);
			NS.data[this.mmPrefix].request();
		},
		add: function(pid){
			var row = this.tables['catalog'].newRow();
			this.tables['catalog'].getRows().add(row);
			row.cell['pid'] = pid;
			this.activeEditor = new CatalogEditor(row, this.mmPrefix);
		},
		edit: function(id){
			var row = this.tables['catalog'].getRows().getById(id);
			this.activeEditor = new CatalogEditor(row, this.mmPrefix);
		},
		remove: function(id){
			var __self = this;
			var row = this.tables['catalog'].getRows().getById(id);
			var mmPrefix = this.mmPrefix;
			new CatalogRemoveMsg(row, function(){
				row.remove();
				__self.tables['catalog'].applyChanges();
				NS.data[mmPrefix].request();
			});
		}
	};
	NS.ManagerWidget = ManagerWidget;
	
	var ManagerPanel = function(mmPrefix){
		this.mmPrefix = mmPrefix;
		ManagerPanel.superclass.constructor.call(this,{
			fixedcenter: true, width: '780px', height: '480px'
		});
	};
	YAHOO.extend(ManagerPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel');
			return this._T['panel'];
		},
		onLoad: function(){
			this.widget = new ManagerWidget(this._TM.getEl('panel.widget'), this.mmPrefix);
			NS.data[this.mmPrefix].request();
		},
		destroy: function(){
			this.widget.destroy();
			ManagerPanel.superclass.destroy.call(this);
		}
	});
	NS.ManagerPanel = ManagerPanel;
	
	var CatalogEditor = function(row, mmPrefix){
		this.mmPrefix = mmPrefix;
		this.row = row;
		CatalogEditor.superclass.constructor.call(this,{
			modal: true, fixedcenter: true, width: '700px'
		});
	};
	YAHOO.extend(CatalogEditor, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'editor');
			return this._T['editor'];
		},
		onLoad: function(){
			var o = this.row.cell;
			this.setelv('name', o['nm']);
			this.setelv('title', o['tl']);
			this.setelv('descript', o['dsc']);
			this.setelv('metatitle', o['ktl']);
			this.setelv('metadesc', o['kdsc']);
			this.setelv('metakeys', o['kwds']);
			this.setelv('ord', o['ord']);
			this.catalogWidget = new NS.CatalogSelectWidget(this._TM.getEl('editor.catalog'), this.mmPrefix);
			this.catalogWidget.removeItem(o['id']);
			this.catalogWidget.setValue(o['pid']);
		},
		el: function(name){
			return Dom.get(this._TId['editor'][name]);
		},
		elv: function(name){
			return Brick.util.Form.getValue(this.el(name));
		},
		setelv: function(name, value){
			Brick.util.Form.setValue(this.el(name), value);
		},
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
		save: function(){
			this.nameTranslite();
			this.row.update({
				'nm': this.elv('name'),
				'tl': this.elv('title'),
				'dsc': this.elv('descript'),
				'ktl': this.elv('metatitle'),
				'kdsc': this.elv('metadesc'),
				'kwds': this.elv('metakeys'),
				'ord': this.elv('ord'),
				'pid': this.catalogWidget.getValue()
			});
			var ds = NS.data[this.mmPrefix]; 
			ds.get('catalog').applyChanges();
			ds.request();
			this.close();
		}
	});
	
	NS.CatalogEditor = CatalogEditor;
	
	
	var CatalogRemoveMsg = function(row, callback){
		this.row = row;
		this.callback = callback;
		CatalogRemoveMsg.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(CatalogRemoveMsg, Brick.widget.Panel, {
		initTemplate: function(){
			var TM = TMG.build('itemremovemsg'), T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;
			
			return TM.replace('itemremovemsg', {
				'info': this.row.cell['tl']
			}); 
		},
		onClick: function(el){
			var tp = this._TId['itemremovemsg'];
			switch(el.id){
			case tp['bremove']: this.close(); this.callback(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			return false;
		}
	});
	
	API.showManagerWidget = function(config){
		var widget = new ManagerWidget(config.container, config.mmPrefix)
		NS.data[config.mmPrefix].request();
		return widget;
	};
	
	API.showManagerPanel = function(mmPrefix){
		var panel = new ManagerPanel(mmPrefix);
		return panel;
	};

	
	var CatalogSelectWidget = function(container, mmPrefix){
		this.init(container, mmPrefix);
	};
	CatalogSelectWidget.prototype = {
		init: function(container, mmPrefix){
			this.container = container;
			this.mmPrefix = mmPrefix;
			this.selectedValue = 0;
			this.removeId = 0;
			
			buildTemplate(this, 'selwidget,selrow,selrowwait,selroot');
			var TM = this._TM, T = this._T, TId = this._TId;

			if (!NS.data[mmPrefix]){
				NS.data[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
			var ds = NS.data[mmPrefix];
			this.tables = {'catalog': ds.get('catalog', true)};
			
			ds.onStart.subscribe(this.dsEvent, this, true);
			ds.onComplete.subscribe(this.dsEvent, this, true);
			ds.isFill(this.tables) ? this.render() : this.renderWait();  
		},
		dsEvent: function(type, args){
			if (args[0].checkWithParam('catalog')){
				type == 'onComplete' ? this.render() : this.renderWait(); 
			}
		},
		destroy: function(){
			var ds = NS.data[this.mmPrefix]
			ds.onComplete.unsubscribe(this.dsEvent);
			ds.onStart.unsubscribe(this.dsEvent);
		},
		renderWait: function(){
			var TM = this._TM;
			this.container.innerHTML = TM.replace('selwidget', {
				'rows': TM.replace('selrowwait')
			});
		},
		render: function(){
			var TM = this._TM, T = this._T, lst = '';
			this.tables['catalog'].getRows().filter({'pid': 0}).foreach(function(row){
				lst += this.buildrow(row, 0, T['selroot']);
			}, this);
			this.container.innerHTML = TM.replace('selwidget', {
				'rows': TM.replace('selrow', {'id': 0, 'tl': T['selroot']}) + lst
			});
			this.setValue(this.selectedValue);
		},
		buildrow: function(row, level, ptl){
			var TM = this._TM, T = this._T, lst = '', di = row.cell;
			if (di['id']*1 == this.removeId*1){ return '';}
			this.tables['catalog'].getRows().filter({'pid': di['id']}).foreach(function(chrow){
				lst += this.buildrow(chrow, level+1, ptl + ' / ' + di['tl']);
			}, this);
			
			return TM.replace('selrow', {'id': di['id'], 'tl': ptl+' / '+di['tl']}) + lst; 
		},
		setValue: function(catalogid){
			this.selectedValue = catalogid;
			this._TM.getEl('selwidget.id').value = catalogid;
		},
		getValue: function(){
			return this._TM.getEl('selwidget.id').value;
		},
		removeItem: function(id){
			this.removeId = id;
			this.render();
		}
	};
	NS.CatalogSelectWidget = CatalogSelectWidget;
	
};
