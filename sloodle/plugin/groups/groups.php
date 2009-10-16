<?php
/****************************************************************************************************
* Defines a plugin class for the SLOODLE hq -
* 
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributer Paul G. Preibisch - aka Fire Centaur 
* 
*****************************************************************************************************/
/** SLOODLE course object data structure */


class SloodleApiPluginGroups  extends SloodleApiPluginBase{
  /**********************************************************
     * getUsersGrps will retrieve a list of groups the user is a member of for this course
     * called by: 
     * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:user,function:getUsersGroups\nSLOODLEID:null|USERNAME:"+avName+"|USERUUID:"+(string)avUuid, NULL_KEY);
     */
     function getUsersGrps($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        $data=$sloodle->request->optional_param('data'); 
        /***************************
        * Extract data from data stream
        ****************************/
        $bits = explode("|", $data);
        $avName=getFieldData($bits[0]);
        $avUuid=getFieldData($bits[1]);
        //build user
       $avUser = new SloodleUser( $sloodle );
        if ($avUser->load_avatar($avUuid,$avName)){
            if ($avUser->load_linked_user()){
                $userGrps = groups_get_all_groups($sloodle->course->get_course_id(),$avUser->get_user_id());
                if ($userGrps){    
                    $sloodle->response->set_status_code(1);          //@output status_code: 1 ok
                    $sloodle->response->set_status_descriptor('OK'); //line 0  
                    $sloodle->response->add_data_line("NUMGRPS:".count($userGrps));    
                    $sloodle->response->add_data_line("AVNAME:".$avName);    
                    $sloodle->response->add_data_line("AVUUID:".$avUuid);    
                    foreach ($userGrps as $grp){
                        $sloodle->response->add_data_line("GRP:".$grp->name);    
                    }//end foreach usergrp
                    return;
                }//endif userGrps
                else{ //return no groups found
                    $sloodle->response->set_status_code(-55000);     //@output status_code: -55000 user doesn’t have any groups
                    $sloodle->response->set_status_descriptor('GROUPS'); //line 0                      
                    $sloodle->response->add_data_line("AVNAME:".$avName);    
                    $sloodle->response->add_data_line("AVUUID:".$avUuid);                        
                    return;
                }//endelse                
            }//endif loadLinked
            else {   //return error user not linked              
                    $sloodle->response->set_status_code(-301);     //@output status_code: -301 There was an unspecified problem authenticating the user.
                    $sloodle->response->set_status_descriptor('USER_AUTH'); //line 0                      
                    $sloodle->response->add_data_line("AVNAME:".$avName);    
                    $sloodle->response->add_data_line("AVUUID:".$avUuid);                        
                    return;
            }//endelse
        }//endif load avatar
        else{ //return error not registered
            $sloodle->response->set_status_code(-321);     //@output status_code: -321 User was not registered and we weren't allowed to register them automatically.
            $sloodle->response->set_status_descriptor('USER_AUTH'); //line 0                      
            $sloodle->response->add_data_line("AVNAME:".$avName);    
            $sloodle->response->add_data_line("AVUUID:".$avUuid);                                     
            return;
        }//endelse*/
     }//function 
  
  
     /*********************************************
     * Gets all the groups in the current course
     * @param string|mixed $data - string with this format: INDEX:0|GROUPSPERPAGE:10
     * 
     ********************************************/
      function getGrps($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $index = getFieldData($bits[0]);
        $groupsPerPage = getFieldData($bits[1]);
        //get all groups in the course
        $groups = groups_get_all_groups($sloodle->course->get_course_id());
        $sloodle->response->set_status_code(1);             //line 0 
        $sloodle->response->set_status_descriptor('OK'); //line 0 
        $dataLine="";
        $counter = 0;
        foreach($groups as $g){
             if (($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage))){
                if ($counter!=0) $dataLine.="|";
                $dataLine .= "GRP:".$g->name;
                $groupMembers =groups_get_members($g->id);
                $numMembers = count($groupMembers);                
                $dataLine .= ",MBRS:".$numMembers;                
             $counter++;
           }//(($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage)))
        }//foreach
        $sloodle->response->add_data_line("INDEX:". $index);   
        $sloodle->response->add_data_line("numGroups:".$counter);//line 
        $sloodle->response->add_data_line($dataLine);//line         
     }//function
}//class
?>
