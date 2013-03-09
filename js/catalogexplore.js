/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var CatalogTreeWidget = function(container, list, cfg){
		cfg = L.merge({
		}, cfg || {});
		
		CatalogTreeWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget,table,row' 
		}, list, cfg);
	};
	YAHOO.extend(CatalogTreeWidget, BW, {
		init: function(list, cfg){
			this.list = list;
			this.config = cfg;
			this.selectedItem = null;
			
			this.editClickEvent = new YAHOO.util.CustomEvent('editClickEvent');
			this.addChildClickEvent = new YAHOO.util.CustomEvent('addChildClickEvent');
			this.selectedItemEvent = new YAHOO.util.CustomEvent('selectedItemEvent');
		},
		render: function(){
			this.elSetHTML('table', this.buildRows(null, this.list, 0));
			this._selectPath(this.selectedItem);
		},
		buildRows: function(pcat, list, level){
			var __self = this, lst = "", i = 0;
			list.foreach(function(cat){
				lst += __self.buildRow(cat, level, i==0, i==list.count()-1);
				i++;
			});
			
			if (lst == ""){ return ""; }
			
			var sRow = {
				'pid': 0,
				'clshide': '',
				'rows': lst
			};
			if (!L.isNull(pcat)){
				sRow['pid'] = pcat.id;
				sRow['clshide'] = pcat.expanded ? '' : 'hide';
			}
			
			return this._TM.replace('table', sRow);
		},
		buildRow: function(cat, level, first, islast){
			var sChild = cat.childs.count() > 0 ? this.buildRows(cat, cat.childs, level+1) : '';

			return this._TM.replace('row', {
				'id': cat.id,
				'tl': cat.title,
				'child': sChild,
				'clst': islast ? 'ln' : 'tn',
				'chdicoview': cat.childs.count() == 0 ? 'hide' : 'none',
				'chdicon': cat.expanded ? 'chdcls' : 'chdexpd'
			});
		},
		onClick: function(el){
			var TId = this._TId,
				prefix = el.id.replace(/([0-9]+$)/, ''),
				numid = el.id.replace(prefix, "");
			
			var tp = TId['row'];
			
			switch(prefix){
			case (tp['badd']+'-'): 
			case (tp['baddc']+'-'):
				this.onAddChildClick(numid);
				return true;
				
			case (tp['bedit']+'-'): 
			case (tp['beditc']+'-'):
				this.onEditClick(numid);
				return true;
				
			case (tp['title']+'-'): 
			case (tp['atitle']+'-'):
				this.selectItem(numid);
				return true;
				
			case (tp['bclsexpd']+'-'): 
				this.shChilds(numid); 
				return true;
			}
			
			return false;
		},
		onEditClick: function(id){
			this.editClickEvent.fire(id);
		},
		onAddChildClick: function(id){
			this.addChildClickEvent.fire(id);
		},
		onSelectedItem: function(id){
			this.selectedItemEvent.fire(id);
		},
		shChilds: function(catid){
			var cat = this.list.find(catid);
			if (L.isNull(cat)){ return; }
			
			cat.expanded = !cat.expanded;
			this.render();
		},
		selectItem: function(id){
			var cat = this.list.find(id);

			this._selectPath(cat);
			this.onSelectedItem(cat);
		},
		_unSelectPathMethod: function(list){
			var TId = this._TId, gel = function(n, id){ return Dom.get(TId[n]['title']+'-'+id); };
			var __self = this;
			list.foreach(function(cat){
				Dom.removeClass(gel('row', cat.id), 'select');
				__self._unSelectPathMethod(cat.childs);
			});
		},
		_selectPath: function(cat){
			this.selectedItem = cat;
			this._unSelectPathMethod(this.list);
			this._selectPathMethod(cat);
		},
		_selectPathMethod: function(cat){
			if (L.isNull(cat)){ return; }
			var TId = this._TId, gel = function(n, id){ return Dom.get(TId[n]['title']+'-'+id); };
			
			Dom.addClass(gel('row', cat.id), 'select');
			
			if ((L.isNull(cat.parent) && cat.parentTaskId > 0) || (cat.parentTaskId == 0 && cat.userid != UID)){
				Dom.addClass(gel('rowuser', cat.userid), 'select');
			}

			this._selectPathMethod(cat.parent);
		}
	});
	NS.CatalogTreeWidget = CatalogTreeWidget;
	
};