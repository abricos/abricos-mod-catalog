var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['optionlist.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var TypeViewWidget = function(container, manager, elType, cfg){
        cfg = L.merge({}, cfg || {});
        TypeViewWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, manager, elType, cfg);
    };
    YAHOO.extend(TypeViewWidget, BW, {
        init: function(manager, elType, cfg){
            this.manager = manager;
            this.cfg = cfg;
            this.elType = elType;

            this.optionListWidget = null;
        },
        setElType: function(elType){
            this.elType = elType;

            this.render();
        },
        render: function(){
            var elType = this.elType;
            this.elSetHTML({
                'tl': elType.title
            });

            if (L.isValue(this.optionListWidget)){
                this.optionListWidget.destroy();
            }
            this.optionListWidget = new NS.OptionListWidget(this.gel('options'), this.manager, elType.optionList);
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['baddoption']:
                    this.optionListWidget.showNewEditor();
                    break;
            }
            return false;
        }
    });
    NS.TypeViewWidget = TypeViewWidget;
};