/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['elementeditor.js']}
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
			
			this.newEditorWidget = null;
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
		},
		onElementSelectClick: function(w){
			this.allEditorClose(w);
			w.editorShow();
		},
		showNewEditor: function(){
			if (!L.isNull(this.newEditorWidget)){ return; }
			
			this.allEditorClose();
			
			var element = new NS.Element({'catid': this.list.catid});
			this.newEditorWidget = 
				new NS.ElementEditorWidget(this.gel('neweditor'), this.manager, element, {
					'onCancelClick': function(wEditor){ __self.newEditorClose(); }
				});
		},
		newEditorClose: function(){
			if (L.isNull(this.newEditorWidget)){ return; }
			this.newEditorWidget.destroy();
			this.newEditorWidget = null;
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
				new NS.ElementEditorWidget(this.gel('easyeditor'), this.manager, this.element, {
					'onCancelClick': function(wEditor){ __self.editorClose(); }
				});
			
			Dom.addClass(this.gel('wrap'), 'rborder');
			Dom.addClass(this.gel('id'), 'rowselect');
			this.elHide('menu');
		},
		editorClose: function(){
			if (L.isNull(this.editorWidget)){ return; }

			Dom.removeClass(this.gel('wrap'), 'rborder');
			Dom.removeClass(this.gel('id'), 'rowselect');
			this.elShow('menu');

			this.editorWidget.destroy();
			this.editorWidget = null;
		}
	});
	NS.ElementRowWidget = ElementRowWidget;	

};