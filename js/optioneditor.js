/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['form.js', 'editor.js']},
		{name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var OptionFTypeSelectWidget = function(container, cfg){
		cfg = L.merge({
			'value': 0,
			'onChange': null
		}, cfg || {});
		
		OptionEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'ftypeselect' 
		}, cfg);
	};
	YAHOO.extend(OptionFTypeSelectWidget, BW, {
		onLoad: function(cfg){

			this.setValue(cfg['value']);

			var __self = this;
			E.on(this.gel('id'), 'change', function(e){
				NS.life(cfg['onChange'], __self.getValue());
			});
		},
		setValue: function(value){
			this.elSetValue('id', value);
		},
		getValue: function(){
			return this.gel('id').value;
		}
	});
	NS.OptionFTypeSelectWidget = OptionFTypeSelectWidget;
	
	var OptionEditorWidget = function(container, manager, option, cfg){
		cfg = L.merge({
			'fromElement': null,
			'onCancelClick': null,
			'onSave': null
		}, cfg || {});
		OptionEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, option, cfg);
	};
	YAHOO.extend(OptionEditorWidget, BW, {
		init: function(manager, option, cfg){
			this.manager = manager;
			this.option = option;
			this.cfg = cfg;

			this.wsOptions = [];
		},
		destroy: function(){
			OptionEditorWidget.superclass.destroy.call(this);
		},
		onLoad: function(manager, option){
			var __self = this;
			this.elSetValue({
				'tl': option.title,
				'nm': option.name,
				'sz': option.size,
				'ord': option.order
			});

			this.fTypeSelectWidget = new NS.OptionFTypeSelectWidget(this.gel('ftypesel'), {
				'value': option.type,
				'onChange': function(){
					__self.refreshFType();
				}
			});
			
			var keypress = function(e){
				if (e.keyCode != 13){ return false; }
				__self.save(); return true; 
			};
			E.on(this.gel('tl'), 'keypress', keypress);
			
			var elTitle = this.gel('tl');
			setTimeout(function(){try{elTitle.focus();}catch(e){}}, 100);
			
			this.refreshFType();
			
			E.on(this.gel('nm'), 'focus', function(e){
				__self.nameTranslate();
			});
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
		refreshFType: function(){
			var fType = this.fTypeSelectWidget.getValue()|0;
			switch(fType){
			case NS.FTYPE['NUMBER']:
			case NS.FTYPE['DOUBLE']:
			case NS.FTYPE['STRING']:
				this.elShow('fsize');
				break;
			default:
				this.elHide('fsize');
				break;
			}
			
			if (this._lastFType != fType){
				this._lastFType = fType;
				switch(fType){
				case NS.FTYPE['NUMBER']:
					this.elSetValue('sz', '10');
					break;
				case NS.FTYPE['DOUBLE']:
					this.elSetValue('sz', '10,2');
					break;
				case NS.FTYPE['STRING']:
					this.elSetValue('sz', '255');
					break;
				}
			}
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
			var cfg = this.cfg, option = this.option;
			var sd = {
				'tl': this.gel('tl').value,
				'nm': this.gel('nm').value,
				'sz': this.gel('sz').value,
				'ord': this.gel('ord').value,
				'tp': this.fTypeSelectWidget.getValue(),
				'tpid': option.typeid
			};

			this.elHide('btnsc,btnscc');
			this.elShow('btnpc,btnpcc');

			var __self = this;
			this.manager.optionSave(option.id, sd, function(){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSave'], __self);
			});
		}
	});
	NS.OptionEditorWidget = OptionEditorWidget;
};