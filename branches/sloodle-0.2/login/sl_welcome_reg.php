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
    require_once(SLOODLE_DIRROOT.'lib/sl_lsllib.php'); // LSL handling code - we use this here for user processing   
    
    print_header('Welcome to sloodle', '', '', '', false, '', '', false, '');
	print_heading('Welcome to sloodle');
    
    // Make sure it's not a guest who is logged in
    sloodle_debug('Ensuring logged-in user is not a guest...<br/>');
    if (is_guest()) {
        sloodle_debug('User is a guest.<br/>');
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
    sloodle_debug('Constructing an LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug('Processing request data...<br/>');
    $lsl->process_request_data();
    
    // Make sure a Sloodle user has been identified
    sloodle_debug('Checking if a Sloodle user has been identified...<br/>');
    if ($lsl->user->get_sloodle_user_id() <= 0) {
        sloodle_debug('Failed to identify a Sloodle user.<br/>');
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
    sloodle_debug('Attempting to confirm Sloodle user by login security token...<br/>');
    if ($lsl->confirm_by_login_security_token(TRUE) !== TRUE) {
        // Verification failed
        sloodle_debug('Failed to confirm Sloodle user by login security token.<br/>');
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
    sloodle_debug('Attempting to link Sloodle user to Moodle account...<br/>');
    $linkresult = $lsl->user->link_users();
    if ($linkresult !== TRUE) {
        sloodle_debug('Failed to link users.<br/>');
        if (is_string($linkresult) sloodle_debug(" Error message: $linkresult<br/>");
        else sloodle_debug(' No error message given.<br/>');
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
    sloodle_debug('Successfully linked avatar to Moodle account.<br/>');
    
    echo "<div style=\"text-align:center\">\n";
	 print_simple_box(get_string('welcometosloodle','sloodle').', '.$lsl->request->get_avatar_name().'<br /><br />'.get_string('userlinksuccessful','sloodle'));
    echo "</div>\n";

    // TODO: fetch XMLRPC channel parameter (up top preferably)
    // TODO: update all this bit to use the response object, localisation strings, and debug messages
    
    // If the object passed us a channel parameter, we'll use it to tell the object that the authentication is done.
    if (($channel != null) && ($channel != '')) {
        
        flush();
        
        $xmlrpcresult = sloodle_send_xmlrpc_message($channel,0,"1|SLOODLE_AUTHENTICATION_DONE|".$sloodleuser->uuid);
        if (!$xmlrpcresult) {
            print '<center>';
            print_simple_box('Error: Unable to tell the object that sent you here that you have been authenticated.');
            print '</center>';
        }
    }
    
    
    exit();
    /// OLD CODE:

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	// TODO: It would be nice, in a case where a user who we know about already has come here with a valid security code, to allow them to log straight in without forcing them to enter their password, etc.
	// However, this has some security implications that we'll need to think through before we do this...
	// (For example, we currently store the security code un-hashed..., which is less secure than the way Moodle doesthings...)
	require_login(); // this will send the user to the registration / login page, and bring them back here, hopefully with the uuid and lsc arguments intact, when they're done.

	print_header('Welcome to sloodle', '', '', '', false, '', '', false, '');
	print_heading('Welcome to sloodle');

	$loginsecuritytoken = required_param('sloodlelst',PARAM_RAW); // Login security token - this should already be in the database for the identified Sloodle user
	//$channel = optional_param('ch',NULL,PARAM_RAW); // optional channel code to tell the object we're done.
		
	if (!$sloodleuser = sloodle_get_sloodle_user_for_security_code($lsc)) {
		print '<center>';
		print_simple_box('Error: Could not find a user for your security code');
		print '</center>';
		print_footer();
		exit;
	}

	if ( ( $sloodleuser->userid == null ) || ($sloodleuser->userid == 0) ) {
	// we don't yet have them matched up
		$result = sloodle_match_sloodle_user_to_current_user($sloodleuser);
		if (!$result) {
			print '<center>';
			print_simple_box('Error: We could not match up your Second Life name to your Moodle name due to a technical problem. Please try again later.');
			print '</center>';
			print_footer();
			exit;
		}
	}

	print '<center>';
	print_simple_box('Welcome to SLoodle, '.$sloodleuser->avname.'<br /><br />You have now been registered at this site.<br /><br />From now on, Sloodle objects in Second Life should recognize you automatically.');
	print '</center>';

	// If the object passed us a channel parameter, we'll use it to tell the object that the authentication is done.
	// If not, the avatar will just have to touch the object again.
	if ( ($channel != null) && ($channel != '') ) {
		//print "<h1>channel is :$channel:</h1>";
		flush();
		$xmlrpcresult = sloodle_send_xmlrpc_message($channel,0,"OK|SLOODLE_AUTHENTICATION_DONE|".$sloodleuser->uuid);
		if (!$xmlrpcresult) {
			print '<center>';
			print_simple_box('Error: Unable to tell the object that sent you here that you have been authenticated.');
			print '</center>';
		}

	}

	print_footer();
	exit;

?>
