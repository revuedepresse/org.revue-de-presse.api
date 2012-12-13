/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.onReady(function(){

    Ext.QuickTips.init();

    // turn on validation errors beside the field globally
    Ext.form.Field.prototype.msgTarget = 'side';

    var bd = Ext.getBody();

    var top = new Ext.FormPanel({
        frame:true,
        title: 'Edit an article',
        bodyStyle:'padding:5px 5px 0',
        width: 600,
        items: [{
            layout:'column',
            items:[{
                columnWidth:1,
                layout: 'form',
                items: [{
                    xtype:'textfield',
                    fieldLabel: 'Title',
                    name: 'title',
                    anchor:'95%'
                }, {
                    xtype:'textfield',
                    fieldLabel: 'Subtitle',
                    name: 'subtitle',
                    anchor:'95%'
                }]
            }]
        },{
            xtype:'htmleditor',
            id:'body',
            fieldLabel:'Body',
            height:400,
            anchor:'98%'
        }],

        buttons: [{
            text: 'Save'
        },{
            text: 'Cancel'
        }]
    });

	container = document.getElementById('container');
    top.render(container);
});