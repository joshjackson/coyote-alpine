<?
	require_once("../includes/loadconfig.php");

	// Extract the vpnsvc addon configuration object
	$vpnconf =& $configfile->get_addon('VPNSVCAddon', $vpnconf);
	if ($vpnconf === false) {
		// WTF?
		header("location:/index.php");
		exit;
	}

	/*
	[name (validate as hostname)] => array(
		[localsub] => ip addr,
		[remotesub] => ip addr,
		[remotegw] => ip addr,
		[p1authtype] => set of (psk, cert),
		[p1cipher] => set of (sha,sha2,md5)-(3des, des, blowfish, aes128, aes256)
		(combine cipher with hash, drop hash from storage, concat each hash option to each cipher option)
		[p1psk] => string,
		[cert] => filenames, get with readdir from /etc/ipsec.d/*_cert.pem,
		[p2ciphers] => set of (sha1,sha2,md5)-(des, 3des, aes128, aes256, blowfish)
		(combine cipher with hash, drop hash from storage, concat each hash option to each cipher option)
		[pfs] => boolean

	)
	*/

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$MenuType="VPN";
	$MenuTitle="Edit IPSEC Tunnel";

	$fd_action=$_POST['action'];

	if($fd_action == 'apply') {
		//populate locals
		$fd_name = $_REQUEST['fd_name'];
		$fd_localsub = $_REQUEST['fd_localsub'];
		$fd_remotesub = $_REQUEST['fd_remotesub'];
		$fd_remotegw = $_REQUEST['fd_remotegw'];
		$fd_p1authtype = $_REQUEST['fd_p1authtype'];
		$fd_p1cipher = $_REQUEST['fd_p1cipher'];
		$fd_p1hash = $_REQUEST['fd_p1hash'];
		$fd_p1psk = $_REQUEST['fd_p1psk'];
		$fd_p1cert = $_REQUEST['fd_p1cert'];
		$fd_p1lifetime = $_REQUEST['fd_p1lifetime'];
		$fd_dhgroup = $_REQUEST['fd_dhgroup'];
		$fd_useMD5 = $_REQUEST['fd_useMD5'];
		$fd_useSHA1 = $_REQUEST['fd_useSHA1'];
		$fd_useDES = $_REQUEST['fd_useDES'];
		$fd_use3DES = $_REQUEST['fd_use3DES'];
		$fd_useAES128 = $_REQUEST['fd_useAES128'];
		$fd_useAES256 = $_REQUEST['fd_useAES256'];
		$fd_useBlowfish = $_REQUEST['fd_useBlowfish'];
		$fd_p2lifetime = $_REQUEST['fd_p2lifetime'];
		$p2h = array();
		$p2c = array();
		if ($fd_useMD5 == "checked")
			array_push($p2h, "hmac_md5");
		if ($fd_useSHA1 == "checked")
			array_push($p2h, "hmac_sha1");
		if ($fd_useDES == "checked")
			array_push($p2c, "des");
		if ($fd_use3DES == "checked")
			array_push($p2c, "3des");
		if ($fd_useAES128 == "checked")
			array_push($p2c, "aes128");
		if ($fd_useAES256 == "checked")
			array_push($p2c, "aes256");
		if ($fd_useBlowfish == "checked")
			array_push($p2c, "blowfish");

		$fd_pfs = $_REQUEST['fd_pfs'];

		if(!is_hostname($fd_name)) {
		  add_critical("Invalid hostname: ".$fd_name);
		}

		if(!is_ipaddrblockopt($fd_localsub)) {
		  add_critical("Invalid IP addr: ".$fd_localsub);
		}

		if(!is_ipaddrblockopt($fd_remotesub)) {
		  add_critical("Invalid IP addr: ".$fd_remotesub);
		}

		if(!is_ipaddrblockopt($fd_remotegw)) {
		  add_critical("Invalid IP addr: ".$fd_remotegw);
		}

		//FIXME: p1cert is a list filenames, validate?

		$MenuTitle="Add IPSEC Tunnel";

		//assign
		$tundef = array (
			"localsub" => $fd_localsub,
			"remotesub" => $fd_remotesub,
			"remotegw" => $fd_remotegw,
			"p1authtype" => $fd_p1authtype,
			"ike" => array(
				"cipher" => $fd_p1cipher,
				"hash" => $fd_p1hash,
				"lifetime" => $fd_p1lifetime,
				"dh-group" => $fd_dhgroup
			),
			"esp" => array(
				"cipher" => implode(",", $p2c),
				"hash" => implode(",", $p2h),
				"lifetime" => $fd_p2lifetime,
				"pfs-group" => $fd_pfs
			),
			"p1psk" => "",
			"cert" => "",
		);

		if  ($fd_p1authtype == "psk") {
			$tundef["p1psk"] = $fd_p1psk;
		} else {
			$tundef["cert"] = $fd_p1cert;
		}


		if(!query_invalid()){
			$configfile->ipsec["tunnels"][$fd_name] = $tundef;
			$configfile->dirty["ipsec"] = true;
			if(!WriteWorkingConfig())
				add_warning("Error writing to working configfile!");
			else {
				header("Location:./ipsecconf.php");
				exit;
			}
		} else {
			add_warning("<p>Wolverine encountered ".query_invalid()." errors.  No changes were made to your config.");
		}

	} else {
		//...parse out the p1cipher and p2cipher data from string into elements
		$fd_name=$_REQUEST["tunnel"];
		if ($fd_name && array_key_exists($fd_name, $configfile->ipsec["tunnels"])) {

			$tundef=$configfile->ipsec["tunnels"][$fd_name];

			$fd_localsub = $tundef["localsub"];
			$fd_remotesub = $tundef['remotesub'];
			$fd_remotegw = $tundef['remotegw'];
			$fd_p1authtype = $tundef['p1authtype'];
			$fd_p1psk = $tundef["p1psk"];
			$fd_p1cert = $tundef["cert"];

			if ($tundef["ike"]["dh-group"]) {
				$fd_dhgroup = $tundef["ike"]["dh-group"];
			}

			if ($tundef["esp"]["pfs-group"]) {
				$fd_pfs = $tundef["esp"]["pfs-group"];
			}
			
			$fd_p1lifetime = $tundef["ike"]["lifetime"];
			$fd_p2lifetime = $tundef["esp"]["lifetime"];
			
			$fd_p1cipher = $tundef["ike"]['cipher'];
			$fd_p1hash = $tundef["ike"]['hash'];
		
			$p2list = explode(",", $tundef["esp"]['cipher']);
			foreach($p2list as $c) {
				switch($c) {
					case "des":
						$fd_useDES = "checked";
						break;
					;;
					case "3des":
						$fd_use3DES = "checked";
						break;
					;;
					case "aes128":
						$fd_useAES128 = "checked";
						break;
					;;
					case "aes256":
						$fd_useAES256 = "checked";
						break;
					;;
					case "blowfish":
						$fd_useBlowfish = "checked";
						break;
					;;
				}
			}

			$p2list = explode(",", $tundef["esp"]['hash']);
			foreach($p2list as $c) {
				switch($c) {
					case "hmac_md5":
						$fd_useMD5 = "checked";
						break;
					;;
					case "hmac_sha1":
						$fd_useSHA1 = "checked";
						break;
					;;
				}
			}
		}
	}

	include("includes/header.php");
