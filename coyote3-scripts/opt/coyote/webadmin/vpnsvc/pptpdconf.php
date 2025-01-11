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
		pptp require following data:
		enable status
		local-address ipaddr
		address-pool ipaddr/block
		dns array (2)
		wins array (2)
		user array (open) //each element array ("uid" => string, "pwd" => string) link to this, do not edit it here
		hosts array(open)
	*/

	$MenuTitle="PPTP Server";
	$MenuType="VPN";

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

  function is_enabled() {
    global $fd_enabled;

	  if($fd_enabled == 'on')
		  return true;
  	else
	  	return false;
  }

	//did we freshly load this page or are we loading on result of a post
	if(strlen($_POST['postcheck']))
		$fd_posted = true;
	else
		$fd_posted = false;

  //fill values from _POST or configfile
	if($fd_posted) {

		//form values
		$fd_enabled = $_POST['enabled'];
		$fd_hostcount = $_POST['hostcount'];
		$fd_localaddress = $_POST['local-address'];
		$fd_addresspoolstart = $_POST['address-pool-start'];
		$fd_addresspoolend = $_POST['address-pool-end'];
		$fd_localaddress = $_POST['local-address'];
		$fd_addresspool = $_POST['address-pool'];
		$fd_proxyarp = $_POST['proxyarp'];

		//dns
		$fd_dns1 = $_POST['dns1'];
		$fd_dns2 = $_POST['dns2'];

		//wins
		$fd_wins1 = $_POST['wins1'];
		$fd_wins2 = $_POST['wins2'];

		//host list
		$fd_hostlist = array();
		for($hc = 0; $hc < $fd_hostcount; $hc++)
		  if(strlen($_POST['host'.$hc])) $fd_hostlist[count($fd_hostlist)] = $_POST['host'.$hc];

    $fd_hostcount = count($fd_hostlist);

	} else {

		if($vpnconf->pptp['enable'])
			$fd_enabled = 'on';
		else
			$fd_enabled = '';

		list($fd_addresspoolstart, $fd_addresspoolend) = explode(":", $vpnconf->pptp['address-pool']);
		$fd_localaddress = $vpnconf->pptp['local-address'];
		$fd_hostcount = count($vpnconf->pptp['hosts']);
		$fd_hostlist = $vpnconf->pptp['hosts'];
		$fd_dns1 = $vpnconf->pptp['dns'][0];
		$fd_dns2 = $vpnconf->pptp['dns'][1];
		$fd_wins1 = $vpnconf->pptp['wins'][0];
		$fd_wins2 = $vpnconf->pptp['wins'][1];
		$fd_proxyarp = ($vpnconf->pptp['proxyarp']) ? 'checked' : '';
  }

	//validate each host?
	if(count($fd_hostlist)) {
		foreach($fd_hostlist as $chost) {

			//validate
			if(!is_ipaddrblockopt($chost)) {
			  add_critical("Invalid IP addr: ".$chost);
			}
		}
	}

	//validate all

	//address-pool
	if(is_enabled() && (!strlen($fd_addresspoolstart) || !is_ipaddr($fd_addresspoolstart))) {
	  add_critical("Invalid IP addr: ".$fd_addresspoolstart);
	}
	if(is_enabled() && (!strlen($fd_addresspoolend) || !is_ipaddr($fd_addresspoolend))) {
	  add_critical("Invalid IP addr: ".$fd_addresspoolend);
	}

	//local-address
	if(is_enabled() && (!strlen($fd_localaddress) || !is_ipaddr($fd_localaddress))) {
	  add_critical("Invalid IP addr: ".$fd_localaddress);
	}

	//dns1
	if(strlen($fd_dns1) && !is_ipaddr($fd_dns1)) {
	  add_critical("Invalid IP addr: ".$fd_dns1);
	}

	//dns2
	if(strlen($fd_dns2) && !is_ipaddr($fd_dns2)) {
	  add_critical("Invalid IP addr: ".$fd_dns2);
	}

	//wins1
	if(strlen($fd_wins1) && !is_ipaddr($fd_wins1)) {
	  add_critical("Invalid IP addr: ".$fd_wins1);
	}

	//wins2
	if(strlen($fd_wins2) && !is_ipaddr($fd_wins2)) {
	  add_critical("Invalid IP addr: ".$fd_wins2);
	}

  //display warnings, if any entries are invalid
	if(query_invalid()) {
		//always display warnings
		add_warning("<hr>Wolverine encountered ".query_invalid()." parameters that could not be validated.");

		//and mention that no changes were written if we were posted
		if($fd_posted)
			add_warning("No changes were made to the working configfile.");
	} else {
		if($fd_posted) {

		  	//clear hosts array, it will be rebuilt if needed
		  	$vpnconf->pptp['hosts'] = array();
			$vpnconf->pptp['wins'] = array();
			$vpnconf->pptp['dns'] = array();

		  //can always mark enable (enabled/disabled)
		  $vpnconf->pptp['enable'] = is_enabled();

		  if(is_enabled()) {

		    $vpnconf->pptp['local-address'] = $fd_localaddress;
		    $vpnconf->pptp['address-pool'] = $fd_addresspoolstart.":".$fd_addresspoolend;
		    $vpnconf->pptp['hosts'] = $fd_hostlist;
		    $vpnconf->pptp['wins'][0] = $fd_wins1;
		    $vpnconf->pptp['wins'][1] = $fd_wins2;
		    $vpnconf->pptp['dns'][0] = $fd_dns1;
		    $vpnconf->pptp['dns'][1] = $fd_dns2;
			$vpnconf->pptp['proxyarp'] = ($fd_proxyarp == 'checked') ? true : false;
		  } else {
		    //clear all (hosts, dns, and wins already clear)
		    $vpnconf->pptp['local-address'] = '';
		    $vpnconf->pptp['address-pool'] = '';
		  }

		  //write config
		  	$vpnconf->dirty["pptp"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

		}
	}

	$fd_hostcount++;

	include("../includes/header.php");

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
	<input type="hidden" id="postcheck" name="postcheck" value="form was posted">
	<input type="hidden" id="hostcount" name="hostcount" value="<?=$fd_hostcount?>">

	<table cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<td class="labelcellmid" nowrap>
			<input type="checkbox" name="enabled" <? if(is_enabled()) print("checked")?>></td>
			<td class="labelcellmid" nowrap width="100%">
			<label>Enable the Wolverine PPTP Service</label></td>
		</tr>
	</table>

	<table cellpadding="3" cellspacing="3" width="100%">
		<tr>
			<td class="labelcell" nowrap>
				<label>Local-address:</label>
			</td>
			<td class="ctrlcell">
				<input type="text" id="local-address" name="local-address" value="<?=$fd_localaddress;?>" size="20">
				<? if(is_enabled()) print(mark_valid(is_ipaddr($fd_localaddress)));?>
				<br>
				<span class="descriptiontext">This is the local-address used by the PPTP service. This address needs to be part of the same network as the address pool but should not be contained in the pool's range of addresses. <br>
				Example: <i>192.168.0.99</i></span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap>
				<label>Address-pool:</label>
			</td>
		  <td class="ctrlcell">
				<input type="text" id="address-pool-start" name="address-pool-start" value="<?=$fd_addresspoolstart;?>" size="20">
				<? if(is_enabled()) print(mark_valid(is_ipaddr($fd_addresspoolstart)));?>
				<br><br>
				<input type="text" id="address-pool-end" name="address-pool-end" value="<?=$fd_addresspoolend;?>" size="20">
				<? if(is_enabled()) print(mark_valid(is_ipaddr($fd_addresspoolend)));?>
				<br>
			  <span class="descriptiontext">This is the range of addresses given to connecting PPTP clients. This address should be on the same network as the local-address but should not contain it. <br>
			  Example: <i><br>
			  192.168.0.100 <br>
			  192.168.0.200 </i></span></td>
		</tr>

		<!-- dns 1 and 2 -->
		<tr>
			<td class="labelcell" nowrap>
				<label>Name servers:</label>
			</td>
			<td class="ctrlcell">
				<input type="text" id="dns1" name="dns1" value="<?=$fd_dns1?>" size="20">
				<? if(strlen($fd_wins2)) print(mark_valid(is_ipaddr($fd_dns1)));?>
				<br><br>
				<input type="text" id="dns2" name="dns2" value="<?=$fd_dns2?>" size="20">
				<? if(strlen($fd_wins2)) print(mark_valid(is_ipaddr($fd_dns1)));?>
				<br>
				<span class="descriptiontext">An optional set of DNS server addresses to be given to connecting PPTP clients. <br>
				Example: <i>192.168.0.6</i></span>
			</td>
		</tr>

		<!-- wins 1 and 2 -->
		<tr>
			<td class="labelcell" nowrap>
				<label>Wins servers:</label>
			</td>
			<td class="ctrlcell">
				<input type="text" id="wins1" name="wins1" value="<?=$fd_wins1?>" size="20">
				<? if(strlen($fd_wins1)) print(mark_valid(is_ipaddr($fd_wins1)));?>
				<br><br>
				<input type="text" id="wins2" name="wins2" value="<?=$fd_wins2?>" size="20">
				<? if(strlen($fd_wins2)) print(mark_valid(is_ipaddr($fd_wins2)));?>
				<br>
				<span class="descriptiontext">Optional WINS server addresses to be given to connecting PPTP clients.<br>
				Example: <i>192.168.0.6</i></span>
			</td>
		</tr>
		<!-- Proxyary -->
		<tr>
			<td class="labelcell" valign="top" nowrap>
				<label>Enable Proxyarp:</label>
			</td>
			<td class="ctrlcell">
				<input type="checkbox" name="proxyarp" value="checked" <?=$fd_proxyarp?>>
				<br>
				<span class="descriptiontext">If your local and remote addresses are part of one of the local subnets attached to this firewall, you will want to enable proxyarp. This will eliminate the need to add static routes on the client and will cause the client to appear as any other directly connect LAN host. </span></td>
		</tr>
	</table>

<br>
		The following can be used to specify a list of hosts or networks which
	are permitted to connect to the PPTP service. These adresses can be a single
	IP address or a network specification in the form<i> address/prefix. </i><b>
	<br>
	Note: </b>If no hosts are specified, this service defaults to allowing
	connections from any host.<br>
	<table width="60%">
		<!-- insert a table, as to list hosts (always at least one) -->
		<tr>
          <td class="labelcell" width="100%"><label>Host IP Addr</label></td>
          <td class="labelcell" align="center"><label>Update</label></td>
          <td class="labelcell" align="center"><label>Delete</label></td>
          <td class="labelcell" align="center"><label>Add</label></td>
        </tr>
        <tr>

        <?
          //loop through host list, then add one empty
          $i = 0;
          if(count($fd_hostlist)) {
            foreach($fd_hostlist as $host) {

              if($i % 2)
                $cellcolor = "#F5F5F5";
              else
                $cellcolor = "#FFFFFF";

              //output with script breaks first, then convert to print() calls
              ?>
              <td align="left"   bgcolor="<?=$cellcolor?>"><input type="text" id="host<?=$i?>" name="host<?=$i?>" value="<?=$host?>" />
              <? if(strlen($host)) mark_valid(is_ipaddr($host)) ?>
              </td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="/images/icon-chk.gif" width="16" height="16"></a></td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_item('host<?=$i?>')"><img src="/images/icon-del.gif" width="16" height="16"></a></td>
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
        <td align="left"   bgcolor="<?=$cellcolor?>" nowrap><input type="text" id="host<?=$i?>" name="host<?=$i?>" value="" /></td>
        <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
        <td align="center" bgcolor="<?=$cellcolor?>">&nbsp;</td>
        <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="/images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>

	</table>

	<table cellpadding="3" cellspacing="0" width="100%">

    <? print("<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>"); ?>

	</table>


</form>
<?
	include("../includes/footer.php");
?>