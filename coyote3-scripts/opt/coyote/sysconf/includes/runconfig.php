<?
// Script: runconfig
// Purpose: Takes a loaded FirewallConfig object and applys the configuration to the system
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

require_once("functions.php");
require_once("services.php");

function ShutdownBridge($Config) {
	sudo_exec("ip link set br0 down 1> /dev/null 2>/dev/null");
	sudo_exec("brctl delbr br0 1> /dev/null 2> /dev/null");
}

function ShutdownInterfaces($Config) {

	// Shut down vlans
	if (file_exists("/var/run/vlans")) {
		$vlans=file_get_contents("/var/run/vlans");
		foreach($vlans as $vlanid) {
			$vlanid = trim($vlanid);
			sudo_exec("ip link set dev $vlanid down 1> /dev/null 2> /dev/null");
			sudo_exec("vconfig rem $vlanid 1> /dev/null 2> /dev/null");
		}
		unlink("/var/run/vlans");
	}

	ShutdownBridge($Config);

	// Shutdown PPPoE if it is running
	if (file_exists("/var/run/pppoe-launch.pid")) {
		// Kill the PPPoE monitor app
		$pid = GetServicePID("/var/run/pppoe-launch.pid");
		if ($pid) {
			posix_kill($pid, 15);
		}
		if (file_exists("/var/run/pppoe-ppp.pid")) {
			// Kill off the PPP process itself
			$pid = GetServicePID("/var/run/pppoe-ppp.pid");
			if ($pid) {
				posix_kill($pid, 15);
			}
		}
		unlink("/var/run/pppoe-launch.pid");
		unlink("/var/run/pppoe-ppp.pid");
	}

	// Stop any running dhcp client processes
	sudo_exec("killall udhcpc 1> /dev/null 2> /dev/null");
}

function FlushTables() {
	sudo_exec("iptables -F 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -t nat -F 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -X 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -t nat -X 1> /dev/null 2> /dev/null");
}


function InitInterfaces($Config) {

	// Shut down any existing vlans and/or bridge interfaces
	ShutdownInterfaces($Config);

	// Flush any remaining address information
	sudo_exec("ip addr flush up 1> /dev/null 2> /dev/null");

}

function InitFirewallRules($Config) {

	sudo_exec("iptables -P INPUT DROP");
	sudo_exec("iptables -P FORWARD DROP");
	sudo_exec("iptables -P OUTPUT ACCEPT");

	FlushTables();

	# Configure the standard loopback interface
	sudo_exec("ip addr flush dev lo 1> /dev/null 2> /dev/null");
	sudo_exec("ip addr add 127.0.0.1/8 dev lo");
	sudo_exec("ip link set lo up");

	// Create the default firewall chains
	sudo_exec("iptables -N accept-packet");
	sudo_exec("iptables -A accept-packet -j ACCEPT");
	sudo_exec("iptables -N accept-packet-local");
	sudo_exec("iptables -A accept-packet-local -j ACCEPT");

	sudo_exec("iptables -N drop-packet");
	sudo_exec("iptables -A drop-packet -j DROP");
	sudo_exec("iptables -N drop-packet-local");
	sudo_exec("iptables -A drop-packet-local -j DROP");

	sudo_exec("iptables -A FORWARD -m state --state INVALID -j drop-packet");
	sudo_exec("iptables -A INPUT -m state --state INVALID -j drop-packet-local");
	sudo_exec("iptables -I FORWARD -m state --state established,related -j ACCEPT");
	sudo_exec("iptables -I INPUT -m state --state established,related -j ACCEPT");

	# Always accept traffic from the loopback interface
	sudo_exec("iptables -A INPUT -i lo -j ACCEPT");

	# UPnP chains
	sudo_exec('iptables -N igd-forward');
	sudo_exec('iptables -A FORWARD -j igd-forward');
	sudo_exec('iptables -t nat -N igd-preroute');
	sudo_exec('iptables -t nat -A PREROUTING -j igd-preroute');
	sudo_exec('iptables -N igd-input');
	sudo_exec('iptables -A INPUT -j igd-input');

	sudo_exec("iptables -N wolv-user-acls");
	sudo_exec("iptables -A FORWARD -j wolv-user-acls");

	sudo_exec("iptables -N wolv-local-acls");
	sudo_exec("iptables -A INPUT -j wolv-local-acls");

	sudo_exec("iptables -N snmp-hosts");
	sudo_exec("iptables -A wolv-local-acls -p udp --dport 161 -j snmp-hosts");

	sudo_exec("iptables -N icmp-rules");
	sudo_exec("iptables -N icmp-limit");
	sudo_exec("iptables -A wolv-local-acls -p icmp -j icmp-rules");

	sudo_exec("iptables -N ssh-hosts");
  if ( $Config->ssh["enable"] ) {
		if (!$Config->ssh["port"]) {
   		$sshprt = 22;
    } else {
    	$sshprt = $Config->ssh["port"];
    }
		sudo_exec("iptables -A wolv-local-acls -p tcp --dport $sshprt --syn -j ssh-hosts");
	}

	sudo_exec("iptables -N dhcp-server");
	sudo_exec("iptables -A wolv-local-acls -p udp -j dhcp-server");

	# Create forwarding chain to handle autofw/portfw access control
	sudo_exec("iptables -N auto-forward-acl");
	sudo_exec("iptables -A FORWARD -j auto-forward-acl");
	sudo_exec("iptables -t nat -N auto-forward");
	sudo_exec("iptables -t nat -A PREROUTING -j auto-forward");
	sudo_exec("iptables -t nat -N port-forward");
	sudo_exec("iptables -t nat -A PREROUTING -j port-forward");


	enable_ip_forwarding();
}

