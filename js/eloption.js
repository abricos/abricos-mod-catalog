/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2010 Abricos. All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


/**
 * @module Catalog
 * @namespace Brick.mod.catalog
 */

var Component = new Brick.Component();
Component.requires = { 
	yahoo: ['json'],
	mod:[
	     {name: 'sys', files: ['form.js','data.js']}
	    ]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		J = YAHOO.lang.JSON;
	
	var NS = this.namespace,
		TMG = this.template,
		API = NS.API;

	NS.data = NS.data || {};
	var DATA = NS.data;

	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};

	/**
	 * Виджет. Список полей элемента определенного типа<br>
	 * 
	 * @class ElementOptionsWidget
	 * @constructor
	 * @param {HTMLObject | String} container Контейнер
	 * @param {Integer} eltypeid Идентификатор типа элемента
	 * @param {String} mmPrefix Префикс управляющего модуля
	 */
	var ElementOptionsWidget = function(container, eltypeid, mmPrefix){
		this.init(container, eltypeid, mmPrefix);
	};
	ElementOptionsWidget.prototype = {
		init: function(container, eltypeid, mmPrefix){
			if (!DATA[mmPrefix]){
				DATA[mmPrefix] = new Brick.util.data.byid.DataSet('catalog', mmPrefix);
			}
	
			this.container = Dom.get(container);
			this.eltypeid = eltypeid;
			this.mmPrefix = mmPrefix;
			
			buildTemplate(this, 'panel,table,rowdel,row,rowgroup');
			
			container.innerHTML = this._TM.replace('panel');
			
			var ds = DATA[mmPrefix];
			this.tables = {
				'eltype': ds.get('eltype', true),
				'eloption': ds.get('eloption', true),
				'eloptgroup': ds.get('eloptgroup', true)
			};
			ds.onComplete.subscribe(this.onDSUpdate, this, true);
			if (ds.isFill(this.tables)){
				this.render();
			}
		},
		onDSUpdate: function(type, args){
			if (args[0].check(['eloption', 'eloptgroup'])){ this.render(); }
		},
		destroy: function(){
			DATA[this.mmPrefix].onComplete.unsubscribe(this.onDSUpdate, this);
		},
		render: function(){
			var TM = this._TM, T = this._T, TId = this._TId;
			
			if (this.eltypeid > 0){
				var elTypeRow = this.tables['eltype'].getRows().getById(this.eltypeid);
				TM.getEl('panel.eltypetl').innerHTML = elTypeRow.cell['tl'];
			}
			
			var lang = Brick.util.Language.getData()['catalog']['element']['fieldtype']; 
			var lst = "", lastGroupId = 0, group, tables = this.tables;
			
			tables['eloption'].getRows().filter({'eltid': this.eltypeid}).foreach(function(row){
				var di = row.cell;
				if (lastGroupId != di['grp']){
					group = tables['eloptgroup'].getRows().getById(di['grp']);
					if (group){
						lst += TM.replace('rowgroup', {
							'id': di['grp'], 'tl': group.cell['tl']
						});
					}
				}
				lastGroupId = di['grp'];
				lst += TM.replace(di['dd']>0 ? 'rowdel' : 'row', {
					'id': di['id'], 
					'tl': di['tl'], 
					'dsc': di['dsc'],
					'fldtp': lang[di['fldtp']]
				});
			});
			
			TM.getEl('panel.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		onClick: function(el){
			var TId = this._TId;
			if (el.id == TId['panel']['badd']){
				this.add();
				return true;
			}
			
			if (el.id == TId['panel']['rcclear']){
				this.recyclerClear();
				return true;
			}
			
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			switch(prefix){
			case (TId['row']['edit']+'-'): this.edit(numid); return true;
			case (TId['row']['remove']+'-'): this.remove(numid); return true;
			case (TId['rowdel']['restore']+'-'): this.restore(numid); return true;
			}
			return false;
		},
		add: function(){
			var row = this.tables['eloption'].newRow();
			this.tables['eloption'].getRows().add(row);
			row.cell['eltid'] = this.eltypeid;
			this.activeEditor = new OptionEditorPanel(row, this.mmPrefix);
		},
		edit: function(id){
			var row = this.tables['eloption'].getRows().getById(id); 
			this.activeEditor = new OptionEditorPanel(row, this.mmPrefix);
		},
		remove: function(id){
			this.tables['eloption'].getRows().getById(id).remove();
			this._query();
		},
		restore: function(id){
			this.tables['eloption'].getRows().getById(id).restore();
			this._query();
		},
		recyclerClear: function(){
			this.tables['eloption'].recycleClear();
			this._query();
		},
		_query: function(){
			this.tables['eloption'].applyChanges();
			DATA[this.mmPrefix].request();
		}
	};
	NS.ElementOptionsWidget = ElementOptionsWidget;
	
	/**
	 * Панель. Редактор поля элемента определенного типа<br>
	 * 
	 * @class OptionEditorPanel
	 * @constructor
	 * @param {DataRow} row Строка таблицы DataSet
	 * @param {String} mmPrefix Префикс управляющего модуля
	 */
	var OptionEditorPanel = function(row, mmPrefix){
		this.mmPrefix = mmPrefix;
		this.row = row;
		OptionEditorPanel.superclass.constructor.call(this);
	};
	YAHOO.extend(OptionEditorPanel, Brick.widget.Dialog, {
		initTemplate: function(){
		
			buildTemplate(this, 'editor,custfuncinsrow,custfunccount,custfunc0,custfuncinp0,custfunconld0,custfunc1,custfuncinp1,custfunconld1');
			var TM = this._TM, T = this._T, TId = this._TId;
			
			var ds = DATA[this.mmPrefix];
			this.tables = {
				'eloption': ds.get('eloption', true),
				'eloptgroup': ds.get('eloptgroup', true)
			};
			var tables = this.tables;

			return TM.replace('editor', {
				'grouplist': (function(){
						var lst = "";
						tables['eloptgroup'].getRows().foreach(function(row){
							di = row.cell;
							lst += TM.replace('edrowgroup', {
								'id': di['id'], 'tl': di['tl']
							});
						});
						return lst;
					})(),
				'opt6vallist': (function(){
						// Составной тип опции
						var lst = "";
						tables['eloption'].getRows().foreach(function(row){
							di = row.cell;
							if (di['fldtp'] == 6){ return; }
							lst += TM.replace('edrowgroup', {
								'id': di['nm'], 'tl': di['tl']
							});
						}, this);
						return lst;
					})(),
				'custfunc': (function(){
					// Расширенные возможности
					// Заполнение справочника готовых функций
					var lst = '', custcount = T['custfunccount'];
					for (var i=0;i<custcount;i++){
						lst += TM.replace('custfuncinsrow', {
							'id': i,
							'tl': T['custfunc'+i]
						});
					}
					return lst;
				})()
			});
		},
		el: function(name){ return Dom.get(this._TId['editor'][name]); },
		elv: function(name){
			var el = this.el(name);
			return Brick.util.Form.getValue(el);
		},
		setel: function(el, value){ Brick.util.Form.setValue(el, value); },
		setelv: function(name, value){
			var el = this.el(name);
			this.setel(el, value);
		},
		onLoad: function(){
			var TM = this._TM, T = this._T, TId = this._TId;

			var __self = this;
			var title = this.el('title');
			var descript = this.el('descript');
			var name = this.el('name');
			var disabled = this.el('disabled');
			var group = this.el('group');

			var fldtype = this.el('fldtype');

			var o = this.row.cell;
			var sprm = o['prms'];
			var prm = sprm ? J.parse(sprm) : {};
			prm['cst'] = prm['cst'] || {};
			var opt = this.getTypeOpt();
			
			var updList = function(){
				for (var i=0;i<10;i++){
					if (opt[i]){opt[i]['panel'].style.display = (i == fldtype.value ? "" : "none");}
				}
				__self.center();
			};
			E.on(fldtype, 'change', function(e){ updList(); });
			
			var elBtnCust = this.el('cust'), elCustCont = this.el('custcont');
			var updCustCont = function(){
				elCustCont.style.display = elBtnCust.checked ? "" : "none"; 
				__self.center();
			};
			E.on(elBtnCust, 'change', function(e){ updCustCont(); });
			
			var elCheckedDE = function(chkboxid, contid){
				var chkbox = __self.el(chkboxid), cont = __self.el(contid); 
				var upd = function(){ cont.disabled = chkbox.checked ? "" : "disabled"; };
				E.on(chkbox, 'change', function(e){ upd(); });
				upd();
			};

			this.setelv('title', o['tl']);
			this.setelv('name', o['nm']);
			this.setelv('descript', o['dsc']);
			this.setelv('fldtype', o['fldtp']);
			this.setelv('group', o['grp']);
			this.setelv('eltitlesource', o['ets']);
			
			var optel = opt[o['fldtp']];
			if (prm['size']){this.setel(optel['size'], prm['size']);}
			if (prm['def']){this.setel(optel['def'], prm['def']);}
			if (prm['val']){this.setel(optel['val'], prm['val']);}

			this.setelv('cust', prm['cst']['en']);
			this.setelv('custinputb', prm['cst']['inpen']);
			this.setelv('custinput', prm['cst']['inp']);
			this.setelv('custonloadb', prm['cst']['onlden']);
			this.setelv('custonload', prm['cst']['onld']);
			
			updList();
			updCustCont();
			elCheckedDE('custinputb', 'custinput');
			elCheckedDE('custonloadb', 'custonload');
			
			if (o['id']>0){
				name.disabled = "disabled";
				fldtype.disabled = "disabled";
				if (optel['size']){optel['size'].disabled = "disabled";}
				if (optel['def']){optel['def'].disabled = "disabled";}
			}
			
			var validobj = {
				elements: {
					'title':{ obj: title, rules: ["empty"]},
					'name':{ obj: name, rules: ["empty", "unixname"]}
				}
			};

			this._validator = new Brick.util.Form.Validator(validobj);
		},
		getTypeOpt: function(){
			var opt = {
				'0': { 'panel': this.el('contopt0'),
					'def': this.el('opt0def')
				},
				'1': { 'panel': this.el('contopt1'),
					'size': this.el('opt1size'),
					'def': this.el('opt1def')
				},
				'2': { 'panel': this.el('contopt2'),
					'size': this.el('opt2size'),
					'def': this.el('opt2def')
				},
				'3': { 'panel': this.el('contopt3'),
					'size': this.el('opt3size'),
					'def': this.el('opt3def')
				},
				'4': { 'panel': this.el('contopt4'),
					'val': this.el('opt4val'),
					'def': this.el('opt4def')
				},
				'5': { 'panel': this.el('contopt5')},
				'6': { 'panel': this.el('contopt6'),
					'val': this.el('opt6val')
				},
				'7': { 'panel': this.el('contopt7') },
				'8': { 'panel': this.el('contopt8'),
					'size': this.el('opt8size')
				},
				'9': { 'panel': this.el('contopt9')}
			};
			return opt;
		},
		nameTranslite: function(){
			var el = this.el('name');
			var title = this.el('title');
			if (!el.value && title.value){
				el.value = Brick.util.Translite.ruen(title.value);
			}
		},
		onClose: function(){
			if (this.row.isNew()){
				this.tables['eloption'].getRows().remove(this.row);
			}
		},
		onClick: function(el){
			var tp = this._TId['editor']; 
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bsave']: this.save(); return true;
			case tp['name']:
				this.nameTranslite();
				return true;
			case tp['bcustfuncins']:
				this.custFuncInsertClick();
				return true;
			case tp['opt6valbadd']:
				this.multiOptBAddClick();
				return true;
			}
		},
		multiOptBAddClick: function(){
			var optid = this.elv('opt6vallist');
			if (!optid){ return; }
			
			var optlist = this.elv('opt6val');
			var arr = optlist.split('\n');
			var oarr = {}, n;
			for (var i=0;i<arr.length;i++){
				n = arr[i];
				if (n){ oarr[n] = n; }
			}
			oarr[optid] = optid;
			arr = [];
			var lst = "";
			for (var n in oarr){
				arr[arr.length] = n; 
			}
			this.setelv('opt6val', arr.join('\n'));
		},
		custFuncInsertClick: function(){
			var T = this._T;
			var fid = this.elv('custfunc');
			if (fid < 0){ return; }
			
			this.setelv('custinputb', 1);
			this.setelv('custonloadb', 1);
			this.setelv('custinput', T['custfuncinp'+fid]);
			this.setelv('custonload', T['custfunconld'+fid]);
			
			var i, lsn;
			lsn = E.getListeners(this.el('custinputb'), "change");
			for (i=0;i<lsn.length;i++){lsn[i].fn.call();}

			lsn = E.getListeners(this.el('custonloadb'), "change");
			for (i=0;i<lsn.length;i++){lsn[i].fn.call();}
		},
		save: function(){
			
			this.nameTranslite();
			
			var disabled = this.el('disabled');

			var prm = {
				'cst': {
					'en': this.elv('cust'),
					'inpen': this.elv('custinputb'),
					'inp': this.elv('custinput'),
					'onlden': this.elv('custonloadb'),
					'onld': this.elv('custonload')
				}
			};

			var ftp = this.elv('fldtype');
			var opt = this.getTypeOpt()[ftp];
			switch(ftp){
			case '0':
				prm['def'] = opt['def'].checked ? '1' : '0';
				break;
			case '1': case '2': case '3':
				prm['size'] = opt['size'].value;
				prm['def'] = opt['def'].value;
				break;
			case '4':
				prm['val'] = opt['val'].value;
				prm['def'] = opt['def'].value;
				break;
			case '5':
			case '7':
				break;
			case '6':
			case '8':
				prm['val'] = opt['val'].value;
				break;
			}
			this.row.update({
				'tl': this.elv('title'),
				'nm': this.elv('name'),
				'fldtp': this.elv('fldtype'),
				'dsc': this.elv('descript'),
				'grp': this.elv('group'),
				'grpalt': this.elv('groupalt'),
				'ets': this.elv('eltitlesource'),
				'prms': J.stringify(prm)
			});

			var ds = DATA[this.mmPrefix];
			
			if (this.row.cell['grpalt']){
				ds.get('eloptgroup').getRows().clear();
			}

			ds.get('eloption').applyChanges();
			ds.request();
			this.close();
		}
	});
	NS.OptionEditorPanel = OptionEditorPanel;
	
	
	/**
	 * 
	 * API модуля
	 * 
	 * @class API
	 */
	
	/**
	 * Отобразить виджет списка полей элемента определенного типа <br />
	 * Пример вызова функции: 
	 * <pre>
	 *  Brick.f('catalog', 'eloption', 'showElementOptionWidget', {
	 *    'container': container,
	 *    'eltypeid': 4,
	 *    'mmPrefix': 'eshop'
	 *  });
	 * </pre>
	 * 
	 * @method showElementOptionWidget
	 * @static
	 * @param {Object} config Объект параметров, где: container - HTMLElement, eltypeid - идентификатор типа элемента, mmPrefix - префикс управляющего модуля
	 */
	API.showElementOptionWidget = function(config){
		new ElementOptionsWidget(config.container, config.eltypeid, config.mmPrefix);
	};
	
	/**
	 * Отобразить панель редактора поля элемента определенного типа <br />
	 * Пример вызова функции: 
	 * <pre>
	 *  Brick.f('catalog', 'eloption', 'showElementOptionWidget', {
	 *    'row': row,
	 *    'mmPrefix': 'eshop'
	 *  });
	 * </pre>
	 * 
	 * @method showOptionEditorPanel
	 * @static
	 * @param {Object} config Объект параметров, где: row - DataRow, mmPrefix - префикс управляющего модуля
	 */
	API.showOptionEditorPanel = function(config){
		new OptionEditorPanel(config.row, config.mmPrefix);
	};


};