?>
<form id="content" method="post" action="<?=$_SERVER['PHP_SELF']?>">
		<input type="hidden" name="action" value="apply" />
		<table border="0" width="100%" id="table2">
			<tr>
				<td nowrap class="labelcellmid" colspan="2"><b><font size="2">
				Mobile Client IPSEC Settings</font></b></td>
			</tr>
			<tr>
				<td nowrap class="labelcell"><label>Enable Mobile Clients: </label></td>
				<td width="100%" class="ctrlcell"><input type="text" name="fd_name" size="30" value="<?=$fd_name?>"><br>
				<span class="descriptiontext">A text name for this tunnel. The name needs to be unique to this firewall and must be alpha-numeric and without any whitespace.<br>
				<em>Example: home-to-work </em>
				</span></td>
			</tr>
			<tr>
				<td nowrap class="labelcell"><label>Local subnet:</label></td>
				<td width="100%" class="ctrlcell"><input type="text" name="fd_localsub" size="20" value="<?=$fd_localsub?>"><br>
				<span class="descriptiontext">The subnet or IP address for the local side of the IPSEC tunnel.
				This address should be in address/prefix notation.<br>
				<i>Example: 192.168.0.0/24</i></span></td>
			</tr>
			<tr>
				<td nowrap class="labelcell"><label>Remote subnet:</label></td>
				<td width="100%" class="ctrlcell"><input type="text" name="fd_remotesub" size="20" value="<?=$fd_remotesub?>"><br>
				<span class="descriptiontext">The subnet or IP address for the remote side of the IPSEC
				tunnel. This address should be in address/prefix notation.<br>
				<i>Example: 192.168.1.0/24</i></span></td>
			</tr>
			<tr>
				<td nowrap class="labelcell"><label>Remote VPN Gateway:</label></td>
				<td width="100%"><input type="text" name="fd_remotegw" size="20" value="<?=$fd_remotegw?>"><br>
				<span class="descriptiontext">The IP address of the remote IPSEC tunnel endpoint.</span></td>
			</tr>
			<tr>
				<td nowrap colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td nowrap class="labelcell" colspan="2"><b><font size="2">Phase 1 Proposal (Authentication)</font></b></td>
			</tr>
	<tr>
		<td nowrap class="labelcell"><label>Authentication Type:</label></td>
		<td width="100%" class="ctrlcell">
			<select size="1" name="fd_p1authtype">
			<option selected value="psk" <? if($fd_p1authtype == "psk") print("selected"); ?>>Preshared Key</option>
			<option value="cert" <? if($fd_p1authtype == "cert") print("selected");?> >X.509 Certificates</option>
			</select>
		</td>
	</tr>