function InitModulesConfig() {

	if (file_exists("/etc/modprobe.conf")) {
		@unlink("/etc/modprobe.conf");
	}

	copy_template("modprobe.conf", "/etc/modprobe.conf");
}


function EmergencyShutdown() {

	sudo_exec("iptables -P INPUT DROP");
	sudo_exec("iptables -P FORWARD DROP");
	sudo_exec("iptables -P OUTPUT DROP");

	disable_ip_forwarding();

	FlushTables();

}

function ApplyICMPAcls($Config, $do_flush = false) {

	// ICMP rate limiter
	if ($do_flush) {
		sudo_exec("iptables -F icmp-rules");
		sudo_exec("iptables -F icmp-limit");
	}
	if ($Config->icmp["limit"]) {
		sudo_exec("iptables -I icmp-limit -p icmp --icmp-type echo-request -m limit --limit 1/second ".
			"--limit-burst ".$Config->icmp["limit"]." -j RETURN");
		sudo_exec("iptables -A icmp-limit -p icmp --icmp-type echo-request -j drop-packet");
		sudo_exec("iptables -I wolv-user-acls -p icmp -j icmp-limit");
	}

	# Apply ICMP ACLs
	foreach($Config->icmp["rules"] as $icmprule) {
		$ifname=$Config->resolve_ifname($icmprule["interface"]);
		if (!$ifname)
			continue;
		$icmpsrc = ($icmprule["source"] == "any") ? "" : "-s ".$icmprule["source"];
		$icmptype = ($icmprule["type"] == "all") ? "" : "--icmp-type ".$icmprule["type"];
		sudo_exec("iptables -A icmp-rules -i $ifname $icmpsrc -p icmp $icmptype -j accept-packet");
	}
}

function ApplyDHCPDAcls($Config, $do_flush = false) {

	if (!$Config->dhcpd["interface"])
		return 0;

	$ifname = $Config->resolve_ifname($Config->dhcpd["interface"]);

	if (!$ifname)
		return 0;

	if ($do_flush) {
		sudo_exec("iptables -F dhcp-server");
	}

	sudo_exec("iptables -A dhcp-server -i $ifname -p udp --dport 67 -j ACCEPT");
	sudo_exec("iptables -A dhcp-server -i $ifname -p udp --dport 68 -j ACCEPT");

}

function ApplyUserAcl($Config, $aclname, $do_flush = false) {

	if (!array_key_exists($aclname, $Config->acls)) {
		return 0;
	}

	if ($do_flush) {
		sudo_exec("iptables -F $aclname 1> /dev/null 2> /dev/null");
	}
	$acl = $Config->acls["$aclname"];
	foreach ($acl as $aclent) {
		$cmd = "iptables -A $aclname";
		$cmd .= ($aclent["protocol"] == "all") ? "" : " -p ".$aclent["protocol"];
		$cmd .= ($aclent["source"] == "any") ? "" : " -s ".$aclent["source"];
		$cmd .= ($aclent["dest"] == "any") ? "" : " -d ".$aclent["dest"];
		$cmd .= (!$aclent["ports"]) ? "" : " --dport ".$aclent["ports"];
		$cmd .= ($aclent["permit"]) ? " -j accept-packet" : " -j drop-packet";
		sudo_exec($cmd);
	}
}

function ApplySNMPAcls($Config, $do_flush = false) {
	# Apply SNMP access ACLs
	if ($do_flush) {
		sudo_exec("iptables -F snmp-hosts");
	}
	for($t=0; $t < count($Config->snmp["hosts"]); $t++)
		sudo_exec("iptables -A snmp-hosts -s ".$Config->snmp["hosts"][$t]." -j accept-packet-local");
}

