{* Copyright (C) 2012-2013  Psychokiller *}
{* Copyright (C) 2026 nfsmw15 - PHP 8.x port *}
{if isset($permoverview['b_virtualserver_servergroup_list']) AND empty($permoverview['b_virtualserver_servergroup_list'])}
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
		<td class="thead" colspan="6">{$lang['servergroups']}</td>
	</tr>
	<tr>
		<td class="thead">{$lang['id']}</td>
		<td class="thead">{$lang['name']}</td>
		<td class="thead">{$lang['type']}</td>
		<td class="thead">{$lang['iconid']}</td>
		<td class="thead">{$lang['savedb']}</td>
		<td class="thead">{$lang['options']}</td>
	</tr>
	{if !empty($servergroups)}
		{foreach key=key item=value from=$servergroups}
			{php}if(!isset($change_col))$change_col=0; $td_col=($change_col%2)?"green1":"green2"; Smarty::$_vars["td_col"]=$td_col;{/php}
			<tr>
				<td class="{$td_col} center">{$value['sgid']}</td>
				<td class="{$td_col}">
				<form method="post" action="index.php?site=sgroups&amp;sid={$sid}&amp;sgid={$value['sgid']}">
				<input type="text" name="name" value="{$value['name']}" /> <input class="button" type="submit" name="sendname" value="{$lang['rename']}" />
				</form>
				</td>
				<td class="{$td_col} center">{$value['type']}</td>
				<td class="{$td_col} center">{php}echo sprintf('%u', $value['iconid'] & 0xffffffff);{/php}</td>
				<td class="{$td_col} center">{$value['savedb']}</td>
				<td class="{$td_col} center">
				{if $value['type'] != '0'}
				<form method="post" action="index.php?site=sgroupclients&amp;sid={$sid}&amp;sgid={$value['sgid']}">
				<input type="submit" class="clients" name="groupclients" value="" title="{$lang['clients']}" />
				</form>
				{/if}
				<form method="post" action="index.php?site=sgroupeditperm&amp;sid={$sid}&amp;sgid={$value['sgid']}">
				<input type="submit" class="eperms" name="permedit" value="" title="{$lang['editperms']}" />
				</form>
				{if !isset($permoverview['b_virtualserver_servergroup_delete']) or $permoverview['b_virtualserver_servergroup_delete'] == 1}
				<form method="post" action="index.php?site=sgroups&amp;sid={$sid}&amp;sgid={$value['sgid']}">
				<input type="submit" class="delete" name="delgroup" value="" title="{$lang['delete']}"  onclick="return confirm('{$lang['deletemsgservergroup']}')" />
				</form>
				{/if}
				</td>
			</tr>
			{php}$change_col++; Smarty::$_vars["change_col"]=$change_col;{/php}
		{/foreach}
	{/if}
</table>
{/if}