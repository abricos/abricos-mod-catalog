/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[{name: 'user', files: ['permission.js']}]
};
Component.entryPoint = function(NS){
	
	var BP = Brick.Permission;

	NS.roles = {
		load: function(callback){
			BP.load(function(){
				callback();
			});
		}
	};
	
};