<?
// PRODUCT: WOLVERINE
// OPTIONAL: FALSE
// ENCODE: TRUE
//-----------------------------------------------------------------------------
// File: 			vpnsvc.php
// Purpose: 	IPSEC / PPTP Firmware Addon configuration routines
// Product:		Wolverine Firewall and VPN Server
// Date:			10/14/2005
// Author:		Joshua Jackson <jjackson@vortech.net>
//
// Copyright (c)2005 Vortech Consulting, LLC
//
// 01/09/2025 - Updates for Coyote 3.1

// Make sure we are running from the appropriate product

require_once("../functions.php");

define ('IPSEC_CONF', COYOTE_CONFIG_DIR."strongswan/swanctl.conf");
define ('SPD_CONF', COYOTE_CONFIG_DIR."strongswan/spd.conf");
define ('IPSEC_SECRETS', COYOTE_CONFIG_DIR."strongswan/psk.txt");


class VPNSVCAddon extends FirmwareAddon {

	var $authentication = array();
	var $radius = array();
	var	$ipsec = array();
	var $pptp = array();

//-----------------------------------------------------------------------------
//
//
	//function VPNSVCAddon() {
	function __construct() {
		parent::__construct();
		// Initialize the VPN addon
		$this->radius = array(
			"servers" => array(),
			"key" => "",
			"start-ip" => "",
			"end-ip" => "",
			"same-server" => true,
			"nas-id" => "",
			"try-next-on-fail" => false
		);

		$this->pptp = array(
			"enable" => false,
			"local-address" => "",
			"address-pool" => "",
			"dns" => array(),
			"wins" => array(),
			"users" => array(),
			"disable-mppe" => false,
			"proxyarp" => false,
			"hosts" => array()
		);

		$this->ipsec = array(
			"enable" => true,
			"autostart" => false,
			"tunnels" => array()
		);

	}

//-----------------------------------------------------------------------------
//
//
	function load_authentication($confstmt) {
		$service = $confstmt[1];
		array_shift($confstmt);
		array_shift($confstmt);
		$this->authentication["$service"] = $confstmt;
	}


//-----------------------------------------------------------------------------
//
//
	function load_pptp($confstmt) {

		switch ($confstmt[1]) {
			case "server":
				if ($confstmt[2] == "enable") {
					$this->pptp["enable"] = true;
				}
				break;
				;;
			case "local-address":
				$this->pptp["local-address"] = $confstmt[2];
				break;
				;;
			case "address-pool":
				$this->pptp["address-pool"] = $confstmt[2].":".$confstmt[3];
				break;
				;;
			case "dns-server":
				array_push($this->pptp["dns"], $confstmt[2]);
				break;
				;;
			case "wins-server":
				array_push($this->pptp["wins"], $confstmt[2]);
				break;
				;;
			case "disable-mppe":
				$this->pptp["disable-mppe"] = true;
				break;
				;;
			case "proxyarp":
				$this->pptp["proxyarp"] = true;
				break;
				;;
			case "user":
				$this->pptp["users"][count($this->pptp["users"])] = array(
					"username" => $confstmt[2],
					"password" => $confstmt[3],
					"ip" => $confstmt[4]
				);
				break;
				;;
			case "host":
				$this->pptp["hosts"][count($this->pptp["hosts"])] = $confstmt[2];
				break;
				;;
		}
	}

//-----------------------------------------------------------------------------
//
//
	function load_radius($confstmt) {

		switch ($confstmt[1]) {
			case "server":
				$newsrv = array (
					"host" => $confstmt[2],
					"authport" => ($confstmt[3]) ? $confstmt[3] : "1812",
					"acctport" => ($confstmt[4]) ? $confstmt[4] : "1813"
				);
				array_push($this->radius["servers"], $newsrv);
				break;
				;;
			case "key":
				$this->radius["key"] = $confstmt[2];
				break;
				;;
		}

	}

//-----------------------------------------------------------------------------
//
//
	function load_ipsec($confstmt) {

		if ($confstmt[1] == "tunnel") {
			array_shift($confstmt);

			if (!array_key_exists($confstmt[1], $this->ipsec["tunnels"])) {
				$this->ipsec["tunnels"][$confstmt[1]] = array(
					"ike" => array(
						"cipher" => array(),
						"hash" => array()
					),
					"esp" => array(
						"cipher" => array(),
						"hash" => array()
					)
				);
			}

			switch ($confstmt[2]) {
				case "auth":
					$this->ipsec["tunnels"][$confstmt[1]]["p1authtype"] = $confstmt[3];
					if ($confstmt[3] == "psk") {
						$this->ipsec["tunnels"][$confstmt[1]]["p1psk"] = $confstmt[4];
					} else {
						$this->ipsec["tunnels"][$confstmt[1]]["cert"] = $confstmt[4];
					}
					break;
					;;

				case "ike":
					switch($confstmt[3]) {
						case "cipher":
							$this->ipsec["tunnels"][$confstmt[1]]["ike"]["cipher"] = $confstmt[4];
							break;
							;;
						case "hash":
							$this->ipsec["tunnels"][$confstmt[1]]["ike"]["hash"] = $confstmt[4];
							break;
							;;
						case "lifetime":
							$this->ipsec["tunnels"][$confstmt[1]]["ike"]["lifetime"] = $confstmt[4];
							break;
							;;
						case "dh-group":
							$this->ipsec["tunnels"][$confstmt[1]]["ike"]["dh-group"] = $confstmt[4];
							break;
							;;
						}
					break;
					;;
				case "esp":
					switch($confstmt[3]) {
						case "cipher":
							$this->ipsec["tunnels"][$confstmt[1]]["esp"]["cipher"] = $confstmt[4];
							break;
							;;
						case "hash":
							$this->ipsec["tunnels"][$confstmt[1]]["esp"]["hash"] = $confstmt[4];
							break;
							;;
						case "lifetime":
							$this->ipsec["tunnels"][$confstmt[1]]["esp"]["lifetime"] = $confstmt[4];
							break;
							;;
						case "pfs-group":
							$this->ipsec["tunnels"][$confstmt[1]]["esp"]["pfs-group"] = $confstmt[4];
							break;
							;;
						}
					break;
					;;
				case "interface":
					$this->ipsec["tunnels"][$confstmt[1]]["interface"] = $confstmt[3];
					break;
					;;
				case "local-address":
					$this->ipsec["tunnels"][$confstmt[1]]["local-address"] = $confstmt[3];
					break;
					;;
				case "disable":
					$this->ipsec["tunnels"][$confstmt[1]]["disabled"] = true;
					break;
					;;
				default:
					$this->ipsec["tunnels"][$confstmt[1]]["localsub"] = $confstmt[2];
					$this->ipsec["tunnels"][$confstmt[1]]["remotesub"] = $confstmt[3];
					$this->ipsec["tunnels"][$confstmt[1]]["remotegw"] = $confstmt[5];
					break;
					;;
			}
		} else {
			switch ($confstmt[1]) {
				case "disable":
					$this->ipsec["enable"] = false;
					break;
					;;
			}
		}
	}

//-----------------------------------------------------------------------------
//
//
	function ipsec_apply_acls($do_flush = false) {

		if ($do_flush) {
			do_exec("iptables -F ipsec-input");
		} else {
			// Create the IPSEC firewall chain and attach it to the local ACL
			sudo_exec("iptables -N ipsec-input");
			sudo_exec("iptables -A wolv-local-acls -j ipsec-input");
		}

		if (!$this->ipsec["enable"]) {
			return;
		}

		sudo_exec("iptables -A ipsec-input -p 50 -mstate --state NEW -j accept-packet");
		sudo_exec("iptables -A ipsec-input -p 51 -mstate --state NEW -j accept-packet");
		sudo_exec("iptables -A ipsec-input -p udp --sport 500 --dport 500 -mstate --state NEW -j accept-packet");

	}

//-----------------------------------------------------------------------------
//
//
	function ipsec_flush_keys() {
		// Flush any existing key information
		sudo_exec("/usr/local/sbin/setkey -FP");
		sudo_exec("/usr/local/sbin/setkey -F");
	}

//-----------------------------------------------------------------------------
//
//
	function ipsec_start_service($Config, $apply_acls=true) {

		if (!$this->ipsec["enable"]) {
			return false;
		}

		$certfile = "/mnt/config/ipsec.d/".$Config->hostname."_cert.pem";

		if (!file_exists($certfile)) {
			write_error("BUG: Local x509 certificate missing, IPSEC disabled.");
			$this->ipsec["enable"] = false;
			return false;
		}

		if ($this->ipsec["autostart"])
			return true;

		// If racoon is already running, instruct it to reload its config
		if (file_exists("/var/run/racoon.pid")) {
			sudo_exec("/usr/local/sbin/racoonctl reload-config");
			return true;
		} else {
			// Enable IPSEC traffic
			if ($apply_acls) {
				$this->ipsec_apply_acls(false);
			}
			$this->ipsec_flush_keys();
			// Load the config files and start IPSEC
			sudo_exec("/usr/local/sbin/setkey -c < ".SPD_CONF." 1> /dev/null 2> /dev/null");
			sudo_exec("/usr/local/sbin/racoon -f ".IPSEC_CONF." 1> /dev/null 2> /dev/null&");
			return true;
		}
	}

//-----------------------------------------------------------------------------
//
//
	function ipsec_stop_service() {
		if (file_exists("/var/run/racoon.pid")) {
			sudo_exec("killall racoon 1> /dev/null 2> /dev/null");
			sleep(2);
			// Make sure the critter is dead, racoons can be dangerous when only wounded
			sudo_exec("killall -9 racoon 1> /dev/null 2> /dev/null");
			// Grumble, racoon doesn't clean up its PID file
			@unlink("/var/run/racoon.pid");
			$this->ipsec_flush_keys();

			// Disable IPSEC traffic
			sudo_exec('iptables -F ipsec-input');
			sudo_exec('iptables -D wolv-local-acls -j ipsec-input');
			sudo_exec('iptables -X ipsec-input');
		}
	}


//-----------------------------------------------------------------------------
//
//
	// Builds a list of interfaces that are relevant to IPSEC
	function ipsec_interface_list($Config) {

		$result = array();

		foreach($this->ipsec["tunnels"] as $tunnel) {
			if ($tunnel["interface"]) {
				$ifidx = $Config->get_if_index($tunnel["interface"]);
				if ($ifidx < 0)
					continue;
				$iface = $Config->interfaces[$ifidx];
				// Determine if this interface is PPPoE configured
				if (!is_array($iface["addresses"]) && ($iface["addresses"] == "pppoe")) {
					if (!in_array("ppp0", $result))
						array_push($result, "ppp0");
				} else {
					if (!in_array($iface["device"], $result))
						array_push($result, $iface["device"]);
				}
			}
		}

		if (!count($result)) {
			// If there are not interfaces specified manually, default to eth0
			$iface = $Config->interfaces[0];
			// Determine if this interface is PPPoE configured
			if (!is_array($iface["addresses"]) && ($iface["addresses"] == "pppoe")) {
				array_push($result, "ppp0");
			} else {
				array_push($result, $iface["device"]);
			}
		}

		return $result;

	}

//-----------------------------------------------------------------------------
//
//
	function ipsec_build_config($Config) {

		// Make sure than IPSEC is enabled and there are tunnels configured
		if (!count($this->ipsec["tunnels"]))
			$this->ipsec["enable"] = false;

		if (!$this->ipsec["enable"]) {
			return false;
		}

		if (file_exists(IPSEC_CONF))
			unlink(IPSEC_CONF);
		if (file_exists(SPD_CONF))
			unlink(SPD_CONF);
		if (file_exists(IPSEC_SECRETS))
			unlink(IPSEC_SECRETS);


		$hostname = $Config->hostname;

		write_config(IPSEC_CONF, "path pre_shared_key \"/etc/psk.txt\";");
		write_config(IPSEC_CONF, "path certificates \"/etc/ipsec.d/\";");

		foreach($this->ipsec["tunnels"] as $tunnel) {

			if ($tunnel["local-address"]) {
				$ep = $tunnel["local-address"];
			} else {
				$ifidx = 0;
				if ($tunnel["interface"]) {
					$ifidx = $Config->get_if_index($tunnel["interface"]);
					if ($ifidx < 0) {
						write_error("IPSEC: Failed to retrieve interface index for ".$tunnel["interface"]. ", defaulting to eth0.");
						$ifidx = 0;
					}
				}
				$ep = get_interface_ip($Config, $ifidx);
				if (!$ep) {
					write_error("IPSEC: Failed to retrieve local endpoint IP, skipping tunnel.");
					continue;
				}
			}


			//$spdconf .= "spdadd {$lansa}/{$lansn} {$lanip}/32 any -P in none;\n";
			//$spdconf .= "spdadd {$lanip}/32 {$lansa}/{$lansn} any -P out none;\n";

			write_config(SPD_CONF, "spdadd {$tunnel['localsub']} " .
				"{$tunnel['remotesub']} any -P out ipsec " .
				"esp/tunnel/{$ep}-" .
				"{$tunnel['remotegw']}/unique;");

			write_config(SPD_CONF, "spdadd {$tunnel['remotesub']} " .
				"{$tunnel['localsub']} any -P in ipsec " .
				"esp/tunnel/{$tunnel['remotegw']}-" .
				"{$ep}/unique;");


			write_config(IPSEC_CONF, "remote {$tunnel['remotegw']} {");
			write_config(IPSEC_CONF,"	exchange_mode main;");
			// Preshared key or x.509 certificates
			if ($tunnel["p1authtype"] == "psk") {
				write_config(IPSEC_CONF,"	my_identifier address;");
				write_config(IPSEC_CONF,"	peers_identifier address {$tunnel['remotegw']};");
				write_config(IPSEC_SECRETS, "{$tunnel['remotegw']}\t{$tunnel['p1psk']}");
			} else {
				write_config(IPSEC_CONF,"	my_identifier asn1dn;");
				write_config(IPSEC_CONF,"	peers_identifier asn1dn;");
				write_config(IPSEC_CONF,"	certificate_type x509 \"{$hostname}_cert.pem\" \"{$hostname}_priv.pem\";");
				write_config(IPSEC_CONF,"	peers_certfile x509 \"{$tunnel['cert']}\";");
			}
			write_config(IPSEC_CONF,"	initial_contact on;");
			write_config(IPSEC_CONF,"	support_proxy on;");
			write_config(IPSEC_CONF,"	proposal_check obey;");

			write_config(IPSEC_CONF,"	proposal {");
			write_config(IPSEC_CONF,"		encryption_algorithm {$tunnel['ike']['cipher']};");
			write_config(IPSEC_CONF,"		hash_algorithm {$tunnel['ike']['hash']};");
			// Preshared key or x.509 certificates
			if ($tunnel["p1authtype"] == "psk") {
				write_config(IPSEC_CONF,"		authentication_method pre_shared_key;");
			} else {
				write_config(IPSEC_CONF,"		authentication_method rsasig;");
			}
			write_config(IPSEC_CONF,"		dh_group {$tunnel['ike']['dh-group']};");

			if ($tunnel['ike']['lifetime'])
				write_config(IPSEC_CONF, "		lifetime time {$tunnel['ike']['lifetime']} secs;");

			write_config(IPSEC_CONF, "	}");

			if ($tunnel['ike']['lifetime'])
				write_config(IPSEC_CONF, "	lifetime time {$tunnel['ike']['lifetime']} secs;");

				//$racoonconf .= "	lifetime time {$tunnel['ike']['lifetime']} secs;";

			write_config(IPSEC_CONF, "}");

			$p2ealgos = $tunnel['esp']['cipher'];
			$p2halgos = $tunnel['esp']['hash'];

			write_config(IPSEC_CONF, "sainfo address {$tunnel['localsub']} any address {$tunnel['remotesub']} any {");
			write_config(IPSEC_CONF, "	encryption_algorithm {$p2ealgos};");
			write_config(IPSEC_CONF, "	authentication_algorithm {$p2halgos};");
			write_config(IPSEC_CONF, "	compression_algorithm deflate;");

			if ($tunnel['esp']['pfs-group'])
				write_config(IPSEC_CONF, "	pfs_group {$tunnel['esp']['pfs-group']};");

			if ($tunnel['esp']['lifetime'])
				write_config(IPSEC_CONF, "	lifetime time {$tunnel['esp']['lifetime']} secs;\n");

			write_config(IPSEC_CONF, "}");
		}


	/*
				$pskconf = "";

				if (is_array($ipseccfg['tunnel'])) {
					foreach ($ipseccfg['tunnel'] as $tunnel) {
						if (isset($tunnel['disabled']))
							continue;
						$pskconf .= "{$tunnel['remote-gateway']}	 {$tunnel['p1']['pre-shared-key']}\n";
					}
				}

				if (is_array($ipseccfg['mobilekey'])) {
					foreach ($ipseccfg['mobilekey'] as $key) {
						$pskconf .= "{$key['ident']}	{$key['pre-shared-key']}\n";
					}
				}

	*/
				if (file_exists(IPSEC_SECRETS)) {
					chmod(IPSEC_SECRETS, 0600);
				}

				// Start racoon
	/*
				foreach ($ipseccfg['tunnel'] as $tunnel) {
					if (isset($tunnel['auto'])) {
						$remotehost = substr($tunnel['remote-subnet'],0,strpos($tunnel['remote-subnet'],"/"));
						$srchost = vpn_endpoint_determine($tunnel, $curwanip);
						if ($srchost)
							mwexec_bg("/sbin/ping -c 1 -S {$srchost} {$remotehost}");
					}
				}
	*/

		return true;

	}

//-----------------------------------------------------------------------------
//
//
	function pptp_apply_acls($do_flush = false) {

		if ($do_flush) {
			sudo_exec("iptables -F pptp-input");
		} else {
			sudo_exec("iptables -N pptp-input");
			sudo_exec("iptables -A wolv-local-acls -j pptp-input");
		}

		// Allow PPTP connections to the firewall
		if ($this->pptp["enable"]) {
			if (!count($this->pptp["hosts"])) {
				sudo_exec("iptables -A pptp-input -p 47 -mstate --state NEW -j accept-packet");
				sudo_exec("iptables -A pptp-input -p tcp --dport 1723 -mstate --state NEW -j accept-packet");
			} else {
				for ($t=0; $t < count($this->pptp["hosts"]); $t++) {
					$saddr=$this->pptp["hosts"][$t];
					sudo_exec("iptables -A pptp-input -p 47 -mstate --state NEW -s $saddr -j accept-packet");
					sudo_exec("iptables -A pptp-input -p tcp --dport 1723  -s $saddr -mstate --state NEW -j accept-packet");
				}
			}
		}
	}


//-----------------------------------------------------------------------------
//
//
	function configure_radius() {

		if (!file_exists("/etc/ppp/pptp.options")) {
			copy_template("pptp.options", "/etc/ppp/pptp.options");
		}

		if (is_array($this->authentication["ppp"])) {
			if (!in_array("radius", $this->authentication["ppp"]))
				return;
		}

		if (is_array($this->radius["servers"]) && $this->radius["key"]) {
			$radsrv = "";
			foreach($this->radius["servers"] as $srvent) {
				if ($radsrv) {
					$radsrv .= ",";
				}
				$radsrv .= $srvent["host"] .":".$srvent["authport"]."/".$srvent["acctport"];
			}
			write_config("/etc/ppp/pptp.options", "radius-servers ".$radsrv);
			write_config("/etc/ppp/pptp.options", "radius-auth-key ".$this->radius["key"]);
		}
	}

//-----------------------------------------------------------------------------
//
//
	function pptp_config_users() {

		@unlink("/etc/ppp/local-secrets");
		foreach($this->pptp["users"] as $users) {
			if (!strlen($users["ip"])) {
				$users["ip"] = "*";
			}
			write_config("/etc/ppp/local-secrets", $users["username"]."\t".$users["password"]."\t".$users["ip"]);
		}
	}


//-----------------------------------------------------------------------------
//
//
	function pptp_build_config() {

		$this->pptp["enable"] = $this->pptp["enable"] && $this->pptp["local-address"] && $this->pptp["address-pool"];

		if ($this->pptp["enable"]) {

			// Configure PPTP Authentication
			$this->configure_radius();
			$this->pptp_config_users();

			copy_template("pptp.options", "/etc/ppp/pptp.options");

			$auth_plugins = array();

			if (is_array($this->authentication["ppp"])) {
				foreach($this->authentication["ppp"] as $authtype) {
					if ($authtype == "local")
						array_push($auth_plugins, "local");
					if ($authtype == "radius")
						array_push($auth_plugins, "radius");
				}
				write_config("/etc/ppp/pptp.options", "plugin stacker.so");
				write_config("/etc/ppp/pptp.options", "stack-plugins-dir /usr/lib/pppd/2.4.3");
				if (count($auth_plugins)) {
					write_config("/etc/ppp/pptp.options", "stack-plugins ".implode(",",$auth_plugins));
				}
			}

			copy_template("pptpd.conf", "/etc/pptpd.conf");
			write_config("/etc/pptpd.conf", "localip ". $this->pptp["local-address"]);

			if (is_array($this->authentication["ppp"])) {
				if (in_array("local", $this->authentication["ppp"]))
					write_config("/etc/ppp/pptp.options", "local-ip-pool ". $this->pptp["address-pool"]);
				if (in_array("radius", $this->authentication["ppp"]))
					write_config("/etc/ppp/pptp.options", "radius-ip-pool ". $this->pptp["address-pool"]);
			}

			if ( $this->pptp["proxyarp"] )
				write_config("/etc/ppp/pptp.options", "proxyarp");

			foreach($this->pptp["wins"] as $winssrv) {
				if (strlen($winssrv)) {
					write_config("/etc/ppp/pptp.options", "ms-wins ".$winssrv);
				}
			}
			foreach($this->pptp["dns"] as $dnssrv) {
				if (strlen($dnssrv)) {
					write_config("/etc/ppp/pptp.options", "ms-dns ".$dnssrv);
				}
			}

			# Load the necessary PPP modules
			load_module("ppp_mppe_mppc");
			load_module("ppp_synctty");
			load_module("ppp_deflate");
			load_module("ppp_async");
		}
	}

//-----------------------------------------------------------------------------
//
//
	function pptp_start_service ($apply_acls=true) {

		if (file_exists("/var/run/pptpd.pid")) {
			return false;
		}


		if ($this->pptp["enable"]) {
			sudo_exec("/usr/local/sbin/pptpd 1> /dev/null 2> /dev/null");
			if ($apply_acls) {
				$this->pptp_apply_acls(False);
			}
		}

	}

//-----------------------------------------------------------------------------
//
//
	function pptp_stop_service () {

		if (!file_exists("/var/run/pptpd.pid")) {
			return false;
		}

		sudo_exec("iptables -F pptp-input 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -D wolv-local-acls -j pptp-input 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -X pptp-input 1> /dev/null 2> /dev/null");

		$pid = GetServicePID("/var/run/pptpd.pid");

		if ($pid) {
			posix_kill($pid, 15);
			usleep(250000);
			if (file_exists("/var/run/pptpd.pid")) {
				posix_kill($pid, 9);
				unlink("/var/run/pptpd.pid");
			}
		} else {
			return false;
		}

		if (file_exists("/var/run/pppoe-ppp.pid")) {
			// If we are running PPPoE, we should not kill the PPPd associated with
			// the PPPoE interface
			$pid = GetServicePID("/var/run/pppoe-ppp.pid");
			if ($pid) {
				foreach(glob("/var/run/*ppp.pid") as $filename) {
					if (strpos($filename, "pppoe") !== false) {
						$pid2 = GetServicePID($filename);
						if ($pid2 && ($pid2 != $pid)) {
							posix_kill($pid2, 15);
						}
					}
				}
			}
		} else {
			do_exec("killall pppd 1> /dev/null 2> /dev/null");
		}
	}


//-----------------------------------------------------------------------------
//
//
	function ProcessStatement($confstmt) {
		// Called to process a configuration directive. Will
		// return true if it was handled or false if it was not

		$handled = true;

		switch ($confstmt[0]) {
			case "authentication":
				$this->load_authentication ($confstmt);
				break;
				;;
			case "pptp":
				$this->load_pptp($confstmt);
				break;
			case "radius":
				$this->load_radius($confstmt);
				break;
			case "ipsec":
				$this->load_ipsec($confstmt);
				break;
			default:
				$handled = false;
		}
		return $handled;
	}

//-----------------------------------------------------------------------------
//
//
	function BuildConfig($Config) {

		// Build the IPSEC configuration files
		$this->ipsec_build_config($Config);

		// Build the PPTP configuration files
		$this->pptp_build_config($Config);

		return true;
	}

//-----------------------------------------------------------------------------
//
//
	function StartService($Config, $apply_acls=true) {
		// Start the PPTP Service
		$this->pptp_start_service();
		// Start the IPSEC Service
		$this->ipsec_start_service($Config);
		return true;
	}

//-----------------------------------------------------------------------------
//
//
	function StopService($Config = "") {
		// Stop the PPTP Service
		$this->pptp_stop_service();
		// Stop the IPSEC Service
		$this->ipsec_stop_service();
		return true;
	}

//-----------------------------------------------------------------------------
//
//

function ApplyAcls($Config, $do_flush=false) {

	$this->pptp_apply_acls($do_flush);
	$this->ipsec_apply_acls($do_flush);

}

//-----------------------------------------------------------------------------
//
//
	function OutputConfig() {
		// Returns an array of strings that should be stored in the config file
		$output = array();

		// Authentication Configuration
		foreach($this->authentication as $authsvc => $authent) {
			array_push($output, "authentication $authsvc ". implode(" ", $authent));
		}

		// Radius Configuration
		if(is_array($this->radius["servers"])) {
			foreach($this->radius["servers"] as $srvent) {
				$outstr = "radius server ".$srvent["host"]." ".$srvent["authport"]." ".$srvent["acctport"];
				array_push($output, $outstr);
			}
		}
		if($this->radius["key"])
			array_push($output, "radius key ".$this->radius["key"]);

		// PPTP Configuration
		if ($this->pptp["enable"])
			array_push($output, "pptp server enable");
		if ($this->pptp["proxyarp"])
			array_push($output, "pptp proxyarp");
		if ($this->pptp["local-address"])
			array_push($output, "pptp local-address ".$this->pptp["local-address"]);
		if ($this->pptp["address-pool"]) {
			$pool=array();
			$pool = explode(":", $this->pptp["address-pool"]);
			array_push($output, "pptp address-pool ".$pool[0]." ".$pool[1]);
		}
		foreach($this->pptp["dns"] as $pptpdns) {
			if ($pptpdns) {
				array_push($output, "pptp dns-server ".$pptpdns);
			}
		}
		foreach($this->pptp["wins"] as $pptpwins) {
			if ($pptpwins) {
				array_push($output, "pptp wins-server ".$pptpwins);
			}
		}
		if ($this->pptp["disable-mppe"])
			array_push($output, "pptp disable-mppe");
		foreach($this->pptp["users"] as $pptpuser) {
			$userline = "pptp user ".$pptpuser["username"]." ".$pptpuser["password"];
			if (strlen($pptpuser["ip"]) && ($pptpuser["ip"] != "*")) {
				$userline .= " ".$pptpuser["ip"];
			}
			array_push($output, $userline);
		}
		for($t=0; $t<count($this->pptp["hosts"]); $t++)
			array_push($output, "pptp host ".$this->pptp["hosts"][$t]);

		// IPSEC Configuration
		if ($this->ipsec["enable"] === false)
			array_push($output, "ipsec disable");


		foreach($this->ipsec["tunnels"] as $tunname => $tundef) {
			$s = "ipsec tunnel ".$tunname." ";

			if ($tundef["local-address"])
				array_push($output, $s."local-address ".$this->ipsec["local-address"]);

			if ($tundef["interface"])
				array_push($output, $s."ipsec interface ".$this->ipsec["interface"]);

			if ($tundef["disable"])
				array_push($output, $s." disable");

			array_push($output, $s.$tundef["localsub"]." ".$tundef["remotesub"]." via ".$tundef["remotegw"]);
			$authdata = ($tundef["p1authtype"] == "psk") ? "psk ".$tundef["p1psk"] : "cert ".$tundef["cert"];
			array_push($output, $s."auth ".$authdata);

			// output IKE data
			array_push($output, $s."ike cipher ".$tundef["ike"]["cipher"]);
			array_push($output, $s."ike hash ".$tundef["ike"]["hash"]);
			if ($tundef["ike"]["lifetime"])
				array_push($output, $s."ike lifetime ".$tundef["ike"]["lifetime"]);
			array_push($output, $s."ike dh-group ".$tundef["ike"]["dh-group"]);

			// output ESP data
			array_push($output, $s."esp cipher ".$tundef["esp"]["cipher"]);
			array_push($output, $s."esp hash ".$tundef["esp"]["hash"]);
			if ($tundef["esp"]["lifetime"])
				array_push($output, $s."esp lifetime ".$tundef["esp"]["lifetime"]);
			array_push($output, $s."esp pfs-group ".$tundef["esp"]["pfs-group"]);
		}

		return $output;
	}


} // Object end

$NewAddon = new VPNSVCAddon();

?>
