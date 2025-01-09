<?
	require_once("../includes/loadconfig.php");

	// Extract the vpnsvc addon configuration object
	$vpnconf =& $configfile->get_addon('VPNSVCAddon', $vpnconf);
	if ($vpnconf === false) {
		// WTF?
		header("location:/index.php");
		exit;
	}


	$MenuTitle="PPTP User Authentication";
	$MenuType="VPN";
	$PageIcon="users.jpg";

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$fd_action = $_REQUEST['action'];
	
	if ($fd_action == "post") {
	
		// Process the radius server records
		$fd_secret = $_POST["radius-secret"];
		$fd_servercount = $_POST["servercount"];
		$fd_servers = array();
		$fd_posted = true;
		
		for($i = 0; $i < $fd_servercount; $i++) {
			if(strlen($_POST['server'.$i])) {
				$newserv = array(
					"host" => $_POST['server'.$i],
					"authport" => $_POST['authport'.$i],
					"acctport" => $_POST['acctport'.$i]
				);
				array_push($fd_servers, $newserv);
			}
		}

		// Process the local user records
		$fd_users = array();
		$fd_usercount = $_POST['usercount'];

		for($i = 0; $i < $fd_usercount; $i++) {
			if(strlen($_POST['username'.$i])) {
				$fd_users[count($fd_users)] = array(
					"username" => $_POST['username'.$i], 
					"password" => $_POST['password'.$i], 
					"passwordc" => $_POST['passwordc'.$i],
					"ip" => $_POST['ipaddr'.$i]);
			}
		}


	} else {
		// Radius servers
		$fd_servers = $vpnconf->radius["servers"];
		$fd_secret = $vpnconf->radius["key"];
		// Local users
		$fd_users = $vpnconf->pptp['users'];
		
		$fd_posted = false;
	}

	//validate each username and password
	if(count($fd_users)) {
		foreach($fd_users as $cuser) {
			//check username was filled in
			if(!strlen($cuser['username'])) {
			  add_critical("Invalid username: cannot be blank.");
			}

			//check password is long enough
			if(intval(strlen($cuser['password'])) < 6) {
			  add_critical("Invalid password: must be at least 6 chars in length.");
			}

			//check passwords match
			if($fd_posted && ($cuser['passwordc'] !== $cuser['password'])) {
			  add_critical("Password and Confirmation for user ".$cuser['username']." did not match!");
			}

			if(strlen($cuser['ip']) && !is_ipaddr($cuser['ip'])) {
				add_critical("Invalid IP address");
			}
			
		}
	}

	//display warnings, if any entries are invalid
	if(query_invalid()) {
		add_warning("<hr>Wolverine encountered ".query_invalid()." parameters that could not be validated.  No changes were made to the working configfile.");
	} else {
		if($fd_action == "post") {

			$vpnconf->pptp['users'] = array();
			$vpnconf->pptp['users'] = $fd_users;
			
			$vpnconf->radius['key'] = $fd_secret;
			$vpnconf->radius['servers'] = $fd_servers;

			$authtypes = array();
			if ($_POST["enable-local"] == "enable") {
				array_push($authtypes, "local");
			}
			if ($_POST["enable-radius"] == "enable") {
				array_push($authtypes, "radius");
			}
			$vpnconf->authentication["ppp"] = $authtypes;

			//write config
			$vpnconf->dirty["pptpusers"] = true;
		  	$vpnconf->dirty["pptp"] = true;
			
			print_r($vpnconf);
			
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");
		}
	}
	
	//update counts
	$fd_servercount = count($fd_servers) + 1;
	$fd_usercount = count($fd_users) + 1;

function is_authsvc_enabled($method) {
	global $vpnconf;
	return (is_array($vpnconf->authentication["ppp"]) && in_array($method, $vpnconf->authentication["ppp"]));
}

function is_radius_enabled() {
	return is_authsvc_enabled("radius");
}

function is_local_enabled() {
	return is_authsvc_enabled("local");
}
	
	include("../includes/header.php");

?>

<script language='javascript'>

	//insert code to handle deletion of an entry
	function delete_user(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete the user?')) {
			f.elements['user'+id].value = '';
			f.elements['password'+id].value = '';
			f.elements['passwordc'+id].value = '';
			f.elements['ipaddr'+id].value = '';
			f.submit();
		}
	}

	function delete_server(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete the server?')) {
			f.elements['server'+id].value = '';
			f.elements['authport'+id].value = '';
			f.elements['acctport'+id].value = '';
			f.submit();
		}
	}

</script>


