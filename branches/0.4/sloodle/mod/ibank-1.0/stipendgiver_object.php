<?php                          
/**
* Defines a class to manipilate a stipendGiver
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @see http://slisweb.sjsu.edu/sl/index.php/Sloodle_Stipend_Giver
*
* @author Paul Preibisch - aka Fire Centaur 
*/

require_once(SLOODLE_LIBROOT.'/ipointbank_object.php');
  class stipendGiverObject extends iPointBank{
      
      /*
      * @var $startingBalance - the default value of the stipend
      */
      var $startingBalance = 0;
      
      /*
      * @var $stipendGiverRec - the actual record of the stipendgiver from sloodle_stipengiver
      */
      var $stipendGiverRec = null;      
      /*
      * @var $icurrency - can be either iPoints or Lindens - specified by initial config page
      */  
      var $icurrency = '';
      /*
      * @var $sloodleId - sloodleId of this stipendgiver
      */  
      var $sloodleId = null;

      /*
      * The class Contstructor
      * @var $id - the sloodle id of this stipendgiver
      */
      function __construct($id){
          parent::__construct($id);
          $this->stipendGiverRec = get_record('sloodle_stipendgiver','sloodleid',$id);
          $this->startingBalance = $this->stipendGiverRec->amount;        
          $this->itype = $this->stipendGiverRec->icurrency;
          $this->icurrency = $this->stipendGiverRec->icurrency;
          $this->sloodleId=$id;
      }
      /**
     * @method updateUserList
     * @author Paul Preibisch
     * 
     * updateUserList searches through the list of users in the course, 
     * and adds new stipends for new users to transactions table, and or removes students stipends 
     * who have been removed from the class list    
     *  
     * @package sloodle
     * @return true if found
     * @return false if not found
     * 
     * @staticvar $userList users in this course
     * @staticvar $userId - moodle id of user sought
     */
      function updateUserList($userList){
          //Get the existing users
          //for each user who doesnt have a stipend allocated, allocate them 0 - because they have just been added
           $newStipends = array();
           foreach ($userList as $u){
                if ($this->getStipendRec($u->id)==false){
                    $newStipend = new stdClass();
                    $newStipend->userid = $u->id;
                    $newStipend->sloodleid = $this->sloodleId;
                    $newStipend->itype="stipend";
                    $newStipend->amount=0; //set amount to zero so teacher must manually add stipends for new students
                    $newStipend->timemodified = time();
                    $newStipends[]=$newStipend;
                }
            }
            //now add to iPoint_trans records for the new students who have not been alocated the default stipend.
            foreach ($newStipends as $nS){
                $this->makeTransaction($nS);
            }
            //also, check to see if any students have been removed from the class, then we should
            //remove their stipends!
                         //build list of students who have been allocated stipends
              //get existing transactions
                //compare userlist with all the stipend transactions.  If there is a transactopm 
                //for a user who is not in the course, remove that stipend allocation
               $tRecs = $this->getTransactionRecords();         
                foreach ($tRecs as $t){
                 if ($t->itype=="stipend"){
                     //check if this user is in this class
                     $found = $this->searchUserList($userList, $t->userid);
                     //if user of this stipend is not in the userList, remove this stipend
                     if (!$found){
                         $this->removeTransaction($t->userid,"stipend");
                     }                 
                 }
                }
      }
     /**
     * @method searchUserList
     * @author Paul Preibisch
     * 
     * searchUserList searches through the list of users     
     *  
     * @package sloodle
     * @return true if found
     * @return false if not found
     * 
     * @staticvar $userList users in this course
     * @staticvar $userId - moodle id of user sought
     */
     
     function searchUserList($userList, $userid){
           $found = false;
           foreach ($userList as $u){
               if ($u->id == $userid) $found=true;
           }
           return $found;
     }
      /*
      * getStartingBalance gets the default stipend property for this stipendgiver object
      */
      function getStartingBalance(){
        return $this->startingBalance;      
      }
      /*
      * setStartingBalance - sets this class property starting balance which is actually the default stipend. DOES NOT write to the db
      * @var $newStartingBalace - x
      */
      function setStartingBalance($newStartingBalance){
        $this->startingBalance = $newStartingBalance;  
      }
      /*
      * geticurrency gets the currency property of this object
      */
      function geticurrency(){
          return $this->icurrency;      
      }


     
     /**
     * @method getStipendBudget
     * @author Paul Preibisch
     * 
     * getStipendBudget gets the stipend amount allocated to this user 
     *  
     * @package sloodle
     * @return returns bugeted amount for this user, 
     * @return If $userId is null, the default stipend of the stipend is returned
     * @returns false if no stipend was allocated for the userId specified
     * 
     * @staticvar $userId moodle id of the user
     */
     function getStipendBudget($userid,$userList){
          //transactionRecords fields are: 
          //avuuid,userid,avname,amount,type,timemodified
          //first update the transaction list in case new students have been added
          $this->updateUserList($userList);
          $budgetAmount = 0;
          if ($userid==null)
            $transRecs = $this->getTransactionRecords();
          else
            $transRecs = $this->getTransactionRecords($userid);          
          if ($transRecs)   {
            foreach ($transRecs as $t)
               if ($t->itype=='stipend')                
                   $budgetAmount +=$t->amount;
            return $budgetAmount; 
          }
          else return false;
         
     }
     /**
     * @method setStipendBudget
     * @author Paul Preibisch
     * 
     * setStipendBudget can be used to set the default stipend, 
     * or a stipend of an individual user     
     *  
     * @package sloodle
     * @return true if record can be updates
     * @return false if there was an error updating ipoint_trans table
     * @staticvar $amount is amount of the stipend - defaults to zero
     * @staticvar $userId moodle id of the user
     * 
     * 
     */
     
     function setStipendBudget($amount=0,$userId=null){
        $update = new stdClass();  
        if ($userId==null){ //modify default stipend                        
            $this->setStartingBalance($amount);            
            $updatedStipendGiver = new stdClass();
            $updatedStipendGiver->id = $this->stipendGiverRec->id;
            $updatedStipendGiver->amount = $amount;
            if (!update_record("sloodle_stipendgiver",$updatedStipendGiver)){
                $sloodle->response->quick_output(-701, 'STIPENDGIVER', 'Failed to update stipendGiver for default stipend in setStipendBudget function', FALSE);
                return false;
            }
        }else{ //modify stipend budget of individual user
            
            $stipendRec = $this->getStipendRec($userId);            
            $update = new stdClass();
            $update->id = $stipendRec->id;
            $update->amount = $amount;
            if (!update_record('sloodle_ipoint_trans', $update)){
             $sloodle->response->quick_output(-701, 'STIPENDGIVER', 'Failed to update stipendGiver in setStipendBudget function', FALSE);
                 return false;
             }
             
        }
            
        return true;
     }
     /**
     * @method getStipendRec
     * @author Paul Preibisch
     * 
     * getStipendRec returns the stipend record for this user. 
     * itype in the sloodle_ipoint_trans table must be 'stipend', 
     *       
     * @package sloodle
     * @returns a record from the sloodle_ipoint_trans table
     * @returns false if no stipend has been allocated
     * @staticvar $userId moodle id of the user
     * 
     * 
     */
     function getStipendRec($userid){
         global $CFG;
         $userStipendId = null;
         $sql = 'SELECT * FROM '.$CFG->prefix.'sloodle_ipoint_trans'.
                             ' WHERE itype=\'stipend\' AND sloodleid='.$this->getSloodleId() .
                             ' AND userid='.$userid;
         $userStipendId = get_record_sql($sql);                     
         return $userStipendId; 
        
        
        }
  }
?>
