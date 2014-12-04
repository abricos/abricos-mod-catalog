/*
 @package Abricos
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    yahoo: ['animation', 'dragdrop']
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event;

    var DDM = YAHOO.util.DragDropMgr;

    var RowDragItem = function(id, cfg){
        RowDragItem.superclass.constructor.call(this, id, '', cfg);

        var el = Dom.get(id);
        Dom.addClass(el, 'dragitem');

        this.goingUp = false;
        this.lastY = 0;
    };
    YAHOO.extend(RowDragItem, YAHOO.util.DDProxy, {
        startDrag: function(x, y){
            var dragEl = this.getDragEl();
            var clickEl = this.getEl();
            dragEl.innerHTML = clickEl.innerHTML;

            Dom.setStyle(clickEl, "visibility", "hidden");
            Dom.setStyle(dragEl, "backgroundColor", "#FFF");
        },
        onDrag: function(e){
            var y = E.getPageY(e);

            if (y < this.lastY){
                this.goingUp = true;
            } else if (y > this.lastY){
                this.goingUp = false;
            }

            this.lastY = y;
        },
        onDragOver: function(e, id){
            var srcEl = this.getEl();
            var destEl = Dom.get(id);

            if (Dom.hasClass(destEl, 'dragitem')){
                var p = destEl.parentNode;

                if (this.goingUp){
                    p.insertBefore(srcEl, destEl); // insert above
                } else {
                    p.insertBefore(srcEl, destEl.nextSibling); // insert below
                }
                DDM.refreshCache();
            }
        },
        endDrag: function(e){

            var srcEl = this.getEl();
            var proxy = this.getDragEl();

            Dom.setStyle(proxy, "visibility", "");
            var a = new YAHOO.util.Motion(
                proxy, {
                    points: {
                        to: Dom.getXY(srcEl)
                    }
                },
                0.2,
                YAHOO.util.Easing.easeOut
            );
            var proxyid = proxy.id;
            var thisid = this.id;

            a.onComplete.subscribe(function(){
                Dom.setStyle(proxyid, "visibility", "hidden");
                Dom.setStyle(thisid, "visibility", "");
            });
            a.animate();

            NS.life(this.config['endDragCallback'], this, srcEl);
        },
        onDragDrop: function(e, id){
            if (DDM.interactionInfo.drop.length === 1){
                var pt = DDM.interactionInfo.point;
                var region = DDM.interactionInfo.sourceRegion;
                if (!region.intersect(pt)){
                    var destEl = Dom.get(id);
                    var destDD = DDM.getDDById(id);
                    destEl.appendChild(this.getEl());
                    destDD.isEmpty = false;
                    DDM.refreshCache();
                }
            }
        }
    });
    NS.RowDragItem = RowDragItem;

};