<?php
/**
* Defines a class for viewing the SLOODLE iBank module in Moodle.
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
require_once(SLOODLE_LIBROOT.'/ipointbank_object.php');
/** SLOODLE stipendgiver object data structure */
require_once(SLOODLE_DIRROOT.'/mod/iBank-1.0/stipendgiver_object.php');
/** Sloodle Session code. */
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');

/** General Sloodle functionality. */
require_once(SLOODLE_LIBROOT.'/general.php');   
               
/**
* Class for rendering a view of a Distributor module in Moodle.
* @package sloodle
*/
class sloodle_view_iBank extends sloodle_base_view_module
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
    * @var $stipendGiverObj - a pointer to the stipendGiverObject with useful functions to access the stipendGiver
    */   
   var $stipendGiverObj = null;
   
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
    var $sloodle = null;

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
    * Also creats a stipendGiver Object which gives us useful functions to access stipendGiver data
    * 
    */
   
    
    function __construct()    
    {
            
             $sloodleid = required_param('id', PARAM_INT);     
             //set Sloodle Course Obj - this object will give us things like: userlist of the course, sloodle id etc.
            $this->sCourseObj = new sloodleCourseObj($sloodleid);
            $this->sloodle= $this->sCourseObj->sloodleRec;

            $this->cm = $this->sCourseObj->cm;
            $this->course = $this->sCourseObj->courseRec;
            $this->course_context = $this->sCourseObj->courseContext;
            
    }
    /**
    * Check that the user has permission to view this module, and check if they can edit it too.
    */
    function check_permission()
    {
        // Make sure the user is logged-in
        require_course_login($this->course, true, $this->cm);

        add_to_log($this->course->id, 'sloodle', 'view sloodle module', "view.php?id={$this->cm->id}", "{$this->sloodle->id}", $this->cm->id);
        
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
    
    
        //====================== Get all course related information
           //coursemodule id
            $sloodleid = required_param('id', PARAM_INT);      
            $this->start = optional_param('start', 0, PARAM_INT);
            $this->sloodleId = $this->sCourseObj->getSloodleId();           
            //get stipendGiver Object (iBank Object)
            $this->stipendGiverObj = new stipendGiverObject($this->sloodleId);
            if ($this->start < 0) $this->start = 0;
            //get icurrency type of points
            $this->icurrency= $this->stipendGiverObj->geticurrency();
            //get users in this course
            $this->userList = $this->sCourseObj->getUserList(); 

            
    }
   
    
    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {        
        //STEP 1 - HAS THE DEFAULT STIPEND AMOUNT BEEN ALLOCATED TO EVERY USER?       
        //======================== creates initial default stipend records for all users 
        //if no transactions exist        

        if (!$this->stipendGiverObj->getTransactionRecords()) {
              //create stipend transactions for each student!
              //get student list
               
               //for each user in the class insert a transaction record with following values:
               //avuuid,userid,avname,amount,type,timemodified
             
             foreach ($this->sCourseObj->getUserList() as $u){ 
             //Ipoints can be various types - ie: real lindens, or just points.                         
                    $iTransaction = new stdClass();
                    $iTransaction->userid       = $u->id;
                    $iTransaction->sloodleid    = $this->stipendGiverObj->getSloodleId();                 
                    $iTransaction->icurrency     = $this->stipendGiverObj->geticurrency();
                    $iTransaction->amount       = $this->stipendGiverObj->getStartingBalance();
                    $iTransaction->iType = "stipend";
                    $iTransaction->timemodified = time();
                    $this->stipendGiverObj->makeTransaction($iTransaction);
             }    
           //insert iTransaction into db            
           }
              
        
       //STEP 2 - Transactions exist for this stipend already, check to see if new students have been added
       //========================= HAVE NEW STUDENTS BEEN ADDED 
       else 
       {
           $this->stipendGiverObj->updateUserList($this->sCourseObj->getUserList());
        }
        //============== STEP 3 - CHECK TO SEE IF THERE ARE PENDING UPDATES TO EXISTING STUDENTS
           //check if we need to update allocations 
           //if "update" param exists, that means someone submitted a form using the update butotn in printUserTable function
           $update= optional_param("update");
            if ($update){
                
                //check each allotment value from form, and compare against db. change where necessary
                $newBudgets=optional_param("newbudgets");
                $userIds = optional_param("userIds");
                $userNames = optional_param("usernames");
                $currIndex=0;   
                $updatedRecs = Array();
                $errorString=''; 
                foreach ($userIds as $userId) {
                    //retreive saved budget for this user from the database
                    $savedBudget = $this->stipendGiverObj->getStipendBudget($userId,$this->userList);                    
                    //compare saved budget with the budget submitted in the form field for this user
                   
                    if ((int)$newBudgets[$currIndex] !==$savedBudget){
                        //The user has submited a new stipend allotment for this user, because the new budget 
                        //from the form differs from what has been saved in the db.
                        //so we must update the alloted amount in db
                        //STEP 1 First we should check if the user has already withdrawn any stipends
                        $withdrawnAmount = 0;
                        $withdrawnAmount = $this->stipendGiverObj->getUserDebits($userId,$this->userList);
                        if ($withdrawnAmount > $newBudgets[$currIndex]){
                            $errorString = "<br>".$userNames[$currIndex]." ".get_string('stipendgiver:alreadywd','stipendgiver') . $withdrawnAmount;
                            $errorString =  get_string('stipendgiver:alreadywd2','stipendgiver');
                        }else{
                                //STEP 2 - get id of transaction to update
                                $stipendRec = $this->stipendGiverObj->getStipendRec($userId);
                               
                                $transactionUpdate = new stdClass();
                                $transactionUpdate->id=$stipendRec->id;
                                $transactionUpdate->amount=$newBudgets[$currIndex];                            
                                $transactionUpdate->timemodified= time();
                                $this->stipendGiverObj->updateTransaction($transactionUpdate);
                                $updatedRecs[]=$userNames[$currIndex];
                                
                        }
                    }
                    $currIndex++;     
                    
                }
                //create and print confirmation message to the user
                $confirmedMessage = get_string("stipendgiver:successfullupdate","sloodle");
                $confirmedMessage .= $this->addCommas($updatedRecs);
                
                //send confirmation Message
                $this->setRenderMessage($confirmedMessage . $errorString);
                //updater stipendgier ibank
                $this->stipendGiverObj->update();
            }
       
       } 
    


      /**
    * Override the base_view_module print_header for formatting reasons 
    */
    
     function print_header()
    {             
        global $CFG;

        // Offer the user an 'update' button if they are allowed to edit the module
        $editbuttons = '';
        if ($this->canedit) {
            $editbuttons = update_module_button($this->cm->id, $this->course->id, get_string('modulename', 'sloodle'));
        }
        // Display the header: Sloodle with edit buttons
        $navigation = "<a href=\"index.php?{$this->course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
        print_header_simple(format_string($this->sloodle->name), "", "{$navigation} ".format_string($this->sloodle->name, "", "", true, $editbuttons, navmenu($this->course, $this->cm)));

        // Display the module name: Sloodle StipendGiver
        $img = '<img src="'.$CFG->wwwroot.'/mod/sloodle/icon.gif" width="16" height="16" alt=""/> ';
        print_heading($img.$this->sloodle->name, 'center');
    
        // Display the module type and description
        $fulltypename = get_string("moduletype:{$this->sloodle->type}", 'sloodle');
        echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
        echo helpbutton("moduletype_{$this->sloodle->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    
         
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
        
        
         //===================== Build table with headers, set alignment and width of cells
        //Create HTML table object
        $sloodletable = new stdClass();
        //Row Data
        
        //Create Sloodle Table Column Labels
        //User | Avatar  |  Amount Alloted  |  Balance Remaining
        if ($this->icurrency=="Lindens"){      
                         $allotedString = get_string('stipendgiver:alloted', 'sloodle');   
                     }else if ($this->icurrency=="iPoints"){
                        $allotedString = get_string('stipendgiver:iPoints', 'sloodle');    
                         
                     }
            
        if ($this->sCourseObj->is_teacher($USER->id)){        
            $allotedString .=' <input type="submit"';
            $allotedString .=' name="update" ';
            $allotedString .='  value="'.get_string("stipendgiver:update","sloodle") .'">';      
        }
        if ($this->icurrency=="Lindens"){
        $sloodletable->head = array('',
                                    get_string('stipendgiver:username', 'sloodle'),
                                    get_string('user', 'sloodle'),  
                                    $allotedString,
                                    get_string('stipendgiver:avatars', 'sloodle'),   
                                    get_string('stipendgiver:debits', 'sloodle'),   
                                    get_string('stipendgiver:balance', 'sloodle'));
        }else if ($this->icurrency=="iPoints"){
        $sloodletable->head = array('',
                                    get_string('stipendgiver:username', 'sloodle'),
                                    get_string('user', 'sloodle'),  
                                    $allotedString,
                                    get_string('stipendgiver:avatars', 'sloodle'),   
                                    
                                    get_string('stipendgiver:totalipoints', 'sloodle'));
        }
        //set alignment of table cells                                        
        $sloodletable->align = array('left', 'left', 'center', 'left','right','center');
        //set size of table cells
        $sloodletable->size = array('2%','30%','15%', '5%','45%','3%');
        $avs='';
        $debits='';
        
        $checkBoxId=0; 
        if (!empty($userData)){
                        
            foreach ($userData as $u){
                 $rowData= Array();  
                
                // Construct URLs to this user's Moodle and SLOODLE profile pages
                $url_moodleprofile = $this->sCourseObj->get_moodleUserProfile($u);
                $url_sloodleprofile = $this->sCourseObj->get_sloodleprofile($u);
                //==========print check box
                //if ($this->sCourseObj->is_teacher($USER->id)){  
               //     $rowData[]= '<input type="checkbox" id="cb'.$checkBoxId++.' name="cid[]" value="'.$u->id.'" checked>';               
               // }      else
                $rowData[]='';
                 //==========print hidden user id for form processing
                $userIdFormElement = '<input type="hidden" name="userIds[]" value="'.$u->id.'">';
                $userIdFormElement .= '<input type="hidden" name="usernames[]" value="'.$u->username.'">';
                //========= print url link to Moodle name
                $rowData[]= $userIdFormElement . "<a href=\"{$url_moodleprofile}\">{$u->firstname} {$u->lastname}</a>";
                $rowData[]=  "<a href=\"{$url_moodleprofile}\">{$u->username}</a>"; 
                // Construct URLs to this user's Moodle and SLOODLE profile pages     
                // Get the Sloodle data for this Moodle user
                $ownedAvatars = null;;
                $ownedAvatars = get_records('sloodle_users', 'userid', $u->id,'avname DESC','userid,uuid,avname');                        
                //=========*** print stipend amount alloted for this moodle user (Prints 0 if no stipend alloted
                $editField='';
                $budget= $this->stipendGiverObj->getStipendBudget($u->id,$this->userList);
                if ($this->sCourseObj->is_teacher($USER->id)){  
                  $editField = '<input style="text-align:right;" type="text" size="6" name="newbudgets[]" value="'.$budget.'">';
                }else  $editField= $budget;
                $rowData[]=$editField;
                
                //========= print names of avatars this moodle user has, and the amount's they've withdrawn and the dates
                    //===== build inner Avatar names table
                       
                        //===== BUILD Avatar table if user has avatars
                            //initialize Av table data;
                                     
                            //if this user has an avatar associated with the accound
                            $userAvatar=null;
                            $avs='';  
                            $debits=''; 
                            if ($ownedAvatars) {
                               
                               
                                foreach ($ownedAvatars as $userAvatar) {
                              
                                 
                                   // If this entry is empty, then skip it
                                    if (empty($userAvatar->avname) || ctype_space($userAvatar->avname)) {
                              
                                         continue;
                                    }
                                    // ========= print avatar name 
                                    $avs = "<a href=\"{$url_sloodleprofile}\">{$userAvatar->avname}</a><br>";
                                    //========== print amount withdrawn by this avatar
                                        //search transactions to see if this user made a debit and return amount. If they havent withdrew any, 0 will be returned
                                    $debits .= $this->stipendGiverObj->getAvatarDebits($userAvatar->uuid) . "<br>";
                                }
                                
                                
                            }else{ 
                         //======= build an empty avatars names table if no avatars                     
                                 //Build Avatar names table
                                 //even though this user has no avatars associated with account, check to see if transactions                     
                                 $transAmount=$this->stipendGiverObj->getUserDebits($u->id);
                                 //have been made with this moodle user id.                     
                               if ($transAmount>0){
                                     //if they have, notify teacher that transaction has been made
                                     //search transaction records and find out which avatar was used
                                     $transRecs = $this->stipendGiverObj->getTransactionRecords($u->id);                                      
                                     $deletedAvs= Array();                                    
                                     if($transRecs)
                                        foreach ($transRecs as $t){
                                            if ($u->id == $t->userid)
                                                $deletedAvs[]=$t->avname;
                                            
                                            }
                                     //print avs that made transactions, but no longer are registered to this user
                                    $avs= $this->addCommas($deletedAvs);
                                 }
                               
                               }
                                
                     //========= now print Inner AvTable
                     //add the collective RowData to inner avTable that will sit in this cell of the bigger user html table
                                             //now place this inner avTable inside the bigger user html table 
                     // the "true" in print_table($table,true) returns the html rather than print it
                     $rowData[]= $avs;
                     if ($this->icurrency=="Lindens"){      
                         $rowData[]= $debits;
                     }           
                     $rowData[]= $this->stipendGiverObj->getStipendBudget($u->id,$this->userList) - $this->stipendGiverObj->getUserDebits($u->id);
                      //===== NOW print the actual User table                 
                      $sloodletable->data[] = $rowData;
                
            }
            print ('<form action="" method="POST">');
            print_table($sloodletable);  
            print('</form>');  
        }  
  
          
               
            
    }  
   
            
      
    /**
    * Render the view of the Stipend Giver.
    */
    function render()              
    {
        global $CFG, $USER;   
        $this->courseid = $this->course->id; 
        $sloodleid=$this->sloodle->id;
    
        // Print a Table Intro / Amount  / Purpose 
        // of Stipend        
        print_box_start('generalbox boxaligncenter boxwidthnormal leftpara'); 
            $iTable = new stdClass();
            $iRow = array();            
            //********* PRINT DESCRIPTION
            print('<b style="color:Black;text-align:left;">'.get_string('stipendgiver:description','sloodle').':</b>'.$this->sloodle->intro.'<br> ');             
            if ($this->icurrency=="Lindens"){      
                 $iRow[] ='<b style="color:green;text-align:left;">'.get_string('stipendgiver:totalallocations','sloodle').'</b>:';
                 $iRow[]=$this->stipendGiverObj->getStipendBudget(null,$this->userList) ." ". $this->stipendGiverObj->geticurrency();
            }else if ($this->icurrency=="iPoints"){
                    $iRow[] ='<b style="color:green;text-align:left;">'.get_string('stipendgiver:totalawarded','sloodle').'</b>:';
                     $iRow[]=$this->stipendGiverObj->getStipendBudget(null,$this->userList) ." ". $this->stipendGiverObj->geticurrency();
            }           
            $iTable->data[] = $iRow;
            //********* PRINT TOTAL DEBITS
            if ($this->icurrency=="Lindens"){      
                $iRow = array();            
                            $iRow[] ='<b style="color:red;text-align:left;">'.get_string('stipendgiver:totaldebits','sloodle').'</b>:&nbsp&nbsp';
                $iRow[] =$this->stipendGiverObj->getUserDebits() . " ".$this->stipendGiverObj->geticurrency();
                $iTable->data[] = $iRow;    
                //********* PRINT STARTING BALANCE
                $iRow = array();                        
                $iRow[] ='<b style="color:blue;text-align:left;">'.get_string('stipendgiver:startingbalance','sloodle').'</b>:&nbsp&nbsp';
                $iRow[] =$this->stipendGiverObj->getStartingBalance() ." ". $this->stipendGiverObj->geticurrency();
                $iTable->data[] = $iRow;
                $iRow = array();             
            }    
            
            print_table($iTable);
            
        print_box_end();      
        //==================================================================================================================
       
       if ($this->getRenderMessage()){
        print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
            print ($this->getRenderMessage());
        print_box_end();
       }
       
       
       //print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
            //======Print TRANSACTIONS TITLE
  if ($this->icurrency=="Lindens"){      
                          print('<h3 style="color:black;text-align:center;">'.get_string('stipendgiver:transactions','sloodle')).'</h3> ';
          
                     }else if ($this->icurrency=="iPoints"){
            print('<h3 style="color:black;text-align:center;">'.get_string('stipendgiver:scoreboard','sloodle')).'</h3> ';
          
                         
                     }           
           
            //======Print STUDENT TRANSACTIONS
             
            if ($this->sCourseObj->getUserList()){
            
                $this->printUserTable($this->sCourseObj->getUserList());
            }
            else print "no student transaction";
            
        //==================================================================================================================
           
             //  $this->destroy();        
        
     
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

function destroy(){
    //debugging only
                      //test file to access db and play with forms
        $dbtype  = 'mysql';
        $dbhost    = 'localhost';
        $dbname    = 'eslteach_moodle';
        $dbuser    = 'eslteach_moodle';
        $dbpass    = 'evkingmoodle555';
          
          //connect to db
          $con = mysql_connect("localhost",$dbuser,$dbpass);
        if (!$con)
          {
          die('Could not connect: ' . mysql_error());
          }
          //use databse
          mysql_select_db($dbname);
          
        $result = mysql_query("TRUNCATE TABLE `mdl_sloodle_stipendgiver_trans`");
               

        mysql_close($con);    
    
    } 
}

          
?>
