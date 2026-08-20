<?php
/*
*Copyright (C) 2012-2013  Psychokiller
*Copyright (C) 2026 nfsmw15 - PHP 8.x port
*
*This program is free software; you can redistribute it and/or modify it under the terms of 
*the GNU General Public License as published by the Free Software Foundation; either 
*version 3 of the License, or any later version.
*
*This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
*without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
*See the GNU General Public License for more details.
*
*You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>. 
*/
function create_channel_tree($pid, $place, $alldata)
	{
	$tree='';
	foreach($alldata AS $key=>$value)
		{
		if ($pid==$value['pid'])
			{
			if(preg_match("/^\[([\S]*)spacer([\d]+)\]$/", $value['channel_name'], $treffer) AND $value['pid']==0 AND $value['channel_flag_permanent']==1)
			{
			$getspacer=explode($treffer[0], $value['channel_name']);
			$checkspacer=$getspacer[1][0].$getspacer[1][0].$getspacer[1][0];
			if($treffer[1]=="*" or strlen($getspacer[1])==3 AND $checkspacer==$getspacer[1])
				{
				$spacer='';
				for($i=0; $i<=50; $i++)
					{
					if(strlen($spacer)<50)
						{
						$spacer.=$getspacer[1];
						}
						else
						{
						break;
						}
					}
				$tree .= "<div class='channel'>".$place."<div class='channame'>".$spacer."</div></div><div class='clear'></div>";
				}
				elseif($treffer[1]=="c")
				{
				$spacer=explode($treffer[0], $value['channel_name']);
				$tree .= "<div class='channel'>".$place."<div class='channame' style=\"width:90%;text-align:center\">".$spacer[1]."</div></div><div class='clear'></div>";
				}
				elseif($treffer[1]=="r")
				{
				$spacer=explode($treffer[0], $value['channel_name']);
				$tree .= "<div class='channel'>".$place."<div class='channame' style=\"float:right\">".$spacer[1]."</div></div><div class='clear'></div>";
				}
				else
				{
				$spacer=explode($treffer[0], $value['channel_name']);
				$tree .= "<div class='channel'>".$place."<div class='channame'>".$spacer[1]."</div></div><div class='clear'></div>";
				}
			}
			else
			{
			$tree.= "<div class='channel'>".$place."<div class='chanimg'>&nbsp;</div><div class='channame'>".secure(str_replace("\\s", " ", $value['channel_name']))."</div><div class='clear'></div></div>\n";
			}
			$tree.= create_channel_tree($value['cid'], $place."<div class='place'>&nbsp;</div>", $alldata);
			}
		}
	return $tree;
	}

function secure($replace)
	{
	$replace=str_replace("<", "&lt;", $replace);
	$replace=str_replace(">", "&gt;", $replace);
	$replace=str_replace("\"", "&quot;", $replace);
	
	return $replace;
	}

function getPermsCommand($mode, $id, $perms) 
	{
	switch($mode)
		{
		case 1;
			$command="servergroupaddperm sgid=".$id." ";
			$delcommand="servergroupdelperm sgid=".$id." ";
			break;
		case 2;
			$command="channelgroupaddperm cgid=".$id." ";
			$delcommand="channelgroupdelperm cgid=".$id." ";
			break;
		case 3;
			$command="channeladdperm cid=".$id." ";
			$delcommand="channeldelperm cid=".$id." ";
			break;
		case 4;
			$command="clientaddperm cldbid=".$id." ";
			$delcommand="clientdelperm cldbid=".$id." ";
			break;
		}
	$permString='';
	$executeString='';
	$count=1;
	foreach($perms AS $key=> $value)
		{
		$delperms='';
		if($value['delete']==1)
			{
			$delperms.=$delcommand."permid=".$key."\n";
			}
			else
			{
			if($count>=150)
				{
				$executeString.=$command.$permString."\n";
				$permString='';
				$count=1;
				}
			switch($mode)
				{
				case 1:
					$permString.="permid=".$key." permvalue=".$value['value']." permnegated=".$value['negated']." permskip=".$value['skip']."|";
					break;
				case 2:
					$permString.="permid=".$key." permvalue=".$value['value']."|";
					break;
				case 3:
					$permString.="permid=".$key." permvalue=".$value['value']."|";
					break;
				case 4:
					$permString.="permid=".$key." permvalue=".$value['value']." permskip=".$value['skip']."|";
					break;
				}
			$count++;
			}
		}
	$executeString.=$command.$permString."\n".$delperms;
	return $executeString;
	}
	
