<?php
    /**
    * Sloodle avatar registration page, modified for the SLOODLE for Schools project.
    *
    * Allows users who have started the avatar registration process on the web-portal to complete it on Moodle.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-10 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    /*
    * This script is expected to be visited by a user with a web browser.
    * The following request parameters (GET or POST) are required:
    *
    *  sloodleuuid - the UUID of the avatar which is being registered
    *  sloodlelst - the login security token generated for the registration attempt
    *
    */
    
    /** Include Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    sloodle_require_login_no_guest(); // Asks guest users to login properly
    
    /** Include the Sloodle API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Display the page header
    print_header_simple(get_string('welcometosloodle', 'sloodle'), "", get_string('welcometosloodle', 'sloodle'), "", "", true);
    
    
    // Process the request data
    $sloodle = new SloodleSession();
    
    // Check parameters
    $sloodleuuid = required_param('sloodleuuid', PARAM_TEXT);
    $sloodlelst = required_param('sloodlelst', PARAM_TEXT);
    
    // Attempt to find a pending avatar entry which matches the given details
    $pa = get_record('sloodle_pending_avatars', 'uuid', $sloodleuuid, 'lst', $sloodlelst);
    if (!$pa) {
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('pendingavatarnotfound', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
		exit();
    }
    
    // Add the new avatar
    if (!$sloodle->user->add_linked_avatar($USER->id, $sloodleuuid, $pa->avname)) {
        // Failed
        ?>
        <div style="text-align:center;">
         <h3><?php print_string('error', 'sloodle'); ?></h3>
         <p><?php print_string('failedcreatesloodleuser', 'sloodle'); ?></p>
        </div>
        <?php
        print_footer();
        exit();
    }

    // Delete any other avatars associated with this user.
    delete_records_select('sloodle_users', "userid = {$USER->id} AND (uuid <> '{$sloodleuuid}' OR avname <> '{$pa->avname}')");

    
    // Display the success message.
    echo "<br/><br/>\n";
    echo "<div style=\"text-align:center; font-size:120%;\">\n";
    echo get_string('welcometosloodle','sloodle').', '.$pa->avname.'<br /><br />'.get_string('userlinksuccessful','sloodle');
    echo "</div>\n";
    
    // After a short pause, redirect the user to the site's frontpage
    $url = $CFG->wwwroot;
    echo "<br/><br/><br/>\n";
    redirect($url, '', 5);
    
    print_footer();
    exit();

?>
