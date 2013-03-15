                {foreach from=$forms key=form_index item=form}
                    {foreach from=$fields item=field key=field_id}
                    <div class='field'>
                        {if $field_id neq 'author' or $form_index eq 0}
                        <label for="{$field_id}">{$field.label_value}</label>
                        {/if}
                        {if $field_id neq 'author'}<input
                            id="{$field_id}"
                            name="{$field_id}_{$form_index}"
                            {if isset( $attributes[$form_index][$field_id] )}value='{$attributes[$form_index][$field_id]}'{/if} />
                        {if isset($form.exception_title) or isset( $form.exception_keywords )}
                        <div class='exception'>
                        {if
                            $field_id eq 'title' and
                            isset( $form.exception_title ) and
                            isset( $attributes[$form_index][$field_id] )
                        }{$form.exception_title}{/if}{
                            if
                                $field_id eq 'keywords' and
                                isset( $form.exception_keywords ) and
                                isset( $attributes[$form_index][$field_id] )
                        }{$form.exception_keywords}{/if}
                        </div>
                        {/if}                        
                        {elseif $form_index eq 0}
                        <select name='{$field_id}'>
                            <option value='_blank' {if isset( $attributes )}selected='selected'{/if}></option>                            
                            {foreach from=$authors item=author key=author_id}
                            <option value='{$author_id}' {if isset( $attributes ) and isset( $attributes.author ) and $attributes.author eq $author_id}selected='selected'{/if}>{$author.full_name}</option>
                            {/foreach}
                        </select>
                        {if
                            ! is_null( $form.exception_author ) and
                            isset( $attributes[$form_index][$field_id] )
                        }
                        <div class='exception'>
                        {$form.exception_author}
                        </div>
                        {/if}
                        {/if}
                    </div>
                    {/foreach}
                    <div class='field'>
                        <label for="upload">{$label_browse}</label>
                        <input id="upload" name='file_{$form_index}' type="file"  />
                    </div>
                {/foreach}