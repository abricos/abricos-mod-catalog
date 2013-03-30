/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'catalog', files: ['catalogexplore.js', 'catalogview.js', 'elementlist.js']},
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		LNG = this.language,
		buildTemplate = this.buildTemplate;
	
	var CatalogManagerWidget = function(container, manager){
		CatalogManagerWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager);
	};
	YAHOO.extend(CatalogManagerWidget, Brick.mod.widget.Widget, {
		init: function(manager){
			this.manager = manager;
			this.treeWidget = null;
			this.catViewWidget = null;
			this.elementListWidget = null;
		},
		destroy: function(){
			if (!L.isNull(this.treeWidget)){
				this.treeWidget.destroy();
			}
			if (!L.isNull(this.catViewWidget)){
				this.catViewWidget.destroy();
			}
			if (!L.isNull(this.elementListWidget)){
				this.elementListWidget.destroy();
			}

			var man = this.manager;
			man.catalogCreatedEvent.unsubscribe(this.onCatalogCreated);
			man.catalogChangedEvent.unsubscribe(this.onCatalogChanged);
			man.catalogRemovedEvent.unsubscribe(this.onCatalogRemoved);
			
			CatalogManagerWidget.superclass.destroy.call(this);
		},
		onLoad: function(man){
			this.elHide('loading');
			this.treeWidget = new NS.CatalogTreeWidget(this.gel('explore'), man.catalogList);
			this.treeWidget.selectedItemEvent.subscribe(this.onSelectedCatalogItem, this, true);
			this.treeWidget.addChildClickEvent.subscribe(this.onAddChildClickCatalogItem, this, true);
			this.treeWidget.editClickEvent.subscribe(this.onEditClickCatalogItem, this, true);
			
			this.showCatalogViewWidget(0);
			
			man.catalogChangedEvent.subscribe(this.onCatalogChanged, this, true);
			man.catalogCreatedEvent.subscribe(this.onCatalogCreated, this, true);
			man.catalogRemovedEvent.subscribe(this.onCatalogRemoved, this, true);
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
			}else{
				this.catViewWidget.setCatalog(cat);
			}

			if (L.isNull(this.elementListWidget)){
				this.elementListWidget = new NS.ElementListWidget(this.gel('ellist'), this.manager, elList);
			}else{
				this.elementListWidget.setList(elList);
			}
		}
	});
	NS.CatalogManagerWidget = CatalogManagerWidget;
};