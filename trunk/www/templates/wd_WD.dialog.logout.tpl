<div id='form_logout'>
    <p>{$dialogs[0]}{if isset($links.member_account) and $links.member_account neq null} <a href='{if isset($links.member_account) and $links.member_account}{$links.member_account}{/if}' title='{$tooltips.member_profile}'>{/if}{$dialogs[1]}{if isset($links.member_account) and $links.member_account neq null}</a>{/if}{$dialogs[2]}</p>
    <form action='{$actions[0]}' method='post'>
        <fieldset>
            {if isset($flags) and $flags neq null and $flags[4]}<input type='hidden' name='a' value='true' />{/if}
            <input accesskey='{$accesskeys.logout}' type='submit' value='{$affordances[0]}'>
        </fieldset>
    </form>
</div>