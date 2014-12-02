/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['old-form.js', 'editor.js']},
        {name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var OptionFromSelectWidget = function(container, manager, curOption, cfg){
        cfg = L.merge({
            'onChange': null
        }, cfg || {});

        OptionFromSelectWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'select,option'
        }, manager, curOption, cfg);
    };
    YAHOO.extend(OptionFromSelectWidget, BW, {
        buildTData: function(manager, curOption, cfg){
            var lst = "", TM = this._TM;
            manager.typeList.foreach(function(elType){
                elType.optionList.foreach(function(option){
                    lst += TM.replace('option', {
                        'id': option.id,
                        'tl': elType.title + ' / ' + option.title
                    });
                });
            });
            return {
                'rows': lst
            };
        },
        onLoad: function(manager, curOption, cfg){
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
    NS.OptionFromSelectWidget = OptionFromSelectWidget;

    var OptionGroupSelectWidget = function(container, manager, cfg){
        cfg = L.merge({
            'value': null,
            'onChange': null
        }, cfg || {});

        OptionGroupSelectWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'select,option'
        }, manager, cfg);
    };
    YAHOO.extend(OptionGroupSelectWidget, BW, {
        buildTData: function(manager, cfg){
            var lst = "", TM = this._TM;
            manager.optionGroupList.foreach(function(group){
                lst += TM.replace('option', {
                    'id': group.id,
                    'tl': group.title
                });
            });
            return {'rows': lst};
        },
        onLoad: function(manager, cfg){
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
    NS.OptionGroupSelectWidget = OptionGroupSelectWidget;

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
            'onSave': null
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
            this.paramWidget = null;
        },
        destroy: function(){
            OptionEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(manager, option){
            var __self = this;
            this.elSetValue({
                'tl': option.title,
                'nm': option.name,
                'sz': option.size,
                'ord': option.order
            });

            if (option.id > 0){
                this._lastFType = option.type;
            } else {
                this.elShow('fcopyfrom');
            }

            this.fTypeSelectWidget = new NS.OptionFTypeSelectWidget(this.gel('ftypesel'), {
                'value': option.type,
                'onChange': function(){
                    __self.refreshFType();
                }
            });

            this.fromTypeSelectWidget = new NS.OptionFromSelectWidget(this.gel('copyfrom'), manager, option, {
                'onChange': function(optionid){
                    __self.copyFrom(optionid);
                }
            });

            this.optionGroupSelectWidget = new NS.OptionGroupSelectWidget(this.gel('optgroup'), manager, {
                'value': option.groupid
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

            this.refreshFType();

            E.on(this.gel('nm'), 'focus', function(e){
                __self.nameTranslate();
            });
        },
        copyFrom: function(optionid){
            var option = this.manager.typeList.getOption(optionid);
            if (!L.isValue(option)){
                return;
            }

            this.fTypeSelectWidget.setValue(option.type);
            this.refreshFType();

            this.elSetValue({
                'tl': option.title,
                'nm': option.name,
                'sz': option.size
            });
        },
        nameTranslate: function(){
            var tl = L.trim(this.gel('tl').value),
                nm = L.trim(this.gel('nm').value);
            if (nm.length == 0){
                nm = Brick.util.Translite.ruen(tl);
            }

            this.elSetValue({
                'nm': Brick.util.Translite.ruen(nm)
            });
        },
        refreshFType: function(){
            var fType = this.fTypeSelectWidget.getValue() | 0;
            switch (fType) {
                case NS.FTYPE['NUMBER']:
                case NS.FTYPE['DOUBLE']:
                case NS.FTYPE['STRING']:
                    this.elShow('fsize');
                    break;
                default:
                    this.elHide('fsize');
                    break;
            }

            if (this._lastFType != fType){
                this._lastFType = fType;
                switch (fType) {
                    case NS.FTYPE['NUMBER']:
                        this.elSetValue('sz', '10');
                        break;
                    case NS.FTYPE['DOUBLE']:
                    case NS.FTYPE['CURRENCY']:
                        this.elSetValue('sz', '10,2');
                        break;
                    case NS.FTYPE['STRING']:
                        this.elSetValue('sz', '255');
                        break;
                }
            }
            if (L.isValue(this.paramWidget)){
                this.paramWidget.destroy();
                this.paramWidget = null;
            }
            if (L.isValue(NS.OptionEditorWidget.paramEditor[fType])){
                this.paramWidget = new NS.OptionEditorWidget.paramEditor[fType](
                    this.gel('param'), this.manager, this.option
                );
                this.elShow('fparam');
            } else {
                this.elHide('fparam');
            }

        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bsave']:
                case tp['bsavec']:
                    this.save();
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
        save: function(){
            this.nameTranslate();
            var cfg = this.cfg, option = this.option;
            var sd = {
                'tl': this.gel('tl').value,
                'nm': this.gel('nm').value,
                'sz': this.gel('sz').value,
                'ord': this.gel('ord').value,
                'tp': this.fTypeSelectWidget.getValue(),
                'tpid': option.typeid,
                'gid': this.optionGroupSelectWidget.getValue(),
                'prm': L.isValue(this.paramWidget) ? this.paramWidget.getValue() : ''
            };

            this.elHide('btnsc,btnscc');
            this.elShow('btnpc,btnpcc');

            var __self = this;
            this.manager.optionSave(option.id, sd, function(){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc');
                NS.life(cfg['onSave'], __self);
            });
        }
    });
    NS.OptionEditorWidget = OptionEditorWidget;

    NS.OptionEditorWidget.paramEditor = {};

    var OptionTypeFilesEditParamWidget = function(container, manager, option){
        OptionTypeFilesEditParamWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'ftefiles'
        }, manager, option);
    };
    YAHOO.extend(OptionTypeFilesEditParamWidget, BW, {
        init: function(manager, option){
            this.manager = manager;
            this.option = option;
        },
        onLoad: function(manager, option, cfg){
            this.elSetValue({
                'prm': option.param
            });
        },
        getValue: function(){
            return this.gel('prm').value;
        }
    });
    NS.OptionTypeFilesEditParamWidget = OptionTypeFilesEditParamWidget;
    NS.OptionEditorWidget.paramEditor[NS.FTYPE.FILES] = OptionTypeFilesEditParamWidget;

};