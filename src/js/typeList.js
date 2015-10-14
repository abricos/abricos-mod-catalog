var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.ElementTypeSelectWidget = Y.Base.create('elementTypeSelectWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            appInstance.config(function(){
                this.renderElementTypeList();
            }, this);
        },
        renderElementTypeList: function(){
            var appInstance = this.get('appInstance'),
                config = appInstance.get('config'),
                elementTypeList = appInstance.get('elementTypeList');

            if (!elementTypeList){
                return;
            }
            this.set('waiting', false);

            var tp = this.template,
                lst = "";

            elementTypeList.each(function(elementType){
                if (config.get('elementBaseTypeDisable') && elementType.get('id') === 0){
                    return;
                }
                lst += tp.replace('row', {
                    id: elementType.get('id'),
                    title: elementType.get('title').get()
                });
            }, this);
            tp.setHTML('id', lst);
            tp.setValue('id', this.get('selected'));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,row'},
            selected: {value: 0}
        }
    });

};