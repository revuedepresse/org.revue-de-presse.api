<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr'>
    <head>
        <title>{$title}</title>
        <link rel="stylesheet" type="text/css" media="screen" href="/css/stylesheet.css" />
        <script src="js/jquery.js"></script>
        <script src="js/script.js"></script>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />        
    </head>
    <body>
        <div>
            <form id="select_album_cardinal_form" method="post" enctype="multipart/form-data" action="{$action_generate_upload_form}">
                <fieldset>
                    <h1 class='notes'>{$title_notes_select_album_cardinal}</h1>
                    <p class='notes'>{$notes_select_album_cardinal}</p>    
                    <legend>{$legend_select_album_cardinal}</legend>
                    <div class='field'>
                        <label for="{$album_cardinal.name}">{$album_cardinal.label_value}</label>
                        <select name='{$album_cardinal.name}' onchange='reload(this)'>                    
                            {foreach from=$album_cardinal.options item=option key=value}
                            <option value='{$option.value}' {if ($option.value eq $album_cardinal_value) or ($option.value eq 5 and $album_cardinal_value eq null)}selected="selected"{/if}>{$option.humanly_readable}</option>
                            {/foreach}
                        </select>
                    </div>
                    <noscript>
                    <div class='field'>
                        <input type="submit" value="Envoyer">
                    </div>
                    </noscript>
                </fieldset>
            </form>            
            <form id="main_form" method="post" enctype="multipart/form-data" action="{$action}">
                <fieldset>
                    <h1 class='notes'>{$title_notes}</h1>
                    <p class='notes'>{$notes}</p>    
                    <legend>{$legend}</legend>