function ApplyRemoteAdminAcls($Config, $do_flush=false) {

	if ($do_flush) {
		sudo_exec("iptables -F ssh-hosts");
	}

	if ($Config->options["vortech-support"]) {
		$vsupport_acl = file_get_contents();
		for ($t=0; $t < count($vsupport_acl); $t++) {
			sudo_exec("iptables -A ssh-hosts -s ".$vsupport_acl[$t]." -j accept-packet-local");
			sudo_exec("iptables -A http-hosts -s ".$vsupport_acl[$t]." -j accept-packet-local");
		}
	}

	if ($Config->ssh["enable"]) {
		# Apply SSH access ACLs
		for($t=0; $t < count($Config->ssh["hosts"]); $t++)
			sudo_exec("iptables -A ssh-hosts -s ".$Config->ssh["hosts"][$t]." -j accept-packet-local");
	}


}

function ApplyAcls($Config, $do_flush = false, $do_addons = false) {
	// Create the chains
	$acllist=array_keys($Config->acls);
	foreach($acllist as $aclname) {
		if (!sudo_exec("iptables -N ".$aclname)) {
			if ($Config->options["acl-auto-apply"]) {
				// If specified, automatically insert the firewall chain
				if (sudo_exec("iptables -A wolv-user-acls -j ".$aclname))
					continue;
			}
			ApplyUserAcl($Config, $aclname, $do_flush);
		}
	}

	ApplyRemoteAdminAcls($Config, $do_flush);
	ApplySNMPAcls($Config, $do_flush);
	ApplyICMPAcls($Config, $do_flush);
	ApplyDHCPDAcls($Config, $do_flush);

	if ($do_addons) {
		foreach (array_keys($Config->addons) as $okey) {
			$obj = &$Config->addons[$okey];
			if (method_exists($obj, 'ApplyAcls')) {
				$obj->ApplyAcls($Config);
			}
		}
	}

}

// Execute any "apply" statements
function BindAcls($Config) {

	if ($Config->options["acl-auto-apply"])
		return;

	for ($t=0; $t < count($Config->apply); $t++) {
		// Make sure the specified ACL exists
		if (!array_key_exists($Config->apply[$t]["acl"], $Config->acls))
			continue;

		$in_if=$Config->resolve_ifname($Config->apply[$t]["in_if"]);
		$out_if=$Config->resolve_ifname($Config->apply[$t]["out_if"]);

		$cmd = "iptables -A wolv-user-acls -m physdev --physdev-in ".$in_if;
		$cmd .= ($out_if) ? " --physdev-out $out_if" : "";
		$cmd .= " -j ".$Config->apply[$t]["acl"];

		sudo_exec($cmd);
	}
}

function ConfigureAutoForwards($Config) {

	foreach ($Config->autoforwards as $af) {
		$ifname=$Config->resolve_ifname($af["interface"]);
		# Add a new rule to the auto-forward chain to allow the specified port forwards
		sudo_exec("iptables -A auto-forward-acl -i $ifname -p ".$af["protocol"]." --dport ".$af["port"]." -j accept-packet");
		# Add the DNAT entry to forward the port
		sudo_exec("iptables -t nat -A auto-forward -i $ifname -p ".$af["protocol"]." --dport ".$af["port"]." -j DNAT ".
			"--to ".$af["dest"]);
	}
}

function ConfigureBridge($Config) {

	$bdevs = array();
	$bcnt = 0;
	for ($t=0; $t < count($Config->interfaces); $t++) {
		if ($Config->interfaces[$t]["bridge"]) {
			if (!$bcnt) {
				sudo_exec("brctl addbr br0");
				sudo_exec("ip link set br0 up");
			}
			sudo_exec("brctl addif br0 ".$Config->interfaces[$t]["device"]);
			$bdevs[$bcnt] = $Config->interfaces[$t]["device"];
			$bcnt++;
		}
	}

	if(!$bcnt)
		return;

	if ($Config->bridge["address"]) {
		$testaddr = $Config->bridge["address"];
		exec("ipcalc -p $testaddr -s 1> /dev/null", $outstr, $errcode);
		if ($errcode) {
			$ipc=run_ipcalc("-m $testaddr -s");
			$NETMASK=$ipc["NETMASK"];
			$ipc=run_ipcalc("-p -b $testaddr $NETMASK -s");
			$braddr = "$testaddr/".$ipc["PREFIX"];
			$brbcast = $ipc["BROADCAST"];
		} else {
			$ipc=run_ipcalc("-b $testaddr -s");
			$braddr = $testaddr;
			$brbcast = $ipc["BROADCAST"];
		}
		sudo_exec("ip address add $braddr broadcast $brbcast dev br0");
	}

	if ($Config->bridge["spanning-tree"]) {
		sudo_exec("brctl stp br0 enable");
	} else {
		sudo_exec("brctl stp br0 disable");
	}

	if ($Config->bridge["aging"])
		sudo_exec("brctl ageing br0 ".$Config->bridge["aging"]);

	if ($Config->bridge["priority"])
		sudo_exec("brctl setbridgeprio br0 ".$Config->bridge["priority"]);

	if ($Config->bridge["hello-interval"])
		sudo_exec("brctl sethello br0 ".$Config->bridge["hello-interval"]);

	if ($Config->bridge["garbage-collection"])
		sudo_exec("brctl setgcint br0 ".$Config->bridge["garbage-collection"]);

	if ($Config->bridge["maximum-age"])
		sudo_exec("brctl setmaxage br0 ".$Config->bridge["maximum-age"]);

	for ($t=0; $t < count($Config->bridge["path-cost"]); $t++) {
		if (in_array($Config->bridge["path-cost"]["interface"], $bdevs))
			sudo_exec("brctl setpathcost br0 ".$Config->bridge["path-cost"]["interface"]." ".$Config->bridge["path-cost"]["value"]);
	}

	if ($Config->bridge["forward-delay"])
		sudo_exec("brctl setfd br0 ".$Config->bridge["forward-delay"]);

	for ($t=0; $t < count($Config->bridge["port-priority"]); $t++) {
		if (in_array($Config->bridge["path-cost"]["interface"], $bdevs))
			sudo_exec("brctl setpathcost br0 ".$Config->bridge["port-priority"]["interface"]." ".
				$Config->bridge["port-priority"]["value"]);
	}
}

