Ext.BLANK_IMAGE_URL = './ext/resources/images/default/s.gif';

Ext.onReady(function() {
    Ext.QuickTips.init();

    var view_port = new Ext.Panel({
        id: 'viewport',
        renderTo: Ext.getBody(),
        height: 400,
        layout: 'border',
        items: [{
            collapseMode: 'mini',
            height: 400,
            maxWidth:0,
            minWidth:0,
            region: 'east',
            split: true,
            width: 20
        },{
            collapseMode: 'mini',
            height: 400,
            html: '<div id="view_port"></div>',
            maxHeight:0,
            maxWidth:0,
            minHeight:0,
            minWidth:0,            
            region: 'center',
            split: true,
            width: 760
        },{
            collapseMode: 'mini',
            height: 400,
            itemId: 'view_port',
            maxWidth:0,
            minWidth:0,
            region: 'west',
            split: true,
            width: 20
        }],
        width: 800        
    })

    var panel = new Ext.Panel({
        renderTo: 'view_port',
        width: 790,
        height: 400,
        layout: 'border',
        items: [{
            height: 460,
            html: 'hello',
            maxHeight:0,
            maxWidth:0,
            minHeight:0,
            minWidth:0,
            region: 'center',
            split: true,
            width: 740
        },{
            collapseMode: 'mini',
            height: 20,
            maxHeight:0,
            minHeight:0,
            region: 'north',
            split: true,
            width: 760
        },{
            collapseMode: 'mini',
            height: 20,
            maxHeight:0,
            minHeight:0,            
            region: 'south',
            split: true,
            width: 760
        },{
            collapseMode: 'mini',
            height: 300,
            maxWidth:0,            
            minWidth:0,
            region: 'west',
            split: true,
            width: 20         
        }]
    })

    var ResizableExample = {
        init : function() {
    
            var basic = new Ext.Resizable('viewport', {
                animate: false,
                draggable: true,
                dynamic: true,
//                duration:.6,
//                easing: 'backIn',
//                handles: 'all',
                height: 405,
                minHeight: 405,
                minWidth: 805,
                pinned: false,
                transparent: true,
                width: 805
            });
        }
    };
    
    ResizableExample.init();
});


