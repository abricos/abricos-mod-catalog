/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var CatalogViewWidget = function(container, manager, cat, cfg){
		cfg = L.merge({
			'addElementClick': null,
			'addCatalogClick': null
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
			this.cat = cat;
			
			this.elSetHTML({
				'cattl': cat.title,
				'elcount': cat.elementCount
			});
			
			if (cat.id == 0){
				this.elHide('beditcat,bremcat');
			}else{
				this.elShow('beditcat,bremcat');
			}
			
			this.elShow('mangroup');
		},
		onAddElementClick: function(){
			NS.life(this.cfg['addElementClick'], this.cat);
		},
		onAddCatalogClick: function(){
			NS.life(this.cfg['addCatalogClick'], this.cat);
		},
		showEditor: function(){
			this.elHide('view');
			var __self = this;
			this.editorWidget = new NS.CatalogEditorWidget(this.gel('editor'), this.manager, this.cat, {
				'onCancelClick': function(){
					__self.elHide('view');
				},
				'onSaveCallback': function(cat){
					__self.elHide('view');
					__self.setCatalog(cat);
				}
			});
		},
		onRemoveCatalogClick: function(){},
		onClick: function(el, tp){
			switch(el.id){
			case tp['baddel']: this.onAddElementClick(); return true;
			case tp['baddcat']: this.onAddCatalogClick(); return true;
			}
		}
	});
	NS.CatalogViewWidget = CatalogViewWidget;
	
	
	var CatalogEditorWidget = function(container, manager, cat, cfg){
		cfg = L.merge({
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
		},
		onLoad: function(manager, cat, cfg){
			
			this.elSetValue({
				'tl': cat.title
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.onCancelClick(); return true;
			}
			return false;
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		save: function(){
			var sd = {
				'tl': this.gel('tl').value,
				'fotos': this.fotosWidget.fotos
			};

			this.elHide('btnsc');
			this.elShow('btnpc');

			var __self = this;
			this.manager.catSave(this.cat.id, sd, function(cat){
				__self.elShow('btnsc');
				__self.elHide('btnpc');
			}, this.cat);
		}
	});
	NS.CatalogEditorWidget = CatalogEditorWidget;	
};