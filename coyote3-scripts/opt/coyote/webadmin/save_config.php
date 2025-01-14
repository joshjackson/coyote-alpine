<?
	require_once("includes/loadconfig.php");
	require_once("runconfig.php");

	$action = $_POST["action"];

	if ($action == "apply") {
		$SaveOk = SaveSystemConfig();

		// $NeedReload = $configfile->dirty["interfaces"] | $configfile->dirty["routes"] | $configfile->dirty["proxyarp"];
		// if ($NeedReload) {
		// 	// Changes have been made that require the system be fully reloaded
		// 	ob_start();
		// 	ProcessFullConfig($configfile, true);
		// 	$outtext = ob_get_contents();
		// 	ob_end_clean();
		// } else {

		// 	if ($configfile->dirty["nat"]) {
		// 		$configfile->dirty["acls"] = true;
		// 	}

		// 	$NeedAcls = !$configfile->dirty["acls"];

		// 	// Since this page is called from the web admin, the addon should
		// 	// always be available
		// 	$httpconf =& $configfile->get_addon('WebAdminService');

		// 	// The VPN Services addon is only available on certain products
		// 	$vpnconf =& $configfile->get_addon('VPNSVCAddon');

		// 	if ($configfile->dirty["passwords"]) {
		// 		ConfigureUsers($configfile);
		// 	}

		// 	if ($configfile->dirty["dhcpd"]) {
		// 		// Reload the DHCPd service
		// 		StopDNSMasqService();
		// 		ConfigureDHCPD($configfile);
		// 		if (!$configfile->dirty["acls"])
		// 			ApplyDHCPDAcls($configfile, true);
		// 	}

		// 	if ($configfile->dirty["sshd"]) {
		// 		// Reload the SSH services
		// 		StopSSHService();
		// 		StartSSHService($configfile);
		// 	}

		// 	if ($httpconf->dirty["httpd"] && $NeedAcls) {
		// 		$httpconf->ApplyAcls(true);
		// 	}

		// 	if ($configfile->dirty["sshd"] && $NeedAcls) {
		// 		ApplyRemoteAdminAcls($configfile, true);
		// 	}

		// 	if ($configfile->dirty["snmpd"]) {
		// 		// Reload the snmp service
		// 		StopSNMPService();
		// 		ConfigureSNMP($configfile);
		// 		if ($NeedAcls)
		// 			ApplySNMPAcls($configfile, true);
		// 	}

		// 	// If we have a VPN Service addon available, check for VPN changes
		// 	if ($vpnconf !== false) {
		// 		if ($vpnconf->dirty["pptp"]) {
		// 			// Reload the PPTP config
		// 			$vpnconf->pptp_stop_service();
		// 			$vpnconf->pptp_build_config();
		// 			$vpnconf->pptp_start_service($NeedAcls);
		// 		} else {
		// 			if ($vpnconf->dirty["pptpusers"]) {
		// 				// Update only the PPTP users
		// 				$vpnconf->pptp_build_config();
		// 			}
		// 		}

		// 		if ($vpnconf->dirty["ipsec"]) {
		// 			$vpnconf->ipsec_build_config($configfile);
		// 			if ($vpnconf->ipsec["enable"]) {
		// 				// Calling the start service routing for IPSEC will simply
		// 				// reload it if already running - no need to stop it first
		// 				$vpnconf->ipsec_start_service($configfile, $NeedAcls);
		// 			} else {
		// 				$vpnconf->ipsec_stop_service();
		// 			}
		// 		}

		// 	}

		// 	if ($configfile->dirty["acls"]) {
		// 		// Reload the ACLs
		// 		InitFirewallRules($configfile);
		// 		ApplyAcls($configfile, false, true);
		// 		ConfigurePortForwards($configfile);
		// 		ConfigureAutoForwards($configfile);
		// 		ConfigureNAT($configfile);
		// 		// The kernel IP filters have been dumped. Reset the UPnP service
		// 		$configfile->dirty["upnp"] = true;
		// 		// We don't need to re-add the logging iptables rules if the logging
		// 		// service is scheduled to be reloaded anyway
		// 		if (!$configfile->dirty["logging"]) {
		// 			ApplyLoggingRules($configfile);
		// 		}
		// 		FinalizeACLConfig($configfile);
		// 	}

		// 	if ($configfile->dirty["logging"]) {
		// 		ConfigureLogging($configfile);
		// 	}

		// 	if ($configfile->dirty["upnp"]) {
		// 		StopUPNPService();
		// 		ConfigureUPNPD($configfile);
		// 	}

		// 	if ($configfile->dirty["qos"]) {
		// 		ConfigureQoS($configfile);
		// 	}

			// Update the running-config to match the current working config.
			$configfile->WriteConfigFile('/tmp/running-config');
//		}
	} else {
		//configure our buttonset for the bottom
		$buttoninfo[0] = array("label" => "Commit", "dest" => "javascript:do_submit()");
		$buttoninfo[1] = array("label" => "Cancel", "dest" => "index.php");
	}

	$MenuTitle="Apply Config";
	$MenuType="APPLY";
	include("includes/header.php");

?>


<form action="save_config.php" method="post">
	<input type="hidden" value="apply" name="action">
</form>
<table width="100%" id="table1">
	<tr>
		<td>
		&nbsp;<p align="center">&nbsp;</p>
		<p align="center">
		<br>
<?	if ($action) {
		if ($SaveOk) {
?>
		<b><font size="2">Working configuration successfully applied to the firewall and committed to storage.</font>
		<pre>
<?=$outtext?>
		</pre>
		</b>
		</p>
<?
		} else {
?>
		<b><font size="2" color="red">WARNING: Failed to commit working configuration to storage.
		</font>
		</p>
		</b>
<?
		}
	} else {
?>
		<b><font size="2">This option will apply the current working configuration to the firewall
		and commit it to storage.
		</font>
		</p>
		<p align="center"><font size="2">Would you like to perform this action?</font></b>
<?
	}
?>

		</td>
	</tr>
</table>

<?
	include("includes/footer.php");
?>

