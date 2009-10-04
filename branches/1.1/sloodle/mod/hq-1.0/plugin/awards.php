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
require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
/** SLOODLE awards object data structure */
require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');

class sloodle_hq_plugin_awards {

  
     /*
     *   makeTransaction($data) will insert data into the sloodle_awards_trans table
     */ 
     function makeTransaction($data){
         
         global $sloodle;
         //sloodleid is the id of the activity in moodle we want to connect with
         $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
         $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
         $cmid = $cm->id;
         //create sCourseObj, and awardsObj
         $sCourseObj = new sloodleCourseObj($cmid);  
         $awardsObj = new Awards((int)$cmid);
         //get the controller id
         $sloodlecontrollerid=$sloodle->request->optional_param('sloodlecontrollerid');    
         $command=$sloodle->request->optional_param('command');  
         //get the module id of the activity we are working with
         $sloodlemoduleid=(int)$sCourseObj->cm->instance;
         
         $data = $sloodle->request->optional_param('data'); 
         $bits = explode("|", $data);
         $sourceUuid        = getFieldData($bits[0]);
         $avUuid            = trim(getFieldData($bits[1]));
         $avName            = trim(getFieldData(trim($bits[2])));  
         $points            = getFieldData($bits[3]);  
         $details           = getFieldData($bits[4]);  
         //get moodleId for the avatar which was sent
        $avUser = new SloodleUser( $sloodle );
        $avUser->load_avatar($avUuid,$avName);
        $avUser->load_linked_user();
        $userid = $avUser->avatar_data->userid;
        //build transaction record 
        $trans = new stdClass();
        $trans->sloodleid       = $sloodlemoduleid;
        $trans->avuuid          = $avUuid;        
        $trans->userid          = $userid;
        $trans->avname          = $avName;           
        $trans->idata           = $details;
        $trans->timemodified=time();       
        if ($points<0) {            
            $trans->itype="debit";
            $points*=-1;;
        }
        else {
           $trans->itype="credit";
        }
        $trans->amount=$points; 
         //add details to this transaction into the mysql db
         $trans->idata = $details; 
        //insert transaction
        $awardsObj->awards_makeTransaction($trans,$sCourseObj);
        
        //retrieve new balance
        $rec = $awardsObj->awards_getBalanceDetails($userid);
        $balance = $rec->balance;
        $sloodle->response->set_status_code(1);             //line 0    1
        $sloodle->response->set_status_descriptor('OK'); 
        //line2: uuid who made the transaction        
        //add command
        $sloodle->response->add_data_line("SOURCE_UUID:".$sourceUuid);
        $sloodle->response->add_data_line("AVUUID:".$avUuid);
        $sloodle->response->add_data_line("AVNAME:".trim($avName));
        $sloodle->response->add_data_line("POINTS:".$balance);
        $sloodle->response->add_data_line("ACTION:".$sloodle->request->optional_param('action'));
        $sloodle->response->add_data_line("SECRETWORD:".$sloodle->request->optional_param('secretword'));        
        $awardsObj->synchronizeDisplays_sl($trans);
    }
    /*
     *  getAwardGrps($data) will return all groups in the course and indicate which ones are connected with this award
     *  outputs:
     *  1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
        RESPONSE:groups|getGrps
        INDEX:index
        GROUPS:Group A,MEMBERS:11,Connected:yes|Group B,MEMBERS:10,Connected:no|Group C,MEMBERS:10,Connected:no|Group D,MEMBERS:12,Connected:no
        numGroups:7
     * 
     * TRIGGER:  You can trigger this function by adding the sloodle_api.lsl script to your prim, then executing the following function:
     * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "plugin:awards,function:getAwardsGrps\nindex:0|groupsPerPage:9", NULL_KEY);
     * 
     * OUTPUT HANDLER
     * In second life, you can add the following code to handle the output generated by this function:
     * 
     * 
     */
     
