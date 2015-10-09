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
            appInstance.catalogList(function(){
                this.onLoadCatalogList();
            }, this);
        },
        destructor: function(){
            if (this.treeWidget){
                treeWidget.destroy();
            }
        },
        onLoadCatalogList: function(){
            var tp = this.template,
                appInstance = this.get('appInstance'),
                catalogid = this.get('catalogid');

            var treeWidget = this.treeWidget = new NS.CatalogTreeWidget({
                appInstance: appInstance,
                srcNode: tp.gel('explore')
            });

            treeWidget.on('selectedItemEvent', this.onSelectedCatalogItem, this);
            treeWidget.on('addChildClickEvent', this.onAddChildClickCatalogItem, this);
            treeWidget.on('editClickEvent', this.onEditClickCatalogItem, this);

            this.elementListWidget = new NS.ElementListWidget({
                appInstance: appInstance,
                srcNode: tp.gel('ellist'),
                catalogid: catalogid

            });

            /*
             this.catalogViewWidget = new NS.CatalogViewWidget(this.gel('catview'), this.manager, cat, {
             'addElementClick': function(){
             __self.elementListWidget.showNewEditor();
             }
             });



             // this.showCatalogViewWidget(this.get('catalogid'));

             /*

             man.catalogChangedEvent.subscribe(this.onCatalogChanged, this, true);
             man.catalogCreatedEvent.subscribe(this.onCatalogCreated, this, true);
             man.catalogRemovedEvent.subscribe(this.onCatalogRemoved, this, true);
             /**/

        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            catalogid: {value: 0}
        }
    });

    return; ////////// TODO: remove old functions

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

    });
    NS.CatalogManagerWidget = CatalogManagerWidget;
};