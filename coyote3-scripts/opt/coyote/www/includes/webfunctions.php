<?

function drawbar_colour ($colour, $percent) {
	// This function takes the integer (from 0-100) $percent as an argument
	// to build a bargraph using small images. It doesn't depend on the gd lib.
	// safety: normalise $percent if it's not between 0 and 100.

	$percent = max(0, min(100, $percent));
	if ($percent >= 90) {
		$l_colour = "red";
	} elseif ($percent >= 70) {
		$l_colour = "yellow";
	} else {
		$l_colour = ($percent == 0   ? "grey" : "green");
	}
	$r_colour = ($percent == 100 ? "red" : "grey");
	// This is a hack to avoid bad browser behaviour from using <img width=0>
	if ($percent == 0  ) { $percent = 1; }
	if ($percent == 100) { $percent = 99; }
	echo "<img src=images/bars/bar-left-$l_colour.gif border=0 height=12 width=5>";
	echo "<img src=images/bars/bar-tile-$l_colour.gif border=0 height=12 width=", ($percent*2), ">";
	echo "<img src=images/bars/bar-tile-$r_colour.gif border=0 height=12 width=", (200 - $percent*2), ">";
	echo "<img src=images/bars/bar-right-$r_colour.gif border=0 height=12 width=5>\n";
}

function GetTimezoneList($Default="UTC") {

	$VALID_TS="CST,EDT,EST,MST,PST,UTC,GMT-12,GMT-11,GMT-10,GMT-9,GMT-8,GMT-7,GMT-6,GMT-5,GMT-4,GMT-3,".
		"GMT-2,GMT-1,GMT,GMT+1,GMT+2,GMT+3,GMT+4,GMT+5,GMT+6,GMT+7,GMT+8,GMT+9,GMT+10,GMT+11,GMT+12";

	$tsarray=explode(",",$VALID_TS);

	$retstr="";
	foreach($tsarray as $ts) {
		if ("$ts" == "$Default") {
			$Selected = " Selected";
		} else {
			$Selected = "";
		}
		$retstr.="<option value=\"$ts\" $Selected>$ts</option>";
	}
	return $retstr;

}

function GetICMPList($Default="echo-request") {

	$ICMP_LIST = "echo-reply,destination-unreachable,network-unreachable,host-unreachable,protocol-unreachable,port-unreachable,".
		"fragmentation-needed,source-route-failed,network-unknown,host-unknown,network-prohibited,host-prohibited,TOS-network-unreachable,".
		"TOS-host-unreachable,communication-prohibited,host-precedence-violation,precedence-cutoff,source-quench,redirect,network-redirect,".
		"host-redirect,TOS-network-redirect,TOS-host-redirect,echo-request,router-advertisement,router-solicitation,time-exceeded,".
		"ttl-zero-during-transit,ttl-zero-during-reassembly,parameter-problem,ip-header-bad,required-option-missing,timestamp-request,".
		"timestamp-reply,address-mask-request,address-mask-reply";

	$icmparray=explode(",",$ICMP_LIST);
	sort($icmparray);

	$retstr="";
	foreach($icmparray as $icmpstr) {
		if ($icmpstr === $Default) {
			$Selected = " Selected";
		} else {
			$Selected = "";
		}
		$retstr.="<option value=\"$icmpstr\" $Selected>$icmpstr</option>";
	}
	return $retstr;
}



function GetProtocolList($Default="tcp") {

	$PROTO_LIST="tcp,udp,icmp,esp,ah,gre,ipip,sctp,l2tp,vrrp,bgp,ospf,eigrp,ipv6,ipv6-route,all";

	$protoarray=explode(",",$PROTO_LIST);

	$retstr="";
	foreach($protoarray as $proto) {
		if ($proto === $Default) {
			$Selected = " Selected";
		} else {
			$Selected = "";
		}
		$retstr.="<option value=\"$proto\" $Selected>$proto</option>";
	}
	return $retstr;

}

