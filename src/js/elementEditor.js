var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: 'filemanager', files: ['attachment.js']},
        {name: '{C#MODNAME}', files: ['fotoeditor.js', 'lib.js']}
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

                    var config = result.config,
                        elementTypeId = 0;

                    var Element = appInstance.get('Element'),
                        element = new Element({
                            appInstance: appInstance
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

            console.log(config.toJSON());
            console.log(element.toJSON());


            elementTypeList.each(function(elementType){
                console.log(elementType.toJSON());
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