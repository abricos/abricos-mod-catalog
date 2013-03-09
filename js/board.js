/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: '{C#MODNAME}', files: ['catalogexplore.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var L = YAHOO.lang,
		buildTemplate = this.buildTemplate,
		BW = Brick.mod.widget.Widget;
	
	var BoardWidget = function(modname, container){
		BoardWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		}, modname);
	};
	YAHOO.extend(BoardWidget, BW, {
		init: function(modname){
			this.modname = modname;
		},
		onLoad: function(modname){
			var __self = this;
			NS.initManager(modname, function(man){
				__self.renderList(man.catalogList);
			});
		},
		renderList: function(list){
			this.elHide('loading');
			this.treeWidget = new NS.CatalogTreeWidget(this.gel('explore'), list);
		}
	});
	NS.BoardWidget = BoardWidget;

};