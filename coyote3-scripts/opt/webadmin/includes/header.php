<?
	require_once($SitePath.'includes/webfunctions.php');
	require_once('product.php');
	
	$SiteTheme = GetSiteTheme();
?>
	

<!DOCTYPE html>
<HTML>
<HEAD>
<TITLE>Firewall Administration - <?=$MenuTitle?></TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<link href="/theme/<?=$SiteTheme?>/admin.css" rel="stylesheet" type="text/css">
</HEAD>

<?
	require_once($SitePath."language/language.php");

	$SiteMenu = array();

	// Home Page
	array_push($SiteMenu, NewMenuItem('NONE', $htHome, 'index.php', 'stats.jpg'));
	// Product Activation
	// array_push($SiteMenu, NewMenuItem('ACTIVATE', $htProdAct, 'activation.php', 'activate.jpg'));
	// System Options Menu
	$SystemMenu = NewMenuItem('SYSTEM', $htSystem, 'system.php', 'stats.jpg');
		AddSubMenuItem($SystemMenu, $htBackup, 'backup.php');
		AddSubMenuItem($SystemMenu, $htFirmware, 'sysupgrade.php');
	array_push($SiteMenu, $SystemMenu);
	// General Setting Menu
	$GeneralMenu = NewMenuItem('GENERAL', $htGeneral, 'general_settings.php', 'stats.jpg');
		AddSubMenuItem($GeneralMenu, $htPasswords, 'passwords.php');
		AddSubMenuItem($GeneralMenu, $htLogging, 'logging.php');
		AddSubMenuItem($GeneralMenu, $htDHCP, "dhcpd.php");
		AddSubMenuItem($GeneralMenu, $htSNMP, 'snmpd.php');
		AddSubMenuItem($GeneralMenu, $htRemoteAdmin, 'remoteconf.php');
		AddSubMenuItem($GeneralMenu, $htUPNP, "upnp.php");
		AddSubMenuItem($GeneralMenu, $htDDNS, "ddns.php");
	array_push($SiteMenu, $GeneralMenu);
	// Interface Menu
	$IntMenu = NewMenuItem('INTERFACES', $htInterfaces, 'interface_settings.php', 'stats.jpg');
		AddSubMenuItem($IntMenu, $htBridging, 'bridging.php');
	array_push($SiteMenu, $IntMenu);
	// Network Menu
	$NetMenu = NewMenuItem('NETWORK', $htNetwork, 'network_settings.php', 'stats.jpg');			
		AddSubMenuItem($NetMenu, $htRoutes, "routing.php");
		AddSubMenuItem($NetMenu, $htNAT, 'nat.php');
		AddSubMenuItem($NetMenu, "Traffic Shaping", 'edit_qos.php');
		AddSubMenuItem($NetMenu, $htPortForward, 'portforwards.php');
		AddSubMenuItem($NetMenu, $htProxyARP, "proxyarp.php");
	array_push($SiteMenu, $NetMenu);
	// Firewall Menu
	$FWMenu = NewMenuItem("RULES", "Firewall Rules", "firewall_rules.php", "securepage.jpg");
		AddSubMenuItem($FWMenu, "ICMP Control", "icmp_rules.php");
	array_push($SiteMenu, $FWMenu);
	// Stats Menu
	
	$StatMenu = NewMenuItem("STATS", "Firewall Statistics", "statistics.php", "stats.jpg");
		AddSubMenuItem($StatMenu, "Network Interfaces", "statistics.php?stats=interface");
		AddSubMenuItem($StatMenu, "Network Traffic", "statistics.php?stats=traffic");
		AddSubMenuItem($StatMenu, "System Log", "statistics.php?stats=syslog");
		AddSubMenuItem($StatMenu, "Running Processes", "statistics.php?stats=procs");
		AddSubMenuItem($StatMenu, "Routing Tables", "statistics.php?stats=routes");
		AddSubMenuItem($StatMenu, "DHCP Leases", "statistics.php?stats=leases");
		//DrawSubMenuCell("Connection Tracking", "statistics.php?stats=conntrack");
		//AddSubMenuItem($StatMenu, "IPSEC Information", "statistics.php?stats=ipsec");
	array_push($SiteMenu, $StatMenu);

	if (file_exists("/tmp/working-config")) {
		$ApplyMenu = NewMenuItem("APPLY", "Apply Configuration", 'save_config.php', 'reload.jpg');
		$ApplyMenu["Style"] = 'apply';					
		array_push($SiteMenu, $ApplyMenu);
	}

	// Include any addon scripts to build the menu items
	$AddonScripts = glob('/etc/sysconf/addons/*-www.php');
	if (is_array($AddonScripts)) {
		foreach($AddonScripts as $Script) {
			include($Script);
		}
	}			
	// Reboot menu item
	array_push($SiteMenu, NewMenuItem("REBOOT", "Reboot Firewall", 'reboot.php', 'reboot.jpg'));
	// Debug Menu
	if ($_SERVER["REMOTE_USER"] == "debug") {
		$DebugMenu = NewMenuItem("DEBUG", "Debug Menu", "debug.php", 'stats.jpg', true);
			AddSubMenuItem($DebugMenu, "Object Dump", "debug.php?dump=object");
			AddSubMenuItem($DebugMenu, "Saved Config", "debug.php?dump=working");
			AddSubMenuItem($DebugMenu, "IPTables", "debug.php?dump=iptables");
		if (file_exists("/tmp/working-config")) {
			AddSubMenuItem($DebugMenu, "Reset Configuration", "debug.php?dump=reset");
		}
		array_push($SiteMenu, $DebugMenu);
	}

	if (!$PageIcon) {
		if (!$MenuType) {
			$PageIcon = "stats.jpg";
		} else {
			$PageIcon = GetPageIcon($SiteMenu, $MenuType);
		}
	}
?>

<BODY>

<TABLE class="maintable" BORDER=0 CELLPADDING=0 CELLSPACING=0>
  <TR> 
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_01.gif" WIDTH=172 HEIGHT=140></TD>
    <TD class="td-content" background="/theme/<?=$SiteTheme?>/wolf-back-actual_02.gif" HEIGHT=140>
	<table border="0" width="100%" id="table2" cellspacing="0" cellpadding="0">
		<tr>
			<td height="60px" valign="top" colspan="3" nowrap><h1><?=$MenuTitle?></h1>
			</td>
		</tr>
		<tr>
			<td height="35px" colspan="3"><img class="wolvbanner" src="/theme/<?=$SiteTheme?>/wolf-banner.gif" height=27></td>
		</tr>
		<tr>
			<td height="40px" valign="bottom" width="35">
			<img border="0" src="/icons/<?=$PageIcon?>" width="32" height="32"></td>
			<td height="40px" width="15px">&nbsp;
			</td>
			<td height="40px" width="100%" align="left">
			<span class="bannertext"><?=$MenuTitle?></span></td>
		</tr>
	</table>    
    </TD>
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_03.gif" HEIGHT=140 ></TD>
  </TR>
  <TR> 
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_04.gif" WIDTH=172 HEIGHT=174 valign="top">
    <table valign="top">
		<?
			RenderMenus($SiteMenu, $MenuType);
		?>    
      </table>
    </TD>
    <TD class="td-content" bgcolor="FFFFFF">
