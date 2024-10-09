<?php

  require_once('functions.php');

//this may not belong, contains formatting!
function mark_valid($p)
{
	if(!$p)
		return '<span class="invalid">*</span>';
	else
		return '';
}

/* returns true if $ipaddr is a valid dotted IPv4 address with optional mask suffix */
function is_ipaddrblockopt($ipaddr) {
  if (!is_string($ipaddr)) return false;

	//get this out of the way quickly.
	if(is_ipaddr($ipaddr)) return true;

	//break the potential add/mask into 2 parts
	$parts = explode("/", $ipaddr);

	//if there is only 1 part, validate as ip addr
	if(count($parts) == 1)
		return is_ipaddr($ipaddr);

	//check the part and mask
	if(!is_ipaddr($parts[0]))
		return false;

	if((intval($parts[1]) < 0) || (intval($parts[1] > 32)))
		return false;
	else
		return true;

}

?>
