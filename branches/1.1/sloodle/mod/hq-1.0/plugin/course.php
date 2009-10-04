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
require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');

class sloodle_hq_plugin_course {

      

     function getDetails($data){
         global $sCourseObj;
         global $sloodle;
         $userList = $sCourseObj->getUserList();
         $avatarList = $sCourseObj->getAvatarList($userList);
         $numSlStudents = count($avatarList);
         $sloodle->response->set_status_code(1);          //line 0    
         $sloodle->response->set_status_descriptor('OK'); //line 0              
         $dataLine="numAvatars:".$numSlStudents;
         $fullname = $sloodle->course->get_full_name();
         $shortname = $sloodle->course->get_short_name();        
         $sloodle->response->add_data_line($fullname);
         $sloodle->response->add_data_line($shortname);
         $sloodle->response->add_data_line($dataLine);
     }  
}
?>