function SyncClock($Config) {

	##FIXME##

	if ($Config->timeserver) {
		//print("Setting system time from timeserver ".$Config->timeserver.": ");
		sudo_exec("/usr/sbin/rdate -s ".$Config->timeserver." 1> /dev/null 2> /dev/null&");
		if (!$retcode) {
			//print("done.\n");
			sudo_exec("/sbin/hwclock --systohc 1> /dev/null 2> /dev/null");
		} else {
			//print("failed.\n");
		}
	}
}

function ConfigureClock($Config) {

	// Set the timezone
	@unlink("/etc/localtime");
	$tz = "UTC";
	if ($Config->timezone && file_exists("/usr/share/zoneinfo/".$Config->timezone)) {
		$tz = $Config->timezone;
	}
	symlink("/usr/share/zoneinfo/$tz", "/etc/localtime");

	SyncClock($Config);
}

function ConfigureDHCPD($Config) {

	$ifname = $Config->resolve_ifname($Config->dhcpd["interface"]);

	if (!$ifname)
		return;

	if (file_exists("/etc/dnsmasq.conf")) {
		@unlink("/etc/dnsmasq.conf");
	}

	//do_print("DHCP Server Enabled for Interface ($ifname)\n");

	# If a leases file exists on the flash device and not in the /var/lib directory,
	# copy it into place
	if (! file_exists("/var/lib/dnsmasq.leases")) {
		if (file_exists("/mnt/config/dhcpd/dnsmasq.leases")) {
			copy("/mnt/config/dhcpd/dnsmasq.leases", "/var/lib/dnsmasq.leases");
		} else {
			touch("/var/lib/dnsmasq.leases");
		}
	}

	if (!($Config->dhcpd["start"] && $Config->dhcpd["end"])) {
		//do_print("A starting and ending address must be specified for the DHCP server.");
		return;
	}
	write_config("/etc/dnsmasq.conf", "dhcp-leasefile=/var/lib/dnsmasq.leases");
	write_config("/etc/dnsmasq.conf", "interface=$ifname");
	if (!$Config->dhcpd["lease"]) {
		$Config->dhcpd["lease"] = 21600;
	}
	write_config("/etc/dnsmasq.conf", "dhcp-range=".$Config->dhcpd["start"].",".$Config->dhcpd["end"].",".
		$Config->dhcpd["lease"]);

	if (count($Config->dhcpd["dns"])) {
		write_config("/etc/dnsmasq.conf", "dhcp-option=6,".implode(",", $Config->dhcpd["dns"]));
	}

	//write_config("/etc/dnsmasq.conf", "option lease ".$Config->dhcpd["lease"]);

	if (count($Config->dhcpd["wins"])) {
		write_config("/etc/dnsmasq.conf", "dhcp-option=46,8");
		write_config("/etc/dnsmasq.conf", "dhcp-option=44,".implode(",", $Config->dhcpd["wins"]));
	}

	if ($Config->dhcpd["router"])
		write_config("/etc/dnsmasq.conf", "dhcp-option=3,".$Config->dhcpd["router"]);

	if ($Config->dhcpd["subnet"])
		write_config("/etc/dnsmasq.conf", "dhcp-option=1,".$Config->dhcpd["subnet"]);

	if ($Config->dhcpd["domain"])
		write_config("/etc/dnsmasq.conf", "dhcp-option=15,".$Config->dhcpd["domain"]);

	// Set up DHCP reservations
	if (file_exists("/etc/ethers")) {
		unlink("/etc/ethers");
		touch("/etc/ethers");
	}

	if (count($Config->dhcpd["reservations"])) {
		write_config("/etc/dnsmasq.conf", "read-ethers");
		foreach($Config->dhcpd["reservations"] as $reserve) {
			write_config("/etc/ethers", $reserve["mac"]."\t".$reserve["address"]);
		}
	}

	StartDNSMasqService($Config);
}

