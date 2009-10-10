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
require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
/** SLOODLE awards object data structure */
require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');

class SloodleApiPluginUser  extends SloodleApiPluginBase{

        
    public function balanceSort($a, $b){
        if ($a->balance == $b->balance) {
            return 0;
        }
        return ($a->balance > $b->balance) ? -1 : 1;
    }
    public function nameSort($a, $b){
        if ($a->avname == $b->avname) {
            return 0;
        }
        return ($a->avname < $b->avname) ? -1 : 1;
    }
     
     /**
     * getUserList Returns a list of users in the course
     * @return array of table rows
     */
      function getUserList(){
          global $sloodle;   
          global $CFG;            
           //get all the users from the users table in the moodle database that are members in this class   
           $sql = "select u.*, ra.roleid from ".$CFG->prefix."role_assignments ra, ".$CFG->prefix."context con, ".$CFG->prefix."course c, ".$CFG->prefix."user u ";
           $sql .= " where ra.userid=u.id and ra.contextid=con.id and con.instanceid=c.id and c.id=".$sloodle->course->controller->cm->course;
           $fullUserList = get_records_sql($sql);          
           return $fullUserList;                          
      }
        /**
        * getAvatarList Returns a list of avatars in the course
        * @param $userList an array of users of the site
        * @return array of table rows of avatars (userid,username,avname,uuid)
        */
      function getAvatarList($userList){
         $avList = array();
         if ($userList){
         foreach ($userList as $u){             
             $sloodledata = get_records('sloodle_users', 'userid', $u->id);   
             //only adds users who have a linked avatar
             if ($sloodledata){
                foreach ($sloodledata as $sd){
                   $av = new stdClass(); 
                   $av->userid = $u->id;
                   $av->username = $u->username;                                      
                   $av->avname = $sd->avname;
                   $av->uuid = $sd->uuid;                   
                   $avList[]=$av;
                  
                }
               }
             }
         }
         return $avList;
      } 
     /*
     *   getClassList($data) will return all users with avatars in a course, along with other data:
     *   UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
     *   llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "PLUGIN:user,FUNCTION:getClassList\nAWARDID:"+(string)currentAwardId+"\nSENDER:"+(string)owner+"|INDEX:"+(string)index+"|       *   SORTMODE:"+sortMode, NULL_KEY);
     */ 
      function getClassList($data){
         global $sloodle;
         //sloodleid is the id of the activity in moodle we want to connect with
         $awardModuleId = $sloodle->request->optional_param('sloodleid');
         //$cmid=$sloodle->request->required_param('sloodlemoduleid');          
         $cm = get_coursemodule_from_instance('sloodle',$awardModuleId);
         $cmid = $cm->id;
         $sCourseObj = new sloodleCourseObj($cmid);  
         $awardsObj = new Awards((int)$cmid);
         $NUM_USERS_TO_RETURN=10;          
           $data = $sloodle->request->required_param('data');            
           $bits = explode("|", $data);
           $senderUuid= getFieldData($bits[0]);           
           $index        = getFieldData($bits[1]);
           $sortMode     = getFieldData($bits[2]);  
          /*  Send message back into SL
           *      LINE   MESSAGE
           *      0)     1 | OK
           *      1)     SENDERUUID:uuid
           *      2)     NUMUSERS:12
           *      3)     INDEX:0
           *      4)     SORTMODE:name/balance
           *      4)     UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
           *      5)     UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
           *      6)     UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
           *      7)     ...
           *      8)     UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
           *      9)     EOF
           */
            $sloodle->response->set_status_code(1);             //line 0    1
            $sloodle->response->set_status_descriptor('OK');    //line 0    OK  
            $sloodle->response->add_data_line("SENDER:".$senderUuid);//line 1                     
            //---sloodledata:
           //  | avuuid 
           //  | moodle user name 
           //  | Avatar Name 
           //  | stipendAllocation 
           //  | user debits
            $userList = $this->getUserList();
            //getAvatarlist returns an array of stdObj  
            //$av->userid 
            //$av->username 
            //$av->avname 
            //$av->uuid             
           
            $avatarList = $this->getAvatarList($userList);
              
            //add balance fields from ipoint_trans database then sort
            $avList= array();
             
            if ($avatarList){
                foreach ($avatarList as $u){
                       $av = new stdClass(); 
                       $av->userid = $u->userid;
                       $av->username = $u->username;                                      
                       $av->avname = $u->avname;
                       $av->uuid = $u->uuid;
                       $av_balanceDetails= $awardsObj->awards_getBalanceDetails($u->userid);
                       $av->balance = $av_balanceDetails->balance;                   
                       $av->credits = $av_balanceDetails->credits;
                       $av->debits = $av_balanceDetails->debits;  
                       $avList[]=$av;
  
                }//foreach
                 //sort by points
                 if ($sortMode=="balance") usort($avList, array("sloodle_hq_plugin_user", "balanceSort")); else
                 if ($sortMode=="name") usort($avList,  array("sloodle_hq_plugin_user", "nameSort"));
                $sloodleData="";
                $size = count($avatarList);
                $i = 0;
                $currIndex = $index;                
                $sloodle->response->add_data_line("INDEX:". $index);                  
                $sloodle->response->add_data_line("USERS:". $size );    
                $sloodle->response->add_data_line("SMODE:". $sortMode);   
                foreach ($avList as $av){                          
                   //print only the NUM_USERS_TO_RETURN number of users starting from the current index point                   
                   if (($i < $currIndex) || ($i > ($currIndex + $NUM_USERS_TO_RETURN-1))) {
                       $i++;                   
                       continue; //skip the ones which have already been sent                
                   }                 
                   else{
                       $sloodleData = "UUID:".$av->uuid."|";
                       $sloodleData .="AV:".  $av->avname . "|";
                       $sloodleData .="BAL:".$av->balance."|";
                       $sloodleData .= "DEBITS:".$av->debits;                   
                       $sloodle->response->add_data_line($sloodleData);   
                       $i++;
                       if ($i==$avListLen){                             
                           $sloodle->response->add_data_line("EOF");
                       }
                   }
                
                }//foreach  
            } else{//$avatarList is empty
                $sloodle->response->set_status_code(80002);             //no avatars
                $sloodle->response->set_status_descriptor('HQ');    //line 0    OK   
            } 
    
    } //getClassList
    
