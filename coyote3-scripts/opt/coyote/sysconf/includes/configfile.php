<?
// PRODUCT: ANY
// OPTIONAL: FALSE
// ENCODE: ANY
//
// Script: functions
// Purpose: general function include file
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

require_once("functions.php");

class FirewallConfig {

	var $hostname;
	var $domainname;
	var $nameservers = array();
	var $timezone;
	var $timeserver;
	var $dyndns = array();
	var $ssh = array();
	var $snmp = array();
	var $dhcpd = array();
	var $fixups = array();
	var $pppoe = array();
	var $hardware_detection;
	var $interfaces = array();
	var $nameif = array();
	var $bridge = array();
	var $options = array();
	var $acls = array();
	var $apply = array();
	var $portforwards = array();
	var $autoforwards = array();
	var $routes = array();
	var $nat = array();
	var $users = array();
	var $proxyarp = array();
	var $icmp = array();
	var $qos = array();
	var $tuning_options = array();
	var $logging = array();
	var	$public_interface;

	var $addons = array();

	var $dirty = array();

	var $outfile;

	// Class constructor - Set some defaults
	//function FirewallConfig() {
	function __construct() {

		$this->hardware_detection = false;
		$this->timezone = "";

		$this->dyndns = array(
			"enable" => false,
			"service" => "",
			"username" => "",
			"password" => "",
			"hostname" => "",
			"interface" => "",
			"max-interval" => 0
		);

		$this->dhcpd = array (
			"start" => "",
			"end" => "",
			"interface" => "",
			"dns" => array(),
			"wins" => array(),
			"lease" => 0,
			"router" => "",
			"subnet" => "",
			"domain" => "",
			"reservations" => array()
		);

		$this->icmp = array (
			"rules" => array (),
			"limit" => 0
		);

		$this->pppoe = array (
			"username" => "",
			"password" => "",
			"demand" => 0
		);

		$this->snmp = array(
			"location" => "",
			"contact" => "",
			"hosts" => array()
		);
		$this->ssh = array(
			"enable" => false,
			"port" => 22,
			"hosts" => array()
		);
		$this->bridge = array(
			"aging" => 0,
			"priority" => 0,
			"hello-interval" => 0,
			"garbage-collection" => 0,
			"maximum-age" => 0,
			"path-cost" => array(),
			"forward-delay" => 0,
			"port-priority" => array(),
			"spanning-tree" => false,
			"address" => ""
		);
		$this->tuning_options = array(
			"ecn" => false,
			"tcp-syn-backlog" => "",
			"frag-timeout" => "",
			"conntrack" => array(
				"max-conn" => "",
				"udp-timeout" => "",
				"udp-stream-timeout" => "",
				"tcp-close-timeout" => "",
				"tcp-close-wait-timeout" => "",
				"tcp-established-timeout" => "",
				"tcp-fin-wait-timeout" => "",
				"tcp-syn-recv-timeout" => "",
				"tcp-syn-sent-timeout" => "",
				"tcp-time-wait-timeout" => "",
				"generic-timeout" => "",
				"icmp-timeout" => ""
			)
		);
		$this->options = array(
			"acl-auto-apply" => true,
			"clamp-mss" => "",
			"vortech-support" => false,
			"upnp" => ""
		);

		$this->logging = array(
			"host" => "",
			"local-accept" => false,
			"local-deny" => false,
			"forward-accept" => false,
			"forward-deny" => false
		);

		$this->qos = array(
			"enable" => false,
			"upstream" => 10000, // Default to 10Mbps
			"downstream" => 10000, // Default to 10Mbps
			"default-class" => QOS_PRIO_LOW,
			"filters" => array()
		);

		$this->public_interface = "eth0";

		$this->dirty = array(
			"interfaces" => false,
			"vlans" => false,
			"routes" => false,
			"acls" => false,
			"dhcpd" => false,
			"sshd" => false,
			"qos" => false
		);

		$this->addons = array();

		// Load and init any addon configuration extensions
		$ObjFiles = glob('/opt/coyote/sysconf/addons/*-conf.php');

		if (is_array($ObjFiles)) {
			foreach($ObjFiles as $Obj) {
				unset($NewAddon);
				include($Obj);
				if (isset($NewAddon) && is_subclass_of($NewAddon, 'FirmwareAddon')) {
					$can_add = true;
					if (method_exists($NewAddon, 'SysInitService')) {
						$can_add = $NewAddon->SysInitService();
					}
					if ($can_add) {
						array_push($this->addons, $NewAddon);
					}
				}
			}
		}

	}

	function load_accesslist($confstmt) {

		// Create a new entry in the ACL list if this is the first time we have seen it
		if (!array_key_exists($confstmt[1], $this->acls)) {
			$this->acls["$confstmt[1]"] = array();
		}

		$acl_entry = array(
			"permit" => ($confstmt[2] == "permit") ? true : false,
			"protocol" => $confstmt[3],
			"source" => $confstmt[4],
			"dest" => $confstmt[5],
			"ports" => (isset($confstmt[6])) ? $confstmt[6] : ""
		);

		$this->acls["$confstmt[1]"][count($this->acls["$confstmt[1]"])] = $acl_entry;
	}