function check_version_consistency($dirtylisturl)
	{
	// Only do the live network check every few hours - this used to run on
	// every single page load and added several seconds of latency each time.
	$cachefile=TS3WI_DIR.DS.'temp'.DS.'.versioncheck';
	$interval=21600; // 6 hours
	if(file_exists($cachefile) AND (time()-(int)@file_get_contents($cachefile))<$interval)
		{
		return;
		}

	// Fetch dirtylist via cURL (more reliable than file_get_contents)
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $dirtylisturl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // avoid multi-second stalls on dead IPv6 routes
	$file=curl_exec($ch);
	$httpcode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	// Throttle from here regardless of outcome, so an unreachable/slow endpoint
	// doesn't force a full network call (and its latency) on every page load.
	@file_put_contents($cachefile, time());

	// Only enforce if we got a valid response
	if($file===false OR $httpcode!==200)
		{
		return;
		}

	// Each line in dirtylist.txt is a required string that must appear in the source files
	$requiredstrings=array_filter(array_map('trim', explode("\n", $file)));
	foreach($requiredstrings AS $required)
		{
		if(empty($required) OR substr($required, 0, 1)==='#')
			{
			continue; // skip empty lines and comments
			}
		// Check if required copyright string exists in the main PHP files
		$found=false;
		$checkfiles=array(
			TS3WI_DIR.DS.'index.php',
			TS3WI_DIR.DS.'functions.inc.php',
			TS3WI_DIR.DS.'header.php',
			);
		foreach($checkfiles AS $checkfile)
			{
			if(file_exists($checkfile) AND strpos(file_get_contents($checkfile), $required)!==false)
				{
				$found=true;
				break;
				}
			}
		if(!$found)
			{
			// Required copyright string missing - lock out
			session_unset();
			session_destroy();
			die('This software has been modified in violation of its license terms. Required attribution has been removed.');
			}
		}
	}
	
function channel_backup_create($path, $channellist)
	{
	global $ts3;
	$handler=@fopen($path, "a+");
	if($handler===false)
		{
		return false;
		}
		else
		{
		$count=1;
		$count_chans=count($channellist);
		foreach($channellist AS $key=>$value)
			{
			$settings='';
			$count2=1;
			foreach($value AS $key2=>$value2)
				{
				$count_settings=count($value);
				$settings.=$key2."=".str_replace(' ', '\s',$value2);
				if($count2!=$count_settings)
					{
					$settings.=" ";
					}
				$count2++;
				}
			$channelperms=$ts3->channelPermList($value['cid']);
			if($channelperms['success']===true)
				{
				$settings.="<perms>";
				$count3=1;
				$count_perms=count($channelperms['data']);
				foreach($channelperms['data'] AS $key3=>$value3)
					{
					$count4=1;
					$count_permsettings=count($value3);
					foreach($value3 AS $key4=>$value4)
						{
						if($key4!="cid")
							{
							$settings.=$key4."=".$value4;
							if($count4!=$count_permsettings)
								{
								$settings.=" ";
								}
							}
						}
					if($count3!=$count_perms)
						{
						$settings.="|";
						}
					}
				$settings.="</perms>";
				}
			if($count!=$count_chans)
				{
				$settings.="||";
				}
				
			if(@!fwrite($handler, $settings))
				{
				return false;
				}
			$count++;
			}
		fclose($handler);
		}
	return true;
	}
	
function channel_backup_deploy($path)
	{
	global $ts3;
	$handler=@file($path);
	if($handler===false)
		{
		return false;
		}
		else
		{
		$getdata=explode('||',$handler[0]);
		foreach($getdata AS $key=>$value)
			{
			$channelsettings=explode('<perms>',$value);
			$channelperms=explode('</perms>', $channelsettings[1]);
			$getsettings=explode(' ', $channelsettings[0]);
			$getperms=explode('|', $channelperms[0]);
			foreach($getperms AS $key2=>$value2)
				{
				$getpermsettings=explode(' ', $value2);
				foreach($getpermsettings AS $key3=>$value3)
					{
					$settings=explode('=', $value3);
					if(!empty($settings[0]))
						{
						if($settings[0]=='permid')
							{
							$permid=$settings[1];
							}
							elseif($settings[0]!='permnegated' AND $settings[0]!='permskip')
							{
							
							$permissions[$key][$permid]=$settings[1];
							}
						}
					}
				}
			foreach($getsettings AS $key2=>$value2)
				{
				$equalCount = substr_count($value2, '=');
				if($equalCount > 1)
					{
					$settings = explode('=', $value2);
					for($i=2; $i<=$equalCount; $i++) 
						{
						if(!empty($settings[$i])) 
							{
							$settings[1].= '='.$settings[$i];
							}
						else
							{
							$settings[1].= '=';
							}
						}
					}
				else
					{
					$settings=explode('=', $value2);
					}
				
				if(!empty($settings[0]))
					{
					$backup[$key][$settings[0]]=$settings[1];
					}
				$backup[$key]['perms']=$permissions[$key];
				}
			}
		}
	return $backup;
	}

