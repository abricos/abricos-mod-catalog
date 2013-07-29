/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['typeeditor.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var TypeManagerWidget = function(container, manager, cfg){
		cfg = L.merge({ }, cfg || {});

		TypeManagerWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, cfg);
	};
	YAHOO.extend(TypeManagerWidget, BW, {
		init: function(manager, cfg){
			this.manager = manager;
			this.config = cfg;
		},
		onLoad: function(manager, cfg){
			this.typeListWidget = new NS.TypeListWidget(this.gel('list'), manager);
		}
	});
	NS.TypeManagerWidget = TypeManagerWidget;

	
	var TypeListWidget = function(container, manager, cfg){
		cfg = L.merge({
		}, cfg || {});
		
		TypeListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'list' 
		}, manager, cfg);
	};
	YAHOO.extend(TypeListWidget, BW, {
		init: function(manager, cfg){
			this.manager = manager;
			this.list = manager.typeList;
			this.config = cfg;
			this.wsList = [];
			
			this.newEditorWidget = null;
		},
		destroy: function(){
			this.clearList();
			TypeListWidget.superclass.destroy.call(this);			
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
			this.allEditorClose();
			this.render();
		},
		render: function(){
			this.clearList();
			
			var elList = this.gel('list'), ws = this.wsList, 
				__self = this, man = this.manager;
			
			man.typeList.foreach(function(tp){
				
				var div = document.createElement('div');
				div['elType'] = tp;
				
				elList.appendChild(div);
				var w = new NS.TypeRowWidget(div, __self.manager, tp, {
					'onEditClick': function(w){__self.onElementEditClick(w);},
					'onCopyClick': function(w){__self.onElementCopyClick(w);},
					'onRemoveClick': function(w){__self.onElementRemoveClick(w);},
					'onSelectClick': function(w){__self.onElementSelectClick(w);},
					'onSaveElement': function(w){ __self.render(); }
				});
				
				ws[ws.length] = w;
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
		onElementCopyClick: function(w){
			this.showNewEditor(w.tp);
		},
		onElementRemoveClick: function(w){
			var __self = this;
			new TypeRemovePanel(this.manager, w.tp, function(list){
				__self.list.remove(w.tp.id);
				__self.render();
			});
		},
		onElementSelectClick: function(w){
			this.allEditorClose(w);
			// w.editorShow();
		},
		showNewEditor: function(fel){
			if (!L.isNull(this.newEditorWidget)){ return; }
			
			this.allEditorClose();
			var man = this.manager, __self = this;
			var tp = man.newElement({'catid': this.list.catid});

			this.newEditorWidget = 
				new NS.TypeEditorWidget(this.gel('neweditor'), man, tp, {
					'fromElement': fel || null,
					'onCancelClick': function(wEditor){ __self.newEditorClose(); },
					'onSaveElement': function(wEditor, element){
						if (!L.isNull(element)){
							__self.list.add(element);
						}
						__self.newEditorClose(); 
						__self.render();
					}
				});
		},
		newEditorClose: function(){
			if (L.isNull(this.newEditorWidget)){ return; }
			this.newEditorWidget.destroy();
			this.newEditorWidget = null;
		}
	});
	NS.TypeListWidget = TypeListWidget;

	var TypeRowWidget = function(container, manager, tp, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onCopyClick': null,
			'onRemoveClick': null,
			'onSelectClick': null,
			'onSaveElement': null
		}, cfg || {});
		TypeRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, manager, tp, cfg);
	};
	YAHOO.extend(TypeRowWidget, BW, {
		init: function(manager, tp, cfg){
			this.manager = manager;
			this.tp = tp;
			this.cfg = cfg;
			this.editorWidget = null;
		},
		onLoad: function(manager, tp){
			this.elSetHTML({
				'tl': tp.title
			});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bedit']: case tp['beditc']:
				this.onEditClick();
				return true;
			case tp['bcopy']: case tp['bcopyc']:
				this.onCopyClick();
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
		onCopyClick: function(){
			NS.life(this.cfg['onCopyClick'], this);
		},
		onRemoveClick: function(){
			NS.life(this.cfg['onRemoveClick'], this);
		},
		onSelectClick: function(){
			NS.life(this.cfg['onSelectClick'], this);
		},
		onSaveElement: function(){
			NS.life(this.cfg['onSaveElement'], this);
		},
		editorShow: function(){
			if (!L.isNull(this.editorWidget)){ return; }
			var __self = this;
			this.editorWidget = 
				new NS.TypeEditorWidget(this.gel('easyeditor'), this.manager, this.tp, {
					'onCancelClick': function(wEditor){ __self.editorClose(); },
					'onSaveElement': function(wEditor){ 
						__self.editorClose(); 
						__self.onSaveElement();
					}
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
	NS.TypeRowWidget = TypeRowWidget;	

	var TypeRemovePanel = function(manager, tp, callback){
		this.manager = manager;
		this.tp = tp;
		this.callback = callback;
		TypeRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(TypeRemovePanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'removepanel').replace('removepanel');
		},
		onClick: function(el){
			var tp = this._TId['removepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bremove']: this.remove(); return true;
			}
			return false;
		},
		remove: function(){
			var TM = this._TM, gel = function(n){ return  TM.getEl('removepanel.'+n); },
				__self = this;
			Dom.setStyle(gel('btns'), 'display', 'none');
			Dom.setStyle(gel('bloading'), 'display', '');
			this.manager.elementRemove(this.tp.id, function(){
				__self.close();
				NS.life(__self.callback);
			});
		}
	});
	NS.TypeRemovePanel = TypeRemovePanel;

};