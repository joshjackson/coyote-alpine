<?
// File: 	webadmin.php
// Purpose: 	Web Administrator Firmware Addon configuration routines
// Product:	Coyote Linux
// Date:	10/14/2005
// Author:	Joshua Jackson <jjackson@vortech.net>
//
// Copyright (c)2005, 2024 Vortech Consulting, LLC
//
// 10/9/2024 - Modifications for Coyote Linux 3.1

	require_once("fwaddon.php");

	class WebAdminService extends FirmwareAddon {

		var	$http;

		function load_http($confstmt) {
			if ( "$confstmt[1] $confstmt[2]" == "server enable") {
				$this->http["enable"] = true;
				if ($confstmt[3]) {
					$this->http["port"] = $confstmt[3];
				}
			} else {
				$this->http["hosts"][count($this->http["hosts"])] = $confstmt[1];
			}
		}

		function WebAdminService() {
			// Initialize the Web Admin addon
			$this->http = array(
				"enable" => false,
				"port" => 443,
				"hosts" => array()
			);
		}

		function ProcessStatement($confstmt) {
			// Called to process a configuration directive. Will
			// return true if it was handled or false if it was not
			$handled = true;

			switch ($confstmt[0]) {
				case "http":
					$this->load_http ($confstmt);
					break;
					;;
				default:
					$handled = false;
			}
			return $handled;
		}

		function ApplyAcls($Config = "", $do_flush=false) {

			if ($do_flush) {
				sudo_exec("iptables -F http hosts 1> /dev/null 2> /dev/null");
			}

			sudo_exec("iptables -N http-hosts");
			sudo_exec("iptables -A coyote-local-acls -p tcp --syn --dport ".$this->http["port"]." -j http-hosts");
			# Apply HTTP access ACLs
			for($t=0; $t < count($this->http["hosts"]); $t++)
				sudo_exec("iptables -A http-hosts -s ".$this->http["hosts"][$t]." -j accept-packet-local");
		}

		function StartService($Config = "", $apply_acls=true) {
			// Check if service is already running
			if (file_exists("/var/run/httpd.pid")) {
				return false;
			}

			if ($this->http["enable"]) {
				if ($apply_acls) {
					$this->ApplyAcls($Config);
				}
				sudo_exec("/usr/sbin/httpd -f /opt/coyote/config/httpd.conf 1> /dev/null 2> /dev/null");
				return true;
			}

			return false;
		}

		function StopService($Config = "") {
			// Check if service is running
			if (!file_exists("/var/run/httpd.pid")) {
				return false;
			}
			$pid = GetServicePID("/var/run/httpd.pid");
			if ($pid) {
				return posix_kill($pid, 15);
			} else {
				return false;
			}

			// Remove the traffic ACL
			sudo_exec("iptables -F http-hosts");
			sudo_exec("iptables -D coyote-local-acls -j http-hosts");
			sudo_exec("iptables -X http-hosts");

			return true;
		}

		function ResetService () {

			if (file_exists("/var/run/httpd.pid")) {
				$this->StopService();
			}

			@unlink("/opt/coyote/www/.htpasswd");
			touch("/opt/coyote/www/.htpasswd");
			chmod("/opt/coyote/www/.htpasswd", 0600);
			@unlink("/opt/coyote/config/httpd/httpd.conf");
			@unlink("/var/state/httpd/server.pem");
		}

		// This function prevents the web service from being stopped by the
		// web administration front end
		function IsWebService() {
			return true;
		}

		function BuildConfig($Config) {

			if (!$this->http["enable"]) {
				return false;
			}

			$hostname = $Config->hostname;
			// Make sure the server host certificates have been generated
			if (! file_exists("/opt/coyote/config/ssl.d/".$hostname."_cert.pem")) {
				// Generate a new set of certificates
				sudo_exec("/opt/coyote/sysconf/gencerts new");
			}

			if (file_exists("/var/state/httpd/server.pem")) {
				unlink("/var/state/httpd/server.pem");
				touch("/var/state/httpd/server.pem");
				chmod("/var/state/httpd/server.pem", 0600);
			}

			do_exec("cat /opt/coyote/config/ssl.d/".$hostname."_priv.pem >> /var/state/httpd/server.pem");
			do_exec("cat /opt/coyote/config/ssl.d/".$hostname."_cert.pem >> /var/state/httpd/server.pem");

			copy_template("httpd.conf", "/opt/coyote/config/httpd.conf");

			write_config("/opt/coyote/config/httpd.conf", "Listen ".$this->http["port"]);
			write_config("/opt/coyote/config/httpd.conf", "ServerAdmin admin@".$hostname);
			write_config("/opt/coyote/config/httpd.conf", "ServerName ".$hostname);
			return true;
		}

		function OutputConfig() {
			// Returns an array of strings that should be stored in the config file
			$output = array();
			if ($this->http["enable"]) {
				$outstr = "http server enable";
				if ($this->http["port"] != 443)
					$outstr .= " ". $this->http["port"];
				array_push($output, $outstr);
			}
			for ($t=0; $t < count($this->http["hosts"]); $t++) {
				array_push($output, "http ". $this->http["hosts"][$t]);
			}
			return $output;
		}

	}

	$NewAddon = new WebAdminService();

?>
