function prepareFocus()
{
	$(document).ready(
		function()
		{
			$("#login").click(
				function(e)
				{
					if ($("#login").attr('value') == 'Email')
	
						$("#login").attr('value', '');
				}
			);

			$("#textarea_text").click(
				function(e)
				{
					if ($('[value^=Please, start]').attr('id') == $(this).attr('id'))
	
						$(this).attr('value', '');
				}
			);

			$("textarea").click(
				function(e)
				{
					if (
						( $("textarea").attr('value') == "...\n" ) ||
						( $("textarea").attr('value') == "..." ) 
					)
	
						$("textarea").attr('value', '');
				}
			);
		}
	);
}