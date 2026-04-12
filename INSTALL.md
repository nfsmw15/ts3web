<!--
Copyright (C) 2012-2013  Psychokiller
Copyright (C) 2026 nfsmw15 - PHP 8.x port

This program is free software; you can redistribute it and/or modify it under the terms of
the GNU General Public License as published by the Free Software Foundation; either
version 3 of the License, or any later version.
-->
# Installation

## Requirements

- PHP **8.0** or higher (tested on PHP 8.0, 8.2, 8.4)
- TeamSpeak 3 Server with ServerQuery enabled
- Apache or Nginx with PHP-FPM

## Steps

1. Add the IP of your web server to `query_ip_allowlist.txt`
   (or `query_ip_whitelist.txt`) in your TeamSpeak 3 server folder.
   **Restart the TS3 server afterwards!**

2. Unzip all files to your web server document root.

3. Copy `config.php.example` to `config.php` and edit it with your TS3 server data:
   ```php
   $server[0]['alias'] = "My Server";
   $server[0]['ip']    = "127.0.0.1";
   $server[0]['tport'] = "10011";
   ```

4. Set the following folders to writable by the web server (`chmod 777` or equivalent):
   - `icons/`
   - `temp/`
   - `templates_c/`
   - `site/backups/` (and all subfolders — created automatically on first use)

5. Open `index.php` in your browser.

6. Login with your TeamSpeak 3 ServerQuery credentials.

7. Have fun!

## Notes

- The `site/backups/` subdirectories are created automatically on first use.
- If you use Nginx, make sure PHP-FPM is configured correctly.
- The web server IP **must** be in the TS3 allowlist, otherwise the login will fail.
