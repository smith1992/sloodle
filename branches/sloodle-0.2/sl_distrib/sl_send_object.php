<?php
    // Sloodle object distribution request script
    // Can be called by browsers or scripts to request the distribution of an object to in-world avatars
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2006-7 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   Edmund Edgar - original design and implementation
    //   Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
    //
        
    // This script may be requested by either a browser or a script.
    // Different parameters are required for different modes.
    // The following parameters are optional:
    //
    //   client = indicates what type the client is: 'browser' or 'script'. (Defaults to 'browser'). This determines what kind of response is given.
    //   cmd = indicates what action should be carried out (can be 'sendobject' to send an object, or ommitted completely to select the users)
    //   fname = first name of the avatar to whom to send an object
    //   lname = last name of the avatar to whom to send an object
    //   uuid = UUID of an avatar to whom to send an object
    //   object = name of the object to send (required if 'cmd' is 'send_object')
    //
    

	$sendToArbitraryAvatars = false; // set this to true to make it possible to enter an avatar name for someone not already registered on the site. This needs a way of getting the UUID for the avatar name - I've implemented this with a web service at webservices.socialminds.jp using LibSL, but I can't guarantee this will be running and available, so for now it's turned off and you can only send objects to avatars already registered in the system.

	require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');

	$client = optional_param('client','browser',PARAM_RAW); // 'script' for a script

	if ($client == 'browser') {
        $nav = "<a href=\"{$CFG->wwwroot}/admin/module.php?module=sloodle\" title=\"Sloodle\">Sloodle</a> ->";
        $nav .= "<a href=\"".SLOODLE_WWWROOT."/sl_distrib/sl_send_object.php\">".get_string('sloodleobjectdistributor', 'sloodle')."</a>";
		print_header(get_string('sloodleobjectdistributor', 'sloodle'), get_string('sloodleobjectdistributor', 'sloodle'), $nav, '', '', false);
	}

    // Fetch the key for the XMLRPC channel
	$distribchannel = sloodle_get_config('sloodle_distrib_channel');
    // Make sure it worked
	if (($distribchannel == NULL) || ($distribchannel == '') || ($distribchannel == FALSE)) {
		if ($client == 'script') {
            SloodleLSLResponse::quick_output(-103, 'SYSTEM', 'Distribution channel not available - Object not rezzed in-world?');
            exit();
		} else {
			error(get_string('sloodleobjectdistributor:nochannel', 'sloodle'));
            exit();
		}
	}

    // Fetch the additional parameters
    if ($client == 'script') {
        // Scripts should *always* specify this parameter
        $cmd = sloodle_required_param('cmd', PARAM_RAW);
    } else {
        $cmd = optional_param('cmd',null,PARAM_RAW);
    }
	$fname = optional_param('fname', '', PARAM_RAW);
	$lname = optional_param('lname', '', PARAM_RAW);
	$uuid = optional_param('uuid', '', PARAM_RAW);
	$object = optional_param('object', 'Sloodle Set 0.64', PARAM_RAW);
    
    // What command was specified?
    switch ($cmd) {
    case NULL:
        $allSloodleUsers = get_records('sloodle_users');
		$keysToNames = array();
		foreach($allSloodleUsers as $su) {
			if ( ($su->uuid != '') && ($su->avname != '') ) {
				$keysToNames[$su->uuid] = $su->avname;
			}
		}

		print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
		print '<table width="100%">';
        print '<tr><td style="text-align:center;"><h3>'.get_string('sloodleobjectdistributor', 'sloodle').' ';
        helpbutton('object_distributor', get_string('sloodleobjectdistributor', 'sloodle'), 'sloodle');
        print '</h3></td></tr>';
        print '<tr><td align="center">';
		print '<table>';        
		if ($sendToArbitraryAvatars) {
			print '<tr><td style="text-align:right;">';
            print_string('enteravatarname','sloodle');
            print ': </td><td>';
			print '<input type="text" name="fname" value="'.$fname.'" />';	
			print '</td><td>';
			print '<input type="text" name="lname" value="'.$lname.'" />';	
			print '</td></tr>';
		}
        print '<tr><td style="text-align:right;">';
        if ($sendToArbitraryAvatars) {
            print_string('or','sloodle');
            print '&nbsp;&nbsp;';
        }
        
        print_string('selectuser','sloodle');
		print ': </td><td colspan="2" style="text-align:left;">';
		if (count($keysToNames) > 0) {
			asort($keysToNames);
			print '<select name="uuid">';
			foreach($keysToNames as $k=>$n) {
				print '<option value="'.$k.'">'.$n.'</option>';
			}
			print '</select>';
		} else {
            print '<span style="color:red;">('.get_string('nosloodleusers','sloodle').')</span>';
        }
		print '</td></tr>';
	
		print '</td></tr>';
		print '<tr><td align="center" colspan="3" style="padding-top:24px;">';
		print '<input type="hidden" name="object" value="'.$object.'" />';	
		print '<input type="hidden" name="cmd" value="sendobject" />';
        print '<input type="submit" value="'.get_string('sendobject','sloodle').'"/>';	
		print '</td></tr>';
		print '<table>';
		print '</td></tr></table>';
		print '</form>';
        break;
        
     case 'sendobject':
        // Send an object to the specified user
        // Has the UUID been omitted?
        if ($uuid == '') {
            // We need to find an avatar key
            // Look them up in the Sloodle user table
            $sl_user = new SloodleUser();
            if ($sl_user->find_sloodle_user('', $fname.' '.$lname)) {
                $uuid = $sl_user->sloodle_user_cache->uuid;
            }
        
            // If that failed, are we allowed to use the name2key system?
			if (empty($uuid) && $sendToArbitraryAvatars) {
                // Make sure the full name was specified
				if ( ($fname != '') && ($lname != '') ) {
                    // Attempt to retrieve the key
					$uuid = keyForName($fname,$lname);
                }
			}
            
            // Do we have a UUID yet?
            if (empty($uuid)) {
                // No - we can't go any futher
                if ($client == 'script') {
                    SloodleLSLResponse::quick_output(-311, 'USER_AUTH', 'Could not find the specified user.');
                } else {
                    error(get_string('sloodleobjectdistributor:usernotfound','sloodle'));
                }
                break;
            }
		}
        
        // Construct our data response
        $response = new SloodleLSLResponse();
        $response->set_status_code(1);
        $response->set_status_descriptor('OK');
        $response->add_data_line(array('SENDOBJECT',$uuid,$object));
		// Render it to a string so we can send it
        $str = '';
        $response->render_to_string($str);
		// Send the XMLRPC
		$ok = sloodle_send_xmlrpc_message($distribchannel,0,$str);
        
        // What was the result?
		if ($ok) {
			if ($client == 'script') {
				SloodleLSLResponse::quick_output(1, 'OK', '');
			} else {
                print '<h3 style="color:green;text-align:center;">'.get_string('sloodleobjectdistributor:successful','sloodle').'</h3>';
                print '<p style="text-align:center;">';
                print get_string('Object','sloodle').': '.$object.'<br/>';
                print get_string('uuid','sloodle').': '.$uuid.'<br/>';
                print get_string('xmlrpc:channel','sloodle').': '.$distribchannel.'<br/>';
                print '</p>';
			}
		} else {
			if ($client == 'script') {
				SloodleLSLResponse::quick_output(-1, 'ERROR', 'Object distribution XMLRPC failed.');
			} else {
                print '<h3 style="color:red;text-align:center;">'.get_string('sloodleobjectdistributor:failed','sloodle').'</h3>';
				print '<p style="text-align:center;">';
                print get_string('Object','sloodle').': '.$object.'<br/>';
                print get_string('uuid','sloodle').': '.$uuid.'<br/>';
                print get_string('xmlrpc:channel','sloodle').': '.$distribchannel.'<br/>';
                print '</p>';
			}
		}
        break;
        
    default:
        // Command unknown - issue an error
        if ($client == 'script') {
            SloodleLSLResponse::quick_output(-811, 'REQUEST', 'Command not recognised.');
            exit();
        } else {
            error(get_string('sloodleobjectdistributor:unknowncommand','sloodle'));
            exit();
        }
        break;
    }
	
    
    // Display the page footer if this is a browser request
    if ($client == 'browser') {
        print_footer();
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
