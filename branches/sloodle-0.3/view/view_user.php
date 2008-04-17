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
    //  id = the ID number of a Moodle user, 'all' for all Sloodle entries, 'search' to search avatars by name/uuid, or 'pending' to list pending (unlinked) avatars
    //
    // The following parameter should be specified when a Sloodle entry is to be deleted:
    //  delete = ID number the Sloodle entry to be deleted
    // The following parameter should be used when the user confirms or rejects deletion
    //  confirm = "Yes" (according to a localization table) if confirmed, unspecified if not yet confirmed, or anything else if cancelled.
    //
    // Optionally, the following parameter can also be specified. It defaults to the site if unspecified:
    //  course = ID of a Moodle course
    //
    // In order to search for avatars, the following parameter is required:
    //  search = a term to search for (avatar name/uuid)


    /** Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** General Sloodle functionality. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    // Enforce login
    require_login();
    // Refuse guest access
    if (isguestuser()) {
        error(get_string('noguestaccess', 'sloodle'));
        exit;
    }
    
    // Fetch the parameters
    $moodleuserid = required_param('id', PARAM_RAW);
    $deletesloodleentry = optional_param('delete', NULL, PARAM_INT);
    $userconfirmed = optional_param('confirm', NULL, PARAM_RAW);
    $courseid = optional_param('course', 1, PARAM_INT);
    $searchstr = addslashes(optional_param('search', '', PARAM_TEXT));
    
    // Check the mode: all, search, pending, or single
    $allentries = false;
    $searchentries = false;
    if (strcasecmp($moodleuserid, 'all') == 0) {
        $allentries = true;
        $moodleuserid = -1;
    } else if (strcasecmp($moodleuserid, 'search') == 0) {
        $searchentries = true;
        $moodleuserid = -1;
    } else {
        // Make sure the Moodle user ID is an integer
        $moodleuserid = (integer)$moodleuserid;
        if ($moodleuserid <= 0) error(ucwords(get_string('unknownuser', 'sloodle')));
    }
    
    
    // Get the name of the course
    $courserecord = get_record('course', 'id', $courseid);
    if (!$courserecord) error(get_string('invalidcourseid','sloodle'));
    $courseurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;
    $courseshortname = $courserecord->shortname;
    $coursefullname = $courserecord->fullname;
    
    // We need to establish some permissions here
    $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
    $system_context = get_context_instance(CONTEXT_SYSTEM);
    $viewingself = false;
    $canedit = false;
    // Is the user trying to view their own profile?
    if ($moodleuserid == $USER->id) {
        $viewingself = true;
        $canedit = true;
    } else {
        // Does the user have permission to edit other peoples' profiles in the system and/or course?
        // If not, can they at least view others' profiles?
        if (has_capability('moodle/user:editprofile', $system_context) || has_capability('moodle/user:editprofile', $course_context)) {
            // User can edit profiles
            $canedit = true;
        } else if (!(has_capability('moodle/user:viewdetails', $system_context) || has_capability('moodle/user:viewdetails', $course_context))) {
            // Cannot view profiles
            error(get_string('insufficientpermissiontoviewpage','sloodle'));
            exit();
        }
    }
    
    // This value will indicate if we are currently confirming a deletion
    $confirmingdeletion = false;
    
    // These are localization strings used by the deletion confirmation form
    $form_yes = get_string('Yes', 'sloodle');
    $form_no = get_string('No', 'sloodle');
    
    
    // Are we deleting a Sloodle entry?
    $deletemsg = '';    
    if ($deletesloodleentry != NULL) {
        // Determine if the user is allowed to delete this entry
        $allowdelete = $canedit; // Just go with the editing ability for now... will maybe change this later. -PRB
        
        // Has the deletion been confirmed?
        if ($userconfirmed == $form_yes) {
            if (record_exists('sloodle_users', 'id', $deletesloodleentry)) {
                // Is the user allowed to delete this?
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
        } else if (is_null($userconfirmed)) {
            // User needs to confirm deletion
            $confirmingdeletion = true;
            
            $form_url = SLOODLE_WWWROOT."/view/view_user.php";
            
            $deletemsg .= '<h3>'.get_string('delete','sloodle').' '.get_string('ID','sloodle').': '.$deletesloodleentry.'<br/>'.get_string('confirmdelete','sloodle').'</h3>';
            $deletemsg .= '<form action="'.$form_url.'" method="get">';
            
            if ($allentries) $deletemsg .= '<input type="hidden" name="id" value="all" />';
            else $deletemsg .= '<input type="hidden" name="id" value="'.$moodleuserid.'" />';
            
            if (!is_null($courseid)) $deletemsg .= '<input type="hidden" name="course" value="'.$courseid.'" />';
            $deletemsg .= '<input type="hidden" name="delete" value="'.$deletesloodleentry.'" />';
            $deletemsg .= '<input style="color:green;" type="submit" value="'.$form_yes.'" name="confirm" />&nbsp;&nbsp;';
            $deletemsg .= '<input style="color:red;" type="submit" value="'.$form_no.'" name="confirm" />';
            $deletemsg .= '</form><br/>';
            
        } else {
            $deletemsg = get_string('deletecancelled','sloodle');
        }
    }
    
    // Are we getting all entries, searching, or just viewing one?
    if ($allentries) {
        // All entries
        $moodleuserdata = null;
        // Fetch a list of all Sloodle user entries
        $sloodleentries = get_records('sloodle_users');
    } else if ($searchentries && !empty($searchstr)) {
        // Search entries
        $moodleuserdata = null;
        $LIKE = sql_ilike();
        $sloodleentries = get_records_select('sloodle_users', "`avname` $LIKE '%$searchstr%' OR `uuid` $LIKE '%$searchstr%'", '`avname`');
    } else {
        // Attempt to fetch the Moodle user data
        $moodleuserdata = get_record('user', 'id', $moodleuserid);
        // Fetch a list of all Sloodle user entries associated with this Moodle account
        $sloodleentries = get_records('sloodle_users', 'userid', $moodleuserid);
    }
    // Post-process the query results
    if ($sloodleentries === FALSE) $sloodleentries = array();
    $numsloodleentries = count($sloodleentries);
    
    
    // Get the localization strings
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strunknown = get_string('unknown', 'sloodle');
    $strminute = get_string('minute', 'sloodle');
    $strminutes = get_string('minutes', 'sloodle');
    $strhour = get_string('hour', 'sloodle');
    $strhours = get_string('hours', 'sloodle');
    $strday = get_string('day', 'sloodle');
    $strdays = get_string('days', 'sloodle');
    $strweek = get_string('week', 'sloodle');
    $strweeks = get_string('weeks', 'sloodle');

    
    // Construct the breadcrumb links
    $navigation = "";
    if ($courseid != 1) $navigation .= "<a href=\"$courseurl\">$courseshortname</a> -> ";
    $navigation .= "<a href=\"".SLOODLE_WWWROOT."/view/view_users.php?course=$courseid\">".get_string('sloodleuserprofiles', 'sloodle') . '</a> -> ';
    if ($moodleuserid > 0) {
        if ($moodleuserdata) $navigation .= $moodleuserdata->firstname.' '.$moodleuserdata->lastname;
        else $navigation .= get_string('unknownuser','sloodle');
    } else {
        $navigation.= get_string('allentries', 'sloodle');
    }
    
    // Display the header
    print_header(get_string('sloodleuserprofile', 'sloodle'), get_string('sloodleuserprofile','sloodle'), $navigation, "", "", true);
    
    echo '<div style="text-align:center;padding-left:8px;padding-right:8px;">';
    // Display the deletion message if we have one
    if (!empty($deletemsg)) {
        echo '<div style="text-align:center; padding:3px; border:solid 1px #aaaaaa; background-color:#dfdfdf; font-weight:bold; color:#dd0000;">';
        echo $deletemsg;
        echo '</div>';
    }
    echo '<br/>';
    
    // Are we dealing with an actual Moodle account
    if ($moodleuserid > 0) {
        echo '<p>';
        // Yes - do we have an account?
        if ($moodleuserdata) {
            // Yes - display the name and other general info
            echo '<span style="font-size:18pt; font-weight:bold;">'. $moodleuserdata->firstname .' '. $moodleuserdata->lastname.'</span>';
            echo " <span style=\"font-size:10pt; color:#444444; font-style:italic;\">(<a href=\"{$CFG->wwwroot}/user/view.php?id=$moodleuserid&amp;course=$courseid\">".get_string('moodleuserprofile','sloodle')."</a>)</span><br/>";
        } else {
            echo get_string('moodleusernotfound', 'sloodle').'<br/>';
        }        
        echo "<br/><br/>\n";
        
        // Check for issues such as no entries, or multiple entries
        if ($numsloodleentries == 0) {
            echo '<span style="color:red; font-weight:bold;">';
            print_string('noentries', 'sloodle');
            echo '</span>';
            // If it is the profile owner who is viewing this, then offer a link to the loginzone entry page
            if ($moodleuserid == $USER->id) {
                echo "<br/><br/><p style=\"padding:8px; border:solid 1px #555555;\"><a href=\"{$CFG->wwwroot}/mod/sloodle/login/sl_loginzone_entry.php\">";
                print_string('getnewloginzoneallocation', 'sloodle');
                echo '</a></p>';
            }            
            
        } else if ($numsloodleentries > 1) {
            echo '<span style="color:red; font-weight:bold; border:solid 2px #990000; padding:4px; background-color:white;">';
            print_string('multipleentries', 'sloodle');
            helpbutton('multiple_entries', get_string('help:multipleentries', 'sloodle'), 'sloodle', true, false);
            echo '</span>';
        }
        echo '</p>';
        
    } else if ($searchentries) {
        // Searching for users
        echo '<span style="font-size:18pt; font-weight:bold; ">'.get_string('avatarsearch','sloodle').": \"$searchstr\"</span><br/><br/>";
        // Check to see if there are no entries
        if ($numsloodleentries == 0) {
            echo '<span style="color:red; font-weight:bold;">';
            print_string('noentries', 'sloodle');
            echo '</span>';
        }
        
    } else {
        // Assume we're listing all entries - explain what this means
        echo '<span style="font-size:18pt; font-weight:bold; ">'.get_string('allentries','sloodle').'</span><br/>';
        echo '<center><p style="width:550px; text-align:left;">'.get_string('allentries:info', 'sloodle').'</p></center>';
        
        // Check to see if there are no entries
        if ($numsloodleentries == 0) {
            echo '<span style="color:red; font-weight:bold;">';
            print_string('noentries', 'sloodle');
            echo '</span>';
        }
    }
    
    // Construct and display a table of Sloodle entries
    if ($numsloodleentries > 0) {
        $sloodletable = new stdClass();
        $sloodletable->head = array(    get_string('ID', 'sloodle'),
                                        get_string('linkedtomoodleusernum', 'sloodle'),
                                        get_string('avatarname', 'sloodle'),
                                        get_string('avataruuid', 'sloodle'),
                                        get_string('lastonlinesl', 'sloodle'),
                                        ''
                                    );
        $sloodletable->align = array('center', 'center', 'left', 'left', 'left', 'left');
        $sloodletable->size = array('5%', '5%', '27%', '35%', '20%', '8%');
        
        $deletestr = get_string('delete', 'sloodle');
                
        // Go through each Sloodle entry for this user
        foreach ($sloodleentries as $su) {
            // Is this entry being deleted (i.e. is the user being asked to confirm its deletion)?
            $deletingcurrent = ($confirmingdeletion == true && $su->id == $deletesloodleentry);
            
            // Reset the line's content
            $line = array();
            
            // Add the ID to the line
            if ($deletingcurrent) $line[] = '<span style="color:red; font-weight:bold;">'.$su->id.'</span>';
            else $line[] = $su->id;
            
            // Add the Moodle user ID and link
            $line[] = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$su->userid}&amp;course=$courseid\">{$su->userid}</a>";            
        
            // Fetch the avatar name and UUID
            $curavname = '-';
            $curuuid = '-';
            if (!empty($su->avname)) $curavname = $su->avname;
            if (!empty($su->uuid)) $curuuid = $su->uuid;
            
            // If we are in all or searching mode, add a link to the Sloodle user profile
            if ($allentries || $searchentries) {
                $curavname .= " <span style=\"font-size:10pt; color:#444444; font-style:italic;\">(<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_user.php?id={$su->userid}&amp;course=$courseid\">".get_string('sloodleuserprofile','sloodle')."</a>)</span>";
            }
            
            // Add them to the table
            $line[] = $curavname;
            $line[] = $curuuid; 
            
            // Do we know when the avatar was last online in SL?
            if (!empty($su->lastonline)) {
                // Calculate the time difference
                $difference = time() - (int)$su->lastonline;
                
                // Assume first that the user is online now
                // (Updates can easily take a minute or so... ignore anything less than 1 minute)
                $duration = '';
                if ($difference < 60) {
                    $duration = ucwords(get_string('now', 'sloodle'));
                } else if ($difference < 119) { // < 2 minutes
                    $duration = "1 $strminute";
                } else if ($difference < 3600) { // < 1 hour
                    $duration = (string)(int)($difference / 60)." $strminutes";
                } else if ($difference < 7200) { // < 2 hours
                    $duration = "1 $strhour";
                } else if ($difference < 86400) { // < 1 day
                    $duration = (string)(int)($difference / 3600)." $strhours";
                } else if ($difference < 172800) { // < 2 days
                    $duration = "1 $strday";
                } else if ($difference < 604800) { // < 1 week
                    $duration = (string)(int)($difference / 86400)." $strdays";
                } else if ($difference < 1209600) { // < 2 weeks
                    $duration = "1 $strweek";
                } else {
                    $duration = (string)(int)($difference / 604800)." $strweeks";
                }
                
                // Add it to the table
                $line[] = $duration;
            } else {
                $line[] = '('.$strunknown.')';
            }
            
            // Display the "delete" action
            if ($canedit) {
                if ($allentries) $deleteurl = $CFG->wwwroot."/mod/sloodle/view/view_user.php?id=all&amp;course=$courseid&amp;delete={$su->id}";
                else $deleteurl = $CFG->wwwroot."/mod/sloodle/view/view_user.php?id=$moodleuserid&amp;course=$courseid&amp;delete={$su->id}";
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
    echo '</div>';
    
    // Display the footer
    print_footer();
?>
