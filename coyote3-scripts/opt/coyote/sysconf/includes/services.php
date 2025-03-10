<?
// Script: services
// Purpose: starts and stops the various firewall services
//
// FIXME: This should be more modular. Each service should have its
//        own control routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

function StartSSHService ($Config) {

	if (file_exists("/var/run/dropbear.pid")) {
		return 0;
	}

	$ssh_opts = "-p ".$Config->ssh["port"];

	sudo_exec("/usr/sbin/sshd $ssh_opts 1> /dev/null 2> /dev/null");
}


function StopSSHService () {

	if (!file_exists("/var/run/dropbear.pid")) {
		return false;
	}

	$pid = GetServicePID("/var/run/dropbear.pid");

	if ($pid) {
		return posix_kill($pid, 15);
	} else {
		return false;
	}
}


function StartDynDNSService($Config) {
	if ($Config->dyndns["enable"]) {
		sudo_exec("/etc/ez-ipupdate.conf 1> /dev/null 2> /dev/null");
	}
}

function StopDynDNSService() {
	sudo_exec("killall -HUP ez-ipupdate");
}


function StartUPNPService ($Config) {

	$extnic = $Config->public_interface;
	$intnic = $Config->options["upnp"];

	if (!$extnic || !$intnic) {
		return 0;
	}

	sudo_exec("ip route add 239.0.0.0/8 dev $intnic");
    sudo_exec("iptables -A igd-input -i $intnic -d 239.0.0.0/8 -j accept-packet-local");
    sudo_exec("iptables -A igd-input -i $intnic -p udp --dport 1900 -j accept-packet-local");
    sudo_exec("iptables -A igd-input -i $intnic -p tcp --dport 2869 -j accept-packet-local");
    # Yuck... upnpd opens a dynamic port range starting at 49152.
    sudo_exec("iptables -A igd-input -i $intnic -p tcp --dport 49152:65535 -j accept-packet-local");
    sudo_exec("/usr/sbin/upnpd $extnic $intnic 1> /dev/null 2> /dev/null");

	return 0;
}

function StopUPNPService () {

	sudo_exec("killall -9 upnpd 1> /dev/null 2> /dev/null");
    sudo_exec("ip route del 239.0.0.0/8 1> /dev/null 2> /dev/null");
    sudo_exec("iptables -F igd-forward 1> /dev/null 2> /dev/null");
    sudo_exec("iptables -F igd-input 1> /dev/null 2> /dev/null");
    sudo_exec("iptables -t nat -F igd-preroute 1> /dev/null 2> /dev/null");

	return 0;
}

function StartDNSMasqService ($Config) {

	# Start the server
	# FIXME: Use openinitrd script service
	sudo_exec("/usr/sbin/dnsmasq 1> /dev/null 2> /dev/null");
}

function StopDNSMasqService () {

	if (!file_exists("/var/run/dnsmasq.pid")) {
		return 0;
	}

	$pid = GetServicePID("/var/run/dnsmasq.pid");
	//posix_kill($pid, 15);
	sudo_exec("kill ".$pid);
}

function StopSNMPService() {
	sudo_exec("killall snmpd 1> /dev/null 2> /dev/null");
}

function ShutdownFirewallServices($Config, $SkipWeb = false, $SkipSSH = false) {

	// Stop addon services
	foreach (array_keys($Config->addons) as $okey) {
		$obj = &$Config->addons[$okey];
		if (method_exists($obj, 'StopService')) {
			if (!($SkipWeb && $obj->IsWebService()))
				$obj->StopService();
		}
	}

	StopDNSMasqService();
	StopUPNPService();
	StopSNMPService();

	if (!$SkipSSH) {
		StopSSHService();
	}
}


?>
