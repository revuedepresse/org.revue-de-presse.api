<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr'>
    <head>
        <title>{if isset( $title )}{$title}{/if}</title>
		<meta http-equiv='content-type' content='text/html; charset=utf-8'></meta>
        <link href='css/sefi_style.css' media="screen" type='text/css' rel='stylesheet' />
    </head>
    <body>
        <div id="main-block">
		{foreach from=$photos key=author_id item=album}
			<div class="album">
			{foreach from=$album key=photo_id item=photo}
			<img
				alt="{$photo.title}"
				height="{$photo.height}"
				src="{$photo.path}"
				title="{$photo.title}"
				width="{$photo.width}" /> 
			{/foreach}
			</div>
		{/foreach}
		</div>
        <div id="footer">
        </div>
    </body>
</html>{literal}

make{/literal}{if isset( $item )}{$item}{/if}{literal}Root: function({/literal}{if isset( $component )}{$component}{/if}{literal}, item, data)
{	
	var thisGrid = new Ext.ux.grid.GridPanel({
		title: lang.DF_LIST_CATALOGUES,
		iconCls: '{/literal}{if isset( $icon_style )}{$icon_style}{/if}{literal}',
		itype: '{/literal}{if isset( $itype )}{$itype}{/if}{literal}',
		height: {/literal}{if isset( $height )}{$height}{/if}{literal},
		rowId: '{/literal}{if isset( $item_primary_key )}{$item_primary_key}{/if}{literal}',
		quickSearch: false,
		reloadAfterEdit: true,
		paging: false,
		exportPath: false,
		columns: [
			{
				header: lang.{/literal}{if isset( $lang )}{$lang}{/if}{literal},
				width: {/literal}{if isset( $width )}{$width}{/if}{literal},
				dataIndex: {/literal}{if isset( $data_index )}{$data_index}{/if}{literal},
				itemLink: ['{/literal}{if isset( $item_type )}{$item_type}{/if}{literal}', '{/literal}{if isset( $item_attribute )}{$item_attribute}{/if}{literal}']
			}
		}]
	});

	ct.add(thisGrid);	
},{/literal}