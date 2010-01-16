/*
@version $Id: api.js 55 2009-09-20 11:57:32Z roosit $
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = { yahoo: ['dom'] };
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace;
	
	var API = NS.API;
	
	API.showManagerWidget = function(container, prefix){
		API.fn('catalog', function(){
			var widget = new NS.ManagerWidget(container, prefix);
			API.addWidget('manager', widget);
			API.dsRequest(prefix);
		});
	};
	
	API.dsRequest = function(prefix){
		if (!Brick.objectExists('Brick.mod.catalog.data')){
			return;
		}
		Brick.mod.catalog.data[prefix].request(true);
	};
	
};
