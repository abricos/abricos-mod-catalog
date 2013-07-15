/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'sys', files: ['item.js','container.js']},
        {name: 'widget', files: ['notice.js']},
        {name: '{C#MODNAME}', files: ['roles.js']}
	]		
};
Component.entryPoint = function(NS){

	var L = YAHOO.lang,
		CE = YAHOO.util.CustomEvent;
	
	var R = NS.roles;
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
	
	var CatalogItem = function(manager, d){
		this.manager = manager;
		d = L.merge({
			'pid': 0,
			'tl':'', // заголовок
			'nm': '', // имя (URL)
			'ord': 0,
			'ecnt': 0,
			'foto': '',
			'childs': []
		}, d || {});
		CatalogItem.superclass.constructor.call(this, d);
	};
	YAHOO.extend(CatalogItem, SysNS.Item, {
		init: function(d){
			this.detail		= null;
			this.parent		= null;
			this.childs		= this.manager.newCatalogList(d['childs']);
			
			var __self = this;
			this.childs.foreach(function(cat){
				cat.parent = __self;
			});
			this.expanded = d['id'] == 0;
			
			CatalogItem.superclass.init.call(this, d);
		},
		update: function(d){
			this.parentid	= d['pid']|0;
			this.title		= d['tl'];
			this.name		= d['nm'];
			this.foto		= d['foto'];
			this.order		= d['ord']|0;
			
			this.elementCount = d['ecnt']|0;
		},
		getPathLine: function(){
			var line = [this];
			if (!L.isNull(this.parent)){
				var pline = this.parent.getPathLine();
				pline[pline.length] = this;
				line = pline;
			}
			return line;
		},
		url: function(){ return null; }
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

	var CatalogList = function(manager, d, catalogItemClass, cfg){
		this.manager = manager;
		cfg = L.merge({
			'order': '!order,title'
		}, cfg || {});
		CatalogList.superclass.constructor.call(this, d, catalogItemClass, cfg);
	};
	YAHOO.extend(CatalogList, SysNS.ItemList, {
		createItem: function(di){
			return this.manager.newCatalogItem(di);
		},
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
	

	var Element = function(manager, d){
		this.manager = manager;

		d = L.merge({
			'catid': 0, 
			'tpid': 0, 
			'tl': '', 
			'nm': '',
			'ord': 0,
			'ext': {}
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
			this.ext		= d['ext'] || {};
		},
		copy: function(){
			var el = this.manager.newElement({
				'catid': this.catid,
				'tpid': this.typeid,
				'tl': "Copy " + this.title,
				'nm': this.name,
				'ord': this.order
			});
			
			if (!L.isNull(this.detail)){
				el.detail = this.detail.copy(el);
			}

			return el;
		},
		url: function(){ return null; }
	});
	NS.Element = Element;
	
	var ElementDetail = function(manager, owner, d){
		d = L.merge({
			'fotos': [],
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
		},
		copy: function(owner){
			
			var optb = {};
			for (var n in this.optionsBase){
				optb[n] = this.optionsBase[n];
			}
			
			var optp = {};
			for (var n in this.optionsPers){
				optp[n] = this.optionsPers[n];
			}

			var dtl = new ElementDetail(this.manager, owner, {
				'fotos': this.fotos,
				'mtl': this.metaTitle,
				'mks': this.metaKeys,
				'mdsc': this.metaDesc,
				'optb': optb,
				'optp': optp
			});
			return dtl;
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
	
	var ElementList = function(manager, d, catid, elementClass, cfg){
		this.manager = manager;
		this.catid = catid;
		ElementList.superclass.constructor.call(this, d, elementClass, cfg);
	};
	YAHOO.extend(ElementList, SysNS.ItemList, {
		createItem: function(di){
			return this.manager.newElement(di);
		},
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
			'CatalogItemClass': NS.CatalogItem,
			'CatalogListClass': NS.CatalogList,
			'ElementClass': NS.Element,
			'ElementListClass': NS.ElementList,
			'language': null
		}, cfg || {});
		
		NS.managers[modname] = this;
		
		this.init(modname, callback, cfg);
	};
	Manager.prototype = {
		init: function(modname, callback, cfg){
			this.modname = modname;
			this.cfg = cfg;

			this.CatalogItemClass	= cfg['CatalogItemClass'];
			this.CatalogListClass	= cfg['CatalogListClass'];
			this.ElementClass		= cfg['ElementClass'];
			this.ElementListClass	= cfg['ElementListClass'];
			
			this.typeList = null;
			
			this.catalogChangedEvent = new CE('catalogChangedEvent');
			this.catalogCreatedEvent = new CE('catalogCraetedEvent');
			this.catalogRemovedEvent = new CE('catalogRemovedEvent');
			
			var __self = this;
			R.load(function(){
				__self.ajax({
					'do': 'cataloginitdata'
				}, function(d){
					__self._initDataUpdate(d);
					NS.life(callback, __self);
				});
			});
		},
		newCatalogItem: function(d){
			return new this.CatalogItemClass(this, d);
		},
		newCatalogList: function(d){
			return new this.CatalogListClass(this, d);
		},
		newElement: function(d){
			return new this.ElementClass(this, d);
		},
		newElementList: function(d, catid, cfg){
			return new this.ElementListClass(this, d, catid, this.ElementClass, cfg);
		},
		onCatalogChanged: function(catid){
			this.catalogChangedEvent.fire(catid);
		},
		onCatalogCreated: function(catid){
			this.catalogCreatedEvent.fire(catid);
		},
		onCatalogRemoved: function(catid){
			this.catalogRemovedEvent.fire(catid);
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
				list = this.newCatalogList(d['catalogs']);
				
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
		_catalogUpdate: function(d){
			if (!(d && d['catalog'] && d['catalog']['dtl'])){
				return null;
			}
			
			var dcat = d['catalog'],  catid = dcat['id'];
			if (catid == 0){
				dcat['tl'] = this.getLang('catalog.title');
			}
				
			var cat = this.catalogList.find(catid), isNew = L.isNull(cat);
				
			if (L.isNull(cat)){
				cat = this.newCatalogItem(d['catalog']);
				
				var pcat = this.catalogList.find(cat.parentid);
				if (L.isValue(pcat)){
					pcat.childs.add(cat);
				}
			}
			
			var parentid = cat.parentid;
			
			cat.update(d['catalog']);
			cat.detail = new NS.CatalogDetail(d['catalog']['dtl']);
			
			if (!isNew && cat.parentid != parentid){ // был сменен родитель
				var oldParent = this.catalogList.find(parentid);
				if (L.isValue(oldParent)){
					oldParent.childs.remove(cat.id);
				}
				var pcat = this.catalogList.find(cat.parentid);
				if (L.isValue(pcat)){
					pcat.childs.add(cat);
				}
			}

			return cat;
		},
		// вся информация по каталогу включая его элементы
		catalogLoad: function(catid, callback, cfg){
			cfg = L.merge({
				'elementlist': false
			}, cfg||{});

			var __self = this;
			this.ajax({
				'do': 'catalog',
				'catid': catid,
				'elementlist': cfg['elementlist']
			}, function(d){
				var cat = __self._catalogUpdate(d);
				var list = __self._elementListUpdate(d, catid);
				NS.life(callback, cat, list);
			});
		},
		catalogSave: function(catid, sd, callback){
			var __self = this;
			this.ajax({
				'do': 'catalogsave',
				'catid': catid,
				'savedata': sd
			}, function(d){
				var cat = __self._catalogUpdate(d);
				if (!L.isNull(cat)){
					if (catid == 0){
						__self.onCatalogCreated(cat.id);
					}else{
						__self.onCatalogChanged(cat.id);
					}
				}
				NS.life(callback, cat);
			});
		},
		catalogRemove: function(catid, callback){
			var cat = this.catalogList.find(catid),
				pcat = null;
			
			if (!L.isNull(cat)){
				pcat = this.catalogList.find(cat.parentid);
			}
			var __self = this;
			this.ajax({
				'do': 'catalogremove',
				'catid': catid
			}, function(d){
				if (!L.isNull(pcat)){
					pcat.childs.remove(catid);
				}
				__self.onCatalogRemoved(catid);
				NS.life(callback);
			});
		},
		_elementListUpdate: function(d, catid){
			var list = null;
			if (d && d['elements'] && !L.isNull(d['elements'])){
				list = this.newElementList(d['elements']['list'], catid);
			}
			return list;
		},
		elementListLoad: function(catid, callback){
			var __self = this;
			this.ajax({
				'do': 'elementlist',
				'catid': catid
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
			if (!(d && d['element'] && d['element']['dtl'])){
				return null;
			}
			element = element || null;
			
			if (!L.isNull(element) && element.id == 0){ element = null; }
			
			if (L.isNull(element)){
				element = this.newElement(d['element']);
			}else{
				element.update(d['element']);
			}
			element.detail = new NS.ElementDetail(this, element, d['element']['dtl']);
			return element;
		},
		elementLoad: function(elementid, callback, element){
			
			if (elementid == 0){
				if (L.isNull(element)){
					element = this.newElement(d);
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