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

  class Awards{
   
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
      function Awards($sloodleId){          
          
  
          $this->sloodle_awards_instance = get_record('sloodle_awards','sloodleid',$sloodleId); 
          $this->sloodleId=$sloodleId;           
          $this->icurrency=$this->sloodle_awards_instance->icurrency;
          $this->transactionRecords = $this->awards_getTransactionRecords();          
      }
      function do_xml_post($data_channel, $data_int, $data_string)
    {
        $service_port = getservbyname('www', 'tcp');
        $address = gethostbyname('xmlrpc.secondlife.com');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        $result = socket_connect($socket, $address, $service_port);

        $data = "<?xml version=\"1.0\"?><methodCall><methodName>llRemoteData</methodName><params><param><value><struct><member><name>Channel</name><value><string>$data_channel</string></value></member><member><name>IntValue</name><value><int>$data_int</int></value></member><member><name>StringValue</name><value><string>$data_string</string></value></member></struct></value></param></params></methodCall>";

        $in = "POST /cgi-bin/xmlrpc.cgi HTTP/1.1\r\n";
        $in .= "Accept: */*\r\n";
        $in .= "Accept-Language: en-gb\r\n";
        $in .= "Cache-control: no-cache\r\n";
        $in .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $in .= "Host: xmlrpc.secondlife.com\r\n";
        $in .= "Content-Length: " . strlen($data) . "\r\n\r\n";
        $in .= $data;
   
        socket_write($socket, $in, strlen($in));      
        socket_close($socket);

    }
      function getXmlChannel($sloodleId){
        $awardRec=get_record('sloodle_awards','sloodleid',$sloodleId);    
        if ($awardRec->xmlchannel==NULL) return 0; else return $awardRec->xmlchannel;
      }
      
      //set functions
      function setXmlChannel($xmlChannel){        
          $this->sloodle_awards_instance->xmlchannel=$xmlChannel; 
          $this->timeupdated = time();
          return update_record('sloodle_awards', $this->sloodle_awards_instance);
        
      }
      function setAmount($amount){
          $this->sloodle_awards_instance->amount=(int)$amount; 
          $this->timeupdated = (int)time();
          return update_record('sloodle_awards', $this->sloodle_awards_instance);
        
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
        if (insert_record('sloodle_award_trans',$iTransaction)) {
            $xmlChannel = $this->getXmlChannel($iTransaction->sloodleid);
            if ($xmlChannel!=0)
                $this->do_xml_post($xmlChannel,"1","UPDATE");
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
      function awards_getTransactionRecords($userId=null)
      {
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
<<<<<<< .mine
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
=======
     }
     function get_assignment_id(){
         return $this->sloodle_awards_instance->assignmentid;
     }
     function get_assignment_name(){
         $recs = get_record('assignment','id',(int)$this->sloodle_awards_instance->assignmentid);
         if ($recs)
            return $recs->name;
         else return null;
     }
      
      /**
>>>>>>> .r742
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
     /* @method awards_getBalanceDetails - gets the total balance, credit, debits for a user
     *  @author Paul Preibisch
     * 
     * @package sloodle
     * @return returns a stdObj with credits, debits, balace for the given userid
     */
       
         function awards_getBalanceDetails($userid){
         global $CFG;
         
         $creditsSql = 'SELECT SUM(amount) as total FROM '.$CFG->prefix.'sloodle_award_trans'.
                             ' WHERE itype=\'credit\' AND sloodleid='.$this->sloodleId .
                             ' AND userid='.$userid;
         $debitsSql = 'SELECT SUM(amount) as total FROM '.$CFG->prefix.'sloodle_award_trans'.
                             ' WHERE itype=\'debit\' AND sloodleid='.$this->sloodleId .
                             ' AND userid='.$userid;  
         $balance=0;                           
         $debits = get_record_sql($debitsSql);
         if ($debits->total==NULL) $tdebits = 0; else $tdebits = $debits->total;                  
         $credits = get_record_sql($creditsSql);   
         if ($credits->total==NULL) $tcredits = 0; else $tcredits = $credits->total;
         $balance =$tcredits -  $tdebits;                  
         $acountInfo = new stdClass();
          $acountInfo->credits = $tcredits;
          $acountInfo->debits = $tdebits;
          $acountInfo->balance = $balance;
         return $acountInfo; 
     }
     
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
      
?>
