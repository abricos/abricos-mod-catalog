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
                    this.renderEditor();
                } else {
                    appInstance.element(elementid, function(err, result){
                        if (!err){
                            this.set('element', result.element);
                        }
                        this.renderEditor();
                    }, this);
                }
            }, this);
        },
        destructor: function(){
            if (this.typeSelectWidget){
                this.typeSelectWidget.destroy();
            }
        },
        renderEditor: function(){
            this.set('waiting', false);

            var appInstance = this.get('appInstance'),
                elementTypeList = appInstance.get('elementTypeList'),
                config = appInstance.get('config'),
                element = this.get('element');

            if (!element){
                return;
            }
            var tp = this.template;

            this.typeSelectWidget = new NS.ElementTypeSelectWidget({
                appInstance: appInstance,
                srcNode: tp.one('typeSelect'),
                selected: element.get('elTypeId')
            });

            console.log(config.toJSON());
            console.log(element.toJSON());

            elementTypeList.each(function(elementType){

            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            elementid: {value: 0},
            element: {}
        }
    });
};