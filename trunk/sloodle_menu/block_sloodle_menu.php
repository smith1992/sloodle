<?php

@include_once($CFG->dirroot .'/mod/sloodle/config.php');
if (defined('SLOODLE_DIRROOT')) {
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_userlib.php');
}

// Define the Sloodle Menu Block version
define('SLOODLE_MENU_VERSION', 0.21);

class block_sloodle_menu extends block_base {

    function init() {
        global $CFG;
        
        $this->title = get_string('blockname', 'block_sloodle_menu');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2008022800;
    }
    
    function has_config() {
        return true; // indicates that we want to use the "config_global.html" configuration file
    }
    
    function hide_header() {
        return false; // change to true if we want to hide the block header
    }

    function get_content() {
        global $CFG, $COURSE, $USER;
        
        // Construct the content
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        // If no course has been specified, then we are using the site course
        if (!isset($COURSE)) {
            $COURSE = get_site();
        }
        
        // If the user is not logged in or if they are using guest access, then we can't show anything
        if (!isloggedin() || isguest()) {
            return $this->content;
        }
        
        // It is expected that, by Sloodle 0.3, things will have changed so much that this block will not be valid
        if (SLOODLE_VERSION >= 0.3) {
            $this->content->text = get_string('pleaseupgrade', 'block_sloodle_menu');
            return $this->content;
        }
        
        // Has the Sloodle activity module been installed?
        if (!(function_exists("sloodle_is_installed") && sloodle_is_installed())) {
            $this->content->text = get_string('sloodlenotinstalled', 'block_sloodle_menu');
            return $this->content;
        }       
        
        // Add the Sloodle and Sloodle Menu version info to the footer of the block
        $this->content->footer = '<span style="color:#565656;font-style:italic; font-size:10pt;">'.get_string('sloodlemenuversion', 'block_sloodle_menu').': '.(string)SLOODLE_MENU_VERSION.'</span>';
        $this->content->footer .= '<br/><span style="color:#888888;font-style:italic;font-size:8pt;">'.get_string('sloodleversion', 'block_sloodle_menu').': '.(string)SLOODLE_VERSION.'</span>';
                
        // Attempt to find a Sloodle user for the Moodle user
        $dbquery = "    SELECT * FROM `{$CFG->prefix}sloodle_users`
                        WHERE `userid` = {$USER->id} AND !(`avname` = '' AND `uuid` = '')
                        LIMIT 0,2
                    ";
        $dbresult = get_records_sql($dbquery);
        $sl_avatar_name = "";
        if (!is_array($dbresult) || count($dbresult) == 0) $userresult = FALSE;
        else if (count($dbresult) > 1) $userresult = "Multiple avatars associated with your Moodle account.";
        else {
            $userresult = TRUE;
            reset($dbresult);
            $sl_avatar_name = current($dbresult)->avname;
        }
        
        // These variables will determine if the individual items are shown
        $show_myprofile = true;
        $show_distributor = true;
        $show_loginzone = true;
        $show_usermanagement = true;
        $show_sloodleconfig = true;
        $show_notecardsetup = true;
        // If an item has not been set, then default to true
        if (isset($CFG->block_sloodle_menu_show_myprofile))         $show_myprofile = (bool)$CFG->block_sloodle_menu_show_myprofile;
        if (isset($CFG->block_sloodle_menu_show_distributor))       $show_distributor = (bool)$CFG->block_sloodle_menu_show_distributor;
        if (isset($CFG->block_sloodle_menu_show_loginzone))         $show_loginzone = (bool)$CFG->block_sloodle_menu_show_loginzone;
        if (isset($CFG->block_sloodle_menu_show_usermanagement))    $show_usermanagement = (bool)$CFG->block_sloodle_menu_show_usermanagement;
        if (isset($CFG->block_sloodle_menu_show_sloodleconfig))     $show_sloodleconfig = (bool)$CFG->block_sloodle_menu_show_sloodleconfig;
        if (isset($CFG->block_sloodle_menu_show_notecardsetup))     $show_notecardsetup = (bool)$CFG->block_sloodle_menu_show_notecardsetup;
        
        
        if ($userresult === TRUE) {
            // Success
            // Make sure there was a name
            if (empty($sl_avatar_name)) $sl_avatar_name = '('.get_string('nameunknown', 'block_sloodle_menu').')';
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>';
            
            // Make the avatar name a link if the user management page exists
            if (SLOODLE_VERSION >= 0.21 && $show_myprofile) {
                $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_user.php?id={$USER->id}&amp;course={$COURSE->id}\">$sl_avatar_name</a>";
            } else {
                $this->content->text .= $sl_avatar_name;
            }

            $this->content->text .= '<br/></center>';
            
        } else if (is_string($userresult)) {
            // An error occurred
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>ERROR ('.$userresult.')</center>';
            
        } else {
            // No avatar linked yet
            $this->content->text .= '<center><span style="font-style:italic;">('.get_string('noavatar', 'block_sloodle_menu').')</span></center>';
        }
        
        // Add links to common Sloodle stuff
        $this->content->text .= '<div style="padding:1px; margin-top:4px; margin-bottom:4px; border-top:solid 1px #cccccc; border-bottom:solid 1px #cccccc;">';

        // Add the Sloodle profile link if we are running Sloodle >= 0.21
        if (SLOODLE_VERSION >= 0.21 && $show_myprofile) {
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/user.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_user.php?id={$USER->id}&amp;course={$COURSE->id}\">".get_string('mysloodleprofile', 'block_sloodle_menu')."</a><br/>";
        }     

        if ($show_distributor) {
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/boxes.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/sl_distrib/sl_send_object.php\">".get_string('objectdistributor', 'block_sloodle_menu')."</a><br/>";
        }

        if ($show_loginzone) {
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/loginzone.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/login/sl_loginzone_entry.php\">".get_string('loginzone', 'block_sloodle_menu')."</a><br/>";
        }
        
        // Only display these if the user is a teacher
        if (isteacherinanycourse()) {
            $this->content->text .= '<hr>';
            
            // Add the user management link if we are running Sloodle >= 0.21
            if (SLOODLE_VERSION >= 0.21 && $show_usermanagement) {
                $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/user_mng.gif\" width=\"16\" height=\"16\"/> ";
                $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_users.php?course={$COURSE->id}\">".get_string('usermanagement', 'block_sloodle_menu')."</a><br/>";
            }
            
        }
        
        // Only display these if the user is an admin
        if (isadmin()) {
            if ($show_sloodleconfig) {
                $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/configure.gif\" width=\"16\" height=\"16\"/> ";
                $this->content->text .= "<a href=\"{$CFG->wwwroot}/admin/module.php?module=sloodle\">".get_string('sloodleconfig', 'block_sloodle_menu')."</a><br/>";
            }
            
            if ($show_notecardsetup) {
                $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/notecard.gif\" width=\"16\" height=\"16\"/> ";
                $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/sl_setup_notecard.php\">".get_string('notecardsetuppage', 'block_sloodle_menu')."</a><br/>";
            }
        }
        
        $this->content->text .= '</div>';
        
        return $this->content;
    }
}

?>
