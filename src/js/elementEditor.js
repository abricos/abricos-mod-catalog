var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: 'filemanager', files: ['attachment.js']},
        {name: '{C#MODNAME}', files: ['typeList.js', 'fotoeditor.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){


    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.ElementEditorWidget = Y.Base.create('elementEditorWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this._optionsWidgets = [];
            this.set('waiting', true);
            appInstance.config(function(err, result){
                var elementid = this.get('elementid');
                if (elementid === 0){

                    var elementTypeList = appInstance.get('elementTypeList'),
                        config = result.config,
                        elTypeId = 0;

                    if (config.get('elementBaseTypeDisable')){
                        var elType = elementTypeList.item(1);
                        if (!elType){
                            throw 'ElementTypeList is empty';
                        }
                        elTypeId = elType.get('id');
                    }

                    var Element = appInstance.get('Element'),
                        element = new Element({
                            appInstance: appInstance,
                            elTypeId: elTypeId
                        });
                    this.set('element', element);
                    this._renderEditor();
                } else {
                    appInstance.element(elementid, function(err, result){
                        if (!err){
                            this.set('element', result.element);
                        }
                        this._renderEditor();
                    }, this);
                }
            }, this);
        },
        destructor: function(){
            if (this._typeSelectWidget){
                this._typeSelectWidget.destroy();
            }
        },
        _renderEditor: function(){
            this.set('waiting', false);

            var appInstance = this.get('appInstance'),
                elementTypeList = appInstance.get('elementTypeList'),
                config = appInstance.get('config'),
                element = this.get('element');

            if (!element){
                return;
            }
            var tp = this.template,
                elType = elementTypeList.getById(element.get('elTypeId'));

            this._typeSelectWidget = new NS.ElementTypeSelectWidget({
                appInstance: appInstance,
                srcNode: tp.one('typeSelect'),
                selected: elType.get('id')
            });

            this._typeSelectWidget.after('selectedChange', this._renderOptions, this);
            this._renderOptions();
        },
        _cleanOptionWidgets: function(){
            var ws = this._optionsWidgets;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
            return this._optionsWidgets = [];
        },
        _renderOptions: function(){
            var tp = this.template,
                appInstance = this.get('appInstance'),
                elementTypeList = appInstance.get('elementTypeList'),
                elTypeId = this._typeSelectWidget.get('selected'),
                elType = elementTypeList.getById(elTypeId),
                element = this.get('element'),
                ws = this._cleanOptionWidgets();

            tp.toggleView(elType.get('composite') === '', 'titleField');

            elementTypeList.optionEach(elTypeId, function(option){
                var type = option.get('type'),
                    OptionWidget = OWS[type];

                if (!OptionWidget){
                    return;
                }
                ws[ws.length] = new OptionWidget({
                    appInstance: appInstance,
                    srcNode: tp.append('options', '<div></div>'),
                    option: option
                });
            }, this);
        },
        toJSON: function(){
            var ws = this._optionsWidgets,
                values = {};

            for (var i = 0; i < ws.length; i++){
                values = Y.merge(values, ws[i].toJSON());
            }
            return {
                id: this.get('element').get('id'),
                elTypeId: this._typeSelectWidget.get('selected'),
                values: values
            };
        },
        save: function(){
            var data = this.toJSON();

            this.set('waiting', true);
            this.get('appInstance').elementSave(data, function(){
                this.set('waiting', false);
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            elementid: {value: 0},
            element: {}
        },
        CLICKS: {
            save: 'save'
        }
    });

    var OWS = NS.ElementEditorWidget.OptionWidgets = {};

    var OptionWidgetExt = function(){

    };
    OptionWidgetExt.NAME = 'optionWidget';
    OptionWidgetExt.ATTRS = {
        component: {value: COMPONENT},
        option: {}
    };
    OptionWidgetExt.prototype = {
        buildTData: function(){
            var option = this.get('option');
            return {
                title: option.get('title').get()
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                option = this.get('option');
        },
        toJSON: function(){
            var d = {},
                option = this.get('option');
            d[option.get('name')] = this.getValue();
            return d;
        },
        getValue: function(){
            return '';
        }
    };
    OWS.OptionWidgetExt = OptionWidgetExt;

    OWS[NS.FTYPE.STRING] = Y.Base.create('optionWidget', SYS.AppWidget, [
        OWS.OptionWidgetExt
    ], {}, {
        ATTRS: {
            templateBlockName: {value: 'optionString'},
        }
    });
};