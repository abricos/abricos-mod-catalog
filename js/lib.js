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
			'nm': '', // имя (URL)
			'childs': []
		}, d || {});
		CatalogItem.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CatalogItem, SysNS.Item, {
		init: function(d){
			this.detail = null;
			CatalogItem.superclass.init.call(this, d);
		},
		update: function(d){
			this.parentid	= d['pid']*1;
			this.title		= d['tl'];
			this.name		= d['nm'];
			
			this.parent		= null;
			this.childs		= new CatalogList(d['childs']);
			
			var __self = this;
			this.childs.foreach(function(cat){
				cat.parent = __self;
			});
			this.expanded = d['id'] == 0;
		},
		url: function(){
			// return NS.navigator.category.view(this.id);
		}
	});		
	NS.CatalogItem = CatalogItem;
	
	var CatalogDetail = function(d){
		d = L.merge({
			'dsc': '',
			'mtl': '', 
			'mks': '', 
			'mdsc': '' 
		}, d || {});
		CatalogDetail.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CatalogDetail, SysNS.Item, {
		update: function(d){
			this.descript		= d['dsc'];
			this.metaTitle		= d['mtl'];
			this.metaKeys		= d['mks'];
			this.metaDescript	= d['mdsc'];
		}
	});		
	NS.CatalogDetail = CatalogDetail;

	var CatalogList = function(d){
		CatalogList.superclass.constructor.call(this, d, CatalogItem);
	};
	YAHOO.extend(CatalogList, SysNS.ItemList, {
		find: function(catid){
			var fcat = null;
			this.foreach(function(cat){
				if (cat.id == catid){
					fcat = cat;
					return true;
				}
				var ffcat = cat.childs.find(catid);
				if (!L.isNull(ffcat) && ffcat.id == catid){
					fcat = ffcat;
					return true;
				}
			});
			return fcat;
		}
	});
	NS.CatalogList = CatalogList;
	

	var Element = function(d){
		d = L.merge({
			'catid': 0, 
			'tpid': 0, 
			'tl': '', 
			'nm': '' 
		}, d || {});
		Element.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Element, SysNS.Item, {
		init: function(d){
			this.detail = null;
			Element.superclass.init.call(this, d);
		},
		update: function(d){
			this.catid		= d['catid']*1;
			this.typeid		= d['tpid']*1;
			this.title		= d['tl'];
			this.name		= d['nm'];
		}
	});		
	NS.Element = Element;
	
	var ElementDetail = function(d){
		d = L.merge({
			'imgs': [],
			'mtl': '',
			'mdsc': '',
			'mks': '',
			'optb': {}
		}, d || {});
		ElementDetail.superclass.constructor.call(this, d);
	};
	YAHOO.extend(ElementDetail, SysNS.Item, {
		update: function(d){
			this.fotos = d['fotos'];
			
			this.metaTitle = d['mtl'];
			this.metaKeys = d['mks'];
			this.metaDesc = d['mdsc'];
			
			this.optionsBase = d['optb'];
		},
		getValue: function(option){
			var vals = option.elTypeId == 0 ? this.optionsBase : {};
			
			return vals[option.name];
		}
	});
	NS.ElementDetail = ElementDetail;
	
	NS.FTYPE = {
		'BOOLEAN':	0,
		'NUMBER':	1,
		'DOUBLE':	2,
		'STRING':	3,
		'LIST':		4,
		'TABLE':	5,
		'TEXT':		7
	};
	
	var ElementList = function(d){
		ElementList.superclass.constructor.call(this, d, Element);
	};
	YAHOO.extend(ElementList, SysNS.ItemList, {});
	NS.ElementList = ElementList;
	
	var ElementOption = function(d){
		d = L.merge({
			'tl':	'',
			'nm':	'',
			'tpid':	0,
			'tp':	0
		}, d || {});
		ElementType.superclass.constructor.call(this, d);
	};
	YAHOO.extend(ElementOption, SysNS.Item, {
		update: function(d){
			this.title		= d['tl'];
			this.name		= d['nm'];
			this.elTypeId	= d['tpid']*1;
			this.type		= d['tp']*1;
		}
	});
	NS.ElementOption = ElementOption;
	
	var ElementOptionList = function(d){
		ElementOptionList.superclass.constructor.call(this, d, ElementOption);
	};
	YAHOO.extend(ElementOptionList, SysNS.ItemList, {});
	NS.ElementOptionList = ElementOptionList;
	
	
	var ElementType = function(d){
		d = L.merge({
			'tl': '',
			'nm': '',
			'options': []
		}, d || {});
		ElementType.superclass.constructor.call(this, d);
	};
	YAHOO.extend(ElementType, SysNS.Item, {
		update: function(d){
			this.title = d['tl'];
			this.name = d['nm'];
			
			this.options = new NS.ElementOptionList(d['options']);
		}
	});
	NS.ElementType = ElementType;
	
	var ElementTypeList = function(d){
		ElementTypeList.superclass.constructor.call(this, d, ElementType);
	};
	YAHOO.extend(ElementTypeList, SysNS.ItemList, {});
	NS.ElementTypeList = ElementTypeList;
	
	
	
	NS.managers = {};
	
	var Manager = function(modname, callback){
		NS.managers[modname] = this;
		
		this.init(modname, callback);
	};
	Manager.prototype = {
		init: function(modname, callback){
			this.modname = modname;
			
			this.typeList = null;
			
			var __self = this;
			this.ajax({
				'do': 'cataloginitdata'
			}, function(d){
				__self._initDataUpdate(d);
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
		_initDataUpdate: function(d){
			this.catalogList = this._catalogListUpdate(d);
			
			if (!L.isNull(d) && !L.isNull(d['eltypes'])){
				this.typeList = new NS.ElementTypeList(d['eltypes']);
			}
		},
		_catalogListUpdate: function(d){
			var list = null;
			if (!L.isNull(d) && !L.isNull(d['catalogs'])){
				list = new NS.CatalogList(d['catalogs']);
			}
			return list;
		},
		catalogListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'cataloglist'
			}, function(d){
				var list = __self._catalogListUpdate(d);
				NS.life(callback, list);
			});
		},
		// вся информация по каталогу включая его элементы
		catalogLoad: function(catid, callback, cfg){
			cfg = L.merge({
				'elementlist': false
			}, cfg||{});
			
			var cat = this.catalogList.find(catid);

			var __self = this;
			this.ajax({
				'do': 'catalog',
				'catid': catid,
				'elementlist': cfg['elementlist']
			}, function(d){
				if (d && d['catalog'] && d['catalog']['dtl']){
					if (L.isNull(cat)){
						cat = new NS.Catalog(d);
					}
					cat.detail = new NS.CatalogDetail(d['catalog']['dtl']);
				}
				
				var list = __self._elementListUpdate(d);
				NS.life(callback, cat, list);
			});
		},
		_elementListUpdate: function(d){
			var list = null;
			if (!L.isNull(d) && !L.isNull(d['elements'])){
				list = new NS.ElementList(d['elements']['list']);
			}
			return list;
		},
		elementListLoad: function(catid, callback){
			var __self = this;
			this.ajax({
				'do': 'elementlist'
			}, function(d){
				var list = __self._elementListUpdate(d);
				NS.life(callback, list);
			});
		},
		
		_elementUpdateData: function(element, d){
			element = element || null;

			if (d && d['element'] && d['element']['dtl']){
				if (L.isNull(element)){
					element = new NS.Element(d);
				}
				element.detail = new NS.ElementDetail(d['element']['dtl']);
			}
			return element;
		},
		elementLoad: function(elementid, callback, element){
			var __self = this;
			this.ajax({
				'do': 'element',
				'elementid': elementid
			}, function(d){
				element = __self._elementUpdateData(element, d);
				NS.life(callback, element);
			});
		},
		elementSave: function(elementid, sd, callback, element){
			var __self = this;
			this.ajax({
				'do': 'elementsave',
				'elementid': elementid,
				'savedata': sd
			}, function(d){
				element = __self._elementUpdateData(element, d);
				NS.life(callback, element);
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