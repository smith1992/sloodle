<?php
/**
* Defines a class for viewing the SLOODLE Awards module in Moodle.
* Derived from the module view base class.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @see http://slisweb.sjsu.edu/sl/index.php/Sloodle_Stipend_Giver
*
* @contributer Paul G. Preibisch - aka Fire Centaur 
*/

/** The base module view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');
       
 /** SLOODLE course data structure */
require_once(SLOODLE_LIBROOT.'/course.php');

/** SLOODLE course object data structure */
require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
/** SLOODLE course object data structure */

/** SLOODLE awards object data structure */
require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');
/** Sloodle Session code. */
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');

/** General Sloodle functionality. */
require_once(SLOODLE_LIBROOT.'/general.php');   
               
/**
* Class for rendering a view of a Distributor module in Moodle.
* @package sloodle
*/
class sloodle_view_awards extends sloodle_base_view_module
{
    
    /**
    * SLOODLE course object, retrieved directly from database.
    * @var object
    * @access private
    */
    var $sloodleCourse = null;
    
    var $renderMessage=null;
    
    /**
    * The result number to start displaying from
    * @var integer
    * @access private
    */
    var $start = 0;  
    
    /**
    * A List of records of the transactions for this stipend
    * @var Array
    * @access private
    */   
    var $transactionRecords = null;
    /**
    * A List of all users avatar UUID's who have collected a stipend
    * @var Array
    * @access private
    */     
    
    var $stipendCollectors_uuid= null;

    /**
    * A List of usrs moodle id's who have collected a stipend
    * @var Array
    * @access private
    */     
    var $stipendCollectors_moodleid= null;
    
    
   /**
   * A List of all the students in this course
   * @var Array
   * @access private
   */ 
   var $userList= null;
    
   var $initialized=false;        
       /**
    * sloodleId The instance of this moodle module
    * @var bigInt(10)
    * @access private
    */  
       
   var $sloodleId = null;
   /**
    * session is a dummy session object to be used as a parameter when creating a sloodleUser object
    * @var SloodleSession Object
    * @access private
    */  
       
   var $session= null;
   /**
    * @var $iPb a pointer to the iPoint bank object base class;  
    */
   var $iPb = null;
   /**
    * @var $sCourseObj - a pointer to the sloodleCourseObject with useful functions to access course data
    */   
   var $sCourseObj = null;
   /**
    * @var $iPts are the number of iPoints
    *  if iPoints were selected as icurrency
    */
   var $iPts = null;
   
   var $showInitForm = false;
   /**
    * @var $icurrency - currency of this stopend - can be Lindens, or iPoints
    */
   var $icurrency; 
   /**
    * @var $awardsObj - a pointer to the awardsObject with useful functions to access the awards
    */   
   var $awardsObj = null;
   
     /**
    * The course module instance, retrieved directly from the database (table: course_modules)
    * @var object
    * @access private
    */
    var $cm = null;

    /**
    * The main SLOODLE module instance, retreived directly from the database (table: sloodle)
    * @var object
    * @access private
    */
    var $sloodleRec = null;

    /**
    * The VLE course object, retrieved directly from the database (table: course)
    * @var object
    * @access private
    */
    var $course = null;

    /**
    * The SLOODLE course object.
    * @var SloodleCourse
    * @access private
    */
    var $sloodle_course = null;

    /**
    * Context object for permissions in the Moodle course.
    * @var object
    * @access private
    */
    var $course_context = null;

