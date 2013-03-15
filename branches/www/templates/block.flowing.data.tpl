<div class='flowing_data'>

	<form>

		<table>{*}</table>

			{/*}{foreach from=$rows key=index item=row}
	
			<tr{if $index eq 0} class='column_name'{/if}>{*}
				
				{/*}{foreach from=$row key=_index item=column}
				<td>
					{if $index eq 0}{$column}{*}

					{/*}{*}
					
					{/*}{else}{foreach from=$tags key=tag item=tagged_columns}{*}

					{/*}{if in_array( $_index, $tagged_columns )}<{$tag}>{else}{*}
						
					{/*}
					{if $index neq 0}<input
						name='{$index}_{$_index}'
						type='' readonly="readonly"
						value="{/if}{*}
					{/*}{/if}{*}

					// add an input element for the current row column

					{/*}{$column}{*}


					{/*}{if in_array( $_index, $tagged_columns )}</{$tag}>{else}{*}
					
					{/*}{if $index != 0}" />{/if}{*}
					
					{/*}{/if}{*}

					{/*}{/foreach}{*}

				{/*}{/if}
				</td>
	
				{/foreach}
	
			</tr>
	
			{/foreach}
	
		</table>

	</form>

</div>