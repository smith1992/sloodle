<?php
    /**
    * The iPointBank class provides basic transaction functions for the Sloodle iBank module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    * @see iBank
    * @contributor Paul G. Preibisch - aka Fire Centaur 
    */
  class iPointBank{
   
      var $name='';
      
      var $sloodleid = null;
      
      var $transactionRecords = null;
      
      function iPointBank($id){
          $this->sloodleid = $id;                    
          $this->transactionRecords = $this->getTransactionRecords();          
      
      }
       /**
     * @method makeTransaction
     * @author Paul Preibisch
     * 
     * makeTransaction inserts a record into the sloodle_ipoint_trans table
     * 
     *  
     * @package sloodle
     * @returns returns true if insert was successful
     * @returns returns false if insert was unsuccessful  
     * 
     * @$iTransaction is a dataObject (stdClass object)  with appropriate fields matching the table structure
     */
      
      function makeTransaction($iTransaction){      
        if (insert_record('sloodle_ipoint_trans',$iTransaction)) return true;
        else return false;
      }
      
      function update(){
         $this->transactionRecords = $this->getTransactionRecords(); 
          
          
      }
    /**
     * @method getTransactionRecords
     * @author Paul Preibisch
     * 
     * getTransactionRecords returns the recordset of sloodle_ipoint_trans record 
     * for the $userId specified.  if $userId is null, returns all the transaction records for this stipend
     *  
     * @package sloodle
     * @return returns transaction records for this user / or for all users
     * @return If $userId is null, then all transactions are returned
     * 
     * @staticvar $userId moodle id of the user
     */
      function getTransactionRecords($userId=null)
      {
          global $CFG;
         if (!$userId){
            return get_records('sloodle_ipoint_trans','sloodleid',$this->getSloodleId());          
         }
         else {
            $sql = 'SELECT * FROM '.$CFG->prefix.'sloodle_ipoint_trans'.
            ' WHERE userid='.$userId.' AND sloodleid='.$this->getSloodleId().
            ' ORDER BY timemodified DESC';
            $transRecs = get_records_sql($sql);
            return $transRecs;            
            
         }
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
     
     function removeTransaction($userId,$iType){
         if (delete_records("sloodle_ipoint_trans",'sloodleid',$this->sloodleid,'itype',$iType,'userid',$userId))
            return true;
            else return false;
     }
      function updateTransaction($transactionUpdate){
        if (!update_record("sloodle_ipoint_trans",$transactionUpdate))
            error(get_string("stipendgiver:cantupdate","sloodle"));
        
      }
      
      function getSloodleId(){
        return $this->sloodleid;
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
 
      
      
  
  }
?>
