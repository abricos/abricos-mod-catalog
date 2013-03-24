/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['animation', 'dragdrop'],
	mod:[
		{name: '{C#MODNAME}', files: ['elementeditor.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var DDM = YAHOO.util.DragDropMgr; 
	
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
			
			this.newEditorWidget = null;
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
				__self = this, man = this.manager;
			
			var list = this.list;

			list.foreach(function(element){
				var div = document.createElement('div');
				div['catalogElement'] = element;
				
				elList.appendChild(div);
				var w = new NS.ElementRowWidget(div, __self.manager, element, {
					'onEditClick': function(w){__self.onElementEditClick(w);},
					'onRemoveClick': function(w){__self.onElementRemoveClick(w);},
					'onSelectClick': function(w){__self.onElementSelectClick(w);}
				});
				
				
				var dd = new NS.ElementRowDragItem(div, {
					'endDragCallback': function(dgi, elDiv){
						var chs = elList.childNodes, ordb = list.count();
						var orders = {};
						for (var i=0;i<chs.length;i++){
							var catel = chs[i]['catalogElement'];
							if (catel){
								catel.order = ordb;
								orders[catel.id] = ordb;
								ordb--;
							}
						}
						man.elementListOrderSave(list.catid, orders);
						__self.render();
					}
				});
		
				ws[ws.length] = w;
			}, 'order', true);
			
			new YAHOO.util.DDTarget(elList);
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
		onElementRemoveClick: function(w){
		},
		onElementSelectClick: function(w){
			this.allEditorClose(w);
			// w.editorShow();
		},
		showNewEditor: function(){
			if (!L.isNull(this.newEditorWidget)){ return; }
			
			this.allEditorClose();
			
			var element = new NS.Element({'catid': this.list.catid});
			this.newEditorWidget = 
				new NS.ElementEditorWidget(this.gel('neweditor'), this.manager, element, {
					'onCancelClick': function(wEditor){ __self.newEditorClose(); }
				});
		},
		newEditorClose: function(){
			if (L.isNull(this.newEditorWidget)){ return; }
			this.newEditorWidget.destroy();
			this.newEditorWidget = null;
		}
	});
	NS.ElementListWidget = ElementListWidget;
	
	var ElementRowDragItem = function(id, cfg){
		ElementRowDragItem.superclass.constructor.call(this, id, '', cfg);
		
		var el = Dom.get(id);
		Dom.addClass(el, 'dragitem');
		
		this.goingUp = false; 
		this.lastY = 0; 
	};
	YAHOO.extend(ElementRowDragItem, YAHOO.util.DDProxy, { 
		startDrag: function(x, y){
			var dragEl = this.getDragEl(); 
			var clickEl = this.getEl(); 
			dragEl.innerHTML = clickEl.innerHTML; 
			
			Dom.setStyle(clickEl, "visibility", "hidden");
			Dom.setStyle(dragEl, "backgroundColor", "#FFF");
		},
		onDrag: function(e){
	        var y = E.getPageY(e);

	        if (y < this.lastY) {
	            this.goingUp = true;
	        } else if (y > this.lastY) {
	            this.goingUp = false;
	        }

	        this.lastY = y;					
		},
		onDragOver: function(e, id) {
	        var srcEl = this.getEl();
	        var destEl = Dom.get(id);

	        if (Dom.hasClass(destEl, 'dragitem')) {
	            var p = destEl.parentNode;

	            if (this.goingUp) {
	                p.insertBefore(srcEl, destEl); // insert above
	            } else {
	                p.insertBefore(srcEl, destEl.nextSibling); // insert below
	            }
	            DDM.refreshCache();
	        }
	    },
	    endDrag: function(e) {
			
	        var srcEl = this.getEl();
	        var proxy = this.getDragEl();

	        // Show the proxy element and animate it to the src element's location
	        Dom.setStyle(proxy, "visibility", "");
	        var a = new YAHOO.util.Motion( 
	            proxy, {
	                points: {
	                    to: Dom.getXY(srcEl)
	                }
	            },
	            0.2,
	            YAHOO.util.Easing.easeOut
	        );
	        var proxyid = proxy.id;
	        var thisid = this.id;

	        a.onComplete.subscribe(function() {
			    Dom.setStyle(proxyid, "visibility", "hidden");
			    Dom.setStyle(thisid, "visibility", "");
			});
	        a.animate();
	        
	        NS.life(this.config['endDragCallback'], this, srcEl);
	    },
	    onDragDrop: function(e, id){
			 if (DDM.interactionInfo.drop.length === 1) { 
				 var pt = DDM.interactionInfo.point;
				 var region = DDM.interactionInfo.sourceRegion;  
				 if (!region.intersect(pt)) { 
					var destEl = Dom.get(id); 
					var destDD = DDM.getDDById(id); 
					destEl.appendChild(this.getEl()); 
					destDD.isEmpty = false; 
					DDM.refreshCache(); 
				 }
			}
		}
	});
	NS.ElementRowDragItem = ElementRowDragItem;
	
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
				new NS.ElementEditorWidget(this.gel('easyeditor'), this.manager, this.element, {
					'onCancelClick': function(wEditor){ __self.editorClose(); }
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
	NS.ElementRowWidget = ElementRowWidget;	

};