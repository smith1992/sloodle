<?php
    /**
    * Sloodle object distribution interface page.
    *
    * Can be accessed by Moodle users in a web-browser to request the distribution of in-world objects.
    *
    * @package sloodledistrib
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script should only be accessed by a browser (a script linker version can be found at "sl_send_object_linker.php")
    // The following parameters are optional, but are typically provided by this script submitting a form to itself:
    //
    //   cmd = indicates what action should be carried out (can be 'sendobject' to send an object, or ommitted completely to select the users)
    //   fname = first name of the avatar to whom to send an object (required if 'lname' is specified)
    //   lname = last name of the avatar to whom to send an object (required if 'fname' is specified)
    //   uuid = UUID of an avatar to whom to send an object
    //   object = name of the object to send (required if 'cmd' is 'sendobject')
    //
    

	$sendToArbitraryAvatars = false; // set this to true to make it possible to enter an avatar name for someone not already registered on the site. This needs a way of getting the UUID for the avatar name - I've implemented this with a web service at webservices.socialminds.jp using LibSL, but I can't guarantee this will be running and available, so for now it's turned off and you can only send objects to avatars already registered in the system.

	require_once('../config.php');
    
    require_login();
    
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    // See if we can find a Sloodle user for this account
    $sl_user = new SloodleUser();
    $sl_user->set_moodle_user_id($USER->id);    $sl_user->find_linked_sloodle_user();
    $avname = '';
    if ($sl_user->get_sloodle_user_id() > 0) $avname = $sl_user->sloodle_user_cache->avname;
    // Anybody with teacher status or higher can send objects to anybody on the system
    // (Everybody else can just send them to their own avatar)
    $show_all_users = isteacherinanycourse(); // This includes admins

    // Construct our bread-crumbs navigation links
    $nav = array();
    $nav = "<a href=\"{$CFG->wwwroot}/admin/module.php?module=sloodle\" title=\"Sloodle\">Sloodle</a> ->";
    $nav .= "<a href=\"".SLOODLE_WWWROOT."/sl_distrib/sl_send_object.php\">".get_string('sloodleobjectdistributor', 'sloodle')."</a>";
    print_header(get_string('sloodleobjectdistributor', 'sloodle'), get_string('sloodleobjectdistributor', 'sloodle'), $nav, '', '', false);

    // Fetch the key for the XMLRPC channel
	$distribchannel = sloodle_get_config('sloodle_distrib_channel');
	if (($distribchannel == NULL) || ($distribchannel == '') || ($distribchannel == FALSE)) {
        error(get_string('sloodleobjectdistributor:nochannel', 'sloodle'));
        exit();
    }
    
    // Fetch the list of objects
    $distribobjects = sloodle_get_distribution_list();
    if (!is_array($distribobjects) || count($distribobjects) == 0) {
        error(get_string('sloodleobjectdistributor:noobjects', 'sloodle'));
        exit();
    }
    sort($distribobjects, SORT_STRING);

    // Fetch the additional parameters
    $cmd = optional_param('cmd',null,PARAM_RAW);
	$fname = optional_param('fname', '', PARAM_RAW);
	$lname = optional_param('lname', '', PARAM_RAW);
	$uuid = optional_param('uuid', '', PARAM_RAW);
	$object = optional_param('object', '', PARAM_RAW);
    
    
    
    // What command was specified?
    switch ($cmd) {
    case NULL:
        // No command - just show the page of options
    
        // Construct a list of all users to whom data may be sent
        $allSloodleUsers = get_records('sloodle_users');
		$keysToNames = array();
        if (is_array($allSloodleUsers)) {
    		foreach($allSloodleUsers as $su) {
    			if ( ($su->uuid != '') && ($su->avname != '') ) {
    				$keysToNames[$su->uuid] = $su->avname;
    			}
    		}
            asort($keysToNames);
        }
        
        // Construct a selection box of objects available for distribution
        $objselect = '<select name="object">';
        foreach ($distribobjects as $obj) {
            // Make sure it isn't empty
            if (is_string($obj) && !empty($obj)) {
                $objselect .= "<option value=\"$obj\">$obj</option>";
            }
        }
        $objselect .= '</select>';
        
        // This value will determine whether to disable the "send to me" form submission button
        $disable_send_to_me = ($sl_user->get_sloodle_user_id() <= 0);
        
        ?>
        
        
        <div style="text-align:center;width:100%;">
        <center>
        <h3 style="width:100%;text-align:center;">
        <?php
         print_string('sloodleobjectdistributor', 'sloodle');
         helpbutton('object_distributor', get_string('sloodleobjectdistributor', 'sloodle'), 'sloodle');
        ?>
        </h3>
        
        <!-- Sending to self -->
        <form   method="GET" action="<?php print $_SERVER['PHP_SELF']; ?>">
         <input type="hidden" name="cmd" value="sendobject" />
        
         <?php
          if ($sl_user->get_sloodle_user_id() > 0) {
            print '<input type="hidden" name="uuid" value="'.$sl_user->sloodle_user_cache->uuid.'"/>';
            print '<table style="border:solid 1px black;width:450px;">';
          } else {
            print '<table style="border:solid 1px #555555; background-color:#cccccc; width:450px;">';
          }
         ?>
         
          <tr><th colspan="2" style="padding-bottom:8px;"><?php print_string('sloodleobjectdistributor:sendtomyavatar','sloodle'); ?></th></tr>

          <tr>
           <td style="text-align:right;width:40%;"><?php print_string('selectobject','sloodle'); ?>: </td>
           <td style="text-align:left; width:60%;"><?php print $objselect; ?></td>
          </tr>
         
          <tr>
           <td colspan="2" style="text-align:center; padding:20px;">
            <input  type="submit"
                    value="<?php
                            print_string('sloodleobjectdistributor:sendtomyavatar','sloodle');
                            if (!$disable_send_to_me) print ' ('.$sl_user->sloodle_user_cache->avname.')';
                           ?>"
                    <?php if ($disable_send_to_me) print 'disabled="true"'; ?> />
            <?php
             if ($disable_send_to_me) {
              print '<br/><span style="color:red;">';
              print_string('avatarnotlinked','sloodle');
              print '</span>';
             }
            ?>
           </tr>
          </td>
         
         </table>
        </form>
        
        <!-- Sending to a custom avatar -->
        <?php
        if ($show_all_users) {
            print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
    		print '<table style="width:450px; border:solid 1px black; margin-top:28px;">';
            
            print '<tr><th colspan="3" style="text-align:center; padding-bottom:8px;">';
            print_string('sloodleobjectdistributor:sendtoanotheravatar','sloodle');
            print '</th></tr>';
           
            // Show the avatar name input boxes
    		if ($sendToArbitraryAvatars) {
    			print '<tr><td style="text-align:right; width:40%;">';
                print_string('enteravatarname','sloodle');
                print ': </td><td>';
    			print '<input type="text" name="fname" value="'.$fname.'" />';
    			print '</td><td>';
    			print '<input type="text" name="lname" value="'.$lname.'" />';
    			print '</td></tr>';
    		}
            
            // Show the select users combo box
            print '<tr><td style="text-align:right; width:40%;">';
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
            
            // Show the object selection box
            print '<tr><td style="text-align:right;">';
            print_string('selectobject', 'sloodle');
            print ': </td><td colspan="2">';
            print $objselect;
            print '</td></tr>';
    	
    		print '<tr><td align="center" colspan="3" style="padding:20px;">';
    		print '<input type="hidden" name="cmd" value="sendobject" />';
            print '<input type="submit" value="'.get_string('sendobject','sloodle').'"/>';
    		print '</td></tr>';
    		print '</table>';
    		print '</form>';
        }
        ?>
        </center>
        </div>
        <?php
        
       
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
                error(get_string('sloodleobjectdistributor:usernotfound','sloodle'));
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
        $str = str_replace("\n", "\\n", $str); // Ugly hack to stop the newline character disappearing before it reaches SL
        
		// Send the XMLRPC
		$ok = sloodle_send_xmlrpc_message($distribchannel,0,$str);
        
        // What was the result?
		if ($ok) {
            print '<h3 style="color:green;text-align:center;">'.get_string('sloodleobjectdistributor:successful','sloodle').'</h3>';
            print '<p style="text-align:center;">';
            print get_string('Object','sloodle').': '.$object.'<br/>';
            print get_string('uuid','sloodle').': '.$uuid.'<br/>';
            print get_string('xmlrpc:channel','sloodle').': '.$distribchannel.'<br/>';
            print '</p>';
		} else {
            print '<h3 style="color:red;text-align:center;">'.get_string('sloodleobjectdistributor:failed','sloodle').'</h3>';
            print '<p style="text-align:center;">';
            print get_string('Object','sloodle').': '.$object.'<br/>';
            print get_string('uuid','sloodle').': '.$uuid.'<br/>';
            print get_string('xmlrpc:channel','sloodle').': '.$distribchannel.'<br/>';
            print '</p>';
		}
        break;
        
    default:
        // Command unknown - issue an error
        error(get_string('sloodleobjectdistributor:unknowncommand','sloodle'));
        exit();
    }
	
    
    // Display the page footer if this is a browser request
    print_footer();

// Uses a web service lookup to fetch the key for an arbitrary name - not currently using this here.
function keyForName($fname,$lname) {

	$url = "http://webservices.socialminds.jp/name2key.php?fname=$fname&lname=$lname";
	$handle = fopen($url, "r");
	$contents = fread($handle,8192);
	fclose($handle);

	return $contents;

}

?>