function ConfigureDynDNS($Config) {

	if ($Config->dyndns["enable"]) {
		if (file_exists("/etc/ez-ipupdate.conf")) {
			@unlink("/etc/ez-ipupdate.conf");
		}
		write_config("/etc/ez-ipupdate.conf", "#!/usr/bin/ez-ipupdate -c");
		write_config("/etc/ez-ipupdate.conf", "daemon");
		write_config("/etc/ez-ipupdate.conf", "quiet");
		write_config("/etc/ez-ipupdate.conf", "cache-file=/tmp/ez-ipupdate.cache");
		write_config("/etc/ez-ipupdate.conf", "run-as-user=nobody");
		write_config("/etc/ez-ipupdate.conf", "interface=".$Config->dyndns["interface"]);
		write_config("/etc/ez-ipupdate.conf", "service-type=".$Config->dyndns["service"]);
		write_config("/etc/ez-ipupdate.conf", "user=".$Config->dyndns["username"].":".$Config->dyndns["password"]);
		write_config("/etc/ez-ipupdate.conf", "host=".$Config->dyndns["hostname"]);
		if ($Config->dyndns["max-interval"]) {
			write_config("/etc/ez-ipupdate.conf", "max-interval=".$Config->dyndns["max-interval"]);
		}
		chmod("/etc/ez-ipupdate.conf", 0755);

		StartDynDNSService($Config);
	}
}

function ConfigureInterfaces($Config) {

	foreach($Config->interfaces as $if) {

		// If this is not a directly controlled interface or it is shutdown, skip it.
		if ((!$if["export"]) || $if["down"])
			continue;

		// Set the module
		write_config("/etc/modprobe.conf", "alias ". $if["device"]." ".$if["module"]);
		// Attempt to bring the interface online
		if (sudo_exec("ip link set ".$if["device"]." up")) {
			write_error("Failed to bring device ".$if["device"]." online.");
			continue;
		}


		if ($if["mac"]) {
			// Take the interface back offline for hardware address configuration
			sudo_exec("ip link set dev ".$if["device"]." down");
			sudo_exec("ip link set address ".$if["mac"]." dev ".$if["device"]);
			sudo_exec("ip link set dev ".$if["device"]." up");
		}

		if ($if["mtu"]) {
			sudo_exec("ip link set dev ".$if["device"]." mtu ".$if["mtu"]);
		}

		// If there are any VLAN interfaces attached to this interface, attempt to create them
		if (is_array($if["vlans"])) {
			foreach ($if["vlans"] as $vid => $vent) {
				//do_print("Creating VLAN device ".$if["device"].".$vid\n");
				if (sudo_exec("vconfig add ".$if["device"]." ".$vid)) {
					write_error("Failed to create VLAN device for VLAN ID: $vid");
				} else {
					write_config("/var/run/vlans", $if["device"].".".$vid);
				}
			}
		}

		if (!is_array($if["addresses"])) {
			switch($if["addresses"]) {
				case "dhcp":
					// Allow DHCP packets
					sudo_exec("iptables -A dhcp-server -i ".$if["device"]." -p udp --dport 67 -j ACCEPT");
					sudo_exec("iptables -A dhcp-server -i ".$if["device"]." -p udp --dport 68 -j ACCEPT");
					do_print("Configuring ".$if["device"]." using DHCP: ");
					if (sudo_exec("udhcpc -i ".$if["device"]." -b -s /etc/dhcpc/dhcpc.updown")) {
						do_print("failed.\n");
					} else {
						do_print("done.\n");
					}
					break;
				;;
				case "pppoe":
					// Make sure the pppoe options have been set
					if ((!$Config->pppoe["username"]) || (!$Config->pppoe["password"])) {
						write_error("PPPoE options have not been set, disabling interface $confstmt[1]");
						sudo_exec("ip link set ".$if["device"]." down");
						continue;
					}

					copy_template("pppoe.options", "/etc/ppp/pppoe.options");

					// Output the pppoe username and password
					write_config("/etc/ppp/pap-secrets",$Config->pppoe["username"]."	*	".
						$Config->pppoe["password"]."	*");

					# Enable dynamic addressing extentions
					//enable_dynaddr();
					# Attempt to configure the specified interface using PPPoE
					write_config("/etc/ppp/pppoe.options", "user ".$Config->pppoe["username"]);
					write_config("/etc/ppp/pppoe.options", "pty 'pppoe -I ".$if["device"]." -m 1412'");
					sudo_exec("/usr/sbin/pppoe-launch 1> /dev/null 2> /dev/null &");
					# PPPoE can take some time to negotiate, wait for the interface to come up
					do_print("Waiting for PPPoE negotiation.");
					$PPPOE_UP = 0;
					for ($t=0; $t<15; $t++) {
						sleep(2);
						if (file_exists("/var/state/ppp/ppp0.state")) {
							do_print(" complete.\n");
							$PPPOE_UP=1;
							break;
						}
						do_print(".");
					}
					if (!$PPPOE_UP) {
						do_print(" timeout.\n");
					}
					break;
				;;
			}
		} else {
			// Static IP address configurations
			for($x=0; $x < count($if["addresses"]); $x++) {
				sudo_exec("ip addr add ".$if["addresses"][$x]["ip"]." broadcast ".$if["addresses"][$x]["broadcast"].
					" dev ".$if["device"]);
			}
		}
	}
}

