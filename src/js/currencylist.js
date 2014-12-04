/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['currencyeditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    var Dom = YAHOO.util.Dom,
        L = Y.Lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var CurrencyListWidget = function(container, manager, cfg){
        cfg = Y.merge({}, cfg || {});

        CurrencyListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, manager, cfg);
    };
    YAHOO.extend(CurrencyListWidget, BW, {
        init: function(manager, cfg){
            this.manager = manager;
            this.list = manager.currencyList;
            this.config = cfg;
            this.wsList = [];

            this.newEditorWidget = null;
        },
        destroy: function(){
            this.clearList();
            CurrencyListWidget.superclass.destroy.call(this);
        },
        clearList: function(){
            var ws = this.wsList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.elSetHTML('list', '');
        },
        setList: function(list){
            this.list = list;
            this.allEditorClose();
            this.render();
        },
        render: function(){
            this.clearList();

            var elList = this.gel('list'), ws = this.wsList,
                __self = this;

            var list = this.list;

            list.foreach(function(currency){

                var div = document.createElement('div');
                div['currency'] = currency;

                elList.appendChild(div);
                var w = new NS.CurrencyRowWidget(div, __self.manager, currency, {
                    'onEditClick': function(w){
                        __self.onElementEditClick(w);
                    },
                    'onRemoveClick': function(w){
                        __self.onElementRemoveClick(w);
                    },
                    'onSave': function(w){
                        __self.render(true);
                    }
                });

                ws[ws.length] = w;
            });
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
        onElementRemoveClick: function(w){
            var __self = this;
            new CurrencyRemovePanel(this.manager, w.currency, function(list){
                __self.list.remove(w.currency.id);
                __self.render();
            });
        },
        showNewEditor: function(){
            if (!L.isNull(this.newEditorWidget)){
                return;
            }

            this.allEditorClose();
            var man = this.manager, __self = this;
            var currency = man.newElementCurrency();

            this.newEditorWidget =
                new NS.CurrencyEditorWidget(this.gel('neweditor'), man, currency, {
                    'onCancelClick': function(wEditor){
                        __self.newEditorClose();
                    },
                    'onSave': function(wEditor){
                        __self.newEditorClose();
                        __self.render(true);
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
    NS.CurrencyListWidget = CurrencyListWidget;

    var CurrencyRowWidget = function(container, manager, currency, cfg){
        cfg = Y.merge({
            'onEditClick': null,
            'onRemoveClick': null,
            'onSave': null
        }, cfg || {});
        CurrencyRowWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'row'
        }, manager, currency, cfg);
    };
    YAHOO.extend(CurrencyRowWidget, BW, {
        init: function(manager, currency, cfg){
            this.manager = manager;
            this.currency = currency;
            this.cfg = cfg;
            this.editorWidget = null;
        },
        onLoad: function(manager, currency){
            this.elSetHTML({
                'tl': currency.title
            });
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bedit']:
                case tp['beditc']:
                    this.onEditClick();
                    return true;
                case tp['bremove']:
                case tp['bremovec']:
                    this.onRemoveClick();
                    return true;
            }
            return false;
        },
        onEditClick: function(){
            NS.life(this.cfg['onEditClick'], this);
        },
        onRemoveClick: function(){
            NS.life(this.cfg['onRemoveClick'], this);
        },
        onSave: function(){
            NS.life(this.cfg['onSave'], this);
        },
        editorShow: function(){
            if (!L.isNull(this.editorWidget)){
                return;
            }
            var __self = this;
            this.editorWidget =
                new NS.CurrencyEditorWidget(this.gel('easyeditor'), this.manager, this.currency, {
                    'onCancelClick': function(wEditor){
                        __self.editorClose();
                    },
                    'onSave': function(wEditor){
                        __self.editorClose();
                        __self.onSave();
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
    NS.CurrencyRowWidget = CurrencyRowWidget;

    var CurrencyRemovePanel = function(manager, currency, callback){
        this.manager = manager;
        this.currency = currency;
        this.callback = callback;
        CurrencyRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(CurrencyRemovePanel, Brick.widget.Dialog, {
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
            this.manager.currencyRemove(this.currency.typeid, this.currency.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.CurrencyRemovePanel = CurrencyRemovePanel;

};