<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<input type="hidden" name="action" value="post">
	<input type="hidden" id="servercount" name="servercount" value="<?=$fd_servercount?>">
	<input type="hidden" id="usercount" name="usercount" value="<?=$fd_usercount?>">

	<table cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<td class="labelcellmid" nowrap>
			<input type="checkbox" name="enable-local" value="enable" <? if(is_local_enabled()) print("checked")?>></td>
			<td class="labelcellmid" nowrap width="100%">
			<label>Enable Local User Authentication </label></td>
		</tr>
	</table>
	<table width="100%">
		<tr>
					<td class="labelcell" width="100%"><label>Login</label></td>
					<td class="labelcell" align="center"><label>Password</label></td>
					<td class="labelcell" align="center"><label>Confirm</label></td>
					<td class="labelcell" align="center"><label>IP Address</label></td>
					<td class="labelcell" align="center"><label>Update</label></td>
					<td class="labelcell" align="center"><label>Delete</label></td>
					<td class="labelcell" align="center"><label>Add</label></td>
	  </tr>
				<tr>

				<?
					//loop through host list, then add one empty
					$i = 0;
					if(count($fd_users)) {
						foreach($fd_users as $cruser) {

							if($i % 2)
								$cellcolor = "#F5F5F5";
							else
								$cellcolor = "#FFFFFF";

						//output with script breaks first, then convert to print() calls
							?>
							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="username<?=$i?>" name="username<?=$i?>" value="<?=$cruser['username']?>" />
								<? if(!strlen($cruser['username'])) mark_valid(0); ?>
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>">
								<input type="password" id="password<?=$i?>" name="password<?=$i?>" value="<?=$cruser['password']?>" />
								<? if(!strlen($cruser['password'])) mark_valid(0); ?>
							</td>
							<td align="left" bgcolor="<?=$cellcolor?>">
								<input type="password" id="passwordc<?=$i?>" name="passwordc<?=$i?>" value="<?=$cruser['password']?>" />
								<? if(!strlen($cruser['password'])) mark_valid(0); ?>
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>"><input type="text" name="ipaddr<?=$i?>" value=<?=$cruser['ip']?>></td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<a href="javascript:do_submit()"><img border="0" src="/images/icon-chk.gif" width="16" height="16"></a>
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<a href="javascript:delete_user(<?=$i?>)"><img border="0" src="/images/icon-del.gif" width="16" height="16"></a>
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
	  </tr><tr>
							<?
							$i++;
						}
					}

						//do this one more time for our extra/default/new row
						if($i % 2)
							$cellcolor = "#F5F5F5";
						else
							$cellcolor = "#FFFFFF";
				?>
							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="username<?=$i?>" name="username<?=$i?>" value="" />
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>"><input type="password" id="password<?=$i?>" name="password<?=$i?>" value="" />
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>"><input type="password" id="passwordc<?=$i?>" name="passwordc<?=$i?>" value="" />
							</td>

				            <td align="center" bgcolor="<?=$cellcolor?>"><input type="text" name="ipaddr<?=$i?>"></td>
				            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
				<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
				<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="/images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>
	</table>
    <span class="descriptiontext"><strong>Note:</strong> To have the PPTP server automatically assign an IP address to the connecting client, leave the IP address field blank. when specifying a static IP address for the client, the address should  be outside of the PPTP address pool.</span> 
	
	<br>
    <br>
  </p>
<table cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<td class="labelcellmid" nowrap>
			<input type="checkbox" name="enable-radius" value="enable" <? if(is_radius_enabled()) print("checked")?>></td>
			<td class="labelcellmid" nowrap width="100%">
			<label>Enable Radius Authentication </label></td>
		</tr>
  </table>
	<table width="100%">
		<tr>
			<td class="labelcell" nowrap>
			<label>Shared Secret: </label>
			</td>
			<td width="100%"><input type="text" name="radius-secret" value=<?=$fd_secret?>>
			  <br>
		    <span class="descriptiontext">The shared secret for communicating with the Radius servers. Note that the shared secret must be the same for all configured Radius servers.</span> 
			</td>
		</tr>
		<tr>
			<td colspan="2" width="100%">
			</td>
		</tr>
	</table>
	<table width="100%">
		<tr>
					<td class="labelcell" width="100%"><label>Radius Server Address </label></td>
					<td class="labelcell" align="center"><label>Authentication Port </label></td>
					<td class="labelcell" align="center"><label>Accounting Port </label></td>
					<td class="labelcell" align="center"><label>Update</label></td>
					<td class="labelcell" align="center"><label>Delete</label></td>
					<td class="labelcell" align="center"><label>Add</label></td>
	  </tr>
				<tr>

				<?
					//loop through host list, then add one empty
					$i = 0;
					if(count($fd_servers)) {
						foreach($fd_servers as $servent) {

							if($i % 2)
								$cellcolor = "#F5F5F5";
							else
								$cellcolor = "#FFFFFF";

						//output with script breaks first, then convert to print() calls
							?>
							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="server<?=$i?>" name="server<?=$i?>" value="<?=$servent['host']?>" />
								<? if(!strlen($cruser['username'])) mark_valid(0); ?>
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>">
								<input type="text" id="authport<?=$i?>" name="authport<?=$i?>" value="<?=$servent['authport']?>" />
							</td>
							<td align="left" bgcolor="<?=$cellcolor?>">
								<input type="text" id="acctport<?=$i?>" name="acctport<?=$i?>" value="<?=$servent['acctport']?>" />
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<a href="javascript:do_submit()"><img border="0" src="/images/icon-chk.gif" width="16" height="16"></a>
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<a href="javascript:delete_server(<?=$i?>)"><img border="0" src="/images/icon-del.gif" width="16" height="16"></a>
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
	  </tr><tr>
							<?
							$i++;
						}
					}

						//do this one more time for our extra/default/new row
						if($i % 2)
							$cellcolor = "#F5F5F5";
						else
							$cellcolor = "#FFFFFF";
				?>
							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="server<?=$i?>" name="server<?=$i?>" value="" />
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="authport<?=$i?>" name="authport<?=$i?>" value="" />
							</td>

							<td align="left" bgcolor="<?=$cellcolor?>"><input type="text" id="acctport<?=$i?>" name="acctport<?=$i?>" value="" />
							</td>

				<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
				<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
				<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="/images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>
	</table>
</form>
<?
	include("../includes/footer.php");
?>