<?
	$p1c = array("3des", "des", "blowfish", "aes128", "aes256");
	$p1h = array("sha1", "md5");
	$dhk = array(array("Group 1 (768 bit)", 1), array("Group 2 (1024 bit)", 2), array("Group 5 (1536 bit)", 5));
	$pfs = array(array("Disabled", 0), array("Group 1 (768 bit)", 1), array("Group 2 (1024 bit)", 2), array("Group 5 (1536 bit)", 5));
?>

	<tr>
		<td nowrap class="labelcell"><label>Encryption Cipher:</label></td>
		<td class="ctrlcell" width="100%">
			<select size="1" name="fd_p1cipher">
			
			<? 	
				print("<!-- $fd_p1cipher $fd_p1hash -->\n\n");
				foreach($p1c as $cipher) {
					$s = ($cipher == $fd_p1cipher) ? "selected" : "";
					print('<option value="'.$cipher.'" '.$s.'>'.strtoupper($cipher).'</option>');
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Hash Algorithm:</label></td>
		<td class="ctrlcell" width="100%">
			<select size="1" name="fd_p1hash">
			<? 	
				foreach($p1h as $hash) {
					$s = ($hash == $fd_p1hash) ? "selected" : "";
					print('<option value="'.$hash.'" '.$s.'>'.strtoupper($hash).'</option>');
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
	  <td nowrap class="labelcell"><label>DH Key Group:</label> </td>
	  <td class="ctrlcell"><select name="fd_dhgroup">
	  <?
	  		foreach($dhk as $dhg) {
				$s = ($dhg[1] == $fd_dhgroup) ? "selected" : "";
		    	print('<option value="'.$dhg[1].'" '.$s.'>'.$dhg[0].'</option>');
			}
	  ?>
      </select>
      <br>
      <span class="descriptiontext">This setting must match the settings of the remote tunnel endpoint.</span> </td>
	  </tr>
	<tr>
	  <td nowrap class="labelcell"><label>Lifetime:</label></td>
	  <td class="ctrlcell"><input type="text" name="fd_p1lifetime" value="<?=$fd_p1lifetime?>">
		<br>
      <span class="descriptiontext">Key lifetime in seconds. This parameter is optional and can be left blank to use the default. </span></td>
	  </tr>
	<tr>
		<td nowrap class="labelcell"><label>Pre-shared Key</label></td>
		<td width="100%" class="ctrlcell">
			<input type="text" name="fd_p1psk" value="<?=$fd_p1psk?>" size="45"><br>
			<span class="descriptiontext">If preshared key authentication has been selected, you need to supply
			the encryption key (password) here. To ensure tunnel security, a key
			length of 128bits (16 characters) or longer is recommended. This key
			must be the same at both ends of the tunnel.</span>
		</td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Remote Certificate:</label></td>
		<td width="100%">
			<select size="1" name="fd_p1cert">
			<?
				$curdir = getcwd();
				if ($IN_DEVELOPMENT) {
					chdir("/home/webdev/sites/wolverine/files");
				} else {
					chdir("/etc/ipsec.d");
				}
				$hostcert = $configfile->hostname."_cert.pem";
				print("<!-- $hostcert -->\n");
				foreach(glob("*_cert.pem") as $certfile) {
					if ($certfile != $hostcert) {
						print('<option value="'.$certfile.'" '.(($certfile == $fd_p1cert) ? " selected" : "").'">'.$certfile.'</option>'."\n");
					}
				}
				chdir($curdir);
			?>
			</select>
			<br>
			<span class="descriptiontext">If you have selected X.509 certificate authentication, you need to
			select the certificate to be used for authentication of the remote
			tunnel endpoint.</span>
		</td>
	</tr>
	<tr>
		<td nowrap colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td nowrap class="labelcellmid" colspan="2"><b><font size="2">Phase 2 Proposal (SA/Key Exchange)</font></b></td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Encryption Ciphers:</label></td>
		<td width="100%" class="ctrlcell">
			<input type="checkbox" name="fd_useDES" value="checked" <?=$fd_useDES?>>DES<br>
			<input type="checkbox" name="fd_use3DES" value="checked" <?=$fd_use3DES?>>3DES<br>
			<input type="checkbox" name="fd_useAES128" value="checked" <?=$fd_useAES128?>>AES128<br>
			<input type="checkbox" name="fd_useAES256" value="checked" <?=$fd_useAES256?>>AES256<br>
			<input type="checkbox" name="fd_useBlowfish" value="checked" <?=$fd_useBlowfish?>>Blowfish<br>
			<br>
			<span class="descriptiontext">Note: The use of DES encryption is considered insecure and should only
			be used when it is the only choice offered by the remote VPN endpoint.</span><br>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Hash Algorithms:</label></td>
		<td width="100%" class="ctrlcell">
			<input type="checkbox" name="fd_useMD5" value="checked" <?=$fd_useMD5?>>MD5<br>
			<input type="checkbox" name="fd_useSHA1" value="checked" <?=$fd_useSHA1?>>SHA1<br>&nbsp;
		</td>
	</tr>
	<tr>
	  <td nowrap class="labelcell"><label>Lifetime:</label></td>
	  <td width="100%" class="ctrlcell"><input type="text" name="fd_p2lifetime" value="<?=$fd_p2lifetime?>">
      <br>
      <span class="descriptiontext">Key lifetime in seconds. This parameter is optional and can be left blank to use the default. </span></td>
	  </tr>
	<tr>
		<td nowrap class="labelcell"><label> PFS key group:</label></td>
		<td width="100%">
			<select name="fd_pfs" size="1">
	<?
	  		foreach($pfs as $pfsg) {
				$s = ($pfsg[1] == $fd_pfs) ? "selected" : "";
		    	print('<option value="'.$pfsg[1].'" '.$s.'>'.$pfsg[0].'</option>');
			}
	?>
			</select>			<br>
			<span class="descriptiontext">Perfect Forward Secrecy (PFS) should only be disabled if the remote
			endpoint does not support it or does not have it enabled.</span>
		</td>
	</tr>
</table>

</form>

<?
	print(query_warnings());
	include("../includes/footer.php");
?>
