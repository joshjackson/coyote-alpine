<?
	require_once("includes/loadconfig.php");
	$MenuTitle="Edit Interface ";
	$MenuType="INTERFACES";

	function is_enabled() {
		global $fd_disabled;

		return !$fd_disabled;
	}

	function is_disabled() {
		global $fd_disabled;

		return $fd_disabled;
	}

	$action = $_REQUEST['action'];
	$fd_idx = $_REQUEST['intfidx'];
	$fd_victim = $_REQUEST['to_kill'];
	$fd_addvlan = $_REQUEST['to_add'];

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']."?intfidx=".$fd_idx);

	//no need to load the form if the index we're passed doesn't exist
	if(is_null($fd_idx) || (!array_key_exists($fd_idx, $configfile->interfaces))) {
		header("Location:index.php");
		die;
	}

	if(!$action) {
		//gather information to populate
		$fd_intf = $configfile->interfaces[$fd_idx];

		//get addr array
		$fd_ipaddrs = $fd_intf['addresses'];

		//FIXME: die if we find none?

		$fd_addrcount = count($fd_ipaddrs);
		$fd_disabled = $fd_intf['down'];
		$fd_mtu = $fd_intf['mtu'];
		$fd_name = $fd_intf['name'];

		//set the menu data
		$MenuTitle="Edit Interface ".$fd_name;

		$fd_vlans = $fd_intf['vlans'];

		if($fd_intf['vlan']) {
			$fd_virtual = true;
			$fd_mode = 'static';
		} else {
			$fd_virtual = false;
		}

		if(is_array($fd_intf['addresses'])) {
			//deal with each address
			$fd_ipaddrs = array();
			foreach($fd_intf['addresses'] as $addr) {
				if(!is_array($addr)) continue;
				$fd_ipaddrs[count($fd_ipaddrs)] = $addr['ip'];
			}

			if(is_ipaddrblockopt($fd_mode)) {
				$fd_ipaddr = $fd_mode;
				$fd_mode = 'static';
			}
		} else {
			//deal with addressing mode, dhcp, pppoe
			$fd_ipaddrs = $fd_intf['addresses'];

			//this addr may be an array as well.
			if(is_array($fd_ipaddrs[0])) {
				$fd_ipaddr = $fd_ipaddrs[0]['ip'];
				$fd_mode = 'static';
			} else {
				$fd_mode = $fd_ipaddrs;
			}

			//last chance
			if(!$fd_mode) $fd_mode = 'static';
		}

		//detect bridging
		if(!is_null($fd_intf['bridge']) && $fd_intf['bridge'])
			$fd_mode = 'bridge';

		$fd_mac = $fd_intf['mac'];
		$fd_addrcount = count($fd_ipaddrs);
		$fd_vlancount = count($fd_vlans);

		//FIXME: DHCP Hostname is NOT stored! ::jc

		$fd_pppoeusername = $configfile->pppoe['username'];
		$fd_pppoepassword = $configfile->pppoe['password'];

	} else if ($action == 'apply') {
		$fd_mode = $_REQUEST['ConfigType'];
		$fd_mac = $_REQUEST['fd_mac'];
		$fd_mtu = $_REQUEST['fd_mtu'];

		if($_REQUEST['intf_enabled'] == 'on')
			$fd_disabled = false;
		else
			$fd_disabled = true;

		$fd_dhcphostname = $_REQUEST['dhcp_hostname'];
		$fd_pppoeusername = $_REQUEST['pppoe_username'];
		$fd_pppoepassword = $_REQUEST['pppoe_password'];
		$fd_ipaddr = $_REQUEST['fd_ipaddr'];

		$addrcount = $_REQUEST['addrcount'];
		$vlancount = $_REQUEST['vlancount'];
		$vlctest = $vlancount - 1;
		$addrctest = $addrcount - 1;

		if (!$_REQUEST['vlan'.$vlctest])
			$vlancount --;

		if (!$_REQUEST['addr'.$addrctest])
			$addrcount --;

		$fd_virtual = $_REQUEST['is_virtual'];

		//If the new type requires dynamic IP addr assignment (pppoe, dhcp) then
		//ensure that there are NO other interfaces with dynamic assignment
		if($fd_mode == 'pppoe' || $fd_mode == 'dhcp') {
			$i = 0;
			foreach($configfile->interfaces as $cintf) {
				if($i != $fd_idx && ($cintf['addresses'] == 'pppoe' || $cintf['addresses'] == 'dhcp')) {
					add_critical("Cannot address this interface as '".$fd_mode."', another interface is already dynamically assigned.");
					break;
				}
			}
			$i++;
		}

		if(strlen($fd_mtu) && !intval($fd_mtu)) {
		  add_critical("Invalid MTU: ".$fd_mtu." is not a number.");
		}

		if(strlen($fd_mac) && !is_macaddr($fd_mac)) {
		  add_critical("Invalid MAC addr: ".$fd_mac);
		}

		if($fd_virtual || $fd_mode == 'static') {

			//ip addrs, first the primary
			$fd_addrs = array();
			$ip = $fd_ipaddr;
			if(is_ipaddrblockopt($ip)) {
				exec("ipcalc -p ".$ip." -s 1> /dev/null", $outstr, $errcode);
				if ($errcode) {
					$ipc=run_ipcalc("-m ".$ip." -s");
					$NETMASK=$ipc["NETMASK"];
					$ipc=run_ipcalc("-p -b ".$ip." $NETMASK -s");
					$addr_entry = array(
						"ip" => $ip."/".$ipc["PREFIX"],
						"broadcast" => $ipc["BROADCAST"]
					);
				} else {
					$ipc=run_ipcalc("-b ".$ip." -s");
					$addr_entry = array(
						"ip" => $ip,
						"broadcast" => $ipc["BROADCAST"]
					);
				}
				$fd_addrs[count($fd_addrs)] = $addr_entry;
			} else {
			  add_critical("Invalid IP addr: ".$ip);
			}

			//next the loop, to pick up the secondaries
			for($i=0;$i < $addrcount;$i++) {
				$ip = $_REQUEST['addr'.$i];

				//skip empties
				if(!strlen($ip)) continue;

				if(!is_ipaddrblockopt($ip)) {
				  add_critical("Invalid IP addr: ".$ip);
				} else {
					exec("ipcalc -p ".$ip." -s 1> /dev/null", $outstr, $errcode);
					if ($errcode) {
						$ipc=run_ipcalc("-m ".$ip." -s");
						$NETMASK=$ipc["NETMASK"];
						$ipc=run_ipcalc("-p -b ".$ip." $NETMASK -s");
						$addr_entry = array(
							"ip" => $ip."/".$ipc["PREFIX"],
							"broadcast" => $ipc["BROADCAST"]
						);
					} else {
						$ipc=run_ipcalc("-b ".$ip." -s");
						$addr_entry = array(
							"ip" => $ip,
							"broadcast" => $ipc["BROADCAST"]
						);
					}
					$fd_addrs[count($fd_addrs)] = $addr_entry;
				}
			}
		}

		$fd_addrcount = count($fd_ipaddrs);

		//vlans
		if($vlancount && !$fd_victim && $fd_addvlan) {
			$vlans = array();

			//get all the other vlan id entries
			$tvlans = array();
			foreach($configfile->interfaces as $intf) {
				if(is_array($intf['vlans'])) {
					//use vlan id (i.e. 1.2, 2.3, etc) as key for comparison to any new vlans added
					foreach($intf['vlans'] as $vl) {
						if($vl)
							$tvlans[$vl] = true;
					}
				}
			}

			$ifentry = false;
			for($i=0; $i < $vlancount; $i++) {
				$vlan = $_REQUEST['vlan'.$i];

				if(!strlen($vlan)) continue;

				if(strlen($vlan) && !intval($vlan)) {
				  add_critical("Invalid VLAN ID: ".$vlan." is not a number.");
					continue;
				}

				//FIXME: this probably needs to add a whole new interface for a new vlan, right? :\
				if(array_key_exists($vlan, $tvlans)) {
				  add_critical("Invalid VLAN ID: ".$vlan." must be unique!");
				} else {
					$vlans[count($vlans)] = $vlan;
					$ifentry = array(
						"module" => '',
						"bridge" => false,
						"vlan" => true,
						"vlanid" => $vlan,
						"mtu" => 1500,
						"name" => "eth".$fd_idx.".".$vlan,
						"device" => "eth".$fd_idx.".".$vlan,
						"addresses" => array(),
						"down" => false,
						"export" => true
					);
				}
			}
			if(count($vlans) > 0) $fd_virtual = false;
		}

		//FIXME: If the addressing mode was changed away from static AND this intf has
		//       vlans associated, the children MUST be removed from the list!
		if($vlancount > 0 && $fd_mode != 'static') {
			$vlans = $configfile->interfaces[$fd_idx]['vlans'];
			$target = count($vlans);
			$hitlist = array();

			//loop through each interface in the configfile
			for($i = 0; $i < count($configfile->interfaces); $i++) {
				//and then each vlan
				$if = $configfile->interfaces[$i];
				if(!$if['vlanid']) continue;
				foreach($vlans as $rvlk => $rvld) {
					$n = $if['vlanid'];
					if($rvlk == $n) {
						array_push($hitlist, $i);
						$target--;
					}
				}
				if(!$target) break;
			}
			unset($configfile->interfaces[$fd_idx]['vlans']);
			foreach($hitlist as $hit) unset($configfile->interfaces[$hit]);
		}


		// FIXME: Need to test if the hostname is valid ONLY if it was specified.
		if($fd_mode == 'dhcp') {
//			if(!strlen($fd_dhcphostname)) {
//				$fd_invalid++;
//				$fd_warnings = $fd_warnings." DHCP hostname must be set if DHCP is enabled.<br>";
//			}
		}

		if($fd_mode == 'pppoe') {
			if(!strlen($fd_pppoeusername)) {
			  add_critical("Invalid PPPoE username: cannot be empty.");
			}
			if(!strlen($fd_pppoepassword)) {
			  add_critical("Invalid PPPoE password: cannot be empty.");
			}
			$configfile->pppoe['username'] = $fd_pppoeusername;
			$configfile->pppoe['password'] = $fd_pppoepassword;
		}

		//assign
		$configfile->interfaces[$fd_idx]['vlan'] = $fd_virtual;

		if($fd_virtual)
			$configfile->interfaces[$fd_idx]['vlanid'] = $vlans;

		$configfile->interfaces[$fd_idx]['mac'] = $fd_mac;
		$configfile->interfaces[$fd_idx]['down'] = is_disabled();
		$configfile->interfaces[$fd_idx]['mtu'] = $fd_mtu;

		if($fd_mode == 'static') {
			$configfile->interfaces[$fd_idx]['addresses'] = $fd_addrs;
		} else {
			$configfile->interfaces[$fd_idx]['addresses'] = $fd_mode;
		}

		if($fd_mode == 'bridge')
			$configfile->interfaces[$fd_idx]['bridge'] = true;
		else
			$configfile->interfaces[$fd_idx]['bridge'] = false;

		//And I *really* hope this works
		if($fd_victim) {
			unset($configfile->interfaces[$fd_idx]['vlans'][$fd_victim]);
			$fd_vicname = $configfile->interfaces[$fd_idx]['name'].".".$fd_victim;
			for($i = 0; $i < count($configfile->interfaces); $i++) {
				if($configfile->interfaces[$i]['name'] == $fd_vicname) {
					unset($configfile->interfaces[$i]);
					break;
				}
			}
		}

		if($ifentry && !$fd_victim) {
			$configfile->interfaces[count($configfile->interfaces)] = $ifentry;
			$configfile->interfaces[$fd_idx]['vlans'][$vlan] = array();
		}

		if(query_invalid()) {
			add_warning("<hr>".query_invalid()." parameters could not validated.");
		} else {
			$configfile->dirty["interfaces"] = true;
			WriteWorkingConfig();
			header("Location:interface_settings.php");
			die;
		}

		if(!query_invalid()) {
			header("Location:interface_settings.php");
			die;
		}
	}

	include("includes/header.php");

	if(!strlen($fd_mode)) $fd_mode = 'static';

	$fd_vlancount++;
	if($fd_mode != 'static') $fd_addrcount++;