function channel_backup_deploy_action($channellist, $pid, $backup, $newcid, $firstrun=1)
	{
	global $ts3;

	if($firstrun==1)
		{
		$rename_def=0;
			
		foreach($channellist AS $key => $value)
			{
			if($rename_def==0)
				{
				$newsettings['channel_name']='Auto delete after backup';
				$newsettings['channel_flag_permanent']='1';
				$newsettings['channel_flag_semi_permanent']='0';
				$newsettings['channel_flag_default']='1';
				$ts3->channelEdit($value['cid'], $newsettings);
				$rename_def=$value['cid'];
				}
				else
				{
				$test=$ts3->channelDelete($value['cid']);
				}
			}
		}
			
	foreach($backup AS $key=>$value)
		{
		if ($pid==$value['pid'])
			{
			$settings['channel_name']=isset($value['channel_name']) ? $value['channel_name']:'';
			if($value['pid']!=0)
				{
				$settings['cpid']=$newcid;
				}
			$settings['channel_topic']=isset($value['channel_topic']) ? $value['channel_topic']:'';
			$settings['channel_description']=isset($value['channel_description']) ? $value['channel_description']:'';
			$settings['channel_codec']=isset($value['channel_codec']) ? $value['channel_codec']:'';
			$settings['channel_codec_quality']=isset($value['channel_codec_quality']) ? $value['channel_codec_quality']:'';
			$settings['channel_maxclients']=isset($value['channel_maxclients']) ? $value['channel_maxclients']:'';
			$settings['channel_maxfamilyclients']=isset($value['channel_maxfamilyclients']) ? $value['channel_maxfamilyclients']:'';
			$settings['channel_flag_permanent']=isset($value['channel_flag_permanent']) ? $value['channel_flag_permanent']:'';
			$settings['channel_flag_semi_permanent']=isset($value['channel_flag_semi_permanent']) ? $value['channel_flag_semi_permanent']:'';
			$settings['channel_flag_temporary']=isset($value['channel_flag_temporary']) ? $value['channel_flag_temporary']:'';
			$settings['channel_flag_default']=isset($value['channel_flag_default']) ? $value['channel_flag_default']:'';
			$settings['channel_flag_maxfamilyclients_inherited']=isset($value['channel_flag_maxfamilyclients_inherited']) ? $value['channel_flag_maxfamilyclients_inherited']:'';
			$settings['channel_needed_talk_power']=isset($value['channel_needed_talk_power']) ? $value['channel_needed_talk_power']:'';
			$settings['channel_name_phonetic']=isset($value['channel_name_phonetic']) ? $value['channel_name_phonetic']:'';
			$cid=$ts3->channelCreate($settings);
			$permid=$ts3->getElement('data', $ts3->permIdGetByName(array('i_group_needed_modify_power')));
			$ts3->channelAddPerm($cid['data']['cid'], $value['perms']);
			if($cid['success']===false)
				{
				return false;
				}
			channel_backup_deploy_action('', $value['cid'], $backup, $cid['data']['cid'], 0);
			}
		}
	
	if(isset($rename_def) AND $rename_def!=0)
		{
		$ts3->channelDelete($rename_def);
		}
		
	 return true;
	}

function conv_traffic($bytes)
	{
	if ($bytes<1024)
		{
		$ret=$bytes."Bytes";
		}
	elseif($bytes<1048576)
		{
		$ret=round(($bytes/1024), 2)."KiB";
		}
	elseif($bytes<1073741824)
		{
		$ret=round(($bytes/1048576), 2)."MiB";
		}
	elseif($bytes<1099511627776)
		{
		$ret=round(($bytes/1073741824), 2)."GiB";
		}
	return $ret;
	}

