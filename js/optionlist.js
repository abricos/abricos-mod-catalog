/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['optioneditor.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var OptionListWidget = function(container, manager, list, cfg){
		
		cfg = L.merge({
		}, cfg || {});
		
		OptionListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, list, cfg);
	};
	YAHOO.extend(OptionListWidget, BW, {
		init: function(manager, list, cfg){
			this.manager = manager;
			this.list = list;
			this.config = cfg;
			this.wsList = [];
			
			this.newEditorWidget = null;
		},
		destroy: function(){
			this.clearList();
			OptionListWidget.superclass.destroy.call(this);			
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
		render: function(isListUpdate){
			if (isListUpdate){
				this.list = this.manager.typeList.get(this.list.elTypeId).optionList;
			}
			
			this.clearList();

			var elList = this.gel('list'), ws = this.wsList, 
				__self = this;
			
			var list = this.list;

			list.foreach(function(option){
				if (list.catid != option.catid){ return; }
				
				var div = document.createElement('div');
				div['catalogElement'] = option;
				
				elList.appendChild(div);
				var w = new NS.OptionRowWidget(div, __self.manager, option, {
					'onEditClick': function(w){__self.onElementEditClick(w);},
					'onCopyClick': function(w){__self.onElementCopyClick(w);},
					'onRemoveClick': function(w){__self.onElementRemoveClick(w);},
					'onSelectClick': function(w){__self.onElementSelectClick(w);},
					'onSave': function(w){ 
						__self.render(true); 
					}
				});
				
				ws[ws.length] = w;
			}, 'order', true);
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
			this.showNewEditor(w.option);
		},
		onElementRemoveClick: function(w){
			var __self = this;
			new OptionRemovePanel(this.manager, w.option, function(list){
				__self.list.remove(w.option.id);
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
			var man = this.manager, __self = this, elTypeId = this.list.elTypeId;
			var option = man.newElementOption({'tpid': elTypeId});

			this.newEditorWidget = 
				new NS.OptionEditorWidget(this.gel('neweditor'), man, option, {
					'fromElement': fel || null,
					'onCancelClick': function(wEditor){ __self.newEditorClose(); },
					'onSave': function(wEditor){
						__self.newEditorClose(); 
						__self.render(true);
					}
				});
		},
		newEditorClose: function(){
			if (L.isNull(this.newEditorWidget)){ return; }
			this.newEditorWidget.destroy();
			this.newEditorWidget = null;
		}
	});
	NS.OptionListWidget = OptionListWidget;
	
	var OptionRowWidget = function(container, manager, option, cfg){
		cfg = L.merge({
			'onEditClick': null,
			'onCopyClick': null,
			'onRemoveClick': null,
			'onSelectClick': null,
			'onSave': null
		}, cfg || {});
		OptionRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row' 
		}, manager, option, cfg);
	};
	YAHOO.extend(OptionRowWidget, BW, {
		init: function(manager, option, cfg){
			this.manager = manager;
			this.option = option;
			this.cfg = cfg;
			this.editorWidget = null;
		},
		onLoad: function(manager, option){
			this.elSetHTML({
				'tl': option.title
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
		onSave: function(){
			NS.life(this.cfg['onSave'], this);
		},
		editorShow: function(){
			if (!L.isNull(this.editorWidget)){ return; }
			var __self = this;
			this.editorWidget = 
				new NS.OptionEditorWidget(this.gel('easyeditor'), this.manager, this.option, {
					'onCancelClick': function(wEditor){ __self.editorClose(); },
					'onSave': function(wEditor){ 
						__self.editorClose(); 
						__self.onSave();
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
	NS.OptionRowWidget = OptionRowWidget;	

	var OptionRemovePanel = function(manager, option, callback){
		this.manager = manager;
		this.option = option;
		this.callback = callback;
		OptionRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(OptionRemovePanel, Brick.widget.Dialog, {
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
			this.manager.optionRemove(this.option.typeid, this.option.id, function(){
				__self.close();
				NS.life(__self.callback);
			});
		}
	});
	NS.OptionRemovePanel = OptionRemovePanel;

};