/*
                 xtype: 'panel'
                id: 'basic',
                renderTo: 'viewport',
                width: 700,
                height: 500,
                layout: 'border',
                items: [{
                    collapseMode: 'mini',            
                    height: 20,
                    minSize: 20,
                    maxSize: 250,
                    region: 'north',
                    split: true
                },{
                    collapseMode: 'mini',           
                    height: 20,
                    minSize: 20,
                    maxSize: 250,
                    region: 'south',
                    split: true
                },{
                    collapseMode: 'mini',
                    minSize: 20,
                    maxSize: 350,            
                    region:'west',
                    split: true,
                    width: 20
                },{
                    collapseMode: 'mini',
                    minSize: 20,
                    maxSize: 350,
                    region:'east',
                    split: true,            
                    width: 20
                 },{
                    region: 'center',     
                    layout: 'fit'
                }]
            }              
    var panel = new Ext.Panel({
        id:'simplestbl',
        height:500,
        layout:'border',
        renderTo:Ext.getBody(),
        title:Ext.getDom('page-title').innerHTML, 
        items: [{
            border:false,
            frame:true,
            html:
                 '<div id="viewport" style="margin:4px">' +
                 '</div>',
            layout:'fit',
            region:'center',
            width:1024
        },{
            border:false,
            collapsible:true,
            collapseMode:'mini',
            region:'east',
            layout:'fit',
            frame:true,
            split:true,
            width:200
        }],
        width:1024
    });
*/
/*
    var viewport_container = new Ext.Viewport({
        id: 'container',
        layout: 'absolute',
        border: false,
        items:[{
                autoScroll:true,
                bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                border:false,
                collapseMode: 'mini',
                height:10,                
                html: 
                    '<div id="viewport" style="height:200;width:500;">' +
                    '</div>',
                region: 'north',
                split:true
            },{
                autoScroll:true,
                bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                border:false,
                collapseMode: 'mini',
                html: 'left',
                region: 'west',
                split:true,
                width:10
            },{
                autoScroll:true,
                bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                border:false,
                collapseMode: 'mini',
                html: 'bottom',
                region: 'south',
                split:true,
                height:10
            },{
                autoScroll:true,
                bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                border:false,
                collapseMode: 'mini',
                html: 'right',
                region: 'east',
                split:true,
                width:10
            },{
                autoScroll:true,
                bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                border:false,
                collapseMode: 'mini',
                html: 'center',
                region: 'center',
                split:true
        }]
    });
*/
/*
    var viewport = new Ext.Viewport({
            id: 'simplevp',
            layout: 'border',
            height:100,
            width:200,
            border: false,
            items:[{
                    autoScroll:true,
                    bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                    border:false,
                    collapseMode: 'mini',
                    html: 'top',
                    region: 'north',
                    split:true,
                    height:10
                },{
                    autoScroll:true,
                    bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                    border:false,
                    collapseMode: 'mini',
                    html: 'left',
                    region: 'west',
                    split:true,
                    width:10
                },{
                    autoScroll:true,
                    bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                    border:false,
                    collapseMode: 'mini',
                    html: 'bottom',
                    region: 'south',
                    split:true,
                    height:10
                },{
                    autoScroll:true,
                    bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                    border:false,
                    collapseMode: 'mini',
                    html: 'right',
                    region: 'east',
                    split:true,
                    width:10
                },{
                    autoScroll:true,
                    bodyStyle: 'padding:5px;font-size:11px;background-color:f4f4f4;',
                    border:false,
                    collapseMode: 'mini',
                    html: 'center',
                    region: 'center',
                    split:true
            }]
    });
*/  

/*
var ResizableExample = {
    init : function() {

        var basic_0 = new Ext.Resizable('basic_0', {
            animate:true,
            dynamic:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:400,
            minHeight:400,
            minWidth:500,
            pinned:true,
            transparent:false,            
            width:500,
            wrap:true
        });

        var basic_1 = new Ext.Resizable('basic_1', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:10,
            pinned:true,
            transparent:false,            
            width:10,
            wrap:true
        });

        var basic_2 = new Ext.Resizable('basic_2', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:380,
            pinned:true,
            transparent:false,            
            width:380,
            wrap:true
        });

        var basic_3 = new Ext.Resizable('basic_3', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:10,
            pinned:true,
            transparent:false,            
            width:10,            
            wrap:true
        });

        var basic_4 = new Ext.Resizable('basic_4', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:380,
            minHeight:380,
            minWidth:10,
            pinned:true,
            transparent:false,
            width:10,
            wrap:true
        });

         var basic_5 = new Ext.Resizable('basic_5', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:380,
            minHeight:380,
            minWidth:480,
            pinned:true,
            transparent:false,            
            width:480,
            wrap:true
        });

        var basic_6 = new Ext.Resizable('basic_6', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:380,
            minHeight:380,
            minWidth:10,
            pinned:true,
            width:10,
            wrap:true
        });

        var basic_7 = new Ext.Resizable('basic_7', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:10,
            pinned:true,
            transparent:false,            
            width:10,
            wrap:true
        });

        var basic_8 = new Ext.Resizable('basic_8', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:380,
            pinned:true,
            transparent:false,            
            width:380,
            wrap:true
        });

        var basic_9 = new Ext.Resizable('basic_9', {
            animate:true,
            dynamic:true,
            draggable:true,
            duration:.6,
            easing: 'backIn',
            handles: 'all',
            height:10,
            minHeight:10,
            minWidth:10,
            pinned:true,
            transparent:false,            
            width:10,
            wrap:true
        });        
    }
};
*/
// Ext.EventManager.onDocumentReady(ResizableExample.init, ResizableExample, true);