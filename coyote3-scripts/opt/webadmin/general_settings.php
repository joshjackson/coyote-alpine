<?
	require_once("includes/loadconfig.php");

	VregCheck();

	//general_settings.php
	//
	//purpose:	1. offer form interface to edit firewall's general settings
	//			2. post form data to post_general_settings.php

	//set title info
	$MenuTitle='General Settings';
	$PageIcon = "settings.jpg";
	$MenuType='GENERAL';

	//configure our buttonset for the bottom
	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "general_settings.php");

    //determine whether or not we were posted to
	if($_POST['orig'])
		$fd_posted = true;
	else
		$fd_posted = false;

	//fill out vars for form (or for configfile)
	if($_POST['Hostname'])
		$fd_hostname = $_POST['Hostname'];
	else
		$fd_hostname = $configfile->hostname;

	if($_POST['DomainName'])
		$fd_domainname = $_POST['DomainName'];
	else
		$fd_domainname = $configfile->domainname;

	if($_POST['DNS1'])
		$fd_dns1 = $_POST['DNS1'];
	else
		$fd_dns1 = $configfile->nameservers[0];

	if($_POST['DNS2'])
		$fd_dns2 = $_POST['DNS2'];
	else
		$fd_dns2 = $configfile->nameservers[1];

	if($_POST['D1'])
		$fd_offset = $_POST['D1'];
	else
		$fd_offset = $configfile->timezone;

	if($_POST['TimeServer'])
		$fd_timeserver = $_POST['TimeServer'];
	else
		$fd_timeserver = $configfile->timeserver;


	//validate each param, start with a clean slate

	if(!is_hostname($fd_hostname)) {
	  add_critical("Invalid hostname: ".$fd_hostname);
	}

	if(!is_domain($fd_domainname)) {
	  add_critical("Invalid domainname: ".$fd_domainname);
	}

	if(strlen($fd_dns1) && !is_ipaddr($fd_dns1)) {
	  add_critical("Invalid nameserver address: ".$fd_dns1);
	}

	if(strlen($fd_dns2) && !is_ipaddr($fd_dns2)) {
	  add_critical("Invalid nameserver address: ".$fd_dns2);
	}

	if(query_invalid())
		add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
	else if($fd_posted) {

		//assign values to configfile
		$configfile->hostname = $fd_hostname;
		$configfile->domainname = $fd_domainname;

		//always clear arrays
		$configfile->nameservers = array();
		if(strlen($fd_dns1)) $configfile->nameservers[count($configfile->nameservers)] = $fd_dns1;
		if(strlen($fd_dns2)) $configfile->nameservers[count($configfile->nameservers)] = $fd_dns2;
		$configfile->timezone = $fd_offset;
		$configfile->timeserver = $fd_timeserver;

		//attempt write
		if(WriteWorkingConfig())
			add_warning("Write to working configfile was successful.");
		else
			add_warning("Error writing to working configfile!");
	}

	//include standard header
	include('includes/header.php');

?>
<form name="content" method="post" action="general_settings.php">
	<table cellpadding="3" cellspacing="3">
		<tr>
			<td class="labelcell" nowrap><label>Hostname:</label></td>
			<td class="ctrlcell">
			<input type="text" id="hostname" value="<?=$fd_hostname;?>" name="Hostname" size="20" />
			<?=mark_valid(is_hostname($fd_hostname));?>
			<br>
			<span class="descriptiontext">Name for this firewall host,
			without domain name.<br>
			Example: <i>firewall</i></span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap><label>Domain Name:</label></td>
			<td class="ctrlcell">
			<input type="text" id="domainname" value="<?=$fd_domainname;?>" name="DomainName" size="20" />
			<?=mark_valid(is_domain($fd_domainname));?>
			<br>
			<span class="descriptiontext">Example: <i>
			domain.com</i></span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap><label>Name servers:</label></td>
			<td class="ctrlcell">
			<input type="text" id="dnsaddr" name="DNS1" size="20" value="<?=$fd_dns1;?>" />
			<?=mark_valid(is_ipaddr($fd_dns1));?>
			<br><br>
			<input type="text" id="dnsaddr" name="DNS2" size="20" value="<?=$fd_dns2;?>" />
			<? if(strlen($fd_dns2)) print mark_valid(is_ipaddr($fd_dns2)); ?>
			<br>
			<span class="descriptiontext">The IP addresses for your DNS name
			servers. Only one server is needed for proper name resolution.</span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap><label>Timezone:</label></td>
			<td class="ctrlcell"><select size="1" name="D1"><?=GetTimezoneList($fd_offset)?></select><br>
			<span class="descriptiontext">Select the timezone for your area.</span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap><label>Time server:</label> </td>
			<td width="80%">
			<input type="text" id="timeserver" value="<?=$fd_timeserver;?>" name="TimeServer" size="20" />
			<?=mark_valid(is_domain($fd_timeserver));?>
			<br>
			<span class="descriptiontext">This host will be used to synchronize the firewall's internal clock.
			Be sure to specify at least one name server. You may use the default
			value to use Vortech Consulting's time server.</span>
			</td>
		</tr>
		<?
			if(query_warnings()) {
				echo "<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>";
			}
		?>
		<tr><td><input type="hidden" id="orig" name="orig" value="orig"></td></tr>
	</table>
</form>
<?

	//include standard footer
	include("includes/footer.php");
?>