    /**
    * Context object for permissions in the Moodle module.
    * @var object
    * @access private
    */
   /**
    * Constructor.
    * This constructor creates a sloodleCourseObject which gives us useful functions to access course data 
    * Also creats a awards Object which gives us useful functions to access awards data
    * 
    */
   
    
    function sloodle_view_awards()    
    {
        global $sCourseObj,$awardsObj;
        
            $sloodleid = required_param('id', PARAM_INT);   
             
             //set Sloodle Course Obj - this object will give us things like: userlist of the course, sloodle id etc.
            $sCourseObj = new sloodleCourseObj($sloodleid);
            $this->sloodleRec= $sCourseObj->sloodleRec;
            $this->cm = $sCourseObj->cm;
            $this->course = $sCourseObj->courseRec;
            $this->course_context = $sCourseObj->courseContext;
            $awardsObj = new Awards($sCourseObj->cm->instance);
    }
    /**
    * Check that the user has permission to view this module, and check if they can edit it too.
    */
    
    function check_permission()
    {
        global $sCourseObj;
        // Make sure the user is logged-in
        require_course_login($this->course, true, $this->cm);
        add_to_log($sCourseObj->courseId, 'sloodle', 'view sloodle module', "view.php?id={$sCourseObj->cm->id}", "{$this->sloodleRec->id}", $sCourseObj->cm->id);
        
        // Check for permissions
        $this->module_context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        $this->course_context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        if (has_capability('moodle/course:manageactivities', $this->module_context)) $this->canedit = true;

        // If the module is hidden, then can the user still view it?
        if (empty($this->cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $this->module_context)) notice(get_string('activityiscurrentlyhidden'));
    }

    /**
    * Check and process the request parameters.
    */
    function process_request()
    {
        global $CFG, $USER;
        global $sCourseObj;
        global $awardsObj;
    
        //====================== Get all course related information
           //coursemodule id
            $sloodleid = required_param('id', PARAM_INT);      
            $this->start = optional_param('start', 0, PARAM_INT);
            $sloodleId =  $sCourseObj->cm->instance;          
            //get awards Object (Awards Object)
            $awardsObj = new Awards((int)$sloodleId);
            if ($this->start < 0) $this->start = 0;
            //get icurrency type of points
            $this->icurrency= $awardsObj->icurrency;
            //get users in this course
            $this->userList = $sCourseObj->getUserList(); 

            
    }
   function creditAll($amount){
       global $awardsObj,$sCourseObj;
       foreach ($this->sCourseObj->getUserList() as $u){ 
            //Ipoints can be various types - ie: real lindens, or just points.                         
            $iTransaction = new stdClass();
            $iTransaction->userid       = (int)$u->id;
            $iTransaction->sloodleid    = (int)$this->sloodleId;
            $iTransaction->icurrency    = (string)$this->icurrency;
            $iTransaction->amount       = (int)$amount;
            $iTransaction->itype = "credit";
            $iTransaction->timemodified = (int)time();
            $awardsObj->makeTransaction($iTransaction,$sCourseObj);
       }   
        $awards_xmlchannel = $awardsObj->sloodle_awards_instance->xmlchannel;
        do_xml_post($awards_xmlchannel,"1","UPDATE");
   } 
    
    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {        
        global $awardsObj;
        global $sCourseObj;
        global $USER;
        //============== - CHECK TO SEE IF THERE ARE PENDING UPDATES TO EXISTING STUDENTS
           //check if we need to update allocations 
           //if "update" param exists, that means someone submitted a form using the update button in printUserTable function
           $update= optional_param("update");
            if ($update){                
                $balance_updates=optional_param("balanceAdjustment");
                //get userId's that were posted
                $userIds = optional_param("userIds");
                //get user names that were posted
                $userNames = optional_param("usernames");
                $currIndex=0;   
                $updatedRecs = Array();
                $errorString=''; 
                //go through each userId posted, and check each update field.  If it's a non-zero
                //then we must make a transaction for this user
                foreach ($userIds as $userId) {
                    //Was a non-zero value entered in the balance_update field for this user?
                    if ($balance_updates[$currIndex]!=0){
                        //build a new transaction record for the sloodle_award_trans table
                            //build sloodle_user object for this user id
                            $sloodle = new SloodleSession( false );
                            $avuser = new SloodleUser( $sloodle ); 
                            $userRec = get_record('sloodle_users', 'userid', $userId);  
                            $trans = new stdClass();
                            $trans->sloodleid=$awardsObj->sloodleId;
                            $trans->avuuid= $userRec->uuid;        
                            if ($balance_updates[$currIndex]>0)$trans->itype='credit'; else 
                            if ($balance_updates[$currIndex]<0){
                                $trans->itype='debit';
                                //check to see if this debit will make a negative amount
                                $userAccountInfo = $awardsObj->awards_getBalanceDetails($userRec->userid);
                                if (($userAccountInfo->balance - abs($balance_updates[$currIndex]))<0){
                                    $balance_updates[$currIndex]= $userAccountInfo->balance;
                                }
                            }
                            $trans->amount=abs($balance_updates[$currIndex]);
                            $trans->userid = $userId; 
                            $trans->avname= $userRec->avname; 
                            $trans->idata="DETAILS:webupdate|by moodle user:".$USER->username;
                            $trans->timemodified=time();       
                            $awardsObj->awards_makeTransaction($trans,$sCourseObj);                        
                    }
                    $currIndex++;        
                }
                //create and print confirmation message to the user
                $confirmedMessage = get_string("awards:successfullupdate","sloodle");
                $confirmedMessage .= $this->addCommas($updatedRecs);
                //send confirmation Message
                $this->setRenderMessage($confirmedMessage . $errorString);
            }
       } 
      /**
    * Override the base_view_module print_header for formatting reasons 
    */
    
