<!--
Copyright (C) 2012-2013  Psychokiller
Copyright (C) 2026 nfsmw15 - PHP 8.x port

This program is free software; you can redistribute it and/or modify it under the terms of
the GNU General Public License as published by the Free Software Foundation; either
version 3 of the License, or any later version.
-->
# ts3web - PHP 8.x Port

**Fork of [ts3web](https://github.com/Psychokiller1888/ts3web) by Psychokiller**

This fork ports the original TeamSpeak 3 Web Interface to **PHP 8.x** compatibility.

## What is ts3web?

ts3web is a web-based administration interface for TeamSpeak 3 servers. It allows you to manage your TS3 server via a browser — including server settings, channels, clients, groups, bans, tokens, backups, and more.

## Changes in this fork

### Core PHP 8.x fixes
- Replaced the bundled **Smarty 3rc4** template engine (which used the removed `create_function()`) with a custom PHP 8.x-compatible Smarty compiler (`libs/Smarty/libs/Smarty.class.php`)
- Fixed all `preg_match` delimiter issues
- Fixed `readdir()` calls to handle `false` return values (PHP 8 TypeError)
- Fixed `date()` to require integer timestamps (PHP 8 TypeError)
- Fixed `str_replace()` return value used as integer
- Fixed undefined variable warnings throughout
- Fixed `opendir()` with relative paths — now uses absolute paths via `TS3WI_DIR`
- Removed dead external URL calls (`check_server_version`, `check_version_consistency`, update checks)
- Removed MD5 copyright checks that caused fatal errors
- Fixed `file()` calls with relative paths
- Added missing directory initialization for backup folders
- Fixed `DS` and `TS3WI_DIR` constants in `site/interactive.php`
- Fixed `$log` variable initialization in `logview.php`

### Smarty compiler fixes
- Full rewrite of Smarty compiler for PHP 8.x compatibility
- Fixed `{if}` / `{elseif}` regex to handle array keys with quotes (e.g. `$var['key']`)
- Fixed `date_format` modifier — properly converts `%d.%m.%Y` strftime format to PHP `date()` format
- Fixed `$smarty.post.X.Y` multi-level POST variable access
- Fixed `{while}` / `{/while}` support
- Fixed numeric array keys in dot-notation (`$value.info.2` → `$value['info'][2]`)
- Fixed nested variable as array index (`{$arr[$var]}`)
- Fixed `{assign}` with backtick expressions replaced by `{php}` blocks
- Fixed `$lang['key']` with quoted keys in `_var()`
- Fixed `{sprintf(...)}` — converted to `{php}echo sprintf(...);{/php}`

### Template fixes
- Fixed bare constants in `{if}` conditions (e.g. `$site == serveredit` → `$site == 'serveredit'`)
- Fixed orphan `<?php } ?>` in `serverview.tpl`
- Fixed `{$serverinfo['virtualserver_clientsonline']-$serverinfo['virtualserver_queryclientsonline']}` arithmetic expression
- Fixed `change_col` / `td_col` variable scoping between `{php}` blocks and Smarty
- Fixed `{$clientdblist[$getstart]['field']}` — variable as array index in clients.tpl
- Fixed `|strpos:{$searchperms}` and `|substr:0:2` modifiers in chanclienteditperm.tpl
- Added `class="border"` to tables in `clientcleaner.tpl`
- Fixed "Aktualisierung ausschalten" link → proper button in `servertraffic.tpl`
- Fixed logview form action URL — `sid` only appended when set

### index.php / header.php fixes
- Fixed `fastswitch` server dropdown — now only shown when a virtual server is selected
- Added `$serverhost` Smarty assignment
- Fixed `$newserverversion` — update warning no longer shown when version check is disabled
- Commented out dead update check includes

## Requirements

- PHP **8.0** or higher (tested on PHP 8.0, 8.2, 8.4)
- PHP extensions: `sockets` or `fsockopen` support
- A running **TeamSpeak 3 Server** with ServerQuery access
- Web server: Apache or Nginx with PHP-FPM

## Installation

1. Copy all files to your web server document root
2. Copy `config.php.example` to `config.php`
3. Edit `config.php` with your TeamSpeak 3 server details:
   ```php
   $server[0]['alias'] = "My Server";
   $server[0]['ip']    = "127.0.0.1";
   $server[0]['tport'] = "10011";
   ```
4. Add your web server's IP to the TS3 ServerQuery allowlist (`query_ip_allowlist.txt`)
5. Restart your TS3 server after modifying the allowlist
6. Create the backup directories:
   ```
   site/backups/channel/
   site/backups/channel/hostbackups/
   site/backups/server/
   site/backups/server/hostbackups/
   ```
7. Make `templates_c/` writable by the web server
8. Open the web interface in your browser and log in with your ServerQuery credentials

## Known limitations

- `logview` (instance log) requires the TS3 server to grant `b_virtualserver_log_view` permission to the serveradmin query user
- The update check functionality has been disabled as the original update server URLs are no longer active
- Channel icons next to usernames in the server view require proper icon uploads

## License

GNU General Public License v3.0 — see `gpl-3.0.txt`

Original author: **Psychokiller** (http://www.psychoscripts.de)  
PHP 8.x port by: **petereita / RiDDiX**
