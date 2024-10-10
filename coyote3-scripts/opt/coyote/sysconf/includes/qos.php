<?
define("QOS_PRIO_LAN", 5);
define("QOS_PRIO_HIGH", 10);
define("QOS_PRIO_NORMAL", 20);
define("QOS_PRIO_LOW", 30);

	function get_qos_output_prio($prio) {
		$ret = "low";
		switch ($prio) {
			case QOS_PRIO_LAN:
				$ret = "lan";
				break;
			case QOS_PRIO_LOW:
				$ret = "low";
				break;
			case QOS_PRIO_NORMAL:
				$ret = "normal";
				break;
			case QOS_PRIO_HIGH:
				$ret = "high";
				break;
		}
		return $ret;
	}


	function get_qos_prio($priostr, $quiet = false) {
		$ret = QOS_PRIO_LOW;
		switch($priostr) {
			case "lan":
				$ret = QOS_PRIO_LAN;
				break;
			case "low":
				$ret = QOS_PRIO_LOW;
				break;
			case "normal":
				$ret = QOS_PRIO_NORMAL;
				break;
			case "high":
				$ret = QOS_PRIO_HIGH;
				break;
			default:
				if (!$quiet)
					do_print("Invalid QoS priority: $priostr - Defaulting to low priority class.");
		}
		return $ret;
	}

	function qos_clear_settings($Config) {
		sudo_exec("iptables -F -t mangle");
		sudo_exec("iptables -F coyote-tc-up -t mangle 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -F coyote-tc-down -t mangle 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -X coyote-tc-up -t mangle 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -X coyote-tc-down -t mangle 1> /dev/null 2> /dev/null");
		sudo_exec("tc qdisc del root dev ".$Config->public_interface. "1> /dev/null 2> /dev/null");
		sudo_exec("tc qdisc del root dev eth1 1> /dev/null 2> /dev/null");
	}


	function ConfigureQoS($Config) {
		// Clear any existing settings
		qos_clear_settings($Config);
		// Set up the QoS subsystem and attach any available filters
		if ($Config->qos["enable"]) {
			StartQoS($Config);
		}
	}

	function StartQoS($Config) {
		// Determine if we should be using a PPP adapter for public interface
		$pi = $Config->public_interface;
		sudo_exec("iptables -t mangle -N coyote-tc-up 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -t mangle -N coyote-tc-down 1> /dev/null 2> /dev/null");
		// Do not subject LAN to LAN traffic to QoS
		sudo_exec("iptables -A FORWARD -t mangle ! -i $pi ! -o $pi -j MARK --set-mark ".QOS_PRIO_LAN." 1> /dev/null 2> /dev/null");


		sudo_exec("iptables -A FORWARD -t mangle -i $pi -j coyote-tc-down 1> /dev/null 2> /dev/null");
		sudo_exec("iptables -A FORWARD -t mangle -o $pi -j coyote-tc-up 1> /dev/null 2> /dev/null");

		// Calculate our thresholds
		$up_max = ceil($Config->qos["upstream"] * .98)."kbit";
		$up_high = ceil($up_max * .90)."kbit";
		$up_norm = ceil($up_max * .50)."kbit";
		$up_ceil = $up_high;  // normal and low prio classes never go above 90%
		$up_low = "1kbit";

		$dl_max = ceil($Config->qos["downstream"] * .98)."kbit";
		$dl_high = ceil($dl_max * .90)."kbit";
		$dl_norm = ceil($dl_max * .50)."kbit";
		$dl_ceil = $dl_high;  // normal and low prio classes never go above 90%
		$dl_low = "1kbit";

		$li = "eth1";

		$dp = ($Config->qos["default-prio"]) ? $Config->qos["default-prio"] : QOS_PRIO_LOW;

		// Set up the public interface
		sudo_exec("tc qdisc add dev $pi root handle 1: htb default $dp");
		sudo_exec("tc class add dev $pi parent 1: classid 1:1 htb rate $up_max burst 15k");
		sudo_exec("tc class add dev $pi parent 1:1 classid 1:10 htb rate $up_high ceil $up_max burst 15k");
		sudo_exec("tc class add dev $pi parent 1:1 classid 1:20 htb rate $up_norm ceil $up_ceil burst 15k");
		sudo_exec("tc class add dev $pi parent 1:1 classid 1:30 htb rate $up_low ceil $up_ceil burst 5k");
		sudo_exec("tc qdisc add dev $pi parent 1:10 handle 10: sfq perturb 10");
		sudo_exec("tc qdisc add dev $pi parent 1:20 handle 20: sfq perturb 10");
		sudo_exec("tc qdisc add dev $pi parent 1:30 handle 30: sfq perturb 10");
		sudo_exec("tc filter add dev $pi parent 1:0 protocol ip prio 1 handle 10 fw flowid 1:10");
		sudo_exec("tc filter add dev $pi parent 1:0 protocol ip prio 1 handle 20 fw flowid 1:20");
		sudo_exec("tc filter add dev $pi parent 1:0 protocol ip prio 1 handle 30 fw flowid 1:30");

		// Set up the internal interface
		sudo_exec("tc qdisc add dev $li root handle 1: htb default $dp");
		sudo_exec("tc class add dev $li parent 1: classid 1:1 htb rate $dl_max burst 15k");
		sudo_exec("tc class add dev $li parent 1:1 classid 1:10 htb rate $dl_high ceil $dl_max burst 15k");
		sudo_exec("tc class add dev $li parent 1:1 classid 1:20 htb rate $dl_norm ceil $dl_ceil burst 15k");
		sudo_exec("tc class add dev $li parent 1:1 classid 1:30 htb rate $dl_low ceil $dl_ceil burst 5k");
		sudo_exec("tc qdisc add dev $li parent 1:10 handle 10: sfq perturb 10");
		sudo_exec("tc qdisc add dev $li parent 1:20 handle 20: sfq perturb 10");
		sudo_exec("tc qdisc add dev $li parent 1:30 handle 30: sfq perturb 10");
		sudo_exec("tc filter add dev $li parent 1:0 protocol ip prio 1 handle 5 fw flowid 1:1");
		sudo_exec("tc filter add dev $li parent 1:0 protocol ip prio 1 handle 10 fw flowid 1:10");
		sudo_exec("tc filter add dev $li parent 1:0 protocol ip prio 1 handle 20 fw flowid 1:20");
		sudo_exec("tc filter add dev $li parent 1:0 protocol ip prio 1 handle 30 fw flowid 1:30");

		// Install filters
		foreach ($Config->qos["filters"] as $qf) {
		  if ($qf["interface"] == $pi) {
				$fc = "coyote-tc-up";
			} else {
				$fc = "coyote-tc-down";
			}
			$cmd = "iptables -t mangle -A $fc";
			if ($qf["proto"] != "all") {
				$cmd .= " -p ".$qf["proto"];
				if (($qf["proto"] == "tcp") || ($qf["proto"] == "udp")) {
					if ($qf["ports"]) {
						$cmd .= " --dport ".$qf["ports"];
					}
				}
			}
			$cmd .= " -j MARK --set-mark ".$qf["prio"];
			sudo_exec($cmd);
		}
	}

	function StopQoS($Config) {
		qos_clear_settings($Config);
	}
?>