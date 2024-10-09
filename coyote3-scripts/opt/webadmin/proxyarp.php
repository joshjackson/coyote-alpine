<?
	include("includes/loadconfig.php");
	VregCheck();

	/*
		[0] => Array (
	    [int_if] => eth1
	    [ext_if] => eth0
	    [address] => 24.106.242.83
    )
  */


	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$MenuTitle="Proxy ARP";
	$MenuType="NETWORK";

	$fd_action = $_REQUEST['action'];

	if(!$fd_action) {
		$fd_parps = $configfile->proxyarp;
		$fd_parpcount = count($fd_parps);
	} else {
		//posted
		$fd_parpcount = $_REQUEST['parpcount'];
		$fd_parps = array();

		for($i=0;$i < $fd_parpcount; $i++) {
			$parp = array(
				'int_if' => $_REQUEST['int_if'.$i],
				'ext_if' => $_REQUEST['ext_if'.$i],
				'address' => $_REQUEST['address'.$i]
			);

			//nothing to do if no address is given
			if(!strlen($parp['address'])) continue;

			if(!is_ipaddr($parp['address'])) {
			  add_critical("Invalid IP addr: ".$parp['address']);
				continue;
			}

			//if control arrives here, add the entry
			$fd_parps[count($fd_parps)] = $parp;
		}

		//write
		if(!query_invalid()) {
			$configfile->proxyarp = $fd_parps;
			$configfile->dirty["proxyarp"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

			Header("Location:proxyarp.php");
			die;
		}
	}

	$fd_parpcount++;

	include("includes/header.php");
?>

<script language='javascript'>
	//insert code to handle deletion of an entry
	function delete_item(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete this parp?')) {
			f.elements['address'+id].value = '';
			f.submit();
		}
	}
</script>

<form id="content" method="post" action="<?=$_SERVER['PHP_SELF'];?>">

<input type="hidden" name="action" value="apply" />
<input type="hidden" name="parpcount" value="<?=$fd_parpcount?>" />

<span class="descriptiontext">Proxy ARP can be used to allow an address that
would normally be part of the network attached to the &quot;external&quot; interface to be
connected to the &quot;internal&quot;
interface. This option can be used instead of bridging if you only have a small block
of IP addresses and want to use services such as IPSEC or PPTP. For more
information on how to properly use Proxy ARP, please refer to the product documentation.</span>

<table width="100%" padding=0 spacing=0>
	<tr>
		<td class="labelcell"><label>Internal Interface</label></td>
		<td class="labelcell"><label>External Interface</label></td>
		<td class="labelcell"><label>Address</label></td>
		<td class="labelcell" align="center"><label>Update</label></td>
		<td class="labelcell" align="center"><label>Delete</label></td>
		<td class="labelcell" align="center"><label>Add</label></td>
	</tr>
	<tr>
	<?
			    $i = 0;
          //loop through existing reservations
          foreach($fd_parps as $parp) {
            if ($i % 2)
              $cellcolor = "#F5F5F5";
            else
              $cellcolor = "#FFFFFF";
            ?>
              <td bgcolor="<?=$cellcolor?>">
								<select id="int_if<?=$i?>" name="int_if<?=$i?>" width="100%">
								<?
								//loop through interfaces
								foreach($configfile->interfaces as $ifentry) {
									//no downed intf allowed, duh
									if($ifentry['down']) continue;

									//if this is the chosen interface, make sure it is marked as selected
									if($ifentry['name'] == $parp['int_if'])
										$selected = "selected";
									else
										$selected = "";

									echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
								}
								?>
								</select>
              </td>
							<td bgcolor="<?=$cellcolor?>">
								<select id="ext_if<?=$i?>" name="ext_if<?=$i?>" width="100%">
								<?
								//loop through interfaces
								foreach($configfile->interfaces as $ifentry) {
									//no downed intf allowed, duh
									if($ifentry['down']) continue;

									//if this is the chosen interface, make sure it is marked as selected
									if($ifentry['name'] == $parp['ext_if'])
										$selected = "selected";
									else
										$selected = "";

									echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
								}
								?>
								</select>
							</td>
							<td bgcolor="<?=$cellcolor?>">
								<input type="text" id="address<?=$i?>" name="address<?=$i?>" value="<?=$parp['address']?>" />
							</td>

              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_item('<?=$i?>')"><img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
              <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
            </tr>
            <tr>
            <?
            $i++;
          }
        //always make one empty row
        if($i % 2)
          $cellcolor = "#F5F5F5";
        else
          $cellcolor = "#FFFFFF";

        ?>
              <td bgcolor="<?=$cellcolor?>">
								<select id="int_if<?=$i?>" name="int_if<?=$i?>" width="100%">
								<?
								//loop through interfaces
								foreach($configfile->interfaces as $ifentry) {
									//no downed intf allowed, duh
									if($ifentry['down']) continue;

									//if this is the chosen interface, make sure it is marked as selected
									if($ifentry['name'] == $parp['int_if'])
										$selected = "selected";
									else
										$selected = "";

									echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
								}
								?>
								</select>
              </td>
							<td bgcolor="<?=$cellcolor?>">
								<select id="ext_if<?=$i?>" name="ext_if<?=$i?>" width="100%">
								<?
								//loop through interfaces
								foreach($configfile->interfaces as $ifentry) {
									//no downed intf allowed, duh
									if($ifentry['down']) continue;

									//if this is the chosen interface, make sure it is marked as selected
									echo '<option value="'.$ifentry["name"].'" >'.$ifentry["name"].'</option>';
								}
								?>
								</select>
							</td>
							<td bgcolor="<?=$cellcolor?>">
								<input type="text" id="address<?=$i?>" name="address<?=$i?>" value="" />
							</td>

					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>
				</tr>
        <?
    if(strlen(query_warnings())) {
	    print("<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>");
    }
	?>
	</tr>
</table>
</form>

<?
	include("includes/footer.php");
?>