	function load_apply($confstmt) {

		// Make sure the specified ACL exists before building an apply entry for it
		if (!array_key_exists($confstmt[1], $this->acls)) {
			return 1;
		}

		$apply_entry = array (
			"acl" => $confstmt[1],
			"in_if" => $confstmt[3],
			"out_if" => $confstmt[5]
		);

		$this->apply[count($this->apply)] = $apply_entry;
	}


	function load_autofw($confstmt) {

		$autofw_entry = array (
			"interface" => $confstmt[1],
			"protocol" => $confstmt[2],
			"port" => $confstmt[3],
			"dest" => $confstmt[4]
		);

		$this->autoforwards[count($this->autoforwards)] = $autofw_entry;

	}

	function load_bridge($confstmt) {

		if (array_key_exists($confstmt[1], $this->bridge)) {
			switch ($confstmt[1]) {
				case "port-priority":
				case "path-cost":
					$new_entry = array(
						"interface" => $confstmt[2],
						"value" => $confstmt[3]
					);
					$this->bridge["$confstmt[1]"][count($this->bridge["$confstmt[1]"])] = $new_entry;
					break;
				;;
				default:
					$this->bridge["$confstmt[1]"] = $confstmt[2];
					break;
					;;
			}

		}
	}

	function load_clock($confstmt) {

		switch ($confstmt[1]) {

			case "server":
				$this->timeserver=$confstmt[2];
				break;
				;;
			case "timezone":
				if (file_exists("/usr/share/zoneinfo/$confstmt[2]")) {
					$this->timezone = $confstmt[2];
				}
				break;
				;;
			default:
				return false;
				;;
		}

		return true;
	}

	function load_config($confstmt) {
		return true;
	}

	function load_dhcpd($confstmt) {

		switch ($confstmt[1]) {
			case "address":
				$this->dhcpd["start"] = $confstmt[2];
				$this->dhcpd["end"] = $confstmt[3];
				break;
				;;
			case "enable":
				$this->dhcpd["interface"] = $confstmt[2];
				break;
			;;
			case "lease":
				$this->dhcpd["lease"] = $confstmt[2];
				break;
			;;
			case "domain":
				$this->dhcpd["domain"] = $confstmt[2];
				break;
			;;
			case "dns":
				$newdns = $confstmt[2];
				if (!is_array($this->dhcpd["dns"])) {
					$this->dhcpd["dns"] = array($newdns);
				} else {
					array_push($this->dhcpd["dns"], $newdns);
				}
				break;
			;;
			case "wins":
				$newwins = $confstmt[2];
				if (!is_array($this->dhcpd["wins"])) {
					$this->dhcpd["wins"] = array($newwins);
				} else {
					array_push($this->dhcpd["wins"], $newwins);
				}
				break;
			;;
			case "router":
				$this->dhcpd["router"] = $confstmt[2];
				break;
			;;
			case "subnet":
				$this->dhcpd["subnet"] = $confstmt[2];
				break;
			;;
			case "reserve":
				$resent = array(
					"address" => $confstmt[2],
					"mac" => $confstmt[3]
				);
				$this->dhcpd["reservations"][count($this->dhcpd["reservations"])] = $resent;
				break;
			;;
		}

	}

	function load_dyndns($confstmt) {
		switch ($confstmt[1]) {
			case "service":
				$this->dyndns["service"] = $confstmt[2];
				break;
			;;
			case "enable":
				$this->dyndns["interface"] = $confstmt[2];
				$this->dyndns["enable"] = true;
				break;
			;;
			case "user":
				$this->dyndns["username"] = $confstmt[2];
				$this->dyndns["password"] = $confstmt[3];
				break;
			;;
			case "hostname":
				$this->dyndns["hostname"] = $confstmt[2];
				break;
			;;
			case "max-interval":
				$this->dyndns["max-interval"] = $confstmt[2];
				break;
			;;
		}
	}

	function load_fixup($confstmt) {
		$this->fixups[count($this->fixups)] = $confstmt[1];
	}

	// Returns the index into the interfaces array for a give device name
	function get_if_index($ifname) {
		for($t = 0; $t < count($this->interfaces); $t++) {
			if ($this->interfaces[$t]["name"] == $ifname) {
				return $t;
			}
			// We will also match against the actual device name in case no
			// alias has been assigned for the interface
			if ($this->interfaces[$t]["device"] == $ifname) {
				return $t;
			}
		}
		return -1;
	}

	function &get_addon($ObjName) {

		foreach(array_keys($this->addons) as $okey) {
			if (is_a($this->addons[$okey], $ObjName)) {
				return $this->addons[$okey];
			}
		}

		return false;
	}

	// Returns the physical device name for an alias
	function resolve_ifname($ifname) {
		$ifidx = $this->get_if_index($ifname);
		if ($ifidx >= 0) {
			return $this->interfaces[$ifidx]["device"];
		} else {
			return "";
		}
	}

	function load_hardware($confstmt) {

		if ($confstmt[1] == "autodetect") {
			$this->hardware_detection = 1;
			// We need to build a list of interface entries

			//$niclist = GetNicModuleNames();
			$niclist = GetNetworkInterfaces();
			for ($t=0; $t < count($niclist); $t++) {
				$ifentry = array(
					"module" => "none",
					"bridge" => false,
					"mtu" => 1500,
					"name" => $niclist[$t]["name"],
					"device" => $niclist[$t]["name"],
					"addresses" => array(),
					"down" => false,
					"export" => true
				);

				$this->interfaces[] = $ifentry;
			}
			return true;
		} else {
			return false;
		}
	}

