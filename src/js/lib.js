var Component = new Brick.Component();
Component.requires = {
    yahoo: ['dom', 'event'],
    mod: [
        {name: 'sys', files: ['application.js', 'item.js', 'container.js']},
        {name: 'widget', files: ['notice.js', 'lib.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    NS.Application = {
        ATTRS: {
            isLoadAppStructure: {value: true},
            Catalog: {value: NS.Catalog},
            CatalogList: {value: NS.CatalogList},
            Element: {value: NS.Element},
            ElementList: {value: NS.ElementList},
            ElementType: {value: NS.ElementType},
            ElementTypeList: {value: NS.ElementTypeList},
        },
        REQS: {
            catalogList: {
                attribute: true,
                type: 'modelList:CatalogList',
            },
            elementTypeList: {
                attribute: true,
                type: 'modelList:ElementTypeList',
            },
            elementList: {
                args: ['config'],
                attribute: false,
                type: 'modelList:ElementList'
            }
        }
    };

    return;
    /* * * * * * * * * TODO: OLD FUNCTIONS * * * * * * */


    NS.managers = {};

    var Manager = function(modname, callback, cfg){
        cfg = Y.merge({
            'roles': {},
            'CatalogItemClass': NS.CatalogItem,
            'CatalogListClass': NS.CatalogList,
            'ElementClass': NS.Element,
            'ElementListClass': NS.ElementList,
            'old_ElementTypeClass': NS.old_ElementType,
            'old_ElementTypeListClass': NS.old_ElementTypeList,
            'ElementOptionGroupClass': NS.ElementOptionGroup,
            'ElementOptionGroupListClass': NS.ElementOptionGroupList,
            'ElementOptionClass': NS.ElementOption,
            'ElementOptionListClass': NS.ElementOptionList,
            'CurrencyClass': NS.Currency,
            'CurrencyListClass': NS.CurrencyList,
            'language': null,
            'elementNameChange': false,
            'elementNameUnique': false,
            'elementCreateBaseTypeDisable': false,
            'versionControl': false
        }, cfg || {});

        cfg['roles'] = Y.merge({
            'isView': false,
            'isWrite': false,
            'isOperator': false,
            'isModerator': false,
            'isAdmin': false
        }, cfg['roles'] || {});

        var r = cfg['roles'];
        r['isOperatorOnly'] = r['isOperator'] && !r['isModerator'] && !r['isAdmin'];

        NS.managers[modname] = this;

        this.init(modname, callback, cfg);
    };
    Manager.prototype = {
        init: function(modname, callback, cfg){
            this.modname = modname;
            this.cfg = cfg;
            this.roles = cfg['roles'];

            this.CatalogItemClass = cfg['CatalogItemClass'];
            this.CatalogListClass = cfg['CatalogListClass'];
            this.ElementClass = cfg['ElementClass'];
            this.ElementListClass = cfg['ElementListClass'];
            this.old_ElementTypeClass = cfg['old_ElementTypeClass'];
            this.old_ElementTypeListClass = cfg['old_ElementTypeListClass'];
            this.ElementOptionGroupClass = cfg['ElementOptionGroupClass'];
            this.ElementOptionGroupListClass = cfg['ElementOptionGroupListClass'];
            this.ElementOptionClass = cfg['ElementOptionClass'];
            this.ElementOptionListClass = cfg['ElementOptionListClass'];
            this.CurrencyClass = cfg['CurrencyClass'];
            this.CurrencyListClass = cfg['CurrencyListClass'];

            this.typeList = null;
            this.optionGroupList = null;

            this.catalogChangedEvent = new CE('catalogChangedEvent');
            this.catalogCreatedEvent = new CE('catalogCraetedEvent');
            this.catalogRemovedEvent = new CE('catalogRemovedEvent');

            var __self = this;
            this.ajax({
                'do': 'cataloginitdata'
            }, function(d){
                __self._initDataUpdate(d);
                NS.life(callback, __self);
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
        newold_ElementType: function(d){
            return new this.old_ElementTypeClass(this, d);
        },
        newold_ElementTypeList: function(d, cfg){
            return new this.old_ElementTypeListClass(this, d, this.old_ElementTypeClass, cfg);
        },
        newElementOptionGroup: function(d){
            return new this.ElementOptionGroupClass(this, d);
        },
        newElementOptionGroupList: function(d, cfg){
            return new this.ElementOptionGroupListClass(this, d, this.ElementOptionGroupClass, cfg);
        },
        newElementOption: function(d){
            return new this.ElementOptionClass(this, d);
        },
        newElementOptionList: function(d, cfg){
            return new this.ElementOptionListClass(this, d, this.ElementOptionClass, cfg);
        },
        newCurrency: function(d){
            return new this.CurrencyClass(this, d);
        },
        newCurrencyList: function(d, cfg){
            return new this.CurrencyListClass(this, d, this.CurrencyClass, cfg);
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
                if (res !== ''){
                    return res;
                }
            }
            return LNG.get('lib.' + path);
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
            this.currencyList = this._currencyListUpdate(d);
            this._typeListUpdate(d);
            this._optionGroupListUpdate(d);
        },
        _typeListUpdate: function(d){
            var list = null;
            if (!L.isNull(d) && !L.isNull(d['eltypes'])){
                list = this.newold_ElementTypeList(d['eltypes']);

                var btype = list.get(0);
                if (!L.isNull(btype)){
                    btype.title = this.getLang('element.type.base');
                }
            }
            if (L.isValue(list)){
                this.typeList = list;
            }
            return list;
        },
        _optionGroupListUpdate: function(d){
            var list = null;
            if (!L.isNull(d) && !L.isNull(d['eloptgroups'])){
                list = this.newold_ElementTypeList(d['eloptgroups']);
            }
            if (L.isValue(list)){
                this.optionGroupList = list;
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
        _currencyListUpdate: function(d){
            var list = null;
            if (d && d.currencies && d.currencies.list){
                list = this.newCurrencyList(d.currencies.list);
            }
            if (L.isValue(list)){
                this.currencyList = list;
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

            var dcat = d['catalog'], catid = dcat['id'];
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
            cfg = Y.merge({
                'elementlist': false
            }, cfg || {});

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
                    } else {
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
                'catid': catid | 0,
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

            if (!L.isNull(element) && element.id == 0){
                element = null;
            }

            if (L.isNull(element)){
                element = this.newElement(d['element']);
            } else {
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
        elementModer: function(elementid, callback, element){
            var __self = this;
            this.ajax({
                'do': 'elementmoder',
                'elementid': elementid
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
        elementTypeSave: function(typeid, sd, callback){
            var __self = this;
            this.ajax({
                'do': 'elementtypesave',
                'eltypeid': typeid,
                'savedata': sd
            }, function(d){
                __self._typeListUpdate(d);
                NS.life(callback);
            });
        },
        elementTypeRemove: function(typeid, callback){
            var __self = this;
            this.ajax({
                'do': 'elementtyperemove',
                'eltypeid': typeid
            }, function(d){
                __self._typeListUpdate(d);
                NS.life(callback);
            });
        },
        optionSave: function(optionid, sd, callback){
            var __self = this;
            this.ajax({
                'do': 'elementoptionsave',
                'optionid': optionid,
                'savedata': sd
            }, function(d){
                __self._typeListUpdate(d);
                NS.life(callback);
            });
        },
        optionRemove: function(typeid, optionid, callback){
            var __self = this;
            this.ajax({
                'do': 'elementoptionremove',
                'eltypeid': typeid,
                'optionid': optionid
            }, function(d){
                __self._typeListUpdate(d);
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
        },
        elementIdByName: function(name, callback){
            this.ajax({
                'do': 'elementidbyname',
                'elname': name
            }, function(d){
                var info = {'elementid': 0, 'userid': 0};
                if (L.isValue(d)){
                    info = d;
                }
                NS.life(callback, info);
            });
        },
        currencySave: function(currencyid, sd, callback){
            var __self = this;
            this.ajax({
                'do': 'currencysave',
                'currencyid': currencyid,
                'savedata': sd
            }, function(d){
                __self._currencyListUpdate(d);
                NS.life(callback);
            });
        },
        сг: function(currencyid, callback){
            var __self = this;
            this.ajax({
                'do': 'currencyremove',
                'currencyid': currencyid
            }, function(d){
                __self._currencyListUpdate(d);
                NS.life(callback);
            });
        }
    };
    NS.Manager = Manager;

    NS.initManager = function(modname, callback, cfg){
        if (!NS.managers[modname]){
            if (Brick.mod[modname] && Brick.mod[modname]['Manager']){
                new Brick.mod[modname]['Manager'](modname, callback, cfg);
            } else {
                new NS.Manager(modname, callback, cfg);
            }
        } else {
            NS.life(callback, NS.managers[modname]);
        }
    };

};