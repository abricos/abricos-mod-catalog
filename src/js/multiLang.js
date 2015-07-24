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
        onInitAppWidget: function(err, appInstance){
            console.log(this);
            /*
            this.publish('layerChange');

            this.set('waiting', true);

            appInstance.fullData(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('layerList', result.layerList);
                }
                this.onLoadMapData();
            }, this);
            /**/
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {
                value: 'widget'
            }
        }
    });

};

