<?php
    /**
    * The awards class provides basic transaction functions for the Sloodle Awards module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    * @see Awards
    * @contributor Paul G. Preibisch - aka Fire Centaur 
    */
global $CFG;    

    @include_once($CFG->dirroot.'/mod/assignment/type/sloodleaward/assignment.class.php');
require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');

  class Awards {
   
      var $sloodleId = null;
      var $transactionRecords = null;
      /*
      * @var $awardRec - the actual record of the stipendgiver from sloodle_stipengiver
      */
      var $sloodle_awards_instance = null;     
      var $xmlchannel; 
      var $icurrency;
      /*
      * The class Contstructor
      * @var $id - the sloodle id of this stipendgiver
      */
      function Awards($courseModuleId){  
          global $sloodle;
          $cm = get_coursemodule_from_id('sloodle',$courseModuleId);              
          $cmid = $cm->instance;
          $this->sloodle_awards_instance = get_record('sloodle_awards','sloodleid',$cmid); 
          $this->sloodleId=$cmid;           
          if ($this->sloodle_awards_instance->icurrency){
              $this->icurrency=$this->sloodle_awards_instance->icurrency;
          }
          $this->transactionRecords = $this->awards_getTransactionRecords();          
      }
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
      function getScoreboards($sloodleId){
        $scoreboardRecs=get_records('sloodle_awards_scoreboards','sloodleid',$sloodleId);    
        return $scoreboardRecs;
      }
      
      //set functions
      function setUrl($url){        
          $scoreboard = new stdClass();
          $scoreboard->url = $url;
          $scoreboard->sloodleid = $this->sloodleId;          
          return insert_record("sloodle_awards_scoreboards",$scoreboard);        
      }
      function setAmount($amount){
          $this->sloodle_awards_instance->amount=(int)$amount; 
          $this->timeupdated = (int)time();
          return update_record('sloodle_awards', $this->sloodle_awards_instance);
        
      }

        //Function by Simba Fuhr
        //Use under the GPL License
        function callLSLScript($URL, $Data, $Timeout = 10)
        {
         //Parse the URL into Server, Path and Port
         $Host = str_ireplace("http://", "", $URL);
         $Path = explode("/", $Host, 2);
         $Host = $Path[0];
         $Path = $Path[1];
         $PrtSplit = explode(":", $Host);
         $Host = $PrtSplit[0];
         $Port = $PrtSplit[1];
         
         //Open Connection
         $Socket = fsockopen($Host, $Port, $Dummy1, $Dummy2, $Timeout);
         if ($Socket)
         {
          //Send Header and Data
          fputs($Socket, "POST /$Path HTTP/1.1\r\n");
          fputs($Socket, "Host: $Host\r\n");
          fputs($Socket, "Content-type: application/x-www-form-urlencoded\r\n");
          fputs($Socket, "User-Agent: Opera/9.01 (Windows NT 5.1; U; en)\r\n");
          fputs($Socket, "Accept-Language: de-DE,de;q=0.9,en;q=0.8\r\n");
          fputs($Socket, "Content-length: ".strlen($Data)."\r\n");
          fputs($Socket, "Connection: close\r\n\r\n");
          fputs($Socket, $Data);
           $res="";
          //Receive Data
          while(!feof($Socket))
           {$res .= fgets($Socket, 128);}
          fclose($Socket);
         }
         
         //ParseData and return it
         if (isset($res)){
            $res = explode("\r\n\r\n", $res);
            return $res[1];
         }else return false;
         
        }
      function setIcurrency($icurrency){
        $this->sloodle_awards_instance->icurrency=$icurrency; 
        $this->timeupdated = time();
        return update_record('sloodle_awards', $this->sloodle_awards_instance);  
      }      
      function setTimemodified($timemodified){
        $this->sloodle_awards_instance->timemodified=$timemodified;  
        $this->timeupdated = time();
        return update_record('sloodle_awards', $this->sloodle_awards_instance);  
      } 
    
       /**********************************
       * synchronizeDisplays($transactions)
       *    This function will get all entries in sloodle_awards_scoreboards that match this award id. It will then send an http request to the each URL 
       *    "COMMAND:GET DISPLAY DATA" and will receive a response indication which display is currently being viewed, and the data currently being displayed in SL
       *    If the currently displayed data matches any of the users in the transaction list, then needsUpdating will be set to true, and an update command will be sent
       *    into SL
       * 
       * @param mixed $transactions
       */
       function synchronizeDisplays($transactions){
          global $sCourseObj;
           //get all httpIn urls connected to this award
           $scoreboards = $this->getScoreboards($this->sloodleId);
           //$sendData='COMMAND:WEB UPDATE|DESCRIPTION:transactionProcessed|AWARDID:'.$sCourseObj->sloodleId."|AVKEY:".$iTransaction->avuuid."|AVNAME:".$iTransaction->avname."|ITYPE:".$iTransaction->itype.'|AMOUNT:'.$iTransaction->amount."|".$iTransaction->idata;
            if ($scoreboards){
                foreach ($scoreboards as $sb){
                    //get current display of each scoreboard
                    $displayData = $this->callLSLScript($sb->url,"COMMAND:GET DISPLAY DATA\n",10);
                    $dataLines = explode("\n", $displayData);
                    if ($displayData){
                        $currentView = $this->getFieldData($dataLines[0]);
                        if ($currentView=="Top Scores"||$currentView=="Sort by Name"){
                            $userData = Array();
                            //initially set needsUpdating to false, change to true if any users being displayed have
                            //processed transactions                            
                            $needsUpdating = false;
                            //build user list from display
                            $numUsers = count($dataLines);
                            $updateString="";
                            for ($userCounter=1;$userCounter<$numUsers;$userCounter++){
                                $userData = explode("|", $dataLines[$userCounter]);                                                            //set updateString to empty
                                
                                //check if user is in transaction list
                             
                                    foreach ($transactions as $t){
                                        if (isset($userData[1])){
                                            if ($t->avname==$userData[1]){
                                                $needsUpdating = true;
                                                $updateMsg="AVKEY:".$t->avuuid."|AVNAME:".$t->avname;
                                                $updateMsg.="|ITYPE:".$t->itype.'|AMOUNT:'.$t->amount;
                                                $updateString.=$updateMsg."\n";
                                                //as soon as we find the individual transaction, 
                                                //exit the for loop
                                                break;
                                            }//endif                                                
                                        }//endif isset
                                    }//foreach
                                
                            }//for
                            //if any updates where made to students being displayed on this 
                            //scoreboard send update command into sl
                            if ($needsUpdating){
                                //send update into SL for this scoreboard
                                $result = $this->callLSLScript($sb->url,"COMMAND:UPDATE DISPLAY\n".$updateString,10);
                            }//endif $needsUpdating
                        }//endif $currentView=="Top Scores"||$currentView=="Sort by Name"
                        else                         
                        if ($currentView=="Team Top Scores"){
                            //set $needsUpdating initially to false
                            $needsUpdating = false;
                            //get the courseId for this award activity
                            
                            //get number of groups for this scoreboard
                            $numGroups = count($dataLines);           
                            //for each scoreboard group, check if transactions have been made for any members
                            for ($i=1;$i<$numGroups;$i++){
                                //get groupData from display
                                $groupData = explode("|", $dataLines[$i]);
                                //get group name
                                $groupName = $groupData[0];                                
                                //get Group record
                                $group = groups_get_group_by_name($sCourseObj->courseId,$groupName);
                                if ($group){
                                    $groupId = $group;
                                    //set updateString to empty
                                    $updateString ="";
                                    //go through each transaction and see if it matches this userid
                                    foreach ($transactions as $t){
                                        if (groups_is_member($groupId,$t->userid)){
                                            $needsUpdating = true;
                                            $updateMsg="AVKEY:".$t->avuuid."|AVNAME:".$t->avname;
                                            $updateMsg.="|ITYPE:".$t->itype.'|AMOUNT:'.$t->amount;
                                            $updateString.=$updateMsg."\n";
                                        }//if
                                    }//endforeach
                                }//endif group
                            }//for
                            if ($needsUpdating){
                                //this means one or more of the groups points has changed
                                $result = $this->callLSLScript($sb->url,"COMMAND:UPDATE DISPLAY\n".$updateString,8);
                            }
                        }//endif$currentView=="Team Top Scores"
                    }//end if displayData
                }//foreach scoreboard
            }//endif $scoreboards
       }//function
       /**********************************
       * synchronizeDisplays_SL($transactions)
       *    This function works the same as the synchronizeDisplays but the transaction object is only one single transaction
       *    It starts by getting all entries in sloodle_awards_scoreboards that match this award id. It will then send an http request to the each URL 
       *    "COMMAND:GET DISPLAY DATA" and will receive a response indication which display is currently being viewed, and the data currently being displayed in SL
       *    If the currently displayed data matches the user in the transaction list, then needsUpdating will be set to true, and an update command will be sent
       *    into SL
       * 
       * @param mixed $transactions
       */
        function synchronizeDisplays_sl($transaction){
          global $sloodle;
           //get all httpIn urls connected to this award
           $scoreboards = $this->getScoreboards($this->sloodleId);      
            if ($scoreboards){
                foreach ($scoreboards as $sb){
                    //get current display of each scoreboard
                    $displayData = $this->callLSLScript($sb->url,"COMMAND:GET DISPLAY DATA\n",8);
                    $dataLines = explode("\n", $displayData);
                    if ($displayData){
                        $currentView = $this->getFieldData($dataLines[0]);
                        if ($currentView=="Top Scores"||$currentView=="Sort by Name"){
                            $userData = Array();
                            //initially set needsUpdating to false, change to true if any users being displayed have
                            //processed transactions                            
                            $needsUpdating = false;
                            //build user list from display
                            $numUsers = count($dataLines);
                            $updateString="";
                            for ($userCounter=1;$userCounter<$numUsers;$userCounter++){
                                $userData = explode("|", $dataLines[$userCounter]);                                                            //set updateString to empty                                //check if user is in transaction list
                                        $t=$transaction;
                                        if ($t->avname==$userData[1]){
                                            $needsUpdating = true;
                                            $updateMsg="AVKEY:".$t->avuuid."|AVNAME:".$t->avname;
                                            $updateMsg.="|ITYPE:".$t->itype.'|AMOUNT:'.$t->amount;
                                            $updateString.=$updateMsg."\n";
                                            //as soon as we find the individual transaction, exit the for loop      
                                            break;
                                        }//endif
                            }//for
                            //if any updates where made to students being displayed on this 
                            //scoreboard send update command into sl
                            if ($needsUpdating){
                                //send update into SL for this scoreboard
                                $result = $this->callLSLScript($sb->url,"COMMAND:UPDATE DISPLAY\n".$updateString,8);
                            }//endif $needsUpdating
                        }//endif $currentView=="Top Scores"||$currentView=="Sort by Name"
                        else                         
                        if ($currentView=="Team Top Scores"){
                            //set $needsUpdating initially to false
                            $needsUpdating = false;
                            //get the courseId for this award activity
                            $courseId = $sloodle->course->get_course_id();
                            //get number of groups for this scoreboard
                            $numGroups = count($dataLines);           
                            //for each scoreboard group, check if transactions have been made for any members
                            for ($i=1;$i<$numGroups;$i++){
                                //get groupData from display
                                $groupData = explode("|", $dataLines[$i]);
                                //get group name
                                $groupName = $this->getFieldData($groupsData[0]);                                
                                //get Group record
                                $group = groups_get_group_by_name($groupName);
                                if ($group){
                                    $groupId = $group->id;
                                    //set updateString to empty
                                    $updateString ="";
                                    //go through each transaction and see if it matches this userid
                                   $t=$transaction;
                                        if (groups_is_member($groupId,$t->userid)){
                                            $needsUpdating = true;
                                            $updateMsg="AVKEY:".$t->avuuid."|AVNAME:".$t->avname;
                                            $updateMsg.="|ITYPE:".$t->itype.'|AMOUNT:'.$t->amount;
                                            $updateString.=$updateMsg."\n";
                                        }//if

                                }//endif group
                            }//for
                            if ($needsUpdating){
                                //this means one or more of the groups points has changed
                                $result = $this->callLSLScript($sb->url,"COMMAND:UPDATE DISPLAY\n".$updateString,10);
                            }
                        }//endif$currentView=="Team Top Scores"
                    }//end if displayData
                }//foreach scoreboard
            }//endif $scoreboards
       }//function
      /**
     * @method awards_makeTransaction
     * @author Paul Preibisch
     * 
     * makeTransaction inserts a record into the sloodle_award_trans table
     * then sends an xml message into Second Life to trigger the scoreboard to request an update
     * It then updates the grade in the gradebook
     *  
     * @package sloodle
     * @returns returns true if insert was successful
     * @returns returns false if insert was unsuccessful  
     * 
     * @$iTransaction is a dataObject (stdClass object)  with appropriate fields matching the table structure
     */
      function awards_makeTransaction($iTransaction,$sCourseObject){      
         global $USER,$COURSE,$sCourseObj; 
         
         //check to see if the transaction will result in a negative balace
          
        if (insert_record('sloodle_award_trans',$iTransaction)) {
            $balanceDetails = $this->awards_getBalanceDetails($iTransaction->userid);          
            if ($balanceDetails->balance<0){
              $iTransaction->amount=  $balanceDetails->balance*-1;
              $iTransaction->itype="credit";
              $iTransaction->idata="DETAILS:System Modified Balance adjustment"; 
              insert_record('sloodle_award_trans',$iTransaction); 
            }//endif
            
            //get maxpoint limit
            $maxPoints = $this->sloodle_awards_instance->maxpoints;
            //Get balance 
            $newGrade=0;
            $detailsRec = $this->awards_getBalanceDetails($iTransaction->userid);
            //make sure we dont give more points than max points
            $pointsEarned = $detailsRec->balance;
            if ($pointsEarned > $maxPoints) $pointsEarned =$maxPoints;
            $newGrade = $pointsEarned / $maxPoints *100;
            //now make the insert newGrade into the gradebook.
            $assignmentId = $this->sloodle_awards_instance->assignmentid;
            
            if ($assignmentId!="") {
                //first we have to find which grade item this assignment is                
                $gradeItem = get_record('grade_items','iteminstance',$assignmentId);
                $itemId = $gradeItem->id; 
                $newgrades= new stdClass();
                $newgrades->usermodified=$USER->id;
                $newgrades->usermodified=$USER->id;
                $newgrades->rawgrade=$newGrade;
                 $newgrades->userid=$iTransaction->userid;
                 global $CFG;
                 $sCourseObj = $sCourseObject;
                 $assignment = get_record('assignment','id',$assignmentId);
                 $sloodleAssignment = new assignment_sloodleaward($sCourseObj->cm->id,$assignment,$sCourseObj->cm,$sCourseObj->courseRec);
                 
                 $submission = $sloodleAssignment->prepare_new_submission($iTransaction->userid,true);
                 $submission->timecreated=time();
                 $submission->timemarked=time();
                 $submission->timemodified=time();
                 
                 $submission->teacher = $USER->id;
                 $submission->submissioncomment="Sloodle Awards Point Update ";
                 $submission->data1="Sloodle Awards Point Update ";
                 $submission->grade=(int)$newGrade;
                 $sloodleAssignment->update_submission($iTransaction->userid,$submission);
                 require_once($CFG->libdir . "/gradelib.php");
                 grade_update( 'mod/assignment', $sCourseObj->courseId, 'mod', 'assignment', $assignmentId, 0, $newgrades);
            }
           
            return true;
        }
        else return false;
      }
    /**
     * @method awards_getTransactionRecords
     * @author Paul Preibisch
     * 
     * getTransactionRecords returns the recordset of sloodle_award_trans record 
     * for the $userId specified.  if $userId is null, returns all the transaction records for this stipend
     *  
     * @package sloodle
     * @return returns transaction records for this user / or for all users
     * @return If $userId is null, then all transactions are returned
     * 
     * @staticvar $userId moodle id of the user
     */
      function awards_getTransactionRecords($userId=null){
          global $CFG,$awardsObj;
         if (!$userId){
            return get_records_select('sloodle_award_trans','sloodleid='.$this->sloodleId,'Timemodified DESC');
         }
         else {
            return get_records_select('sloodle_award_trans','userid='.$userId.' AND sloodleid='.$awardsObj->sloodleId,'Timemodified ASC');
            
            
         }
      }
      /**
       * getTotals - returns the credit, debit, and balance totals for all of the students 
       * 
       */
       function getTotals(){
         global $CFG; 
         $totalAmountRecs = get_records_select('sloodle_award_trans','itype=\'credit\' AND sloodleid='.$this->sloodleId);
         $credits=0;
         if ($totalAmountRecs)
            foreach ($totalAmountRecs as $userCredits){
                 $credits+=$userCredits->amount;
            }
         $totalAmountRecs = get_records_select('sloodle_award_trans','itype=\'debit\' AND sloodleid='.$this->sloodleId);
         $debits=0;         
         if ($totalAmountRecs)
            foreach ($totalAmountRecs as $userDebits){
                 $debits+=$userDebits->amount;
            }
         $balances = $credits-$debits;
         $totals= new stdClass();
         $totals->totalcredits = $credits;
         $totals->totaldebits = $debits;
         $totals->totalbalances = $credits - $debits;  
         
         return $totals;
      }
     
      /**
     * @method removeTransaction
     * @author Paul Preibisch
     * 
     * removeTransaction removes the transaction for this stipend     
     *  
     * @package sloodle
     * @return true if successful
     * @return false if unsuccessful
     * 
     * @staticvar $userId moodle id of the user
     * @staticvar $iType type of the transaction "stipend","credit","debit"
     */
     
     function awards_removeTransaction($userId,$iType){
         return delete_records("sloodle_award_trans",'sloodleid',$this->getSloodleId(),'itype',$iType,'userid',$userId);
     }
     function awards_updateTransaction($transRec){
        if (!update_record("sloodle_award_trans",$transRec))
            error(get_string("cantupdate","sloodle"));
     }
     function get_assignment_id(){
         return $this->sloodle_awards_instance->assignmentid;
     }
     function get_assignment_cmid($courseId){
         
          $recs = get_record('course_modules','instance',(int)$this->sloodle_awards_instance->assignmentid,'course',(int)$courseId);
         if ($recs)
            return $recs->id;
         else return null;
     }
     function get_assignment_name(){
         $recs = get_record('assignment','id',(int)$this->sloodle_awards_instance->assignmentid);
         if ($recs)
            return $recs->name;
         else return null;
     }
      
      /**
     * @method getLastTransaction
     * @author Paul Preibisch
     * 
     * getLastTransaction will retrieve the last transaction made for this user
     *  
     * @package sloodle
     */
     function getLastTransaction($avuuid,$details)    {
         global $CFG; 
         //get the maximum id (the last transaction) of a user with the details in idata in transaction db - this is the last transaction
        
         //get id of user         
         $awardTrans = get_records_select('sloodle_award_trans','avuuid',addSlashes($avuuid),'sloodleid',$this->sloodleId);         
         $maxId = 0;         
         foreach ($awardTrans as $trans){
             //find records with the $details in the idata
            if (strstr($awardTrans->idata,addSlashes($details))){
                //find max id
                if ($awardTrans->id>$maxId){
                    $maxId=$awardTrans->id;
                }                
            }
         }
         if ($maxId!=0){
             $rec = get_record('sloodle_award_trans','id',$maxId);            
             return $rec;
         }else {             
             return "";
         }
     } 
      /**
     * @method findTransaction
     * @author Paul Preibisch
     * 
     * getLastTransaction will retrieve the last transaction made for this user
     *  
     * @package sloodle
     */
     function findTransaction($avuuid,$details)    {
      global $CFG; 
         //get the maximum id (the last transaction) of a user with the details in idata in transaction db - this is the last transaction
        
         //get id of user         
         $awardTrans = get_records_select('sloodle_award_trans',"avuuid='".addSlashes($avuuid)."'".' AND sloodleid='.$this->sloodleId);         
         $foundArray = Array();      
         foreach ($awardTrans as $trans){
             //find records with the $details in the idata
            if (strstr($trans->idata,addSlashes($details))){
                //find max id
               $foundArray[]=$trans;
            }
         }
         return $foundArray;
     } 
     /* awards_getBalanceDetails - gets the total balance, credit, debits for a user
     *  @author Paul Preibisch
     * 
     * @package sloodle
     * @return returns a stdObj with credits, debits, balance for the given userid
     */       
     function awards_getBalanceDetails($userid){
         global $CFG;
         $totalAmountRecs = get_records_select('sloodle_award_trans','itype=\'credit\' AND sloodleid='.$this->sloodleId.' AND userid='.$userid);
         $credits=0;
         if ($totalAmountRecs)
            foreach ($totalAmountRecs as $userCredits){
                 $credits+=$userCredits->amount;
            }
         $totalAmountRecs = get_records_select('sloodle_award_trans','itype=\'debit\' AND sloodleid='.$this->sloodleId.' AND userid='.$userid);
         $debits=0;         
         if ($totalAmountRecs)
            foreach ($totalAmountRecs as $userDebits){
                 $debits+=$userDebits->amount;
            }
          $balance = $credits-$debits;
          $acountInfo = new stdClass();
          $acountInfo->credits = $credits;
          $acountInfo->debits = $debits;          
          $acountInfo->balance = $balance;
         return $acountInfo;      
     } 
   
     /**
     * getAvatarDebits function
     * @desc This function will search through all the transactions 
     * for this stipend based on avatar uuid and return the TOTAL debits amount
     * @staticvar integer $debitAmount will be zero if no debits exist for this user and stipend
     * @param string $avuuid the avatar UUID to search for
     * @link http://sloodle.googlecode.com
     * @return integer 
     */ 
     function getAvatarDebits($avuuid){
          //transactionRecords fields are: 
          //avuuid,userid,avname,amount,type,timemodified
          $debits = 0;   
         foreach ($this->getTransactionRecords() as $t){
            if ($t->avuuid == $avuuid)
               if ($t->itype=='debit')
                    $debits +=$t->amount;
         }
         return $debits; 
     } 
     /**
 * getUserDebits function
 * @desc This function will search through all the transactions 
 * and return TOTAL debits for this course user
 * @staticvar integer $debitAmount will be zero if no debits exist for this user and stipend
 * @param string $avuuid the avatar UUID to search for
 * @link http://sloodle.googlecode.com
 * @return integer 
 */ 
 function getUserDebits($userid=null){
      //transactionRecords fields are: 
      //avuuid,userid,avname,amount,type,timemodified
      $debits = 0;
      if ($userid==null)
          $transRecs = $this->getTransactionRecords();
      else      
          $transRecs = $this->getTransactionRecords($userid);
          if ($transRecs)
            foreach ($transRecs as $t)
                   if ($t->itype=='debit')
                       $debits +=$t->amount;
     return $debits; 
 }
    function get_class_list(){
            $fulluserlist = get_users(true, '');
            if (!$fulluserlist) $fulluserlist = array();
            $userlist = array();
            // Filter it down to members of the course
            foreach ($fulluserlist as $ful) {
                // Is this user on this course?
                if (has_capability('moodle/course:view', $this->course_context, $ful->id)) {
                    // Copy it to our filtered list and exclude administrators
                    if (!isadmin($ful->id))
                      $userlist[] = $ful;
                }
            }
            return $userlist;
      
      }
  }      
?>
