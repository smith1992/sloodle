<?php    
    
    /**
    * Sloodle LoginZone entry-point interface script.
    *
    * Provides an entry-point for Moodle users who want to create a new Sloodle loginzone to authenticate their avatar
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script is expected to be accessed manually via a web-browser.
    // The user will be required to login before using it.
    // On loading, if no Sloodle account is already linked to the Moodle account, the script will allocate
    //  and link a new Sloodle user for the logged-in Moodle account.
    // A SLurl will then be provided which the user may use to enter an in-world loginzone.
    // Their position when they login will be relayed back to the server, providing avatar authentication.
    
    require_once('../config.php');
    
    // Make sure the user is logged-in to Moodle
    require_login();
    
    // Display the Moodle page headers
    print_header('Teleport to Second Life', '', '', '', false, '', '', false, '');
	print_heading('Sloodle entrance to login zone');
    
    // Make sure it's not a guest who is logged in
    if (isguest()) {
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('noguestaccess', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // Include our other Sloodle libraries
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    // We need to use the Sloodle user functionality
    sloodle_debug_output("Instantiating SloodleUser object...<br/>");
    $user = new SloodleUser();
    $user->set_moodle_user_id($USER->id);
    
    //This string will contain the body of our response
    $body = "";
    
    // Determine whether or not the user already has a Sloodle account
    sloodle_debug_output("Attempting to find Sloodle user linked with Moodle account...<br/>");
    $has_sloodle_account = $user->find_linked_sloodle_user();
    // Determine if the account is authenticated (i.e. has both the avatar name and UUID)
    $sloodle_is_auth = FALSE;
    if ($has_sloodle_account) {
        $sloodle_is_auth = (!(empty($user->sloodle_user_cache->avname)) && !(empty($user->sloodle_user_cache->uuid)));
    }
    
    // Did an error occur?
    if (is_string($has_sloodle_account)) {
        // An error occurred
        sloodle_debug_output("-&gt;An error occurred.<br/>");
        $body .= "<h3>".get_string('error','sloodle')."</h3>\n";
        $body .= "<p>".get_string('errorlinkedsloodleuser', 'sloodle')."</p>";
        if (SLOODLE_DEBUG && is_string($has_sloodle_account)) {
            $body .= "<p>(DEBUG): $has_sloodle_account</p>";
        }
        
    } else if ($has_sloodle_account && $sloodle_is_auth) {
        // The user has a Sloodle account and is fully authenticated
        sloodle_debug_output("-&gt;Found Sloodle user - fully authenticated.<br/>");
        
        // Generate an appropriate teleport link outside the loginzone, since the user is already authenticated
        $coord = sloodle_finished_login_coordinates();
        $region = sloodle_get_loginzone_region();
        if ($region === FALSE || $coord === FALSE) {
            $body .= "<p>".get_string('loginzone:datamissing', 'sloodle')."<br/>\n";
            $body .= get_string('loginzone:mayneedrerez', 'sloodle')."</p>\n";
        } else {
            $link = "secondlife://$region/{$coord['x']}/{$coord['y']}/{$coord['z']}";
    		$body .= "<p>".get_string('alreadyauthenticated', 'sloodle')."<br/>\n";
            $body .= get_string('avatarname', 'sloodle').": ".$user->sloodle_user_cache->avname."<br/>\n";
            $body .= "<a href=\"$link\">".get_string('clicktoteleportanyway', 'sloodle')."</a></p>\n";
        }
        
    } else {
        // We want to know if the registration process is successful
        $result = TRUE;
        if ($has_sloodle_account) {
            sloodle_debug_output("-&gt;User already has Sloodle account.<br/>");
        } else {
            // User has no Sloodle account at all
            sloodle_debug_output("-&gt;No linked Sloodle user.<br/>");
            // Create an empty Sloodle user, linked to the Moodle account
            sloodle_debug_output("Created Sloodle user for Moodle account...<br/>");
            if ($user->create_sloodle_user('', '', $user->get_moodle_user_id()) !== TRUE) {
                // Something went wrong
                sloodle_debug_output("-&gt;Failed to add user to database.<br/>");
                $body = "<p>".get_string('failedcreatesloodleuser', 'sloode')."</p>\n";
                $result = FALSE;
            } else {
                // Success
                sloodle_debug_output("-&gt;Success.<br/>");
            }
        }
        
        // This will store our login position
        $loginpos = array();
        // Calculate a login position expiry time, 15 minutes from now
        $expires = time() + 900;
        
        // Has the use never been given a login position, or has it expired?
        if ($result && (empty($user->sloodle_user_cache->loginposition) || (int)$user->sloodle_user_cache->loginpositionexpires < time())) {
            // Generate a new login zone position
            sloodle_debug_output("Generating new login position.<br/>");
            $loginpos = $user->generate_login_position($expires);
            if (is_array($loginpos)) {
                // Successful allocation
                sloodle_debug_output("-&gt;Success.<br/>");
            } else if ($loginpos === FALSE) {
                // No positions left to allocate
                sloodle_debug_output("-&gt;Failed.<br/>");
                $body .= "<p>".get_string('loginzone:allocationfailed','sloodle')."</p>";
                $result = FALSE;
            } else {
                // An error occurred
                if (is_string($result)) {
                    sloodle_debug_output("-&gt;ERROR: $result.<br/>");
                } else {
                    sloodle_debug_output("-&gt;ERROR: error not specified.<br/>");
                }
                $result = FALSE;
                $body .= "<p>".get_string('loginzone:allocationerror','sloodle')."</p>";
            }
            
        } else {
            // Renew the expiry time (give it another 15 minutes from now) and use the existing login position
            sloodle_debug_output("Renewing current login-position.<br/>");
            $user->sloodle_user_cache->loginpositionexpires = $expires;
            $user->update_sloodle_user_cache_to_db();
            $loginpos = sloodle_vector_to_array($user->sloodle_user_cache->loginposition);
        }
        
        // Attempt to get the region for the loginzone
        $region = sloodle_get_loginzone_region();
        if ($region === FALSE) {
            $body .= "<p>".get_string('loginzone:datamissing', 'sloodle')."<br/>\n";
            $body .= get_string('loginzone:mayneedrerez', 'sloodle')."</p>\n";
            $result = FALSE;
        }

        // Did everything go OK?
        if ($result) {
            // Construct the teleport link
            $link = "secondlife://$region/{$loginpos['x']}/{$loginpos['y']}/{$loginpos['z']}";
            // Add instructions and the teleport link to the body
            $body .= "<p>".get_string('loginzone:useteleportlink','sloodle')."</p>\n";
            $body .= "<p style=\"font-size:150%;\"><a href=\"$link\">".get_string('loginzone:teleport','sloodle')."</a></p>";
            $body .= "<p>".get_string('loginzone:expirynote', 'sloodle')."</p>\n";
        }
    }
    
    
    // Output the response
    echo '<div style="text-align:center;">';    
    echo $body;
    echo '</div>';
    
    print_footer();
    exit();
?>
