<?php
    
    /**
    * Sloodle user profilew page.
    *
    * Shows a list of users, with links to information about associated avatars/LoginZone allocations
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // The 'course' parameter is required, and should be an integer ID of the course to show users from
    // The 'sloodleonly' parameter is optional, and if 'true', will show only users who have associated Sloodle data
    
    // Note: this page is accessible only by teachers and administrators
       


    require_once('config.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    
    // Enforce login
    require_login();
    // Refuse guest access
    if (isguest()) {
        error(get_string('noguestaccess', 'sloodle'));
        exit;
    }
    
    // Fetch the parameters
    $courseid = required_param('course', PARAM_INT);
    $sloodleonly = optional_param('sloodleonly', false, PARAM_BOOL);
    
    // Get the course data
    $courserecord = get_record('course', 'id', $courseid);
    if (!$courserecord) error(get_string('invalidcourseid','sloodle'));
    $courseurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;
    $courseshortname = $courserecord->shortname;
    $coursefullname = $courserecord->fullname;
    
    // Only allow teachers and admions
    if (isadmin() == false && isteachercourse($courseid) == false) {
        error(get_string('insufficientpermissiontoviewpage','sloodle'));
        exit();
    }
    
    // Get the localization strings
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    
    // Display the header
    $navigation = "";
    if ($courseid != 1) $navigation .= "<a href=\"$courseurl\">$courseshortname</a> -> ";
    $navigation .= get_string('sloodleuserprofiles', 'sloodle');
    print_header_simple(get_string('sloodleuserprofile', 'sloodle'), "", $navigation, "", "", true, "");
    
    
    echo '<div style="text-align:center;padding-left:8px;padding-right:8px;">';
    // Display the deletion message if we have one
    if (!empty($deletemsg)) {
        echo '<div style="text-align:center; padding:3px; border:solid 1px #aaaaaa; background-color:#dfdfdf; font-weight:bold; color:#dd0000;">';
        echo $deletemsg;
        echo '</div>';
    }
    
    
    // Obtain a list of all Moodle users enrolled in the specified course
    $userlist = get_course_users($courseid, 'lastname, firstname', '', 'user.id, firstname, lastname');    
    // Construct and display a table of Sloodle entries
    if ($userlist) {
        $sloodletable = new stdClass();
        $sloodletable->head = array(    get_string('ID', 'sloodle'),
                                        get_string('name', 'sloodle'),
                                        get_string('numsloodleentries', 'sloodle'),
                                        get_string('avatarname', 'sloodle')
                                    );
        $sloodletable->align = array('center', 'left', 'left', 'left');
                
        // Go through each Sloodle entry for this user
        foreach ($userlist as $u) {            
            // Reset the line's content
            $line = array();
            
            // Construct a URL to this user's Sloodle profile data
            $url = SLOODLE_WWWROOT."/view_user.php?id={$u->id}&amp;course=$courseid";
            
            // Add the ID and Moodle name to the line
            $line[] = $u->id;
            // Add the Moodle name
            $line[] = "<a href=\"$url\">{$u->firstname} {$u->lastname}</a>";
            
            // Get the Sloodle data for this Moodle user
            $sloodledata = get_records('sloodle_users', 'userid', $u->id);
            if ($sloodledata) {
                // Show the number of records
                $numrecs = count($sloodledata);
                if ($numrecs <= 1) $line[] = (string)$numrecs;
                else $line[] = '<span style="font-weight:bold;color:red;">'.$numrecs.'</span>';
                
                // Display all avatars names, if available
                $avnames = '';
                $firstentry = true;
                foreach ($sloodledata as $sd) {
                    // If this entry is empty, then skip it
                    if (empty($sd->avname) || ctype_space($sd->avname)) continue;
                    // Comma separated entries
                    if ($firstentry) $firstentry = false;
                    else $avnames .= ', ';
                    // Add the current name
                    $avnames .= $sd->avname;
                }
                // Add the avatar name(s) to the line
                $line[] = $avnames;                
                
            } else {
                // The query failed - if we are showing only Sloodle-enabled users, then skip the rest
                if ($sloodleonly) continue;
                $line[] = '0';
                $line[] = '-';
            }
            
            // Add the line to the table
            $sloodletable->data[] = $line;
        }
        
        // Display the table
        print_table($sloodletable);
    } else {
        // Failed to query for list of users
        echo '<div style="font-weight:bold; color:red;">';
        print_string('nouserdata','sloodle');
        echo '</div>';
    }
    
    // Display the footer
    print_footer();
?>
