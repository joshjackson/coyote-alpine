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
	if($_SERVER['REQUEST_METHOD'] == 'POST')
		$fd_posted = true;
	else
		$fd_posted = false;

	//$fd_enabled = $_POST['dhcpenabled'];
	$fd_enabled = filter_input(INPUT_POST, 'dhcpenabled', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
	//$fd_enabled = filter_input(INPUT_POST, 'dhcpenabled', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? $configfile->dhcpd['enable'];

function is_enabled() {

	global $fd_enabled;

	if($fd_enabled == 'on')
		return true;
	else
		return false;
}

	//note: interface being populated in configfile->dhcpd
	//      acts to indicate if dhcp is enabled

	$interface = filter_input(INPUT_POST, 'interface', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? $configfile->dhcpd['interface'];

	if (!$fd_posted){
		//not posted: fill all from configfile
		$fd_interface = (!empty($configfile->dhcpd['interface']) ? $configfile->dhcpd['interface'] : 'none');
		//interface choice should force checkbox enabled
		$fd_enabled = ($fd_interface != "none") ? "on" : "off";
	} else {
		//posted, checkbox was disabled, interface was filled: clear interface and disable
		if(!is_enabled() && !empty($interface)) {
			$fd_interface = 'none';
		} else {
			$fd_interface = $interface;
		}
	}

print("<!-- fd_enabled: $fd_enabled -->\n");
print("<!-- fd_interface: $fd_interface -->\n");

function getIfSet($key, $default, $idx = -1) {

	if ($idx >= 0 && is_array($default)) {
		$def1 = (isset($default[$idx]) ? $default[$idx] : '');
	} else {
		$def1 = (isset($default) ? $default : '');
	}
	return isset($_POST[$key]) ? $_POST[$key] : $def1;
}

	//fill the rest of the fields
	$fd_start = getIfSet('start', $configfile->dhcpd['start']);
	$fd_end = getIfSet('end', $configfile->dhcpd['end']);
	$fd_dns1 = getIfSet('DNS1', $configfile->dhcpd['dns'], 0);
	$fd_dns2 = getIfSet('DNS2', $configfile->dhcpd['dns'], 1);
	$fd_wins1 = getIfSet('WINS1', $configfile->dhcpd['wins'], 0);
	$fd_wins2 = getIfSet('WINS2', $configfile->dhcpd['wins'], 1);
	$fd_lease = getIfSet('lease', $configfile->dhcpd['lease']);
	$fd_router = getIfSet('router', $configfile->dhcpd['router']);
	$fd_subnet = getIfSet('subnet', $configfile->dhcpd['subnet']);
	$fd_domain = getIfSet('domain', $configfile->dhcpd['domain']);

    //get a count of reservations
	if (!is_array($configfile->dhcpd['reservations'])) {
		$configfile->dhcpd['reservations'] = array();
	}
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
				<input type="checkbox" name="dhcpenabled" <? if(is_enabled()) echo "checked" ?>></td>
				<td class="labelcellmid" nowrap width="100%">
				<label><font size="2">Enable the DHCP Service</font></label></td>
			</tr>
	</table>

	<table cellpadding="3" cellspacing="3" cols="3" width="100%">
		<tr>
			<td class="labelcell" nowrap><label>Interface:</label></td>
			<td class="ctrlcell" colspan="2">
			<select id="interface" name="interface">
			<option value="none" <? if($fd_interface == 'none') print "selected";?>>none</option>
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
				&nbsp;
			</td>
		</tr>
	</table>
</form>
<?
	include("includes/footer.php");
?>