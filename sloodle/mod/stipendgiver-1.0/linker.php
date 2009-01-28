<?php
    /**
    * Sloodle distributor linker (for Sloodle 0.4).
    * Allows a Sloodle Vending Machine to update its inventory and XMLRPC channel details.
    *
    * @package sloodledistributor
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Paul Preibisch - aka Fire Centaur
    * 
    */
    
    // This script should be called with the following parameters:
    //  sloodlecontrollerid = ID of a Sloodle Controller through which to access Moodle
    //  sloodlepwd = the prim password or object-specific session key to authenticate the request
    //  sloodlemoduleid = ID of a chatroom
    //  sloodleinventory = a pipe-separated list of names of items in the obect's inventory
    //  sloodlechannel = the UUID of an XMLRPC channel which can be used to request object distribution
    //
    // The following parameter is optional:
    //  sloodledebug = if 'true', then Sloodle debugging mode is activated    
    

    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
      
    // Authenticate the request, and load a chat module
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->validate_user();   
    $avatarname = $sloodle->user->get_avatar_name(); 
    $avataruuid= $sloodle->user->get_avatar_uuid(); 
    $sloodle->load_module('stipendgiver', true);
    $amount = $sloodle->module->get_amount();
    $sloodleid=$sloodle->request->optional_param('sloodlemoduleid'); 
    if (!$sloodle->module->alreadyPaid($avatarname,$avataruuid,$sloodleid)) {
        $sloodle->response->add_data_line('OKTOWITHDRAW'); 
       // echo "oktowithdraw";
    }
        else 
        {
            $sloodle->response->add_data_line('ALREADYWITHDREW');
        //    echo "alreadywithdrew";
        }
       
    
    // Everything seems fine
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    $sloodle->response->add_data_line($amount); 
    $sloodle->response->add_data_line($avataruuid);
    $sloodle->response->add_data_line($avatarname);
    $sloodle->response->add_data_line($sloodle->module->get_name());
    $sloodle->response->add_data_line(strip_tags($sloodle->module->get_intro()));
    
  
    // Output our response
    $sloodle->response->render_to_output();
    
?>
