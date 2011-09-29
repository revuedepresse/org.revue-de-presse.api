<div>
	<div class="table">
		<ul class="sortable_cells">
		{foreach from=$table key=row_index item=row}
			{*}<div class="tr">{/*}
			{foreach from=$row key=column_index item=column}
				<li class='ui-state-default'>
					<div class="td">
						<p>
						{$column}
						</p>
					</div>
				</li>
			{/foreach}
			{*}</div>{/*}
		{/foreach}
		</ul>
	</div>
</div>