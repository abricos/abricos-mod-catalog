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
		buildTemplate = this.buildTemplate;
	
	var CatalogListWidget = function(modname, container){
		CatalogListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, modname);
	};
	YAHOO.extend(CatalogListWidget, Brick.mod.widget.Widget, {
		init: function(modname){
			this.modname = modname;
		},
		onLoad: function(modname){
			var __self = this;
			NS.initManager(modname, function(){
				__self.renderList();
			});
		},
		renderList: function(){
			// Brick.console('ok');
		}
	});
	NS.CatalogListWidget = CatalogListWidget;

};