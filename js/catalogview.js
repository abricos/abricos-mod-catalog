/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['editor.js']},
		{name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
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
			}else{
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
			
			if (cat.id == 0){
				this.elHide('beditcat,bremcat');
			}else{
				this.elShow('beditcat,bremcat');
			}
			
			this.elShow('mangroup');
			this.closeEditor();
		},
		onAddElementClick: function(){
			NS.life(this.cfg['addElementClick'], this.cat);
		},
		showEditor: function(isnew){
			if (!L.isNull(this.editorWidget)){ return; }
			
			var cat = this.cat;
			
			if (isnew){
				cat = new NS.CatalogItem({'pid': cat.id});
			}else{
				this.elHide('view');
			}
			this.elHide('mangroup');
			
			var __self = this;
			this.editorWidget = new NS.CatalogEditorWidget(this.gel('editor'), this.manager, cat, {
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
			if (L.isNull(this.editorWidget)){ return; }

			this.elShow('view,mangroup');
			
			this.editorWidget.destroy();
			this.editorWidget = null;
		},
		onRemoveCatalogClick: function(){},
		onClick: function(el, tp){
			switch(el.id){
			case tp['baddel']: this.onAddElementClick(); return true;
			case tp['baddcat']: this.showEditor(true); return true;
			case tp['beditcat']: this.showEditor(); return true;
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
			}else{
				Dom.addClass(this.gel('wrap'), 'catisedit');
			}
			this.elShow('wrap');
			
			this.elSetValue({
				'tl': cat.title,
				'ord': cat.order,
				'mtl': dtl.metaTitle,
				'mks': dtl.metaKeys,
				'mdsc': dtl.metaDesc
			});
			
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
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['badd']: case tp['baddc']: 
			case tp['bsave']: case tp['bsavec']: 
				this.save(); return true;
			case tp['bcancel']: case tp['bcancelc']:
				this.onCancelClick(); return true;
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
				foto = fotos[fotos.length-1];
			}
			
			var sd = {
				'pid': this.cat.parentid, 
				'tl': this.gel('tl').value,
				'dsc': this.editorWidget.getContent(),
				'foto': foto,
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
};