<?
    include("includes/loadconfig.php");
	//configure dchp reservations (add, edit, delete)
	$MenuTitle="DHCP Reservations";
	$MenuType="GENERAL";

	//define our buttons
	$buttoninfo[0] = array("label" => "Back to DHCPD", "dest" => "dhcpd.php");

	//did we freshly load this page or are we loading on result of a post
	if(strlen($_POST['postcheck']))
		$fd_posted = true;
	else
		$fd_posted = false;

    //get a count of reservations
    //if we are posted, expect to detect a new entry
    if($fd_posted)
      $fd_reservationcount = intval($_POST['reserve_count']) + 1;
    else
      $fd_reservationcount = count($configfile->dhcpd['reservations']);

    //if we are posted, create something to hold form data we'll need
    $fd_reservations = array();

	//less than straightforward validation checking, we want to loop
	if($fd_posted) {
      for($i=0; $i < $fd_reservationcount; $i++) {

	    $current = array("mac" => $_POST['mac'.$i], "address" => $_POST['ip'.$i]);

	    //print("(".$i."): ".$current['mac']." --> ".$current['address']."<br>");

	    //continue loop if both members are empty
	    if(!strlen($current['mac']) && !strlen($current['address'])) continue;

	    //check address member of pair
	    if(!is_ipaddr($current['address'])) {
	      add_critical("Invalid IP addr: ".$current['address']);
	    }

	    //check mac member of pair
	    if(!is_macaddr($current['mac'])) {
	      add_critical("Invalid MAC addr: ".$current['mac']);
	    }

      $fd_reservations[count($fd_reservations)] = $current;
	  }
  }

  //always display warnings
	if(query_invalid())
		add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
	else
		if($fd_posted) {
		//we are posted to and data was confirmed valid
		//attempt to assign data to configfile obj

        //always recreate arrays
        $configfile->dhcpd['reservations'] = array();

        //loop through posted, valid, NON EMPTY data
        foreach($fd_reservations as $crec) {
          $configfile->dhcpd['reservations'][count($configfile->dhcpd['reservations'])] = $crec;
        }

        //attempt write and display result of call ... eventually
        $configfile->dirty['dhcpd'] = true;
		if(WriteWorkingConfig())
			add_warning("Write to working configfile was successful.");
		else
			add_warning("Error writing to working configfile!");
	}

	include("includes/header.php");
?>

<script language="javascript">
  function delete_item(id) {
    f = document.forms[0];
    found = 0;

    if(!confirm("Delete this reservation?")) exit;

    //please kill me
    for(i=0;i<f.elements.length;i++) {
      if(f.elements[i].name == ('ip'+id)) {
        f.elements[i].value = '';
        found++;
      }

      if(f.elements[i].name == ('mac'+id)) {
        f.elements[i].value = '';
        found++;
      }
    }

    //submit if we actually found something to delete, best to confirm too.
    if(found) f.submit();
  }
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
<table width="100%">
	<tr>
		<td class="labelcell"><label>IP Addr</label></td>
		<td class="labelcellctr"><label>MAC Addr</label></td>
		<td class="labelcellctr"><label>Update</label></td>
		<td class="labelcellctr"><label>Delete</label></td>
	</tr>
	<?
	    $i = 0;
	    if($fd_reservationcount) {
          echo '<tr>';

          //loop through existing reservations
          foreach($configfile->dhcpd['reservations'] as $rec) {
            if ($i % 2)
              $cellcolor = "#F5F5F5";
            else
              $cellcolor = "#FFFFFF";
            ?>
              <td style="text-align: left; background-color: <?=$cellcolor?>;"><input type="text" id="ip<?=$i?>" name="ip<?=$i?>" value="<?=$rec['address']?>" /></td>
              <td style="text-align: center; background-color: <?=$cellcolor?>;"><input type="text" id="mac<?=$i?>" name="mac<?=$i?>" value="<?=$rec['mac']?>" /></td>
              <td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:do_submit()"><img src="images/icon-chk.gif" width="16" height="16"></a></td>
              <td style="text-align: center; background-color: <?=$cellcolor?>;"><a href="javascript:delete_item('<?=$i?>')"><img src="images/icon-del.gif" width="16" height="16"></a></td>
            </tr>
            <tr>
            <?
            $i++;
          }
        }
        //always make one empty row
        if($i % 2)
          $cellcolor = "#F5F5F5";
        else
          $cellcolor = "#FFFFFF";

        //FIXME: why is this done via calls to print?
        print('<td bgcolor="'.$cellcolor.'"><input type="text" id="ip'.$i.'" name="ip'.$i.'" value="" /></td>');
        print('<td align="center" bgcolor="'.$cellcolor.'"><input type="text" id="mac'.$i.'" name="mac'.$i.'" value="" /></td>');
        print('<td align="center" bgcolor="'.$cellcolor.'"><a href="javascript:do_submit()"><img border="0" src="images/icon-plus.gif" width="16" height="16"></a></td>');
        print('</tr>');

    if(strlen(query_warnings())) {
	    print("<tr><td class=ctrlcell colspan=2>".query_warnings()."</td></tr>");
    }
	?>
    <tr>
    	<td>
			<input type="hidden" id="postcheck" name="postcheck" value="form was posted">
			<input type="hidden" id="reserve_count" name="reserve_count" value="<?=$i?>" />
		</td>
	</tr>
</table>
</form>
<?
	include("includes/footer.php");
?>