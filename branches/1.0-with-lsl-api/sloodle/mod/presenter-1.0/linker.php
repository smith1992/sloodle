<?php
    /**
    * Sloodle Presenter linker.
    * Allows a Sloodle Presenter object in-world to request a list of entries for a presentation.
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
    //  sloodlemoduleid = ID of a presenter
    //
    // Status code 1 will be returned on success.
    // Each data line specifies one entry in the presetnation, as follows:
    //  type|url|name
    // The type may be "image", "video" or "web".
    // In future, scaling values may be applied.
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
    $sloodle->load_module(SLOODLE_TYPE_PRESENTER, true);
    
    // Start preparing the response
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    
    // Output each URL and entry type
    $entries = $sloodle->module->get_entry_urls();
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            // Convert our type back to a legacy type for sake of old Presenters
			$entrytype = strtolower($entry[1]);
            $type = $entrytype;
            switch ($entrytype)
            {
            case 'presenterslideimage': case 'sloodlepluginpresenterslideimage': $type = 'image'; break;
            case 'presenterslideweb': case 'sloodlepluginpresenterslideweb': $type = 'web'; break;
            case 'presenterslidevideo': case 'sloodlepluginpresenterslidevideo': $type = 'video'; break;
            }

            $sloodle->response->add_data_line(array($type, $entry[0], $entry[2]));
        }
    }
    
    // Output our response
    $sloodle->response->render_to_output();
    
?>