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
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var Foto80Widget = function(container, fh, cfg){
		cfg = L.merge({
			'onRemoveClick': null,
			'onMoveLeftClick': null,
			'onMoveRightClick': null
		}, cfg || {});
		Foto80Widget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'foto', 'isRowWidget': true 
		}, fh, cfg);
	};
	YAHOO.extend(Foto80Widget, BW, {
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
	NS.Foto80Widget = Foto80Widget;
	
	var FotoListEditWidget = function(container, manager, fotos, cfg){
		if (L.isString(fotos) && fotos.length >= 8){
			fotos = [fotos];
		}else{
			fotos = fotos || [];
		}
		cfg = L.merge({
			'limit': 0
		}, cfg || {});
		FotoListEditWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'fotos,nofoto' 
		}, manager, fotos, cfg);
	};
	YAHOO.extend(FotoListEditWidget, BW, {
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
			
			var fotos = this.fotos, cfg = this.cfg;
			if (fotos.length == 0){
				this.elSetHTML('fotolist', this._TM.replace('nofoto'));
			}else{
				this.elSetHTML('fotolist', '');
			}
			
			var ws = [], __self = this;
			for (var i=0;i<fotos.length;i++){
				ws[ws.length] = new NS.Foto80Widget(this.gel('fotolist'), fotos[i], {
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
			var url = '/catalogbase/uploadimg/'+man.modname+'/';
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
	NS.FotoListEditWidget = FotoListEditWidget;

};