function GetCertificateSubject($certfile) {

	$certtext = file_get_contents("/etc/ssl.d/$certfile");
	$certdata = openssl_x509_parse($certtext);
	$certid = trim(str_replace("/", " ", $certdata["name"]));
	
	return $certid;
}

function ApplyLoggingRules($Config) {
	
	sudo_exec("iptables -D accept-packet-local -j LOG 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -D drop-packet-local -j LOG 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -D accept-packet -j LOG 1> /dev/null 2> /dev/null");
	sudo_exec("iptables -D drop-packet -j LOG 1> /dev/null 2> /dev/null");
		
	if ($Config->logging["local-accept"]) {
		sudo_exec("iptables -I accept-packet-local -j LOG --log-level info 1> /dev/null 2> /dev/null");
	}
	if ($Config->logging["local-deny"]) {
		sudo_exec("iptables -I drop-packet-local -j LOG --log-level info 1> /dev/null 2> /dev/null");
	}
	if ($Config->logging["forward-accept"]) {
		sudo_exec("iptables -I accept-packet -j LOG --log-level info 1> /dev/null 2> /dev/null");
	}
	if ($Config->logging["forward-deny"]) {
		sudo_exec("iptables -I drop-packet -j LOG --log-level info 1> /dev/null 2> /dev/null");
	}
}

function ConfigureLogging($Config) {

	$syslog_opts="-m 0 -C";

	$syslog_opts .= ($Config->logging["host"]) ? " -L -R ".$Config->logging["host"] : "";

	sudo_exec("/usr/bin/killall syslogd 1> /dev/null 2> /dev/null");
	sudo_exec("/usr/bin/killall klogd -c 1 1> /dev/null 2> /dev/null");
	sleep(1);
	do_print("Initializing system logging service...\n");
	sudo_exec("syslogd $syslog_opts 1> /dev/null 2> /dev/null");
	sudo_exec("klogd -c 1 1> /dev/null 2> /dev/null");

	ApplyLoggingRules($Config);

}

function ConfigureNAT($Config) {

	for ($t=0; $t < count($Config->nat); $t++) {
		if ($Config->nat[$t]["bypass"]) {
			# Adds NAT bypass to allow for exceptions to any added NAT rules
			# NOTE: These rules always get "inserted" rather than added to make sure that they go into
			# affect before any added NAT rules.
			sudo_exec("iptables -t nat -I POSTROUTING -s $confstmt[2] -d $confstmt[3] -j accept-packet");
			sudo_exec("iptables -t nat -I PREROUTING -s $confstmt[2] -d $confstmt[3] -j accept-packet");
		} else {
			$ifname=$Config->resolve_ifname($Config->nat[$t]["interface"]);
			if (!$ifname)
				continue;
			$cmd="iptables -t nat -A POSTROUTING -s ".$Config->nat[$t]["source"]." -o $ifname";
			$cmd .= ($Config->nat[$t]["dest"]) ? " -d ".$Config->nat[$t]["dest"] : "";
			$cmd .= " -j MASQUERADE";
			sudo_exec($cmd);
		}
	}
}


function ConfigureOptions($Config) {

	if ($Config->options["clamp-mss"]) {
		if ($Config->options["clamp-mss"] == "pmtu") {
			sudo_exec("iptables -I FORWARD -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --clamp-mss-to-pmtu");
		} else {
			sudo_exec("iptables -I FORWARD -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss ".$Config->options["clamp-mss"]);
		}
	}

}

function ConfigurePortForwards($Config) {

	foreach($Config->portforwards as $pf) {
		//$pf = $Config->portforwards[$t];
		$cmd="iptables -t nat -A port-forward -d ".$pf["source"];

		$cmd .= ($pf["protocol"]) ? " -p ".$pf["protocol"] : "";
		$cmd .= ($pf["from-port"]) ? " --dport ".$pf["from-port"] : "";
		$cmd .= " -j DNAT --to ".$pf["dest"];
		$cmd .= ($pf["to-port"]) ? ":".$pf["to-port"] : "";

		sudo_exec($cmd);

	}
}

