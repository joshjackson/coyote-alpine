<?
	require_once("includes/loadconfig.php");
	VregCheck();

	/*
		[interface] => eth1
		[bypass] => 0
		[source] => 192.168.5.0/24
		[dest] => 172.120.0.0/16
	*/
	$MenuTitle="Network Address Translation";
	$MenuType="NETWORK";

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$fd_action = $_REQUEST['action'];

	if(!$fd_action) {
		$fd_nats = $configfile->nat;
		$fd_natcount = count($fd_nats);
	} else if($fd_action == 'delete') {
		$fd_natcount = $_REQUEST['natcount'];
		$fd_nats = array();

		for($i = 0; $i < $fd_natcount; $i++) {
			$fd_nat = array(
				'interface' => $_REQUEST['interface'.$i],
				'bypass' => $_REQUEST['bypass'.$i],
				'source' => $_REQUEST['source'.$i],
				'dest' => $_REQUEST['dest'.$i]
			);
			if($fd_nat['bypass']) $fd_nat['interface'] = '';
			if(!strlen($fd_nat['source']) && !strlen($fd_nat['dest'])) continue;
			$fd_nats[count($fd_nats)] = $fd_nat;
		}

		if(!query_invalid()) {
			$configfile->nat = $fd_nats;
		  if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");
			Header("Location:nat.php");
			die;
		}

	} else if($fd_action == 'apply') {
		//posted
		$fd_natcount = $_REQUEST['natcount'];
		$fd_nats = array();

		for($i = 0; $i < $fd_natcount; $i++) {
			$fd_nat = array(
				'interface' => $_REQUEST['interface'.$i],
				'bypass' => $_REQUEST['bypass'.$i],
				'source' => $_REQUEST['source'.$i],
				'dest' => $_REQUEST['dest'.$i]
			);

			//convert on -> 1
			if($fd_nat['bypass'] == 'on') $fd_nat['bypass'] = 1;

			//skip over the blank line at the end, permitting edits of earlier items
			if(!strlen($fd_nat['source']) && !strlen($fd_nat['dest'])) continue;

			if($fd_nat['bypass']) {
				$fd_nat['interface'] = '';
				if(!is_ipaddrblockopt($fd_nat['source'])) {
				  add_critical("Invalid IP addr: ".$fd_nat['source']." cannot be a source.");
					continue;
				}
				if(!is_ipaddrblockopt($fd_nat['dest'])) {
				  add_critical("Invalid IP addr: ".$fd_nat['dest']." cannot be a destination.");
					continue;
				}
			} else {
				//dest optional
				if(!is_ipaddrblockopt($fd_nat['source'])) {
				  add_critical("Invalid IP addr: ".$fd_nat['source']." cannot be a source.");
					continue;
				}
				if(strlen($fd_nat['dest']) && !is_ipaddrblockopt($fd_nat['dest'])) {
				  add_critical("Invalid IP addr: ".$fd_nat['dest']." cannot be a destination.");
					continue;
				}
			}
			$fd_nats[count($fd_nats)] = $fd_nat;
		}

		if(!query_invalid()) {
			$configfile->dirty["nat"] = true;
			$configfile->nat = $fd_nats;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");
			Header("Location:nat.php");
			die;
		}

	}

	include("includes/header.php");

	$fd_natcount++;
?>

<script language='javascript'>
	//insert code to handle deletion of an entry

	function c() { return <?=$fd_natcount?>; }

	function delete_item(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete this NAT rule?')) {
			f.elements['source'+id].value = '';
			f.elements['dest'+id].value = '';
			f.elements['action'].value = 'delete';
			f.submit();
		}
	}

	function toggle_bypass(ob, n) {
		//toggle select, checkbox
		v = ob.checked;
		if(v)
			document.forms[0].elements['interface'+i].disabled = true;
	}

	//on uncheck of box, enable the intf select, on check of box, disable the intf select
	function modify(id) {
		c = document.forms[0].elements['bypass'+id];
		s = document.forms[0].elements['interface'+id];
		s.disabled = c.checked;
	}

	function init() {
		//disable appropriate checkboxes / selects
		n = c();
		f = document.forms[0];
		for(i = 0; i < n; i++) {
			ob = f.elements['interface'+i];
			if(f.elements['bypass'+i].checked) {
				ob.value = 'none';
				ob.disabled = true;
			} else {
				ob.disabled = false;
			}
		}
	}
