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

require_login();
if (!isadmin()) {
	print_heading('You need to be an administrator to authorize an object');
	exit;
}


print_heading('Authorize this object?');

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
print 'The following object wants your permission to access Moodle:';
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

function sloodle_send_xmlrpc_message($channel,$intval,$strval) {

	require_once('../lib/xmlrpc.inc');

    $client = new xmlrpc_client("http://xmlrpc.secondlife.com/cgi-bin/xmlrpc.cgi");

	    $content = '<?xml version="1.0"?><methodCall><methodName>llRemoteData</methodName><params><param><value><struct><member><name>Channel</name><value><string>'.$channel.'</string></value></member><member><name>IntValue</name><value><int>'.$intval.'</int></value></member><member><name>StringValue</name><value><string>'.$strval.'</string></value></member></struct></value></param></params></methodCall>';

	$response = $client->send(
		$content,
		60,
		'http'
	);

	//var_dump($response);
	if ($response->val == 0) {
		print '<p align="center">Not getting the expected XMLRPC response. Is Second Life broken again?<br />';
		print "XMLRPC Error - ".$response->errstr."</p>";
		return false;
	}
	//TODO: Check the details of the response to see if this was successful or not...
	return true;

}

?>
