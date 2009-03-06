<?php        
    /**
    * Sloodle iBank linker (for Sloodle 0.4).
    * Allows a iBank to get the class list, and iPoints / Stipends assigned to each user
    *
    * @package iBank
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Paul Preibisch - aka Fire Centaur
    * 
    */
                    
             
     define ('CHUNK_SIZE',10);
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
        /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    
    /** SLOODLE course object data structure */
    require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
    /** SLOODLE stipendgiver object data structure */
    require_once(SLOODLE_DIRROOT.'/mod/iBank-1.0/stipendgiver_object.php');
    /** Sloodle Session code. */
    /** Grab the Sloodle/Moodle configuration. */

    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    require_once(SLOODLE_LIBROOT.'/modules/module_iBank.php');
    
     $sCourseObj = null;
     $stipendGiverObj = null;
    
    
    // Authenticate the request, and load a chat module
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->validate_user();  
    $sloodle->load_module('iBank', true); 
    //get user data   
    $avatarname = $sloodle->user->get_avatar_name(); 
    $avataruuid= $sloodle->user->get_avatar_uuid();     
    $sloodlemoduleid=$sloodle->request->optional_param('sloodlemoduleid'); 
    $sloodlecontrollerid=$sloodle->request->optional_param('sloodlecontrollerid');    
    $senderUuid=$sloodle->request->optional_param('senderuuid');  
    $senderAvatarName=$sloodle->request->optional_param('senderavatarname');  
    $sCourseObj = new sloodleCourseObj($sloodlemoduleid);
    $sloodleid=$sCourseObj->getSloodleId();
    $stipendGiverObj = new stipendGiverObject((int)$sloodleid);
    $userid = $sloodle->user->get_user_id();
    //this function will add all user stipend data to the output
    function addUserData($index){
        global $sloodle;
        global $sCourseObj;
        global $stipendGiverObj;
         //add all avatar data to the remaining lines    //7+   student DATA
           //---sloodledata:
           //  | moodleid 
           //  | moodle user name 
           //  | Avatar Name 
           //  | stipendAllocation 
           //  | user debits
            $userList = $sCourseObj->userList;
            //getAvatarlist returns an array of stdObj  
            //$av->userid 
            //$av->username 
            //$av->avname 
            //$av->uuid             
            $avatarList = $sCourseObj->getAvatarList($userList);
            $sloodleData="";
            $size = count($avatarList);
            $i = 0;
            $currIndex = $index; 
            
            foreach ($avatarList as $av){
                //print only the CHUNK_SIZE number of users starting from the current index point
               if (($i < $currIndex) || ($i > ($currIndex + CHUNK_SIZE))) {
                   $i++; 
                   continue; //skip the ones which have already been sent
                
               }
               else{
                   $sloodleData = $av->userid . "|".$av->username."|";
                   $sloodleData .=  $av->avname . "|";
                   $sloodleData .=$stipendGiverObj->getStipendBudget($av->userid,$userList)."|";
                   $sloodleData .=$stipendGiverObj->getUserDebits($av->userid);
                   $sloodle->response->add_data_line($sloodleData);   
                   $i++;
                   if ($i==count($avatarList))  $sloodle->response->add_data_line("EOF");
               }
               
            }
               
            
        
        
    }
    function addDefaultStats(){
        //add full course name
        global $sloodle;
        global $sCourseObj;
        global $stipendGiverObj;                                     
            $userList = $sCourseObj->userList;
            $avatarNamesList = $sCourseObj->getAvatarList($userList);
            $sloodle->response->add_data_line(count($avatarNamesList)); //line 3               
            $sloodle->response->add_data_line($sCourseObj->sloodleCourseObject->get_full_name()); //line 4         
            //add sloodle name
            $sloodleName= $sloodle->module->get_name();
            $sloodle->response->add_data_line($sloodleName); //line 5   
            //add sloodle intro
            $sloodleIntro= $sloodle->module->get_intro();
            $sloodle->response->add_data_line($sloodleIntro); //line 6
            
            //add default Stipend
            $defaultStipend= $stipendGiverObj->getStartingBalance();
            $sloodle->response->add_data_line($defaultStipend); //line 7
            
            //add Total Stipends Allocated
            $totalStipends = $stipendGiverObj->getStipendBudget(null,$userList);
            $sloodle->response->add_data_line($totalStipends); //line 8
            
            //add iCurrencyType
            $iCurrencyType = $stipendGiverObj->geticurrency();
            $sloodle->response->add_data_line($iCurrencyType); //line 9
    }
    //load module to get access to the stipend_giver_object and its functions


 //   $stipendGiverObj = new stipendGiverObject($sloodleid);
  //  $sCourseObj = new sloodleCourseObj($sloodleid);
    //get command
    $command = $sloodle->request->optional_param('command');
    
    switch ($command){
         case "WITHDRAW":
             $userList =    $sCourseObj->getUserList();
             $stipend = $stipendGiverObj->getStipendBudget($userid,$userList);
             if ($stipend!==false){
                 $withdrawn = $stipendGiverObj->getUserDebits($userid);
                 $remainingBalance = $stipend - $withdrawn;
                 $withdrawAmount=$remainingBalance;
                 if ($remainingBalance >0){
                     $trans = new stdClass();
                     $trans->amount = $withdrawAmount;
                     $trans->userid =$sloodle->user->get_user_id();
                     $trans->sloodleid=$sloodleid;
                     $trans->itype="debit";
                     $trans->avname=$senderAvatarName; 
                     $trans->avuuid=$senderUuid;
                     
                     if ($stipendGiverObj->makeTransaction($trans)){
                        $sloodle->response->set_status_code(1);             //line 0    1
                        $sloodle->response->set_status_descriptor('OK');    //line 0    OK           
                        //add command to send back into sl
                        $sloodle->response->add_data_line($senderUuid);//line 1          
                        $sloodle->response->add_data_line("withdrawStipendResponse");//line 2          
                        addDefaultStats();                                                     
                        //add data lines
                        //userName|Avatar Name | senderUuid| new balance |iCurrency
                        $data = $userid ."|". $senderAvatarName ."|".$senderUuid."|".$withdrawAmount ."|". $stipendGiverObj->geticurrency() ;
                        $sloodle->response->add_data_line($data);       //line 9
                       // addUserData();    //lines 10+
                     }
                     else{ //there was a problem
                        
                        $sloodle->response->set_status_code(701);             //line 0    1
                        $sloodle->response->set_status_descriptor('COULDNT_ADD_TO_TRANSACTION_TABLE');    //line 0    BAD                                 
                        $sloodle->response->add_data_line($senderUuid);//line 1          
                        $sloodle->response->add_data_line("withdrawStipendResponse");//line 2
                        addDefaultStats();                                                  
                        $data = $userId ."|". $senderAvatarName ."|".$senderUuid."|".$withdrawAmount ."|". $stipendGiverObj->geticurrency() ;
                        $sloodle->response->add_data_line($data);       //line 10
                      //  addUserData();     
                      }
                      
                      $sloodle->response->render_to_output(); 
                     
                     
                 }else{      //ZERO BALANCE
                        $sloodle->response->set_status_code(701);             //line 0    1
                        $sloodle->response->set_status_descriptor('ZERO BALANCE');    //line 0    BAD                                 
                        $sloodle->response->add_data_line($senderUuid);//line 1          
                        $sloodle->response->add_data_line("withdrawStipendResponse");//line 2
                        addDefaultStats();                                                  
                        $data = $userid ."|". $senderAvatarName ."|".$senderUuid."|".$withdrawAmount ."|". $stipendGiverObj->geticurrency() ; 
                        $sloodle->response->add_data_line($data);       //line 10
                        $sloodle->response->render_to_output(); 
                 }
             }else{
                       //STUDENT NOT ADDED TO STIPEND YET - TEACHER MUST RELOAD CLASS
                        $sloodle->response->set_status_code(701);             //line 0    1
                        $sloodle->response->set_status_descriptor('STUDENT NOT ADDED YET');    //line 0    BAD                                 
                        $sloodle->response->add_data_line($senderUuid);//line 1          
                        $sloodle->response->add_data_line("withdrawStipendResponse");//line 2
                        addDefaultStats();                                                  
                        $data = $userid ."|". $senderAvatarName ."|".$senderUuid."|".$withdrawAmount ."|". $stipendGiverObj->geticurrency() ; 
                        $sloodle->response->add_data_line($data);       //line 10
                        $sloodle->response->render_to_output(); 
                 
                 
                 
             }
             
         break;
        //student commands
       case "updateStipend":
       //send message back to stipend giver 
       //   LINE   MESAGE
       //    0)     1 | OK
       //    1)     senderUuid user who sent the comand
       //    2)     updateStipendResponse 
       //    3)     num users
       //    4)     full course name 
       //    5)     sloodle name 
       //    6)     sloodle intro 
       //    7)     default Stipend 
       //    8)     Total Stipends Allocated
       //    9)     iCurrencyType 
       //    10)     userName|Avatar Name | newStipendBudget 

       
                 $data = $sloodle->request->optional_param('data'); 
                 //data retrieved should be a string with the following elements 
                 // 0) moodleId 
                 // 1) avName 
                 // 2) avUuid
                 // 3) amount 
                 $bits = explode("|", $data);
                 $moodleId      = $bits[0];
                 $avName        = $bits[1];
                 $modifyAmount  = $bits[2];
                 if ($stipendGiverObj->setStipendBudget($modifyAmount,$moodleId)){
                    $sloodle->response->set_status_code(1);             //line 0    1
                    $sloodle->response->set_status_descriptor('OK');    //line 0    OK           
                    //add command to send back into sl
                    $sloodle->response->add_data_line($senderUuid);//line 1          
                    $sloodle->response->add_data_line("updateStipendResponse");//line 2          
                    addDefaultStats();                                                     
                    //add data lines
                    //userName|Avatar Name | newStipendBudget
                    $data = $moodleId ."|". $avName ."|". $modifyAmount;
                    $sloodle->response->add_data_line($data);       //line 9
                   // addUserData();    //lines 10+
                 }
                 else{ //there was a problem
                    
                    $sloodle->response->set_status_code(701);             //line 0    1
                    $sloodle->response->set_status_descriptor('BAD');    //line 0    BAD                                 
                    $sloodle->response->add_data_line("updateStipendResponse");//line 1                     
                    addDefaultStats();                                                  
                    $data = $moodleId ."|". $avName ."|". $avUuid ."|". $modifyAmount;
                    $sloodle->response->add_data_line($data);       //line 9     
                  //  addUserData();     
                  }
                  
                  $sloodle->response->render_to_output(); 
                     
      
       break;
 case "setDefaultStipend":
       //send message back to stipend giver 
       //   LINE   MESAGE
       //    0)     1 | OK
       //    1)     senderUuid user who sent the comand
       //    2)     updateStipendResponse 
       //    3)     num students
       //    4)     full course name 
       //    5)     sloodle name 
       //    6)     sloodle intro 
       //    7)     default Stipend 
       //    8)     Total Stipends Allocated 
       //    9)     iCurrencyType               
       //    10)     userName|Avatar Name | newStipendBudget 

       
                 $data = $sloodle->request->optional_param('data'); 
                 //data retrieved should be a string with the following elements 
                 // 0) moodleId 
                 // 1) avName 
                 // 2) avUuid
                 // 3) amount 
                 
                 $modifyAmount  = $data;
                 if ($stipendGiverObj->setStipendBudget($modifyAmount)){
                    $sloodle->response->set_status_code(1);             //line 0    1
                    $sloodle->response->set_status_descriptor('OK');    //line 0    OK           
                    //add command to send back into sl
                    $sloodle->response->add_data_line($senderUuid);//line 1          
                    $sloodle->response->add_data_line("setDefaultStipendResponse");//line 2          
                    addDefaultStats();                                                     
                    //add data lines
                    //userName|Avatar Name | newStipendBudget
                   // addUserData();    //lines 8+
                 }
                 else{ //there was a problem
                    
                    $sloodle->response->set_status_code(701);             //line 0    1
                    $sloodle->response->set_status_descriptor('BAD');    //line 0    BAD                                 
                    $sloodle->response->add_data_line("setDefaultStipendResponse");//line 1                     
                    addDefaultStats();                                                  
                    $data = $moodleId ."|". $avNam ."|". $avUuid ."|". $modifyAmount;
                    addUserData();     
                  }
                  
                  $sloodle->response->render_to_output(); 
                     
      
       break;       
       //game and student commands
       case "getClassList":
           $index = $sloodle->request->optional_param('data');
           //send message back to stipend giver 
           //   LINE   MESAGE
           //    0)     1 | OK
           //    1)     senderUuid
           //    2)     getClassListResponse |
           //    3)     num students
           //    4)     full course name |
           //    5)     sloodle name |
           //    6)     sloodle intro |
           //    7)     default Stipend |       
           //    8)     Total Stipends Allocated |       
           //    9+)     stipend data for each user |                     
            $sloodle->response->set_status_code(1);             //line 0    1
            $sloodle->response->set_status_descriptor('OK');    //line 0    OK  
            $sloodle->response->add_data_line($senderUuid);//line 1                   
            //add command to send back into sl
            $sloodle->response->add_data_line("getClassListResponse");//line 1   
            addDefaultStats();
            addUserData($index);
            // Output our response
            $sloodle->response->render_to_output();        
       break;
    }
?>