	function load_hostname($confstmt) {
		$this->hostname = $confstmt[1];
	}


	function load_icmp($confstmt) {
		switch ($confstmt[1]) {
			case "deny":
				break;
			;;
			case "permit":
				$icmp_entry = array(
					"source" => $confstmt[2],
					"type" => $confstmt[3],
					"interface" => $confstmt[4]
				);
				if (!isset($this->icmp["rules"])) {
					$this->icmp["rules"] = array();
				}
				$this->icmp["rules"][count($this->icmp["rules"])] = $icmp_entry;
				break;
			;;
			case "limit":
				$this->icmp["limit"] = $confstmt[2];
				break;
			;;
			default:
				return false;
				break;
			}
		return true;
	}

	function load_interface($confstmt) {

		$ifidx = $this->get_if_index($confstmt[1]);

		if (($confstmt[2] != "module") && ($ifidx < 0)) {
			return false;
		}

		switch($confstmt[2]) {
			case "public":
				// Deprecated
				break;
				;;
			case "module":
				if ($this->hardware_detection) {
					return false;
				}
				if ($ifidx < 0) {
					// Create a new interface entry
					$ifentry = array(
						"module" => "",
						"bridge" => false,
						"vlan" => false,
						"mtu" => 1500,
						"mac" => "",
						"name" => "$confstmt[1]",
						"device" => "$confstmt[1]",
						"addresses" => array(),
						"down" => false,
						"export" => true
					);
					$ifidx = count($this->interfaces);
					$this->interfaces[$ifidx] = $ifentry;
				}
				array_shift($confstmt);
				array_shift($confstmt);
				array_shift($confstmt);
				$this->interfaces[$ifidx]["module"] = implode(" ", $confstmt);
				break;
				;;
			case "mtu":
				$this->interfaces[$ifidx]["mtu"] = $confstmt[3];
				break;
				;;
			case "mac":
				$this->interfaces[$ifidx]["mac"] = $confstmt[3];
				break;
				;;
			case "bridge":
				$this->interfaces[$ifidx]["bridge"] = true;
				break;
				;;
			case "vlan":
				$vlanid = $confstmt[3];
				if (($vlanid >= 0) && ($vlanid <= 4095)) {
					if (!is_array($this->interfaces[$ifidx]["vlans"])) {
						$this->interfaces[$ifidx]["vlans"] = array();
					}
					// TODO: VLAN options
					$this->interfaces[$ifidx]["vlans"][$vlanid] = array();
					// Add an interface entry for this vlan
					$vifname = "$confstmt[1].$confstmt[3]";
					$vifidx = $this->get_if_index($vifname);
					if ($vifidx < 0) {
						$ifentry = array(
							"module" => "",
							"bridge" => false,
							"vlan" => true,
							"vlanid" => $vlanid,
							"mtu" => 1500,
							"name" => $vifname,
							"device" => $vifname,
							"addresses" => array(),
							"down" => false,
							"export" => true
						);
						$this->interfaces[count($this->interfaces)] = $ifentry;
					}
				}
				break;
				;;
			case "address":
				// If this interface is part of a bridge, it can not be assigned an address
				if ($this->interfaces[$ifidx]["bridge"]) {
					return false;
				}
				switch ($confstmt[3]) {
					case "pppoe":
      					// Add an interface definition for the default PPP device
						$pppidx = $this->get_if_index("ppp0");
						if ($pppidx >= 0) {
							$this->interfaces[$pppidx]["export"] = false;
						} else {
							$ifentry = array(
								"module" => "",
								"bridge" => false,
								"vlan" => false,
								"mtu" => 1500,
								"name" => "ppp0",
								"device" => "ppp0",
								"addresses" => array(),
								"down" => false,
								"export" => false
							);
							$this->interfaces[count($this->interfaces)] = $ifentry;
							$this->public_interface = "ppp0";
						}
					case "dhcp":
						// An interface can not have a static address and a DHCP address
						if (count($this->interfaces[$ifidx]["addresses"])) {
							return false;
						}
						$this->interfaces[$ifidx]["addresses"] = $confstmt[3];
						if (!empty($confstmt[4]) && $confstmt[4] == "down") {
							$this->interfaces[$ifidx]["down"] = true;
						}
						break;
						;;
					default:
						if (($this->interfaces[$ifidx]["addresses"] == "dhcp") || ($this->interfaces[$ifidx]["addresses"] == "pppoe")) {
							return false;
						}
						# Flush any existing configuration info for the Interface if this is not
						# designated as a secondary address
						if ( $confstmt[4] != "secondary" ) {
							$this->interfaces[$ifidx]["addresses"] = array();
						}
						if ( $confstmt[4] == "down" ) {
							$this->interfaces[$ifidx]["down"] = true;
						}
						exec("ipcalc -p $confstmt[3] -s 1> /dev/null", $outstr, $errcode);
						if ($errcode) {
							$ipc=run_ipcalc("-m $confstmt[3] -s");
							$NETMASK=$ipc["NETMASK"];
							$ipc=run_ipcalc("-p -b $confstmt[3] $NETMASK -s");
							$addr_entry = array(
								"ip" => "$confstmt[3]/".$ipc["PREFIX"],
								"broadcast" => $ipc["BROADCAST"]
							);
						} else {
							$ipc=run_ipcalc("-b $confstmt[3] -s");
							$addr_entry = array(
								"ip" => "$confstmt[3]",
								"broadcast" => $ipc["BROADCAST"]
							);
						}
						$addridx = count($this->interfaces[$ifidx]["addresses"]);
						$this->interfaces[$ifidx]["addresses"][$addridx] = $addr_entry;
						break;
						;;
				}
				break;
				;;
			default:
				return false;
				break;
				;;
		}
		return true;
	}


