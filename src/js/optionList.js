var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['optioneditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.OptionListWidget = Y.Base.create('optionListWidget', SYS.AppWidget, [], {

        _newEditorWidget: null,
        _wsList: [],

        onInitAppWidget: function(err, appInstance){
            var tp = this.template;

            var w = this.typeListWidget = new NS.TypeListWidget({
                appInstance: appInstance,
                boundingBox: tp.gel('list')
            });
            w.on('rowMenuClick', this._onRowMenuClick, this);
        },

        onClick: function(e){
            switch (e.dataClick) {
                case 'create':
                    this.showTypeEditor();
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            elementType: {value: null}
        }
    });

};