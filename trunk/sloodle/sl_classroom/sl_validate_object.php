<?php
/*
*/
require_once('../config.php');
require_once('../locallib.php');
require_once('../login/sl_authlib.php');
require_once('sl_classroomlib.php');

// The prim should call us without any arguments before sending the user here.
$objuuid = optional_param('objuuid',null,PARAM_RAW);
$objname = optional_param('objname',null,PARAM_RAW);
$channel = optional_param('ch',null,PARAM_RAW); // the channel we should use to talk back to the object

$auth = optional_param('auth',null,PARAM_RAW);
if ($objuuid == null) {
	sloodle_prim_render_output(array('ok'));
	exit;
}

//print "<h1>channel is ".$channel."</h1>";
//var_dump($_POST);

print_header('Authorize Object?', '', '', '', false, '', '', false, '');

print_heading('Authorize this object?');

if ($auth == null) {
	print '<p align="center">';
	print 'An object in Second Life wants your permission to access Moodle.';
	print '</p>';
}

require_login();
if (!isadmin()) {
	print_heading('You need to be an administrator to authorize an object');
	exit;
}



if ($auth == 'no') {
	print_heading('Authorization denied');
	exit;
}

if ($auth == 'yes') {
	$userid = $USER->id;
	$result = authorize_object($objuuid,$objname,$userid,$channel);
	if ($result) {
		print_heading('Sent authorization.');
	} else {
		print_heading('Authorization failed');
	}
	exit;
}

print '<p align="center">';
print 'Do you want to authorize the following object?';
print '<br />';
print '<br />';
print $objname;
print '<br />';
print $objuuid;
print '<center>';
print '<table border="0"><tr><td>';
print '<form method="post" action="sl_validate_object.php"><input type="hidden" name="objuuid" value="'.$objuuid.'" /><input type="hidden" name="objname" value="'.$objname.'" /><input type="hidden" name="ch" value="'.$channel.'" /><input type="hidden" name="auth" value="no"><input type="submit" value="Don\'t Authorize"/></form>';
print '</td><td>';
print '<form method="post" action="sl_validate_object.php"><input type="hidden" name="objuuid" value="'.$objuuid.'" /><input type="hidden" name="objname" value="'.$objname.'" /><input type="hidden" name="ch" value="'.$channel.'" /><input type="hidden" name="auth" value="yes"><input type="submit" value="Authorize" /></form>';
print '</td></tr>';
print '</table>';
print '</center>';

function authorize_object($uuid,$name,$userid,$channel) {
	$entry = sloodle_register_object($uuid,$name,$userid,$uuid);
	if ($entry == null) {
		return false;
	}
	//return authorize_object_email($uuid,$entry->pwd);
	//return authorize_object_xmlrpc($uuid,$entry->pwd,$channel);
	//print "<h1>sending message on channel ".$channel."</h1>";
	return sloodle_send_xmlrpc_message($channel,0,$entry->pwd);

}
?>
