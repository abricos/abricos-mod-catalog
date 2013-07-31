/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['form.js', 'editor.js']},
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;

	var TypeEditorWidget = function(container, manager, elType, cfg){
		cfg = L.merge({
			'fromElement': null,
			'onCancelClick': null,
			'onSave': null
		}, cfg || {});
		TypeEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, elType, cfg);
	};
	YAHOO.extend(TypeEditorWidget, BW, {
		init: function(manager, elType, cfg){
			this.manager = manager;
			this.elType = elType;
			this.cfg = cfg;
		},
		onLoad: function(manager, elType){
			var cfg = this.cfg, fel = cfg['fromElement'];
			if (!L.isNull(fel)){
				/*
				if (!L.isNull(fel.detail)){
					this._onLoadElement(fel.copy());
				}else{
					var __self = this;
					manager.typeLoad(fel.id, function(fel){
						__self._onLoadElement(fel.copy());
					}, fel);
				}
				/**/
			}
			
			this.elHide('loading');
			this.elShow('view');
			
			var __self = this;
			
			this.elSetValue({
				'tl': elType.title,
				'nm': elType.name
			});
			
			var keypress = function(e){
				if (e.keyCode != 13){ return false; }
				__self.save(); return true; 
			};
			E.on(this.gel('tl'), 'keypress', keypress);
			
			E.on(this.gel('nm'), 'focus', function(e){
				__self.nameTranslate();
			});
			
			var elTitle = this.gel('tl');
			setTimeout(function(){try{elTitle.focus();}catch(e){}}, 100);
		},
		nameTranslate: function(){
			var tl = L.trim(this.gel('tl').value),
				nm = L.trim(this.gel('nm').value);
			if (nm.length == 0){
				nm = Brick.util.Translite.ruen(tl);
			}
			
			this.elSetValue({
				'nm': Brick.util.Translite.ruen(nm)
			});
		},
		_wsClear: function(){
			var ws = this.wsOptions;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.wsOptions = [];
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bsave']: case tp['bsavec']: 
				this.save(); return true;
			case tp['bcancel']: case tp['bcancelc']: 
				this.onCancelClick(); return true;
			}
			return false;
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		save: function(){
			this.nameTranslate();
			
			var cfg = this.cfg, elType = this.elType;
			var sd = {
				'tl': this.gel('tl').value,
				'nm': this.gel('nm').value,
				'dsc': ''
			};

			this.elHide('btnsc,btnscc');
			this.elShow('btnpc,btnpcc');

			var __self = this;
			this.manager.elementTypeSave(elType.id, sd, function(elType){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSave'], __self, elType);
			}, elType);
		}
	});
	NS.TypeEditorWidget = TypeEditorWidget;
};