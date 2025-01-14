<?
	require_once("includes/loadconfig.php");

	$httpconf =& $configfile->get_addon('WebAdminService');

		$MenuTitle="Remote Administration";
		$MenuType="GENERAL";
		$PageIcon="remote.jpg";

		$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
		$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

		function is_ssh_enabled() {
			global $fd_ssh_enabled;

			if($fd_ssh_enabled == 'on')
				return true;
			else
				return false;
		}

		function is_http_enabled() {
			global $fd_http_enabled;

			if($fd_http_enabled == 'on')
				return true;
			else
				return false;
		}

		//did we freshly load this page or are we loading on result of a post
		$fd_posted = ($_SERVER['REQUEST_METHOD'] == 'POST');

		//fill values from _POST or configfile
		if($fd_posted) {

			//enabled values
			$fd_http_enabled = $_POST['http_enabled'];
			$fd_ssh_enabled = $_POST['ssh_enabled'];

			//form values
			$fd_http_port = $_POST['http_port'];
			$fd_ssh_port = $_POST['ssh_port'];
			$fd_http_hostcount = $_POST['httphostcount'];
			$fd_ssh_hostcount = $_POST['sshhostcount'];

			//http host list
			$fd_http_hostlist = array();
			for($hc = 0; $hc < $fd_http_hostcount; $hc++) {
				if(strlen($_POST['httphost'.$hc])) {
					$fd_http_hostlist[count($fd_http_hostlist)] = $_POST['httphost'.$hc];
				}
			}

			//ssh host list
			$fd_ssh_hostlist = array();
			for($hc = 0; $hc < $fd_ssh_hostcount; $hc++) {
				if(strlen($_POST['sshhost'.$hc])) {
					$fd_ssh_hostlist[count($fd_ssh_hostlist)] = $_POST['sshhost'.$hc];
				}
			}

			//update counts
			$fd_http_hostcount = count($fd_http_hostlist);
			$fd_ssh_hostcount = count($fd_ssh_hostlist);
		} else {

			//values from configfile, http
			if($httpconf->http['enable']) {
				$fd_http_enabled = 'on';
				$fd_http_port = $httpconf->http['port'];
				$fd_http_hostlist = $httpconf->http['hosts'];
			}

			//values from configfile, ssh
			if($configfile->ssh['enable']) {
				$fd_ssh_enabled = 'on';
				$fd_ssh_port = $configfile->ssh['port'];
				$fd_ssh_hostlist = $configfile->ssh['hosts'];
			}

			//update counts, add one so that we will always draw an extra row
			//and account properly after posting
			if(!is_array($fd_http_hostlist)) $fd_http_hostlist = array();
			if(!is_array($fd_ssh_hostlist)) $fd_ssh_hostlist = array();
			$fd_http_hostcount = count($fd_http_hostlist);
			$fd_ssh_hostcount = count($fd_ssh_hostlist);

		}

		//validate

		if($fd_posted && is_http_enabled() && !strlen($fd_http_port)) {
		  add_warning("No port specified for HTTP administration; assuming default of 443.");
			$fd_http_port = '443';
		}

		if($fd_posted && is_ssh_enabled() && !strlen($fd_ssh_port)) {
		  add_warning("No port specified for SSH administration; assuming default of 22.");
			$fd_ssh_port = '22';
		}

		//validate each http host
		if(count($fd_http_hostlist)) {
			foreach($fd_http_hostlist as $cvhhost) {

				//use ipcalc (wrapped) to validate
				if(!is_ipaddr($cvhhost) && !is_ipaddrblockopt($cvhhost)) {
				  add_critical("Invalid IP addr: ".$cvhhost);
				}
			}
		}

		//validate each ssh host
		if(count($fd_ssh_hostlist)) {
			foreach($fd_ssh_hostlist as $cvshost) {

				//use ipcalc (wrapped) to validate
				if(!is_ipaddr($cvshost) && !is_ipaddrblockopt($cvshost)) {
				  add_critical("Invalid IP addr: ".$cfshost);
				}
			}
		}

		//display warnings, if any entries are invalid
		if(query_invalid()) {
			add_warning("<hr>Encountered ".query_invalid()." parameters that could not be validated.  No changes were made to the config.");
		} else {
			if($fd_posted) {

				//clear hosts arrays, they will be rebuilt if needed
				$httpconf->http['hosts'] = array();
				$configfile->ssh['hosts'] = array();

				$httpconf->http['enable'] = is_http_enabled();
				$httpconf->http['port'] = $fd_http_port;
				$httpconf->http['hosts'] = $fd_http_hostlist;

				$configfile->ssh['enable'] = is_ssh_enabled();
				$configfile->ssh['port'] = $fd_ssh_port;
				$configfile->ssh['hosts'] = $fd_ssh_hostlist;

				//write config
  			if(WriteWorkingConfig())
  				add_warning("Write to working configfile was successful.");
  			else
  			  add_warning("Error writing to working configfile!");

			}
		}

		//hostcount should be incremented now, it will be filled into a hidden element
		//to indicate new host count at next post
		$fd_ssh_hostcount++;
		$fd_http_hostcount++;
		include("includes/header.php");
?>

