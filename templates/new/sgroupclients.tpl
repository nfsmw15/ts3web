{* Copyright (C) 2012-2013  Psychokiller *}
{* Copyright (C) 2026 nfsmw15 - PHP 8.x port *}
{if isset($permoverview['b_virtualserver_servergroup_client_list']) AND empty($permoverview['b_virtualserver_servergroup_client_list'])}
	<table class="border" style="width:50%;" cellpadding="1" cellspacing="0">
		<tr>
			<td class="thead">{$lang['error']}</td>
		</tr>
		<tr>
			<td class="green1">{$lang['nopermissions']}</td>
		</tr>
	</table>
{else}
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
<table style="width:100%" class="border" cellpadding="1" cellspacing="0">
	<tr>
		<td class="thead" colspan="6">{$lang['searchfor']}{$lang['client']}</td>
	</tr>
	<tr>
		<td class="green1" colspan="6">
		<form method="post" action="index.php?site=sgroupclients&amp;sid={$sid}&amp;sgid={$sgid}">
		<select name="searchby">
		<option value="cldbid">{$lang['cldbid']}</option>
		<option value="name">{$lang['name']}</option>
		</select>
		<input type="text" name="search" value="" />
		<input type="submit" name="sendsearch" value="{$lang['search']}" />
		</form>
		</td>
	</tr>
	<tr>
		<td class="thead" colspan="6">({$sgroupid}) {$sgroupname} {$lang['groupmember']}</td>
	</tr>
	<tr>
		<td class="thead">{$lang['uniqueid']}</td>
		<td class="thead">{$lang['dbid']}</td>
		<td class="thead">{$lang['name']}</td>
		<td class="thead">{$lang['created']}</td>
		<td class="thead">{$lang['lastonline']}</td>
		<td class="thead">{$lang['option']}</td>
	</tr>
	{if !empty($groupclients)}
		{foreach key=key item=value from=$groupclients}
			{php}if(!isset($change_col))$change_col=0; $td_col=($change_col%2)?"green1":"green2"; Smarty::$_vars["td_col"]=$td_col;{/php}
			<tr>
				<td class="{$td_col} center">{$value['client_unique_identifier']}</td>
				<td class="{$td_col} center">{$value['cldbid']}</td>
				<td class="{$td_col} center">{secure($value['client_nickname'])}</td>
				<td class="{$td_col} center">{date("d.m.Y", $value['client_created'])}</td>
				<td class="{$td_col} center">{date("d.m.Y", $value['client_lastconnected'])}</td>
				<td class="{$td_col} center">
				<form method="post" action="index.php?site=sgroupclients&amp;sid={$sid}&amp;sgid={$sgid}">
				<input type="hidden" name="cldbid" value="{$value['cldbid']}" />
				<input type="submit" class="delete" name="kickclient" value="" title="kick"/>
				</form>
				</td>
			</tr> 
			{php}$change_col++; Smarty::$_vars["change_col"]=$change_col;{/php}
		{/foreach}
	{/if}
</table>
<br />
<form method="post" action="index.php?site=sgroupclients&amp;sid={$sid}&amp;sgid={$sgid}">
<table class="border" cellpadding="1" cellspacing="0">
	<tr>
		<td colspan="2" class="thead">{$lang['addclient']}</td>
	</tr>
	<tr>
		<td class="green1">{$lang['cldbid']}</td>
		<td class="green1"><input type="text" name="cldbid" value="" /></td>
	</tr>
	<tr>
		<td class="green2">{$lang['options']}</td>
		<td class="green2"><input type="submit" name="addclient" value="{$lang['add']}" /></td>
	</tr>
</table>
</form>
{/if}