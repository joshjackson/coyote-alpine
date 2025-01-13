<?
	include("../includes/loadconfig.php");

	// Extract the vpnsvc addon configuration object
	$vpnconf =& $configfile->get_addon('VPNSVCAddon', $vpnconf);
	if ($vpnconf === null) {
		// WTF?
		header("location:/index.php");
		exit;
	}

	$fd_action = $_REQUEST['action'];
	
	if ($fd_action == 'apply') {

		$fd_victim = $_REQUEST['remove'];
		$fd_type = $_REQUEST['deltype'];
		$fd_enabled = $_REQUEST['fd_enabled'];

		switch ($fd_type) {
			case 'tunnel':
				if (!$fd_victim) break;
				if(array_key_exists($fd_victim, $vpnconf->ipsec['tunnels']))
					unset($vpnconf->ipsec['tunnels'][$fd_victim]);
				$vpnconf->dirty["ipsec"] = true;
				WriteWorkingConfig();
				break;
				;;
			case 'cert':
				if (!$fd_victim) break;
				if ($IN_DEVELOPMENT) {
					$cdir = "/home/webdev/sites/wolverine/files/";
				} else {
					$cdir = COYOTE_CONFIG_DIR."ipsec.d/";
				}
				mount_flash_rw();
				unlink($cdir.$fd_victim);
				mount_flash_ro();
				break;
				;;
			case 'enable':
				$vpnconf->ipsec["enable"] = ($fd_enabled == "checked") ? true : false;
				$vpnconf->dirty["ipsec"] = true;
				WriteWorkingConfig();
				break;
				;;
		}
	}

	if ($vpnconf->ipsec["enable"]) {
		$fd_enabled = 'checked';
	} else {
		$fd_enabled = '';
	}
	
	$MenuTitle="IPSEC Configuration";
	$MenuType="VPN";
	include("../includes/header.php");
?>
<form name="content" method="post" action="<?=$_SERVER['../PHP_SELF']; ?>">
<script language="javascript">
	function delete_tunnel(itm) {
		if(confirm('Are you sure you want to delete the '+itm+' tunnel?')) {
			var r = document.getElementById('remove');
			var a = document.getElementById('action');
			var t = document.getElementById('deltype');
			r.value = itm;
			a.value = 'apply';
			t.value = 'tunnel';
			document.forms[0].submit();
		}
	}
	
	function delete_cert(c) {
		if(confirm('Are you sure you want to delete the '+c+' certificate file?')) {
			var r = document.getElementById('remove');
			var a = document.getElementById('action');
			var t = document.getElementById('deltype');
			r.value = c;
			a.value = 'apply';
			t.value = 'cert';
			document.forms[0].submit();
		}
	}

	function toggle_enable() {
		var a = document.getElementById('action');
		var t = document.getElementById('deltype');
		a.value = 'apply';
		t.value = 'enable';
		document.forms[0].submit();
	}

</script>

<input type="hidden" id="action" name="action" value="">
<input type="hidden" id="remove" name="remove" value="">
<input type="hidden" id="deltype" name="deltype" value="">

	<table cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<td class="labelcellmid" nowrap>
			<input name="fd_enabled" type="checkbox" onclick="javascript:toggle_enable()" value="checked" <?=$fd_enabled?>></td>
			<td class="labelcellmid" nowrap width="100%">
			<label>Enable the Wolverine IPSEC Service</label></td>
		</tr>
	</table>

<center><font size=2><b>IPSEC Tunnels</b></font></center>
			<table border="0" width="100%">
				<tr>
					<td class="labelcell" nowrap><label>Tunnel Name </label></td>
					<td class="labelcell" align="center" nowrap><label>Local 
					subnet</label></td>
					<td class="labelcell" align="center" nowrap><label>Remote 
					Subnet</label></td>
					<td class="labelcell" align="center" nowrap><label>Edit</label></td>
					<td class="labelcell" align="center" nowrap><label>Del</label></td>
				</tr>