     function print_header(){             
        global $CFG,$sCourseObj;
        // Offer the user an 'update' button if they are allowed to edit the module
        $editbuttons = '';
        if ($this->canedit) {
            $editbuttons = update_module_button($this->cm->id, $this->course->id, get_string('modulename', 'sloodle'));
        }
        // Display the header: Sloodle with edit buttons
        $navigation = "<a href=\"index.php?{$this->course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
        $courseName=$sCourseObj->sloodleRec->name;
        print_header_simple(format_string($courseName), "", "{$navigation} ".format_string($courseName, "", "", true, $editbuttons, navmenu($this->course, $this->cm)));
        // Display the module name: Sloodle awards
       
        
    
        // Display the module type and description
        $fulltypename = get_string("moduletype:{$sCourseObj->sloodleRec->type}", 'sloodle');
        echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
        echo helpbutton("moduletype_{$sCourseObj->sloodleRec->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    }   
 
 /**
 * printUserTable function
 * @desc This function will display an HTML table of the users transactions
 * Columns displayed will be:  UserName | Avatar  |  Amount Alloted  |  Balance Remaining   
 * @staticvar null
 * @param $userData - an array of users
 * @link http://sloodle.googlecode.com
 * @return null 
 */ 
    function printUserTable($userData){        
        global $CFG;
        global $USER;
        global $sCourseObj;
        global $awardsObj;

        
         //===================== Build table with headers, set alignment and width of cells
        //Create HTML table object
        $sloodletable = new stdClass();
        $sloodletable->tablealign='center';
        //Row Data
        
        //Create Sloodle Table Column Labels
        //User | Avatar  |  Amount Alloted  |  Balance Remaining
        if ($this->icurrency=="Lindens"){      
                         $allotedString = get_string('awards:alloted', 'sloodle');   
                     }else if ($this->icurrency=="iPoints"){
                        $allotedString = get_string('awards:iPoints', 'sloodle');    
                     }
         $context = get_context_instance(CONTEXT_MODULE, $sCourseObj->cm->id);          
          if (has_capability('moodle/course:manageactivities',$context, $USER->id)) {                 
            $updateString =' <input type="submit"';
            $updateString .=' name="update" ';
            $updateString .='  value="'.get_string("awards:update","sloodle") .'">';      
        }else {$updateString = '';}
             $totals = $awardsObj->getTotals();   
            $totalbalances =  $totals->totalbalances;         
            $totalcredits = $totals->totalcredits;
            $totaldebits = $totals->totaldebits;
//            $totalusers =  $totals->totalusers;
            $sloodletable->head = array(
             get_string('awards:fullname', 'sloodle'),
             get_string('awards:avname', 'sloodle'),             
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:credits', 'sloodle').'<br>('.$totalcredits.')'.'</h4>',
             '<h4><div style="color:red;text-align:center;">'.get_string('awards:debits', 'sloodle').'<br>('.$totaldebits.')'.'</h4>',
             '<h4><div style="color:green;text-align:center;">'.get_string('awards:balance', 'sloodle').'<br>('.$totalbalances.')'.'</h4>',
             $updateString);
        //set alignment of table cells                                        
        $sloodletable->align = array('left', 'left','right','right','right');
        //set size of table cells
        $sloodletable->size = array('15%','35%', '5%','5%','10%');
        
        $avs='';
        $debits='';
        $checkBoxId=0; 
        if (!empty($userData)){
            foreach ($userData as $u){
                 //==========print hidden user id for form processing
                $userIdFormElement = '<input type="hidden" name="userIds[]" value="'.$u->id.'">';
                // Get the Sloodle user data for this Moodle user
               
                if ($sCourseObj->is_teacher($USER->id))
                  $editField = '<input style="text-align:right;" type="text" size="6" name="balanceAdjustment[]" value=0>';
                  else $editField ='';
                //build row
                //col 0: fullname & link to profile
                //col 1: avatar
                //col 2: balance
                //col 3: updateAmount
                //col 4: transaction link
                $rowData= Array();  
                // Construct URLs to this user's Moodle and SLOODLE profile pages
                $url_moodleprofile= $sCourseObj->get_moodleUserProfile($u);
                $rowData[]= $userIdFormElement . "<a href=\"{$url_moodleprofile}\">{$u->firstname} {$u->lastname}</a>";
                //create a url to the transaction list of each avatar the user owns
                
                 $ownedAvatars = get_records('sloodle_users', 'userid', $u->id,'avname DESC','userid,uuid,avname');                        
                if ($ownedAvatars){
                    $trans_url ='';   
                    foreach ($ownedAvatars as $av){
                       $trans_url.='<a href="'.$CFG->wwwroot.'/mod/sloodle/view.php?id=';
                       $trans_url.=$sCourseObj->cm->id.'&';
                       $trans_url.='action=gettrans&userid='.$av->userid.'">';
                       $trans_url.=$av->avname;
                       $rowData[]=$trans_url;
                       $trans_details = $awardsObj->awards_getBalanceDetails($av->userid);
                       if (!$trans_details) {
                           $credits=0; $debits=0;$balance=0;
                       }else{
                           $credits=$trans_details->credits;
                           $debits=$trans_details->debits;
                           $balance=$trans_details->balance;
                       }
                       $rowData[]='<div style="color:black;text-align:center;">'.$credits.'</div>';
                       $rowData[]='<div style="color:red;text-align:center;">'.$debits.'</div>';
                       $rowData[]='<div style="color:green;text-align:center;">'.$balance.'</div>';
                       $rowData[]=$editField;
                    }
                    
                   
                    $sloodletable->data[] = $rowData;
                    
                }
            }
            print ('<form action="" method="POST">');
            print_table($sloodletable);  
            print('</form>');  
        }  
    }  
   
 /**
 * printTransTable function
 * @desc This function will display an HTML table of a single users transactions
 * @staticvar null
 * @param $userData - an array of users
 * @link http://sloodle.googlecode.com
 * @return null 
 */ 
    function printTransTable($userid){        
        global $CFG;
        global $USER;
        global $sCourseObj;
        global $awardsObj;
        
         $context = get_context_instance(CONTEXT_MODULE, $sCourseObj->cm->id);          
         $permissions = has_capability('moodle/course:manageactivities',$context, $USER->id);
      
        //get sloodle_record
        $userRec = get_record('sloodle_users', 'userid', $userid);  
        //build table
        print("<div align='center'>");
        $sloodletable = new stdClass();            
        $sloodletable->tablealign='center';
        //build row
        $rowData=Array();
        $text='<h2><div style="color:black;text-align:center;">'.get_string('awards:usertransactions','sloodle').$userRec->avname.'</div></h2>';
        $text.='<div style="color:black;text-align:center;">'.$sCourseObj->sloodleRec->name.'<br>';
        $text.='<a href="'.$CFG->wwwroot.'/mod/sloodle/view.php?id='.$sCourseObj->cm->id.'">'.get_string('awards:goback','sloodle').'</a></div>';
        $rowData[]=$text;        
        $sloodletable->data[]=$rowData;            
        print_table($sloodletable); 
              
        $totals = $awardsObj->awards_getBalanceDetails($userid);   
            if ($totals->balance==NULL)$totalbalances =0;
                else $totalbalances =  $totals->balance;         
            if ($totals->credits==NULL)$totalcredits=0;
                else $totalcredits = $totals->credits;
            if ($totals->debits==NULL) $totaldebits = 0;
            else $totaldebits = $totals->debits;
            
        $userRec = get_record('sloodle_users', 'userid', $userid);  
        $avName = $userRec->avname; 
        
       $tsloodletable = new stdClass(); 
        //create transactions table
        if ($permissions) {
            $tsloodletable->head = array(             
             '<h4><div style="color:black;text-align:center;">'.get_string('ID', 'sloodle').'<br></h4>',
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:details', 'sloodle').'<br></h4>',
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:credits', 'sloodle').'<br>('.$totaldebits.')'.'</h4>',
             '<h4><div style="color:red;text-align:center;">'.get_string('awards:debits', 'sloodle').'<br>('.$totaldebits.')'.'</h4>',
             '<h4><div style="color:green;text-align:center;">'.get_string('awards:balance', 'sloodle').'<br>('.$totalbalances.')'.'</h4>',             
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:date', 'sloodle').'</h4>');
             //set alignment of table cells                                        
            $tsloodletable->align = array('left','left', 'right','right','right','left');
            $tsloodletable->width="95%";
            //set size of table cells
            $tsloodletable->size = array('5%','40%','10%', '10%','10%','35%');
        } else {
            $tsloodletable->head = array(             
             '<h4><div style="color:black;text-align:center;">'.get_string('ID', 'sloodle').'<br></h4>',
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:credits', 'sloodle').'<br>('.$totaldebits.')'.'</h4>',
             '<h4><div style="color:red;text-align:center;">'.get_string('awards:debits', 'sloodle').'<br>('.$totaldebits.')'.'</h4>',
             '<h4><div style="color:green;text-align:center;">'.get_string('awards:balance', 'sloodle').'<br>('.$totalbalances.')'.'</h4>',             
             '<h4><div style="color:black;text-align:center;">'.get_string('awards:date', 'sloodle').'</h4>');             
              //set alignment of table cells                                        
            $tsloodletable->align = array('left','right','right','right','left');
            $tsloodletable->width="95%";
            //set size of table cells
            $tsloodletable->size = array('5%','10%', '10%','10%','25%');
        }
        //get all users transactions
        $transactions = $awardsObj->awards_getTransactionRecords($userid);
        if (!empty($transactions)){
            $balance=0;
            foreach ($transactions as $t){
               $trowData= Array();        
               $trowData[]=$t->id;
               if ($permissions) {                 
                $trowData[]=$t->idata;         
               }
               if ($t->itype=='credit') { 
                   $balance+=$t->amount;
                   $trowData[]='<div style="color:black;text-align:center;">'.$t->amount.'</div>'; 
                   $trowData[]='';
               }else
               if ($t->itype=='debit') { 
                   $balance-=$t->amount;
                   $trowData[]='';
                   $trowData[]='<div style="color:black;text-align:center;">'.$t->amount.'</div>';                    
               }               
               $trowData[]='<div style="color:green;text-align:center;">'.$balance.'</div>';
               
               $trowData[]=date("D M j G:i:s T Y",$t->timemodified);
               $tsloodletable->data[] = $trowData;     
            }
            
            }                        
            print_table($tsloodletable);  
            print("</div>"); 
        }    
   
            
      
    /**
    * Render the view of the Stipend Giver.
    */
    function render()              
    {
        global $CFG, $USER,$sCourseObj,$awardsObj;   
        $this->courseid = $sCourseObj->courseId;
        $sloodleid=$awardsObj->sloodleId;
        $action = optional_param('action');  
        switch ($action){
         case "gettrans":
            //get user id
            $userid = optional_param('userid');           
            $this->printTransTable($userid);
            
         break;
         default: 
         // Print a Table Intro
            print('<div align="center">');
          
                $iTable = new stdClass();
                $iRow = array();
                $totals = $awardsObj->getTotals();   
                $totalbalances =  $totals->totalbalances;         
                 if ($totals->totalcredits==NULL) $totalcredits=0; else
                    $totalcredits = $totals->totalcredits;
                if ($totals->totaldebits==NULL) $totaldebits = 0; else
                $totaldebits = $totals->totaldebits;
//                $totalusers =  $totals->totalusers;
                $sloodletable = new stdClass();
                $sloodletable->tablealign='center';
                
                $img = '<img src="'.$CFG->wwwroot.'/mod/sloodle/icon.gif" width="16" height="16" alt=""/> ';
                $rowData=Array();
                $rowData[]=get_string('awards:course','sloodle'). $img.$sCourseObj->courseRec->fullname;
                $sloodletable->data[]=$rowData;
                $rowData=Array();
                $rowData[]='<h2><div style="color:blue;text-align:center;">'.$sCourseObj->sloodleRec->name.'<h2>';
                $sloodletable->data[]=$rowData;
                
                print_table($sloodletable); 
                 
                
            //==================================================================================================================
               
           if ($this->getRenderMessage()){
            print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
                print ('<h1 style="color:red;text-align:center;">'.$this->getRenderMessage().'</div>');
            print_box_end();
            
           }
           print('</div>'); 
            //======Print STUDENT TRANSACTIONS
              
            if ($sCourseObj->getUserList()){
              print("<div align='center'>");
                $this->printUserTable($sCourseObj->getUserList());
                 print("</div>"); 
            }
           
            else {
                
                print('<div style="text-align:center;">');
                print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
                print ('<h1 style="color:red;text-align:center;">'.get_string('awards:nostudents','sloodle').'</div>');
                print_box_end();
                print('</div>');                
            }
            
        //==================================================================================================================
           
             //  $this->destroy();        
        
       }
    }
    



 
 
  /**
 * addCommas takes an array of strings and add commas between elements except at the last element, or if there is only one element
 * @param array $arrList
 * @return string
 */ 
 function addCommas($arrList){
     $runTwice=false;  //for the comma to print correctly
     $newList = '';
     foreach ($arrList as $arr){
         
         if ($runTwice) $newList.=",";
         $runTwice = true;
         $newList .=$arr; 
     }
     return $newList;
 
 
 }  
  
 /**
 * buildAvTable function
 * @desc Simply sets up the inner avatar table, alignment, and sizes 
 * @staticvar object $avTable
 * @param null
 * @link http://sloodle.googlecode.com
 * @return object 
 */ 
 function buildAvTable(){
 //create inter avatar table
        $avTable = new stdClass();        
        $avTable->align = array('left','right');
        $avTable->size = array('70%','30%');
        $avTable->data = null;
   return $avTable;
 
 }  
 
 function setRenderMessage($str){
    $this->renderMessage=$str;
    
 }
 function getRenderMessage(){
    return $this->renderMessage;
 }
        
}

          
?>
