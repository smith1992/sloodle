<?php

@include_once($CFG->dirroot .'/mod/sloodle/config.php');
if (defined('SLOODLE_DIRROOT')) {
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_userlib.php');
}

class block_sloodle_menu extends block_base {

    function init() {
        global $CFG;
        
        $this->title = get_string('blockname', 'block_sloodle_menu');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2008012300;
    }
    
    function has_config() {
        return false; // change to true if we want to use "global_config.html" for block configuration
    }
    
    function hide_header() {
        return false; // change to true if we want to hide the block header
    }

    function get_content() {
        global $CFG, $course, $USER;
        
        // Construct the content
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        // If no course has been specified, then we are using the site course
        if (!isset($course)) {
            $course = SITEID;
        }
        
        // If the user is not logged in or if they are using guest access, then we can't show anything
        if (!isloggedin() || isguest()) {
            return $this->content;
        }
        
        // Has the Sloodle activity module been installed?
        if (!(function_exists("sloodle_is_installed") && sloodle_is_installed())) {
            $this->content->text = get_string('sloodlenotinstalled', 'block_sloodle_menu');
            return $this->content;
        }       
        
        // Add the Sloodle version info to the footer of the block
        $this->content->footer = '<span style="color:#565656;font-style:italic;">'.get_string('sloodleversion', 'block_sloodle_menu').': '.(string)SLOODLE_VERSION.'</span>';
                
        // Attempt to find a Sloodle user for the Moodle user
        $sl_user = new SloodleUser();
        $sl_user->set_moodle_user_id($USER->id);
        $sl_avatar_name = '';
        $userresult = $sl_user->find_linked_sloodle_user();
        
        if ($userresult === TRUE) {
            // Success - store the avatar name locally
            $sl_avatar_name = $sl_user->sloodle_user_cache->avname;
            // Make sure there was a name
            if (empty($sl_avatar_name)) $sl_avatar_name = '('.get_string('nameunknown', 'block_sloodle_menu').')';
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>'.$sl_avatar_name.'</center>';
            
        } else if (is_string($userresult)) {
            // An error occurred
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>ERROR ('.$userresult.')</center>';
            
        } else {
            // No avatar linked yet
            $this->content->text .= '<center><span style="font-style:italic;">('.get_string('noavatar', 'block_sloodle_menu').')</span></center>';
        }
        
        // Add links to common Sloodle stuff
        $this->content->text .= '<div style="padding:1px; margin-top:4px; margin-bottom:4px; border-top:solid 1px #cccccc; border-bottom:solid 1px #cccccc;">';
        
        $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/boxes.gif\" width=\"16\" height=\"16\"/> ";
        $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/sl_distrib/sl_send_object.php\">".get_string('objectdistributor', 'block_sloodle_menu')."</a><br/>";
        
        $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/loginzone.gif\" width=\"16\" height=\"16\"/> ";
        $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/login/sl_loginzone_entry.php\">".get_string('loginzone', 'block_sloodle_menu')."</a><br/>";
        
        // Only display these if the user is an admin
        if (isadmin()) {
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/configure.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/admin/module.php?module=sloodle\">".get_string('sloodleconfig', 'block_sloodle_menu')."</a><br/>";
            
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/notecard.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/sl_setup_notecard.php\">".get_string('notecardsetuppage', 'block_sloodle_menu')."</a><br/>";
        }
        
        $this->content->text .= '</div>';
        
        
        return $this->content;
    }
}

?>