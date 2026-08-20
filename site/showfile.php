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
session_start();
$ip='';
if(isset($_SESSION['logged']) AND $_SESSION['logged']==true OR isset($_SESSION['pubviewer']) AND $_SESSION['pubviewer']==true)
	{
	if(isset($_GET['ip']))
		{
		$ip=$_GET['ip'];
		}
		elseif(isset($_SESSION['server_ip']))
		{
		$ip=$_SESSION['server_ip'];
		}
	// $ip/port become directory name components below - only allow characters
	// valid in a hostname/IPv4/IPv6, never path separators or "..".
	if(!preg_match('/^[a-zA-Z0-9.:_-]+$/', $ip)) {$ip='';}
	$port=(isset($_GET['port']) && preg_match('/^\d+$/', $_GET['port'])) ? $_GET['port'] : '';
	$name=str_replace("\\","",$_REQUEST['name']);
	$name=str_replace("/","",$name);
	if(str_replace('icon_', '', $name)==100 OR str_replace('icon_', '', $name)==200 OR str_replace('icon_', '', $name)==300 OR str_replace('icon_', '', $name)==500 OR str_replace('icon_', '', $name)==600)
			{
			$path='../icons/';
			}
			else
			{
			$path='../icons/'.$ip.'-'.$port.'/';
			}

	$mime=getimagesize($path.$name);

	if(strpos($mime['mime'], "image")===false)
		{
		die('error');
		}
		else
		{
		header('Content-type: '.$mime['mime']);
		
		echo file_get_contents($path.$name);
			
		}
	}
	else
	{
	echo "The file can not be executed alone.";
	}
?>