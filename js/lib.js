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
	var LNG = this.language;

	var buildTemplate = this.buildTemplate;
	buildTemplate({},'');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	var Dict = function(d){
		d = L.merge({
			'tl':''
		}, d || {});
		Dict.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Dict, SysNS.Item, {
		update: function(d){
			this.title		= d['tl'];
		}
	});		
	NS.Dict = Dict;	
	
	var DictList = function(d){
		DictList.superclass.constructor.call(this, d, Dict);
	};
	YAHOO.extend(DictList, SysNS.ItemList, { });
	NS.DictList = DictList;
	
	var CatalogItem = function(d){
		d = L.merge({
			'pid': 0,
			'tl':'', // заголовок
			'nm': '', // имя (URL)
			'ecnt': 0,
			'foto': '',
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
			this.parentid	= d['pid']|0;
			this.title		= d['tl'];
			this.name		= d['nm'];
			this.foto		= d['foto'];
			
			this.elementCount = d['ecnt']|0;
			
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
			'nm': '',
			'ord': 0
		}, d || {});
		Element.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Element, SysNS.Item, {
		init: function(d){
			this.detail = null;
			Element.superclass.init.call(this, d);
		},
		update: function(d){
			this.catid		= d['catid']|0;
			this.typeid		= d['tpid']|0;
			this.title		= d['tl'];
			this.name		= d['nm'];
			this.order		= d['ord']|0;
		}
	});		
	NS.Element = Element;
	
	var ElementDetail = function(manager, owner, d){
		d = L.merge({
			'imgs': [],
			'mtl': '',
			'mdsc': '',
			'mks': '',
			'optb': {},
			'optp': {}
		}, d || {});
		this.init(manager, owner, d);
	};
	ElementDetail.prototype = {
		init: function(manager, owner, d){
			this.manager = manager;
			this.owner = owner;

			this.fotos = d['fotos'];
			
			this.metaTitle = d['mtl'];
			this.metaKeys = d['mks'];
			this.metaDesc = d['mdsc'];
			
			this.optionsBase = d['optb'];
			this.optionsPers = d['optp'];
		},
		foreach: function(f){
			var man = this.manager;
			var fore = function(tpid, opts){
				var tp = man.typeList.get(tpid);
				if (L.isNull(tp)){ return; }
				tp.options.foreach(function(option){
					var value = opts[option.name] || '';
					NS.life(f, option, value);
				});
			};
			fore(0, this.optionsBase);
			
			if (this.owner.typeid > 0){
				fore(this.owner.typeid, this.optionsPers);
			}
		}
	};
	NS.ElementDetail = ElementDetail;
	
	NS.FTYPE = {
		'BOOLEAN':	0,
		'NUMBER':	1,
		'DOUBLE':	2,
		'STRING':	3,
		// 'LIST':		4,
		'TABLE':	5,
		'TEXT':		7
	};
	
	var ElementList = function(d, catid){
		this.catid = catid;
		ElementList.superclass.constructor.call(this, d, Element);
	};
	YAHOO.extend(ElementList, SysNS.ItemList, {
		foreach: function(f, orderBy, orderDesc){
			if (!L.isString(orderBy)){
				ElementList.superclass.foreach.call(this, f);
				return;
			}
			
			var list = this.list.sort(function(el1, el2){
				
				if (el1[orderBy] > el2[orderBy]){ return orderDesc ? -1 : 1; }
				if (el1[orderBy] < el2[orderBy]){ return orderDesc ? 1 : -1; }
				
				return 0;
			});
			for (var i=0;i<list.length;i++){
				if (NS.life(f, list[i])){ return; }
			}
		}
	});
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
			this.typeid	= d['tpid']|0;
			this.type		= d['tp']|0;
		}
	});
	NS.ElementOption = ElementOption;
	
	var ElementOptionTable = function(d){
		d = L.merge({
			'values': {}
		}, d || {});
		ElementOptionTable.superclass.constructor.call(this, d);
	};
	YAHOO.extend(ElementOptionTable, ElementOption, {
		update: function(d){
			ElementOptionTable.superclass.update.call(this, d);
			
			this.updateValues(d['values']);
		},
		updateValues: function(vals){
			vals = vals || {};
			var arr = [];
			for (var n in vals){
				arr[arr.length] = vals[n];
			}
			arr = arr.sort(function(d1, d2){
				if (d1['tl'] > d2['tl']){ return 1; }
				if (d1['tl'] < d2['tl']){ return -1; }
				return 0;
			});
			this.values = new NS.DictList(arr);
		}
	});
	NS.ElementOptionTable = ElementOptionTable;
	
	var ElementOptionList = function(d){
		ElementOptionList.superclass.constructor.call(this, d, ElementOption);
	};
	YAHOO.extend(ElementOptionList, SysNS.ItemList, {
		createItem: function(di){
			if (di['tp'] == NS.FTYPE['TABLE']){
				var opt = new ElementOptionTable(di);
				return opt;
			}
			return ElementOptionList.superclass.createItem.call(this, di);
		}
	});
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
	
	var Manager = function(modname, callback, cfg){
		
		cfg = L.merge({
			'language': null
		}, cfg || {});
		
		NS.managers[modname] = this;
		
		this.init(modname, callback, cfg);
	};
	Manager.prototype = {
		init: function(modname, callback, cfg){
			this.modname = modname;
			this.cfg = cfg;
			
			this.typeList = null;
			
			var __self = this;
			this.ajax({
				'do': 'cataloginitdata'
			}, function(d){
				__self._initDataUpdate(d);
				NS.life(callback, __self);
			});
		},
		getLang: function(path){
			var lng = this.cfg['language'];
			if (!L.isNull(lng) && L.isFunction(lng.get)){
				var res = lng.get(path);
				if (res != path){ return res;}
			}
			return LNG.get(path);
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
			this.typeList = this._typeListUpdate(d);
		},
		_typeListUpdate: function(d){
			var list = null;
			if (!L.isNull(d) && !L.isNull(d['eltypes'])){
				list = new NS.ElementTypeList(d['eltypes']);
				
				var btype = list.get(0);
				if (!L.isNull(btype)){
					btype.title = this.getLang('element.type.base');
				}
			}
			return list;
		},
		_catalogListUpdate: function(d){
			var list = null;
			if (!L.isNull(d) && !L.isNull(d['catalogs'])){
				list = new NS.CatalogList(d['catalogs']);
				
				var rootItem = list.find(0);
				if (!L.isNull(rootItem)){
					rootItem.title = this.getLang('catalog.title');
				}
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
		_elementListUpdate: function(d, catid){
			var list = null;
			if (!L.isNull(d) && !L.isNull(d['elements'])){
				list = new NS.ElementList(d['elements']['list'], catid);
			}
			return list;
		},
		elementListLoad: function(catid, callback){
			var __self = this;
			this.ajax({
				'do': 'elementlist'
			}, function(d){
				var list = __self._elementListUpdate(d, catid);
				NS.life(callback, list);
			});
		},
		
		elementListOrderSave: function(catid, orders, callback){
			var __self = this;
			this.ajax({
				'do': 'elementlistordersave',
				'catid': catid|0,
				'orders': orders
			}, function(d){
				var list = __self._elementListUpdate(d, catid);
				NS.life(callback, list);
			});
		},
		
		_elementUpdateData: function(element, d){
			element = element || null;

			if (d && d['element'] && d['element']['dtl']){
				if (L.isNull(element)){
					element = new NS.Element(d);
				}else{
					element.update(d['element']);
				}
				element.detail = new NS.ElementDetail(this, element, d['element']['dtl']);
			}
			return element;
		},
		elementLoad: function(elementid, callback, element){
			
			if (elementid == 0){
				if (L.isNull(element)){
					element = new NS.Element(d);
				}
				element.detail = new NS.ElementDetail(this, element);
				NS.life(callback, element);
				return;
			}
			
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
		},
		elementRemove: function(elementid, callback){
			this.ajax({
				'do': 'elementremove',
				'elementid': elementid
			}, function(d){
				NS.life(callback);
			});
		},
		optionTableValueSave: function(typeid, optid, valueid, value, callback){
			this.ajax({
				'do': 'optiontablevaluesave',
				'eltypeid': typeid,
				'optionid': optid,
				'valueid': valueid,
				'value': value
			}, function(d){
				var values = null, valueid = 0;
				if (!L.isNull(d)){
					values = d['values'];
					valueid = d['valueid'];
				}
				NS.life(callback, values, valueid);
			});
		},
		optionTableValueRemove: function(typeid, optid, valueid, callback){
			this.ajax({
				'do': 'optiontablevalueremove',
				'eltypeid': typeid,
				'optionid': optid,
				'valueid': valueid
			}, function(d){
				var values = null;
				if (!L.isNull(d)){
					values = d['values'];
				}
				NS.life(callback, values);
			});
		}
	};
	NS.Manager = Manager;
	
	NS.initManager = function(modname, callback, cfg){
		if (!NS.managers[modname]){
			if (Brick.mod[modname] && Brick.mod[modname]['Manager']){
				new Brick.mod[modname]['Manager'](modname, callback, cfg);
			}else{
				new NS.Manager(modname, callback, cfg);
			}
		}else{
			NS.life(callback, NS.managers[modname]);
		}
	};
	
};