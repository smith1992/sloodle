<?php
/**
* Defines a plugin class for the SLOODLE hq -
* 
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributer Paul G. Preibisch - aka Fire Centaur 
* 
* ADDGROUP - This function will create a new group in the course specified only if it doesnt already exist
* 
* Data expected: SOURCEUUID:uuid|groupName:name
*        
* @return: HTTP RESPONSE:
* 
* LINE 0: status code | status descriptor  
*             778001  |  GROUPS             - for some reason could not create a group, possible database problem?
*             778002  |  GROUPS             - group already exists 
*                  1  |  OK                 - group added successfully
* 
* LINE 1:  SENDERUUID:uuid of sender
* LINE 2:  RESPONSE:CREATEGROUPOK
* LINE 3:  GROUPNAME:name|GROUPID:gid  //data line
*/
/** SLOODLE course object data structure */
/** SLOODLE course object data structure */

/** SLOODLE awards object data structure */


class sloodle_hq_plugin_authenticate {

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
    
     function getTools($data){
        global $sloodle;
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $index = $this->getFieldData($bits[0]);        
        $registrants = get_records('sloodle_hq_registrant','controllerid',$sloodle->course->controller->sloodle_controller_instance->id);
        
        if ($registrants){
            $sloodle->response->set_status_code(1);          //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0
            $sloodle->response->add_data_line("TOTALITEMS:".count($registrants));
            $sloodle->response->add_data_line("INDEX:".$index);            
            foreach ($registrants as $r){
                $sloodle->response->add_data_line("ID:".$r->id."|TYPE:".$r->type."|LOC:".$r->location."|".$r->url); 
            }//foreach
        }else{
            $sloodle->response->set_status_code(-92110); //no registrants
            $sloodle->response->set_status_descriptor('HQ'); //line 0
            $sloodle->response->add_data_line("TOTALITEMS:".count($registrants));
            $sloodle->response->add_data_line("INDEX:".$index);            
        }//else        
     }//function
     
     function addRegistrant($data){
         global $sloodle;
            //llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:authenticate,
            //function:addRegistrant\nSLOODLEID:null\nTYPE:"+type+"|URL:"+url+
            //"|LOCATION:"+llEscapeURL((string)llGetPos()), NULL_KEY);        global $sloodle;
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $type= $this->getFieldData($bits[0]);        
        $url= $bits[1];        
        $location= $this->getFieldData($bits[2]);        
        if ($type&&$url){
            $r= new stdClass();
            $r->type=$type;
            $r->url=$url;
            $r->location = $location;
            $r->controllerid = $sloodle->course->controller->sloodle_controller_instance->id;
            $r->course = $sloodle->course->get_course_id();
            if (insert_record('sloodle_hq_registrant',$r)){
                $sloodle->response->set_status_code(1);          //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0
            }
            else { //report error
                $sloodle->response->set_status_code(-92111);          //could not insert registrant
                $sloodle->response->set_status_descriptor('HQ'); //line 0
            }
        }
     }
}//class
?>

