var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var E = YAHOO.util.Event,
        L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var CurrencyEditorWidget = function(container, manager, currency, cfg){
        cfg = L.merge({
            'onCancelClick': null,
            'onSave': null
        }, cfg || {});
        CurrencyEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, manager, currency, cfg);
    };
    YAHOO.extend(CurrencyEditorWidget, BW, {
        init: function(manager, currency, cfg){
            this.manager = manager;
            this.currency = currency;
            this.cfg = cfg;
        },
        destroy: function(){
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.unlock();
            }
            CurrencyEditorWidget.superclass.destroy.call(this);
        },
        onLoad: function(){
            if (YAHOO.util.DragDropMgr){
                YAHOO.util.DragDropMgr.lock();
            }
            var currency = this.currency;

            this.elHide('loading');
            this.elShow('view');

            this.elSetValue({
                'title': currency.title,
                'codestr': currency.codestr,
                'codenum': currency.codenum,
                'rateval': currency.rateval,
                'prefix': currency.prefix,
                'postfix': currency.postfix
            });
            this.gel('def').checked = !!currency.isDefault;

            var elTitle = this.gel('title');
            setTimeout(function(){
                try {
                    elTitle.focus();
                } catch (e) {
                }
            }, 100);

            var __self = this;
            E.on(this.gel('id'), 'keypress', function(e){
                if ((e.keyCode == 13 || e.keyCode == 10) && e.ctrlKey){
                    __self.save();
                    return true;
                }
                return false;
            });
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bsave']:
                    this.save();
                    return true;
                case tp['bcancel']:
                    this.onCancelClick();
                    return true;
            }
            return false;
        },
        onCancelClick: function(){
            NS.life(this.cfg['onCancelClick'], this);
        },
        save: function(){
            var cfg = this.cfg;
            var currency = this.currency;
            var sd = {
                'id': currency.id,
                'title': this.gel('title').value,
                'codestr': this.gel('codestr').value,
                'codenum': this.gel('codenum').value,
                'rateval': this.gel('rateval').value,
                'prefix': this.gel('prefix').value,
                'postfix': this.gel('postfix').value,
                'isdefault': this.gel('def').checked ? 1 : 0
            };

            this.elHide('btnsc');
            this.elShow('btnpc');

            var __self = this;
            this.manager.currencySave(currency.id, sd, function(currency){
                __self.elShow('btnsc,btnscc');
                __self.elHide('btnpc,btnpcc');
                NS.life(cfg['onSave'], __self, currency);
            }, currency);
        }
    });
    NS.CurrencyEditorWidget = CurrencyEditorWidget;
};