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
        function sendUrl($url,$post){         
             $ch = curl_init(); 
             //curl_setopt($ch, CURLOPT_URL, 'http://sim5468.agni.lindenlab.com:12046/cap/48c6c5fc-f19d-4dc2-6a50-fc3566186508'); 
             // FIND BOOKS ON PHP AND MYSQL ON AMAZON 
            $ch = curl_init();    // initialize curl handle 
            curl_setopt($ch, CURLOPT_URL,$url); // set url to post to 
            curl_setopt($ch, CURLOPT_FAILONERROR, 1); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable 
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s 
            curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
             curl_setopt($ch, CURLOPT_POSTFIELDS,$post); // add POST fields        
            $result = curl_exec($ch); // run the whole process 
            curl_close($ch);   
             return $result;          
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
        function refreshScoreboard($sloodleid){
            $scoreboards = get_records('sloodle_award_scoreboards','sloodleid',$sloodleid);
               
            if (!empty($scoreboards)){
                foreach ($scoreboards as $sb){
                       
                     $expiry = time()-$sb->timemodified;
                      
                     if ($expiry>60*60*48){
                        //this is url is a week old, delete it because the inworld scoreboards 
                        //update their URL atleast once a week
                        delete_records('sloodle_award_scoreboards','sloodleid',$sb->sloodleid);
                         
                    }
                    //get current display of each scoreboard
                    $displayData = $this->sendUrl($sb->url,"COMMAND:GET DISPLAY DATA\n");
                   
                    $dataLines = explode("\n", $displayData);
                    if ($displayData!=FALSE){
                          
                        $currentView = $this->getFieldData($dataLines[0]);
                         
                        if ($currentView=="Top Scores"||$currentView=="Sort by Name"){
                            $result = $this->sendUrl($sb->url,"COMMAND:UPDATE DISPLAY\n".$updateString);
                        }
                    }
                }//foreach scoreboard
            }//endif $scoreboards
     }
        function get_currency_types(){
            global $CFG;
                $currencyTypes= get_records_sql("select name,units FROM {$CFG->prefix}sloodle_currency_types ORDER BY 'name' ASC ");
                return $currencyTypes;
        }
        function get_transactions($avname=null,$currency){
            global $CFG;
            if ($avname!=null){
                $sql= "select t.avname, t.userid, t.currency,t.amount,t.itype,t.amount,t.timemodified, c.units from {$CFG->prefix}sloodle_award_trans t INNER JOIN {$CFG->prefix}sloodle_currency_types c ON c.name = t.currency where t.currency='{$currency}' AND t.avname='{$avname}' ORDER BY t.timemodified DESC"; //gets a particular currency
                //$sql= "select t.id, t.avname,t.amount,t.currency,t.itype,sum(case t.itype when 'debit' then cast(t.amount*-1 as signed) else t.amount end) as amount, c.units from {$CFG->prefix}sloodle_award_trans t INNER JOIN {$CFG->prefix}sloodle_currency_types c ON c.name = t.currency AND t.avname='{$avname}' GROUP BY c.name";
               
                $trans = get_records_sql($sql);    
                 
                    return $trans;          
            }else{
                $sql= "select t.*, sum(case t.itype when 'debit' then cast(t.amount*-1 as signed) else t.amount end) as amount, c.units from {$CFG->prefix}sloodle_award_trans t INNER JOIN {$CFG->prefix}sloodle_currency_types c ON c.name = t.currency where t.currency='{$currency}' GROUP BY t.avname ORDER BY t.amount DESC";
               
                $trans = get_records_sql($sql);    
                return $trans;          
                
            }
              
            
          
        }
         
        function get_balance($currency_name,$userid=null,$avuuid=null,$gameid=null){
               global $CFG;
            //$currency = get_record('sloodle_currency_types','name',$currency_name);            
            //if (!$currency) return null;//currency doesnt exist
            $gameid_str="";
            if (!empty($gameid))$gameid_str=" AND gameid={$gameid}";
            if ($userid){
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where userid={$userid} AND currency='{$currency_name}' AND itype='credit'".$gameid_str;
              
                $credits= get_records_sql($sql);
                $cr=0;
                foreach ($credits as $key =>$val){
                    $cr=$val->amt;
                }
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where userid={$userid} AND currency='{$currency_name}' AND itype='debit'".$gameid_str;
               
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
                  $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where avuuid={$avuuid} AND currency='{$currency_name}' AND itype='credit'".$gameid_str;
                $credits= get_records_sql($sql);
                $cr=0;
                foreach ($credits as $key =>$val){
                    $cr=$val->amt;
                }
                $sql = "select SUM(amount) as amt from {$CFG->prefix}sloodle_award_trans where avuuid={$avuuid} AND currency='{$currency_name}' AND itype='debit'".$gameid_str;
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
            $t->gameid=(int)$gameid;                  
            $t->avuuid=$avuuid;                  
            $t->userid=(int)$userid;            
            $t->avname=$avname;
            $t->currency=$currency_name;
            
            if ((int)$amount<0){
                $t->itype="debit";                
            }else
            if ((int)$amount>=0)$t->itype="credit";
            $t->amount=abs((int)$amount);    
            $t->idata=$idata;
            $t->timemodified=time();            

            if (insert_record('sloodle_award_trans',$t)) {
            
            $balance    = $this->get_balance($currency_name,$userid,$avuuid,$gameid);
              
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
