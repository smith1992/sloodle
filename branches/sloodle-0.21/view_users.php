<?php
    
    /**
    * Sloodle user profile page.
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
    
    // The 'course' parameter is optional, and should be an integer ID of the course to show users from
    // Alternatively, if the 'search' parameter is specified, then 'course' is ignored, the the search string is used to search for Moodle users.
    
    // The 'sloodleonly' parameter is optional, and if 'true', will show only users who have associated Sloodle data
    //
    
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
    $courseid = optional_param('course', NULL, PARAM_INT);
    $searchstr = optional_param('search', NULL, PARAM_RAW);
    $sloodleonly = optional_param('sloodleonly', false, PARAM_BOOL);
    
    // If a search string was specified, then force the course value to the site course
    if ($searchstr != NULL) $courseid = 1;
    // If neither a search string nor a course was specified, then stop on an error
    if ($searchstr == NULL && $courseid == NULL) error(get_string('error:expectedsearchorcourse', 'sloodle'));
    
    // Get the course data (if no course specified, use the site course)
    $courserecord = get_record('course', 'id', $courseid);
    if (!$courserecord) error(get_string('invalidcourseid','sloodle'));
    $courseurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;
    $courseshortname = $courserecord->shortname;
    $coursefullname = $courserecord->fullname;
    
    // Only allow teachers and admins
    if (isadmin() == false && isteachercourse($courseid) == false) {
        error(get_string('insufficientpermissiontoviewpage','sloodle'));
        exit();
    }
    
    // Get the localization strings
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    
    // Display the header
    $navigation = '';
    if ($courseid != 1) $navigation .= "<a href=\"$courseurl\">$courseshortname</a> -> ";
    $navigation .= get_string('sloodleuserprofiles', 'sloodle');
    print_header(get_string('sloodleuserprofiles', 'sloodle'), get_string('sloodleuserprofiles', 'sloodle'), $navigation, "", "", false);
    
    // Open the main body section
    echo '<div style="text-align:center;padding-left:8px;padding-right:8px;">';
    
    // Are we searching for users?
    if ($searchstr != NULL)
    {
        // Display the search term
        echo '<br/><span style="font-size:16pt; font-weight:bold;">'.get_string('usersearch','sloodle').': '.$searchstr.'</span><br/><br/>';
        // Search the list of users
        $userlist = get_users(true, $searchstr);
    } else {
        // Getting all users in a course
        // Display the name of the course
        echo '<br/><span style="font-size:18pt; font-weight:bold;">'.$coursefullname.'</span><br/><br/>';
        // Obtain a list of all Moodle users enrolled in the specified course
        $userlist = get_course_users($courseid, 'lastname, firstname', '', 'u.id, firstname, lastname');
    }
    
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
    echo '</div>';
    
    

    
    echo '<br/><center><table style="text-align:left; vertical-align:top;">';
// // SEARCH FORMS // //
    echo '<tr>';

    // COURSE SELECT FORM //
    
    echo '<td style="width:350px; border:solid 1px #888888; padding:4px; vertical-align:top;">';
    
    echo "<form action=\"{$CFG->wwwroot}/mod/sloodle/view_users.php\" method=\"get\">";
    echo '<span style="font-weight:bold;">'.get_string('changecourse','sloodle').'</span><br/>';
    
    echo '<select name="course" size="1">';
    
    // Get a list of all courses
    $allcourses = get_courses('all', 'c.shortname', 'c.id, c.shortname, c.fullname');
    if (!$allcourses) $allcourses = array();
    foreach ($allcourses as $as) {
        // Get if the user is a teacher on this course
        if (isteacher($as->id)) {
            // Output this as an option
            echo "<option value=\"{$as->id}\"";
            if ($as->id == $courseid) echo "selected";
            echo ">{$as->fullname}</option>";
        }
    }    
    echo '</select><br/>';
    
    echo '<input type="checkbox" value="true" name="sloodleonly"';
    if ($sloodleonly) echo "checked";
    echo '/>'.get_string('showavatarsonly','sloodle').'<br/>';
    
    echo '<input type="submit" value="'.get_string('submit','sloodle').'" />';
    echo '</form>';
    
    echo '</td>';
    
    // SEARCH FORM //
    echo '<td style="width:350px; border:solid 1px #888888; padding:4px; vertical-align:top;">';    
    
    echo "<form action=\"{$CFG->wwwroot}/mod/sloodle/view_users.php\" method=\"get\">";
    echo '<span style="font-weight:bold;">'.get_string('usersearch','sloodle').'</span><br/>';
    echo '<input type="text" value="'.$searchstr.'" name="search" size="30" maxlength="30"/><br/>';
    
    echo '<input type="checkbox" value="true" name="sloodleonly"';
    if ($sloodleonly) echo "checked";
    echo '/>'.get_string('showavatarsonly','sloodle').'<br/>';
    
    echo '<input type="submit" value="'.get_string('submit','sloodle').'" />';
    echo '</form>';
    
    echo '</td>';
    
    
    
    // SPECIAL PAGES //
    echo '<td style="width:350px; border:solid 1px #888888; padding:4px; vertical-align:top;">';
    echo '<span style="font-weight:bold;">'.get_string('specialpages','sloodle').'</span><br/>';
    
    echo '<p>';
    echo "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_user.php?id=0\" title=\"".get_string('viewunlinked','sloodle')."\">";
    print_string('viewunlinked','sloodle');
    echo '</a><br/>';
    
    echo "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_user.php?id=all\" title=\"".get_string('viewall','sloodle')."\">";
    print_string('viewall','sloodle');
    echo '</a><br/>';
    echo '</p>';
    
    echo '</td>';
    
    
    
// // - END FORMS - // //
    echo '</tr>';
    echo '</table></center>';
    
    // Close the main body section
    echo '</div>';

    
    // Display the footer
    print_footer();
?>
