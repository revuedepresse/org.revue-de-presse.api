{if isset($threads) && is_array($threads)}
{foreach from=$threads key=index item=thread}
<div {if isset($thread.id)}id='_{$thread.id}' {/if}class='insight'>
    {if isset($thread.nodes) && is_array($thread.nodes) && count($thread.nodes) != 0}
    <ul class='insight'>
        {foreach from=$thread.nodes key=index item=node}
        <li>
            <div class='owner'>
                <div id='_{$node.id}' class='avatar'>{$node.avatar}</div>
            </div>
            <div class='modification_date'><p>{$node.date_modification}</p></div>
            <p><pre>{$node.body}</pre></p>
            <div class='insight_actions'>
                {if isset($node.insight_actions) && is_array($node.insight_actions)}
                <ul>
                    {foreach from=$node.insight_actions key=action_index item=action}
                    <li><a
                        class='{$action.class}'
                        href='{$action.link}'
                        title='{$action.label}'><span>{$action.label}</span></li>
                    {/foreach}
                </ul>
                {/if}
            </div>
            {if isset($node.children)}
                {$node.children}
            {/if}
        </li>
        {/foreach}
    </ul>
    {/if}
</div>
{/foreach}
{/if}