	function load_domain($confstmt) {
		$this->domainname = $confstmt[1];
	}

	function load_nameserver($confstmt) {
		$this->nameservers[count($this->nameservers)] = $confstmt[1];
	}

	function load_logging($confstmt) {
		switch ($confstmt[1]) {
			case "host":
				$this->logging["host"] = $confstmt[2];
				break;
			;;
			case "local-accept":
			case "local-deny":
			case "forward-accept":
			case "forward-deny":
				$this->logging[$confstmt[1]] = true;
				break;
			;;
		}
	}

	function load_nameif($confstmt) {

		for ($t=0; $t < count($this->interfaces); $t++) {
			if ($this->interfaces[$t]["device"] == $confstmt[1]) {
				$this->interfaces[$t]["name"] = $confstmt[2];
				return;
			}
		}

		$ifentry = array(
			"module" => "",
			"bridge" => false,
			"vlan" => false,
			"mtu" => 1500,
			"name" => "$confstmt[2]",
			"device" => "$confstmt[1]",
			"addresses" => array(),
			"down" => false,
			"export" => true
		);
		$this->interfaces[count($this->interfaces)] = $ifentry;
	}

	function load_nat($confstmt) {

		switch ($confstmt[1]) {
			case "bypass":
				# Adds NAT bypass to allow for exceptions to any added NAT rules
				# NOTE: These rules always get "inserted" rather than added to make sure that they go into
				# affect before any added NAT rules.
				$nat_entry = array (
					"interface" => "",
					"bypass" => 1,
					"source" => $confstmt[2],
					"dest" => $confstmt[3]
				);
				break;
				;;
			default:
				$nat_entry = array (
					"interface" => $confstmt[1],
					"bypass" => 0,
					"source" => $confstmt[2],
					"dest" => isset($confstmt[3]) ? $confstmt[3] : ""
				);
				break;
				;;
		}
		$this->nat[count($this->nat)] = $nat_entry;
	}

	function load_options($confstmt) {
		switch($confstmt[1]) {
			case "clamp-mss":
				if ($confstmt[2]) {
					$this->options["clamp-mss"] = $confstmt[2];
				} else {
					$this->options["clamp-mss"] = "pmtu";
				}
				break;
			;;
			case "acl-auto-apply":
			case "vortech-support":
				switch($confstmt[2]) {
					case "disable":
						$this->options["$confstmt[1]"] = false;
						break;
					;;
					case "enable":
						$this->options["$confstmt[1]"] = true;
						break;
					;;
				}
				break;
			;;
			case "upnp":
				$this->options["upnp"] = $confstmt[3];
				break;
			;;

		}
	}

	function load_password($confstmt) {
		$user_entry = array(
			"username" => $confstmt[1],
			"password" => $confstmt[2],
			"encrypted" => (isset($confstmt[3]) && ($confstmt[3] == "encrypted")) ? true : false
		);
		$this->users[count($this->users)] = $user_entry;
	}

	function load_proxyarp($confstmt) {
		$proxyarp_entry = array(
			"int_if" => $confstmt[4],
			"ext_if" => $confstmt[1],
			"address" => $confstmt[3]
		);
		$this->proxyarp[count($this->proxyarp)] = $proxyarp_entry;
	}

	function load_snmp($confstmt) {
		switch($confstmt[1]) {
			case "contact":
				array_shift($confstmt);
				array_shift($confstmt);
				$this->snmp["contact"] = implode(" ", $confstmt);
				break;
				;;
			case "location":
				array_shift($confstmt);
				array_shift($confstmt);
				$this->snmp["location"] = implode(" ", $confstmt);
				break;
				;;
			case "host":
				$this->snmp["hosts"][count($this->snmp["hosts"])] = $confstmt[2];
				;;
			default:
				return false;
				;;
		}
		return true;
	}

	function load_tuning($confstmt) {
		switch($confstmt[1]) {
			case "ecn":
				switch ($confstmt[2]) {
					case "enable":
						$this->tuning_options["ecn"] = true;
						break;
					;;
					case "disable":
						$this->tuning_options["ecn"] = false;
						break;
					;;
				}
				break;
			;;
			case "conntrack":
				if (array_key_exists($confstmt[2], $this->tuning_options["conntrack"])) {
					$this->tuning_options["conntrack"]["$confstmt[2]"] = $confstmt[3];
				}
				break;
			;;
			default:
				if (array_key_exists($confstmt[1], $this->tuning_options)) {
					$this->tuning_options["$confstmt[1]"] = $confstmt[2];
				}
			;;
		}
	}

	function load_portfw($confstmt) {
		$portfw_entry = array(
			"source" => $confstmt[1],
			"dest" => $confstmt[2],
			"protocol" => $confstmt[3],
			"from-port" => $confstmt[4],
			"to-port" => $confstmt[5]
		);

		$this->portforwards[count($this->portforwards)] = $portfw_entry;
	}

