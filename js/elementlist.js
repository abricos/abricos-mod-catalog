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
	
	var ElementListWidget = function(container, manager, list, cfg){
		
		cfg = L.merge({
		}, cfg || {});
		
		ElementListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, list, cfg);
	};
	YAHOO.extend(ElementListWidget, BW, {
		init: function(manager, list, cfg){
			this.manager = manager;
			this.list = list;
			this.config = cfg;
			this.wsList = [];
		},
		destroy: function(){
			this.clearList();
			ElementListWidget.superclass.destroy.call(this);			
		},
		clearList: function(){
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.elSetHTML('list', '');
		},
		setList: function(list){
			this.list = list;
			this.render();
		},
		render: function(){
			this.clearList();
			
			var elList = this.gel('list'), ws = this.wsList, 
				__self = this;

			this.list.foreach(function(element){
				var div = document.createElement('div');
				elList.appendChild(div);
				ws[ws.length] = new NS.ElementRowWidget(div, __self.manager, element, {
					'onEditClick': function(w){__self.onElementEditClick(w);},
					'onRemoveClick': function(w){__self.onElementRemoveClick(w);},
					'onSelectClick': function(w){__self.onElementSelectClick(w);}
				});
			});
		},
		foreach: function(f){
			if (!L.isFunction(f)){ return; }
			var ws = this.wsList;
			for (var i=0;i<ws.length;i++){
				if (f(ws[i])){ return; }
			}
		},
		allEasyEditorClose: function(wExclude){
			this.foreach(function(w){
				if (w != wExclude){
					w.editorClose();
				}
			});
		},
		onElementEditClick: function(w){
			this.allEasyEditorClose(w);
			w.editorShow();
		},
		onElementRemoveClick: function(w){
		},
		onElementSelectClick: function(w){
			this.allEasyEditorClose(w);
			w.editorShow();
		}
	});
	NS.ElementListWidget = ElementListWidget;
	
	var ElementRowWidget = function(container, manager, element, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onRemoveClick': null,
			'onSelectClick': null
		}, cfg || {});
		ElementRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, manager, element, cfg);
	};
	YAHOO.extend(ElementRowWidget, BW, {
		init: function(manager, element, cfg){
			this.manager = manager;
			this.element = element;
			this.cfg = cfg;
			this.editorWidget = null;
		},
		onLoad: function(manager, element){
			this.elSetHTML({
				'tl': element.title
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bedit']: case tp['beditc']:
				this.onEditClick();
				return true;
			case tp['bremove']: case tp['bremovec']:
				this.onRemoveClick();
				return true;
			case tp['dtl']: case tp['tl']:
				this.onSelectClick();
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
		onSelectClick: function(){
			NS.life(this.cfg['onSelectClick'], this);
		},
		editorShow: function(){
			if (!L.isNull(this.editorWidget)){ return; }
			var __self = this;
			this.editorWidget = 
				new NS.ElementEasyEditRowWidget(this.gel('easyeditor'), this.manager, this.element, {
					'onSaveClick': function(wEditor, saveData){
						__self.onEditorSaveClick(wEditor, saveData);
					},
					'onCancelClick': function(wEditor){ __self.editorClose(); }
				});
			
			Dom.addClass(this.gel('wrap'), 'rborder');
		},
		editorClose: function(){
			if (L.isNull(this.editorWidget)){ return; }

			Dom.removeClass(this.gel('wrap'), 'rborder');
			this.editorWidget.destroy();
			this.editorWidget = null;
		}
	});
	NS.ElementRowWidget = ElementRowWidget;	
	
	var ElementImage50Widget = function(container, fh, cfg){
		cfg = L.merge({
			'onRemoveClick': null
		}, cfg || {});
		ElementImage50Widget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'foto', 'isRowWidget': true 
		}, fh, cfg);
	};
	YAHOO.extend(ElementImage50Widget, BW, {
		buildTData: function(fh){
			return {'fh': fh};
		}
	});
	NS.ElementImage50Widget = ElementImage50Widget;
	
	var ElementEasyEditRowWidget = function(container, manager, element, cfg){
		cfg = L.merge({
			'onSaveClick': null,
			'onCancelClick': null
		}, cfg || {});
		ElementEasyEditRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'easyeditor,nofoto' 
		}, manager, element, cfg);
	};
	YAHOO.extend(ElementEasyEditRowWidget, BW, {
		init: function(manager, element, cfg){
			this.manager = manager;
			this.element = element;
			this.cfg = cfg;
			this.uploadWindow = null;
			this.wsFotos = [];
		},
		onLoad: function(manager, element){
			if (!L.isNull(element.detail)){
				this._onLoadElement(element);
			}else{
				var __self = this;
				manager.elementLoad(element.id, function(element){
					__self._onLoadElement(element);
				}, element);
			}
		},
		_onLoadElement: function(element){
			this.elHide('loading');
			this.elShow('view');
			this.elSetValue({
				'tl': element.title
			});
			
			this.renderFotos();
		},
		clearFotos: function(){
			var ws = this.wsFotos;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.wsFotos = [];
		},
		renderFotos: function(){
			this.clearFotos();
			
			var fotos = this.element.detail.fotos;
			if (fotos.length == 0){
				this.elSetHTML('fotolist', this._TM.replace('nofoto'));
			}else{
				this.elSetHTML('fotolist', '');
			}
			
			var ws = [];
			for (var i=0;i<fotos.length;i++){
				ws[ws.length] = new NS.ElementImage50Widget(this.gel('fotolist'), fotos[i]);
			}
			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}
			this.wsFotos = ws;
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['baddfotos']: this.imageUploadShow(); return true;
			case tp['bsave']: this.onSaveClick(); return true;
			case tp['bcancel']: this.onCancelClick(); return true;
			}
			return false;
		},
		onSaveClick: function(){
			NS.life(this.cfg['onSaveClick'], this);
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		imageUploadShow: function(wEditor){
			
			NS.uploadActiveImageList = this;
			
			var man = this.manager;
			
			if (!L.isNull(this.uploadWindow) && !this.uploadWindow.closed){
				this.uploadWindow.focus();
				return;
			}
			var url = '/catalogbase/uploadelementimg/'+man.modname+'/';
			this.uploadWindow = window.open(
				url, 'catalogimage',	
				'statusbar=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=550,height=500' 
			);
			NS.activeImageList = this;
		},
		imageAdd: function(imgs){
			Brick.console(imgs);
		}
	});
	NS.ElementEasyEditRowWidget = ElementEasyEditRowWidget;
};