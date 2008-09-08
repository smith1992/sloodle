<?php
    /**
    * Sloodle Slideshow linker.
    * Allows a Sloodle Slideshow object in-world to request a list of images for a slideshow.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    // This script should be called with the following parameters:
    //  sloodlecontrollerid = ID of a Sloodle Controller through which to access Moodle
    //  sloodlepwd = the prim password or object-specific session key to authenticate the request
    //  sloodlemoduleid = ID of a chatroom
    //
    // Status code 1 will be returned on success.
    // The data lines will contain a URL on each line, each URL pointing at an image.
    //
    

    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request, and load a slideshow module
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->load_module('slideshow', true);
    
    // Start preparing the response
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    
    // Output each URL
    $imageurls = $sloodle->module->get_image_urls();
    if (is_array($imageurls)) {
        foreach ($imageurls as $img) {
            $sloodle->response->add_data_line($img);
        }
    }
    
    // Output our response
    $sloodle->response->render_to_output();
    
?>