	function load_pppoe($confstmt) {

		switch ($confstmt[1]) {
			case "user":
				$this->pppoe["username"] = $confstmt[2];
				$this->pppoe["password"] = $confstmt[3];
				break;
			;;
			case "demand":
				$this->pppoe["demand"] = $confstmt[2];
				break;
			;;
		}
	}



	function load_route($confstmt) {

		$route_entry = array(
			"dest" => $confstmt[1],
			"gw" => $confstmt[2],
			"dev" => "",
			"metric" => ""
		);

		array_shift($confstmt); array_shift($confstmt);

		while ($confstmt[1]) {
			switch ($confstmt[1]) {

				case "metric":
					$route_entry["metric"] = $confstmt[2];
					break;
					;;
				case "dev":
					$route_entry["dev"] = $confstmt[2];
					break;
					;;
				default:
					return false;
					break;
					;;
			}
			array_shift($confstmt); array_shift($confstmt);
		}

		$this->routes[count($this->routes)] = $route_entry;
	}

	function load_ssh($confstmt) {
		if (isset($confstmt[1], $confstmt[2]) && "$confstmt[1] $confstmt[2]" == "server enable") {
			$this->ssh["enable"] = true;
			if (isset($confstmt[3])) {
				$this->ssh["port"] = $confstmt[3];
			}
		} else {
			if (!isset($this->ssh["hosts"])) {
				$this->ssh["hosts"] = array();
			}
			$this->ssh["hosts"][count($this->ssh["hosts"])] = $confstmt[1];
		}
	}

	function load_qos($confstmt) {

		switch($confstmt[1]) {
			case "enable":
				$this->qos["enable"] = true;
				break;
				;;
			case "rate":
				$this->qos["upstream"] = $confstmt[2];
				$this->qos["downstream"] = $confstmt[3];
				break;
			case "filter":
				$qos_rule = array(
					"interface" => $confstmt[2],
					"proto" => $confstmt[3],
					"ports" => "",
					"prio" => ""
					);
					if ($confstmt[4] != "prio") {
						$qos_rule["ports"] = $confstmt[4];
						array_shift($confstmt);
					}
					$qos_rule["prio"] = get_qos_prio($confstmt[5]);
					array_push($this->qos["filters"], $qos_rule);
					break;
			case "default-priority":
				$this->qos["default-prio"] = get_qos_prio($confstmt[2]);
				break;
		}
	}

	function LoadConfigFile($filename) {

		$conffile = fopen($filename, "r");
		if (!$conffile) {
			return false;
		}
		while (!feof($conffile)) {
			$confline = trim(fgets($conffile, 1024));
			if ((!$confline) || ($confline[0] == "#")) {
				continue;
			} else {
				//print($confline."\n");
			}
			$confstmt = explode(" ", $confline);

			switch ($confstmt[0]) {
				case "access-list":
					$this->load_accesslist($confstmt);
					break;
				case "apply":
					$this->load_apply ($confstmt);
					break;
					;;
				case "auto-forward":
					$this->load_autofw ($confstmt);
					break;
					;;
				case "bridge":
					$this->load_bridge ($confstmt);
					break;
					;;
				case "clock":
					$this->load_clock ($confstmt);
					break;
					;;
				case "config":
					$this->load_config($confstmt);
					break;
					;;
				case "dhcpd":
					$this->load_dhcpd ($confstmt);
					break;
					;;
				case "dyndns":
					$this->load_dyndns ($confstmt);
					break;
					;;
				case "fixup":
					$this->load_fixup ($confstmt);
					break;
					;;
				case "hardware":
					$this->load_hardware ($confstmt);
					break;
					;;
				case "hostname":
					$this->load_hostname ($confstmt);
					break;
					;;
				case "icmp":
					$this->load_icmp ($confstmt);
					break;
					;;
				case "interface":
					$this->load_interface ($confstmt);
					break;
					;;
				case "ip":
					$this->load_tuning ($confstmt);
					break;
					;;
				case "domain-name":
					$this->load_domain ($confstmt);
					break;
					;;
				case "name-server":
					$this->load_nameserver ($confstmt);
					break;
					;;
				case "logging":
					$this->load_logging ($confstmt);
					break;
					;;
				case "nameif":
					$this->load_nameif ($confstmt);
					break;
					;;
				case "nat":
					$this->load_nat ($confstmt);
					break;
					;;
				case "password":
					$this->load_password ($confstmt);
					break;
					;;
				case "proxyarp":
					$this->load_proxyarp ($confstmt);
					break;
					;;
				case "route":
					$this->load_route ($confstmt);
					break;
					;;
				case "snmp":
					$this->load_snmp ($confstmt);
					break;
					;;
				case "option":
					$this->load_options ($confstmt);
					break;
					;;
				case "port-forward":
					$this->load_portfw ($confstmt);
					break;
					;;
				case "pppoe":
					$this->load_pppoe ($confstmt);
					break;
					;;
				case "ssh":
					$this->load_ssh ($confstmt);
					break;
					;;
				case "qos":
					$this->load_qos($confstmt);
					break;
					;;
				default:
					// Pass the statement to any addon modules that have been loaded
					$handled = false;
					foreach (array_keys($this->addons) as $okey) {
						$obj = &$this->addons[$okey];
						if (method_exists($obj, 'ProcessStatement')) {
							$handled = $obj->ProcessStatement($confstmt);
							if ($handled === true)
								break;
						}
					}
					if (!$handled)
						print("Unknown configuration directive: $confstmt[0]\n");
					break;
			}
		}
		fclose($conffile);
		return true;
	}

