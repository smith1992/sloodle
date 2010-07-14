<?php
    // This file is part of the Sloodle project (www.sloodle.org)
   
    /**
    * This file defines a structure for Sloodle data about a particular Moodle course.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
   
    /** Include the general Sloodle library. */
    require_once(SLOODLE_LIBROOT.'/general.php');

   
    /**
    * The Sloodle course data class
    * @package sloodle
    */
    class SloodleCurrency
    {
    // DATA //
   
      
       
       
    // FUNCTIONS //
   
        /**
        * Constructor
        */
        function SloodleCurrency()
        {
            
        }
        function get_currency_types(){
            global $CFG;
                $currencyTypes= get_records_sql("select name,units FROM {$CFG->prefix}sloodle_currency_types ORDER BY 'name' ASC ");
                return $currencyTypes;
        }
        function get_transactions($avname,$currency){
            $sql= "select t.*, c.units from {$CFG->prefix}sloodle_award_trans t INNER JOIN {$CFG->prefix}sloodle_currency_types c ON c.name = t.currency where t.currency='{$currency}' AND t.avname={$avname}";
            $trans = get_records_sql($sql);    
            return $trans;
        }
        
        function get_balance($currency_name,$userid=null,$avuuid=null){
               global $CFG;
            //$currency = get_record('sloodle_currency_types','name',$currency_name);            
            //if (!$currency) return null;//currency doesnt exist
            
            if ($userid!=null){
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where userid={$userid} AND currency='{$currency_name}' AND itype='credit'";
                $credits= get_records_sql($sql);
                $cr=0;
                foreach ($credits as $key =>$val){
                    $cr=$val->amt;
                }
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where userid={$userid} AND currency='{$currency_name}' AND itype='debit'";
                $dbts=0;
                $debits= get_records_sql($sql);
                foreach ($debits as $key =>$val){
                    $dbts=(int)$val->amt;
                }
                $balance=(int)($cr)-(int)$dbts;
                return $balance;
            }
            else
            if ($avuuid!=null){
                  $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where avuuid={$avuuid} AND currency='{$currency_name}' AND itype='credit'";
                $credits= get_records_sql($sql);
                $cr=0;
                foreach ($credits as $key =>$val){
                    $cr=$val->amt;
                }
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where avuuid={$avuuid} AND currency='{$currency_name}' AND itype='debit'";
                $dbts=0;
                $debits= get_records_sql($sql);
                foreach ($debits as $key =>$val){
                    $dbts=(int)$val->amt;
                }
                $balance=(int)($cr)-(int)$dbts;
                return $balance;
            }
            else return null; //never provided userid or avuuid
        }
        function addTransaction($userid=null,$avname=null,$avuuid=null,$gameid=null,$currency_name="Credits",$amount,$idata=null,$sloodleid=null){
            global $USER,$COURSE,$CFG; 
            $t= new stdClass();
            $t->sloodleid=$sloodleid;            
            $t->gameid=$gameid;                  
            $t->avuuid=$avuuid;                  
            $t->userid=$userid;            
            $t->avname=$avname;
            $t->currency=$currency_name;
            if ($amount<0)$t->itype="debit";
            if ($amount>=0)$t->itype="credit";
            $t->amount=$amount;
            $t->idata=$idata;
            $t->timemodified=time();            

            if (insert_record('sloodle_award_trans',$t)) {
            $balance    = $this->get_balance($currency_name,$userid,$avuuid);
                if ($balance<0){
                  $t->amount=  $balance*-1;
                  $t->itype="credit";
                  $t->idata="DETAILS:System Modified Balance adjustment"; 
                  insert_record('sloodle_award_trans',$t); 
                }//endif
                return true;
            }
            else return false;             
        }//addTransaction
}
?>
