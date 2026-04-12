{* Copyright (C) 2012-2013  Psychokiller *}
{* Copyright (C) 2026 nfsmw15 - PHP 8.x port *}
{if isset($permoverview['b_virtualserver_client_list']) AND empty($permoverview['b_virtualserver_client_list']) OR isset($permoverview['b_virtualserver_client_dblist']) AND empty($permoverview['b_virtualserver_client_dblist'])}
	<table class="border" style="width:50%;" cellpadding="1" cellspacing="0">
		<tr>
			<td class="thead">{$lang['error']}</td>
		</tr>
		<tr>
			<td class="green1">{$lang['nopermissions']}</td>
		</tr>
	</table>
{else}
<table style="width:100%" class="border" cellpadding="1" cellspacing="0">
	<tr>
		<td class="thead" colspan="8">{$lang['searchfor']}{$lang['client']}</td>
	</tr>
	<tr>
		<td class="green1" colspan="8">
		<form method="post" action="index.php?site=clients&amp;sid={$sid}">
		<select name="searchby">
		<option value="uniqueid">{$lang['uniqueid']}</option>
		<option value="cldbid">{$lang['cldbid']}</option>
		<option value="name">{$lang['name']}</option>
		</select>
		<input type="text" name="search" value="" />
		<input type="submit" name="sendsearch" value="{$lang['search']}" />
		</form>
		</td>
	</tr>
	<tr>
		<td class="thead" colspan="8">
		{$lang['clients']}<br />
		{if $pages!=0}
			{while $print_pages <= $pages}
				{if $print_pages == 1}
					<a class="paging" href="index.php?site=clients&amp;sid={$sid}&amp;getstart=0{if isset($smarty.get.sortby)}&amp;sortby={$smarty.get.sortby}{/if}{if isset($smarty.get.sorttype)}&amp;sorttype={$smarty.get.sorttype}{/if}">{$print_pages}</a>	
				{else}
					-<a class="paging" href="index.php?site=clients&amp;sid={$sid}&amp;getstart={$countstarted}{if isset($smarty.get.sortby)}&amp;sortby={$smarty.get.sortby}{/if}{if isset($smarty.get.sorttype)}&amp;sorttype={$smarty.get.sorttype}{/if}">{$print_pages}</a>
				{/if}
				{php}$countstarted+=$duration;{/php}
				{php}$print_pages++;{/php}
			{/while}
		{/if}
		</td>
	</tr>
	<tr>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=cldbid&amp;sorttype={if $sortby == 'cldbid' AND $sorttype == $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['dbid']}</a>
		</td>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=unique&amp;sorttype={if $sortby == 'client_unique_identifier' AND $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['uniqueid']}</a>
		</td>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=name&amp;sorttype={if $sortby == 'client_nickname' AND $sorttype == $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['nickname']}</a>
		</td>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=created&amp;sorttype={if $sortby == 'client_created' AND $sorttype == $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['created']}</a>
		</td>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=lastcon&amp;sorttype={if $sortby == 'client_lastconnected' AND $sorttype == $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['lastonline']}</a>
		</td>
		<td class="thead">
		<a class="headlink" href="index.php?site=clients&amp;sid={$sid}&amp;sortby=status&amp;sorttype={if $sortby == 'clid' AND $sorttype == $smarty.const.SORT_ASC}desc{else}asc{/if}">{$lang['status']}</a>
		</td>
		<td class="thead">{$lang['option']}</td>
	</tr>
	{while $showclients <= $duration AND isset($clientdblist[$getstart])}
		{php}if(!isset($change_col))$change_col=0; $td_col=($change_col%2)?"green1":"green2"; Smarty::$_vars["td_col"]=$td_col;{/php}
		<tr>
			<td class="{$td_col} center">
			{php}echo htmlspecialchars((string)($clientdblist[$getstart]['cldbid']), ENT_QUOTES, 'UTF-8');{/php}
			</td>
			<td class="{$td_col} center">{php}echo htmlspecialchars((string)($clientdblist[$getstart]['client_unique_identifier']), ENT_QUOTES, 'UTF-8');{/php}</td>
			<td class="{$td_col} center">{php}echo htmlspecialchars((string)($clientdblist[$getstart]['client_nickname']), ENT_QUOTES, 'UTF-8');{/php}</td>
			<td class="{$td_col} center">{php}echo date('d.m.Y - H:i:s', (int)$clientdblist[$getstart]['client_created']);{/php}</td>
			<td class="{$td_col} center">{php}echo date('d.m.Y - H:i:s', (int)$clientdblist[$getstart]['client_lastconnected']);{/php}</td>
			<td class="{$td_col} center">
			{php}if(isset($clientdblist[$getstart]['clid'])){echo '<span class="online">'.$lang['online'].'</span>';} else {echo '<span class="offline">'.$lang['offline'].'</span>';}{/php}
			</td>
			<td class="{$td_col} center">
			<form method="post" action="index.php?site=cleditperm&amp;sid={$sid}&amp;cldbid={php}echo htmlspecialchars((string)($clientdblist[$getstart]['cldbid']), ENT_QUOTES, 'UTF-8');{/php}">
			<input type="submit" class="eperms" name="editperms" value="" title="{$lang['editperms']}" />
			</form>
			{if !isset($permoverview['b_client_delete_dbproperties']) OR $permoverview['b_client_delete_dbproperties'] == 1}
				<form method="post" action="index.php?site=clients&amp;sid={$sid}">
				<input type="hidden" name="cldbid" value="{php}echo htmlspecialchars((string)($clientdblist[$getstart]['cldbid']), ENT_QUOTES, 'UTF-8');{/php}" />
				<input type="submit" class="delete" name="clientdel" value="" title="{$lang['delete']}" onclick="return confirm('{$lang['deletemsgclient']}')" />
				</form>
			{/if}
			</td>
		</tr>
		
		{php}$change_col++; Smarty::$_vars["change_col"]=$change_col;{/php}
		{php}$showclients++;{/php}
		{php}$getstart++;{/php}
	{/while}
</table>
{/if}