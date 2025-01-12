<?
	require_once("includes/loadconfig.php");
    //snmpd.php
    //
    // config settings and hostlist for snmpd
    /*
		$configfile->snmp = array(
			"location" => "",
			"contact" => "",
			"hosts" => array()
		);
    */

	$MenuTitle="SNMP Service";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	include("includes/header.php");

    function is_enabled() {
       	global $fd_enabled;

	    if($fd_enabled == 'on')
		  return true;
    	else
	    	return false;
    }

	//did we freshly load this page or are we loading on result of a post
	$fd_posted = ($_SERVER['REQUEST_METHOD'] == 'POST');

    //fill values from _POST or configfile
	if (!is_array($configfile->snmp['hosts'])) {
		$configfile->snmp['hosts'] = array();
	}

	if($fd_posted) {

		//form values
		$fd_contact = $_POST['contact'];
		$fd_location = $_POST['location'];
		$fd_hostcount = $_POST['hostcount'];

		//host list
		$fd_hostlist = array();
		for($hc = 0; $hc < $fd_hostcount; $hc++)
		  if(strlen($_POST['host'.$hc])) $fd_hostlist[count($fd_hostlist)] = $_POST['host'.$hc];

        //update count
        $fd_hostcount = count($fd_hostlist);
	} else {
		$fd_contact = $configfile->snmp['contact'];
		$fd_location = $configfile->snmp['location'];
		$fd_hostcount = count($configfile->snmp['hosts']);
		$fd_hostlist = $configfile->snmp['hosts'];
    }

    //determine enabled
    if($fd_posted)
      $fd_enabled = $_POST['enabled'];
    else if (strlen($fd_contact) && strlen($fd_location))
      $fd_enabled = 'on';

    //validate

    //FIXME: is this really required? ::jc
    if($fd_posted && is_enabled() && !strlen($fd_contact)) {
      add_critical("Invalid contact name: cannot be null.");
    }

    if($fd_posted && is_enabled() && !strlen($fd_location)) {
      add_critical("Invalid location: cannot be null.");
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

    //display warnings, if any entries are invalid
	if(query_invalid())
		add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
	else
        //assign to config obj, if posted
		if($fd_posted) {

		  //clear hosts array, it will be rebuilt if needed
		  $configfile->snmp['hosts'] = array();

		  if(is_enabled()) {
		    $configfile->snmp['contact'] = $fd_contact;
		    $configfile->snmp['location'] = $fd_location;
		    $configfile->snmp['hosts'] = $fd_hostlist;
		  } else {
		    //clear all (hosts already clear)
		    $configfile->snmp['contact'] = '';
		    $configfile->snmp['location'] = '';
		  }

		  //write config
 		  $configfile->dirty["snmpd"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

		}

    //hostcount should be incremented now, it will be filled into a hidden element
    //to indicate new host count at next post
    $fd_hostcount++;
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
	<input type="hidden" id="hostcount" name="hostcount" value="<?=$fd_hostcount?>">
	<table cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td class="labelcellmid" nowrap>
			<input type="checkbox" name="enabled" <? if(is_enabled()) print("checked")?>></td>
			<td class="labelcellmid" nowrap width="100%">
			<label><font size="2">Enable the SNMP Service</font></label></td>
		</tr>
	</table>
	<table cellpadding="3" cellspacing="3" width="100%">
		<tr>
			<td class="labelcell" nowrap>
				<label>Contact:</label>
			</td>
			<td class="ctrlcell">
				<input type="text" id="contact" value="<?=$fd_contact?>" name="contact" size="20">
				<? if(is_enabled()) print(mark_valid(strlen($fd_contact)));?>
				<br>
				<span class="descriptiontext">This is a text description for the
				name of the contact for this firewall. Typically this is the
				name of the individual or department responsible for the
				firewall.<br>
				Example: <i>Joe Smith</i></span>
			</td>
		</tr>

		<tr>
			<td class="labelcell" nowrap>
				<label>Location:</label>
			</td>
			<td>
				<input type="text" id="location" value="<?=$fd_location;?>" name="location" size="20" />
				<? if(is_enabled()) print(mark_valid(strlen($fd_location)));?>
				<br>
			<span class="descriptiontext">This is a text description for the
			location of this firewall.<br>
			Example: <i>My Company</i></span></td>
		</tr>
      </table>
      <br>
	<b><font size="2">SNMP access control</font><br>
	</b><span class="descriptiontext">The following is a list of hosts permitted to query the firewall's SNMP
	services. This list can be single IP addresses or an addresses in <i>
	network/prefix</i> format.</span><br>
    <table width="60%">

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
              <? if(strlen($host)) mark_valid(is_ipaddrblockopt($host)) ?>
              </td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_item('host<?=$i?>')"><img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
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
        <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>
		</tr>
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