        /*
        * getAwardMbrs will return a list of users with 
        * avatars in the course along with a tag indicating if they are a member of the group
        * This function can be called in SL using the following linked message:
        * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:users,function:getAwardMbrs\nSENDERUUID:UUID\nGROUPNAME:"+clickedGroup"\nINDEX:index\nSORTMODE:name", NULL_KEY);
        */
        function getAwardGrpMbrs($data){
           $NUM_USERS_TO_RETURN=10; 
           global $sloodle;           
           //sloodleid is the id of the activity in moodle we want to connect with
           $sloodleid = $sloodle->request->optional_param('sloodleid');
           //cmid is the module id of the sloodle activity we are connecting to
           $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
           $cmid = $cm->id;
           $sCourseObj = new sloodleCourseObj($cmid);  
           $awardsObj = new Awards((int)$cmid);
           $data = $sloodle->request->required_param('data');            
           $bits = explode("|", $data);                                 
           //$data=GROUPNAME:groupname|INDEX:index|SORTMODE:name/balance
           $senderUuid= getFieldData($bits[0]);
           $groupName  = getFieldData($bits[1]);         
           $index      = getFieldData($bits[2]);
           $sortMode   = getFieldData($bits[3]);  
           $groupId = groups_get_group_by_name($sCourseObj->courseId,$groupName);
           if (!$groupId){
                $sloodle->response->set_status_code(-500401); //group doesnt exist in course 
                $sloodle->response->set_status_descriptor('GROUPS');    //line 0    OK                              
                $sloodle->response->add_data_line("GRPNAME:".$groupName);
                return;
           }
           /*  Send message back into SL
           *      LINE   MESAGE
           *      0)     1 | OK
           *      1)     SENDERUUID:uuid
           *      2)     GROUPNAME:name
           *      3)     TOTALUSERS:89
           *      4)     TOTALMBRS:12 
           *      3)     INDEX:0
           *      4)     UUID:uuid|AVNAME:avname|BALANCE:balance|MBR:yes
           *      5)     UUID:uuid|AVNAME:avname|BALANCE:balance|MBR:yes
           *      6)     UUID:uuid|AVNAME:avname|BALANCE:balance|MBR:no
           *      7)     ...
           *      8)     UUID:uuid|AVNAME:avname|BALANCE:balance|MBR:yes
           *      9)     EOF
           */                   
            $sloodle->response->set_status_code(1);             //line 0    1
            $sloodle->response->set_status_descriptor('OK');    //line 0    OK  
            $sloodle->response->add_data_line("SENDER:".$senderUuid);//line 1                     
            //---sloodledata:
           //  | avuuid 
           //  | moodle user name 
           //  | Avatar Name 
           //  | stipendAllocation 
           //  | user debits
            $userList = $this->getUserList();
            //getAvatarlist returns an array of stdObj  
            //$av->userid 
            //$av->username 
            //$av->avname 
            //$av->uuid             
            $avatarList = $this->getAvatarList($userList);
            //add balance fields from sloodle_award_trans database then sort
            $avList= array();             
            if ($avatarList){
                foreach ($avatarList as $u){
                       $av = new stdClass(); 
                       $av->userid = $u->userid;
                       $av->username = $u->username;                                      
                       $av->avname = $u->avname;
                       $av->uuid = $u->uuid;
                       $av_balanceDetails= $awardsObj->awards_getBalanceDetails($u->userid);
                       $av->balance = $av_balanceDetails->balance;                   
                       $av->credits = $av_balanceDetails->credits;
                       //get users groups                                              
                       $mbrStatus= groups_is_member($groupId,$u->userid);
                       if ($mbrStatus){
                        $av->memberStatus = "yes";
                       }else {
                         $av->memberStatus = "no";
                       }
                       $avList[]=$av;
                }//foreach
                //sort by points
                if ($sortMode=="balance") usort($avList, array("sloodle_hq_plugin_user", "balanceSort")); else
                if ($sortMode=="name") usort($avList,  array("sloodle_hq_plugin_user", "nameSort"));
                $sloodleData="";
                $totalUsers= count($avatarList);
                $totalMembers= count(groups_get_members($groupId));
                $i = 0;
                $currIndex = $index;                                
                $sloodle->response->add_data_line("USERS:". $totalUsers );   
                $sloodle->response->add_data_line("MBRS:". $totalMembers );   
                $sloodle->response->add_data_line("GNAME:". $groupName );   
                $sloodle->response->add_data_line("INDEX:". $index);   
                
                foreach ($avList as $av){                          
                   //print only the $NUM_USERS_TO_RETURN number of users starting from the current index point
                   
                   if (($i < $currIndex) || ($i > ($currIndex + $NUM_USERS_TO_RETURN-1))) {
                       $i++;                   
                       continue; //skip the ones which have already been sent                
                   }                 
                   else{
                       $sloodleData = "UUID:".$av->uuid."|";
                       $sloodleData .="AV:".  $av->avname . "|";
                       $sloodleData .="BAL:".$av->balance."|";
                       $sloodleData .= "MBR:".$av->memberStatus;                   
                       $sloodle->response->add_data_line($sloodleData);   
                       $i++;
                       if ($i==$avListLen){                             
                           $sloodle->response->add_data_line("EOF");
                       }
                   }
                
              }  //foreach
            }else{ //avatarList is empty
                $sloodle->response->set_status_code(80002);             //no avatars
                $sloodle->response->set_status_descriptor('HQ');    //line 0    OK   
            } 
    
    }//getAwardMbrs
     /**********************************************************
     * addGrpMbr will attempt to add a member to a group 
     * called by: 
     * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:user,function:addGrpMbr\nSENDERUUID:"+(string)owner+"|GROUPNAME:"+current_grp_membership_group+"|USERUUID:"+(string)useruuid|USERNAME:avname, NULL_KEY);
     * @output status_code: -500800 user doesn’t have capabilities to edit group membersip
     */
     function addGrpMbr($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        $cmid = $cm->id;
        $sCourseObj = new sloodleCourseObj($cmid);          
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $sender_uuid=getFieldData($bits[0]);
        $sender_name=$sloodle->request->required_param('sloodleavname');
        $avUser = new SloodleUser( $sloodle );
        $avUser->load_avatar($sender_uuid,$sender_name);
        $avUser->load_linked_user();
        $sender_moodle_id= $avUser->avatar_data->userid;
        $grpName =  getFieldData($bits[1]);
        $newMemberUuid = getFieldData($bits[2]);
        $newMemberName= getFieldData($bits[3]);
        $context = get_context_instance(CONTEXT_COURSE, $sCourseObj->courseId);
        //check to see if user has authority to edit group membership
        if (!has_capability('moodle/course:managegroups', $context,$sender_moodle_id)) {
           $sloodle->response->set_status_code(-500800);     //@output status_code: -500800 user doesn’t have capabilities to edit group membersip
           $sloodle->response->set_status_descriptor('GROUPS'); //line 0  
           $sloodle->response->add_data_line("GRP:".$grpName);
           $sloodle->response->add_data_line("MBRNAME:".$newMemberName);
           $sloodle->response->add_data_line("MBRUUID:".$newMemberUuid);
           return;
        }//has_capability('moodle/course:managegroups'
        //search for group to get id, then add to the sloodle_award_teams database
        $groupId = groups_get_group_by_name($sCourseObj->courseId,$grpName);
        if ($groupId){            
            $avUser = new SloodleUser( $sloodle );
            $avUser->load_avatar($newMemberUuid,$newMemberName);
            $avUser->load_linked_user();
            $newMemberMoodleId= $avUser->avatar_data->userid;
            if (groups_add_member($groupId,$newMemberMoodleId)){
                $sloodle->response->set_status_code(1);             //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0 
           $sloodle->response->add_data_line("GRP:".$grpName);
           $sloodle->response->add_data_line("MBRNAME:".$newMemberName);
           $sloodle->response->add_data_line("MBRUUID:".$newMemberUuid);
            }else{//could not add user to group
                $sloodle->response->set_status_code(-500900); //-500900 could not add user to group
                $sloodle->response->set_status_descriptor('GROUPS'); //line 0                 
            }
            return;
        }else {//groupid is null
            //@output status_code: -500400 group doesnt exist for this award 
            $sloodle->response->set_status_code(-500400);     
            $sloodle->response->set_status_descriptor('GROUPS'); //line 0      
           $sloodle->response->add_data_line("GRP:".$grpName);
           $sloodle->response->add_data_line("MBRNAME:".$newMemberName);
           $sloodle->response->add_data_line("MBRUUID:".$newMemberUuid);
            return;
        } 
            
     }
     /**********************************************************
     * removeGrpMbr will attempt to remove a member to a group 
     * called by: 
     * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:user,function:removeGrpMbr\nSENDERUUID:"+(string)owner+"|GROUPNAME:"
     * +current_grp_membership_group+"|USERUUID:"+(string)useruuid+"|USERNAME:"+userName, NULL_KEY);
     * 
     * @output status_code: -500800 user doesn’t have capabilities to edit group membersip
     * @output status_code: -500900 could not add user to group
     */
     function removeGrpMbr($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        $cmid = $cm->id;
        $sCourseObj = new sloodleCourseObj($cmid);          
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $sender_uuid=getFieldData($bits[0]);
        $sender_name=$sloodle->request->required_param('sloodleavname');
        $avUser = new SloodleUser( $sloodle );
        $avUser->load_avatar($sender_uuid,$sender_name);
        $avUser->load_linked_user();
        $sender_moodle_id= $avUser->avatar_data->userid;
        $grpName =  getFieldData($bits[1]);
        $newMemberUuid = getFieldData($bits[2]);
        $newMemberName= getFieldData($bits[3]);
        $context = get_context_instance(CONTEXT_COURSE, $sCourseObj->courseId);
        //check to see if user has authority to edit group membership
        if (!has_capability('moodle/course:managegroups', $context,$sender_moodle_id)) {
           $sloodle->response->set_status_code(-500800);     //@output status_code: -500800 user doesn’t have capabilities to edit group membersip
           $sloodle->response->set_status_descriptor('GROUPS'); //line 0  
           $sloodle->response->add_data_line($grpName);
           $sloodle->response->add_data_line($newMemberName);
           $sloodle->response->add_data_line($newMemberUuid);
           return;
        }//has_capability('moodle/course:managegroups'
        //search for group to get id, then add to the sloodle_award_teams database
        $groupId = groups_get_group_by_name($sCourseObj->courseId,$grpName);
        if ($groupId){            
            $avUser = new SloodleUser( $sloodle );
            $avUser->load_avatar($newMemberUuid,$newMemberName);
            $avUser->load_linked_user();
            $newMemberMoodleId= $avUser->avatar_data->userid;
            if (groups_remove_member($groupId,$newMemberMoodleId)){
                $sloodle->response->set_status_code(1);             //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $sloodle->response->add_data_line($grpName);
                $sloodle->response->add_data_line($newMemberName);
                $sloodle->response->add_data_line($newMemberUuid);                
            }else{//could not add user to group
                $sloodle->response->set_status_code(-500901); //-500901 could not remove user to group
                $sloodle->response->set_status_descriptor('GROUPS'); //line 0                 
            }
            return;
        }else {//groupid is null
            //@output status_code: -500400 group doesnt exist for this award 
            $sloodle->response->set_status_code(-500400);     
            $sloodle->response->set_status_descriptor('GROUPS'); //line 0      
            $sloodle->response->add_data_line($grpName);
            $sloodle->response->add_data_line($newMemberName);
            $sloodle->response->add_data_line($newMemberUuid);                
            return;
        } 
            
     }
}//class
?>
