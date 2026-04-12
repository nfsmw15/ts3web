{* Copyright (C) 2012-2013  Psychokiller *}
{* Copyright (C) 2026 nfsmw15 - PHP 8.x port *}
<form method="post" action="site/clientsexport.php" target="_blank">
<table class="border" cellpadding="1" cellspacing="0">
	<tr>
		<td class="thead">{$lang['clientsexport']}</td>
	</tr>
	<tr>
		<td>{$lang['clientsexportdesc']}</td>
	</tr>
	<tr>
		<td class="green1">
		{$lang['serverid']}: <input type="text" name="sid" value="" />
		<input type="hidden" name="sid" value="{$sid}" />
		<input class="button" type="submit" name="give" value="{$lang['clientsexport']}" />
		</td>
	</tr>
</table>
</form>