<script language="javascript">
	function delete_item(id) {
		f = document.forms[0];
		found = 0;

		if(!confirm('Delete this Host?')) exit;

		//please kill me
		for(i=0;i<f.elements.length;i++) {
			if(f.elements[i].name == id) {
				f.elements[i].value = '';
				found++;
			}
		}

		//submit if we actually found something to delete, best to confirm too.
		if(found) f.submit();
	}
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">

		<!-- hidden items used after post -->
	<input type="hidden" id="httphostcount" name="httphostcount" value="<?=$fd_http_hostcount?>">
	<input type="hidden" id="sshhostcount" name="sshhostcount" value="<?=$fd_ssh_hostcount?>">

<table cellspacing="0" cellpadding="3" width="100%" id="table2">
	<tr>
		<td class="labelcellmid"><input type="checkbox" name="http_enabled" <? if(is_http_enabled()) print("checked")?>> </td>
		<td class="labelcellmid" width="100%"><label><font size=2>Enable
		remote web administration of this firewall</font></label></td>
	</tr>
</table>

<table cellspacing="0" cellpadding="3" width="100%" id="table2">
	<tr>
		<td class="labelcell"><label>Web port:</label></td>
		<td><input type="text" name="http_port" size="6" value="<?=$fd_http_port?>"><br>
		<span class="descriptiontext">The port to listen for remote web administration connections. The
		default port for remote web administration is 443.</span></td>
	</tr>
</table>

<input type="hidden" name="action" value="ssh">

	<table cellspacing="0" cellpadding="3" width="100%" id="table3">
	<tr>
		<td class="labelcellmid"><input type="checkbox" name="ssh_enabled" <? if(is_ssh_enabled()) print("checked")?>></td>
		<td class="labelcellmid" width="100%"><label><font size=2>Enable
		remote SSH administration of this firewall</font></label></td>
	</tr>
	</table>

	<table cellspacing="0" cellpadding="3" width="100%" id="table3">
	<tr>
			<td class="labelcell"><label>SSH port:</label></td>
			<td><input type="text" name="ssh_port" size="6" value="<?=$fd_ssh_port?>"><br>
			<span class="descriptiontext">The port to listen for remote SSH
			administration connections. The default port for remote SSH
			administration is 22.</span></td>
		</tr>
	</table>

	<!-- table contains host list for remote HTTP connections -->
	<table width="60%">
		<tr><td>&nbsp;</td></tr>

		<!-- insert a table, as hosts are enabled to list hosts (always at least one) -->
		<tr>
					<td class="labelcell" width="100%"><label>Permit HTTP administration from the following hosts and/or networks:</label></td>
					<td class="labelcellctr"><label>Update</label></td>
					<td class="labelcellctr"><label>Delete</label></td>
					<td class="labelcellctr"><label>Add</label></td>
				</tr>
				<tr>

				<?
					//loop through host list, then add one empty
					$i = 0;
					if(count($fd_http_hostlist)) {
						foreach($fd_http_hostlist as $chhost) {

							if($i % 2)
								$cellcolor = "#F5F5F5";
							else
								$cellcolor = "#FFFFFF";

							//output with script breaks first, then convert to print() calls
							?>
							<td style="text-align: left; background-color: <?=$cellcolor?>;"><input type="text" id="httphost<?=$i?>" name="httphost<?=$i?>" value="<?=$chhost?>" />
							<? if(strlen($chhost)) mark_valid(is_ipaddrblockopt($chhost)) ?>
							</td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:do_submit()"><img src="images/icon-chk.gif" width="16" height="16"></a></td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:delete_item('httphost<?=$i?>')"><img src="images/icon-del.gif" width="16" height="16"></a></td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
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
				<td style="text-align: left; background-color: <?=$cellcolor?>;" nowrap><input type="text" id="httphost<?=$i?>" name="httphost<?=$i?>" value="" /></td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:do_submit()"><img src="images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>
	</table>

	<!-- table contains host list for remote SSH connections -->
	<table width="60%">
		<tr><td>&nbsp;</td></tr>

		<!-- insert a table, as hosts are enabled to list hosts (always at least one) -->
		<tr>
					<td class="labelcell" width="100%"><label>Permit SSH administration from the following hosts and/or networks:</label></td>
					<td class="labelcellctr"><label>Update</label></td>
					<td class="labelcellctr"><label>Delete</label></td>
					<td class="labelcellctr"><label>Add</label></td>
				</tr>
				<tr>

				<?
					//loop through host list, then add one empty
					$i = 0;
					if(count($fd_ssh_hostlist)) {
						foreach($fd_ssh_hostlist as $cshost) {

							if($i % 2)
								$cellcolor = "#F5F5F5";
							else
								$cellcolor = "#FFFFFF";

							//output with script breaks first, then convert to print() calls
							?>
							<td style="text-align: left; background-color: <?=$cellcolor?>;"><input type="text" id="sshhost<?=$i?>" name="sshhost<?=$i?>" value="<?=$cshost?>" />
							<? if(strlen($cshost)) mark_valid(is_ipaddrblockopt($cshost)) ?>
							</td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:do_submit()"><img src="images/icon-chk.gif" width="16" height="16"></a></td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:delete_item('sshhost<?=$i?>')"><img src="images/icon-del.gif" width="16" height="16"></a></td>
							<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
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
				<td style="text-align: left; background-color: <?=$cellcolor?>;" nowrap><input type="text" id="sshhost<?=$i?>" name="sshhost<?=$i?>" value="" /></td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;">&nbsp;</td>
				<td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:do_submit()"><img src="images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>
	</table>

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
