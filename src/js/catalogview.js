var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: 'widget', files: ['select.js']},
        {name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var CatalogViewWidget = function(container, manager, cat, cfg){
        cfg = L.merge({
            'addElementClick': null
        }, cfg || {});

        CatalogViewWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, manager, cat, cfg);
    };
    YAHOO.extend(CatalogViewWidget, BW, {
        init: function(manager, cat, cfg){
            this.manager = manager;
            this.cat = cat;
            this.cfg = cfg;

            this.editorWidget = null;
        },
        buildTData: function(manager, cat, cfg){
            return {
                'lngelementcount': manager.getLang('element.count'),
                'lngelementbuttonadd': manager.getLang('element.button.add')
            };
        },
        onLoad: function(manager, cat, cfg){
            this.setCatalog(cat);
        },
        setCatalog: function(cat){
            if (!L.isNull(cat.detail)){
                this._onLoadCatalog(cat);
            } else {
                var __self = this;
                manager.catalogLoad(cat.id, function(cat){
                    __self._onLoadCatalog(cat);
                });
            }
        },
        _onLoadCatalog: function(cat){
            this.cat = cat;

            this.elHide('loading');
            this.elShow('view');

            this.elSetHTML({
                'cattl': cat.title,
                'elcount': cat.elementCount
            });
            var roles = this.manager.roles;
            if (roles['isAdmin']){
                this.elShow('baddcat,beditcat,bremcat');
                if (cat.id == 0){
                    this.elHide('beditcat,bremcat');
                } else {
                    this.elShow('beditcat,bremcat');
                }
            }
            if (roles['isOperator']){
                this.elShow('mangroup');
            }
            this.closeEditor();
        },
        onAddElementClick: function(){
            NS.life(this.cfg['addElementClick'], this.cat);
        },
        showEditor: function(isnew){
            if (!L.isNull(this.editorWidget)){
                return;
            }

            var cat = this.cat, man = this.manager;

            if (isnew){
                cat = man.newCatalogItem({'pid': cat.id});
            } else {
                this.elHide('view');
            }
            this.elHide('mangroup');

            var __self = this;
            this.editorWidget = new NS.CatalogEditorWidget(this.gel('editor'), man, cat, {
                'onCancelClick': function(){
                    __self.closeEditor();
                },
                'onSaveCallback': function(cat){
                    __self.closeEditor();
                    if (!L.isNull(cat)){
                        __self.setCatalog(cat);
                    }
                }
            });
        },
        closeEditor: function(){
            if (L.isNull(this.editorWidget)){
                return;
            }

            this.elShow('view,mangroup');

            this.editorWidget.destroy();
            this.editorWidget = null;
        },
        showRemovePanel: function(){
            new CatalogRemovePanel(this.manager, this.cat, function(){
            });
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['baddel']:
                    this.onAddElementClick();
                    return true;
                case tp['baddcat']:
                    this.showEditor(true);
                    return true;
                case tp['beditcat']:
                    this.showEditor();
                    return true;
                case tp['bremcat']:
                    this.showRemovePanel();
                    return true;
            }
        }
    });
    NS.CatalogViewWidget = CatalogViewWidget;


    var CatalogEditorWidget = function(container, manager, cat, cfg){
        cfg = L.merge({
            'onSaveCallback': null,
            'onCancelClick': null
        }, cfg || {});
        CatalogEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'editor'
        }, manager, cat, cfg);
    };
    YAHOO.extend(CatalogEditorWidget, BW, {
        init: function(manager, cat, cfg){
            this.manager = manager;
            this.cat = cat;
            this.cfg = cfg;

            if (cat.id == 0){
                cat.detail = new NS.CatalogDetail();
            }
        },
        onLoad: function(manager, cat, cfg){
            var dtl = cat.detail;

            if (cat.id == 0){
                Dom.addClass(this.gel('wrap'), 'catisnew');
            } else {
                Dom.addClass(this.gel('wrap'), 'catisedit');
            }
            this.elShow('wrap');

            this.parentWidget = new NS.CatalogSelectWidget(this.gel('parent'), manager, {
                'cat': cat
            });

            this.elSetValue({
                'tl': cat.title,
                'ord': cat.order,
                'mtl': dtl.metaTitle,
                'mks': dtl.metaKeys,
                'mdsc': dtl.metaDesc
            });

            this.gel('mdsb').checked = cat.menuDisable > 0 ? 'checked' : '';
            this.gel('ldsb').checked = cat.listDisable > 0 ? 'checked' : '';

            this.fotosWidget = new NS.FotoListEditWidget(this.gel('fotos'), this.manager, this.cat.foto, {
                'limit': 1
            });

            var Editor = Brick.widget.Editor;
            this.editorWidget = new Editor(this.gel('text'), {
                'toolbar': Editor.TOOLBAR_STANDART,
                // 'mode': Editor.MODE_VISUAL,
                'toolbarExpert': false,
                'separateIntro': false
            });

            this.editorWidget.setContent(dtl.descript);

            var __self = this, keypress = function(e){
                if (e.keyCode != 13){
                    return false;
                }
                __self.save();
                return true;
            };
            E.on(this.gel('tl'), 'keypress', keypress);
            E.on(this.gel('ord'), 'keypress', keypress);
            E.on(this.gel('mtl'), 'keypress', keypress);
            E.on(this.gel('mks'), 'keypress', keypress);
            E.on(this.gel('mdsc'), 'keypress', keypress);

            var elTitle = this.gel('tl');
            setTimeout(function(){
                try {
                    elTitle.focus();
                } catch (e) {
                }
            }, 100);
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['badd']:
                case tp['baddc']:
                case tp['bsave']:
                case tp['bsavec']:
                    this.save();
                    return true;
                case tp['bcancel']:
                case tp['bcancelc']:
                    this.onCancelClick();
                    return true;
            }
            return false;
        },
        onCancelClick: function(){
            NS.life(this.cfg['onCancelClick']);
        },
        save: function(){

            var foto = '';
            var fotos = this.fotosWidget.fotos;
            if (fotos.length > 0){
                foto = fotos[fotos.length - 1];
            }

            var sd = {
                'pid': this.parentWidget.getValue(),
                'tl': this.gel('tl').value,
                'dsc': this.editorWidget.getContent(),
                'foto': foto,
                'mdsb': this.gel('mdsb').checked ? 1 : 0,
                'ldsb': this.gel('ldsb').checked ? 1 : 0,
                'ord': this.gel('ord').value,
                'mtl': this.gel('mtl').value,
                'mks': this.gel('mks').value,
                'mdsc': this.gel('mdsc').value
            };

            this.elHide('btnsc');
            this.elShow('btnpc');

            var __self = this, cfg = this.cfg;
            this.manager.catalogSave(this.cat.id, sd, function(cat){
                __self.elShow('btnsc');
                __self.elHide('btnpc');

                NS.life(cfg['onSaveCallback'], cat);
            }, this.cat);
        }
    });
    NS.CatalogEditorWidget = CatalogEditorWidget;

    var CatalogSelectWidget = function(container, manager, cfg){
        cfg = L.merge({
            'cat': null
        }, cfg || {});

        if (L.isValue(cfg['cat'])){
            cfg['value'] = cfg['cat'].parentid;
        }

        var rootItem = manager.catalogList.get(0);
        var list = new NS.DictList();

        var fetchList = function(catList, level){
            catList.foreach(function(cat){
                if (L.isValue(cfg['cat']) && cfg['cat'].id == cat.id){
                    return;
                }

                var tl = cat.title;

                for (var i = 0; i < level; i++){
                    tl = " - " + tl;
                }

                list.add(new NS.Dict({
                    'id': cat.id,
                    'tl': tl
                }));

                fetchList(cat.childs, level + 1);
            });
        };
        fetchList(rootItem.childs, 0);

        CatalogSelectWidget.superclass.constructor.call(this, container, list, cfg);
    };
    YAHOO.extend(CatalogSelectWidget, Brick.mod.widget.SelectWidget, {});
    NS.CatalogSelectWidget = CatalogSelectWidget;

    var CatalogRemovePanel = function(manager, cat, callback){
        this.manager = manager;
        this.cat = cat;
        this.callback = callback;
        CatalogRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(CatalogRemovePanel, Brick.widget.Dialog, {
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
            this.manager.catalogRemove(this.cat.id, function(){
                __self.close();
                NS.life(__self.callback);
            });
        }
    });
    NS.CatalogRemovePanel = CatalogRemovePanel;

};