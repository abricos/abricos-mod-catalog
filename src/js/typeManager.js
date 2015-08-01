var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['typeView.js', 'typeEditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TypeManagerWidget = Y.Base.create('typeManagerWidget', SYS.AppWidget, [], {
        typeEditor: null,

        onInitAppWidget: function(err, appInstance){
            var tp = this.template;

            var w = this.typeListWidget = new NS.TypeListWidget({
                appInstance: appInstance,
                boundingBox: tp.gel('list')
            });
            w.on('rowMenuClick', this._onRowMenuClick, this);
        },

        _onRowMenuClick: function(e){
            var elType = e.widget.get('elementType');
            switch (e.action) {
                case 'edit':
                    this.showTypeEditor(elType.get('name'));
                    break;
            }
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'create':
                    this.showTypeEditor();
                    return true;
            }
        },
        showTypeEditor: function(elType){
            if (this.typeEditor){
                return;
            }

            if (!elType){
                var ElementType = this.get('appInstance').get('ElementType');
                elType = new ElementType();
            } else {
                if (Y.Lang.isString(elType)){
                    elType = this.typeListWidget.get('elementTypeList').getById(elType);
                }
            }
            if (!elType){
                return;
            }
            var tp = this.template,
                appInstance = this.get('appInstance'),
                container = Y.one(this.gel('eltypeeditor'))
                    .appendChild(Y.Node.create('<div></div>'));

            this.elTypeEditor = new NS.TypeEditorWidget({
                boundingBox: container,
                appInstance: appInstance,
                elType: elType
            });

            this.elTypeEditor.on('onCancelClick', this.closeElTypeEditor, this);
            this.elTypeEditor.on('onSave', this.render, this);

            Y.one(tp.gel('eltypeviewer')).addClass('hide');
            Y.one(tp.gel('eltypeeditor')).removeClass('hide');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            elementTypeList: {value: null},
            elTypeEditor: {value: null},
            elTypeViewer: {value: null}
        }
    });

    NS.TypeListWidget = Y.Base.create('typeListWidget', SYS.AppWidget, [], {

        _wsList: [],

        onInitAppWidget: function(err, appInstance){
            this.publish('rowMenuClick');

            this.set('waiting', true);

            this.get('appInstance').elementTypeList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('elementTypeList', result.elementTypeList);
                }
                this.renderElementTypeList();
            }, this);
        },
        destructor: function(){
            this._clearWidgetList();
        },
        _clearWidgetList: function(){
            var tp = this.template,
                ws = this._wsList;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }

            tp.gel('list').innetHTML = '';
        },
        renderElementTypeList: function(){
            this._clearWidgetList();

            var appInstance = this.get('appInstance'),
                tp = this.template,
                ws = this._wsList,
                elList = Y.one(tp.gel('list'));

            this.get('elementTypeList').each(function(elType){
                var div = Y.Node.create('<div></div>');
                div.elType = elType;
                elList.appendChild(div);

                var w = new NS.TypeRowWidget({
                    boundingBox: div,
                    appInstance: appInstance,
                    elementType: elType
                });
                ws[ws.length] = w;

                w.on('menuClick', this._onMenuClick, this);

            }, this);

            this.set('selected', '__base');
        },
        _onMenuClick: function(e, act){
            this.fire('rowMenuClick', {widget: e.target, action: act});
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'list'},
            elementTypeList: {value: null},
            selected: {value: null}
        }
    });

    NS.TypeRowWidget = Y.Base.create('typeRowWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var data = this.get('elementType').toJSON(true);
            if (data.name === '__base'){
                data.title = Abricos.Language.get('mod.catalog.lib.element.type.base');
            }
            return data;
        },
        onInitAppWidget: function(err, appInstance){
            this.publish('menuClick');

            var tp = this.template,
                elType = this.get('elementType');

            if (elType.get('name') === '__base'){
                Y.one(tp.gel('menu')).hide();
            }
        },

        onClick: function(e){
            switch (e.dataClick) {
                case 'edit':
                case 'copy':
                case 'remove':
                    this.fire('menuClick', e.dataClick);
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'row'},
            elementType: {value: null}
        }
    });
};