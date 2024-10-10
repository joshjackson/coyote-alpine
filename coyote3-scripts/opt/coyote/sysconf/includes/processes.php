<?
// Script: processes
// Purpose: process execution routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004
//
// 10/9/2024 - Add sudo command to exec functions.

require_once("defines.php");

function do_exec($cmd) {

	global $DEBUG_MODE;

	$errcode = 0;

	debug_print($cmd."\n");

	if (!($DEBUG_MODE && DEBUG_NOEXEC)) {
		exec($cmd, $outstr, $errcode);
	}

	return $errcode;
}

function sudo_exec($cmd) {
	// If we are already running as root, skip using sudo
	if (posix_geteuid() == 0) {
		do_exec($cmd);
	} else {
		do_exec("sudo ". $cmd);
	}
}

function check_depmod() {
	sudo_exec("depmod -a -q 1> /dev/null 2> /dev/null");
}

function load_module($modname) {

	if (is_array($modname)) {
		$modstr = implode(" ", $modname);
	} else {
		$modstr = $modname;
	}
	sudo_exec("modprobe -qs $modstr 1> /dev/null 2> /dev/null");
}

function GetServicePID($pidfile) {

	$pidhandle = fopen($pidfile, "r");
	$pidbuf = intval(trim(fgets($pidhandle, 128)));
	fclose($pidhandle);

	debug_print("Read PID $pidbuf from $pidfile\n");

	return $pidbuf;
}

?>
