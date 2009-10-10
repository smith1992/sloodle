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
     
     $avatarname = $sloodle->user->get_avatar_name(); 
     $avataruuid= $sloodle->user->get_avatar_uuid();     
     $sloodlecontrollerid=$sloodle->request->optional_param('sloodlecontrollerid');    
   
     
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
    
        
        $pluginName= $sloodle->request->required_param('plugin');
        /*attempt to load the $pluginName.  This will compare the $pluginName with the list of api_folders.
        * if pluginName matches a folder name in the api folders list, the path name for that plugin will be returned
        * 
        */
        $apiPath = $sloodle->api_plugins->get_api_plugin_path($pluginName);
        if (!$apiPath) {
            $sloodle->response->quick_output(-8721, 'APIPLUGIN', 'Failed to load path of the SLOODLE api plugin. Please check your "sloodle/plugin" folder.', false);
        exit();
        }
     
        
        //got path now, so include each file in that path
        $apiFiles = sloodle_get_files($apiPath);
        if (!$apiFiles) {
             $sloodle->response->quick_output(-8722, 'APIPLUGIN', 'No api filesexist in specified api plugin path', false);
             exit();
        }
      
        // Start by including the relevant base class files, if they are available
        @include_once(SLOODLE_DIRROOT.'/plugin/_api_base.php');
        @include_once($apiPath.'/_api_base.php');
        
        // Now, include each file in the api plugin path
        foreach ($apiFiles as $file) {
            // Include the specified file
            include_once($apiPath.'/'.$file);
        }

        
            $functionName = $sloodle->request->required_param('function');        
            $data=$sloodle->request->optional_param('data');//request data from the LSL request
            // Attempt to include the relevant  class
               
            // Create and execute the plugin instance
            $classname = 'SloodleApiPlugin'.$pluginName;
        
        
        if (!class_exists($classname)) {
            error("SLOODLE class missing: {$classname}");
            exit();
        }
        
        
        //if file exists, create class
         $plugin = new $classname();
            
        //add appropriate header
        
        //retrieve output data from the plugin
     
              
        //send output back to SL           
        $sloodle->response->add_data_line("RESPONSE:".$pluginName."|".$functionName);//line 1
        $plugin->{$functionName}($data);  
        $sloodle->response->render_to_output();
                  
     
?>
