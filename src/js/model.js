var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']},
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        SYS = Brick.mod.sys;

    NS.Catalog = Y.Base.create('catalog', SYS.AppModel, [], {
        structureName: 'catalog'
    });

    NS.CatalogList = Y.Base.create('catalogList', SYS.AppModelList, [], {
        appItem: NS.Catalog
    });

    NS.ElementType = Y.Base.create('elementType', SYS.AppModel, [], {
        appInstance: null,
        structureName: 'elementType'
    }, {
        ATTRS: {
        }
    });

    NS.ElementTypeList = Y.Base.create('elementTypeList', SYS.AppModelList, [], {
        appInstance: null,
        appItem: NS.ElementType,
        idField: 'name'
    });

    return; /* * * * * * * * * TODO: OLD FUNCTIONS * * * * * * */

    var old_ElementType = function(manager, d){
        this.manager = manager;

        d = Y.merge({
            'tl': '',
            'tls': '',
            'nm': '',
            'options': []
        }, d || {});
        old_ElementType.superclass.constructor.call(this, d);
    };
    YAHOO.extend(old_ElementType, SYS.Item, {
        update: function(d){
            this.title = d['tl'];
            this.titleList = d['tls'];
            this.name = d['nm'];

            this.optionList = this.manager.newElementOptionList(d['options']);
            this.optionList.elTypeId = d['id'] | 0;
        }
    });
    NS.old_ElementType = old_ElementType;

    var old_ElementTypeList = function(manager, d, elementTypeClass, cfg){
        this.manager = manager;
        old_ElementTypeList.superclass.constructor.call(this, d, elementTypeClass, cfg);
    };
    YAHOO.extend(old_ElementTypeList, SYS.ItemList, {
        createItem: function(di){
            return this.manager.newold_ElementType(di);
        },
        getOption: function(optionid){
            var foption = null;
            this.foreach(function(elType){
                elType.optionList.foreach(function(option){
                    if (optionid == option.id){
                        foption = option;
                        return true;
                    }
                });
            });
            return foption;
        }
    });
    NS.old_ElementTypeList = old_ElementTypeList;

    NS.Item = SYS.Item;
    NS.ItemList = SYS.ItemList;

    NS.FTYPE = {
        'BOOLEAN': 0,
        'NUMBER': 1,
        'DOUBLE': 2,
        'STRING': 3,
        // 'LIST':		4,
        'TABLE': 5,
        'TEXT': 7,
        'ELDEPENDS': 9,
        'ELDEPENDSNAME': 10,
        'FILES': 11,
        'CURRENCY': 12
    };

    var Dict = function(d){
        d = Y.merge({
            'tl': ''
        }, d || {});
        Dict.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Dict, SYS.Item, {
        update: function(d){
            this.title = d['tl'];
        }
    });
    NS.Dict = Dict;

    var DictList = function(d){
        DictList.superclass.constructor.call(this, d, Dict);
    };
    YAHOO.extend(DictList, SYS.ItemList, {});
    NS.DictList = DictList;

    var CatalogItem = function(manager, d){
        this.manager = manager;
        d = Y.merge({
            'pid': 0,
            'tl': '', // заголовок
            'nm': '', // имя (URL)
            'mdsb': 0,
            'ldsb': 0,
            'ord': 0,
            'ecnt': 0,
            'foto': '',
            'childs': []
        }, d || {});
        CatalogItem.superclass.constructor.call(this, d);
    };
    YAHOO.extend(CatalogItem, SYS.Item, {
        init: function(d){
            this.detail = null;
            this.parent = null;
            this.childs = this.manager.newCatalogList(d['childs']);

            var __self = this;
            this.childs.foreach(function(cat){
                cat.parent = __self;
            });
            this.expanded = d['id'] == 0;

            CatalogItem.superclass.init.call(this, d);
        },
        update: function(d){
            this.parentid = d['pid'] | 0;
            this.title = d['tl'];
            this.name = d['nm'];
            this.foto = d['foto'];
            this.menuDisable = (d['mdsb'] | 0) > 0;
            this.listDisable = (d['ldsb'] | 0) > 0;
            this.order = d['ord'] | 0;

            this.elementCount = d['ecnt'] | 0;
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
        url: function(){
            return null;
        }
    });
    NS.CatalogItem = CatalogItem;

    var CatalogDetail = function(d){
        d = Y.merge({
            'dsc': '',
            'mtl': '',
            'mks': '',
            'mdsc': ''
        }, d || {});
        CatalogDetail.superclass.constructor.call(this, d);
    };
    YAHOO.extend(CatalogDetail, SYS.Item, {
        update: function(d){
            this.descript = d['dsc'];
            this.metaTitle = d['mtl'];
            this.metaKeys = d['mks'];
            this.metaDescript = d['mdsc'];
        }
    });
    NS.CatalogDetail = CatalogDetail;

    var CatalogList = function(manager, d, catalogItemClass, cfg){
        this.manager = manager;
        cfg = Y.merge({
            'order': '!order,title'
        }, cfg || {});
        CatalogList.superclass.constructor.call(this, d, catalogItemClass, cfg);
    };
    YAHOO.extend(CatalogList, SYS.ItemList, {
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
        d = Y.merge({
            'catid': 0,
            'uid': 0,
            'tpid': 0,
            'tl': '',
            'nm': '',
            'ord': 0,
            'ext': {},
            'mdr': 0
        }, d || {});
        Element.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Element, SYS.Item, {
        init: function(d){
            this.detail = null;
            Element.superclass.init.call(this, d);
        },
        update: function(d){
            this.catid = d['catid'] | 0;
            this.userid = d['uid'] | 0;
            this.typeid = d['tpid'] | 0;
            this.title = d['tl'];
            this.name = d['nm'];
            this.order = d['ord'] | 0;
            this.isModer = (d['mdr'] | 0) > 0;
            this.ext = d['ext'] || {};
        },
        copy: function(){
            var tl = this.title;
            if (!this.manager.cfg['versionControl']){
                tl = "Copy " + this.title;
            }

            var el = this.manager.newElement({
                'catid': this.catid,
                'tpid': this.typeid,
                'tl': tl,
                'nm': this.name,
                'ord': this.order
            });

            if (!L.isNull(this.detail)){
                el.detail = this.detail.copy(el);
            }

            return el;
        },
        url: function(){
            return null;
        }
    });
    NS.Element = Element;

    var ElementDetail = function(manager, owner, d){
        d = Y.merge({
            'fotos': [],
            'mtl': '',
            'mdsc': '',
            'mks': '',
            'optb': {},
            'optp': {},
            'chlg': ''
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

            this.changeLog = d['chlg'];
        },
        foreach: function(f){
            var man = this.manager;
            var fore = function(tpid, opts){
                var tp = man.typeList.get(tpid);
                if (L.isNull(tp)){
                    return;
                }
                tp.optionList.foreach(function(option){
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

    var ElementList = function(manager, d, catid, elementClass, cfg){
        this.manager = manager;
        this.catid = catid;
        ElementList.superclass.constructor.call(this, d, elementClass, cfg);
    };
    YAHOO.extend(ElementList, SYS.ItemList, {
        createItem: function(di){
            return this.manager.newElement(di);
        },
        foreach: function(f, orderBy, orderDesc){
            if (!L.isString(orderBy)){
                ElementList.superclass.foreach.call(this, f);
                return;
            }

            var list = this.list.sort(function(el1, el2){

                if (el1[orderBy] > el2[orderBy]){
                    return orderDesc ? -1 : 1;
                }
                if (el1[orderBy] < el2[orderBy]){
                    return orderDesc ? 1 : -1;
                }

                return 0;
            });
            for (var i = 0; i < list.length; i++){
                if (NS.life(f, list[i])){
                    return;
                }
            }
        }
    });
    NS.ElementList = ElementList;

    var ElementOption = function(manager, d){
        this.manager = manager;
        d = Y.merge({
            'tl': '',
            'nm': '',
            'prm': '',
            'tpid': 0,
            'tp': 0,
            'sz': '',
            'gid': 0,
            'crcid': 0,
            'ord': 0
        }, d || {});
        ElementOption.superclass.constructor.call(this, d);
    };
    YAHOO.extend(ElementOption, SYS.Item, {
        update: function(d){
            this.title = d['tl'];
            this.name = d['nm'];
            this.param = d['prm'];
            this.typeid = d['tpid'] | 0;
            this.type = d['tp'] | 0;
            this.size = d['sz'];
            this.currencyid = d['crcid'] | 0;
            this.groupid = d['gid'] | 0;
            this.order = d['ord'] | 0;
        }
    });
    NS.ElementOption = ElementOption;

    var ElementOptionTable = function(manager, d){
        d = Y.merge({
            'values': {}
        }, d || {});
        ElementOptionTable.superclass.constructor.call(this, manager, d);
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
                if (d1['tl'] > d2['tl']){
                    return 1;
                }
                if (d1['tl'] < d2['tl']){
                    return -1;
                }
                return 0;
            });
            this.values = new NS.DictList(arr);
        }
    });
    NS.ElementOptionTable = ElementOptionTable;

    var ElementOptionList = function(manager, d, elementOptionItemClass, cfg){
        this.manager = manager;
        elementOptionItemClass = elementOptionItemClass || ElementOption;

        cfg = Y.merge({
            'order': '!order,title'
        }, cfg || {});

        ElementOptionList.superclass.constructor.call(this, d, elementOptionItemClass, cfg);
    };
    YAHOO.extend(ElementOptionList, SYS.ItemList, {
        createItem: function(di){
            if (di['tp'] == NS.FTYPE['TABLE']){
                var opt = new ElementOptionTable(this.manager, di);
                return opt;
            }
            return this.manager.newElementOption(di);
        }
    });
    NS.ElementOptionList = ElementOptionList;

    var ElementOptionGroup = function(manager, d){
        this.manager = manager;

        d = Y.merge({
            'tpid': 0,
            'tl': '',
            'nm': '',
            'ord': 0
        }, d || {});
        ElementOptionGroup.superclass.constructor.call(this, d);
    };
    YAHOO.extend(ElementOptionGroup, SYS.Item, {
        update: function(d){
            this.elTypeId = d['tpid'] | 0;
            this.title = d['tl'];
            this.name = d['nm'];
        }
    });
    NS.ElementOptionGroup = ElementOptionGroup;

    var ElementOptionGroupList = function(manager, d, elementOptionGroupClass, cfg){
        this.manager = manager;
        ElementOptionGroupList.superclass.constructor.call(this, d, elementTypeClass, cfg);
    };
    YAHOO.extend(ElementOptionGroupList, SYS.ItemList, {
        createItem: function(di){
            return this.manager.newElementOptionGroup(di);
        }
    });
    NS.ElementOptionGroupList = ElementOptionGroupList;


    var Currency = function(manager, d){
        this.manager = manager;

        d = Y.merge({
            'isdefault': 0,
            'title': '',
            'codestr': '',
            'codenum': 0,
            'rateval': 0,
            'ratedate': 0,
            'prefix': '',
            'postfix': '',
            'ord': 0
        }, d || {});
        Currency.superclass.constructor.call(this, d);
    };
    YAHOO.extend(Currency, SYS.Item, {
        update: function(d){
            this.isDefault = d['isdefault'] | 0 > 0;
            this.title = d['title'];
            this.codestr = d['codestr'];
            this.codenum = d['codenum'];
            this.rateval = d['rateval'];
            this.ratedate = d['ratedate'];
            this.prefix = d['prefix'];
            this.postfix = d['postfix'];
            this.ord = d['ord'];
        }
    });
    NS.Currency = Currency;

    var CurrencyList = function(manager, d, currencyClass, cfg){
        this.manager = manager;
        CurrencyList.superclass.constructor.call(this, d, currencyClass, cfg);
    };
    YAHOO.extend(CurrencyList, SYS.ItemList, {
        createItem: function(di){
            return this.manager.newCurrency(di);
        }
    });
    NS.CurrencyList = CurrencyList;

};