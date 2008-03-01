<?php
    // Sloodle avatar registration page
    // Allows users who have clicked an in-world registration booth to complete
    //  their avatar registration by logging-in to their Moodle account (or
    //  creating one).
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) Sloodle 2007
    // Released under the GNU GPL
    //
    // Contributors:
    //  Edmund Edgar - original design and implementation
    //  Peter R. Bloomfield - updated to use new API
    //
    
    // This script is expected to be visited by a user with a web browser.
    // The following request parameters (GET or POST) are required for an initial page view:
    //
    //  sloodleuuid - the UUID of the avatar which is being registered
    //  sloodlelst - the login security token generated for the registration attempt
    //
    // Optionally, objects which need to know when registration has been accomplished can provide the following:
    //
    //  sloodlechannel - UUID of an XMLRPC channel
    //
    // (Note that the avatar name can be given in parameter 'sloodleavname' instead of the UUID parameter, but this is not recommended)
    //
    // In most cases, after login, the above parameters will be sufficient.
    // However, where the same avatar is already registered to another Moodle user,
    //  the user will be asked for confirmation. At this point, an additional
    //  parameter will be added:
    //
    //  sloodleconfirm - 'true' if the change/overwrite of existing details is confirmed
    //
    
    // At some stage, we may implement the use of an XMLRPC channel to send a
    //  confirmation back to the in-world object which initiated the registration.
    

    require_once('../config.php'); // Sloodle/Moodle configuraton
    
    // Make sure the Moodle user is logged-in
    require_login();
    
    require_once(SLOODLE_DIRROOT.'/sl_debug.php'); // Enables debug mode if necessary... very useful! :-)
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php'); // LSL handling code - we use this here for user processing   
    
    print_header('Welcome to sloodle', '', '', '', false, '', '', false, '');
	print_heading('Welcome to sloodle');
    
    // Make sure it's not a guest who is logged in
    sloodle_debug_output('Ensuring logged-in user is not a guest...<br/>');
    if (isguest()) {
        sloodle_debug_output('User is a guest.<br/>');
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('noguestaccess', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // Process the request data
    sloodle_debug_output('Constructing an LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing request data...<br/>');
    $lsl->request->process_request_data();
    
    // Get an additional channel paramter if it was specified
    $channel = optional_param('sloodlechannel', NULL, PARAM_RAW);
    
    // Make sure a Sloodle user has been identified
    sloodle_debug_output('Checking if a Sloodle user has been identified...<br/>');
    if ($lsl->user->get_sloodle_user_id() <= 0) {
        sloodle_debug_output('Failed to identify a Sloodle user.<br/>');
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('avatarnotfound', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // Check that we could verify the Sloodle user by the security token
    sloodle_debug_output('Attempting to confirm Sloodle user by login security token...<br/>');
    if ($lsl->confirm_by_login_security_token(TRUE) !== TRUE) {
        // Verification failed
        sloodle_debug_output('Failed to confirm Sloodle user by login security token.<br/>');
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('loginsecuritytokenfailed', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // TODO: add confirmation for changing avatar to different account...
    
    
    // Get the Moodle user ID
    $lsl->user->set_moodle_user_id($USER->id);
    // Link the accounts together
    sloodle_debug_output('Attempting to link Sloodle user to Moodle account...<br/>');
    $linkresult = $lsl->user->link_users();
    if ($linkresult !== TRUE) {
        sloodle_debug_output('Failed to link users.<br/>');
        if (is_string($linkresult)) sloodle_debug_output("Error message: $linkresult<br/>");
        else sloodle_debug_output(' No error message given.<br/>');
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('userlinkfailed', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // Success!
    sloodle_debug_output('Successfully linked avatar to Moodle account.<br/>');
    
    echo "<div style=\"text-align:center\">\n";
     echo get_string('welcometosloodle','sloodle').', '.$lsl->request->get_avatar_name().'<br /><br />'.get_string('userlinksuccessful','sloodle');
    echo "</div>\n";

    // TODO: update all this bit to use the response object, localisation strings, and debug messages
    
    // If the object passed us a channel parameter, we'll use it to tell the object that the authentication is done.
    // (Parameter name: sloodlechannel)
    if (is_string($channel) && !empty($channel)) {
        flush();
        sloodle_debug_output('Preparing XMLRPC confirmation message...<br/>');
        
        // Prepare a response as a string
        $str = '';
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('USER_AUTH');
        $lsl->response->add_data_line('User has been successfully registered.');
        $lsl->response->render_to_string($str);
        
        // XMLRPC hack -- double escape the newlines
        $str = str_replace("\n", "\\n", $str);
        
        sloodle_debug_output('Sending XMLRPC confirmation message...<br/>');
        $xmlrpcresult = sloodle_send_xmlrpc_message($channel, 0, $str);
        if (!$xmlrpcresult) {
            sloodle_debug_output('ERROR: failed to send XMLRPC confirmation message.<br/>');
            echo '<div style="text-align:center;">';
            echo 'ERROR: Unable to tell the object that sent you here that you have been authenticated.';
            echo '</div>';
        } else {
            sloodle_debug_output('Successfully sent XMLRPC confirmation message.<br/>');
        }
    } else {
        sloodle_debug_output('XMLRPC confirmation message not requested.<br/>');
    }
    
    sloodle_debug_output('Finished.<br/>');
    
    print_footer();
    exit();

?>
