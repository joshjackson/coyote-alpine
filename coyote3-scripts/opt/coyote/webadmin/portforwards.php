<?
	require_once("includes/loadconfig.php");

	/*
	[portforwards] => Array
  	[0] => Array
			[source] => 10.0.0.2
			[dest] => 192.168.0.100
			[protocol] => tcp
			[from-port] => 8080
			[to-port] => 80
	[autoforwards] => Array
		[0] => Array
			[interface] => eth0
			[protocol] => tcp
			[port] => 110:111 (start:end)
			[dest] => 192.168.0.101
	*/


	$MenuTitle='Port Forwards';
	$MenuType="NETWORK";

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	//assemble valid IP addresses from across all interfaces
	$fd_pfsources = array();
	foreach($configfile->interfaces as $ifentry) {
		//continue quickly, if possible.  This will not be an array
		//if the addressing mode for this intf is dhcp, pppoe, etc
		if(!is_array($ifentry['addresses'])) continue;

		foreach($ifentry['addresses'] as $addr) {
			//add only the ip address, without the suffix ::jc
			//if(!is_ipaddrblockopt($addr['ip'])) continue;

			if (($addr["ip"] != "pppoe") && ($addr != "dhcp")) {
				$ip = explode('/',$addr['ip']);
				if(is_array($ip))
					$fd_pfsources[count($fd_pfsources)] = $ip[0];
				else
					$fd_pfsources[count($fd_pfsources)] = $addr['ip'];
			}
		}
	}

	if (!$_SERVER['REQUEST_METHOD'] == 'POST') {
		$fd_posted = true;
		$fd_pfwds = $configfile->portforwards;
		$fd_afwds = $configfile->autoforwards;
		$fd_pcount = count($fd_pfwds);
		$fd_acount = count($fd_afwds);
	} else {
		$fd_acount = $_REQUEST['fd_acount'];
		$fd_pcount = $_REQUEST['fd_pcount'];

		$fd_pfwds = array();
		$fd_afwds = array();

		//port forwards
		for($i = 0; $i < $fd_pcount; $i++) {

			$fd_pfwd = array(
				'source' => $_REQUEST['source'.$i],
				'dest' => $_REQUEST['fdest'.$i],
				'protocol' => $_REQUEST['fprotocol'.$i],
				'from-port' => $_REQUEST['from'.$i],
				'to-port' => $_REQUEST['to'.$i]
			);

			//if dest is empty, skip it; both are required
			if(!strlen($fd_pfwd['dest'])) continue;

			$from = $fd_pfwd['from-port'];
			$to = $fd_pfwd['to-port'];

			//FIXME: port range isn't ipv6 ready ::Jc
			if (strlen($from)) {
				if((intval($from) <= 0) || (intval($from) > 65535)) {
					add_critical("Invalid port (from): ".$from);
					$fd_pfwd["invalid"] = true;
				}
	
				if(strlen($to) && ((intval($to) <= 0) || (intval($to) > 65535))) {
				  	add_critical("Invalid port (to): ".$to);
					$fd_pfwd["invalid"] = true;
				}
			} else {
				$from = '';
				$to = '';
			}

			if(!is_ipaddr($fd_pfwd['source'])) {
				add_critical("Invalid IP addr: ".$fd_pfwd['source']);
				$fd_pfwd["invalid"] = true;
			}

			if(!is_ipaddr($fd_pfwd['dest'])) {
			  	add_critical("Invalid IP addr: ".$fd_pfwd['dest']);
				$fd_pfwd["invalid"] = true;
			}

			$fd_pfwds[count($fd_pfwds)] = $fd_pfwd;
		}

		//autoforwards
		for($i = 0; $i < $fd_acount; $i++) {

			$fd_afwd = array(
				'interface' => $_REQUEST['interface'.$i],
				'dest' => $_REQUEST['adest'.$i],
				'protocol' => $_REQUEST['aprotocol'.$i],
				'port' => '',
			);

			$sp = $_REQUEST['astartport'.$i];
			$ep = $_REQUEST['aendport'.$i];

			//if either is empty, skip it; both are required
			if(!strlen($sp) || !strlen($fd_afwd['dest'])) continue;

			//deal with ports
			if(!strlen($sp)) {
			  add_critical("Invalid port (start): may not be null.");
				continue;
			}

			//check port ranges
			//FIXME: upper bound of port is not ipv6 ready ::jc
			if((intval($sp) <= 0) || (intval($sp) > 65535)) {
			  add_critical("Invalid port (start): ".$sp." is out of IPv4 range [0..65535].");
				continue;
			}

			if(strlen($ep) && ((intval($ep) < 0) || (intval($ep) > 65535))) {
			  add_critical("Invalid port (end): ".$ep." is out of IPv4 range [0..65535].");
				continue;
			}

			//startport must be lower intval than endport
			//FIXME: port validation can be standardized ::jc
			if(strlen($ep) && intval($sp) > intval($ep)) {
			  add_critical("Invalid port range: ".$sp."..".$ep." must be reversed or endport nulled.");
				continue;
			}

			if(!is_ipaddr($fd_afwd['dest'])) {
			  add_critical("Invalid IP addr: ".$fd_afwd['dest']);
				continue;
			}

			if(($sp == $ep) || !strlen($ep))
				$fd_afwd['port'] = $sp;
			else
				$fd_afwd['port'] = $sp.':'.$ep;

			$fd_afwds[count($fd_afwds)] = $fd_afwd;
		}

		if(query_invalid()) {
			add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
		} else {
			$configfile->dirty['acls'] = true;

			$configfile->portforwards = $fd_pfwds;
			$configfile->autoforwards = $fd_afwds;

			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

			Header("Location:".$_SERVER['PHP_SELF']);
			die;
		}
	}

	include("includes/header.php");

	$fd_pcount++;
	$fd_acount++;