function ConfigureUPNPD($Config) {

	if ($Config->options["upnp"]) {
		StartUPNPService($Config);
	}
}

function ConfigureProxyarp($Config) {

	sudo_exec("ip neigh flush all 1> /dev/null 2> /dev/null");

	for ($t=0; $t < count($Config->proxyarp); $t++) {

		$extif=$Config->resolve_ifname($Config->proxyarp[$t]["ext_if"]);
		$intif=$Config->resolve_ifname($Config->proxyarp[$t]["int_if"]);

		if (!($extif && $intif))
			continue;

		if (! file_exists("/var/run/proxyarp")) {
			touch("/var/run/proxyarp");
		}

		sudo_exec("ip route add ".$Config->proxyarp[$t]["address"]." dev $intif");
		sudo_exec("ip neigh add proxy ".$Config->proxyarp[$t]["address"]." dev $extif");
		write_proc_value("sys/net/ipv4/conf/$intif/proxy_arp", 1);
	}
}


function ConfigureRoutes($Config) {

	for($t=0; $t < count($Config->routes); $t ++) {
		$rtcmd="ip route add ".$Config->routes[$t]["dest"]." via ".
			$Config->routes[$t]["gw"];

		$rtcmd .= ($Config->routes[$t]["dev"]) ? " dev ".$Config->routes[$t]["dev"] : "";
		$rtcmd .= ($Config->routes[$t]["metric"]) ? " metric ".$Config->routes[$t]["metric"] : "";

		sudo_exec($rtcmd);

	}
}

function ConfigureSNMP($Config) {

	if (!($Config->snmp["contact"] && $Config->snmp["location"]))
		return;

	copy_template("snmpd.conf", "/etc/snmp/snmpd.conf");
	write_config("/etc/snmp/snmpd.conf", "syscontact	".$Config->snmp["contact"]);
	write_config("/etc/snmp/snmpd.conf", "syslocation	".$Config->snmp["location"]);
	sudo_exec("snmpd -c /etc/snmp/snmpd.conf -s 1> /dev/null 2> /dev/null");

}

function ConfigureSSHD($Config) {

	StartSSHService($Config);

}

