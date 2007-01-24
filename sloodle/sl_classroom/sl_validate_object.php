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
$auth = optional_param('auth',null,PARAM_RAW);
if ($objuuid == null) {
	sloodle_prim_render_output(array('ok'));
	exit;
}

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
	$result = authorize_object($objuuid,$objname,$userid);
	if ($result) {
		print_heading('Sent authorization. (Currently using e-mail; May take some time to reach your objects...)');
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
print '<form method="post" action="sl_validate_object.php"><input type="hidden" name="objuuid" value="'.$objuuid.'" /><input type="hidden" name="objname" value="'.$objname.'" /><input type="hidden" name="auth" value="no"><input type="submit" value="Don\'t Authorize"/></form>';
print '</td><td>';
print '<form method="post" action="sl_validate_object.php"><input type="hidden" name="objuuid" value="'.$objuuid.'" /><input type="hidden" name="objname" value="'.$objname.'" /><input type="hidden" name="auth" value="yes"><input type="submit" value="Authorize" /></form>';
print '</td></tr>';
print '</table>';
print '</center>';

function authorize_object($uuid,$name,$userid) {
	$entry = sloodle_register_object($uuid,$name,$userid,$uuid);
	if ($entry == null) {
		return false;
	}
	return authorize_object_email($uuid,$entry->pwd);
	//return authorize_object_xmlrpc($uuid);
}
function authorize_object_email($uuid,$pwd) {
	$to = $uuid.'@lsl.secondlife.com';
	//$to = 'test@edochan.com';
	$subject = "SLOODLE AUTH";
	$body =  'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'|'.$pwd;
	return (mail($to, $subject, $body)); 
}
function authorize_object_xmlrpc($uuid,$pwd) {
// DOESN'T WORK 

	$content = '<?xml version="1.0"?><methodCall><methodName>llRemoteData</methodName><params><param><value><struct><member><name>Channel</name><value><string>'.$objuuid.'</string></value></member><member><name>IntValue</name><value><int>123456789</int></value></member><member><name>StringValue</name><value><string></string></value></member></struct></value></param></params></methodCall>';
	$length = strlen($content);
	print "<h1>length is $length</h1>";

	$host = 'xmlrpc.secondlife.com';

	/* Get the port for the WWW service. */
	$service_port = getservbyname('www');

	/* Get the IP address for the target host. */
	$address = gethostbyname($host);

	/* Create a TCP/IP socket. */
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket < 0) {
		echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n";
	} else {
		echo "OK.\n";
	}

	echo "Attempting to connect to '$address' on port '$service_port'...";
	$result = socket_connect($socket, $address, $service_port);
	if ($result < 0) {
		echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n";
	} else {
		echo "OK.\n";
	}

	$in = "POST /cgi-bin/xmlrpc.cgi HTTP/1.0\r\n";
	$in .= "Host: $host\r\n";
	$in .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$in .= "Content-Length: $length\r\n";
	$in .= "Connection: close\r\n";
	$in .= "\r\n";
	$in .= $content;
	$out = '';

	echo "Sending request...";
	socket_write($socket, $in, strlen($in));
	echo "OK.\n";

	echo "Reading response:\n\n";
	while ($out = socket_read($socket, 2048)) {
		echo $out;
	}

	echo "Closing socket...";
	socket_close($socket);
	echo "OK.\n\n";
}

?>
