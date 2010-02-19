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
*/


require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
/** SLOODLE awards object data structure */
require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');

class SloodleApiPluginAwards extends SloodleApiPluginBase{

  function balanceSort($a, $b){
        if ($a->balance == $b->balance) {
            return 0;
        }
        return ($a->balance > $b->balance) ? -1 : 1;
    }
    function nameSort($a, $b){
        if ($a->name == $b->name) {
            return 0;
        }
        return ($a->name < $b->name) ? -1 : 1;
    }
     function markSort($a, $b){
        if ($a->mark == $b->mark) {
            return 0;
        }
        return ($a->mark > $b->mark) ? -1 : 1;
    }
     /*
     *   makeTransaction() will insert data into the sloodle_awards_trans table
     */ 
     function makeTransaction(){         
         global $sloodle;
         //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodle activity as its instance.
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         //if the sloodlemoduleid is not specified, get the course module from the sloodle instance
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             if ($sloodleid) {
              $cm = get_coursemodule_from_instance('sloodle',$sloodleid);                 
              $cmid = $cm->id;                                            
             }
             else {
                 //&sloodlemoduleid or &sloodleid must be defined and included in the url 
                 //request so we can connect to an awards activity to complete this transaction
                 $sloodle->response->set_status_code(-500900); 
                 $sloodle->response->set_status_descriptor('HQ'); 
             }
         }
         else $cmid= $coursemoduleid;
         //create sCourseObj, and awardsObj
         $sCourseObj = new sloodleCourseObj($cmid);  
         $awardsObj = new Awards((int)$cmid);
         //get the controller id
         $sloodlecontrollerid=$sloodle->request->required_param('sloodlecontrollerid');    
         //get the course module id of the activity we are working with
         $sloodleid=(int)$sCourseObj->cm->instance;