function add_warning($msg) {
   global $fd_warnings;
   $fd_warnings .= $msg."<br>";
}

function add_critical($msg) {
  global $fd_invalid, $fd_warnings;
  add_warning($msg);
  $fd_invalid++;
}

function query_invalid() {
  global $fd_invalid;
  return $fd_invalid;
}

function query_warnings() {
  global $fd_warnings;
  return $fd_warnings;
}

function GetDHCPLeases() {
	
	$ret = array();
	
	if (file_exists('/var/lib/dnsmasq.leases')) {
		$lf = file("/var/lib/dnsmasq.leases");
		foreach($lf as $line) {
			$lease = explode(' ', $line);
			$lease_ent = array(
				"IP" => $lease[2],
				"MAC" => $lease[1],
				"Host" => $lease[3],
				"Expires" => date("M j, Y, g:i a", $lease[0])
			);
			array_push($ret, $lease_ent);
		}
	}
	
	return $ret;
}

function DrawMenuCell($Desc, $URL) {
	print("<tr>\n");
    print('<td nowrap width=400px class="menucell"><a class="menulink" href="/'.$URL.'">'.$Desc."</a></td>\n");
    print("</tr>\n");
}

function DrawApplyMenuCell($Desc, $URL) {
	print("<tr>\n");
    print('<td nowrap width=400px class="applymenucell"><a class="applymenulink" href="/'.$URL.'">'.$Desc."</a></td>\n");
    print("</tr>\n");
}

function DrawSubMenuCell($Desc, $URL) {
	print("<tr>\n");
    print('<td nowrap class="submenucell"><a class="menulink" href="/'.$URL.'">'.$Desc."</a></td>\n");
    print("</tr>\n");
}

function NewMenuItem($MenuType, $Desc, $URL, $PageIcon, $ShowSub = false) {

	$NewMenu = Array();
	$NewMenu['MenuType'] = $MenuType;
	$NewMenu['Desc'] = $Desc;
	$NewMenu['URL'] = $URL;
	$NewMenu['PageIcon'] = $PageIcon;
	$NewMenu['AlwaysShowSub'] = $ShowSub;
	$NewMenu['Style'] = 'normal';
	$NewMenu['SubMenu'] = array();

	return $NewMenu;
}

function AddSubMenuItem(&$Menu, $Desc, $URL) {

	// Sanity tests	
	if (!is_array($Menu)) {
		$Menu = array();
	}
	if (!is_array($Menu['SubMenu'])) {
		$Menu['SubMenu'] = array();
	}
	
	$NewItem = array();
	$NewItem['Desc'] = $Desc;
	$NewItem['URL'] = $URL;
	
	array_push($Menu['SubMenu'], $NewItem);	
}

function RenderMenus($MenuDef, $MenuType) {
	
	foreach($MenuDef as $MenuItem) {
		// Render Main Menu item
		if ($MenuItem['Style'] == 'apply') {
			DrawApplyMenuCell($MenuItem['Desc'], $MenuItem['URL']);
		} else {
			DrawMenuCell($MenuItem['Desc'], $MenuItem['URL']);
		}
		
		if (($MenuType == $MenuItem['MenuType']) || $MenuItem['AlwaysShowSub']) {
			// Render the submenu
			foreach($MenuItem['SubMenu'] as $SubItem) {
				DrawSubMenuCell($SubItem['Desc'], $SubItem['URL']);
			}
		}
	}
}

function GetPageIcon($MenuDef, $MenuType) {
	foreach($MenuDef as $MenuItem) {
		if ($MenuItem["MenuType"] == $MenuType)
			return $MenuItem["PageIcon"];
	}
	return "stats.jpg";
}

function GetSiteTheme() {
	// This webadmin app will never be used for anything other than Coyote
	// going forward. 
	return "coyote";
}

?>