function check_wi_version($wiversion, $wiversionurl)
	{
	// Only hit the network every few hours - this used to run on every
	// single page load (two chained HTTP requests) and added latency each time.
	$cachefile=TS3WI_DIR.DS.'temp'.DS.'.wiversioncheck';
	$interval=21600; // 6 hours
	if(file_exists($cachefile))
		{
		$cached=json_decode((string)@file_get_contents($cachefile), true);
		if(is_array($cached) AND isset($cached['time'], $cached['result']) AND (time()-$cached['time'])<$interval)
			{
			return $cached['result'];
			}
		}

	$result=check_wi_version_impl($wiversion, $wiversionurl);
	@file_put_contents($cachefile, json_encode(array('time'=>time(), 'result'=>$result)));
	return $result;
	}

function check_wi_version_impl($wiversion, $wiversionurl)
	{
	// Fetch wiversion.html which contains API-URL|Base-Download-URL
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $wiversionurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // avoid multi-second stalls on dead IPv6 routes
	$response=trim(curl_exec($ch));
	$httpcode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if($response===false OR $httpcode!==200)
		{
		return true;
		}

	// Parse API-URL and base download URL separated by |
	$parts=explode('|', $response, 2);
	if(count($parts)!==2)
		{
		return true;
		}

	$apiurl=trim($parts[0]);
	$basedownloadurl=trim($parts[1]);

	// Security: only allow https:// URLs
	if(substr($apiurl, 0, 8)!=='https://' OR substr($basedownloadurl, 0, 8)!=='https://')
		{
		return true;
		}

	// Fetch latest release from API
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // avoid multi-second stalls on dead IPv6 routes
	curl_setopt($ch, CURLOPT_USERAGENT, 'ts3web-updatecheck');
	$json=curl_exec($ch);
	$httpcode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if($json===false OR $httpcode!==200)
		{
		return true;
		}

	$data=json_decode($json, true);
	if(!isset($data['tag_name']))
		{
		return true;
		}

	$latestversion=trim($data['tag_name']);

	// Security: validate version format - only allow v3.x.x.x-php8 pattern
	if(!preg_match('/^v[\d]+\.[\d]+\.[\d]+(?:\.[\d]+)?(?:-php8)?$/', $latestversion))
		{
		return true;
		}

	// Security: validate download URL starts with base URL
	$downloadurl=$basedownloadurl.$latestversion;
	if(substr($downloadurl, 0, strlen($basedownloadurl))!==$basedownloadurl)
		{
		return true;
		}

	// Compare versions
	$get_number=str_replace(array('beta', 'v', '-php8'), '', $wiversion);
	$get_number2=str_replace(array('beta', 'v', '-php8'), '', $latestversion);
	if($wiversion===$latestversion OR version_compare($get_number, $get_number2, '>='))
		{
		return true;
		}
		else
		{
		return "<a class=\"warning\" href=\"".$downloadurl."\" target=\"_blank\">".$latestversion."</a>";
		}
	}
	
	
	
function parse_bbcode($txt)
	{
	$txt=str_replace("8)", "<img src='gfx/images/cool.png' alt='8)' />", $txt);
	$txt=str_replace(":-(", "<img src='gfx/images/sad.png' alt='8)' />", $txt);
	$txt=str_replace(":-)", "<img src='gfx/images/smile.png' alt='8)' />", $txt);
	$txt=str_replace(";-)", "<img src='gfx/images/twinkle.png' alt='8)' />", $txt);
	
	$txt=preg_replace("^\[b\](.*)\[/b\]^isU", "<b>$1</b>", $txt);
	$txt=preg_replace("^\[i\](.*)\[/i\]^isU", "<i>$1</i>", $txt);
	$txt=preg_replace("^\[u\](.*)\[/u\]^isU", "<u>$1</u>", $txt);
	$txt=preg_replace("^\[url\](.*)\[/url\]^isU", "<a href=\"$1\">$1</a>", $txt);
	$txt=preg_replace("^\[url=(.*)\](.*)\[/url\]^isU", "<a href=\"$1\">$2</a>", $txt);
	$txt=preg_replace("^\[color=(.*)\](.*)\[/color\]^isU", "<font color=\"$1\">$2</font>", $txt);
	$txt=preg_replace("^\[img\](.*)\[/img\]^isU", "<img src=\"$1\" alt=\"$1\" />", $txt);
	
	return $txt;
	}
?>