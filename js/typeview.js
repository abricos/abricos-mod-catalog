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

	var TypeViewWidget = function(container, manager, elType, cfg){
		cfg = L.merge({
		}, cfg || {});
		TypeViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, manager, elType, cfg);
	};
	YAHOO.extend(TypeViewWidget, BW, {
		init: function(manager, elType, cfg){
			this.manager = manager;
			this.elType = elType;
			this.cfg = cfg;
		},
		onLoad: function(manager, elType, cfg){
		},
		setElType: function(elType){
			this.elType = elType;
			this.render();
		},
		render: function(){
			var elType = this.elType;
			this.elSetHTML({
				'tl': elType.title
			});
		}
	});
	NS.TypeViewWidget = TypeViewWidget;
};