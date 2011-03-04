<?xml version='1.0'?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr'>
    <head>
        <title>{if isset($title_page)}{$title_page}{/if}</title>
		<link href='/css/custom-theme/jquery-ui-1.8.9.custom.css' rel='stylesheet' type='text/css' />
        <link rel='stylesheet' type='text/css' media='screen' href='/load-stylesheet' />
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
        <script src='/js/jquery/jquery-1.5.js' type='text/javascript'></script>
        <script src='/js/jquery/jquery.focus.js' type='text/javascript'></script>
        <script src='/js/jquery/ui/jquery-ui.min.js' type='text/javascript'></script>
        <script src='/js/script.js' type='text/javascript'></script>
    </head>
    <body id='body'>
    {if isset($body)}{$body}{/if}
    {if isset($footer)}{$footer}{/if}
    </body>
</html>