	function line_output($data) {
		if (is_array($data)) {
			foreach($data as $line)
				fwrite($this->outfile, $line."\n");
		} else {
			fwrite($this->outfile, $data."\n");
		}
	}

	// Firewall configuration version
	function output_config() {
		$this->line_output("config version ".PRODUCT_VERSION);
	}

	// Various firewall behaviour "options"
	function output_options() {
		if (!$this->options["acl-auto-apply"])
			$this->line_output("option acl-auto-apply disable");
		if ($this->options["upnp"])
			$this->line_output("option upnp enable ".$this->options["upnp"]);
		if ($this->options["clamp-mss"]) {
			if ($this->options["clamp-mss"] != "pmtu") {
				$this->line_output("option clamp-mss ".$this->options["clamp-mss"]);
			} else {
				$this->line_output("option clamp-mss");
			}
		}
	}

	// IP Kernel tweaking parameters
	function output_tuning() {
		$tuneopts = array_keys($this->tuning_options);
		for($t=0; $t < count($tuneopts); $t++) {
			switch($tuneopts[$t]) {
				case "ecn":
					if ($this->tuning_options["ecn"])
						$this->line_output("ip ecn enable");
					break;
				;;
				case "tcp-syn-backlog":
				case "frag-timeout":
					if ($this->tuning_options["$tuneopts[$t]"])
						$this->line_output("ip ".$tuneopts[$t]." ".$this->tuning_options[$tuneopts[$t]]);
					break;
				;;
				case "conntrack":
					$ctopts = array_keys($this->tuning_options["conntrack"]);
					for($x=0; $x < count($ctopts); $x++) {
						$ctidx=$ctopts[$x];
						if ($this->tuning_options["conntrack"]["$ctidx"])
							$this->line_output("ip conntrack $ctidx ".$this->tuning_options["conntrack"]["$ctidx"]);
					}
					break;
				;;
			}
		}
	}

	// NIC auto-detection
	// FIXME: New versions of Coyote (3.1+) will always auto-detect NICs
	function output_hardware() {
		if ($this->hardware_detection) {
			$this->line_output("hardware autodetect");
		}
	}

	// Network time sync options
	function output_clock() {
		if ($this->timezone)
			$this->line_output("clock timezone ".$this->timezone);
		if ($this->timeserver)
			$this->line_output("clock server ".$this->timeserver);
	}

	// Firewall user configuration (permitted logins)
	function output_users() {
		for($t=0; $t < count($this->users); $t++) {
			if (!$this->users[$t]["encrypted"]) {
				$pass=crypt($this->users[$t]["password"], "$1$$");
			} else {
				$pass=$this->users[$t]["password"];
			}
			$this->line_output("password ".$this->users[$t]["username"].
				" ".$pass." encrypted");
		}
	}

	// Masquerading protocol helpers
	function output_fixup() {
		for ($t=0; $t < count($this->fixups); $t++) {
			$this->line_output("fixup ".$this->fixups[$t]);
		}
	}

	// Firewall Hostname
	function output_hostname() {
		if ($this->hostname)
			$this->line_output("hostname ". $this->hostname);
	}

	// Firewall Domain name
	function output_domain() {
		if ($this->domainname)
			$this->line_output("domain-name ". $this->domainname);
	}

	// Dynamic DNS client config
	function output_dyndns() {
		if ($this->dyndns["enable"]) {
			$this->line_output("dyndns enable ".$this->dyndns["interface"]);
			$this->line_output("dyndns service ".$this->dyndns["service"]);
			$this->line_output("dyndns user ".$this->dyndns["username"]." ".$this->dyndns["password"]);
			$this->line_output("dyndns hostname ".$this->dyndns["hostname"]);
			if ($this->dyndns["max-interval"]) {
				$this->line_output("dyndns max-interval ".$this->dyndns["max-interval"]);
			}
		}
	}

	// Network Interface name aliases
	function output_nameif() {
		for ($t=0; $t<count($this->interfaces); $t++) {
			if ($this->interfaces[$t]["name"] != $this->interfaces[$t]["device"]) {
				$this->line_output("nameif ".$this->interfaces[$t]["device"]." ".$this->interfaces[$t]["name"]);
			}
		}
	}

	// Nameservers
	function output_nameservers() {
		foreach($this->nameservers as $nsrec) {
			$this->line_output("name-server ".$nsrec);
		}
	}

	// PPPoE WAN configuration
	function output_pppoe() {
		if ($this->pppoe["username"])
			$this->line_output("pppoe user ".$this->pppoe["username"]." ".
				$this->pppoe["password"]);
		if ($this->pppoe["demand"])
			$this->line_output("pppoe demand ".$this->pppoe["demand"]);
	}

