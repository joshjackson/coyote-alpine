<?
	include("../includes/loadconfig.php");

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

	function cleanArray($arr) {
		$ret = array();
		if (!empty($arr) && is_array($arr)) {
			foreach($arr as $option) {
				$val = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
				array_push($ret, $val); 
			}
		}
		return $ret;
	}

	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		//populate locals
		$fd_name = filter_input(INPUT_POST, 'fd_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_localsub = filter_input(INPUT_POST, 'fd_localsub', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_remotesub = filter_input(INPUT_POST, 'fd_remotesub', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_remotegw = filter_input(INPUT_POST, 'fd_remotegw', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_p1authtype = filter_input(INPUT_POST, 'fd_p1authtype', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		
		$p1c = $_POST['fd_p1cipher'] ?? [];
		$p1h = $_POST['fd_p1hash'] ?? [];
		$p1g = $_POST['fd_p1group'] ?? [];

		$fd_p1cipher = cleanArray($p1c);
		$fd_p1hash = cleanArray($p1h);
		$fd_p1group = cleanArray($p1g);

		$fd_p1psk = filter_input(INPUT_POST, 'fd_p1psk', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_p1cert = filter_input(INPUT_POST, 'fd_p1cert', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_p1lifetime = filter_input(INPUT_POST, 'fd_p1lifetime', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		
		$p2c = $_POST['fd_p2cipher'] ?? [];
		$p2h = $_POST['fd_p2hash'] ?? [];
		$p2g = $_POST['fd_p2group'] ?? [];
		
		$fd_p2cipher = cleanArray($p2c);
		$fd_p2hash = cleanArray($p2h);
		$fd_p2group = cleanArray($p2g);
		
		$fd_dhgroup = filter_input(INPUT_POST, 'fd_dhgroup', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_p2lifetime = filter_input(INPUT_POST, 'fd_p2lifetime', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fd_pfs = filter_input(INPUT_POST, 'fd_pfs', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

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
		// print("<pre>");
		// print_r($fd_p1cipher);
		// print_r($fd_p1hash);
		// print_r($fd_p1group);
		// print_r($fd_p2cipher);
		// print_r($fd_p2hash);
		// print_r($fd_p2group);
		// print("</pre>");
		// die;

		$tundef = array (
			"localsub" => $fd_localsub,
			"remotesub" => $fd_remotesub,
			"remotegw" => $fd_remotegw,
			"p1authtype" => $fd_p1authtype,
			"ike" => array(
				"cipher" => implode(",", $fd_p1cipher),
				"hash" => implode(",", $fd_p1hash),
				"lifetime" => $fd_p1lifetime,
				"dh-group" => implode(",", $fd_p1group)
			),
			"esp" => array(
				"cipher" => implode(",", $fd_p2cipher),
				"hash" => implode(",", $fd_p2hash),
				"lifetime" => $fd_p2lifetime,
				"pfs-group" => implode(",", $fd_p2group)
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
			$vpnconf->ipsec["tunnels"][$fd_name] = $tundef;
			$vpnconf->dirty["ipsec"] = true;
			if(!WriteWorkingConfig())
				add_warning("Error writing to working configfile!");
			else {
				header("Location:./ipsecconf.php");
				exit;
			}
		} else {
			add_warning("<p>Encountered ".query_invalid()." errors.  No changes were made to your config.");
		}

	} else {
		$fd_name=$_REQUEST["tunnel"];
		if ($fd_name && array_key_exists($fd_name, $vpnconf->ipsec["tunnels"])) {

			$tundef=$vpnconf->ipsec["tunnels"][$fd_name];

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
		
			$fd_p2cipher = explode(",", $tundef["esp"]['cipher']);
			$fd_p2hash = explode(",", $tundef["esp"]['hash']);
		}
	}

	include("../includes/header.php");
?>
<form id="content" method="post" action="<?=$_SERVER['PHP_SELF']?>">
		<input type="hidden" name="action" value="apply" />
		<table border="0" width="100%" id="table2">
			<tr>
				<td nowrap class="labelcellmid" colspan="2"><b><font size="2">
				General Tunnel Settings</font></b></td>
			</tr>
			<tr>
				<td nowrap class="labelcell"><label>VPN Tunnel Name:</label></td>
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

	function setSelected($options, $arr) {
		if (!empty($arr) && is_array($arr)) {
			foreach(array_keys($options) as $optkey) {
				$options[$optkey][1] = (in_array($optkey, $arr)) ? 1 : 0;
			}
		}
		return $options;
	}

	$p1c = array();
	$p1c["chacha20poly1305"] = array("256 bit ChaCha20-Poly1305/128 bit ICV", 1);
	$p1c["aes256gcm128"] = array("256 bit AES-GCM/128 bit ICV", 1);
	$p1c["aes256gcm96"] = array("256 bit AES-GCM/96 bit ICV", 1);
	$p1c["aes256gcm64"] = array("256 bit AES-GCM/64 bit ICV", 1);
	$p1c["aes256"] = array("256 bit AES-CBC", 1);
	$p1c["camellia256"] = array("256 bit Camellia-CBC", 0);
	$p1c["aes192gcm128"] = array("192 bit AES-GCM/128 bit ICV", 1);
	$p1c["aes192gcm96"] = array("192 bit AES-GCM/96 bit ICV", 1);
	$p1c["aes192gcm64"] = array("192 bit AES-GCM/64 bit ICV",10);
	$p1c["aes192"] = array("192 bit AES-CBC", 1);
	$p1c["camellia192"] = array("192 bit Camellia-CBC", 0);
	$p1c["aes128gcm128"] = array("128 bit AES-GCM/128 bit ICV", 1);
	$p1c["aes128gcm96"] = array("128 bit AES-GCM/96 bit ICV", 1);
	$p1c["aes128gcm64"] = array("128 bit AES-GCM/64 bit ICV", 1);
	$p1c["aes128"] = array("128 bit AES-CBC", 1);
	$p1c["camellia128"] = array("128 bit Camellia-CBC", 0);
	$p1c["3des"] = array("168 bit 3DES-EDE-CBC (Weak)", 0);
	$p1c = setSelected($p1c, $fd_p1cipher);
	
	$p1h = array();
	$p1h["sha2_512"] = array("SHA2 512 bit", 1);
	$p1h["sha2_384"] = array("SHA2 384 bit", 0);
	$p1h["sha2_256"] = array("SHA2 256 bit", 1);
	$p1h["aesxcbc"] = array("AES XCBC", 0);
	$p1h["sha"] = array("SHA1 (Weak)", 0);
	$p1h["md5"] = array("MD5 (Broken)", 0);
	$p1h = setSelected($p1h, $fd_p1hash);

	$p1g = array();
	$p1g["curve448"] = array("Curve 448 (224 bit)", 1);
	$p1g["curve25519"] = array("Curve 25519 (128 bit)", 1);
	$p1g["e521"] = array("ECP-521 (NIST)", 1);
	$p1g["e512bp"] = array("ECP-512 (Brainpool)", 0);
	$p1g["e384"] = array("ECP-384 (NIST)", 1);
	$p1g["e384bp"] = array("ECP-384 (Brainpool)", 0);
	$p1g["e256"] = array("ECP-256 (NIST)", 0);
	$p1g["e256bp"] = array("ECP-256 (Brainpool)", 0);
	$p1g["e224"] = array("ECP-224 (NIST)", 0);
	$p1g["e224bp"] = array("ECP-224 (Brainpool)", 0);
	$p1g["e192"] = array("ECP-192 (NIST)", 0);
	$p1g["8192"] = array("MODP-8192", 0);
	$p1g["6144"] = array("MODP-6144", 0);
	$p1g["4096"] = array("MODP-4096", 1);
	$p1g["3072"] = array("MODP-3072", 1);
	$p1g["2048"] = array("MODP-2048 (Weak)", 0);
	$p1g["1536"] = array("MODP-1536 (Broken)", 0);
	$p1g["1024"] = array("MODP-1024 (Broken)", 0);
	$p1g["768"] = array("MODP-768 (Broken)", 0);
	$p1g = setSelected($p1g, $fd_p1group);

	$p2c = array();
	$p2c["chacha20poly1305"] = array("256 bit ChaCha20-Poly1305/128 bit ICV", 1);
	$p2c["aes256gcm128"] = array("256 bit AES-GCM/128 bit ICV", 1);
	$p2c["aes256gcm96"] = array("256 bit AES-GCM/96 bit ICV", 1);
	$p2c["aes256gcm64"] = array("256 bit AES-GCM/64 bit ICV", 1);
	$p2c["aes256"] = array("256 bit AES-CBC", 1);
	$p2c["camellia256"] = array("256 bit Camellia-CBC", 0);
	$p2c["aes192gcm128"] = array("192 bit AES-GCM/128 bit ICV", 1);
	$p2c["aes192gcm96"] = array("192 bit AES-GCM/96 bit ICV", 1);
	$p2c["aes192gcm64"] = array("192 bit AES-GCM/64 bit ICV", 1);
	$p2c["aes192"] = array("192 bit AES-CBC", 1);
	$p2c["camellia192"] = array("192 bit Camellia-CBC", 0);
	$p2c["aes128gcm128"] = array("128 bit AES-GCM/128 bit ICV", 1);
	$p2c["aes128gcm96"] = array("128 bit AES-GCM/96 bit ICV", 1);
	$p2c["aes128gcm64"] = array("128 bit AES-GCM/64 bit ICV", 1);
	$p2c["aes128"] = array("128 bit AES-CBC", 1);
	$p2c["camellia128"] = array("128 bit Camellia-CBC", 0);
	$p2c["3des"] = array("168 bit 3DES-EDE-CBC (Weak)", 0);
	$p2c = setSelected($p2c, $fd_p2cipher);

	$p2h = array();
	$p2h["sha2_512"] = array("SHA2 512 bit", 1);
	$p2h["sha2_384"] = array("SHA2 384 bit", 0);
	$p2h["sha2_256"] = array("SHA2 256 bit", 1);
	$p2h["aesxcbc"] = array("AES XCBC", 0);
	$p2h["sha1"] = array("SHA1 (Weak)", 0);
	$p2h["md5"] = array("MD5 (Broken)", 0);
	$p2h = setSelected($p2h, $fd_p2hash);

	$p2g = array();
	$p2g["curve448"] = array("Curve 448 (224 bit)", 1);
	$p2g["curve25519"] = array("Curve 25519 (128 bit)", 1);
	$p2g["e521"] = array("ECP-521 (NIST)", 1);
	$p2g["e512bp"] = array("ECP-512 (Brainpool)", 0);
	$p2g["e384"] = array("ECP-384 (NIST)", 1);
	$p2g["e384bp"] = array("ECP-384 (Brainpool)", 0);
	$p2g["e256"] = array("ECP-256 (NIST)", 1);
	$p2g["e256bp"] = array("ECP-256 (Brainpool)", 0);
	$p2g["e224"] = array("ECP-224 (NIST)", 0);
	$p2g["e224bp"] = array("ECP-224 (Brainpool)", 0);
	$p2g["e192"] = array("ECP-192 (NIST)", 0);
	$p2g["8192"] = array("MODP-8192", 0);
	$p2g["6144"] = array("MODP-6144", 0);
	$p2g["4096"] = array("MODP-4096", 1);
	$p2g["3072"] = array("MODP-3072", 1);
	$p2g["2048"] = array("MODP-2048 (Weak)", 0);
	$p2g["1536"] = array("MODP-1536 (Broken)", 0);
	$p2g["1024"] = array("MODP-1024 (Broken)", 0);
	$p2g["768"] = array("MODP-768 (Broken)", 0);
	$p2g = setSelected($p2g, $fd_p2group);

	//$p1h = array("sha1", "md5");
	$dhk = array(array("Group 1 (768 bit)", 1), array("Group 2 (1024 bit)", 2), array("Group 5 (1536 bit)", 5));
	$pfs = array(array("Disabled", 0), array("Group 1 (768 bit)", 1), array("Group 2 (1024 bit)", 2), array("Group 5 (1536 bit)", 5));

	function buildSelectOption($arr) {
		if (!empty($arr) && is_array($arr)) {
			foreach(array_keys($arr) as $optkey) {
				$option = $arr[$optkey];
				print('<option value="'.$optkey.'" '.(($option[1] == 1) ? " selected" : "").'>'.$option[0].'</option>'."\n");
			}
		}
	}
?>

	<tr>
		<td nowrap class="labelcell"><label>Encryption Cipher:</label></td>
		<td class="ctrlcell" width="100%">

				<select name="fd_p1cipher[]" multiple="multiple" size="10" style="width: 100%">
					<? buildSelectOption($p1c); ?>
				</select>
		</td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Hash Algorithm:</label></td>
		<td class="ctrlcell" width="100%">

				<select name="fd_p1hash[]" multiple="multiple" size="6" style="width: 100%">
					<? buildSelectOption($p1h); ?>
				</select>
		</td>
	</tr>
	<tr>
	  <td nowrap class="labelcell"><label>DH Key Group:</label> </td>
	  <td class="ctrlcell">
				<select name="fd_p1group[]" multiple="multiple" size="6" style="width: 100%">
					<? buildSelectOption($p1g); ?>
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
				chdir(COYOTE_CONFIG_DIR."ipsec.d");
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
				<select name="fd_p2cipher[]" multiple="multiple" size="6" style="width: 100%">
					<? buildSelectOption($p2c); ?>
				</select>
			<br>
			<span class="descriptiontext">Note: The use of DES encryption is considered insecure and should only
			be used when it is the only choice offered by the remote VPN endpoint.</span><br>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td nowrap class="labelcell"><label>Hash Algorithms:</label></td>
		<td width="100%" class="ctrlcell">
				<select name="fd_p2hash[]" multiple="multiple" size="6" style="width: 100%">
					<? buildSelectOption($p2h); ?>
				</select>
		</td>
	</tr>
	<tr>
		<td class="labelcell">
			<label>Group Type: </label>
		</td>
		<td width="100%" class="ctrlcell">
			<select name="fd_p2group[]" multiple="multiple" size="6" style="width: 100%">
				<? buildSelectOption($p2g); ?>
				<option value="none">- none -</option>
			</select>
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
