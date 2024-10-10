<?
// Script: functions
// Purpose: general function include file
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004
	putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");

	require_once("defines.php")
	// Debug bitmask to determine if debug_print(s) are displayed and wether or not
	// to actually execute commands on the system
	$DEBUG_MODE = DEBUG_NONE;

	// Some functions are configured to behave differently when Coyote is in 
	// development mode
	$IN_DEVELOPMENT = false;

	require_once("hardware.php");
	require_once("network.php");
	require_once("filesystem.php");
	require_once("processes.php");
	require_once("consoleio.php");
	require_once("qos.php");
	require_once("fwaddon.php");


?>