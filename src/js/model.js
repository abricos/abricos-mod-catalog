var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']},
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.FTYPE = {
        BOOLEAN:        0,
        NUMBER:         1,
        DOUBLE:         2,
        STRING:         3,
        // 'LIST':		4,
        TABLE:          5,
        TEXT:           7,
        ELDEPENDS:      9,
        ELDEPENDSNAME:  10,
        FILES:          11,
        CURRENCY:       12
    };

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });

    NS.Catalog = Y.Base.create('catalog', SYS.AppModel, [], {
        structureName: 'Catalog'
    });

    NS.CatalogList = Y.Base.create('catalogList', SYS.AppModelList, [], {
        appItem: NS.Catalog
    });

    NS.Element = Y.Base.create('element', SYS.AppModel, [], {
        structureName: 'Element'
    });

    NS.ElementList = Y.Base.create('elementList', SYS.AppModelList, [], {
        appItem: NS.Element
    });

    NS.ElementType = Y.Base.create('elementType', SYS.AppModel, [], {
        structureName: 'ElementType'
    });

    NS.ElementTypeList = Y.Base.create('elementTypeList', SYS.AppModelList, [], {
        appItem: NS.ElementType
    });

    NS.ElementOption = Y.Base.create('elementOption', SYS.AppModel, [], {
        structureName: 'ElementOption'
    });

    NS.ElementOptionList = Y.Base.create('elementOption', SYS.AppModelList, [], {
        appItem: NS.ElementOption
    });

};