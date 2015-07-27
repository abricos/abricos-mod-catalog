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

    NS.MultiLangInputWidget = Y.Base.create('multiLangInputWidget', SYS.AppWidget, [], {
        /*
         initializer: function(){
         this.after('fieldNameChange', this._afterFieldNameChange, this);
         },
         _afterFieldNameChange: function(){

         },
         /**/
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                lst = "",
                langs = Brick.env.languages,
                fieldName = this.get('fieldName');

            for (var i = 0; i < langs.length; i++){
                var lang = langs[i];
                lst += tp.replace('input', {
                    lang: lang,
                    name: fieldName + '_' + lang
                });
            }
            tp.gel('id').innerHTML = lst;
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {
                value: 'widget,input'
            },
            fieldName: {
                value: null
            }
        }
    });

};

