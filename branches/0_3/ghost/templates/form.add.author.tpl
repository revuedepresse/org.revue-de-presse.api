<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr'>
    <head>
        <title>{$title}</title>
        <link rel="stylesheet" type="text/css" media="screen" href="/css/stylesheet.css" />
        <script src="js/jquery.js"></script>
        <script src="js/jquery.multifile.js"></script>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />        
    </head>
    <body>
        <div>
            <form id="main_form" method="post" enctype="multipart/form-data" action="{$action}">
                <fieldset>
                    <h1 class='notes'>{$title_notes}</h1>
                    <p class='notes'>{$notes}</p>                    
                    <legend>{$legend}</legend>
                    {foreach from=$fields item=field key=field_id}
                        {if
                            $field_id neq 'month' and $field_id neq 'year' &&
                            isset( $field.type )
                        }
                        <div class='field {$field_id}'>
                        {/if}
                            {if $field_id neq 'year' and $field_id neq 'month' &&
                                isset( $field.type )
                            }
                            <label for="{$field_id}">{if isset( $field.is_mandatory )}<span class='mandatory'>{/if}
                            {if isset( $field.label_value )}{$field.label_value}{/if}{
                            if isset( $field.is_mandatory )}</span>{/if}</label>
                            {elseif isset( $field.type ) and isset( $field.label_value )}
                            <span class='{$field_id}'> {$field.label_value}</span>
                            {/if}
                            {if isset( $field.type ) &&  $field.type eq 'text'}
                            <input id="{$field_id}" name="{$field_id}" {if isset( $field.max_char ) }maxlength='{$field.max_char}'{/if} type='{$field.type}'{if isset( $field.value)} value='{$field.value}'{/if} />
                            {elseif isset($field.type) && $field.type eq 'textarea'}
                            <textarea id="{$field_id}" cols="100" rows="5" name="{$field_id}" {if isset( $field.max_char ) }maxlength='{$field.max_char}'{/if}></textarea>
                            {/if}
                        {if $field_id neq 'day' and $field_id neq 'month'  &&
                            isset( $field.type )
                        }
                        </div>
                        {if isset( $field.exception ) && isset( $field.validity) }
                        <div class='exception'>
                            {$field.exception}
                        </div>
                        {/if}
                        {/if}
                    {/foreach}
                    <div class="field">
                        <input type="submit" value="Envoyer">
                    </div>
                </fieldset>
            </form>
        </div>
    </body>
</html>