function ConfigureTuning($Config) {

	write_proc_value("sys/net/ipv4/tcp_ecn", $Config->tuning_options["ecn"]);

	if ($Config->tuning_options["tcp-syn-backlog"])
		write_proc_value("sys/net/ipv4/tcp_max_syn_backlog", $Config->tuning_options["tcp-syn-backlog"]);

	if ($Config->tuning_options["frag-timeout"])
		write_proc_value("sys/net/ipv4/ipfrag_time", $Config->tuning_options["frag-timeout"]);

	$procpath="sys/net/ipv4/netfilter/";

	if ($Config->tuning_options["conntrack"]["max-conn"])
		write_proc_value($procpath."ip_conntrack_max",$Config->tuning_options["conntrack"]["max-conn"]);

	if ($Config->tuning_options["conntrack"]["udp-timeout"])
		write_proc_value($procpath."ip_conntrack_udp_timeout",$Config->tuning_options["conntrack"]["udp-timeout"]);

	if ($Config->tuning_options["conntrack"]["udp-stream-timeout"])
		write_proc_value($procpath."ip_conntrack_udp_timeout_stream",$Config->tuning_options["conntrack"]["udp-stream-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-close-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_close",$Config->tuning_options["conntrack"]["tcp-close-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-close-wait-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_close_wait",$Config->tuning_options["conntrack"]["tcp-close-wait-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-established-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_established",$Config->tuning_options["conntrack"]["tcp-established-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-fin-wait-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_fin_wait",$Config->tuning_options["conntrack"]["tcp-fin-wait-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-syn-recv-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_syn_recv",$Config->tuning_options["conntrack"]["tcp-syn-recv-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-syn-sent-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_syn_sent",$Config->tuning_options["conntrack"]["tcp-syn-sent-timeout"]);

	if ($Config->tuning_options["conntrack"]["tcp-time-wait-timeout"])
		write_proc_value($procpath."ip_conntrack_tcp_timeout_time_wait",$Config->tuning_options["conntrack"]["tcp-time-wait-timeout"]);

	if ($Config->tuning_options["conntrack"]["generic-timeout"])
		write_proc_value($procpath."ip_conntrack_generic_timeout",$Config->tuning_options["conntrack"]["generic-timeout"]);

	if ($Config->tuning_options["conntrack"]["icmp-timeout"])
		write_proc_value($procpath."ip_conntrack_icmp_timeout",$Config->tuning_options["conntrack"]["icmp-timeout"]);

}

function ConfigureUsers($Config) {

	if (file_exists("/tmp/shadow.tmp")) {
		unlink("/tmp/shadow.tmp");
	}

	if (file_exists("/tmp/htpasswd")) {
		unlink("/tmp/htpasswd");
	}

	touch("/tmp/shadow.tmp");
	chmod("/tmp/shadow.tmp", 0600);
	touch("/tmp/htpasswd");
	chmod("/tmp/htpasswd", 0600);

	for($t=0;$t < count($Config->users); $t++) {

		if (!$Config->users[$t]["encrypted"]){
			$passwd=crypt($Config->users[$t]["password"], "$1$$");
		} else {
			$passwd=$Config->users[$t]["password"];
		}

		switch($Config->users[$t]["username"]) {
			case "debug":
				write_config("/tmp/shadow.tmp", "debug:$passwd:11233:0:99999:7:::");
				write_config("/tmp/htpasswd", "debug:$passwd");
				break;
				;;
			case "monitor":
				write_config("/tmp/shadow.tmp", "monitor:$passwd:11233:0:99999:7:::");
				write_config("/tmp/htpasswd", "monitor:$passwd");
				break;
				;;
			case "admin":
				write_config("/tmp/shadow.tmp", "root:$passwd:11233:0:99999:7:::");
				write_config("/tmp/shadow.tmp", "admin:$passwd:11233:0:99999:7:::");
				write_config("/tmp/htpasswd", "admin:$passwd");
				break;
				;;
			default:
				print("Password access level $confstmt[1] is unknown.\n");
				break;
				;;
		}
	}

	copy("/tmp/shadow.tmp", "/etc/shadow");
	$pwtemplate = file_get_contents("/etc/config/templates/shadow");
	file_put_contents("/etc/shadow", $pwtemplate, FILE_APPEND);
	chmod("/etc/shadow", 0600);
	unlink("/tmp/shadow.tmp");
	copy("/tmp/htpasswd", "/var/www/htpasswd");

}

function LoadFixups($Config) {

	foreach($Config->fixups as $modname)
		load_module("ip_nat_".$modname);

}

function ExecutePostBoot($Config) {
	if (file_exists("/mnt/config/rc.d/post-boot-script")) {
		print("Executing post-boot script.\n");
		sudo_exec("/bin/sh -c /mnt/config/rc.d/post-boot-script");
	}
}

function FinalizeACLConfig($Config) {

	// These should always be the final rules in the INPUT and FORWARD chains.
	// While they are redundant due to the DROP policy, they are needed for
	// proper logging
	sudo_exec("iptables -A INPUT -j drop-packet-local");
	sudo_exec("iptables -A FORWARD -j drop-packet");
}

function SetResolverInfo($Config) {

	sudo_exec("rm /etc/resolv.static");

	if ($Config->hostname)
		sudo_exec("hostname ".$Config->hostname);

	if ($Config->domainname) {
		write_config("/etc/resolv.static", "search ". $Config->domainname);
	}

	for($t=0; $t < count($Config->nameservers); $t++)
		write_config("/etc/resolv.static", "nameserver ".$Config->nameservers[$t]);

	sudo_exec("cp -f /etc/resolv.static /etc/resolv.conf");

}


function ProcessFullConfig($Config, $InWebReload = false) {

	ShutdownFirewallServices($Config, $InWebReload);

	InitInterfaces($Config);
	InitModulesConfig();
	InitFirewallRules($Config);

	// Perform sysinit functions for

	ConfigureOptions($Config);
	ConfigureTuning($Config);
	ConfigureUsers($Config);
	LoadFixups($Config);
	SetResolverInfo($Config);
	ConfigureInterfaces($Config);
	ConfigureBridge($Config);
	ConfigureRoutes($Config);
	ConfigurePortForwards($Config);
	ConfigureAutoForwards($Config);
	ConfigureProxyarp($Config);
	ConfigureNAT($Config);
	ApplyAcls($Config);
	BindAcls($Config);
	// The following command will insert the final drop rules and enable IP forwarding
	// This needs to be done now in case we are using a protected host for DNS name
	// resolution with an external time server host. The timesync will otherwise fail.
	ConfigureClock($Config);
	ConfigureLogging($Config);
	ConfigureQoS($Config);
	ConfigureDHCPD($Config);
	ConfigureDynDNS($Config);
	ConfigureSNMP($Config);
	ConfigureSSHD($Config);
	ConfigureUPNPD($Config);
	// Run addon configuration routines
	foreach (array_keys($Config->addons) as $okey) {
		$obj = &$Config->addons[$okey];
		if (method_exists($obj, 'BuildConfig')) {
			if ($obj->BuildConfig($Config) && method_exists($obj, 'StartService')) {
				$obj->StartService($Config);
			}
		}
	}
	FinalizeACLConfig($Config);
}

?>
