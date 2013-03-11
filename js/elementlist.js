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
	
	var ElementListWidget = function(container, list, cfg){
		
		cfg = L.merge({
		}, cfg || {});
		
		ElementListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, list, cfg);
	};
	YAHOO.extend(ElementListWidget, BW, {
		init: function(list, cfg){
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
				ws[ws.length] = new NS.ElementRowWidget(div, element, {
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
					w.easyEditorClose();
				}
			});
		},
		onElementEditClick: function(w){
		},
		onElementRemoveClick: function(w){
		},
		onElementSelectClick: function(w){
			this.allEasyEditorClose(w);
			w.easyEditorShow();
		}
	});
	NS.ElementListWidget = ElementListWidget;
	
	var ElementRowWidget = function(container, element, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onRemoveClick': null,
			'onSelectClick': null
		}, cfg || {});
		ElementRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, element, cfg);
	};
	YAHOO.extend(ElementRowWidget, Brick.mod.widget.Widget, {
		init: function(element, cfg){
			this.element = element;
			this.cfg = cfg;
			this.easyEditorWidget = null;
		},
		onLoad: function(element){
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
		easyEditorShow: function(){
			if (!L.isNull(this.easyEditorWidget)){ return; }
			this.easyEditorWidget = 
				new NS.ElementEasyEditRowWidget(this.gel('easyeditor'), this.element);
			
			Dom.addClass(this.gel('wrap'), 'rborder');
		},
		easyEditorClose: function(){
			if (L.isNull(this.easyEditorWidget)){ return; }

			Dom.removeClass(this.gel('wrap'), 'rborder');
			this.easyEditorWidget.destroy();
			this.easyEditorWidget = null;
		}
	});
	NS.ElementRowWidget = ElementRowWidget;	
	
	var ElementEasyEditRowWidget = function(container, element, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onRemoveClick': null,
			'onSelectClick': null
		}, cfg || {});
		ElementEasyEditRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'easyeditor' 
		}, element, cfg);
	};
	YAHOO.extend(ElementEasyEditRowWidget, Brick.mod.widget.Widget, {
		init: function(element, cfg){
			this.element = element;
			this.cfg = cfg;
		},
		onLoad: function(element){
			this.elSetValue({
				'tl': element.title
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			}
			return false;
		}
	});
	NS.ElementEasyEditRowWidget = ElementEasyEditRowWidget;
};