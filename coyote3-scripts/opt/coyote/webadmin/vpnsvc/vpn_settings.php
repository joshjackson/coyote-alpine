<?
	require_once("../includes/loadconfig.php");

	// Extract the vpnsvc addon configuration object
	$vpnconf =& $configfile->get_addon('VPNSVCAddon', $vpnconf);
	if ($vpnconf === false) {
		// WTF?
		header("location:/index.php");
		exit;
	}

	$MenuTitle="VPN Configuration";
	$MenuType="VPN";
	include("../includes/header.php");
?>

<font size="2">To configure Wolverine's VPN services, please make a selection from the VPN Configuration sub-menu.</font></p>
<p><strong>Wolverine's PPTP service includes the following features:</strong></p>
<ul>
  <li>128 bit MPPE encryption with MPPC compression</li>
  <li>MSCHAPv2 authentication</li>
  <li>Local and Radius user password verification <font color="#FF0000">**</font> </li>
  <li>Radius accounting support </li>
  <li>Radius assigned static IP addresses</li>
  <li>Radius authentication failover when multiple servers are used</li>
</ul>
<p><strong>Wolverine's IPSEC service includes the following features:</strong></p>
<ul>
  <li>Preshared key (PSK) and x.509 certificate authentication support</li>
  <li>DES, 3DES, Blowfish, AES128, and AES256 cipher support</li>
  <li>SHA1, SHA2, and MD5 digest support</li>
</ul>
<p><font color="#FF0000">**</font> Radius Authentication is not available for personal use licenses. </p>
<?
	include("../includes/footer.php");
?>