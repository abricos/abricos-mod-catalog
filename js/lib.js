/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'widget', files: ['notice.js']}
	]		
};
Component.entryPoint = function(NS){

	var L = YAHOO.lang;
	
	var SysNS = Brick.mod.sys;

	var buildTemplate = this.buildTemplate;
	buildTemplate({},'');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	var CatalogItem = function(d){
		d = L.merge({
			'pid': 0,
			'tl':'', // заголовок
			'nm': '' // имя (URL)
		}, d || {});
		CatalogItem.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CatalogItem, SysNS.Item, {
		update: function(d){
			this.parentid	= d['pid']*1;
			this.title		= d['tl'];
			this.name		= d['nm'];
		},
		url: function(){
			// return NS.navigator.category.view(this.id);
		}
	});		
	NS.CatalogItem = CatalogItem;
	
	var CatalogList = function(d){
		CatalogList.superclass.constructor.call(this, d, CatalogItem);
	};
	YAHOO.extend(CatalogList, SysNS.ItemList, {
		
	});
	NS.CatalogList = CatalogList;	
	
	NS.managers = {};
	
	var Manager = function(modname, callback){
		NS.managers[modname] = this;
		
		this.init(modname, callback);
	};
	Manager.prototype = {
		init: function(modname, callback){
			this.modname = modname;
			
			var __self = this;
			this.catalogListLoad(function(list){
				__self.catalogList = list;
				NS.life(callback, __self);
			});
		},
		ajax: function(data, callback){
			data = data || {};

			Brick.ajax(this.modname, {
				'data': data,
				'event': function(request){
					NS.life(callback, request.data);
				}
			});
		},
		catalogListLoad: function(callback){
			this.ajax({
				'do': 'cataloglist'
			}, function(d){
				var list = null;
				Brick.console(d);
				
				if (!L.isNull(d) && !L.isNull(d['catalogs'])){
					list = new NS.CatalogList();
				}
				
				NS.life(callback, list);
			});
		}
	};
	NS.Manager = Manager;
	
	NS.initManager = function(modname, callback){
		if (!NS.managers[modname]){
			if (Brick.mod[modname] && Brick.mod[modname]['Manager']){
				new Brick.mod[modname]['Manager'](modname, callback);
			}else{
				new NS.Manager(modname, callback);
			}
		}else{
			NS.life(callback, NS.managers[modname]);
		}
	};
	
};