<?php        
    /**
    * Sloodle HQ (for Sloodle 1.0).
    * Provides easy access to MOODLE for LSL Scripts
    *
    * @package HQ
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @copyright Paul Preibisch - aka Fire Centaur
    */
     
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
        /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** Sloodle Session code. */
    /** Grab the Sloodle/Moodle configuration. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');

    // Authenticate the request
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->validate_user();  
    
    
    //get user data from the http request 
     $avatarname = $sloodle->user->get_avatar_name(); 
     $avataruuid= $sloodle->user->get_avatar_uuid();     
     $sloodlecontrollerid=$sloodle->request->optional_param('sloodlecontrollerid');    
     $command=$sloodle->request->optional_param('command'); 

    /*
    * getFieldData - string data sent to the awards has descripters built into the message so messages have a context
    * when debugging.  ie: instead of sending 2|Fire Centaur|1000 we send:  USERID:2|AVNAME:Fire Centaur|POINTS:1000
    * This function just strips of the descriptor and returns the data field 
    * 
    * @param string fieldData - the field you want to strip the descripter from
    */
   function getFieldData($fieldData){
           $tmp = explode(":", $fieldData); 
           return $tmp[1];
    }
    
        //all plugins live in /mod/hq-1.0/plugins.
        //Each plugin can be called via this module
        //and execute code within the SLOODLE system
        //Once exectuted, data back to SL via a dataLine.
        
        $pluginName= $sloodle->request->required_param('plugin');
        $functionName = $sloodle->request->required_param('function');        
        $data=$sloodle->request->optional_param('data');//request data from the LSL request
      // Attempt to include the relevant  class
        $filename = SLOODLE_DIRROOT."/mod/hq-1.0/plugin/{$pluginName}.php";
           
        if (!file_exists($filename)) {
            error("SLOODLE file not found: mod/hq-1.0/plugin/{$pluginName}.php");
            exit();
        }       
         
       require_once($filename);
       
        // Create and execute the plugin instance
        $classname = 'sloodle_hq_plugin_'.$pluginName;
        
        
        if (!class_exists($classname)) {
            error("SLOODLE class missing: {$classname}");
            exit();
        }
        
        
        //if file exists, create class
         $plugin = new $classname();
            
        //add appropriate header
        
        //retrieve output data from the plugin
     
        //addMsgHeader($pluginName);        
        //send output back to SL           
        $sloodle->response->add_data_line("RESPONSE:".$pluginName."|".$functionName);//line 1
        $plugin->{$functionName}($data);  
        $sloodle->response->render_to_output();
                  
     
?>