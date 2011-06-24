<?php

    /**
    * This file is part of SLOODLE Tracker.
    * Copyright (c) 2009 Sloodle
    *
    * SLOODLE Tracker is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * SLOODLE Tracker is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License.
    * If not, see <http://www.gnu.org/licenses/>
    *
    * Contributors:
    * Peter R. Bloomfield  
    * Julio Lopez (SL: Julio Solo)
    * Michael Callaghan (SL: HarmonyHill Allen)
    * Kerri McCusker  (SL: Kerri Macchi)
    *
    * A project developed by the Serious Games and Virtual Worlds Group.
    * Intelligent Systems Research Centre.
    * University of Ulster, Magee	
    */



    /**
    * Allows an activity tool in SecondLife to send the activity tracker to MOODLE
    * (Creates a new entry in the 'sloodle_activity_tracker' DB table.)
    *
    */
    
    // This script should be called with the following parameters:
    //  sloodlemoduleid = ID of a SecondLife Tracker in MOODLE
    //  sloodleobjuuid = UUID of the object
    //  sloodleavuuid = UUID of the avatar
    //
 
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request, and load a chat module
    //$authrequired = (isset($_REQUEST['sloodlecontrollerid']) || isset($_REQUEST['sloodlepwd']));
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->load_module(SLOODLE_TYPE_TRACKER, true);

    //$request_auth = $sloodle->authenticate_request($authrequired);
    // If server access level is public, then validation is not essential... otherwise, it is
    $sloodleserveraccesslevel = $sloodle->request->get_server_access_level(false);
    if ($sloodleserveraccesslevel == 0) $sloodle->validate_user(false);
    else $sloodle->validate_user(true);
    
    // Get the parameters
	$sloodletrackerid = $sloodle->request->required_param('sloodlemoduleid');
    $sloodleobjuuid = $sloodle->request->required_param('sloodleobjuuid');
    $sloodleavuuid = $sloodle->request->required_param('sloodleuuid');
    
    // Create a SloodleModuleTracker instance and a new entry in the 'sloodle_activity_tracker' DB table.
    $tracker = new SloodleModuleTracker($sloodle);
    
    $authid = $tracker->record_action($sloodletrackerid,$sloodleobjuuid,$sloodleavuuid);
  
    // Set the response parameters
    if ($authid) {
            $sloodle->response->set_status_code(1);
            $sloodle->response->set_status_descriptor('OK');
            $sloodle->response->add_data_line($authid);
    } else {
            $sloodle->response->set_status_code(-201);
            $sloodle->response->set_status_descriptor('OBJECT_AUTH');
            $sloodle->response->add_data_line('Failed to register the action.');
    }
    
    // Render the output
    sloodle_debug('<pre>');
    $sloodle->response->render_to_output();
    sloodle_debug('</pre>');

?>