<?php
    
    /**
    * Sloodle user profile page.
    *
    * Shows all Sloodle avatar entries associated with a particular user's Moodle account.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    //
    // This script will display Sloodle-related user information about a particular Moodle user.
    // Notably, this will include avatar data (i.e. name and UUID).
    // In the case of a user viewing their own profile, or an admin viewing anybody's profile,
    //  there will be the ability to unregister an avatar from the Moodle account, and to
    //  delete allocated LoginZone positions.
    //
    
    //
    // This script requires the following paramter:
    //  id = the ID number of a Moodle user
    //
    // The following parameter should be specified when a Sloodle entry is to be deleted:
    //  delete = ID number the Sloodle entry to be deleted


    require_once('config.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    
    // Enforce login
    require_login();
    // Refuse guest access
    if (isguest()) {
        error(get_string('noguestaccess', 'sloodle'));
        exit;
    }
    
    // Is the user permitted to edit the details?
    $canedit = isadmin() || ($USER->id == $moodleuserid);
    
    // Fetch the parameters
    $moodleuserid = required_param('id', PARAM_INT);
    $deletesloodleentry = optional_param('delete', NULL, PARAM_INT);
    
    // Are we deleting a Sloodle entry?
    $deletemsg = '';
    if ($deletesloodleentry != NULL && record_exists('sloodle_users', 'id', $deletesloodleentry)) {
        // Determine if the user is allowed to delete this entry
        $allowdelete = FALSE;
        // Admins are always allowed to delete
        if (isadmin()) $allowdelete = TRUE;
        else {
            // A Moodle user can delete their own entry
            $deleterecord = get_record('sloodle_users', 'id', $deletesloodleentry);
            if ($deleterecord !== FALSE) {
                $allowdelete = ($deleterecord->userid == $USER->id);
            }
        }
    
        if ($allowdelete) {
            // Make sure it's a valid ID
            if (is_int($deletesloodleentry) && $deletesloodleentry > 0) {
                
                // Attempt to delete the entry
                $deleteresult = delete_records('sloodle_users', 'id', $deletesloodleentry);
                if ($deleteresult === FALSE) {
                    $deletemsg = get_string('deletionfailed', 'sloodle').': '.get_string('databasequeryfailed', 'sloodle');
                } else {
                    $deletemsg = get_string('deletionsuccessful', 'sloodle');
                }
                
            
            } else {
                $deletemsg = get_string('deletionfailed', 'sloodle').': '.get_string('invalidid', 'sloodle');
            }
        } else {
            $deletemsg = get_string('deletionfailed', 'sloodle').': '.get_string('insufficientpermission', 'sloodle');
        }
    }
    
    
    // Fetch the Moodle user data
    $moodleuserdata = get_record('user', 'id', $moodleuserid);
    if ($moodleuserdata === FALSE) error(get_string('databasequeryfailed', 'sloodle'));
    // Fetch a list of all Sloodle user entries associated with this Moodle account
    $sloodleentries = get_records('sloodle_users', 'userid', $moodleuserid);
    if ($sloodleentries === FALSE) $sloodleentries = array();
    $numsloodleentries = count($sloodleentries);
    
    
    // Get the localization strings
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    
    // Display the header
    $navigation = "<a href=\"\">$strsloodle</a> -> ";
    $navigation .= get_string('sloodleuserprofile', 'sloodle');
    print_header_simple(get_string('sloodleuserprofile', 'sloodle'), "", $navigation, "", "", true, "");

    
    
    echo '<div style="text-align:center;padding-left:8px;padding-right:8px;">';
    // Display the deletion message if we have one
    if (!empty($deletemsg)) {
        echo '<div style="text-align:center; padding:3px; border:solid 1px #aaaaaa; background-color:#dfdfdf; font-weight:bold; color:#dd0000;">';
        echo $deletemsg;
        echo '</div>';
    }
    
    // Display general information about the Moodle account
    echo '<p>';
    print_string('name', 'sloodle');
    echo ' '. $moodleuserdata->firstname .' '. $moodleuserdata->lastname .'<br/>';
    
    if ($numsloodleentries == 0) print_string('noentries', 'sloodle');
    else if ($numsloodleentries > 1) {
        echo '<span style="color:red;">';
        print_string('multipleentries', 'sloodle');
        helpbutton('multiple_entries', get_string('help:multipleentries', 'sloodle'), 'sloodle', true, false);
        echo '</span>';
    }
    echo '</p></div>';
    
    
    // Construct and display a table of Sloodle entries
    if ($numsloodleentries > 0) {
        $sloodletable = new stdClass();
        $sloodletable->head = array(    get_string('avatarname', 'sloodle'),
                                        get_string('avataruuid', 'sloodle'),
                                        get_string('loginzoneposition', 'sloodle'),
                                        ''
                                    );
        $sloodletable->align = array('left', 'left', 'left', 'left');
        
        $deletestr = get_string('delete', 'sloodle');
                
        // Go through each Sloodle entry for this user
        foreach ($sloodleentries as $su) {
            // Add the avatar name and UUID
            $line = array();
            if (empty($su->avname)) $line[] = '-';
            else $line[] = $su->avname;
            if (empty($su->uuid)) $line[] = '-';
            else $line[] = $su->uuid;
            
            // Display the LoginZone status
            // Is there LoginZone position information in this entry?
            if (!empty($su->loginposition)) {
                // Has the LoginZone position expired?
                if ((int)($su->loginpositionexpires) < time()) {
                    // Expired
                    $line[] = '<span style="color:#dd0000;">'.get_string('expired', 'sloodle').'</span>';
                } else {
                    // Still active - calculate how long until expiry
                    $timeleft = (int)$su->loginpositionexpires - time();
                    $secondsleft = $timeleft % 60;
                    $minutesleft = (int)(($timeleft - $secondsleft) / 60);
                    $expiretext = "(".get_string('expiresin','sloodle').' ';
                    if ($minutesleft > 1) $expiretext .= $minutesleft.' '.get_string('minutes','sloodle');
                    else if ($minutesleft == 1) $expiretext .= '1 '.get_string('minute','sloodle');
                    if ($secondsleft > 1) $expiretext .= ' '.$secondsleft.' '.get_string('seconds','sloodle');
                    else $expiretext .= ' 1 '.get_string('second','sloodle');
                    $expiretext .= ')';

                    // Construct the link
                    $loginpos = sloodle_vector_to_array($su->loginposition);
                    $loginurl = "secondlife://{$su->loginpositionregion}/{$loginpos['x']}/{$loginpos['y']}/{$loginpos['z']}";
                    $logincaption = get_string('loginzone:teleport','sloodle');
                    $logintext = get_string('allocated','sloodle');
                    $line[] = "<a href=\"$loginurl\" title=\"$logincaption\">$logintext</a> $expiretext";
                }                
            } else {
                $line[] = '-';
            }
            
            // Display the "delete" action
            if ($canedit) {
                $deleteurl = $CFG->wwwroot."/mod/sloodle/view_user.php?id=$moodleuserid&delete={$su->id}";
                $deletecaption = get_string('clicktodeleteentry','sloodle');
                $line[] = "<a href=\"$deleteurl\" title=\"$deletecaption\">$deletestr</a>";
                
            } else {
                $line[] = '<span style="color:#777777;" title="'.get_string('nodeletepermission','sloodle').'">'.get_string('delete','sloodle').'</span>';
            }
            
            // Add the line to the table
            $sloodletable->data[] = $line;
        }
        
        // Display the table
        print_table($sloodletable);
    }
    
    // Display the footer
    print_footer();
?>
