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
    //  sloodlepwd = password for authentication
    //  sloodleauthid = the object whose configuration is being downloaded
    //
    // The controller is identified by the object's authorisation.
    // If succesful, status code 1 is returned, and each data line contains a name/value pair, like so:
    //
    //  1|OK
    //  name|value
    //
    // Returns status code -103 if the object was not found.
    //
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Start a new Sloodle session
    $sloodle = new SloodleSession();
    
    // Get the object ID
    $sloodleauthid = (int)$sloodle->request->required_param('sloodleauthid');
    $auth_obj = SloodleController::get_object($sloodleauthid);
    if (!$auth_obj) {
        $sloodle->response->quick_output(-103, 'SYSTEM', 'Object not found', false);
        exit();
    }
    // Is the object authorised?
    if ($auth_obj->course->controller->is_loaded() == false || $auth_obj->user->is_user_loaded() == false) {
        $sloodle->response->quick_output(-103, 'SYSTEM', 'Object not authorised', false);
        exit();
    }
    
    // Authenticate the request
    $sloodle->course = $auth_obj->course; // The object doesn't know it's controller yet, but the database does.
    $_REQUEST['sloodlecontrollerid'] = $auth_obj->course->controller->get_id(); // Dirty hack... MUST BE TESTED!
    $sloodle->authenticate_request();
    
    // Add a note of the controller to the outgoing data
    $sloodle->response->add_data_line(array('sloodlecontrollerid', $auth_obj->course->controller->get_id()));
    
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