         $sourceUuid        = $sloodle->request->required_param('sourceuuid'); 
         $avUuid            = $sloodle->request->required_param('avuuid'); 
         $avName            = $sloodle->request->required_param('avname'); 
         $points            = $sloodle->request->required_param('points'); 
         $details           = $sloodle->request->optional_param('details'); 
         //get moodleId for the avatar which was sent
         $avUser = new SloodleUser( $sloodle );
         $avUser->load_avatar($avUuid,$avName);
         $avUser->load_linked_user();
         $userid = $avUser->avatar_data->userid;
        //build transaction record 
        $trans = new stdClass();
        $trans->sloodleid       = $sloodleid;
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
        //TODO: change to xml output?
        $sloodle->response->add_data_line("SOURCE_UUID:".$sourceUuid);
        $sloodle->response->add_data_line("AVUUID:".$avUuid);
        $sloodle->response->add_data_line("AVNAME:".trim($avName));
        $sloodle->response->add_data_line("POINTS:".$balance);
        $sloodle->response->add_data_line("ACTION:".$sloodle->request->optional_param('action'));
        $sloodle->response->add_data_line("SECRETWORD:".$sloodle->request->optional_param('secretword'));        
        $awardsObj->synchronizeDisplays_sl($trans);
    }
        /*
     * modifyCash() API command - inserts into the sloodle_awards_trans a record with the sloodleid of -777  this signifies a new type of currency called GameCurrency which can be used as a form of non-monitory or monitory cash system for sloodle
     * This will enable us to insert moodle-site-wide curency into the awards table.
     */ 
     
     function modifyCashBalance(){         
         global $sloodle;         
         //get avatar details of who this award is awarded to
            $avUuid            = $sloodle->request->required_param('avuuid'); 
            $avName            = $sloodle->request->required_param('avname'); 
            $points            = $sloodle->request->required_param('points');          
            $details           = $sloodle->request->optional_param('details');             
            
         //get moodleId for the avatar which was sent
             $avUser = new SloodleUser( $sloodle );
             $avUser->load_avatar($avUuid,$avName);
             $avUser->load_linked_user();                          
             $userid = $avUser->avatar_data->userid;
             
        if (empty($userid)){
           $sloodle->response->set_status_code(-331);             //line 0 - User did not have permission to access the resources requested
           $sloodle->response->set_status_descriptor('USER_AUTH'); //line 0  
           return; 
        }     
        //build transaction record 
        $trans = new stdClass();        
        $trans->sloodleid       = -777;// negative 777 indicates that this is a cash deposit
        $trans->avuuid          = $avUuid;        
        $trans->userid          = $userid;
        $trans->avname          = $avName;           
        $trans->idata           = $details;
        $trans->timemodified=time();       

        if ($points<0) {            
            $trans->itype="cashDebit";
            $points*=-1;;
        }
        else {
           $trans->itype="cashCredit";
        }
        $trans->amount=$points; 
         //add details to this transaction into the mysql db
        $trans->idata = $details; 
        //insert transaction
        if (!insert_record('sloodle_award_trans',$trans)) {
            $sloodle->response->set_status_code(-500100);             //line 0 tried to insert Sloodle_awards_teams but got an error
            $sloodle->response->set_status_descriptor('HQ'); //line 0 
            return;            
        }
        //retrieve new balance
        $cashCredits=0;
        $cashDebits=0;
        $balance = get_records('sloodle_award_trans','userid',$userid);
        foreach ($balance as $b){
            if ($b->itype=="cashCredit"){
                $cashCredits+=$b->amount;
            }else if ($b->itype=="cashDebit"){
                $cashDredits+=$b->amount;                
            }
        }        
        $balance = $cashCredits-$cashDebits;
        $sloodle->response->set_status_code(1);             //line 0    1
        $sloodle->response->set_status_descriptor('OK'); 
        //line2: uuid who made the transaction        
        //add command
        //TODO: change to xml output?        
        $sloodle->response->add_data_line("AVUUID:".$avUuid);
        $sloodle->response->add_data_line("AVNAME:".trim($avName));
        $sloodle->response->add_data_line("NEW CASH BALANCE:".$balance);
    }
     function getMarks(){      
      global $sloodle;   
        global $CFG;  
         //request any variables that were sent from Second Life               
         $index =  $sloodle->request->required_param('index');         
         $assignmentId =   $sloodle->request->required_param('assignmentid');
         $maxItems= $sloodle->request->required_param('maxitems');         
         $sortMode= $sloodle->request->required_param('sortmode');         
         $displayMode= $sloodle->request->required_param('displaymode');         
         
         if (!function_exists('grade_get_grades')) { //workaround for buggy PHP versions
            require_once($CFG->libdir.'/gradelib.php');
         }
         //get the cmid
         $coursemoduleid= $sloodle->course->controller->cm->id;    
         //construct sCourseObj so we can access the class list
         $sCourseObj = new sloodleCourseObj($coursemoduleid);  
         //get the assignment info for this assignment                
         $assignmentInfo=grade_get_grades($sloodle->course->get_course_id(), 'mod', "assignment", $assignmentId); 
        //get everyone in this class
         $classList = $sCourseObj->getUserList();
         //abstract userIds
         $classIds = array();
         foreach($classList as $cuser){
             $classIds[]=$cuser->id;             
         }
         //get grades for all the user ides
            $gradeInfo=grade_get_grades($sloodle->course->get_course_id(), 'mod', "assignment", $assignmentId,$classIds);       
             $items=    $gradeInfo->items;
             $sloodle->response->set_status_code(1);             //line 0 
             $sloodle->response->set_status_descriptor('OK'); //line 0 
             $sloodle->response->add_data_line("INDEX:". $index);   
             $sloodle->response->add_data_line("numItems:".sizeof($items[0]->grades));//line  
             $counter =0;
             //build list
             $userGrades = array();
             
             foreach ($items[0]->grades as $userid =>$gradeItem){
                     $avUser = new SloodleUser( $sloodle );
                     $avUser->load_user($userid);
                     $avUser->load_linked_avatar();
                     $usr = new stdClass();
                     if ($avUser->get_avatar_name()){
                         $userMark = $gradeItem->grade;
                         if ($displayMode=="all"){
                            if (empty($userMark)) $userMark =0;                         
                            $usr->name = $avUser->get_avatar_name();
                            $usr->mark =$userMark;
                            $userGrades[]=$usr;
                         }//displaymode
                         else
                         if ($displayMode=="submitted"){
                             if (!empty($userMark)) {
                                $usr->name = $avUser->get_avatar_name();
                                $usr->mark =$userMark;
                                $userGrades[]=$usr; 
                             }//of
                         }//submitted
                     }//get_avatar_name
             }//foreach
             if ($sortMode=='marks') usort($userGrades,array("SloodleApiPluginAwards",  "markSort"));
             else if ($sortMode=='name') usort($userGrades,array("SloodleApiPluginAwards",  "nameSort"));
             foreach ($userGrades as $ug){
                 $dataLine="";
                 if (($counter>=($index*$maxItems))&&($counter<($index*$maxItems+$maxItems))){                    
                        $dataLine = "avname:".$ug->name;                        
                        $dataLine .= "|grade:".$ug->mark;   
                        $sloodle->response->add_data_line($dataLine);                                             
                 }      
                   $counter++;  
                }//foreach  
     }
     function getAssignments(){      
      global $sloodle;   
        global $CFG;  
         $courseId = $sloodle->course->get_course_id(); 
         $maxItems= $sloodle->request->required_param('maxitems');                     
         $index =  $sloodle->request->required_param('index'); 
                 
         $counter = 0;
        //get all groups in sloodle_awards_teams for this sloodleid
         $assignments= get_records('assignment','course',$sloodle->course->get_course_id());       
        if (!empty($assignments)){
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0         
            $sloodle->response->add_data_line("INDEX:". $index);   
            $sloodle->response->add_data_line("TOTALITEMS:". sizeof($assignments));   
            foreach($assignments as $a){
                $dataLine="";
             if (($counter>=($index*$maxItems))&&($counter<($index*$maxItems+$maxItems))){
                $dataLine = "id:".$a->id;   
                $dataLine .= "|name:".$a->name;
                $dataLine .= "|desc:".$a->description;
                 $sloodle->response->add_data_line($dataLine);
                 $counter++;
               }//(($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage)))
            }//foreach
        
        }
     }
     /*
     *  getAwardGrps() will return all groups in the course and indicate which ones are connected with this award
     *  outputs:
     *  1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
        RESPONSE:groups|getGrps
        INDEX:index
        GROUPS:Group A,MEMBERS:11,Connected:yes|Group B,MEMBERS:10,Connected:no|Group C,MEMBERS:10,Connected:no|Group D,MEMBERS:12,Connected:no
        numGroups:7
     * 
     * TRIGGER:  You can trigger this function by adding the sloodle_api.lsl script to your prim, then executing the following function:
        llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->getAwardGrps"+authenticatedUser+"&sloodleid="+(string)currentAwardId+"&index="+(string)index+"&maxitems=10", NULL_KEY);
     * 
     * OUTPUT HANDLER
     * In second life, you can add the following code to handle the output generated by this function:
     * 
     * 
     */
     function getAwardGrps(){
        global $sloodle;
        //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
        $sCourseObj = new sloodleCourseObj($cmid);                  
        $index =  $sloodle->request->required_param('index'); 
        $maxItems= $sloodle->request->required_param('maxitems'); 
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
             if (($counter>=($index*$maxItems))&&($counter<($index*$maxItems+$maxItems))){
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
        $sloodle->response->add_data_line("numGroups:".$counter);//line 
        $sloodle->response->add_data_line($dataLine);//line         
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
     function addAwardGrp(){
        global $sloodle;
        //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
        $sCourseObj = new sloodleCourseObj($cmid);          
        $grpName =  $sloodle->request->required_param('grpname');  
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
     function removeAwardGrp(){
        global $sloodle;
        //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
         $sCourseObj = new sloodleCourseObj($cmid);          
       
        //get group name to remove
        $grpName =  $sloodle->request->required_param('grpname');   
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
     function getTeamScores(){
        global $sloodle;     
        $teamScores = array();          
        
         //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
        //create courseObject
        $sCourseObj = new sloodleCourseObj($cmid);  
        //create awards object
        $awardsObj = new Awards((int)$cmid);
        $index =  $sloodle->request->required_param('index');    
        $maxitems= $sloodle->request->required_param('maxitems');  
        $sortMode =$sloodle->request->required_param('sortmode');            
        $dataLine="";
        $counter = 0;
        //get all groups in sloodle_awards_teams for this sloodleid
        $awardGroups = get_records('sloodle_awards_teams','sloodleid',$sCourseObj->sloodleId);                
        if ($awardGroups){
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
            foreach($awardGroups as $awdGrp){
                
                    $teamData = new stdClass();
                 if (($counter>=($index*$maxitems))&&($counter<($index*$maxitems+$maxitems))){                    
                    $groupName = groups_get_group_name($awdGrp->groupid);                   
                    $groupMembers =groups_get_members($awdGrp->groupid);
                    $total=0;
                    foreach ($groupMembers as $gMbr){
                         $balanceDetails = $awardsObj->awards_getBalanceDetails($gMbr->id);
                         if ($balanceDetails)
                            $total+=$balanceDetails->balance;                    
                    }  //foreach
                    $teamData->name=$groupName;
                    $teamData->balance=$total;
                    $teamScores[]=$teamData;
                 $counter++;
               }//(($counter>=($index*$groupsPerPage))&&($counter<($index*$groupsPerPage+$groupsPerPage)))
            } //foreach
        } else{ //no groups exist for this award in sloodle_awards_teams
            $sloodle->response->set_status_code(-500700);//no awards groups exist for this sloodle module id
            $sloodle->response->set_status_descriptor('HQ'); //line 0 
        }//else
        if ($sortMode=="balance") usort($teamScores,array("SloodleApiPluginAwards",  "balanceSort")); else
        if ($sortMode=="name") usort($teamScores, array("SloodleApiPluginAwards",  "nameSort"));  
        foreach($teamScores as $ts){
            $dataLine .= "GRP:".$ts->name; 
            $dataLine .= ",BALANCE:".$ts->balance;
            $dataLine.="|";
        }
        $dataLine = substr($dataLine,0,strlen($dataLine)-1);
        $sloodle->response->add_data_line("INDEX:". $index);   
        $sloodle->response->add_data_line("numGroups:".$counter);//line 
        $sloodle->response->add_data_line($dataLine);//line 
                
     } //function getTeamScore()
     /**********************************************************
     * getTeamScores will return a total for the group specified
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * @output: 1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     * @output: RESPONSE:awards|getTeamScores
     * @output: GRP:name
     * @output: BALANCE:100
     * 
     */
     function getTeamScore(){
        global $sloodle;
        //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
        //create courseObject
        $sCourseObj = new sloodleCourseObj($cmid);  
        //create awards object
        $awardsObj = new Awards((int)$cmid);
        //extract data from sl
        $groupName = $sloodle->request->required_param('grpname'); 
        $dataLine="";
        $counter = 0;
        //get all groups in sloodle_awards_teams for this sloodleid
        $groupId = groups_get_group_by_name($sCourseObj->courseId,$groupName);
        if ($groupId){
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
            $sloodle->response->add_data_line("GROUPNAME:".$groupName);//line             
            $groupMembers =groups_get_members($groupId);
            $total=0;
            foreach ($groupMembers as $gMbr){
                 $balanceDetails = $awardsObj->awards_getBalanceDetails($gMbr->id);
                 if ($balanceDetails)
                    $total+=$balanceDetails->balance;                    
            }  //foreach
            $sloodle->response->add_data_line("BALANCE:".$total);//line 
        } //end if $groupId
        else{ //no groups exist for this award in sloodle_awards_teams
            $sloodle->response->set_status_code(-500700);//no awards groups
            $sloodle->response->set_status_descriptor('HQ'); //line 0 
        }//else
     } //function getTeamScore($data)
     /**********************************************************
     * getAwards will return all the sloodle_awards in this course
     * 
     * @param string|mixed $data - should be in the format: GROUPNAME:grpname
     * 
     * Call from SL to sloodle_api.lsl: 
     * 
     * string cmdCall = "awards->getAwards&index=0&maxitems=9"
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
     
      function getAwards(){
        global $sloodle;        
        $index = $sloodle->request->required_param('index'); 
        $maxItems=$sloodle->request->required_param('maxitems');    
        $dataLine="";
        $counter = 0;
        $courseId = $sloodle->course->get_course_id();
        $awards = get_records_select('sloodle','course='.$courseId.' AND type=\'Awards\'');
        if ($awards){
            $sloodle->response->set_status_code(1);          //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0
            $sloodle->response->add_data_line("INDEX:".$index); 
            $sloodle->response->add_data_line("#AWDS:".count($awards)); 
            foreach($awards as $awd){                
                 if (($counter>=($index*$maxItems))&&($counter<($index*$maxItems+$maxItems))){        
                    $sloodle->response->add_data_line("ID:".$awd->id."|NAME:".$awd->name);
                 }//endif 
            }//foreach
      }else { //if ($awars) - no awards
          $sloodle->response->set_status_code(-501100);    //no Sloodle_awards for this course
          $sloodle->response->set_status_descriptor('OK'); //line 0
      }
    } //getAwards()
     /**********************************************************
     * registerScoreboard will attempt to add an entry to the sloodle_awards_scoreboards     
     */
     function registerScoreboard(){
        global $sloodle;
        //sloodleid is the id of the activity in moodle we want to connect with
        $sloodleid = $sloodle->request->required_param('sloodleid');
        //get data
        
        $url = $sloodle->request->required_param('url');
        $type =$sloodle->request->required_param('type');
        $name=$sloodle->request->required_param('name');
        //add scoreboard to sloodle_awards_scoreboard table
       
            //create new scoreboard
            $sb= new stdClass();
            $sb->sloodleid = $sloodleid;
            $sb->url=$url;
            $sb->type=$type;
            $sb->name=$name;
            //checl if already registered
            $alreadyRegistered = get_records_select('sloodle_awards_scoreboards',"url=\"".$url."\" AND name=\"".$name."\"");
            if (!$alreadyRegistered){
                if (!insert_record('sloodle_awards_scoreboards',$sb)){
                     $sloodle->response->set_status_code(-501200);    //cant insert record in sloodle_awards_scoreboards
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
                //delete all instances of the old urls for this scoreboard 
                if (!delete_records('sloodle_awards_scoreboards','url',$url,'name',$name)){
                    //insert new scoreboard url
                    insert_record('sloodle_awards_scoreboards',$sb);
                    $sloodle->response->set_status_code(1);    
                    $sloodle->response->set_status_descriptor('OK'); //line 0 
                    $sloodle->response->add_data_line($url); //line 1
                }
            }//end else

     } //function registerScoreboard()
     /**********************************************************
     * deregisterScoreboard will attempt to remove an entry from the sloodle_awards_scoreboards     
     */
     function deregisterScoreboard(){
        global $sloodle;
        //get data
        $url=$sloodle->request->required_param('url'); 
        //add scoreboard to sloodle_awards_scoreboard table
        $name=$sloodle->request->required_param('name'); 
            //remove scoreboard            
            if (!delete_records('sloodle_awards_scoreboards','url',$url,'name',$name)){
                 $sloodle->response->set_status_code(-501300);    //cant delete record in sloodle_awards_scoreboards
                 $sloodle->response->set_status_descriptor('HQ'); //line 0 
                 $sloodle->response->add_data_line($url); //line 1
            }//endif
            else{
                $sloodle->response->set_status_code(1);    //deleted record
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $sloodle->response->add_data_line($url); //line 1
            }//end else
        
        
     } //function deregisterScoreboard()
     
     
     /**********************************************************
     * findTransaction will search the sloodle_awards_trans table for any transaction matching
     * who's avuuid and idata field match the query sent.
     * 
     * Example:  Let's say you want to track whether a student has already touched a plant leaf in SL
     * Using findTransaction, you could search through all the transactions in the sloodle_trans table for an 
     * avatar with avuuid: 2102f5ab-6854-4ec3-aec5-6cd6233c31c6 and idata: "user touched flower"
     * 
     * If a transaction matching that query is found, the following information would be returned:
     * 
     *      1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     *      RESPONSE:awards|findTransaction
     *      AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     *      QUERY:user touched a flower
     *      ID:563|ITYPE:credit|AMT:1000 
     * 
     * If not found, the following info would be returned:
     * 
     *      -500800|HQ|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     *      RESPONSE:awards|findTransaction
     *      AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6
     *      QUERY:user touched a flower
     * 
     *      
     */
           /**
     * @method findTransaction
     * @author Paul Preibisch
     * 
     * getLastTransaction will retrieve the last transaction made for this user
     *  
     * @package sloodle
     */

     function findTransaction(){
        global $sloodle;                  
         //sloodleid is the id of the record in mdl_sloodle of this sloodle activity
         $sloodleid = $sloodle->request->optional_param('sloodleid');
         //coursemoduleid is the id of course module in sdl_course_modules which refrences a sloodleid as a field in its row called ""instance.""
         //when a notecard is generated from a sloodle awards activity, the course module id is given instead of the id in the sloodle table
         //There may be some instances, where the course module is sent instead of the instance. We account for that here.
         $coursemoduleid= $sloodle->request->optional_param('sloodlemoduleid');    
         if (!$coursemoduleid){
            //cmid is the module id of the sloodle activity we are connecting to
             $cm = get_coursemodule_from_instance('sloodle',$sloodleid);
             $cmid = $cm->id;
         }
         else $cmid= $coursemoduleid;
 
        //create courseObject
        $sCourseObj = new sloodleCourseObj($cmid);  
        //create awards object
        $awardsObj = new Awards((int)$cmid);
        //extract data from sl
        
        $avuuid = $sloodle->request->required_param('avuuid'); 
        $searchString= $sloodle->request->required_param('details'); 
        $dataLine="";
        $counter = 0;
        $foundRecs = $awardsObj->findTransaction($avuuid,$searchString);       
        
        if ($foundRecs){
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
           
            foreach($foundRecs as $trans){
                    $dataLine .= "ID:".$trans->id."|"; 
                    $dataLine .= "ITYPE:".$trans->itype."|"; 
                    $dataLine .= "AMT:".$trans->amount."\n"; 
            } //foreach
            
        } else{ //no groups exist for this award in sloodle_awards_teams
            $sloodle->response->set_status_code(-500800);//A transaction was searched for based on avatar uuid, and transaction details.  However, we could not find the transaction searched for, based on the query specified
            $sloodle->response->set_status_descriptor('HQ'); //line 0 
        }//else
            $sloodle->response->add_data_line("AVUUID:". $avuuid);    
            $sloodle->response->add_data_line("QUERY:". $searchString); 
            if ($dataLine!="") $sloodle->response->add_data_line($dataLine);              
     } //function findTransaction()
     
     function submitGrade(){
        global $sloodle;   
        global $CFG;  
         //request any variables that were sent from Second Life               
         $assignmentName =   $sloodle->request->required_param('assignmentname');
         $assignmentId =   $sloodle->request->required_param('assignmentid');
         $uuid=   $sloodle->request->required_param('uuid');  
         $avname= $sloodle->request->required_param('avname');  
         $mark=   $sloodle->request->required_param('grade');         
         if (!function_exists('grade_update')) { //workaround for buggy PHP versions
            require_once($CFG->libdir.'/gradelib.php');
         }
         //get the cmid
         $coursemoduleid= $sloodle->course->controller->cm->id;    
         //construct sCourseObj 
         $sCourseObj = new sloodleCourseObj($coursemoduleid);  
         
         //get moodleId for the avatar which was sent
         $avUser = new SloodleUser( $sloodle );
         $avUser->load_avatar($uuid,$avname);
         $avUser->load_linked_user();
         $userid = $avUser->avatar_data->userid;
         $grade = new object();
         $grade->userid   = $userid;
         $grade->rawgrade = $mark;             
         $params=array("itemname"=>$assignmentName);
         //$grade->itemtype="quiz";         
         //$grade->itemname=$awardName;
         // $params= new object;
         //$params->itemname=$awardName;
         grade_update("mod/assignment",$sloodle->course->get_course_id(),'mod','assignment',0,$assignmentId,$grade,$params);         
         $sloodle->response->set_status_code(1);             //line 0 
         $sloodle->response->set_status_descriptor('OK'); //line 0 
     }
     /**********************************************************
     * @method getBalance will return the total sum of all point credits, point debits, and cash a user has in the      
     * entire MOODLE site
     * @author Paul Preibisch
     *         
     * @package sloodle
     */
     function getBalance(){
         global $CFG;
         global $sloodle;
         $avuuid=   $sloodle->request->required_param('avuuid');  
         $avname=   $sloodle->request->required_param('avname'); 
         $avUser = new SloodleUser( $sloodle );
         $avUser->load_avatar($avuuid,$avname);
         $avUser->load_linked_user();
         $userid = $avUser->avatar_data->userid;
          if (empty($userid)){
           $sloodle->response->set_status_code(-331);             //line 0 - User did not have permission to access the resources requested
           $sloodle->response->set_status_descriptor('USER_AUTH'); //line 0  
           return; 
        }      
         $userData = get_records('sloodle_award_trans','userid',$userid);
         $pointTotal=0;
         $pointCredits=0;
         $pointDebits=0;
         
         $cashTotal=0;
         $cashDebits = 0;
         $cashCredits =0;
         
         if (!empty($userData)){
            foreach ($userData as $d){
                switch ($d->itype){                
                   case "cashCredit":
                        $cashTotal+=$d->amount; 
                        $cashCredits+=$d->amount; 
                    break;
                    case "cashDebit":
                        $cashTotal-=$d->amount; 
                        $cashDebits+=$d->amount; 
                    break;
                    case "credit":
                        $pointTotal+=$d->amount; 
                        $pointCredits+=$d->amount; 
                    break;
                    case "debit":
                        $pointTotal-=$d->amount; 
                        $pointDebits+=$d->amount; 
                    break;                 
                }//switch
            }//foreach   
            $data = new stdClass();
            $data->cashTotal = $cashTotal;
            $data->cashCredits = $cashCredits;
            $data->cashDebits = $cashDebits;
            
            $data->pointTotal = $pointTotal;
            $data->pointCredits = $pointCredits;
            $data->pointDebits = $pointDebits;
            
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
            $sloodle->response->add_data_line("POINT TOTAL:". $data->pointTotal);    
            $sloodle->response->add_data_line("POINT CREDITS:". $data->pointCredits);    
            $sloodle->response->add_data_line("POINT DEBITS:". $data->pointDebits);    
            
            $sloodle->response->add_data_line("CASH TOTAL:". $data->cashTotal);    
            $sloodle->response->add_data_line("CASH CREDITS:". $data->cashCredits);    
            $sloodle->response->add_data_line("CASH DEBITS:". $data->cashDebits);    
            $sloodle->response->add_data_line("USERID:". $userid);     
        }//if
        else {
           $sloodle->response->set_status_code(-777000);             //line 0  transaction not found           
           $sloodle->response->set_status_descriptor('TRANSACTION '.$userid); //line 0  
           $sloodle->response->add_data_line("USER ID:".$userid );    
        }             
     }//function
}//class
?>