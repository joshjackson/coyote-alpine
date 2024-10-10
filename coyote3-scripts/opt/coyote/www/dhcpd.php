<?
	require_once("includes/loadconfig.php");
	//dhcpd.php
	//
	//configure dchp service

	//interface rules for selection
    //$configfile->interfaces[i]['name'] is the choice
	//							'bridge' element MUST be false to be included in list
	//							'down' element MUST be false to be included in list


	$MenuTitle="DHCP Service";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";

	include("includes/header.php");

	//define our buttons
	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "dhcpd.php");

	//get configfile and _POST data


	//did we freshly load this page or are we loading on result of a post
	if(strlen($_POST['postcheck']))
		$fd_posted = true;
	else
		$fd_posted = false;

	$fd_enabled = $_POST['dhcpenabled'];

function is_enabled() {

	global $fd_enabled;

	if($fd_enabled == 'on')
		return true;
	else
		return false;
}

	//note: interface being populated in configfile->dhcpd
	//      acts to indicate if dhcp is enabled


	//posted, checkbox was disabled, interface was filled: clear interface and disable
	if($fd_posted && !is_enabled() && $_POST['interface']) {
		$fd_interface = 'none';
	}

	//posted, checkbox was enabled, interface was filled: validate and attempt to enable
	if($fd_posted && is_enabled() && $_POST['interface']) {
		$fd_interface = $_POST['interface'];
	}

	//not posted: fill all from configfile
	if($fd_posted == false) {
		$fd_interface = $configfile->dhcpd['interface'];

		//interface choice should force checkbox enabled
		if($fd_interface)
			$fd_enabled = "on";
		else
			$fd_enabled = "off";
	}

	if($_POST['start'])
		$fd_start = $_POST['start'];
	else
		$fd_start = $configfile->dhcpd['start'];

	if($_POST['end'])
		$fd_end = $_POST['end'];
	else
		$fd_end = $configfile->dhcpd['end'];

	if($_POST['DNS1'])
		$fd_dns1 = $_POST['DNS1'];
	else
		$fd_dns1 = $configfile->dhcpd['dns'][0];

	if($_POST['DNS2'])
		$fd_dns2 = $_POST['DNS2'];
	else
		$fd_dns2 = $configfile->dhcpd['dns'][1];

	if($_POST['WINS1'])
		$fd_wins1 = $_POST['WINS1'];
	else
		$fd_wins1 = $configfile->dhcpd['wins'][0];

	if($_POST['WINS2'])
		$fd_wins2 = $_POST['WINS2'];
	else
		$fd_wins2 = $configfile->dhcpd['wins'][1];

	if($_POST['lease'])
		$fd_lease = $_POST['lease'];
	else
		$fd_lease = $configfile->dhcpd['lease'];

	if($_POST['router'])
		$fd_router = $_POST['router'];
	else
		$fd_router = $configfile->dhcpd['router'];

	if($_POST['subnet'])
		$fd_subnet = $_POST['subnet'];
	else
		$fd_subnet = $configfile->dhcpd['subnet'];

	if($_POST['domain'])
		$fd_domain = $_POST['domain'];
	else
		$fd_domain = $configfile->dhcpd['domain'];

    //get a count of reservations
    $fd_reservations = count($configfile->dhcpd['reservations']);

	//if user is attempting to disable the service, do not force validation?
	//I will be interested to know how they got the thing submitted to enable
	//dhcp without valid entries, but I suppose they could edit the config
	//via the shell interface.

	//domain
	if(is_enabled() && !is_domain($fd_domain)) {
	  add_critical("Invalid domain: ".$fd_domain);
	}

	//pool start
	if(is_enabled() && !is_ipaddr($fd_start)) {
	  add_critical("Invalid IP addr: ".$fd_start);
	}

	//pool end
	if(is_enabled() && !is_ipaddr($fd_end)) {
	  add_critical("Invalid IP addr: ".$fd_end);
	}

	//subnet
	if(is_enabled() && !is_ipaddr($fd_subnet)) {
	  add_critical("Invalid IP addr: ".$fd_subnet);
	}

	//primary dns
	if(strlen($fd_dns1) && !is_ipaddr($fd_dns1)) {
	  add_critical("Invalid IP addr: ".$fd_dns1);
	}

	//secondary dns
	if(strlen($fd_dns2) && !is_ipaddr($fd_dns2)) {
	  add_critical("Invalid IP addr: ".$fd_dns2);
	}

	//primary wins
	if(strlen($fd_wins1) && !is_ipaddr($fd_wins1)) {
	  add_critical("Invalid IP addr: ".$fd_wins1);
	}

	//secondary wins
	if(strlen($fd_wins2) && !is_ipaddr($fd_wins2)) {
	  add_critical("Invalid IP addr: ".$fd_wins2);
	}

	//need more rules for this?
	if(is_enabled()) {
		if(!is_int(intval($fd_lease))) {
		  add_critical("Invalid lease term: ".$fd_lease." is not a number.");
		}
		if((int)$fd_lease < 120) {
		  add_critical("Invalid lease term: ".$fd_lease." second lease too short.");
		}
	}

	//router
	if(strlen($fd_router) && !is_ipaddr($fd_router)) {
	  add_critical("Invalid IP addr: ".$fd_router);
	}

	if(query_invalid())
	  add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
	else
		if($fd_posted) {

		//ok, we just posted and user wants to enable DHCP
		//... so assign validated settings to configfile object
		if(is_enabled()) {
			//we are enabling the service, fill everything we can
			$configfile->dhcpd['start'] = $fd_start;
			$configfile->dhcpd['end'] = $fd_end;
			$configfile->dhcpd['interface'] = $fd_interface;
			$configfile->dhcpd['lease'] = $fd_lease;
			$configfile->dhcpd['subnet'] = $fd_subnet;
			$configfile->dhcpd['router'] = $fd_router;
			$configfile->dhcpd['domain'] = $fd_domain;

			//optionals, may be blank!

			//always recreate arrays that may contain blanks
			$configfile->dhcpd['dns'] = array();
			if(strlen($fd_dns1)) $configfile->dhcpd['dns'][count($configfile->dhcpd['dns'])] = $fd_dns1;
			if(strlen($fd_dns2)) $configfile->dhcpd['dns'][count($configfile->dhcpd['dns'])] = $fd_dns2;

			$configfile->dhcpd['wins'] = array();
			if(strlen($fd_wins1)) $configfile->dhcpd['wins'][count($configfile->dhcpd['wins'])] = $fd_wins1;
			if(strlen($fd_wins2)) $configfile->dhcpd['wins'][count($configfile->dhcpd['wins'])] = $fd_wins2;

			if(strlen($fd_router)) $configfile->dhcpd['router'] = $fd_router;
			if(strlen($fd_router)) $configfile->dhcpd['domain'] = $fd_domain;

		} else {
			//we are disabling the service.  Empty all fields.
			$configfile->dhcpd['start'] = "";
			$configfile->dhcpd['end'] = "";
			$configfile->dhcpd['interface'] = "";
			$configfile->dhcpd['lease'] = "";
			$configfile->dhcpd['router'] = "";
			$configfile->dhcpd['subnet'] = "";
			$configfile->dhcpd['domain'] = "";
			$configfile->dhcpd['dns'] = array();
			$configfile->dhcpd['wins'] = array();
			$configfile->dhcpd['reservations'] = array();
		}

		$configfile->dirty["dhcpd"] = true;
		if(WriteWorkingConfig())
			add_warning("Write to working configfile was successful.");
		else
			add_warning("Error writing to working configfile!");
	}