</script>

<form id="content" method="post" action="<?=$_SERVER['PHP_SELF'];?>">

<input type="hidden" name="action" value="apply" />
<input type="hidden" name="natcount" value="<?=$fd_natcount?>" />

<table width="100%" padding=0 spacing=0>
	<tr>
		<td class="labelcell"><label>Interface</label></td>
		<td class="labelcell" align="center"><label>Bypass?</label></td>
		<td class="labelcell" align="center"><label>Source</label></td>
		<td class="labelcell" align="center"><label>Destination</label></td>
		<td class="labelcell" align="center"><label>Update</label></td>
		<td class="labelcell" align="center"><label>Delete</label></td>
		<td class="labelcell" align="center"><label>Add</label></td>
	</tr>
	<tr>
	<?
	    $i = 0;
      //loop through existing rules
      foreach($fd_nats as $nat) {
        if ($i % 2)
          $cellcolor = "#F5F5F5";
        else
          $cellcolor = "#FFFFFF";
        ?>
          <td bgcolor="<?=$cellcolor?>">
						<select id="interface<?=$i?>" name="interface<?=$i?>" width="100%">
							<option value="none" <?if($nat['bypass']) print("selected");?> >none</option>
						<?
						//loop through interfaces
						foreach($configfile->interfaces as $ifentry) {
							//no downed intf allowed, duh
							if($ifentry['down']) continue;

							//if this is the chosen interface, make sure it is marked as selected
							if($ifentry['name'] == $nat['interface'])
								$selected = "selected";
							else
								$selected = "";

							echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
						}
						?>
						</select>
          </td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input onchange="modify(<?=$i?>)" type="checkbox" id="bypass<?=$i?>" name="bypass<?=$i?>" <? if($nat['bypass']) print("checked");?> />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="source<?=$i?>" name="source<?=$i?>" value="<?=$nat['source']?>" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="dest<?=$i?>" name="dest<?=$i?>" value="<?=$nat['dest']?>" />
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
						<select id="interface<?=$i?>" name="interface<?=$i?>" width="100%">
						<option value="none">none</option>
						<?
						//loop through interfaces
						foreach($configfile->interfaces as $ifentry) {
							//no downed intf allowed, duh
							if($ifentry['down']) continue;

							//if this is the chosen interface, make sure it is marked as selected
							$selected = "";

							echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
						}
						?>
						</select>
          </td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input onchange="modify(<?=$i?>)" type="checkbox" id="bypass<?=$i?>" name="bypass<?=$i?>" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="source<?=$i?>" name="source<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="dest<?=$i?>" name="dest<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>
	</tr>
        <?
    if(strlen($fd_warnings)) {
	    print("<tr><td class=ctrlcell colspan=2>$fd_warnings</td></tr>");
    }
	?>
	</tr>
</table>

<p>
  <script language="javascript">
	init();
</script>
  <span class="descriptiontext">The interface specificed should be the <em>outbound</em> interface. This would be the interface the specified traffic would leave on its way to the destination address. The source and destination addresses need to be in <em>address/prefix</em> notation. </span></p>
<p><span class="descriptiontext">The destination address is optional. If you want all traffic leaving the specified interface, coming from the source to be subject to NAT, leave the Destination blank. This would be the typical scenerio when sharing an Internet connection with the LAN behind the firewall. </span></p>
<p><span class="descriptiontext">The <em>bypass</em> option can be used to prevent certain traffic from being subject to NAT if it would normally  match against one of the other NAT directives.</span> </p>
</form>
<?
  print(query_warnings());
	include("includes/footer.php");
?>