?>

<script language='javascript'>

	//insert code to handle deletion of an entry
	function delete_pfwd(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete this port forward rule?')) {
			f.elements['fdest'+id].value = '';
			f.elements['from'+id].value = '';
			f.elements['to'+id].value = '';
			f.submit();
		}
	}

	function delete_afwd(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete this auto forward rule?')) {
			f.elements['adest'+id].value = '';
			f.elements['astartport'+id].value = '';
			f.elements['aendport'+id].value = '';
			f.submit();
		}
	}

</script>

<form id="content" method="post" action="<?=$_SERVER['PHP_SELF'];?>">

<input type="hidden" name="action" value="apply" />
<input type="hidden" name="fd_acount" value="<?=$fd_acount?>" />
<input type="hidden" name="fd_pcount" value="<?=$fd_pcount?>" />

<table width="100%" padding=0 spacing=0>
	<tr>
		<td class="labelcell" width="100%" colspan=8>
			<label><font size=2>Port Forwarding</font></label>
		</td>
	</tr>
	<tr>
		<td class="labelcell"><label>Source</label></td>
		<td align="center" class="labelcell"><label>Destination</label></td>
		<td class="labelcell" align="center"><label>Protocol</label></td>
		<td class="labelcell" align="center"><label>From port</label></td>
		<td class="labelcell" align="center"><label>To port</label></td>
		<td class="labelcell" align="center"><label>Update</label></td>
		<td class="labelcell" align="center"><label>Delete</label></td>
		<td class="labelcell" align="center"><label>Add</label></td>
	</tr>
	<tr>
	<?
	    $i = 0;
      //loop through existing rules
      foreach($fd_pfwds as $pf) {
	  	if ($pf["invalid"]) {
			$cellcolor = "#FFA6A6";
		} else {
			if ($i % 2)
			  $cellcolor = "#F5F5F5";
			else
			  $cellcolor = "#FFFFFF";
		}
        ?>
          <td bgcolor="<?=$cellcolor?>">
						<select id="source<?=$i?>" name="source<?=$i?>">
							<?
								foreach($fd_pfsources as $ip) {
									if($pf['source'] == $ip)
										$selected = 'selected';
									else
										$selected = '';
									echo '<option value="'.$ip.'" '.$selected.'>'.$ip.'</option>';
								}
							?>
						</select>
          </td>

          <td align="center" bgcolor="<?=$cellcolor?>">
            <input type="text" id="fdest<?=$i?>" name="fdest<?=$i?>" value="<?=$pf['dest']?>" />
</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<select id="fprotocol<?=$i?>" name="fprotocol<?=$i?>">
						<? print(GetProtocolList($pf['protocol'])); ?>
						</select>
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="from<?=$i?>" name="from<?=$i?>" value="<?=$pf['from-port']?>" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="to<?=$i?>" name="to<?=$i?>" value="<?=$pf['to-port']?>" />
					</td>

          <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
          <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_pfwd('<?=$i?>')"><img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
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
						<select id="source<?=$i?>" name="source<?=$i?>">
							//print($fd_pfsources);
							<?
								foreach($fd_pfsources as $ip) {
									$selected = '';
									echo '<option value="'.$ip.'" '.$selected.'>'.$ip.'</option>';
								}
							?>
						</select>
          </td>

          <td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="fdest<?=$i?>" name="fdest<?=$i?>" value="" />
          </td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<select id="fprotocol<?=$i?>" name="fprotocol<?=$i?>">
						<? print(GetProtocolList('')); ?>
						</select>
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="from<?=$i?>" name="from<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="to<?=$i?>" name="to<?=$i?>" value="" />
					</td>

		<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
		<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
		<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>
	</tr>
</table>
<p><span class="descriptiontext">Port-forwards can be used to redirect traffic received on a specific firewall IP address to another host. Since the IP address needs to be static to create a port-forward, this option is not typically used with connections which have a DHCP or PPPoE assigned address. The auto-forwards are better suited for use when the public IP address is dynamically assigned.</span></p>
<p><span class="descriptiontext"><strong>Note: </strong>You will need to add an access list to permit the specified traffic to pass to the internal host. Please see the product documentation section on port-forwards for more information on the proper access-list format.</span>
    <br>
    <br>

</p>
<table width="100%" padding=0 spacing=0>
	<tr>
		<td class="labelcell" width="100%" colspan=8>
			<label><font size=2>Autoforwards</font></label>
		</td>
	</tr>
	<tr>
		<td class="labelcell"><label>Interface</label></td>
		<td class="labelcell" align="center"><label>Destination</label></td>
		<td class="labelcell" align="center"><label>Protocol</label></td>
		<td class="labelcell" align="center"><label>Start Port</label></td>
		<td class="labelcell" align="center"><label>End Port</label></td>
		<td class="labelcell" align="center"><label>Update</label></td>
		<td class="labelcell" align="center"><label>Delete</label></td>
		<td class="labelcell" align="center"><label>Add</label></td>
	</tr>
	<tr>
	<?
	    $i = 0;
      //loop through existing rules
      foreach($fd_afwds as $af) {
        if ($i % 2)
          $cellcolor = "#F5F5F5";
        else
          $cellcolor = "#FFFFFF";
        ?>
          <td bgcolor="<?=$cellcolor?>">
						<select id="interface<?=$i?>" name="interface<?=$i?>" width="100%">
							<option value="none" <?if($af['source']) print("selected");?> >none</option>
						<?
						//loop through interfaces
						foreach($configfile->interfaces as $ifentry) {
							//no downed intf allowed, duh
							if($ifentry['down']) continue;

							//if this is the chosen interface, make sure it is marked as selected
							if($ifentry['name'] == $af['interface'])
								$selected = "selected";
							else
								$selected = "";

							echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
						}
						?>
						</select>
          </td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="adest<?=$i?>" name="adest<?=$i?>" value="<?=$af['dest']?>" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<? //select box for protocols here (ONLY TCP/UDP) ?>
						<select id="aprotocol<?=$i?>" name="aprotocol<?=$i?>" width="100%">
							<option value="tcp" <?if($af['protocol'] == 'tcp') print("selected");?> >TCP</option>
							<option value="udp" <?if($af['protocol'] == 'udp') print("selected");?> >UDP</option>
						</select>
					</td>

					<? //construct start port and end port from port entry

						$ps = explode(':',$af['port']);
						if(is_array($ps)) {
							$asp = $ps[0];
							$aep = $ps[1];
						} else {
							$asp = $ps;
							$aep = '';
						}

					?>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="astartport<?=$i?>" name="astartport<?=$i?>" value="<?=$asp?>" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="aendport<?=$i?>" name="aendport<?=$i?>" value="<?=$aep?>" />
					</td>

          <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
          <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_afwd('<?=$i?>')"><img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
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
						<input type="text" id="adest<?=$i?>" name="adest<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<? //select box for protocols here (ONLY TCP/UDP) ?>
						<select id="aprotocol<?=$i?>" name="aprotocol<?=$i?>" width="100%">
							<option value="tcp" selected >TCP</option>
							<option value="tcp" >UDP</option>
						</select>
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="astartport<?=$i?>" name="astartport<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">
						<input type="text" id="aendport<?=$i?>" name="aendport<?=$i?>" value="" />
					</td>

					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
					<td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>
	</tr>
</table>
<p><span class="descriptiontext">Auto-forwards are well suited for firewalls with dynamic IP addresses. An auto-forward will redirect any traffic received on the specified interface that matches the protocol and port specification to the destination host. You do not need to add an additional access-list for auto-forwards to function properly. </span></p>
<p>
    <?
    if(strlen(query_warnings())) {
	    print(query_warnings());
    }
?>

</p>
</form>
<?
	include("includes/footer.php");
?>