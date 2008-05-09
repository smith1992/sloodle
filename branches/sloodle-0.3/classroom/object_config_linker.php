<?php
    /**
    * Sloodle object configuration linker.
    *
    * Allows objects in-world to download their configuration settings.
    *
    * @package sloodleclassroom
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script should be called with the following parameters:
    //
    //  sloodlecontrollerid = the controller being accessed
    //  sloodlepwd = password for authentication
    //  sloodleauthid = the object whose configuration is being downloaded
    //
    //
    // If succesful, status code 1 is returned, and each data line contains a name/value pair, like so:
    //
    //  1
    //  name|value
    //
    // (NOTE: may return no settings at all, but still status code 1)
    // Returns status code -103 if the object was not found.
    //
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request(true);
    
    // Get the object ID
    $sloodleauthid = (int)$sloodle->request->required_param('sloodleauthid');
    $auth_obj = SloodleController::get_object($sloodleauthid);
    if (!$auth_obj) {
        $sloodle->response->quick_output(-103, 'SYSTEM', 'Object not found', false);
        exit();
    }
    
    // Fetch all the configuration settings
    $settings = get_records('sloodle_object_config', 'object', $sloodleauthid);
    if (!$settings) $settings = array();
    foreach ($settings as $s) {
        $sloodle->response->add_data_line(array($s->name, $s->value));
    }
    
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    $sloodle->response->render_to_output();

    exit();
?>