/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['typeview.js', 'typeeditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var TypeManagerWidget = function(container, manager, cfg){
        cfg = L.merge({}, cfg || {});

        TypeManagerWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, manager, cfg);
    };
    YAHOO.extend(TypeManagerWidget, BW, {
        init: function(manager, cfg){
            this.manager = manager;
            this.cfg = cfg;
            this.elTypeEditor = null;
            this.elTypeViewer = null;
        },
        onLoad: function(manager, cfg){
            var __self = this;
            this.typeListWidget = new NS.TypeListWidget(this.gel('list'), manager, {
                'onRowEditClick': function(w){
                    __self.showElTypeEditor(w.elType.id);
                },
                'onRowRemoveClick': function(w){
                    __self.showRemoveElTypePanel(w.elType.id);
                },
                'onRowSelect': function(w){
                    __self.showElTypeViewer(w.elType.id);
                }
            });
            this.typeListWidget.select(0);
        },
        render: function(){
            this.closeElTypeEditor();
            this.typeListWidget.render();
            this.elTypeViewer.render();
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['baddeltype']:
                    this.showElTypeEditor(0);
                    return true;
            }
            return false;
        },
        showRemoveElTypePanel: function(elTypeId){
            var elType = this.manager.typeList.get(elTypeId);
            if (!L.isValue(elType)){
                return;
            }

            var __self = this;

            new NS.TypeRemovePanel(this.manager, elType, function(){
                __self.typeListWidget.render();
            });
        },
        showElTypeEditor: function(elTypeId){
            if (L.isValue(this.elTypeEditor)){
                return;
            }

            var man = this.manager, __self = this;
            var elType = elTypeId == 0 ? man.newElementType() : man.typeList.get(elTypeId);
            this.elTypeEditor =
                new NS.TypeEditorWidget(this.gel('eltypeeditor'), man, elType, {
                    // 'fromElement': fel || null,
                    'onCancelClick': function(wEditor){
                        __self.closeElTypeEditor();
                    },
                    'onSave': function(wEditor){
                        __self.render();
                    }
                });
            this.elHide('eltypeviewer');
            this.elShow('eltypeeditor');
        },
        closeElTypeEditor: function(){
            if (!L.isValue(this.elTypeEditor)){
                return;
            }

            this.elTypeEditor.destroy();
            this.elTypeEditor = null;

            this.elShow('eltypeviewer');
            this.elHide('eltypeeditor');
        },
        showElTypeViewer: function(elTypeId){
            this.closeElTypeEditor();

            var elType = this.manager.typeList.get(elTypeId);

            if (!L.isValue(this.elTypeViewer)){
                this.elTypeViewer = new NS.TypeViewWidget(this.gel('eltypeviewer'), this.manager, elType);
            } else {
                this.elTypeViewer.setElType(elType);
            }

            this.elShow('eltypeviewer');
            this.elHide('eltypeeditor');
        }
    });
    NS.TypeManagerWidget = TypeManagerWidget;


    var TypeListWidget = function(container, manager, cfg){
        cfg = L.merge({
            'onRowEditClick': null,
            'onRowCopyClick': null,
            'onRowRemoveClick': null,
            'onRowSelect': null
        }, cfg || {});

        TypeListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'list'
        }, manager, cfg);
    };
    YAHOO.extend(TypeListWidget, BW, {
        init: function(manager, cfg){
            this.manager = manager;
            this.cfg = cfg;
            this.wsList = [];

            this._selectedid = 0;
        },
        destroy: function(){
            this.clearList();
            TypeListWidget.superclass.destroy.call(this);
        },
        clearList: function(){
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.elSetHTML('list', '');
        },
        render: function(){
            this.clearList();

            var elList = this.gel('list'), ws = this.wsList,
                __self = this, man = this.manager;

            man.typeList.foreach(function(elType){

                var div = document.createElement('div');
                div['elType'] = elType;

                elList.appendChild(div);
                var w = new NS.TypeRowWidget(div, __self.manager, elType, {
                    'onEditClick': function(w){
                        __self.onRowEditClick(w);
                    },
                    'onCopyClick': function(w){
                        __self.onRowCopyClick(w);
                    },
                    'onRemoveClick': function(w){
                        __self.onRowRemoveClick(w);
                    },
                    'onSelectClick': function(w){
                        __self.onRowSelectClick(w);
                    }
                });

                ws[ws.length] = w;
            });

            this._selectMethod(this._selectedid);
        },
        onRowEditClick: function(w){
            this._selectMethod(w.elType.id);
            NS.life(this.cfg['onRowEditClick'], w);
        },
        onRowCopyClick: function(w){
            NS.life(this.cfg['onRowCopyClick'], w);
        },
        onRowRemoveClick: function(w){
            NS.life(this.cfg['onRowRemoveClick'], w);
        },
        onRowSelectClick: function(w){
            this.select(w.elType.id);
        },
        foreach: function(f){
            if (!L.isFunction(f)){
                return;
            }
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                if (f(ws[i])){
                    return;
                }
            }
        },
        select: function(elTypeId){
            var w = this._selectMethod(elTypeId);
            NS.life(this.cfg['onRowSelect'], w);
        },
        _selectMethod: function(elTypeId){
            this._selectedid = elTypeId;
            var row = null;
            this.foreach(function(w){
                if (w.elType.id == elTypeId){
                    row = w;
                    w.select();
                } else {
                    w.unSelect();
                }
            });
            return row;
        }
    });
    NS.TypeListWidget = TypeListWidget;

    var TypeRowWidget = function(container, manager, elType, cfg){
        cfg = L.merge({
            'onEditClick': null,
            'onCopyClick': null,
            'onRemoveClick': null,
            'onSelectClick': null
        }, cfg || {});
        TypeRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, manager, elType, cfg);
    };
    YAHOO.extend(TypeRowWidget, BW, {
        init: function(manager, elType, cfg){
            this.manager = manager;
            this.elType = elType;
            this.cfg = cfg;
            this.editorWidget = null;
        },
        onLoad: function(manager, elType, cfg){
            this.elSetHTML({
                'tl': elType.title
            });
            if (elType.id == 0){
                this.elHide('menu');
            }
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bedit']:
                case tp['beditc']:
                    NS.life(this.cfg['onEditClick'], this);
                    return true;
                case tp['bcopy']:
                case tp['bcopyc']:
                    NS.life(this.cfg['onCopyClick'], this);
                    return true;
                case tp['bremove']:
                case tp['bremovec']:
                    NS.life(this.cfg['onRemoveClick'], this);
                    return true;
                case tp['dtl']:
                case tp['tl']:
                    NS.life(this.cfg['onSelectClick'], this);
                    return true;
            }
            return false;
        },
        select: function(){
            Dom.addClass(this.gel('wrap'), 'selected');
        },
        unSelect: function(){
            Dom.removeClass(this.gel('wrap'), 'selected');
        },
        isSelect: function(){
            return Dom.hasClass(this.gel('wrap'), 'selected');
        }
    });
    NS.TypeRowWidget = TypeRowWidget;

    var TypeRemovePanel = function(manager, elType, callback){
        this.manager = manager;
        this.elType = elType;
        this.callback = callback;
        TypeRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(TypeRemovePanel, Brick.widget.Dialog, {
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
            this.manager.elementTypeRemove(this.elType.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.TypeRemovePanel = TypeRemovePanel;

};