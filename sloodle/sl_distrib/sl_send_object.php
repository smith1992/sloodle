<?php

	$sendToArbitraryAvatars = false; // set this to true to make it possible to enter an avatar name for someone not already registered on the site. This needs a way of getting the UUID for the avatar name - I've implemented this with a web service at webservices.socialminds.jp using LibSL, but I can't guarantee this will be running and available, so for now it's turned off and you can only send objects to avatars already registered in the system.

	require_once('../config.php');
	require_once('../locallib.php');

	$client = optional_param('client','browser',PARAM_RAW); // 'script' for a script

	if ($client == 'browser') {
		print_header(get_string("sloodleobjectdistributor", "sloodle"), '', '', '', false, '', '', false, '');
		print_heading(get_string("sloodleobjectdistributor", "sloodle"));
	}

	$distribchannel = sloodle_get_config('distribchannel');

	if ( ($distribchannel == null) || ($distribchannel == '') ) {
		if ($client == 'script') {
			print 'ERROR|Distribution channel not available - Object not rezzed in-world?';
		} else {
			print '<h3>'.get_string('sloodleobjectdistributor:nochannel', 'sloodle').'</h3>';
		}
	}

	$cmd = optional_param('cmd',null,PARAM_RAW);

	$fname = optional_param('fname','',PARAM_RAW);
	$lname = optional_param('lname','',PARAM_RAW);

	$uuid = optional_param('uuid','',PARAM_RAW);

	$object = optional_param('object','',PARAM_RAW);
	if ($object == '') {
		$object = 'Sloodle Set 0.64';
	}

	if ($cmd == null) {
		
		$allSloodleUsers = get_records('sloodle_users');
		$keysToNames = array();
		foreach($allSloodleUsers as $su) {
			if ( ($su->uuid != '') && ($su->avname != '') ) {
				$keysToNames[$su->uuid] = $su->avname;
			}
		}

		print '<form method="get" action="'.$PHP_SELF.'">';
		print '<table width="100%"><tr><td align="center">';
		print '<table>';
		if ($sendToArbitraryAvatars) {
			print '<tr><td>';
			print '<input type="text" name="fname" value="'.$fname.'" />';	
			print '</td><td>';
			print '<input type="text" name="lname" value="'.$lname.'" />';	
			print '</td></tr>';
		}
		print '<tr><td colspan="2">';
		if (count($keysToNames) > 0) {
			asort($keysToNames);
			print '<select name="uuid">';
			foreach($keysToNames as $k=>$n) {
				print '<option value="'.$k.'">'.$n.'</option>';
			}
			print '</select>';
		}
		print '</td></tr>';
	
		print '</td></tr>';
		print '<tr><td align="center">';
		print '<input type="hidden" name="object" value="'.$object.'" />';	
		print '<input type="hidden" name="cmd" value="sendobject" />';	
		print '<input type="submit" value="Send object"/>';	
		print '</td></tr>';
		print '<table>';
		print '</td></tr></table>';
		print '</form>';

	} else if ($cmd == 'sendobject') {

		if ($uuid == '') {
			if ($sendToArbitraryAvatars) {
				if ( ($fname != '') && ($lname != '') ) {
					$uuid = keyForName($fname,$lname);
					if ( ($uuid == null) || ($uuid == '') ) {
						print '<h3>Error: Could not find avatar key for '.$fname.' '.$lname.'</h3>';
						exit;
					}
				} else {
					print '<h3>Error: Part of avatar name missing.</h3>';
					exit;
				}
			} else {
				print '<h3>Error: Avatar not selected.</h3>';
				exit;
			}
		}
		$data = "SENDOBJECT|$uuid|$object";
		
		
		$ok = sloodle_send_xmlrpc_message($distribchannel,0,$data);

		if ($ok) {
			if ($client == 'script') {
				print 'OK';
			} else {
				print "Sent object $object to $uuid on channel $distribchannel";
			}
		} else {
			if ($client == 'script') {
				print 'ERROR|Sending failed';
			} else {
				print "Error attempting to send object $object to $uuid on channel $distribchannel";
			}
		}
		

	}


// Uses a web service lookup to fetch the key for an arbitrary name - not currently using this here.
function keyForName($fname,$lname) {

	$url = "http://webservices.socialminds.jp/name2key.php?fname=$fname&lname=$lname";
	$handle = fopen($url, "r");
	$contents = fread($handle,8192);
	fclose($handle);

	return $contents;

}

?>