	// Firewall Network Interface configuration
	function output_interfaces() {
		// If we are not doing hardware auto detection, output the interface module
		// definitions
		if (!$this->hardware_detection) {
			for($t=0; $t < count($this->interfaces); $t++) {
				if ($this->interfaces[$t]["export"] && !$this->interfaces[$t]["vlan"])
					$this->line_output("interface ".$this->interfaces[$t]["device"].
						" module ".$this->interfaces[$t]["module"]);
			}
		}

		for($t=0; $t < count($this->interfaces); $t++) {
			$devname=$this->interfaces[$t]["device"];
			if($this->interfaces[$t]["mac"])
				$this->line_output("interface $devname mac ".$this->interfaces[$t]["mac"]);
			if ($this->interfaces[$t]["bridge"]) {
				$this->line_output("interface $devname bridge enable");
			} else {
				if (is_array($this->interfaces[$t]["addresses"])) {
					for($x=0; $x < count($this->interfaces[$t]["addresses"]); $x++) {
						$outstr = "interface $devname address ". $this->interfaces[$t]["addresses"][$x]["ip"];
						if ($x)
							$outstr .= " secondary";
						$this->line_output($outstr);
						if (is_array($this->interfaces[$t]["vlans"])) {
							foreach($this->interfaces[$t]["vlans"] as $vlanid => $vlanopts) {
								$outstr = "interface $devname vlan $vlanid create";
								$this->line_output($outstr);
								// TODO: output vlan options
							}
						}
					}
				} else {
					$outstr = "interface $devname address ". $this->interfaces[$t]["addresses"];
					$this->line_output($outstr);
				}
			}
			if($this->interfaces[$t]["mtu"] != 1500)
				$this->line_output("interface $devname mtu ".$this->interfaces[$t]["mtu"]);
		}

	}

	// Network interface bridging
	function output_bridge() {
		$bropts = array_keys($this->bridge);
		for ($t=0; $t < count($bropts); $t++) {
			if (!$this->bridge["$bropts[$t]"])
				continue;
			$outstr = "bridge ".$bropts[$t]." ";
			switch($bropts[$t]) {
				case "path-cost":
				case "port-priority":
					$outstr .= $this->bridge["$bropts[$t]"]["interface"]." ".
						$this->bridge["$bropts[$t]"]["value"];
					break;
				;;
				default:
					$outstr .=$this->bridge["$bropts[$t]"];
				;;
			}
			$this->line_output($outstr);
		}
	}

	// Network Routing
	function output_routes() {
		foreach($this->routes as $route) {
			$routestmt = "route ".$route["dest"]." ".$route["gw"];
			if ($route["dev"])
				$routestmt .= " dev ".$route["dev"];
			if ($route["metric"])
				$routestmt .= " metric ".$route["metric"];
			$this->line_output($routestmt);
		}
	}

	// Manual IP forwarding
	function output_portfw() {
		for($t=0; $t < count($this->portforwards); $t++) {
			$outstr = "port-forward " .$this->portforwards[$t]["source"]." ".
				$this->portforwards[$t]["dest"];
			if ($this->portforwards[$t]["protocol"])
				$outstr .= " ".$this->portforwards[$t]["protocol"];
			if ($this->portforwards[$t]["from-port"])
				$outstr .= " ".$this->portforwards[$t]["from-port"];
			if ($this->portforwards[$t]["to-port"])
				$outstr .= " ".$this->portforwards[$t]["to-port"];
			$this->line_output($outstr);
		}
	}

	// ProxyARP 
	function output_proxyarp() {
		for($t=0; $t < count($this->proxyarp); $t++) {
			$this->line_output("proxyarp ".$this->proxyarp[$t]["ext_if"]." host ".
				$this->proxyarp[$t]["address"]." ".
				$this->proxyarp[$t]["int_if"]);
		}
	}

	// Firewall ICMP controls
	function output_icmp() {
		foreach($this->icmp["rules"] as $icmprule) {
			$this->line_output("icmp permit ".$icmprule["source"]." ".
				$icmprule["type"]." ".
				$icmprule["interface"]);
		}
		if ($this->icmp["limit"]) {
			$this->line_output("icmp limit ".$this->icmp["limit"]);
		}
	}

	// Network ACLs
	function output_acls() {
		$aclnames = array_keys($this->acls);
		for($t=0; $t < count($aclnames); $t++) {
			$idx = $aclnames[$t];
			for($x=0; $x < count($this->acls[$idx]); $x++) {
				$outstr="access-list $idx ";
				$outstr .= ($this->acls["$idx"][$x]["permit"]) ? "permit" : "deny";
				$outstr .= " " . $this->acls["$idx"][$x]["protocol"] . " " . $this->acls["$idx"][$x]["source"].
				" ". $this->acls["$idx"][$x]["dest"];
				if ($this->acls["$idx"][$x]["ports"])
					$outstr .= " ".$this->acls["$idx"][$x]["ports"];
				$this->line_output($outstr);
			}
		}
	}

	// Statements for binding network ACLs to interfaces
	function output_apply() {
		for($t=0; $t < count($this->apply); $t++) {
			$outstr = "apply ".$this->apply[$t]["acl"]." in ".
				$this->apply[$t]["in_if"];
			if ($this->apply[$t]["out_if"])
				$outstr .= " out ".$this->apply[$t]["out_if"];
			$this->line_output($outstr);
		}
	}

	// IP (TCP/UDP) port auto-forwarding
	function output_autofw() {
		for($t=0; $t < count($this->autoforwards); $t++) {
			$this->line_output("auto-forward ".$this->autoforwards[$t]["interface"]." ".
				$this->autoforwards[$t]["protocol"]." ".
				$this->autoforwards[$t]["port"]." ".
				$this->autoforwards[$t]["dest"]);
		}
	}

