<?
	require_once("includes/loadconfig.php");
	/*

	[0] => Array (
	  [dest] => 172.16.0.0/16
	  [gw] => 192.168.5.254
	  [dev] =>		//optional (intf name, "default" by default)
	  [metric] =>	//optional (integer, 1..1000)
  	)

	*/
	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$MenuTitle='Static Routes';
	$PageIcon = "routes.jpg";
	$MenuType='NETWORK';

	$fd_action = $_REQUEST['action'];

	if(!$fd_action) {
		//populate
		$fd_routes = $configfile->routes;
		$fd_routecount = count($fd_routes);
	} else if($fd_action == 'apply') {
		//populate locals
		$fd_routes = array();
		$fd_routecount = $_REQUEST['routecount'];

		//validate

		for($i = 0; $i < $fd_routecount; $i++) {
			//assign to local
			$route = array(
				'dest' => $_REQUEST['dest'.$i],
				'gw' => $_REQUEST['gw'.$i],
				'dev' => $_REQUEST['dev'.$i],
				'metric' => $_REQUEST['metric'.$i]
			);

			//then validate

			//do not include blank entries destination
			if(!strlen($route['dest']) || $route['dest'] == '') continue;

			//ip addr of dest
			if(strlen($route['dest']) && !is_ipaddrblockopt($route['dest'])) {
			  add_critical("Invalid IP addr: ".$route['dest']);

				//continue, do not use this route
				continue;
			}

			//ip addr of gw
			if(!strlen($route['gw']) || !is_ipaddr($route['gw'])) {
			  add_critical("Invalid IP addr: ".$route['gw']);

				//continue, do not use this route
				continue;
			}

			//FIXME: should the warning msg be different if the metric is simply a stupid value (like 50000) ?
			if(strlen($route['metric']) && (!intval($route['metric']) || (intval($route['metric']) < 1) || (intval($route['metric']) > 1000))) {
			  add_critical("Invalid metric: ".$route['metric']." is not a number or out of range.");
				continue;
			}

			//device is optional, null entry is interpreted as default
			if($route['dev'] == 'default') $route['dev'] = '';

			//if control arrives here, the route passes, so assign to the local collection
			if (!is_array($routes)) $routes = array();
			$routes[count($routes)] = $route;
		} //for

		if(query_invalid()) {
			add_warning("<hr>Encountered ".query_invalid()." errors.  Your config was not changed.<br>");
		} else {
			//assign
			$configfile->routes = $routes;

			//write
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
			  add_warning("Error writing to working configfile!");

			Header("Location:routing.php");
			die;

		}
	}

	$fd_routecount++;

	//include this last to allow redirects
	include("includes/header.php");
?>
<script language='javascript'>
	//insert code to handle deletion of an entry
	function delete_item(id) {
		f = document.forms[0];
		if(confirm('Are you sure you want to delete this route?')) {
			f.elements['dest'+id].value = '';
			f.elements['gw'+id].value = '';
			f.elements['metric'+id].value = '';
			f.submit();
		}
	}

</script>


<form id="content" method="post" action="<?=$_SERVER['PHP_SELF'];?>">

<input type="hidden" name="action" value="apply" />
<input type="hidden" name="routecount" value="<?=$fd_routecount?>" />

<span class="descriptiontext">This is a list of the static IP routes to be configured on this firewall. The <i>
destination</i> is the destination address or network and <i>gateway</i> is the
address of the next-hop router. The metric and device fields are optional.</span><table width="100%" padding=0 spacing=0>
	<tr>
		<td class="labelcell"><label>Destination</label></td>
		<td class="labelcell" align="left"><label>Gateway</label></td>
		<td class="labelcell" align="left"><label>Metric</label></td>
		<td class="labelcell" align="center"><label>Device</label></td>
		<td class="labelcell" align="center"><label>Update</label></td>
		<td class="labelcell" align="center"><label>Delete</label></td>
		<td class="labelcell" align="center"><label>Add</label></td>
	</tr>
	<tr>
	<?
	    $i = 0;
	    if(count($fd_routes)) {
          echo '<tr>';

          //loop through existing reservations
          foreach($fd_routes as $route) {
            if ($i % 2)
              $cellcolor = "#F5F5F5";
            else
              $cellcolor = "#FFFFFF";
            ?>
              <td bgcolor="<?=$cellcolor?>"><input type="text" id="dest<?=$i?>" name="dest<?=$i?>" value="<?=$route['dest']?>" /></td>
							<td bgcolor="<?=$cellcolor?>"><input type="text" id="gw<?=$i?>" name="gw<?=$i?>" value="<?=$route['gw']?>" /></td>
							<td bgcolor="<?=$cellcolor?>"><input type="text" id="metric<?=$i?>" name="metric<?=$i?>" value="<?=$route['metric']?>" /></td>
							<td align="center" bgcolor="<?=$cellcolor?>">
								<select id="dev<?=$i?>" name="dev<?=$i?>" width="100%">
								<option value="" <? if(!strlen($route['dev'])) print("selected");?>>default</option>
									<?
									//loop through interfaces
									foreach($configfile->interfaces as $ifentry) {
										//no downed intf allowed, duh
										if($ifentry['down']) continue;

										//if this is the chosen interface, make sure it is marked as selected
										if($ifentry['name'] == $fd_routes[$i]['dev'])
											$selected = "selected";
										else
											$selected = "";

										echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
									}
									?>
								</select>
							</td>

              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:do_submit()"><img border="0" src="images/icon-chk.gif" width="16" height="16"></a></td>
              <td align="center" bgcolor="<?=$cellcolor?>"><a href="javascript:delete_item('<?=$i?>')"><img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
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

        ?>
	        <td bgcolor="<?=$cellcolor?>"><input type="text" id="dest<?=$i?>" name="dest<?=$i?>" value="" /></td>
					<td bgcolor="<?=$cellcolor?>"><input type="text" id="gw<?=$i?>" name="gw<?=$i?>" value="" /></td>
					<td bgcolor="<?=$cellcolor?>"><input type="text" id="metric<?=$i?>" name="metric<?=$i?>" value="" /></td>
					<td align="center" bgcolor="<?=$cellcolor?>">
						<select id="dev<?=$i?>" name="dev<?=$i?>" width="100%">
						<option value="" selected >default</option>
						<?
						//loop through interfaces
						foreach($configfile->interfaces as $ifentry) {
							//no downed intf allowed, duh
							if($ifentry['down']) continue;

							//if this is the chosen interface, make sure it is marked as selected
							if($ifentry['name'] == $fd_routes[$i]['dev'])
								$selected = "selected";
							else
								$selected = "";

							echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
						}
						?>
						</select>
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

<span class="descriptiontext"><b>Note:</b> To add a default route, specify a destination address of <em>0.0.0.0/0</em> and the IP of your default router as the <em>Gateway</em>.

<?
	include("includes/footer.php");
?>