?>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">

	<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="labelcellmid" nowrap>
				<input type="checkbox" name="dhcpenabled" <? if(is_enabled() || $_POST['dhcpenabled']) echo "checked" ?>></td>
				<td class="labelcellmid" nowrap width="100%">
				<label><font size="2">Enable the DHCP Service</font></label></td>
			</tr>
	</table>

	<table cellpadding="3" cellspacing="3" cols="3" width="100%">
		<tr>
			<td class="labelcell" nowrap><label>Interface:</label></td>
			<td class="ctrlcell" colspan="2">
			<select id="interface" name="interface">
			<option value="none" <? if($_POST['interface'] == 'none') print "selected";?>>none</option>
			<?
			//loop through interfaces
			foreach($configfile->interfaces as $ifentry) {
				//no bridged intf
				if($ifentry['bridge']) continue;

				//no downed intf allowed, duh
				if($ifentry['down']) continue;

				//if this is the chosen interface, make sure it is marked as selected
				if($ifentry['name'] == $fd_interface)
					$selected = "selected";
				else
					$selected = "";

				echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
			}
			?>
			</select>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>Domainname</label></td>
			<td class="ctrlcell" colspan="2">
			<input type="text" id="domain" value="<?=$fd_domain;?>" name="domain" size="20">
			<? if(is_enabled()) print mark_valid(is_domain($fd_domain));?>
			<br>
			<span class="descriptiontext">Domain name for the DHCP pool.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>Start Address</label></td>
			<td class="ctrlcell" colspan="2">
			<input type="text" id="start" value="<?=$fd_start;?>" name="start" size="20">
			<? if(is_enabled()) print mark_valid(is_ipaddr($fd_start));?>
			<br>
			<span class="descriptiontext">Starting IP address for the DHCP pool.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>End Address:</label></td>
			<td class="ctrlcell">
			<input type="text" id="end" value="<?=$fd_end;?>" name="end" size="20" />
			<? if(is_enabled()) print mark_valid(is_ipaddr($fd_end));?>
			<br>
			<span class="descriptiontext">Ending IP address for the DHCP pool.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>Subnet mask:</label></td>
			<td class="ctrlcell">
			<input type="text" id="subnet" value="<?=$fd_subnet;?>" name="subnet" size="20" />
			<? if(is_enabled()) print mark_valid(is_ipaddr($fd_subnet));?>
			<br>
			<span class="descriptiontext">The subnet mask to be sent to DHCP
			clients.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>Router address:</label></td>
			<td class="ctrlcell">
			<input type="text" id="router" value="<?=$fd_router;?>" name="router" size="20" />
			<? if(strlen($fd_router)) print mark_valid(is_domain($fd_router));?>
			<br>
			<span class="descriptiontext">The default gateway address to be sent to DHCP clients.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>DNS servers:</label></td>
			<td class="ctrlcell">
			<input type="text" id="dnsaddr" name="DNS1" size="20" value="<?=$fd_dns1;?>" />
			<? if(strlen($fd_dns1)) print mark_valid(is_ipaddr($fd_dns1));?>
			<br><br>
			<input type="text" id="dnsaddr" name="DNS2" size="20" value="<?=$fd_dns2;?>" />
			<? if(strlen($fd_dns2)) print mark_valid(is_ipaddr($fd_dns2)); ?>
			<br>
			<span class="descriptiontext">The IP addresses for DNS server that
			will be passed to DHCP clients.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>WINS servers:</label></td>
			<td class="ctrlcell">
			<input type="text" id="dnsaddr" name="WINS1" size="20" value="<?=$fd_wins1;?>" />
			<? if(strlen($fd_wins1)) mark_valid(is_ipaddr($fd_wins1));?>
			<br><br>
			<input type="text" id="dnsaddr" name="WINS2" size="20" value="<?=$fd_wins2;?>" />
			<? if(strlen($fd_wins2)) print mark_valid(is_ipaddr($fd_wins2)); ?>
			<br>
			<span class="descriptiontext">The IP addresses for WINS servers that will be passed to DHCP clients.</span></td>
		</tr>

		<tr>
			<td class="labelcell" nowrap><label>Lease time:</label></td>
			<td class="ctrlcell">
			<input type="text" name="lease" size="20" value="<?=$fd_lease?>">
			<?
				print mark_valid((int)$fd_lease > 120);
				print mark_valid(is_int((int)$fd_lease));
			?>
			<br>
			<span class="descriptiontext">The DHCP lease time in seconds.</span></td>
		</tr>

		<tr>
		  <td class="labelcell" nowrap><label>Reservations:</label></td>
		  <td class="ctrlcell" nowrap><span class="descriptiontext">
		<?
			if($fd_reservations)
				$amt = $fd_reservations;
			else
				$amt = "no";

    	print("The DHCP service has ".$fd_reservations." editable DHCP reservations assigned.&nbsp;&nbsp;");
     	print("[<a class=\"inline\" href=\"dhcpreservations.php\">Edit Reservations</a>]");

		?>
		    </span></td>
		</tr>
		<?
			if(query_warnings()) {
				echo "<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>";
			}
		?>
		<tr>
			<td>
				<input type="hidden" id="postcheck" name="postcheck" value="form was posted">
			</td>
		</tr>
	</table>
</form>
<?
	include("includes/footer.php");
?>