?>
<script language="javascript">
	function f() { return document.forms[0]; }
	function ac() { return <?=$fd_addrcount?>; }
	function vc() { return <?=$fd_vlancount?>; }
	function has_vlans() { return (vc() > 1 ? true : false); }

	function confirm_vlan_del() {
		var msg;
		msg = 'This interface has vlans assigned to it!\n'+
			'By changing the addressing mode of this interface away from static, '+
			'Wolverine MUST delete the child vlan interfaces (when this form is posted) '+
			'that are associated with this one.  Is the delete action acceptable?';
		return confirm(msg);
	}

	function all_static(amode) {
		//loop through all static addrs and set readonly/disabled to mode
		c = ac();
		for(i=0;i < c;i++) {
			nm = ('addr'+i);
			try { f().elements[nm].readonly = amode; } catch(er) { }
			try { f().elements[nm].disabled = amode; } catch(er) { }
		}
	}

	function all_vlans(amode) {
		//loop through all vlans and set readonly/disabled to mode
		c = vc();
		for(i=0;i < c;i++) {
			nm = ('vlan'+i);
			try { f().elements[nm].readonly = amode; } catch(er) { }
			try { f().elements[nm].disabled = amode; } catch(er) { }
		}
	}

	function disable_dhcp() {

		if(!f().dhcp_hostname) return;

		f().dhcp_hostname.value = '';
		f().dhcp_hostname.readonly = true;
		f().dhcp_hostname.disabled = true;
	}

	function disable_pppoe() {
		if(!f().pppoe_username) return;

		f().pppoe_username.value = '';
		f().pppoe_username.readonly = true;
		f().pppoe_username.disabled = true;

		f().pppoe_password.value = '';
		f().pppoe_password.readonly = true;
		f().pppoe_password.disabled = true;
	}

	function disable_static() {
		f().fd_ipaddr.value = '';
		f().fd_ipaddr.readonly = true;
		f().fd_ipaddr.disabled = true;
		all_static(true);
		all_vlans(true);
	}

	function disable_bridge() {
		//FIXME: wtf?
		enable_static();
	}

	function enable_static() {
		//disable dhcp
		disable_dhcp();

		//disable pppoe
		disable_pppoe();

		//enable static
		f().fd_ipaddr.readonly = false;
		f().fd_ipaddr.disabled = false;

		all_static(false);
		all_vlans(false);
	}

	function enable_dhcp() {
		//disable pppoe
		disable_pppoe();

		//disable static
		disable_static();

		//enable dhcp
		f().dhcp_hostname.readonly = false;
		f().dhcp_hostname.disabled = false;
	}

	function enable_pppoe() {
		//disable dhcp
		disable_dhcp();

		//disable static
		disable_static();

		//enable pppoe
		f().pppoe_username.readonly = false;
		f().pppoe_username.disabled = false;
		f().pppoe_password.readonly = false;
		f().pppoe_password.disabled = false;
	}

	function enable_bridging() {
		//FIXME: disable all
		disable_static();
		disable_dhcp();
		disable_pppoe();
		all_static(true);
		all_vlans(true);
	}

	function init(mode) {
		if(mode == 'static') enable_static();
		if(mode == 'dhcp') enable_dhcp();
		if(mode == 'pppoe') enable_pppoe();
		if(mode == 'bridge') enable_bridging();
	}

	function change() {
		ob = document.forms[0].ConfigType;
		val = ob.value;

		if(val != 'static' && has_vlans() && !confirm_vlan_del()) {
			val = 'static';
			return;
		}

		document.forms[0].mode.value = val;
		init(val);
	}

	function delete_item(id) {
		if(confirm('Are you sure you want to delete this item?')) {
			f().elements[id].value = '';
			f().submit();
		}
	}

	function delete_vlan(id) {
		v = f().elements['vlan'+id].value;
		if(confirm('Are you sure you want to delete this vlan?  Note: this will also remove an interface.')) {
			f().elements['to_kill'].value = v;
			f().elements['vlan'+id].value = '';
			f().submit();
		}
	}

	function add_vlan() {
		f().elements['to_add'].value = '1';
		f().submit();
	}

