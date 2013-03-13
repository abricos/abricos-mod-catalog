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
	
	var ElementImage80Widget = function(container, fh, cfg){
		cfg = L.merge({
			'onRemoveClick': null,
			'onMoveLeftClick': null,
			'onMoveRightClick': null
		}, cfg || {});
		ElementImage80Widget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'foto', 'isRowWidget': true 
		}, fh, cfg);
	};
	YAHOO.extend(ElementImage80Widget, BW, {
		buildTData: function(fh, cfg){
			return {'fh': fh};
		},
		init: function(fhash, cfg){
			this.fhash = fhash;
			this.cfg = cfg;
		},
		onClick: function(el){
			var tp = this._TId['foto'];
			switch(el.id){
			case tp['bremove']: this.onRemoveClick(); return true;
			case tp['bleft']: this.onMoveLeftClick(); return true;
			case tp['bright']: this.onMoveRightClick(); return true;
			}
		},
		onRemoveClick: function(){
			NS.life(this.cfg['onRemoveClick'], this);
		},
		onMoveLeftClick: function(){
			NS.life(this.cfg['onMoveLeftClick'], this);
		},
		onMoveRightClick: function(){
			NS.life(this.cfg['onMoveRightClick'], this);
		}
	});
	NS.ElementImage80Widget = ElementImage80Widget;
	
	var ElementFotosEditWidget = function(container, manager, fotos, cfg){
		fotos = fotos || [];
		cfg = L.merge({}, cfg || {});
		ElementFotosEditWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'fotos,nofoto' 
		}, manager, fotos, cfg);
	};
	YAHOO.extend(ElementFotosEditWidget, BW, {
		init: function(manager, fotos, cfg){
			this.manager = manager;
			this.fotos = fotos;
			this.cfg = cfg;
			this.wsFotos = [];
			this.uploadWindow = null;
		},
		clearFotos: function(){
			var ws = this.wsFotos;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.wsFotos = [];
		},
		render: function(){
			this.clearFotos();
			
			var fotos = this.fotos;
			if (fotos.length == 0){
				this.elSetHTML('fotolist', this._TM.replace('nofoto'));
			}else{
				this.elSetHTML('fotolist', '');
			}
			
			var ws = [], __self = this;
			for (var i=0;i<fotos.length;i++){
				ws[ws.length] = new NS.ElementImage80Widget(this.gel('fotolist'), fotos[i], {
					'onRemoveClick': function(wFoto){
						__self.fotoRemove(wFoto.fhash);
					},
					'onMoveLeftClick': function(wFoto){
						__self.fotoMoveLeft(wFoto.fhash);
					},
					'onMoveRightClick': function(wFoto){
						__self.fotoMoveRight(wFoto.fhash);
					}
				});
			}
			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}
			this.wsFotos = ws;
		},
		onClick: function(el, tp){
			var ws = this.wsFotos;
			for (var i=0;i<ws.length;i++){ ws[i].onClick(el); }

			switch(el.id){
			case tp['baddfotos']: this.fotoUploadShow(); return true;
			}
			return false;
		},
		fotoUploadShow: function(wEditor){
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
		fotoAdd: function(nfotos){
			var arr = [];
			for (var i=0;i<this.fotos.length;i++){
				arr[arr.length] = this.fotos[i];
			}			
			for (var i=0;i<nfotos.length;i++){
				arr[arr.length] = nfotos[i];
			}
			this.fotos = arr;
			this.render();
		},
		fotoMoveLeft: function(fhash){
			var arr = [];
			for (var i=0;i<this.fotos.length;i++){
				var f = this.fotos[i];
				if (f == fhash){
					if (i == 0){ return; } // он и так первый
					var flast = arr[arr.length-1];
					arr[arr.length-1] = f;
					arr[arr.length] = flast;
				}else{
					arr[arr.length] = f;
				}
			}
			this.fotos = arr;
			this.render();
		},
		fotoMoveRight: function(fhash){
			var arr = [];
			for (var i=0;i<this.fotos.length;i++){
				var f = this.fotos[i];
				if (f == fhash){
					if (i == this.fotos.length-1){ return; } // он и так последний
					
					var fnext = this.fotos[i+1];
					arr[arr.length] = fnext;
					arr[arr.length] = f;
					i++;
				}else{
					arr[arr.length] = f;
				}
			}
			this.fotos = arr;
			this.render();
		},
		fotoRemove: function(fhash){
			var arr = [];
			for (var i=0;i<this.fotos.length;i++){
				if (this.fotos[i] != fhash){
					arr[arr.length] = this.fotos[i];
				}
			}
			this.fotos = arr;
			this.render();
		}
	});
	NS.ElementFotosEditWidget = ElementFotosEditWidget;
	
	
	var ElementEditNumberWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditNumberWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'optnumber' // , 'isRowWidget': true 
		}, option, value, cfg);
	};
	YAHOO.extend(ElementEditNumberWidget, BW, {
		buildTData: function(option, value, cfg){
			return {'tl': option.title};
		},
		init: function(option, value, cfg){
			this.option = option;
			this.value = value;
			this.cfg = cfg;
		},
		onLoad: function(option, value, cfg){
			this.elSetValue('val', value);
		}
	});
	NS.ElementEditNumberWidget = ElementEditNumberWidget;	

	var ElementEditStringWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditStringWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'optstring' // , 'isRowWidget': true 
		}, option, value, cfg);
	};
	YAHOO.extend(ElementEditStringWidget, BW, {
		buildTData: function(option, value, cfg){
			return {'tl': option.title};
		},
		init: function(option, value, cfg){
			this.option = option;
			this.value = value;
			this.cfg = cfg;
		},
		onLoad: function(option, value, cfg){
			this.elSetValue('val', value);
		}
	});
	NS.ElementEditStringWidget = ElementEditStringWidget;	

	var ElementEasyEditRowWidget = function(container, manager, element, cfg){
		cfg = L.merge({
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
			this.fotosWidget = null;
			this.wsOptions = [];
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
			
			this.fotosWidget = new NS.ElementFotosEditWidget(this.gel('fotos'), this.manager, this.element.detail.fotos);
			
			var typeList = this.manager.typeList,
				tbase = typeList.get(0);
			
			var ws = [], elList = this.gel('optlist');
			tbase.options.foreach(function(toption){
				var div = document.createElement('div'),
					value = element.detail.getValue(toption);

				elList.appendChild(div);
				
				switch(toption.type){
				case NS.FTYPE['NUMBER']:
					ws[ws.length] = new NS.ElementEditNumberWidget(div, toption, value);
					break;
				case NS.FTYPE['STRING']:
					ws[ws.length] = new NS.ElementEditStringWidget(div, toption, value);
					break;
				}
			});
			this.wsOptions = ws;
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
			this.manager.elementSave(this.element.id, sd, function(element){
				__self.elShow('btnsc');
				__self.elHide('btnpc');
			}, this.element);
		}
	});
	NS.ElementEasyEditRowWidget = ElementEasyEditRowWidget;
};