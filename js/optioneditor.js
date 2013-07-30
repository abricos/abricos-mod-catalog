/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['editor.js']},
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
			'onSaveElement': null
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

			var option = this.option;
			var sd = {
				'catid': this.catSelectWidget.getValue(),
				'tpid': option.typeid,
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
			this.manager.optionSave(option.id, sd, function(option){
				__self.elShow('btnsc,btnscc');
				__self.elHide('btnpc,btnpcc');
				NS.life(cfg['onSaveElement'], __self, option);
			}, option);
			/**/
		}
	});
	NS.OptionEditorWidget = OptionEditorWidget;
};