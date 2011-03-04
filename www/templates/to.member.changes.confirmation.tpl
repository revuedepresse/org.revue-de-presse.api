Dear {$name},

Someone asked us to update the following item(s) in your profile page:
{foreach from=$updates key=index item=update}
- {$update}
{/foreach}

Please confirm the changes listed above by clicking on the following confirmation link within the next 24 hours:
{$link} 

If you have never planned to make these changes or you are willing to discard the modifications, please simply ignore this message. 

Kind regards,
{$signature}