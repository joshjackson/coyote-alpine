<?

// JJ: FIXME - Get rid of this crap - either an extension or internal calcs
function run_ipcalc($params) {


	$ipc=array(
		"BROADCAST" => "",
		"PREFIX" => "",
		"NETMASK" => "",
		"NETWORK" => "",
		"retcode" =>0
	);

	exec("ipcalc $params", $outstr, $errcode);

	for ($t=0; $t < count($outstr); $t++) {
		$tmpary = explode("=", $outstr[$t]);
		$ipc["$tmpary[0]"] = $tmpary[1];
	}

	$ipc["retcode"] = $errcode;

	return $ipc;
}

function get_hostname() {

	$procfile = fopen("/proc/sys/kernel/hostname", "r");
	$hostname = trim(fgets($procfile));
	fclose($procfile);
	return $hostname;
}


// Enables IP forwarding at the Linux kernel level
function enable_ip_forwarding() {
	write_proc_value("sys/net/ipv4/ip_forward", 1);
}

// Disables IP forwarding at the Linux kernel level
function disable_ip_forwarding() {
	write_proc_value("sys/net/ipv4/ip_forward", 0);
}


// Get default WAN address
function get_interface_ip($Config, $intf = 0) {

	global $DEBUG_MODE;

	if (!is_array($Config->interfaces[$intf]))
		return false;

	$iface = $Config->interfaces[$intf];

	// If we are using PPPoE, the interface should be PPP0, not eth0
	if (!is_array($iface["addresses"]) && ($iface["addresses"] == "pppoe")) {
		$ifname = "ppp0";
	} else {
		$ifname = $iface["device"];
	}

	if ($DEBUG_MODE) {
		do_print("Executing /bin/getifaddr for $ifname (idx $intf passed to function)\n");
		print_r($iface);
	}

	// JJ: FIXME - Get rid of this crap - should be moved to an extension
	exec("sudo /bin/getifaddr ".$ifname, $outstr, $errcode);

	if (!$errcode) {
		return $outstr[0];
	} else {
		return false;
	}
}

//-------------------------------------------------------------------------
// The following are IPv4 calculation and validation routines

// returns true if $ipaddr is a valid dotted IPv4 address
function is_ipaddr($ipaddr) {

	if (!is_string($ipaddr))
		return false;

	$ip_long = ip2long($ipaddr);
	$ip_reverse = long2ip($ip_long);

	return ($ipaddr == $ip_reverse) ? true : false;
}

// returns true if $subnet is a valid subnet in CIDR format
function is_subnet($subnet) {
	if (!is_string($subnet))
		return false;

	list($hp,$np) = explode('/', $subnet);

	if (!is_ipaddr($hp))
		return false;

	if (!is_numeric($np) || ($np < 1) || ($np > 32))
		return false;

	return true;
}

// returns true if $hostname is a valid hostname
function is_hostname($hostname) {
	if (!is_string($hostname))
		return false;

	if (preg_match("/^[a-z0-9\-]+$/i", $hostname))
		return true;
	else
		return false;
}

// returns true if $domain is a valid domain name
function is_domain($domain) {
	if (!is_string($domain))
		return false;

	if (preg_match("/^([a-z0-9\-]+\.?)*$/i", $domain))
		return true;
	else
		return false;
}

function is_numericint($arg) {
	return (preg_match("/[^0-9]/", $arg) ? false : true);
}

/* returns true if $port is a valid TCP/UDP port */
function is_port($port) {

	if (!is_numericint($port))
		return false;

	return (($port < 1) || ($port > 65535)) ? false : true;
}

// returns true if $macaddr is a valid MAC address
function is_macaddr($macaddr) {
	if (!is_string($macaddr))
		return false;

	$maca = explode(":", $macaddr);
	if (count($maca) != 6)
		return false;

	foreach ($maca as $macel) {
		if (($macel === "") || (strlen($macel) > 2))
			return false;
		if (preg_match("/[^0-9a-f]/i", $macel))
			return false;
	}

	return true;
}

// returns a subnet mask long (given a bit count)
function gen_subnet_mask_long($bits) {
	$sm = 0;
	for ($i = 0; $i < $bits; $i++) {
		$sm >>= 1;
		$sm |= 0x80000000;
	}
	return $sm;
}

// Returns a bit count for a subnet mask
function gen_mask_bit_count($mask) {

	if (!is_ipaddr($mask)) {
		return false;
	}

	$ml = ip2long($mask);
	$havezero = false;
	$bits = 0;
	for ($i = 31; $i >= 0; $i--) {
		$bc = $ml >> $i;
		if ($bc & 1) {
			if ($havezero) {
				return false;
			}
			$bits++;
		} else {
			$havezero = true;
		}
	}

	return $bits;
}

// same as above but returns a string
function gen_subnet_mask($bits) {
	return long2ip(gen_subnet_mask_long($bits));
}

// Return the subnet address given a host address and a subnet bit count
function gen_ip_subnet($ipaddr, $bits) {
	if (!is_ipaddr($ipaddr) || !is_numeric($bits))
		return "";

	return long2ip(ip2long($ipaddr) & gen_subnet_mask_long($bits));
}

// return the broadcast address in the subnet given a host address and a subnet bit count
function gen_ip_broadcast($ipaddr, $bits) {
	if (!is_ipaddr($ipaddr) || !is_numeric($bits))
		return "";

	return long2ip(ip2long($ipaddr) | ~gen_subnet_mask_long($bits));
}

// find out whether two subnets overlap
function check_subnets_overlap($subnet1, $bits1, $subnet2, $bits2) {

	if (!is_numeric($bits1))
		$bits1 = 32;
	if (!is_numeric($bits2))
		$bits2 = 32;

	if ($bits1 < $bits2)
		$relbits = $bits1;
	else
		$relbits = $bits2;

	$sn1 = gen_subnet_mask_long($relbits) & ip2long($subnet1);
	$sn2 = gen_subnet_mask_long($relbits) & ip2long($subnet2);

	if ($sn1 == $sn2)
		return true;
	else
		return false;
}

// compare two IP addresses
function ipcmp($a, $b) {
	if (ip2long($a) < ip2long($b))
		return -1;
	else if (ip2long($a) > ip2long($b))
		return 1;
	else
		return 0;
}

// return true if $addr is in $subnet, false if not
function ip_in_subnet($addr,$subnet) {
	list($ip, $mask) = explode('/', $subnet);
	$mask = 0xffffffff << (32 - $mask);
	return ((ip2long($addr) & $mask) == (ip2long($ip) & $mask));
}



?>
