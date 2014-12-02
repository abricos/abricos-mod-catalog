/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: 'filemanager', files: ['attachment.js']},
        {name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var UID = Brick.env.user.id;

    var ElementEditBooleanWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditBooleanWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optboolean'
        }, option, value | 0, cfg);
    };
    YAHOO.extend(ElementEditBooleanWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(option, value, cfg){
            this.gel('val').checked = value > 0 ? 'checked' : '';
        },
        getValue: function(){
            return this.gel('val').checked ? 1 : 0;
        }
    });
    NS.ElementEditBooleanWidget = ElementEditBooleanWidget;


    var ElementEditNumberWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditNumberWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optnumber' // , 'isRowWidget': true
        }, option, value | 0, cfg);
    };
    YAHOO.extend(ElementEditNumberWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditNumberWidget = ElementEditNumberWidget;


    var ElementEditStringWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditStringWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optstring' // , 'isRowWidget': true
        }, option, value, cfg);
    };
    YAHOO.extend(ElementEditStringWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditStringWidget = ElementEditStringWidget;


    var ElementEditDoubleWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditDoubleWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optdouble'
        }, option, value, cfg);
    };
    YAHOO.extend(ElementEditDoubleWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditDoubleWidget = ElementEditDoubleWidget;


    var ElementEditCurrencyWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditCurrencyWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optcurrency'
        }, option, value, cfg);
    };
    YAHOO.extend(ElementEditCurrencyWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditCurrencyWidget = ElementEditCurrencyWidget;


    var ElementEditTableWidget = function(container, manager, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditTableWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'opttable,opttablelist,opttablerow'
        }, manager, option, value, cfg);
    };
    YAHOO.extend(ElementEditTableWidget, BW, {
        buildTData: function(manager, option, value, cfg){
            return {'tl': option.title};
        },
        init: function(manager, option, value, cfg){
            this.manager = manager;
            this.option = option;
            this.value = value;
            this.cfg = cfg;

            this.editValId = 0;
        },
        onLoad: function(manager, option, value, cfg){
            this.renderList();
            this.setValue(value);

            var __self = this;
            E.on(this.gel('input'), 'keypress', function(e){
                if (e.keyCode != 13){
                    return false;
                }
                __self.save();
                return true;
            });
        },
        renderList: function(){
            var TM = this._TM,
                lst = TM.replace('opttablerow', {'id': 0, 'tl': ""});

            this.option.values.foreach(function(dict){
                lst += TM.replace('opttablerow', {
                    'id': dict.id,
                    'tl': dict.title
                });
            });

            this.elSetHTML({
                'table': TM.replace('opttablelist', {'rows': lst})
            });

            var __self = this;
            E.on(this.gel('opttablelist.id'), 'change', function(e){
                __self.render();
            });
        },
        render: function(){
            var val = this.getValue();

            if (val == 0){
                this.elHide('bedit,bremove');
            } else {
                this.elShow('bedit,bremove');
            }
        },
        getValue: function(){
            return this.gel('opttablelist.id').value;
        },
        setValue: function(value){
            this.elSetValue('opttablelist.id', value);
            this.render();
        },
        onClick: function(el, tp){
            var TId = this._TId, tpb = TId['opttable'];
            switch (el.id) {
                case tpb['badd']:
                case tpb['baddc']:
                    this.showEditor(0);
                    return true;
                case tpb['bedit']:
                case tpb['beditc']:
                    this.showEditor(this.getValue());
                    return true;
                case tpb['bremove']:
                case tpb['bremovec']:
                    this.showRemove();
                    return true;
                case tpb['bremcancel']:
                    this.closeRemove();
                    return true;
                case tpb['bremok']:
                    this.remove();
                    return true;
                case tpb['bedadd']:
                case tpb['bedsave']:
                    this.save();
                    return true;
                case tpb['bedcancel']:
                    this.closeEditor();
                    return true;
            }
        },
        showRemove: function(){
            var valueid = this.getValue();
            if (valueid == 0){
                return;
            }

            var value = this.option.values.get(valueid);
            if (L.isNull(value)){
                return;
            }

            var tl = value.title, sblen = 25;
            if (tl.length > sblen){
                tl = tl.substring(0, sblen) + "...";
            }

            this.elSetHTML('remtl', tl);

            this.elHide('view');
            this.elShow('remove');
        },
        closeRemove: function(){
            this.elShow('view');
            this.elHide('remove');
        },
        remove: function(){
            var valueid = this.getValue();
            if (valueid == 0){
                return;
            }

            var __self = this;
            this.elHide('rembtns');
            this.elShow('remprocess');

            var option = this.option;

            this.manager.optionTableValueRemove(option.typeid, option.id, valueid, function(values){
                __self.elHide('remprocess');
                __self.elShow('rembtns');
                __self.closeRemove();

                option.updateValues(values, true);

                __self.renderList();
                __self.setValue(0);
            });
        },
        showEditor: function(valueid){
            valueid = valueid || 0;

            this.editValId = valueid;
            var value = "";

            if (valueid == 0){
                this.elHide('bedsave');
                this.elShow('bedadd');
                this.elSetValue('input', "");
            } else {
                this.elHide('bedadd');
                this.elShow('bedsave');
                value = this.option.values.get(valueid);
                this.elSetValue('input', value.title);
            }

            this.elHide('view');
            this.elShow('editor');

            try {
                this.gel('input').focus();
            } catch (e) {
            }
        },
        closeEditor: function(){
            this.elShow('view');
            this.elHide('editor');
        },
        save: function(){
            var value = L.trim(this.gel('input').value);
            if (value.length == 0){
                return;
            }

            var valueid = this.editValId;

            var __self = this;
            this.elHide('edbtns');
            this.elShow('process');

            var option = this.option;

            this.manager.optionTableValueSave(option.typeid, option.id, valueid, value, function(values, newid){
                __self.elHide('process');
                __self.elShow('edbtns');
                __self.closeEditor();

                option.updateValues(values);

                __self.renderList();
                __self.setValue(newid);
            });
        }
    });
    NS.ElementEditTableWidget = ElementEditTableWidget;

    var ElementEditTextWidget = function(container, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditTextWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'opttext'
        }, option, value, cfg);
    };
    YAHOO.extend(ElementEditTextWidget, BW, {
        buildTData: function(option, value, cfg){
            return {'tl': option.title};
        },
        init: function(option, value, cfg){
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        destroy: function(){
            this.editorWidget.destroy();
            ElementEditTextWidget.superclass.destroy.call(this);
        },
        onLoad: function(option, value, cfg){

            var Editor = Brick.widget.Editor;
            this.editorWidget = new Editor(this.gel('text'), {
                'toolbar': Editor.TOOLBAR_STANDART,
                // 'mode': Editor.MODE_VISUAL,
                'toolbarExpert': false,
                'separateIntro': false
            });

            this.editorWidget.setContent(value);
        },
        getValue: function(){
            return this.editorWidget.getContent();
        }
    });
    NS.ElementEditTextWidget = ElementEditTextWidget;


    var ElementEditElDependsWidget = function(container, manager, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditElDependsWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'opteldepends'
        }, manager, option, value, cfg);
    };
    YAHOO.extend(ElementEditElDependsWidget, BW, {
        buildTData: function(manager, option, value, cfg){
            return {'tl': option.title};
        },
        init: function(manager, option, value, cfg){
            this.manager = manager;
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(manager, option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditElDependsWidget = ElementEditElDependsWidget;

    var ElementEditElDependsNameWidget = function(container, manager, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditElDependsNameWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'opteldependsname'
        }, manager, option, value, cfg);
    };
    YAHOO.extend(ElementEditElDependsNameWidget, BW, {
        buildTData: function(manager, option, value, cfg){
            return {'tl': option.title};
        },
        init: function(manager, option, value, cfg){
            this.manager = manager;
            this.option = option;
            this.value = value;
            this.cfg = cfg;
        },
        onLoad: function(manager, option, value, cfg){
            this.elSetValue('val', value);
        },
        getValue: function(){
            return this.gel('val').value;
        }
    });
    NS.ElementEditElDependsNameWidget = ElementEditElDependsNameWidget;

    var ElementEditFilesWidget = function(container, manager, option, value, cfg){
        cfg = L.merge({}, cfg || {});
        ElementEditFilesWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'optfiles'
        }, manager, option, value, cfg);
    };
    YAHOO.extend(ElementEditFilesWidget, BW, {
        buildTData: function(manager, option, value, cfg){
            return {'tl': option.title};
        },
        init: function(manager, option, value, cfg){
            this.manager = manager;
            this.option = option;
            this.value = value;
            this.cfg = cfg;
            this.uploadWindow = null;
        },
        onLoad: function(manager, option, value, cfg){
            var __self = this, files = [], a = value.split(',');
            for (var i = 0; i < a.length; i++){
                if (a[i].length == 0){
                    continue;
                }
                var aa = a[i].split(':');
                files[files.length] = {
                    'id': aa[0], 'nm': aa[1]
                };
            }
            this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(this.gel('files'), files, {
                'hideFMButton': true,
                'clickFileUploadCallback': function(){
                    __self.showFileUpload();
                }
            });
        },
        getValue: function(){
            var files = this.filesWidget.files, a = [];
            for (var i = 0; i < files.length; i++){
                a[a.length] = files[i]['id'] + ':' + files[i]['nm'];
            }
            return a.join(',');
        },
        showFileUpload: function(){

            ElementEditFilesWidget.uploadFiles = this;

            var man = this.manager;

            if (!L.isNull(this.uploadWindow) && !this.uploadWindow.closed){
                this.uploadWindow.focus();
                return;
            }
            var url = '/catalogbase/uploadoptfiles/' + man.modname + '/' + this.option.id + '/';
            this.uploadWindow = window.open(
                url, 'catalogimage',
                'statusbar=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=550,height=500'
            );
            NS.activeImageList = this;
        },
        filesAdd: function(files){
            if (!L.isArray(files)){
                return;
            }
            for (var i = 0; i < files.length; i++){
                this.filesWidget.appendFile(files[i]);
            }
        }
    });
    ElementEditFilesWidget.uploadFiles = null;
    NS.ElementEditFilesWidget = ElementEditFilesWidget;

    var ElementEditorWidget = function(container, manager, element, cfg){
        cfg = L.merge({
            'fromElement': null,
            'onCancelClick': null,
            'onSaveElement': null
        }, cfg || {});
        ElementEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget,tpwidget,tplist,tprow,optnumber,nofoto'
        }, manager, element, cfg);
    };
    YAHOO.extend(ElementEditorWidget, BW, {
        init: function(manager, element, cfg){
            this.manager = manager;
            this.element = element;
            this.cfg = cfg;
            this.fotosWidget = null;
            this.wsOptions = [];
        },
        buildTData: function(manager, element, cfg){
            var sTypeList = "", TM = this._TM;
            var tpList = manager.typeList;

            if (tpList.count() > 1){
                var lst = "";
                tpList.foreach(function(tp){
                    if (manager.cfg.elementCreateBaseTypeDisable && tp.id == 0){
                        return;
                    }
                    lst += TM.replace('tprow', {
                        'id': tp.id, 'tl': tp.title
                    });
                });
                sTypeList = TM.replace('tpwidget', {'tplist': TM.replace('tplist', {'rows': lst})});
            }
            return {'typelist': sTypeList};
        },
        destroy: function(){
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.unlock();
            }
            ElementEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(manager, element){
            var cfg = this.cfg, fel = cfg['fromElement'];
            if (!L.isNull(fel)){
                if (!L.isNull(fel.detail)){
                    this._onLoadElement(fel.copy());
                } else {
                    var __self = this;
                    manager.elementLoad(fel.id, function(fel){
                        __self._onLoadElement(fel.copy());
                    }, fel);
                }
            } else {
                if (!L.isNull(element.detail)){
                    this._onLoadElement(element);
                } else {
                    var __self = this;
                    manager.elementLoad(element.id, function(element){
                        __self._onLoadElement(element);
                    }, element);
                }
            }
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.lock();
            }
        },
        _onLoadElement: function(element){
            this.element = element;

            var man = this.manager;

            if (element.id == 0){
                this.elShow('bcreate,bcreatec,newflagtl');
            } else {
                this.elShow('bsave,bsavec');
            }

            if (man.cfg['elementNameChange']){
                this.elShow('fnm');
            }

            if (man.cfg['versionControl']){
                this.elShow('versioncontrol');
            }

            if (man.roles['isAdmin']){
                this.elShow('seo,ford');
            }

            var rootCatItem = this.manager.catalogList.get(0);
            if (rootCatItem.childs.count() == 0){
                this.elHide('fcatalog');
            }

            this.elHide('loading');
            this.elShow('view');

            var __self = this, dtl = element.detail;

            this.catSelectWidget = new NS.CatalogSelectWidget(this.gel('catalog'), this.manager, {
                'value': element.catid
            });

            this.elSetValue({
                'tl': element.title,
                'nm': element.name,
                'ord': element.order,
                'mtl': dtl.metaTitle,
                'mks': dtl.metaKeys,
                'mdsc': dtl.metaDesc,
                'chlg': dtl.changeLog
            });

            this.fotosWidget = new NS.FotoListEditWidget(this.gel('fotos'), this.manager, this.element.detail.fotos);

            if (element.id == 0 && man.cfg.elementCreateBaseTypeDisable){
                var elType = man.typeList.getByIndex(1);
                if (L.isValue(elType)){
                    element.typeid = elType.id;
                }
            }

            this.renderOptions();
            this.elSetValue('tplist.id', element.typeid);

            E.on(this.gel('tplist.id'), 'change', function(e){
                __self.elementTypeChange();
            });

            var keypress = function(e){
                if (e.keyCode != 13){
                    return false;
                }
                __self.save();
                return true;
            };
            E.on(this.gel('tl'), 'keypress', keypress);

            var elTitle = this.gel('tl');
            setTimeout(function(){
                try {
                    elTitle.focus();
                } catch (e) {
                }
            }, 100);

            if (man.cfg['elementNameUnique']){
                if (element.id > 0){
                    this.elDisable('nm');
                    if (man.roles['isAdmin'] && element.isModer){
                        this.elShow('bmoder,bmoderc');
                    }
                } else {
                    E.on(this.gel('nm'), 'change', function(e){
                        __self._onNameChange();
                    });
                    E.on(this.gel('nm'), 'keyup', function(e){
                        __self._onNameChange();
                    });
                    if (this.gel('nm').value.length > 0){
                        this._onNameChange();
                    }
                }
            }
        },
        _nameUniqueSetNewVersionStatus: function(info){
            this.elHide('uniquenameloading');

            if (info['elementid'] > 0){
                var roles = this.manager.roles;
                if (roles['isOperatorOnly'] && (info['userid'] != UID || info['ismoder'] == 1)){
                    this.elHide('bsavenewv,bsavenewvc,bcreate,bcreatec');
                } else {
                    this.elShow('bsavenewv,bsavenewvc');
                    this.elHide('bcreate,bcreatec');
                }
            } else {
                this.elHide('bsavenewv,bsavenewvc');
                this.elShow('bcreate,bcreatec');
            }
        },
        _onNameChange: function(){
            if (this._nameChangeLock){
                return;
            }
            this._nameChangeLock = true;
            var nm = this.gel('nm').value;
            nm = nm.replace(/[^a-zA-Z0-9\-\_]/, '');
            this.gel('nm').value = nm;
            this._nameChangeLock = false;

            if (!this._cacheCheckUniqueName){
                this._cacheCheckUniqueName = {};
            }
            if (this._cacheCheckUniqueName[nm]){
                this._nameUniqueSetNewVersionStatus(this._cacheCheckUniqueName[nm]);
                return;
            }

            this.elShow('uniquenameloading');

            // проверка на новую версию
            this._currentCheckUniqueName = nm;
            var __self = this, man = this.manager;
            setTimeout(function(){
                if (__self._currentCheckUniqueName != nm){
                    return;
                } // уже идет новая проверка

                man.elementIdByName(nm, function(info){
                    if (__self._currentCheckUniqueName != nm){
                        return;
                    } // уже идет новая проверка

                    __self._cacheCheckUniqueName[nm] = info;
                    __self._nameUniqueSetNewVersionStatus(info);
                });

            }, 1000);
        },
        _wsClear: function(){
            var ws = this.wsOptions;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            this.wsOptions = [];
        },
        renderOptions: function(){
            this._wsClear();
            var ws = [], elList = this.gel('optlist');
            var man = this.manager;

            this.element.detail.foreach(function(option, value){
                var div = document.createElement('div');
                elList.appendChild(div);

                switch (option.type) {
                    case NS.FTYPE['BOOLEAN']:
                        ws[ws.length] = new NS.ElementEditBooleanWidget(div, option, value);
                        break;
                    case NS.FTYPE['CURRENCY']:
                        ws[ws.length] = new NS.ElementEditCurrencyWidget(div, option, value);
                        break;
                    case NS.FTYPE['NUMBER']:
                        ws[ws.length] = new NS.ElementEditNumberWidget(div, option, value);
                        break;
                    case NS.FTYPE['STRING']:
                        ws[ws.length] = new NS.ElementEditStringWidget(div, option, value);
                        break;
                    case NS.FTYPE['DOUBLE']:
                        ws[ws.length] = new NS.ElementEditDoubleWidget(div, option, value);
                        break;
                    case NS.FTYPE['TABLE']:
                        ws[ws.length] = new NS.ElementEditTableWidget(div, man, option, value);
                        break;
                    case NS.FTYPE['TEXT']:
                        ws[ws.length] = new NS.ElementEditTextWidget(div, option, value);
                        break;
                    case NS.FTYPE['ELDEPENDS']:
                        ws[ws.length] = new NS.ElementEditElDependsWidget(div, man, option, value);
                        break;
                    case NS.FTYPE['ELDEPENDSNAME']:
                        ws[ws.length] = new NS.ElementEditElDependsNameWidget(div, man, option, value);
                        break;
                    case NS.FTYPE['FILES']:
                        ws[ws.length] = new NS.ElementEditFilesWidget(div, man, option, value);
                        break;
                }
            });
            this.wsOptions = ws;
        },
        elementTypeChange: function(){
            var tpid = this.gel('tplist.id').value | 0;
            if (this.element.typeid == tpid){
                return;
            }

            this.element.typeid = tpid;
            this.renderOptions();
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bcreate']:
                case tp['bcreatec']:
                case tp['bsave']:
                case tp['bsavec']:
                case tp['bsavenewv']:
                case tp['bsavenewvc']:
                    this.save();
                    return true;
                case tp['bmoder']:
                case tp['bmoderc']:
                    this.moder();
                    return true;
                case tp['bcancel']:
                case tp['bcancelc']:
                    this.onCancelClick();
                    return true;
            }
            return false;
        },
        onCancelClick: function(){
            NS.life(this.cfg['onCancelClick'], this);
        },
        moder: function(){
            this.elHide('btnsc,btnscc');
            this.elShow('btnpc,btnpcc');

            var __self = this, cfg = this.cfg, element = this.element;
            this.manager.elementModer(element.id, function(element){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc,bmoder,bmoderc');
                NS.life(cfg['onSaveElement'], __self, element);
            }, element);
        },
        save: function(){
            var cfg = this.cfg;
            var vals = {};
            var ws = this.wsOptions;
            for (var i = 0; i < ws.length; i++){
                var w = ws[i];
                var tpid = w.option.typeid;

                vals[tpid] = vals[tpid] || {};
                vals[tpid][w.option.name] = w.getValue();
            }

            var element = this.element;
            var sd = {
                'catid': this.catSelectWidget.getValue(),
                'tpid': element.typeid,
                'tl': this.gel('tl').value,
                'nm': this.gel('nm').value,
                'fotos': this.fotosWidget.fotos,
                'values': vals,
                'ord': this.gel('ord').value,
                'mtl': this.gel('mtl').value,
                'mks': this.gel('mks').value,
                'mdsc': this.gel('mdsc').value,
                'chlg': this.gel('chlg').value
            };

            this.elHide('btnsc,btnscc');
            this.elShow('btnpc,btnpcc');

            var __self = this;
            this.manager.elementSave(element.id, sd, function(element){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc');
                NS.life(cfg['onSaveElement'], __self, element);
            }, element);
        }
    });
    NS.ElementEditorWidget = ElementEditorWidget;
};