<?

		//$fd_maxacl = count($configfile->acls) - 1;
		//$aclnames = array_keys($configfile->acls);
	
		$idx = 0;
		foreach($vpnconf->ipsec["tunnels"] as $tunnelname => $tunneldef) {
			if ($idx % 2) {
				$cellcolor = "#F5F5F5";
			} else {
				$cellcolor = "#FFFFFF";
			}
?>
			<tr>
				<td bgcolor="<?=$cellcolor?>"><?=$tunnelname?></td>
				<td bgcolor="<?=$cellcolor?>" align="center"><?=$tunneldef["localsub"]?></td>
				<td bgcolor="<?=$cellcolor?>" align="center"><?=$tunneldef["remotesub"]?></td>
				<td align="center" bgcolor="<?=$cellcolor?>">
				<a href="edit_tunnel.php?tunnel=<?=$tunnelname?>">
				<img border="0" src="../images/icon-edit.gif" width="16" height="16"></a></td>
				<td align="center" bgcolor="<?=$cellcolor?>">
				<a href="javascript:delete_tunnel('<?=$tunnelname?>')">
				<img border="0" src="../images/icon-del.gif" width="16" height="16"></a></td>
			</tr>

<?
			$idx++;
		}
?>
			</table>
			<table border="0" width="100%" id="table2">
				<tr>
					<td><a href="edit_tunnel.php">
					<img border="0" src="../images/icon-plus.gif" width="16" height="16"></a>
					</td>
					<td width="100%"><b><a href="edit_tunnel.php">Add a new IPSEC tunnel</a></b></td>
				</tr>
			</table>
<br><br>
<center><font size=2><b>x.509 certificate management</b></font></center>
<table width="100%"  border="0">
  <tr>
    <td class="labelcell"><label>Filename</label></td>
    <td class="labelcell"><label>Subject</label></td>
    <td class="labelcell"><div align="center"><label>View</label></div></td>
    <td class="labelcell"><div align="center"><label>Edit</label></div></td>
    <td class="labelcell"><div align="center"><label>Delete</label></div></td>
  </tr>
<?
	$curdir=getcwd();
	if ($IN_DEVELOPMENT) {
		chdir("/home/webdev/sites/wolverine/files");
	} else {
		chdir(COYOTE_CONFIG_DIR."/ipsec.d");
	}
	$idx = 0;
	$hostcert = $configfile->hostname."_cert.pem";
	foreach(glob("*_cert.pem") as $certfile) {	
		if ($idx % 2) {
			$cellcolor = "#F5F5F5";
		} else {
			$cellcolor = "#FFFFFF";
		}
		$certtext = file_get_contents($certfile);
		$certdata = openssl_x509_parse($certtext);
		$certid = trim(str_replace("/", " ", $certdata["name"]));
?>
  <tr>
    <td bgcolor="<?=$cellcolor?>"><?=$certfile?></td>
		
		
    <td bgcolor="<?=$cellcolor?>"><?=$certid?></td>
		<td align="center" bgcolor="<?=$cellcolor?>">
				<a href="viewcert.php?cert=<?=$certfile?>">
				<img border="0" src="../images/icon-view.gif" width="16" height="16"></a></td>
		<td align="center" bgcolor="<?=$cellcolor?>">
<?	if ($hostcert != $certfile ) { ?>
				<a href="editcert.php?cert=<?=$certfile?>">
				<img border="0" src="../images/icon-edit.gif" width="16" height="16"></a>
<?	} else print("&nbsp;"); ?>
		</td>
		<td align="center" bgcolor="<?=$cellcolor?>">
<?	if ($hostcert != $certfile ) { ?>
				<a href="javascript:delete_cert('<?=$certfile?>')">
				<img border="0" src="../images/icon-del.gif" width="16" height="16"></a>
<?	} else print("&nbsp;"); ?>
		</td>
  </tr>
<?
		$idx++;
	}
	chdir($curdir);
?>
</table>
</form>
			<table border="0" width="100%" id="table2">
				<tr>
					<td><a href="edit_tunnel.php">
					<img border="0" src="../images/icon-plus.gif" width="16" height="16"></a>
					</td>
					<td width="100%"><b><a href="editcert.php">Install a new certificate</a></b></td>
				</tr>
			</table>
<span class="descriptiontext">These certificates are used for the creation of IPSEC tunnels which use x.509 certificates for remote endpoint authentication. You will need to install a certificate for each remote endpoint which will use this authententication method. The certificate for this firewall can not be edited nor deleted.</span>
<?
	include("../includes/footer.php");
?>