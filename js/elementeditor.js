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
	
	var ElementEditBooleanWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditBooleanWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'optboolean' 
		}, option, value|0, cfg);
	};
	YAHOO.extend(ElementEditBooleanWidget, BW, {
		buildTData: function(option, value, cfg){
			return {'tl': option.title};
		},
		init: function(option, value, cfg){
			this.option = option;
			this.value = value;
			this.cfg = cfg;
		},
		onLoad: function(option, value, cfg){
			this.gel('val').checked = value>0 ? 'checked' : '';
		},
		getValue: function(){
			return this.gel('val').checked == 'checked' ? 1 : 0;
		}
	});
	NS.ElementEditBooleanWidget = ElementEditBooleanWidget;


	var ElementEditNumberWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditNumberWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'optnumber' // , 'isRowWidget': true 
		}, option, value|0, cfg);
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
		},
		getValue: function(){
			return this.gel('val').value;
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
		},
		getValue: function(){
			return this.gel('val').value;
		}
	});
	NS.ElementEditStringWidget = ElementEditStringWidget;


	var ElementEditDoubleWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditDoubleWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'optdouble'  
		}, option, value, cfg);
	};
	YAHOO.extend(ElementEditDoubleWidget, BW, {
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
		},
		getValue: function(){
			return this.gel('val').value;
		}
	});
	NS.ElementEditDoubleWidget = ElementEditDoubleWidget;
	

	var ElementEditTableWidget = function(container, manager, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditTableWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'opttable,opttablelist,opttablerow'  
		}, manager, option, value, cfg);
	};
	YAHOO.extend(ElementEditTableWidget, BW, {
		buildTData: function(manager, option, value, cfg){
			return {'tl': option.title};
		},
		init: function(manager, option, value, cfg){
			this.manager = manager;
			this.option = option;
			this.value = value;
			this.cfg = cfg;
			
			this.editValId = 0;
		},
		onLoad: function(manager, option, value, cfg){
			this.renderList();
			this.setValue(value);
			
			var __self = this;
			E.on(this.gel('input'), 'keypress', function(e){
				if (e.keyCode != 13){ return false; }
				__self.save();
				return true;
			});

		},
		renderList: function(){
			var TM = this._TM,
				lst = TM.replace('opttablerow', {'id': 0, 'tl': ""});
	
			this.option.values.foreach(function(dict){
				lst += TM.replace('opttablerow', {
					'id': dict.id,
					'tl': dict.title
				});
			});
			
			this.elSetHTML({
				'table': TM.replace('opttablelist', {'rows': lst})
			});
			
			var __self = this;
			E.on(this.gel('opttablelist.id'), 'change', function(e){
				__self.render(); 
			});
		},
		render: function(){
			var val = this.getValue();
			
			if (val == 0){
				this.elHide('bedit,bremove');
			}else{
				this.elShow('bedit,bremove');
			}
		},
		getValue: function(){
			return this.gel('opttablelist.id').value;
		},
		setValue: function(value){
			this.elSetValue('opttablelist.id', value);
			this.render();
		},
		onClick: function(el, tp){
			var TId = this._TId, tpb = TId['opttable'];
			switch(el.id){
			case tpb['badd']: case tpb['baddc']:
				this.showEditor(0);
				return true;
			case tpb['bedit']: case tpb['beditc']:
				this.showEditor(this.getValue());
				return true;
			case tpb['bremove']: case tpb['bremovec']:
				this.showRemove();
				return true;
			case tpb['bremcancel']: this.closeRemove(); return true;
			case tpb['bremok']: this.remove(); return true;
			case tpb['bedadd']: case tpb['bedsave']:
				this.save();
				return true;
			case tpb['bedcancel']: this.closeEditor(); return true;
			}
		},
		showRemove: function(){
			var valueid = this.getValue();
			if (valueid == 0){ return; }
			
			var value = this.option.values.get(valueid);
			if (L.isNull(value)){ return; }
			
			var tl = value.title, sblen = 25;
			if (tl.length > sblen){
				tl = tl.substring(0, sblen)+"...";
			}
			
			this.elSetHTML('remtl', tl);
			
			this.elHide('view');
			this.elShow('remove');
		},
		closeRemove: function(){
			this.elShow('view');
			this.elHide('remove');
		},
		remove: function(){
			var valueid = this.getValue();
			if (valueid == 0){ return; }

			var __self = this;
			this.elHide('rembtns');
			this.elShow('remprocess');
			
			var option = this.option;
			
			this.manager.optionTableValueRemove(option.typeid, option.id, valueid, function(values){
				__self.elHide('remprocess');
				__self.elShow('rembtns');
				__self.closeRemove();
				
				option.updateValues(values, true);
				
				__self.renderList();
				__self.setValue(0);
			});
		},
		showEditor: function(valueid){
			valueid = valueid || 0;
			
			this.editValId = valueid;
			var value = "";
			
			if (valueid == 0){
				this.elHide('bedsave');
				this.elShow('bedadd');
				this.elSetValue('input', "");
			}else{
				this.elHide('bedadd');
				this.elShow('bedsave');
				value = this.option.values.get(valueid);
				this.elSetValue('input', value.title);
			}
			
			this.elHide('view');
			this.elShow('editor');
			
			try {
				this.gel('input').focus();
			}catch(e){}
		},
		closeEditor: function(){
			this.elShow('view');
			this.elHide('editor');
		},
		save: function(){
			var value = L.trim(this.gel('input').value);
			if (value.length == 0){ return; }
			
			var valueid = this.editValId;

			var __self = this;
			this.elHide('edbtns');
			this.elShow('process');
			
			var option = this.option;
			
			this.manager.optionTableValueSave(option.typeid, option.id, valueid, value, function(values, newid){
				__self.elHide('process');
				__self.elShow('edbtns');
				__self.closeEditor();
				
				option.updateValues(values);
				
				__self.renderList();
				__self.setValue(newid);
			});
		}
	});
	NS.ElementEditTableWidget = ElementEditTableWidget;
	
	var ElementEditTextWidget = function(container, option, value, cfg){
		cfg = L.merge({
		}, cfg || {});
		ElementEditTextWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'opttext'  
		}, option, value, cfg);
	};
	YAHOO.extend(ElementEditTextWidget, BW, {
		buildTData: function(option, value, cfg){
			return {'tl': option.title};
		},
		init: function(option, value, cfg){
			this.option = option;
			this.value = value;
			this.cfg = cfg;
		},
		destroy: function(){
			this.editorWidget.destroy();
			ElementEditTextWidget.superclass.destroy.call(this);
		},
		onLoad: function(option, value, cfg){
			
			var Editor = Brick.widget.Editor;
			this.editorWidget = new Editor(this.gel('text'), {
				'toolbar': Editor.TOOLBAR_STANDART,
				// 'mode': Editor.MODE_VISUAL,
				'toolbarExpert': false,
				'separateIntro': false
			});
			
			this.editorWidget.setContent(value);
		},
		getValue: function(){
			return this.editorWidget.getContent();
		}
	});
	NS.ElementEditTextWidget = ElementEditTextWidget;

	

	var ElementEditorWidget = function(container, manager, element, cfg){
		cfg = L.merge({
			'onCancelClick': null,
			'onSaveElement': null
		}, cfg || {});
		ElementEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget,tpwidget,tplist,tprow,optnumber,nofoto' 
		}, manager, element, cfg);
	};
	YAHOO.extend(ElementEditorWidget, BW, {
		init: function(manager, element, cfg){
			this.manager = manager;
			this.element = element;
			this.cfg = cfg;
			this.fotosWidget = null;
			this.wsOptions = [];
		},
		buildTData: function(manager, element, cfg){
			var sTypeList = "", TM = this._TM;
			var tpList = manager.typeList;
			
			if (tpList.count() > 1){
				if (element.id == 0){
					var lst = "";
					tpList.foreach(function(tp){
						lst += TM.replace('tprow', {
							'id': tp.id, 'tl': tp.title
						});
					});
					sTypeList = TM.replace('tpwidget', {'tplist': TM.replace('tplist', {'rows': lst})}); 
				}else{
					var tp = tpList.get(element.typeid);
					if (!L.isNull(tp)){
						sTypeList = TM.replace('tpwidget', {'tplist': tp.title}); 
					}
				}
			}
			return {'typelist': sTypeList};
		},
		destroy: function(){
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.unlock();
			} 
			ElementEditorWidget.superclass.destroy.call(this);
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
			if (YAHOO.util.DragDropMgr){
				YAHOO.util.DragDropMgr.lock();
			} 
		},
		_onLoadElement: function(element){
			this.element = element;
			
			this.elHide('loading');
			this.elShow('view');
			
			var __self = this;
			var dtl = element.detail;
			
			this.elSetValue({
				'tl': element.title,
				'ord': element.order,
				'mtl': dtl.metaTitle,
				'mks': dtl.metaKeys,
				'mdsc': dtl.metaDesc
			});
			
			this.fotosWidget = new NS.ElementFotosEditWidget(this.gel('fotos'), this.manager, this.element.detail.fotos);
			
			this.renderOptions();

			E.on(this.gel('tplist.id'), 'change', function(e){
				__self.elementTypeChange(); 
			});
		},
		_wsClear: function(){
			var ws = this.wsOptions;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this.wsOptions = [];
		},
		renderOptions: function(){
			this._wsClear();
			var ws = [], elList = this.gel('optlist');
			var man = this.manager;
			
			this.element.detail.foreach(function(option, value){
				var div = document.createElement('div');
				elList.appendChild(div);
				
				switch(option.type){
				case NS.FTYPE['BOOLEAN']:
					ws[ws.length] = new NS.ElementEditBooleanWidget(div, option, value);
					break;
				case NS.FTYPE['NUMBER']:
					ws[ws.length] = new NS.ElementEditNumberWidget(div, option, value);
					break;
				case NS.FTYPE['STRING']:
					ws[ws.length] = new NS.ElementEditStringWidget(div, option, value);
					break;
				case NS.FTYPE['DOUBLE']:
					ws[ws.length] = new NS.ElementEditDoubleWidget(div, option, value);
					break;
				case NS.FTYPE['TABLE']:
					ws[ws.length] = new NS.ElementEditTableWidget(div, man, option, value);
					break;
				case NS.FTYPE['TEXT']:
					ws[ws.length] = new NS.ElementEditTextWidget(div, option, value);
					break;
				}
			});
			this.wsOptions = ws;
		},
		elementTypeChange: function(){
			var tpid = this.gel('tplist.id').value|0;
			if (this.element.typeid == tpid){ return; }
			
			this.element.typeid = tpid;
			this.renderOptions();
		},
		onClick: function(el, tp){
			var TId = this._TId;
			switch(el.id){
			case tp['bsave']: 
			case tp['bsavec']: 
				this.save(); return true;
			case tp['bcancel']: 
			case tp['bcancelc']: 
				this.onCancelClick(); return true;
				/*
			case TId['tplist']['id']: 
				this.elementTypeChange(); return false;
				/**/
			}
			return false;
		},
		onCancelClick: function(){
			NS.life(this.cfg['onCancelClick'], this);
		},
		save: function(){
			var cfg = this.cfg;
			var vals = {};
			var ws = this.wsOptions;
			for (var i=0;i<ws.length;i++){
				var w = ws[i];
				var tpid = w.option.typeid;
				
				vals[tpid] = vals[tpid] || {};
				vals[tpid][w.option.name] = w.getValue();
			}

			var element = this.element;
			var sd = {
				'catid': element.catid,
				'tpid': element.typeid,
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
			this.manager.elementSave(element.id, sd, function(element){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSaveElement'], __self);
			}, element);
		}
	});
	NS.ElementEditorWidget = ElementEditorWidget;
};