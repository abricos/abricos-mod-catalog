var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['form.js']},
        {name: '{C#MODNAME}', files: ['multiLang.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TypeEditorWidget = Y.Base.create('typeEditorWidget', SYS.AppWidget, [
        SYS.Form,
        SYS.FormAction
    ], {
        initializer: function(){
            this.publish('onCancelClick');
            this.publish('onSave');
        },
        onInitAppWidget: function(err, appInstance){
            var tp  = this.template;

            this.titleInputWidget = new NS.MultiLangInputWidget({
                boundingBox: tp.gel('title'),
                fieldName: 'title',
                appInstance: appInstance
            });

            this.set('model', this.get('elType'));

            Y.one(this.gel('name')).on('focus', this.nameTranslate, this);
        },
        nameTranslate: function(){
            this.updateModelFromUI();
            var tp = this.template,
                tl = L.trim(tp.gel('title').value),
                nm = L.trim(tp.gel('name').value);
            if (nm.length == 0){
                nm = Brick.util.Translite.ruen(tl);
            }
            this.get('model').set('name', Brick.util.Translite.ruen(nm));
        },
        onSubmitFormAction: function(){
            this.save();
        },
        save: function(){
            this.nameTranslate();

            var attrs = this.get('model').toJSON(),
                elType = this.get('elType'),
                sd = {
                    'tl': attrs.title,
                    'tls': attrs.titleList,
                    'nm': attrs.name,
                    'dsc': ''
                };

            this.set('waiting', true);

            var __self = this;
            this.get('manager').elementTypeSave(elType.id, sd, function(elType){
                __self.set('waiting', false);
                __self.fire('onSave', elType);
            });
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'cancel':
                    this.fire('onCancelClick');
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            manager: {value: null},
            elType: {value: null},
            fromElement: {value: null},
            formFocusField: {value: 'title'}
        }
    });

};