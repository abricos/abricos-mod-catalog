var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['catalogExplore.js', 'catalogView.js', 'elementList.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.CatalogManagerWidget = Y.Base.create('catalogManagerWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template;

            var treeWidget = this.treeWidget = new NS.CatalogTreeWidget({
                srcNode: tp.gel('explore'),
                appInstance: appInstance
            });

            treeWidget.on('selectedItemEvent', this.onSelectedCatalogItem, this);
            treeWidget.on('addChildClickEvent', this.onAddChildClickCatalogItem, this);
            treeWidget.on('editClickEvent', this.onEditClickCatalogItem, this);

            /*
             this.showCatalogViewWidget(cfg['catid']);

             man.catalogChangedEvent.subscribe(this.onCatalogChanged, this, true);
             man.catalogCreatedEvent.subscribe(this.onCatalogCreated, this, true);
             man.catalogRemovedEvent.subscribe(this.onCatalogRemoved, this, true);
             /**/
        },
        destructor: function(){
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            catalogid: {value: 0}
        }
    });

    return;

    YAHOO.extend(CatalogManagerWidget, Brick.mod.widget.Widget, {
        onLoad: function(man, cfg){
            this.elHide('loading');

        },
        onCatalogChanged: function(){
            this.treeWidget.render();
        },
        onCatalogCreated: function(evt, prms){
            var catid = prms[0];
            this.treeWidget.render();
            this.treeWidget.selectItem(catid);
        },
        onCatalogRemoved: function(evt, prms){
            this.treeWidget.render();
            this.treeWidget.selectItem(0);
        },
        onAddChildClickCatalogItem: function(evt, prms){
            var cat = prms[0], __self = this;

            this.showCatalogViewWidget(cat.id, function(){
                __self.catViewWidget.showEditor(true);
            });
        },
        onEditClickCatalogItem: function(evt, prms){
            var cat = prms[0], __self = this;
            this.showCatalogViewWidget(cat.id, function(){
                __self.catViewWidget.showEditor();
            });
        },
        onSelectedCatalogItem: function(evt, prms){
            var cat = prms[0];
            this.showCatalogViewWidget(cat.id);
        },
        showCatalogViewWidget: function(catid, callback){
            this.elShow('colloading');
            this.elHide('colview');
            var __self = this;
            this.manager.catalogLoad(catid, function(cat, elList){
                __self._onLoadCatalogDetail(cat, elList);
                NS.life(callback);
            }, {'elementlist': true});
        },
        _onLoadCatalogDetail: function(cat, elList){
            this.elHide('colloading');
            this.elShow('colview');

            var __self = this;
            if (L.isNull(this.catViewWidget)){
                this.catViewWidget = new NS.CatalogViewWidget(this.gel('catview'), this.manager, cat, {
                    'addElementClick': function(){
                        __self.elementListWidget.showNewEditor();
                    }
                });
            } else {
                this.catViewWidget.setCatalog(cat);
            }

            if (L.isNull(this.elementListWidget)){
                this.elementListWidget = new NS.ElementListWidget(this.gel('ellist'), this.manager, elList);
            } else {
                this.elementListWidget.setList(elList);
            }
        }
    });
    NS.CatalogManagerWidget = CatalogManagerWidget;
};