</script>

<form id="content" method="post" action="<?=$_SERVER['PHP_SELF'];?>?intfidx=<?=$fd_idx?>">
  <input type="hidden" name="action" value="apply" />
  <input type="hidden" name="mode" value="<?= $fd_mode ?>" />
  <input type="hidden" name="addrcount" value="<?=count($fd_ipaddrs)?>" />
  <input type="hidden" name="vlancount" value="<?=$fd_vlancount?>" />
  <input type="hidden" name="is_virtual" value="<?=$fd_virtual?>" />
  <input type="hidden" name="to_kill" value="" />
  <input type="hidden" name="to_add" value="" />
  <table border="0" width="100%" id="table2">
    <tr>
      <td class="labelcell" nowrap><label>Interface configuration method:</label></td>
      <td width="100%"><? if($fd_virtual) { ?>
        <select size="1" name="ConfigType" onchange="change()">
          <option selected value="static">Static IP Address</option>
        </select>
        <? } else { ?>
        <select size="1" name="ConfigType" onchange="change()">
          <option <? if($fd_mode == 'static') print("selected"); ?> value="static">Static IP Address</option>
          <option <? if($fd_mode == 'dhcp') print("selected"); ?> value="dhcp">DHCP Assigned Address</option>
          <option <? if($fd_mode == 'pppoe') print("selected"); ?> value="pppoe">PPPoE Assigned Address</option>
          <option <? if($fd_mode == 'bridge') print("selected"); ?> value="bridge">Enable Bridging</option>
        </select>
        <? } ?>
        <br>
        <span class="descriptiontext">The configuration method used to assign an address to this firewall interface. Once one interface may be assigned a dynamic (DHCP or PPPoE) address. If an interface is configured for dynamic address assignment, secondary addresses and VLAN sub-interfaces can not be added. If bridging is enabled for this interface, an address can not be directly assigned to it.</span></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>Enabled:</label></td>
      <td width="100%"><input type="checkbox" name="intf_enabled" <? if(is_enabled()) print("checked"); ?> /></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>Interface MTU:</label></td>
      <td width="100%"><input type="text" name="fd_mtu" size="20" value="<?=$fd_mtu?>" />
        <br>
        <span class="descriptiontext">The maximum transmit unit size for this interface. Unless absolutely required, this value should be left at the default setting of 1500.</span></td>
    </tr>
    <? if(!$fd_virtual) { ?>
    <tr>
      <td class="labelcell" nowrap><label>Hardware address:</label></td>
      <td width="100%"><input type="text" name="fd_mac" size="20" value="<?=$fd_mac?>" />
        <br>
        <span class="descriptiontext">The MAC address to report for this interface. This option can be used for MAC &quot;spoofing&quot; if your ISP requires a specific MAC address to be registered to obtain Internet access. Do not assign a MAC address to this interface if the same MAC is used by another device on your network.</span></td>
    </tr>
    <? } ?>
    <tr>
      <td colspan="2" nowrap>&nbsp;</td>
    </tr>
    <? if(!$fd_virtual) { ?>
    <tr>
      <td colspan="2" class="labelcellmid" nowrap><label><font size="2">DHCP Client Configuration</font></label></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>DHCP Hostname:</label></td>
      <td width="100%"><input type="text" name="dhcp_hostname" size="20" value="<?=$fd_dhcphostname?>"/>
        <br>
        <span class="descriptiontext">Some ISP's require that the client's hostname be sent with the DHCP request. If your ISP requires this, you can specify the hostname to be sent when requesting an IP address. If your ISP does not require this, simply leave the entry blank.</span></td>
    </tr>
    <tr>
      <td colspan="2" nowrap>&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2" class="labelcellmid" nowrap><label><font size="2">PPPoE Client Configuration</font></label></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>PPPoE Username:</label></td>
      <td width="100%"><input type="text" name="pppoe_username" size="20" value="<?=$fd_pppoeusername?>" /></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>PPPoE Password:</label></td>
      <td width="100%"><input type="password" name="pppoe_password" size="20" value="<?=$fd_pppoepassword?>" /></td>
    </tr>
    <tr>
      <td colspan=2 nowrap>&nbsp;</td>
    </tr>
    <? } ?>
    <tr>
      <td colspan="2" class="labelcellmid" nowrap><label><font size="2">Static IP Address Configuration</font></label></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>IP Address:</label></td>
      <td width="100%"><input type="text" name="fd_ipaddr" size="20" value="<?=$fd_ipaddrs[0]?>" />
        <?
				//hateful
				if($fd_mode == 'static' && count($fd_ipaddrs)) {
					$i = 0;
					foreach($fd_ipaddrs as $addr) {
						if($i) $realaddrs[count($realaddrs)] = $addr;
						$i++;
					}
					$fd_ipaddrs = $realaddrs;
				}
		?>
        <br>
        <span class="descriptiontext">The primary static IP address for this interface. The address should be in address/prefix notation.<br>
        <i>Example: 192.168.0.1/24</i></span></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap><label>Secondary Addresses:</label></td>
      <td width="100%"><table width="100%">
          <tr>
            <td class="labelcell" width="100%"><label>IP Addr</label></td>
            <td class="labelcell" align="center"><label>Update</label></td>
            <td class="labelcell" align="center"><label>Delete</label></td>
            <td class="labelcell" align="center"><label>Add</label></td>
          </tr>
          <tr>
            <?
        $i = 0;
        if($fd_mode == 'static' && count($fd_ipaddrs)) {
	        foreach($fd_ipaddrs as $addr) {
	          if($i % 2)
	            $cellcolor = "#F5F5F5";
	          else
	            $cellcolor = "#FFFFFF";

	          //output with script breaks first, then convert to print() calls
	          ?>
            <td align="left"   bgcolor="<?=$cellcolor?>"><input type="text" id="addr<?=$i?>" name="addr<?=$i?>" value="<?=$addr?>" />
              <? if(strlen($addr)) mark_valid(is_ipaddrblockopt($addr)) ?>
            </td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_item('addr<?=$i?>')"><img src="images/icon-del.gif" width="16" height="16"></a></td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
          </tr>
          <tr>
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
            <td align="left"   bgcolor="<?=$cellcolor?>" nowrap><input type="text" id="addr<?=$i?>" name="addr<?=$i?>" value="" />
            </td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a> </td>
          </tr>
        </table></td>
    </tr>
    <? if(!$fd_virtual) {?>
    <tr>
      <td class="labelcell" nowrap><label>802.1q VLAN Sub-interfaces:</label></td>
      <td width="100%"><table width="100%">
          <tr>
            <td class="labelcell" width="100%"><label>VLAN ident</label></td>
            <td class="labelcell" align="center"><label>Update</label></td>
            <td class="labelcell" align="center"><label>Delete</label></td>
            <td class="labelcell" align="center"><label>Add</label></td>
          </tr>
          <tr>
            <?
			//loop through vlan list, then add one empty
			$i = 0;
			if(count($fd_vlans)) {
				foreach($fd_vlans as $key => $value) {
					if($i % 2)
						$cellcolor = "#F5F5F5";
					else
						$cellcolor = "#FFFFFF";

					?>
            <td align="left"   bgcolor="<?=$cellcolor?>"><input type="text" id="vlan<?=$i?>" name="vlan<?=$i?>" value="<?=$key?>" /></td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_vlan('<?=$i?>')"><img src="images/icon-del.gif" width="16" height="16"></a></td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
          </tr>
          <tr>
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
            <td align="left"   bgcolor="<?=$cellcolor?>" nowrap><input type="text" id="vlan<?=$i?>" name="vlan<?=$i?>" value="" />
            </td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
            <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
            <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:add_vlan();"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a> </td>
          </tr>
        </table></td>
    </tr>
    <? } ?>
  </table>
  <? print(query_warnings()); ?>
</form>
<script language="javascript">
		init('<?=$fd_mode?>');
</script>
<?
	include("includes/footer.php");
?>
