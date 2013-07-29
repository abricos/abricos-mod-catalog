/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['editor.js']},
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;

	var TypeEditorWidget = function(container, manager, type, cfg){
		cfg = L.merge({
			'fromElement': null,
			'onCancelClick': null,
			'onSaveElement': null
		}, cfg || {});
		TypeEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, type, cfg);
	};
	YAHOO.extend(TypeEditorWidget, BW, {
		init: function(manager, type, cfg){
			this.manager = manager;
			this.type = type;
			this.cfg = cfg;
		},
		onLoad: function(manager, type){
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
			this.type = type;
			
			this.elHide('loading');
			this.elShow('view');
			
			var __self = this, dtl = type.detail;
			
			this.elSetValue({
				'tl': type.title
			});
			
			var keypress = function(e){
				if (e.keyCode != 13){ return false; }
				__self.save(); return true; 
			};
			E.on(this.gel('tl'), 'keypress', keypress);
			
			var elTitle = this.gel('tl');
			setTimeout(function(){try{elTitle.focus();}catch(e){}}, 100);
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
			case tp['bsave']: 
			case tp['bsavec']: 
				this.save(); return true;
			case tp['bcancel']: 
			case tp['bcancelc']: 
				this.onCancelClick(); return true;
			}
			return false;
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		save: function(){
			/*
			var cfg = this.cfg;
			var vals = {};
			var ws = this.wsOptions;
			for (var i=0;i<ws.length;i++){
				var w = ws[i];
				var tpid = w.option.typeid;
				
				vals[tpid] = vals[tpid] || {};
				vals[tpid][w.option.name] = w.getValue();
			}

			var type = this.type;
			var sd = {
				'catid': this.catSelectWidget.getValue(),
				'tpid': type.typeid,
				'tl': this.gel('tl').value,
				'fotos': this.fotosWidget.fotos,
				'values': vals,
				'ord': this.gel('ord').value,
				'mtl': this.gel('mtl').value,
				'mks': this.gel('mks').value,
				'mdsc': this.gel('mdsc').value
			};

			this.elHide('btnsc,btnscc');
			this.elShow('btnpc,btnpcc');

			var __self = this;
			this.manager.typeSave(type.id, sd, function(type){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSaveElement'], __self, type);
			}, type);
			/**/
		}
	});
	NS.TypeEditorWidget = TypeEditorWidget;
};