     function getAwardGrps($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        $cmid = $cm->id;
        $sCourseObj = new sloodleCourseObj($cmid);          
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $index = getFieldData($bits[0]);
        $groupsPerPage = getFieldData($bits[1]);
        //get all groups in the course
        $groups = groups_get_all_groups($sCourseObj->courseId);
        $sloodle->response->set_status_code(1);             //line 0 
        $sloodle->response->set_status_descriptor('OK'); //line 0 
        $dataLine="";
        $counter = 0;
        //get all groups in sloodle_awards_teams for this sloodleid
        $awardGroups = get_records('sloodle_awards_teams','sloodleid',$sCourseObj->sloodleId);        
        //get_records_select('sloodle_award_trans','itype=\'credit\' AND sloodleid='.$this->sloodleId.' AND userid='.$userid);        
        foreach($groups as $g){
             if (($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage))){
                if ($counter!=0) $dataLine.="|";
                $dataLine .= "GRP:".$g->name;
                $groupMembers =groups_get_members($g->id);
                $numMembers = count($groupMembers);                
                $dataLine .= ",MBRS:".$numMembers;                
                if ($awardGroups){
                    //search to see if group is in the awards group
                    $found = get_records_select('sloodle_awards_teams','sloodleid='.$sCourseObj->sloodleId.' AND groupid='.$g->id);
                    if ($found) { 
                        $dataLine .= ",Connected:yes";                    
                    }else {
                        $dataLine .= ",Connected:no";                    
                    }
                }else { // no groups connected to this awards activity
                 $dataLine .= ",Connected:no";
                } //else
             $counter++;
           }//(($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage)))
        }//foreach
        $sloodle->response->add_data_line("INDEX:". $index);   
        $sloodle->response->add_data_line($dataLine);//line 
        $sloodle->response->add_data_line("numGroups:".$counter);//line 
     }//function
     
     /**********************************************************
     * addAwardGrp will attempt to add a group to the sloodle_awards_teams
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * @output status_code: -500100 tried to insert Sloodle_awards_teams  but got an error  
     * @output status_code: -500200 tried to add an award group, but group name passed into the function does not exist in moodle.
     * @output status_code: -500300 group already exists for this award
     * @output status_code: 1 
     * @output GROUPNAME:name
     */
     function addAwardGrp($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        $cmid = $cm->id;
        $sCourseObj = new sloodleCourseObj($cmid);          
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $grpName =  getFieldData($bits[0]);
        //search for group to get id, then add to the sloodle_award_teams database
        $groupId = groups_get_group_by_name($sCourseObj->courseId,$grpName);
        if ($groupId){
            //first check to see if the group has already been added        
            $groups = get_records_select('sloodle_awards_teams','sloodleid='.$sCourseObj->sloodleId .' AND groupid='.$groupId);
            if ($groups){
                //-500300 group already exists for this award
                $sloodle->response->set_status_code(-500300);             
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                return;
            } //group has not been added yet to sloodle_awards_teams
            $awdGrp= new stdClass();
            $awdGrp->sloodleid=$sCourseObj->sloodleId;
            $awdGrp->groupid=$groupId;
            if (insert_record('sloodle_awards_teams',$awdGrp)){
                $sloodle->response->set_status_code(1);             //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                $sloodle->response->add_data_line($dataLine);
                return;
            }else { //insert failed
                //-500100 tried to insert Sloodle_awards_teams  but got an error
                $sloodle->response->set_status_code(-500100);     
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                $sloodle->response->add_data_line($dataLine);
                return;
            } //else
            
        }else { //grpName was not found in course
                //-500200 tried to add an award group, but group name passed into the function does not exist in moodle.
                $sloodle->response->set_status_code(-500200);             //line 0 
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                $sloodle->response->add_data_line($dataLine);
                return;
            } //else
     } //function addAwardGrp($data)
     
     /**********************************************************
     * removeAwardGrp will attempt to remove a group to the sloodle_awards_teams
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * @output status_code: -500100 tried to insert Sloodle_awards_teams  but got an error  
     * @output status_code: -500200 group name does not exist in this moodle course.
     * @output status_code: -500300 group already exists for this award
     * @output status_code: -500400 group doesnt exist for this award 
     * @output status_code: -500500 could not delete the group from the sloodle_awards_teams table
     * @output status_code: -500600 group does not exist in the sloodle_awards_teams table
     * @output status_code: 1
     * @output GROUPNAME:name
     */
     function removeAwardGrp($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //cmid is the module id of the sloodle activity we are connecting to
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        $cmid = $cm->id;
        //create courseObject
        $sCourseObj = new sloodleCourseObj($cmid);          
        //get data parameter (sent from SL)
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        //get group name to remove
        $grpName =  getFieldData($bits[0]);
        //search for group to get id, then add to the sloodle_award_teams database
        $groupId = groups_get_group_by_name($sCourseObj->courseId,$grpName);
        if ($groupId){
            //first check to see if the group has already been added        
            $groups = get_record_select('sloodle_awards_teams','sloodleid='.$sCourseObj->sloodleId.' AND groupid='.$groupId);
            if (!$groups){
                //-500400 group doesnt exist for this award
                $sloodle->response->set_status_code(-500400);             
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                return;
            }//group exists
            if (!delete_records('sloodle_awards_teams','sloodleid',$sCourseObj->sloodleId,'groupid',$groups->groupid)){
                //delete failed
                //-500500 could not delete the group from the sloodle_awards_teams table
                $sloodle->response->set_status_code(-500500);             
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                return;
            }else{ //delete suceeded                
                $sloodle->response->set_status_code(1);             //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                $sloodle->response->add_data_line($dataLine);
                return;
            } //else
        }else { //-500200 group name does not exist in this moodle course.                
                $sloodle->response->set_status_code(-500200);             //line 0 
                $sloodle->response->set_status_descriptor('HQ'); //line 0 
                $dataLine="GROUPNAME:".$grpName;
                $sloodle->response->add_data_line($dataLine);
                return;
        }  //else
     } // function removeAwardGrp
     
     /**********************************************************
     * getTeamScores will return a total for the group of all users scores 
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * @output: 1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     * @output: RESPONSE:awards|getTeamScores
     * @output: GRP:name,BALANCE:100|GRP:name,BALANCE:200
     * @output: INDEX:0
     * @output: NUMGROUPS:10
     * 
     */
     function getTeamScores($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->optional_param('sloodleid');
        //get course module from the course_modules table for this sloodle activity
        $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
        //extract cmid from cm -
        //cmid is the course module id of the sloodle activity in the course_modules table 
        $cmid = $cm->id;
        //create courseObject
        $sCourseObj = new sloodleCourseObj($cmid);  
        //create awards object
        $awardsObj = new Awards((int)$cmid);
        //extract data from sl
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $index = getFieldData($bits[0]);
        $groupsPerPage = getFieldData($bits[1]);
        $dataLine="";
        $counter = 0;
        //get all groups in sloodle_awards_teams for this sloodleid
        $awardGroups = get_records('sloodle_awards_teams','sloodleid',$sCourseObj->sloodleId);                
        if ($awardGroups){
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
            foreach($awardGroups as $awdGrp){
                 if (($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage))){
                    if ($counter!=0) $dataLine.="|";
                    $groupName = groups_get_group_name($awdGrp->groupid);
                    $dataLine .= "GRP:".$groupName; 
                    $groupMembers =groups_get_members($awdGrp->groupid);
                    $total=0;
                    foreach ($groupMembers as $gMbr){
                         $balanceDetails = $awardsObj->awards_getBalanceDetails($gMbr->id);
                         if ($balanceDetails)
                            $total+=$balanceDetails->balance;                    
                    }  //foreach
                    $dataLine .= ",BALANCE:".$total;
                 $counter++;
               }//(($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage)))
            } //foreach
        } else{ //no groups exist for this award in sloodle_awards_teams
            $sloodle->response->set_status_code(-500700);//no awards groups
            $sloodle->response->set_status_descriptor('HQ'); //line 0 
        }//else
        $sloodle->response->add_data_line("INDEX:". $index);   
        $sloodle->response->add_data_line($dataLine);//line 
        $sloodle->response->add_data_line("numGroups:".$counter);//line         
     } //function getTeamScores($data)
     
     /**********************************************************
     * getAwards will return all the sloodle_awards in this course
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * 
     * Call from SL to sloodle_api.lsl: 
     * 
     * string cmdCall = "plugin:awards,function:getAwards\nsloodleid:null\nINDEX:";
     *        cmdCall+=(string)0+"|MAXITEMS:9"
     * llMessageLinked(LINK_SET, PLUGIN_CHANNEL, cmdCall, NULL_KEY);

     * @output: 1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     * @output -501100|AWARDS||||| no Sloodle_awards for this course
     *      
     * @output: RESPONSE:awards|getAwards
     * @output: INDEX:0
     * @output: NUMAWARDS:12
     * @output: AWARDID:sloodleid|NAME:name
     * @output: AWARDID:sloodleid|NAME:name          
     * ...
     * @output: AWARDID:sloodleid|NAME:name      
     * 
     */
     
      function getAwards($data){
        global $sloodle;
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $index = getFieldData($bits[0]);
        $awardsPerPage=getFieldData($bits[1]);
        $dataLine="";
        $counter = 0;
        //$cmid=$sloodle->request->required_param('sloodlemoduleid'); 
        //$sCourseObj = new sloodleCourseObj($cmid);  
        $courseId = $sloodle->course->get_course_id();
        $awards = get_records_select('sloodle','course='.$courseId.' AND type=\'Awards\'');
        if ($awards){
            $sloodle->response->set_status_code(1);          //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0
            $sloodle->response->add_data_line("INDEX:".$index); 
            $sloodle->response->add_data_line("#AWDS:".count($awards)); 
            foreach($awards as $awd){                
                 if (($counter>=($index*$awardsPerPage))&&($counter<($index*$awardsPerPage+$awardsPerPage))){        
                    $sloodle->response->add_data_line("ID:".$awd->id."|NAME:".$awd->name);
                 }//endif 
            }//foreach
      }else { //if ($awars) - no awards
          $sloodle->response->set_status_code(-501100);    //no Sloodle_awards for this course
          $sloodle->response->set_status_descriptor('OK'); //line 0
      }
    } //getAwards($data)
     /**********************************************************
     * registerScoreboard will attempt to add an entry to the sloodle_awards_scoreboards
     * Available HTTP vars
     * sloodlecontrollerid
     * sloodleid
     * sloodlepwd
     * sloodleserveraccesslevel=0&
     * sloodleuuid
     * sloodleavname=Fire%20Centaur&
     * plugin=awards
     * function=registerScoreboard
     * data=urlhttp://sim4644.agni.lindenlab.com:12046/cap/e9ffef0c-f1e9-acf0-0f9d-ffc8370290d3|
     * data=DISPLAYTYPE:Scoreboard
     */
     function registerScoreboard($data){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->required_param('sloodleid');
        //get data
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $url = $bits[0];     
        $type =getFieldData($bits[1]);   
        //add scoreboard to sloodle_awards_scoreboard table
        if (count($bits)<2){
             $sloodle->response->set_status_code(-92113);    //missing parameters
             $sloodle->response->set_status_descriptor('HQ'); //line 0
             $sloodle->response->add_data_line($url); //line 1
        }//endif
        else{
            //create new scoreboard
            $sb= new stdClass();
            $sb->sloodleid = $sloodleid;
            $sb->url=$url;
            $sb->type=$type;
            //checl if already registered
            $alreadyRegistered = get_records('sloodle_awards_scoreboards','url',$url);
            if (!$alreadyRegistered){
                if (!insert_record('sloodle_awards_scoreboards',$sb)){
                     $sloodle->response->set_status_code(-92114);    //cant insert record in sloodle_awards_scoreboards
                     $sloodle->response->set_status_descriptor('HQ'); //line 0 
                     $sloodle->response->add_data_line($url); //line 1
                }//endif
                else {
                    $sloodle->response->set_status_code(1);    
                    $sloodle->response->set_status_descriptor('OK'); //line 0 
                    $sloodle->response->add_data_line($url); //line 1
                }
            }//alreadyRegistered
            else{
                $sloodle->response->set_status_code(1);    
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $sloodle->response->add_data_line($url); //line 1
            }//end else

        }//end else
        
     } //function registerScoreboard($data)
     /**********************************************************
     * deregisterScoreboard will attempt to remove an entry from the sloodle_awards_scoreboards
     * Available HTTP vars
     * sloodlecontrollerid
     * sloodleid
     * sloodlepwd
     * sloodleserveraccesslevel=0&
     * sloodleuuid
     * sloodleavname=Fire%20Centaur&
     * plugin=awards
     * function=registerScoreboard
     * data=urlhttp://sim4644.agni.lindenlab.com:12046/cap/e9ffef0c-f1e9-acf0-0f9d-ffc8370290d3|
     * data=DISPLAYTYPE:Scoreboard
     */
     function deregisterScoreboard($data){
        global $sloodle;
        //get data
        $url=$sloodle->request->optional_param('data'); 
        //add scoreboard to sloodle_awards_scoreboard table
        if ($data==""){
             $sloodle->response->set_status_code(-92113);    //missing parameters
             $sloodle->response->set_status_descriptor('HQ'); //line 0
             $sloodle->response->add_data_line($url); //line 1
        }//endif
        else{
            //remove scoreboard            
            if (!delete_records('sloodle_awards_scoreboards','url',$url)){
                 $sloodle->response->set_status_code(-92115);    //cant delete record in sloodle_awards_scoreboards
                 $sloodle->response->set_status_descriptor('HQ'); //line 0 
                 $sloodle->response->add_data_line($url); //line 1
            }//endif
            else{
                $sloodle->response->set_status_code(1);    //deleted record
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $sloodle->response->add_data_line($url); //line 1
            }//end else
        }//end else
        
     } //function deregisterScoreboard($data)
}//class
?>
