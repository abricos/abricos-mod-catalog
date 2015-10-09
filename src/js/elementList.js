var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['elementeditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.ElementListWidget = Y.Base.create('elementListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this._widgetList = [];

            // var tp = this.template;

            var listConfig = {
                id: 'default',
                filter: [{
                    id: 1,
                    field: 'catalogid',
                    exp: '=',
                    value: this.get('catalogid')
                }]
            };

            appInstance.elementList(listConfig, function(err, result){
                if (!err){
                    this.set('elementList', result.elementList);
                }
                this.renderElementList();
            }, this);
        },
        destructor: function(){
            this.clearList();
        },
        clearList: function(){
            var ws = this._widgetList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.template.setHTML('list', '');
        },
        renderElementList: function(){
            var elementList = this.get('elementList');
            if (!elementList){
                return;
            }
            this.clearList();

            var tp = this.template,
                ws = this._widgetList;

            elementList.each(function(element){
                var w = new NS.ElementListWidget.RowWidget({
                    srcNode: tp.append('list', '<div></div>'),
                    element: element
                });
                ws[ws.length] = w;
            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            elementList: {},
            catalogid: {value: 0}
        }
    });

    NS.ElementListWidget.RowWidget = Y.Base.create('elementRowWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){

        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            element: {}
        }
    });


    return; ////////// TODO: remove old functions

    YAHOO.extend(ElementListWidget, BW, {

        setList: function(list){
            this.list = list;
            this.allEditorClose();
            this.render();
        },
        render: function(){

            var elList = this.gel('list'), ws = this._widgetList,
                __self = this, man = this.manager;

            var list = this.list;

            var buildList = function(catel){
                if (list.catid != catel.catid){
                    return;
                }

                var div = document.createElement('div');
                div['catalogElement'] = catel;

                elList.appendChild(div);
                var w = new NS.ElementRowWidget(div, __self.manager, catel, {
                    'onEditClick': function(w){
                        __self.onElementEditClick(w);
                    },
                    'onCopyClick': function(w){
                        __self.onElementCopyClick(w);
                    },
                    'onRemoveClick': function(w){
                        __self.onElementRemoveClick(w);
                    },
                    'onSelectClick': function(w){
                        __self.onElementSelectClick(w);
                    },
                    'onSaveElement': function(w){
                        __self.render();
                    }
                });

                if (man.roles['isAdmin']){
                    new NS.ElementRowDragItem(div, {
                        'endDragCallback': function(dgi, elDiv){
                            var chs = elList.childNodes, ordb = list.count();
                            var orders = {};
                            for (var i = 0; i < chs.length; i++){
                                var catel = chs[i]['catalogElement'];
                                if (catel){
                                    catel.order = ordb;
                                    orders[catel.id] = ordb;
                                    ordb--;
                                }
                            }
                            man.elementListOrderSave(list.catid, orders);
                            __self.render();
                        }
                    });
                }

                ws[ws.length] = w;
            };

            list.foreach(function(el){
                if (!el.isModer){
                    return;
                }
                buildList(el);
            }, 'order', true);

            list.foreach(function(el){
                if (el.isModer){
                    return;
                }
                buildList(el);
            }, 'order', true);

            new YAHOO.util.DDTarget(elList);
        },
        foreach: function(f){
            if (!L.isFunction(f)){
                return;
            }
            var ws = this._widgetList;
            for (var i = 0; i < ws.length; i++){
                if (f(ws[i])){
                    return;
                }
            }
        },
        allEditorClose: function(wExclude){
            this.newEditorClose();
            this.foreach(function(w){
                if (w != wExclude){
                    w.editorClose();
                }
            });
        },
        onElementEditClick: function(w){
            this.allEditorClose(w);
            w.editorShow();
        },
        onElementCopyClick: function(w){
            this.showNewEditor(w.catel);
        },
        onElementRemoveClick: function(w){
            var __self = this;
            new ElementRemovePanel(this.manager, w.catel, function(list){
                __self.list.remove(w.catel.id);
                __self.render();
            });
        },
        onElementSelectClick: function(w){
            this.allEditorClose(w);
            // w.editorShow();
        },
        showNewEditor: function(fel){
            if (!L.isNull(this.newEditorWidget)){
                return;
            }

            this.allEditorClose();
            var man = this.manager, __self = this;
            var catel = man.newElement({'catid': this.list.catid});

            this.newEditorWidget =
                new NS.ElementEditorWidget(this.gel('neweditor'), man, catel, {
                    'fromElement': fel || null,
                    'onCancelClick': function(wEditor){
                        __self.newEditorClose();
                    },
                    'onSaveElement': function(wEditor, element){
                        if (!L.isNull(element)){
                            __self.list.add(element);
                        }
                        __self.newEditorClose();
                        __self.render();
                    }
                });
        },
        newEditorClose: function(){
            if (L.isNull(this.newEditorWidget)){
                return;
            }
            this.newEditorWidget.destroy();
            this.newEditorWidget = null;
        }
    });
    NS.ElementListWidget = ElementListWidget;

    var ElementRowDragItem = function(id, cfg){
        ElementRowDragItem.superclass.constructor.call(this, id, '', cfg);

        var el = Dom.get(id);
        Dom.addClass(el, 'dragitem');

        this.goingUp = false;
        this.lastY = 0;
    };
    YAHOO.extend(ElementRowDragItem, YAHOO.util.DDProxy, {
        startDrag: function(x, y){
            var dragEl = this.getDragEl();
            var clickEl = this.getEl();
            dragEl.innerHTML = clickEl.innerHTML;

            Dom.setStyle(clickEl, "visibility", "hidden");
            Dom.setStyle(dragEl, "backgroundColor", "#FFF");
        },
        onDrag: function(e){
            var y = E.getPageY(e);

            if (y < this.lastY){
                this.goingUp = true;
            } else if (y > this.lastY){
                this.goingUp = false;
            }

            this.lastY = y;
        },
        onDragOver: function(e, id){
            var srcEl = this.getEl();
            var destEl = Dom.get(id);

            if (Dom.hasClass(destEl, 'dragitem')){
                var p = destEl.parentNode;

                if (this.goingUp){
                    p.insertBefore(srcEl, destEl); // insert above
                } else {
                    p.insertBefore(srcEl, destEl.nextSibling); // insert below
                }
                DDM.refreshCache();
            }
        },
        endDrag: function(e){

            var srcEl = this.getEl();
            var proxy = this.getDragEl();

            Dom.setStyle(proxy, "visibility", "");
            var a = new YAHOO.util.Motion(
                proxy, {
                    points: {
                        to: Dom.getXY(srcEl)
                    }
                },
                0.2,
                YAHOO.util.Easing.easeOut
            );
            var proxyid = proxy.id;
            var thisid = this.id;

            a.onComplete.subscribe(function(){
                Dom.setStyle(proxyid, "visibility", "hidden");
                Dom.setStyle(thisid, "visibility", "");
            });
            a.animate();

            NS.life(this.config['endDragCallback'], this, srcEl);
        },
        onDragDrop: function(e, id){
            if (DDM.interactionInfo.drop.length === 1){
                var pt = DDM.interactionInfo.point;
                var region = DDM.interactionInfo.sourceRegion;
                if (!region.intersect(pt)){
                    var destEl = Dom.get(id);
                    var destDD = DDM.getDDById(id);
                    destEl.appendChild(this.getEl());
                    destDD.isEmpty = false;
                    DDM.refreshCache();
                }
            }
        }
    });
    NS.ElementRowDragItem = ElementRowDragItem;

    var ElementRowWidget = function(container, manager, catel, cfg){
        cfg = L.merge({
            'onEditClick': null,
            'onCopyClick': null,
            'onRemoveClick': null,
            'onSelectClick': null,
            'onSaveElement': null
        }, cfg || {});
        ElementRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, manager, catel, cfg);
    };
    YAHOO.extend(ElementRowWidget, BW, {
        init: function(manager, catel, cfg){
            this.manager = manager;
            this.catel = catel;
            this.cfg = cfg;
            this.editorWidget = null;
        },
        onLoad: function(man, el){
            this.elSetHTML({
                'idval': el.id,
                'nm': el.name,
                'tl': el.title
            });
            if (L.isNull(el.url())){
                this.elHide('bgopage');
            }
            if (man.cfg.elementNameUnique){
                this.elShow('colnm');
            }
            var roles = man.roles;
            if (roles['isAdmin']){
                this.elShow('bedit,bcopy,bremove');
            } else if (roles['isOperatorOnly'] && el.userid == UID){
                if (el.isModer){
                    this.elShow('bedit,bremove');
                } else {
                    this.elShow('bcopy');
                }
            }
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bgopage']:
                case tp['bgopagec']:
                    this.goPage();
                    return true;
                case tp['bedit']:
                case tp['beditc']:
                    this.onEditClick();
                    return true;
                case tp['bcopy']:
                case tp['bcopyc']:
                    this.onCopyClick();
                    return true;
                case tp['bremove']:
                case tp['bremovec']:
                    this.onRemoveClick();
                    return true;
                case tp['dtl']:
                case tp['tl']:
                    this.onSelectClick();
                    return true;
            }
            return false;
        },
        goPage: function(catid){
            var url = this.catel.url();
            window.open(url);
        },
        onEditClick: function(){
            NS.life(this.cfg['onEditClick'], this);
        },
        onCopyClick: function(){
            NS.life(this.cfg['onCopyClick'], this);
        },
        onRemoveClick: function(){
            NS.life(this.cfg['onRemoveClick'], this);
        },
        onSelectClick: function(){
            NS.life(this.cfg['onSelectClick'], this);
        },
        onSaveElement: function(){
            NS.life(this.cfg['onSaveElement'], this);
        },
        editorShow: function(){
            if (!L.isNull(this.editorWidget)){
                return;
            }
            var __self = this;
            this.editorWidget =
                new NS.ElementEditorWidget(this.gel('easyeditor'), this.manager, this.catel, {
                    'onCancelClick': function(wEditor){
                        __self.editorClose();
                    },
                    'onSaveElement': function(wEditor){
                        __self.editorClose();
                        __self.onSaveElement();
                    }
                });

            Dom.addClass(this.gel('wrap'), 'rborder');
            Dom.addClass(this.gel('id'), 'rowselect');
            this.elHide('menu');
        },
        editorClose: function(){
            if (L.isNull(this.editorWidget)){
                return;
            }

            Dom.removeClass(this.gel('wrap'), 'rborder');
            Dom.removeClass(this.gel('id'), 'rowselect');
            this.elShow('menu');

            this.editorWidget.destroy();
            this.editorWidget = null;
        }
    });
    NS.ElementRowWidget = ElementRowWidget;

    var ElementRemovePanel = function(manager, catel, callback){
        this.manager = manager;
        this.catel = catel;
        this.callback = callback;
        ElementRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(ElementRemovePanel, Brick.widget.Dialog, {
        initTemplate: function(){
            return buildTemplate(this, 'removepanel').replace('removepanel');
        },
        onClick: function(el){
            var tp = this._TId['removepanel'];
            switch (el.id) {
                case tp['bcancel']:
                    this.close();
                    return true;
                case tp['bremove']:
                    this.remove();
                    return true;
            }
            return false;
        },
        remove: function(){
            var TM = this._TM, gel = function(n){
                    return TM.getEl('removepanel.' + n);
                },
                __self = this;
            Dom.setStyle(gel('btns'), 'display', 'none');
            Dom.setStyle(gel('bloading'), 'display', '');
            this.manager.elementRemove(this.catel.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.ElementRemovePanel = ElementRemovePanel;

};