<?

// Add the additional menu items
if (is_array($SiteMenu)) {
	$NewMenu = NewMenuItem('VPN', 'VPN Configuration', 'vpnsvc/vpn_settings.php', 'security.jpg', false);
	AddSubMenuItem($NewMenu, 'PPTP Configuration', 'vpnsvc/pptpdconf.php');
	AddSubMenuItem($NewMenu, 'User Authentication', 'vpnsvc/vpnauth.php');
	AddSubMenuItem($NewMenu, 'IPSEC Configuration', 'vpnsvc/ipsecconf.php');	
	//DrawSubMenuCell("Mobile IPSEC", "mobileipsec.php");
	array_push($SiteMenu, $NewMenu);
}

?>