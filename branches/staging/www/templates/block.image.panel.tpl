<div class='image'{if $internal_anchors and $internal_anchors.current} id='{$internal_anchors.prefix}{$internal_anchors.current}'{/if}>
    {$resource}
    <div class='panel_container'>
        <div class='panels'>
            {if isset($admin) && $admin eq true and $affordances|@count neq null}
            <ul class='action_panel'>
                {foreach from=$affordances key=index item=affordance}
                {if $affordance.access_type.group eq 1 or $affordance.access_type.group eq 3}
                    <li>{if $affordance.link neq null}
                        <a
                           {if $affordance.accesskey neq null}accesskey='{$affordance.accesskey}'{/if}                           
                           {if $affordance.class neq null}class='{$affordance.class}'{/if}
                           href='{$affordance.link}'
                           {if $affordance.id neq null}id='{$affordance.id}'{/if}
                        >{/if}{$affordance.label}{if $affordance.link neq null}</a>
                    {/if}</li>
                {/if}
                {/foreach}
            </ul>
            {else if $affordances|@count neq null}
            <ul class='action_panel'>
                {foreach from=$affordances key=index item=affordance}
                {if $affordance.access_type.group eq 3}                
                    <li>{if $affordance.link neq null}
                        <a
                           {if $affordance.accesskey neq null}accesskey='{$affordance.accesskey}'{/if}
                           {if $affordance.class neq null}class='{$affordance.class}'{/if}
                           href='{$affordance.link}'
                           {if $affordance.id neq null}id='{$affordance.id}'{/if}
                        >{/if}{$affordance.label}{if $affordance.link neq null}</a>
                    {/if}</li>
                {/if}                    
                {/foreach}
            </ul>
            {/if}
            {if $links}
            <ul class='action_panel navigation'>
                <li>
                    <a
                        {if isset($accesskeys.next) and $accesskeys.next} accesskey='{$accesskeys.bottom}'{/if}
                        {if $internal_anchors.class_bottom} class="{$internal_anchors.class_bottom}"{/if}
                        href='{$internal_anchors.bottom}'
                    >{$links.bottom}</a>
                </li>
                {if isset($links.previous) and $links.previous neq null}<li>
                    <a
                        {if isset($accesskeys.next) and $accesskeys.next} accesskey='{$accesskeys.previous}'{/if}
                        {if $internal_anchors.class_previous neq null}
                        class="{$internal_anchors.class_previous}"{/if}
                        href='{$internal_anchors.previous}'>{$links.previous}</a>
                </li>{/if}
                {if isset($links.next) and $links.next neq null}<li>
                    <a
                        {if isset($accesskeys.next) and $accesskeys.next} accesskey='{$accesskeys.next}'{/if}
                        {if $internal_anchors.class_next neq null}
                        class="{$internal_anchors.class_next}"{/if}
                        href='{$internal_anchors.next}'>{$links.next}</a>
                </li>{/if}
                <li>
                    <a
                       {if isset($accesskeys.next) and $accesskeys.next} accesskey='{$accesskeys.top}'{/if}
                       {if $internal_anchors.class_top} class="{$internal_anchors.class_top}"{/if}
                       href='{$internal_anchors.top}'>{$links.top}</a>
                </li>
            </ul>
            {/if}
            {if $information|@count neq null}
            <ul class='information_panel'>
                {foreach from=$information key=index item=data}
                    {if isset($data.label) and $data.label neq null}
                        <li>
                            {if $data.link neq null}<a href='{$data.link}'>{/if}{$data.label}{if $data.link neq null}</a>{/if}
                        </li>
                    {else if isset($data.property) && $data.property neq null}
                        <li {if $data.class neq null}class='{$data.class}'{/if}>
                            {if isset($data.class_property) && $data.class_property neq null}<span class='{$data.class_property}'>{/if}{$data.property}{if isset($data.class_property) && $data.class_property neq null}</span>{/if}{if isset($data.class_separator) && $data.class_separator}<span class='{if isset($data.class_separator)}{$data.class_separator}{/if}'>{/if}:{if isset($data.class_separator) && $data.class_separator}</span>{/if} {if isset($data.class_value) && $data.class_value neq null}<span class='{if isset($data.class_value)}{$data.class_value}{/if}'>{/if}{$data.value}{if isset($data.class_value) and $data.class_value neq null}</span>{/if}
                        </li>
                    {/if}
                {/foreach}
            </ul>
            {/if}
            <div class='thread'>
            {literal}{thread}{/literal}
            </div>
            {if isset($form_insight)}
            <div class='post_insight'>
                <div class='form_insight'>{$form_insight}</div>
            </div>
            {/if}
        </div>
    </div>
</div>