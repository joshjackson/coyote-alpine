#!/usr/bin/php -q
<?
	require_once("functions.php");
	require_once("configfile.php");
	
	print("Initial system configuration generator v4.00\n\n");
	print("This script will assist you in the process of generating the initial\n");
	print("system configuration. Once complete and the system has finished starting\n");
	print("up, you can use the console or web admin to fine-tune your configuration.\n\n");
	
	// Prevent script timeouts
	set_time_limit(0);
	
	function pause() {
		print("--- Press ENTER to continue ---");
		fgets(STDIN, 255);
	}
	
	function read_ip_subnet() {
	
		$isvalid = false;
		while (!$isvalid) {
			print("IP Address: ");
			$ipaddr = trim(fgets(STDIN, 16));
			if (!is_ipaddr($ipaddr)) {
				print("Invalid IP Address.\n");
				continue;
			}
			print("Subnet Mask: ");
			$ipmask = trim(fgets(STDIN, 16));
			$bc = gen_mask_bit_count($ipmask);
			if (($bc === false) || ($bc < 2) || ($bc > 32)) {
				print("Invalid subnet mask.\n");
				continue;
			}
			$isvalid = true;
		}
		$ret = array();
		$ret["ip"] = $ipaddr;
		$ret["bits"] = $bc;
		return $ret;
	}
	
	// build a list of installed network cards
	$nics = GetNicModuleNames();
	$configfile = array();
	
	if (count($nics) < 2) {
		print("At least 2 supported PCI network interfaces are required.\n\n");
		print("Setup will continue, but your firewall will not function properly!");
		pause();
	}
	
	array_push($configfile, "config version 4.00");
	array_push($configfile, "hostname firewall");
	array_push($configfile, "domain-name localdomain");
	array_push($configfile, "password admin fwadmin");
	array_push($configfile, "password debug fwadmin");
	array_push($configfile, "fixup pptp");
	array_push($configfile, "fixup ftp");
	array_push($configfile, "fixup irc");
	array_push($configfile, "hardware autodetect");
	array_push($configfile, "clock server time.vortech.net");
	array_push($configfile, "clock timezone EST");
	
	$isvalid = false;
	$useppp = false;
	
	while (!$isvalid) {
		print("\n\nWAN Interface Configuration\n\n");
		print("\t1. DHCP Address\n");
		print("\t2. PPPoE Address\n");
		print("\t3. Static IP Address\n\n");
		print("Please select address type: ");
		$wantype = fgets(STDIN, 10);
		switch ($wantype) {
		
			case 1:
				// DHCP Assigned addressing
				array_push($configfile, "interface eth0 address dhcp");		
				$isvalid = true;
				break;	
			case 2:
				array_push($configfile, "interface eth0 address pppoe");
				print("Enter PPPoE Username: ");
				$pppoeuser = trim(fgets(STDIN, 255));
				print("Enter PPPoE Password: ");
				$pppoepass = trim(fgets(STDIN, 255));
				$isvalid = true;
				$useppp = true;
				break;
			case 3:
				$wanip = read_ip_subnet();
				array_push($configfile, "interface eth0 address ".$wanip["ip"]."/".$wanip["bits"]);
				while (true) {
					print("Enter default gateway: ");
					$gateway = trim(fgets(STDIN, 16));
					if (!is_ipaddr($gateway)) {
						print("Invalid gateway address.\n");
						continue;
					}
					array_push($configfile, "route add 0.0.0.0/0 $gateway");
					break;
				}
				$isvalid = true;
		}
	}	

	print("\n\nLAN Interface Configuration\n\n");
	$lanip = read_ip_subnet();
	$lannet = gen_ip_subnet($lanip["ip"], $lanip["bits"]);
	$lannet = $lannet."/".$lanip["bits"];
	array_push($configfile, "interface eth1 address ".$lanip["ip"]."/".$lanip["bits"]);

	$isvalid = false;
	while (!$isvalid) {
		print("\n\nInternet Connection Sharing\n");
		print("If this firewall should share its Internet connection with the attached\n");
		print("network, this needs to be enabled. This option enabled outbound NAT for\n");
		print("the protected hosts. If unsure, choose Y.");
		print("\n\nEnable Internet Connection Sharing? [Y/n] ");
		$yn = trim(fgets(STDIN, 255));
		if (($yn == "") || ($yn == "Y") || ($yn == "y")) {
			// Enable outbound NAT
			if ($useppp) {
				array_push($configfile, "nat ppp0 ".$lannet);
			} else {
				array_push($configfile, "nat eth0 ".$lannet);
			}
			array_push($configfile, "access-list natout permit all ".$lannet." any");
			$isvalid = true;
		} elseif (($yn == "N") || ($yn == "n")) {
			$isvalid = true;
		}
	}
	
	// Allow pings from local network
	array_push($configfile, "icmp permit $lannet all eth1");
	
	// Allow remote administration from LAN
	array_push($configfile, "http server enable");
	array_push($configfile, "http $lannet");
	array_push($configfile, "ssh server enable");
	array_push($configfile, "ssh $lannet");
	array_push($configfile, "");

	file_put_contents("/etc/config/sysconfig", implode("\n",$configfile));
		
	print("\n\nSetup interview complete. Once the system has finished booting, you\n");
	print("can adjust your configuration from the following URL:\n\n");
	print("  https://".$lanip["ip"]."\n\n");
	print("The default login for the system is 'admin' with a password of 'fwadmin'.\n");
	print("This password should be changed immediately for both the admin and debug\n");
	print("users.\n\n");
		
	pause();
		
?>
