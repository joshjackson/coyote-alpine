<?
	require_once("includes/loadconfig.php");
	VregCheck();

	$MenuTitle="System Passwords";
	$PageIcon = "settings.jpg";
	$MenuType='GENERAL';

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

function GetUserIndex($Username, $UArray) {

	for ($t = 0; $t < count($UArray); $t++) {
		if ($UArray[$t]["username"] == $Username) {
			return $t;
		}
	}
	return -1;

}

	//did we freshly load this page or are we loading on result of a post
	if(strlen($_POST['postcheck']))
		$fd_posted = true;
	else
		$fd_posted = false;

	if($fd_posted) {
		//user list - start with the users in the config file
		$fd_users = $configfile->users;
		$fd_usercount = count($fd_users);

		for($i = 0; $i < $fd_usercount; $i++) {
			// Update the users with non blank passwords
			$upd_user = GetUserIndex($_POST['username'.$i], $fd_users);
			if ($upd_user >= 0) {
				if(strlen($_POST['password'.$i]) || strlen($_POST['passwordc'.$i])) {
					$fd_users[$upd_user] =
						array(
							"username" => $_POST['username'.$i],
							"password" => $_POST['password'.$i],
							"encrypted" => $_POST['encrypted'.$i],
							"passwordc" => $_POST['passwordc'.$i],
							"update" => true
						);
				} else {
					$fd_users[$upd_user]["update"] = false;
				}
			}
		}

	} else {

		//values from configfile
		if(count($configfile->users))
			$fd_users = $configfile->users;
		else
			$fd_users = array();

		$fd_usercount = count($fd_users);

	}

	//validate?

	//looping with index to compare with the password confirm
	if($fd_posted) {
		foreach($fd_users as $vuser) {
			if (array_key_exists("update", $vuser) && $vuser["update"]) {
				//enforce minimum length
				if(strlen($vuser['password']) < 6) {
				  add_critical("Invalid password: must be at least 6 chars in length.");
				}
	
				//enforce pwd and conf match
				if($vuser['password'] !== $vuser['passwordc']) {
				  add_critical("Passwords and Confirmation for ".$vuser['username']." did not match!");
				}
			}
		}
	}

	//update configfile.. I guess
	if(query_invalid()) {
		add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
	} else {
		if($fd_posted) {

			for($pi = 0; $pi < count($fd_users); $pi++) {
				if (array_key_exists("update", $fd_users[$pi])) {
					if ($fd_users[$pi]["update"])
						unset($fd_users[$pi]['passwordc']);
					unset($fd_users[$pi]["update"]);
				}
				$configfile->users[$pi] = $fd_users[$pi];
			}

			//write config
			$configfile->dirty["passwords"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

			//get the newly-encrypted passwords back.. I hope
			$fd_users = $configfile->users;
		}
	}
	//get the newly-encrypted passwords back.. I hope
	$fd_users = $configfile->users;

	include("includes/header.php");
?>

<script language="javascript">
	function update_enc(id) {
		f = document.forms[0];
		found = 0;

		for(i=0;i<f.elements.length;i++) {
			if(f.elements[i].name == id) f.elements[i].value = 0;
		}
	}
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>" width="100%">

		<!-- hidden items used after post -->
	<input type="hidden" id="postcheck" name="postcheck" value="form was posted">
	<input type="hidden" id="usercount" name="usercount" value="<?=$fd_usercount?>">

	<!-- table contains host list for user logins -->
	<table width="50%">
		<tr>
			<td class="labelcell" width="100%"><label>Login</label></td>
			<td class="labelcell" width="100%"><label>Password</label></td>
			<td class="labelcell" width="100%"><label>Confirm</label></td>
			<td class="labelcell" align="center"><label>Update</label></td>
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
							<td align="left" bgcolor="<?=$cellcolor?>">
								<input readonly type="text" id="username<?=$i?>" name="username<?=$i?>" value="<?=$cruser['username']?>" />
							</td>

							<td align="center" bgcolor="<?=$cellcolor?>">
								<input type="password" id="password<?=$i?>" name="password<?=$i?>" value="" onchange="update_enc('encrypted<?=$i?>');" />
							</td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<input type="password" id="passwordc<?=$i?>" name="passwordc<?=$i?>" value="" onchange="update_enc('encrypted<?=$i?>');" />
								<input type="hidden" id="encrypted<?=$i?>" name="encrypted<?=$i?>" value="<?=$cruser['encrypted']?>" />
							</td>

							<td align="center" bgcolor="<?=$cellcolor?>">
								<a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a>
							</td>
		</tr><tr>
							<?
							$i++;
						}
					}

				?>
		</tr>
	</table>

	<span class="descriptiontext"><p><b>Note:</b> The user account &quot;admin&quot; should be used for general administration purposes. The &quot;debug&quot; account is only valid from the text console and its use is not supported nor recommended.
</p>
	<p>To leave a password unchanged, leave the entry fields blank. </p></span>
	<table width="100%">
		<?
			if(strlen(query_warnings())) {
				echo "<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>";
			}
		?>
	</table>

</form>

<?
	include("includes/footer.php");
?>