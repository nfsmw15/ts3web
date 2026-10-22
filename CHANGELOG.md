<!--
Copyright (C) 2012-2013  Psychokiller
Copyright (C) 2026 nfsmw15 - PHP 8.x port

This program is free software; you can redistribute it and/or modify it under the terms of
the GNU General Public License as published by the Free Software Foundation; either
version 3 of the License, or any later version.
-->
# Changelog

## v3.4.6-php8 (2026-10-22)

- Security review:
  - Fixed a path traversal in `site/showfile.php` (`ip`/`port` weren't sanitized like `name` was) - reachable by any visitor without logging in, since `tsviewpub.php` unlocks it
  - The `|addslashes` Smarty modifier was a silent no-op (the compiler looked for a function that never existed), leaving the one place that escaped a value for a JS string context doing nothing - fixed, closing a stored-XSS vector via virtual server names
  - `ts3admin.class.php`: `ftRenameFile()` now escapes `cpw`/`tcpw` and sends the previously-missing `tcpw=` key
  - `secure()` now also escapes `&` and `'`, not just `<`, `>`, `"`
  - Added login rate limiting (5 failed attempts / 10 min -> 15 min lockout per IP)
  - Added CSRF protection: a per-session token is generated and validated on every POST, auto-injected into all `<form method="post">` from a single place in the Smarty compiler instead of touching every template; the two GET-based delete links now carry and check the token explicitly
  - Added `.htaccess` (deny all) to `temp/`, `templates_c/`, `site/backups/` for Apache-based deployments (nginx deployments need an equivalent `location` block instead - see README)
  - Removed the footer/README link to psychoscripts.de - the original author's domain has lapsed and now redirects to an unrelated gambling site; the Psychokiller attribution text itself stays (required by the GPL license header)
- Fixed a PHP 8.4 deprecation (`case N;` instead of `case N:`) in `functions.inc.php`
- Fixed a long-standing "Undefined variable $fastswitch" warning on every page where the fastswitch dropdown isn't shown

## v3.4.5-php8 (2026-08-20)

- Fixed broken `Serverview` links (empty `sid=`) for scoped/single-server logins on pages whose own URL didn't already carry `?sid=`
- `check_version_consistency()`/`check_wi_version()` now do their live network check at most once every 6 hours instead of on every page load, and force IPv4 to avoid multi-second stalls on dead IPv6 routes — fixes pages feeling slow
- `ts3admin.class.php`:
  - `unEscapeText()` no longer corrupts the first field of a ServerQuery response (TS3 prefixes responses with a raw `\r` that has to be stripped, not just escape-decoded); `escapeText()` now escapes backslashes
  - `executeCommand()`/`ftRead()` no longer loop forever if the connection drops mid-response
  - `quit()` no longer errors if called without an active connection
  - Removed PHP 8.1-deprecated optional-before-required parameters in the file-transfer functions

## PHP 8.x Port (2026)

### Core PHP 8.x fixes
- Replaced **Smarty 3rc4** (used removed `create_function()`) with a custom PHP 8.x-compatible Smarty compiler
- Fixed all `preg_match` delimiter issues in `functions.inc.php`, `site/tsview.php`, `tsviewpub.php`
- Fixed `readdir()` calls to handle `false` return values (PHP 8 TypeError)
- Fixed `date()` to require integer timestamps (PHP 8 TypeError) in `serverview.php`
- Fixed undefined variable warnings throughout
- Fixed `opendir()` with relative paths via `TS3WI_DIR`
- Removed dead external URL calls (`check_server_version`, `check_version_consistency`, update checks)
- Removed MD5 copyright checks that caused fatal errors in `index.php` and `header.php`
- Fixed `file()` calls with relative paths
- Added automatic backup directory creation for `site/backups/`
- Fixed `DS` and `TS3WI_DIR` constants in `site/interactive.php`
- Fixed `$log` variable initialization in `logview.php`
- Fixed `fastswitch` server dropdown — now only shown when a virtual server is selected
- Added `$serverhost` and `$serverlist` Smarty assignments

### Smarty compiler rewrite
- Full rewrite of Smarty compiler for PHP 8.x compatibility
- Fixed `{if}` / `{elseif}` regex to handle array keys with quotes (e.g. `$var['key']`)
- Fixed `date_format` modifier — strftime to PHP date() format conversion
- Fixed `$smarty.post.X.Y` multi-level POST variable access
- Fixed `{while}` / `{/while}` support
- Fixed numeric array keys in dot-notation (`$value.info.2` -> `$value['info'][2]`)
- Fixed nested variable as array index (`{$arr[$var]}`)
- Fixed `{assign}` with backtick arithmetic expressions -> `{php}` blocks
- Fixed `{sprintf(...)}` -> `{php}echo sprintf(...);{/php}`
- Fixed `change_col` / `td_col` scoping via `Smarty::$_vars`

### Template fixes
- `serverview.tpl`: Fixed bare constants in `{if}`, orphan `<?php } ?>`, arithmetic expression
- `head.tpl`: Fixed `$site == serveredit` without quotes
- `mainbar.tpl`: Fixed `$site !== login` without quotes
- `clients.tpl`: Fixed variable as array index, `{while}` loop and counter increments
- `chanclienteditperm.tpl`: Fixed `|strpos` and `|substr` modifiers
- `fileupload.tpl`: Fixed dot-notation for numeric keys
- `logview.tpl`: Fixed form action URL, fixed `$smarty.post.type.error` multi-level access
- `servertraffic.tpl`: Fixed refresh button (link -> button), added CSS class
- `clientcleaner.tpl`: Added `class="border"` to table
- `sgroups.tpl`, `complainlist.tpl`: Fixed `{sprintf(...)}`
- All 20 templates: Fixed `change_col` increment and Smarty::$_vars sync

---

## Original Changelog by Psychokiller

```
//Beta 3.4.3.1
- Fixed some url problems

//Beta 3.4.3
- Fixed bug with the serverbackup function
- Remove some code because security reason!

//Beta 3.4.2
+ Add the new option $show_version to the config.php
* The server msg field on the serverview is now a textarea field
* The live view on serverview will now show the name of the group on mouseover
- Fixed bug that the new channel codecs not show on all settings and infos
- Fixed bug with the public viewer that join on a subchannel not work

//Beta 3.4.1
- Fixed bug with the channel backup function (All channel backups created with 3.4 are broken!)
- Fixed bug with instanz server backup

//Beta 3.4
! This version will not Work 100% with server versions < 3.0.7
* Add french to the language file
* The serverbackups will now sorted by date groups
* The channelbackups will now save more settings
* Add the new Codecs of server 3.0.7
- Fixed bug with the permissionlists on server version 3.0.7

//Beta 3.3
+ Add function for temporary passwords
* The Host Banner will now show a small preview
* It will now show error messages if there is a error on edit Permissions
* There is now a select all field on the icon delete function
- Fixed bug with the fast server select window
- Fixed bug that server not will go in virtual online mode if there are offline
- Fixed bug that the Start and Stop button not will show on the Serverview
- Fixed bug that Icons will be deleted if the external Serverviewer loaded
- Fixed bug that Icons will not all show on icon set window

//Beta 3.2
! This version will not Work with server versions < 3.0.2
* The welcomemessage and the hostmessage are now textarea fields
* BBCode in welcomemessage and hostmessage will now parsed on serverview
* Some Option on Server Edit are now select fields
* Permissions will now show correct if there are new permissions with server update
- Fixed bug that some log messages not show correct
- Remove the hostlogin function and the port field on login

//Beta 3.1
+ Add function that the icons which are on the server show on icon Upload
+ Add Client Cleaner
* Add checkboxes on the tokenlist to delete more tokens
* The totalsize of all Files will show in the Filelist
- Fixed bug that the icons will deleted and downloaded again
- Fixed bug that the webinterface will not decode in utf-8
- Remove the default design there will now only the new design

//Beta 3.0.1
- Fixed bug that you got banned from server if you use some functions

//Beta 3.0
* The password for the login will not longer show on plain text in the session
* The Serverbackups will now save on folder with the ip of the instance and the port
+ Add function that will delete old icons which are not longer on the virtual server
+ Add function to make mass action on the serverlist
+ Add function to open all permissions on the permission edit
+ Add function to set autostart on serverlist

//Beta 2.9.1
- Fixed bug that the channelicons will not show in the public ts viewer
+ Add a link on the mainbar for mass move/kick/ban function

//Beta 2.9
- Fixed many bugs with icons, permissions and public webviewer
+ Add a message of the day window to the login
+ Add a mass move/kick/ban function to the viewer

//Beta 2.8.2
- Fixed bug that the interface not work with the Internet Explorer
- Fixed some language bugs
+ Add the language Netherlandish by pd1evl
+ Add country flags to the ts viewer

//Beta 2.8.1
+ Add the Log options to the Server Edit function
+ Add a new Design to the Webinterface

//Beta 2.8
* The Webinterface will now use Smarty as Template Engine!
+ Add the option Parent Id on create channel to create subchannel
+ Add a search function on the client list
+ Add a sort function to the serverlist
+ Add a new Design to the Webinterface

//Beta 2.7
- Fixed many small bugs
+ Add Instanz Backup function
+ Add a search function to permission edit

//Beta 2.6
+ Add a external Ts Viewer
+ Add a filelist

//Beta 2.5
+ Add a filebrowser
* Updated client list with a sort function and a new paging function

//Beta 2.4.1
- Fix the problem that the interactive window is blank
- Fix the problem that the runtime on server info is wrong
+ Add a tooltip on mouseover on the server viewer

//Beta 2.4
- Fixed many small Bugs
+ Add Server Alias to the config.php
+ Add a Log Viewer
+ Add Weblist function to Instance Edit
+ Add permission check if a Server Selected
* Update to the newest library from par0noid

//Beta 2.3.4
- Fixed login Bug with Server Version 29

//Beta 2.3.3
- Fixed a critical security gap

//Beta 2.3.2
- Fixed a security gap
- Fixed Icon Bug with 64bit Systems

//Beta 2.3.1
- Fixed a security gap

//Beta 2.3
- Fixed several bugs with channel backup and live administration
- Remove all old Designs
+ Add new Design
+ Add Instance Edit function
+ Add Icon Upload/Download function
+ Add Start/Stop function to the Serverview
+ Add Instance Statistik to the Serverlist

//Beta 2.2
+ Add Bans export function
+ Add Client export function
+ Add Host Server/Channel Backup function
+ Add a fast server switch to the header

//Beta 2.1
+ Add Serversnaphot function
+ Add Query Console
+ Add Permission export function
+ Add Start/Stop button on Server View

//Beta 2.0
+ Add Channel Backup function
+ Add Complain List with Delete function

//Beta 1.9.1
* Advanced the Token list with "token created" and "token description"

//Beta 1.9
+ Add page refresh by traffic
+ Add clip function like ts3 client to the permissions

//Beta 1.8.1
- Fixed the bug if the cache after update not empty

//Beta 1.8
+ Add multiple server login
+ Add show Instance/V-Server Traffic
+ Add show Client Counter (Total, Online, Today, This Week, This Month)

//Beta 1.7.1
- Fixed bug with Send message to Server

//Beta 1.7
+ Add Serverhost function
+ Add group/user search function to channel group clients

//Beta 1.6
+ Add Send message to Server
+ Add Ban List and Ban functions
* Changed the main navigation from the top to the left side

//Beta 1.5
+ Add Kick/Ban/Poke/Move function to Server View

//Beta 1.4
+ Add Server Port, Upload/Download Quota edit to Server Edit

//Beta 1.3
+ Add paging to Client List
* All Permissions can edit by one click

//Beta 1.2
+ Add a simple style system
+ Add Upload/Download Bandwidth edit to Server Edit

//Beta 1.1
* Change the Green Style to the white style from RogueT

//Beta 1.0
Initial release
```