	// Network Address Translation
	function output_nat() {
		for($t=0; $t<count($this->nat); $t++) {
			$outstr = "nat ";
			if ($this->nat[$t]["bypass"]) {
				$outstr .= "bypass ";
			} else {
				$outstr .= $this->nat[$t]["interface"]." ";
			}
			$outstr .= $this->nat[$t]["source"];
			if ($this->nat[$t]["dest"])
				$outstr .= " ".$this->nat[$t]["dest"];
			$this->line_output($outstr);
		}
	}

	// DHCP Server Config
	function output_dhcpd() {
		if ($this->dhcpd["interface"]) {

			if ($this->dhcpd["start"] && $this->dhcpd["end"]) {
				$this->line_output("dhcpd address ". $this->dhcpd["start"]. " ".
					$this->dhcpd["end"]);
			}
			if($this->dhcpd["lease"])
				$this->line_output("dhcpd lease ".$this->dhcpd["lease"]);
			if($this->dhcpd["domain"])
				$this->line_output("dhcpd domain ".$this->dhcpd["domain"]);
			if($this->dhcpd["router"])
				$this->line_output("dhcpd router ".$this->dhcpd["router"]);
			if($this->dhcpd["subnet"])
				$this->line_output("dhcpd subnet ".$this->dhcpd["subnet"]);
			foreach($this->dhcpd["dns"] as $srv)
				$this->line_output("dhcpd dns ". $srv);
			foreach($this->dhcpd["wins"] as $srv)
				$this->line_output("dhcpd wins ". $srv);

			$this->line_output("dhcpd enable ".$this->dhcpd["interface"]);
		}
		
		foreach($this->dhcpd["reservations"] as $resent) {
			$this->line_output("dhcpd reserve ".$resent["address"]." ".$resent["mac"]);
		}
	}

	// SNMPd
	function output_snmp() {
		if ($this->snmp["location"])
			$this->line_output("snmp location ".$this->snmp["location"]);
		if ($this->snmp["contact"])
			$this->line_output("snmp contact ".$this->snmp["contact"]);
		for($t=0; $t < count($this->snmp["hosts"]); $t++) {
			$this->line_output("snmp host ".$this->snmp["hosts"][$t]);
		}
	}

	// SSH Server
	function output_ssh() {
		if ($this->ssh["enable"]) {
			$outstr = "ssh server enable";
			if ($this->ssh["port"] != 443)
				$outstr .= " ". $this->ssh["port"];
			$this->line_output($outstr);
		}
		for ($t=0; $t < count($this->ssh["hosts"]); $t++) {
			$this->line_output("ssh ". $this->ssh["hosts"][$t]);
		}
	}

	// Quality of Service (QoS)
	function output_qos() {
		if ($this->qos["enable"]) {
			$this->line_output("qos enable");
		}
		if ($this->qos["upstream"]) {
			$this->line_output("qos rate ".$this->qos["upstream"] ." ".$this->qos["downstream"]);
		}
		if ($this->qos["default-prio"]) {
			$this->line_output("qos default-priority ". get_qos_output_prio($this->qos["default-prio"]));
		}
		foreach($this->qos["filters"] as $qf) {
			$outstr = "qos filter " . $qf["interface"] ." ". $qf["proto"]. " ";
			if ($qf["ports"]) {
				$outstr .= $qf["ports"];
			}
			$outstr .= " prio ". get_qos_output_prio($qf["prio"]);
			$this->line_output($outstr);
		}
	}

	// System (syslogd) Logging
	function output_logging() {
		foreach($this->logging as $logkey => $logdata) {
			if ($logkey == "host") {
				if ($logdata)
					$this->line_output("logging host ". $logdata);
			} else {
				if ($logdata)
					$this->line_output("logging ".$logkey);
			}
		}
	}

	// Dumps a formatted Coyote Linux configuration file to stdout
	function WriteConfigFile($filename) {
		if (!$filename) {
			$filename = "php://stdout";
		}

		$this->outfile = fopen($filename, "w");
		if (!$this->outfile) {
			return false;
		}

		$this->output_config();
		$this->output_options();
		$this->output_tuning();
		$this->output_hardware();
		$this->output_clock();
		$this->output_users();
		$this->output_fixup();
		$this->output_hostname();
		$this->output_domain();
		$this->output_nameif();
		$this->output_nameservers();
		$this->output_pppoe();
		$this->output_interfaces();
		$this->output_bridge();
		$this->output_routes();
		$this->output_dyndns();
		$this->output_portfw();
		$this->output_proxyarp();
		$this->output_icmp();
		$this->output_acls();
		$this->output_apply();
		$this->output_autofw();
		$this->output_nat();
		$this->output_dhcpd();
		$this->output_snmp();
		$this->output_ssh();
		$this->output_qos();
		$this->output_logging();

		// Get the configuration output from each of the addons
		foreach (array_keys($this->addons) as $okey) {
			if (method_exists($this->addons[$okey], 'OutputConfig')) {
				$objconf = $this->addons[$okey]->OutputConfig();
				$this->line_output($objconf);
			}
		}

		fclose($this->outfile);
		return true;
	}
}

?>
