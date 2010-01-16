/**
* @version $Id$
* @package CMSBrick
* @copyright Copyright (C) 2008 CMSBrick. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


var Component = new Brick.Component();
Component.requires = {
	mod:[
	     {name: 'sys', files: ['form.js','data.js']},
	     {name: 'catalog', files: ['api.js','lib.js','element.js']}
	    ]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace,
		TMG = this.template;
	
	var API = NS.API;
	
(function(){
	var TM = TMG.build('css');
	Brick.util.CSS.update(TM.data['css']);
})();

	var dateExt = Brick.dateExt;
	var elClear = Brick.elClear;
	var wWait = Brick.widget.WindowWait;
	var tSetVar = Brick.util.Template.setProperty;

	if (!NS.data){
		NS.data = {};
	}
(function(){
	
	var ManagerWidget = function(container, mmPrefix){
		this.init(container, mmPrefix);
	};
	ManagerWidget.prototype = {
		init: function(container, mmPrefix){
			this.mmPrefix = mmPrefix;
			
			var TM = TMG.build('panel,list,item,bcatadd,bcatempty'),
				T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;

			container.innerHTML = T['panel'];
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
				'catalogcfg':	ds.get('catalogcfg', true),
				'eltype': 		ds.get('eltype', true),
				'eloption': 	ds.get('eloption', true),
				'eloptgroup':	ds.get('eloptgroup', true)
			};

			var __self = this;
			ds.onComplete.subscribe(function(type, args){
				var f = args[0];
				if (f.check(['catalog', 'catalogcfg'])){
					__self.render();
				}
			});
		},
		onCatalogUpdate: function(){
			this.render();
		},
		render: function(){
			var TId = this._TId;
			
			var rows = this.tables['catalog'].getRows().filter({'pid': 0});
			var lst = "";
			rows.foreach(function(row){
				lst += this.buildrow(row, 0);
			}, this);
			this._TM.getEl('panel.list').innerHTML = lst;
		},
		buildrow: function(row, level){
			var TM = this._TM, T = this._T;
			
			var badd = (level >= this.tables['catalogcfg'].getRows().count()-1) ? T['bcatempty'] : T['bcatadd'];
			var t = this._TM.replace('item', {
				'badd': badd,
				'id': row.cell['id'],
				'level': level,
				'tl': row.cell['tl']
			}); 
			var lst="", rows = this.tables['catalog'].getRows().filter({'pid': row.cell['id']});
			rows.foreach(function(row){
				lst += this.buildrow(row, level+1);
			}, this);
			if (lst.length > 0){
				lst = TM.replace('list', {'list': lst});
			}
			
			t = tSetVar(t, 'child', lst);

			return t;
		},
		destroy: function(){ },
		onClick: function(el){
			var TId = this._TId;
			if (this.activeElements){
				if (this.activeElements.onClick(el)){return true;}
			}
			if (el.id == TId['panel']['badd']){ this.add(0); return true; }

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
			this.activeElements = new NS.ElementManagerWidget(this._TM.getEl('panel.elements'), catalogid, this.mmPrefix);
			API.dsRequest(this.mmPrefix);
		},
		add: function(pid){
			var row = this.tables['catalog'].newRow();
			this.tables['catalog'].getRows().add(row);
			row.cell['pid'] = pid;
			this.activeEditor = new Editor(row, this.mmPrefix);
		},
		edit: function(id){
			var row = this.tables['catalog'].getRows().getById(id);
			this.activeEditor = new Editor(row, this.mmPrefix);
		},
		remove: function(id){
			var __self = this;
			var row = this.tables['catalog'].getRows().getById(id);
			var mmPrefix = this.mmPrefix;
			new CatalogRemoveMsg(row, function(){
				row.remove();
				__self.tables['catalog'].applyChanges();
				API.dsRequest(mmPrefix);
			});
		}
	};

	NS.ManagerWidget = ManagerWidget;
	
	var Editor = function(row, mmPrefix){
		this.mmPrefix = mmPrefix;
		this.row = row;
		Editor.superclass.constructor.call(this,{
			modal: true, fixedcenter: true
		});
	};

	YAHOO.extend(Editor, Brick.widget.Panel, {
		initTemplate: function(){
			var TM = TMG.build('editor'), T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;
			
			return T['editor'];
		},
		onLoad: function(){
			var o = this.row.cell;
			this.setelv('name', o['nm']);
			this.setelv('title', o['tl']);
			this.setelv('descript', o['dsc']);
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
				'dsc': this.elv('descript')
			});
			var ds = NS.data[this.mmPrefix]; 
			ds.get('catalog').applyChanges();
			API.dsRequest(this.mmPrefix);
			this.close();
		}
	});
	
	Brick.Catalog.Editor = Editor;
	
	
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
	
})();	
};
