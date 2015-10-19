var Component = new Brick.Component();
Component.requires = {
    yahoo: ['dom', 'event'],
    mod: [
        {name: 'sys', files: ['application.js', 'item.js', 'container.js']},
        {name: 'widget', files: ['notice.js', 'lib.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    NS.Application = {
        ATTRS: {
            isLoadAppStructure: {value: true},
            Config: {value: NS.Config},
            Catalog: {value: NS.Catalog},
            CatalogList: {value: NS.CatalogList},
            Element: {value: NS.Element},
            ElementList: {value: NS.ElementList},
            ElementType: {value: NS.ElementType},
            ElementTypeList: {value: NS.ElementTypeList},
            ElementOption: {value: NS.ElementOption},
            ElementOptionList: {value: NS.ElementOptionList},
        },
        REQS: {
            config: {
                attach: 'elementTypeList',
                attribute: true,
                type: 'model:Config',
            },
            catalogList: {
                attribute: true,
                type: 'modelList:CatalogList'
            },
            elementTypeList: {
                attribute: true,
                type: 'modelList:ElementTypeList'
            },
            element: {
                attach: 'elementTypeList',
                args: ['elementid'],
                type: 'model:Element'
            },
            elementSave: {
                args: ['elementData']
            },
            elementRemove: {
                args: ['elementid']
            },
            elementList: {
                attach: 'elementTypeList',
                args: ['config'],
                attribute: false,
                type: 'modelList:ElementList'
            },
        }
    };

};