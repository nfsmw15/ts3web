{* Copyright (C) 2012-2013  Psychokiller *}
{* Copyright (C) 2026 nfsmw15 - PHP 8.x port *}
{if !empty($error) OR !empty($noerror)}
<table>
	{if !empty($error)}
	<tr>
		<td class="error">{$error}</td>
	</tr>
	{/if}
	{if !empty($noerror)}
	<tr>
		<td class="noerror">{$noerror}</td>
	</tr>
	{/if}
</table>
{/if}
<form method="post" action="index.php?site=clientcleaner&amp;sid={$sid}">
<table class="border" cellpadding="1" cellspacing="0" style="width:50%">
	<tr>
		<td class="thead">{$lang['clientcleaner']}</td>
	</tr>
	{if isset($deleted)}
	<tr>
		<td>{$deleted}</td>
	</tr>
	{/if}
	<tr>
		<td>{$lang['deleteallclientsoffline']}<input type="text" name="number" value="30" size="3"/>{$lang['deleteallclientsoffline2']}</td>
	</tr>
	<tr>
		<td>{$lang['checkgroupsprotected']}</td>
	</tr>
	{foreach key=key item=value from=$sgrouplist}
	<tr>
		<td><input type="checkbox" name="sgroups[]" value="{$value.sgid}" /> {$value.name}</td>
	</tr>
	{/foreach}
	<tr>
		<td><input type="submit" name="cleanit" value="{$lang['clean']}" /></td>
	</tr>
</table>
</form>