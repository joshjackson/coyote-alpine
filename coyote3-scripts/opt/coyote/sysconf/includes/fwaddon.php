<?
//-----------------------------------------------------------------------------
// File: 		fwaddon.php
// Purpose: 	Firmware Addon class skeleton
// Product:		Vortech Embedded Linux
// Date:		10/14/2005
// Author:		Joshua Jackson <jjackson@vortech.net>
//
// Copyright (c)2005 Vortech Consulting, LLC

	class FirmwareAddon {

		var	$config;

		//function FirmwareAddon() {
		function __construct() {
			$this->config = array();
		}

		function ProcessStatment($confstmt) {
			// Called to process a configuration directive. Will
			// return true if it was handled or false if it was not
			return false;
		}

		function SysInitService() {
			return true;
		}

		function ResetService() {
			return true;
		}

		function StartService($Config, $apply_acls=true) {
			return true;
		}

		function StopService() {
			return true;
		}

		function ApplyAcls($Config, $do_flush=false) {
		}

		// Indicates wether or not this addon is a web based service
		function IsWebService() {
			return false;
		}

		// Indicates wether or not this addons is an SSH based service
		function IsSSHService() {
			return false;
		}

		function OutputConfig() {
			// Returns an array of strings that should be stored in the config file
			$output = array();
			return $output;
		}
	}

?>