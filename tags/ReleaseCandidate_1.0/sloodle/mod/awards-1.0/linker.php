<?php        
    /**
    * Sloodle Awards linker (for Sloodle 0.4).
    * Allows a Awards to get the class list, and iPoints / Stipends assigned to each user
    *
    * @package Awards
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @copyright Paul Preibisch - aka Fire Centaur
    */
     
    define ('CHUNK_SIZE',10);
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
        /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** SLOODLE course object data structure */
    require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');
    /** SLOODLE stipendgiver object data structure */
    require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');
    /** Sloodle Session code. */
    /** Grab the Sloodle/Moodle configuration. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');

     $sCourseObj = null;   //this will be used to access course specific information
     $awardsObj = null;
    // Authenticate the request
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->validate_user();  
    $sloodle->load_module('awards', true); 
    //get user data from the http request 
    $avatarname = $sloodle->user->get_avatar_name(); 
    $avataruuid= $sloodle->user->get_avatar_uuid();     
    $cmid=$sloodle->request->optional_param('sloodlemoduleid'); 
    $sloodlemoduleid=$sloodle->module->cm->instance;
    $sloodlecontrollerid=$sloodle->request->optional_param('sloodlecontrollerid');    
    $command=$sloodle->request->optional_param('command');  
    $sCourseObj = new sloodleCourseObj($cmid);
    $awardsObj = new Awards((int)$sCourseObj->cm->instance);
    function balanceSort($a, $b){
        if ($a->balance == $b->balance) {
            return 0;
        }
        return ($a->balance > $b->balance) ? -1 : 1;
    }
    function nameSort($a, $b){
        if ($a->avname == $b->avname) {
            return 0;
        }
        return ($a->avname < $b->avname) ? -1 : 1;
    }
    
    /*
    * addUserData - this function will print all user related data to the output during a getClassList request
    * @param int index - this is the start index of the users to send - so if index is 11, send users 11-20
    */
    function addUserData($index,$sortMode){
        global $sloodle;
        global $sCourseObj;
        global $awardsObj;
         
           //---sloodledata:
           //  | avuuid 
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
            //add balance fields from ipoint_trans database then sort
            $avList= array();
             foreach ($avatarList as $u){
                   $av = new stdClass(); 
                   $av->userid = $u->userid;
                   $av->username = $u->username;                                      
                   $av->avname = $u->avname;
                   $av->uuid = $u->uuid;
                   $av_balanceDetails= $awardsObj->awards_getBalanceDetails($u->userid);
                   $av->balance = $av_balanceDetails->balance;                   
                   $av->credits = $av_balanceDetails->credits;
                   $av->debits = $av_balanceDetails->debits;  
                   $avList[]=$av;
             }
             //sort by points
             if ($sortMode=="balance") usort($avList, "balanceSort"); else
             if ($sortMode=="name") usort($avList, "nameSort");
                
          
            $sloodleData="";
            $size = count($avatarList);
            
            $i = 0;
            $currIndex = $index; 
            
            foreach ($avList as $av){
                   
                    //print only the CHUNK_SIZE number of users starting from the current index point
               if (($i < $currIndex) || ($i > ($currIndex + CHUNK_SIZE-1))) {
                   $i++;                   
                   continue; //skip the ones which have already been sent
                
               }                 
               else{
                   $sloodleData = $av->uuid."|";
                   $sloodleData .=  $av->avname . "|";
                   $sloodleData .=$av->balance."|";
                   $sloodleData .=$av->debits;
                   
                   $sloodle->response->add_data_line($sloodleData);   
                   $i++;
                   if ($i==count($avatarList))  $sloodle->response->add_data_line("EOF");
               }
            }
               
            
        
        
    }

    /*
    * addDefaultStats - Every time the linker.php script sends data back to the Awards it includes basic
    * stats of the course and this module, so the awards can stay updated in SL
    * This function appends the following lines to the output:
    * line 3) num users 
    * line 4) full course name
    * line 5) sloodle name 
    * line 6) sloodle intro 
    * line 7) iCurrencyType 
    * line 8) api  do we pass through to awards, or an awards module?  YES - SET TO TRUE
    * 
    * @param int index - The start index of the users to send - so if index is 11, send users 11-20
    */

    function addDefaultStats(){
        //add full course name
        global $sloodle;
        global $sCourseObj;
        global $awardsObj;  
            $awardsId = $sloodle->request->optional_param('sloodlemoduleid'); 
            $userList = $sCourseObj->userList;
            $avatarNamesList = $sCourseObj->getAvatarList($userList);
            $assignmentCourseModule = $awardsObj->get_assignment_cmid((int)$sCourseObj->courseId);
           $assignmentName = $awardsObj->get_assignment_name();
            $sloodle->response->add_data_line("NUM USERS:".count($avatarNamesList)); //line 3  
            
            $sloodle->response->add_data_line("COURSE NAME:".$sCourseObj->sloodleCourseObject->get_full_name()); //line 4         
            //add sloodle name
            $sloodleName= $sloodle->module->get_name();
            
            $sloodle->response->add_data_line("AWARDS NAME:".$sloodleName); //line 5   
            //add sloodle intro
            $sloodleIntro= $sloodle->module->get_intro();
            $sloodle->response->add_data_line("AWARDS INTRO:".$sloodleIntro); //line 6
            //add iCurrencyType
            $iCurrencyType = $awardsObj->sloodle_awards_instance->icurrency;
            $sloodle->response->add_data_line("ICURRENCY:".$iCurrencyType); //line 7
            //the api_passthrough var is a variable that is sent with every call to the linker
            //if it is set to true, then a plugin is the one who the response should go to
            //otherwise if it is false, the awards uses the response
            $sloodle->response->add_data_line("AWARDSID:".$awardsId);               //line8
            $sloodle->response->add_data_line("ASSIGNMENTCMID:".$assignmentCourseModule);               //line 9
            $sloodle->response->add_data_line("ASSIGNMENTNAME:".$assignmentName);               //line 10        
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
    
    //*****************************************************************************************************
    //Execution point:
    //retreive the command sent from the awards
    $command = $sloodle->request->optional_param('command');
    $secretWord = $sloodle->request->optional_param('secretword');
    switch ($command){
        /* register response WE send back
        *
        0* 1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
        1* XMLCHANNELID:b9aee0a3-9412-0d4a-219f-84483812b541
        2* COMMAND:REGISTER RESPONSE
        3* NUM USERS:22
        4* COURSE NAME:Awards Demo
        5* AWARDS NAME:awards: get your ipoints!
        6* AWARDS INTRO:Test
        7* ICURRENCY:iPoints
        8* AWARDSID:114
        */    
        case "REGISTER":
            $data=$sloodle->request->optional_param('data'); 
            $awardsObj->setXmlChannel($data);
            $sloodle->response->set_status_code(1);             //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0 
            $sloodle->response->add_data_line("XMLCHANNELID:".$data);//line 1  
            $sloodle->response->add_data_line("COMMAND:REGISTER RESPONSE");//line 2  
            addDefaultStats();   // LINE 3,4,5,6,7,8
            //now render output
            $sloodle->response->render_to_output();
        break;
        //find trans allows the inworld retreiving of transactions from the awards transaction table based on a the avuuid, sloodleid, and details columns. This will output all transactions found matching the avuuid and the idata
        case "FINDTRANS":
            $data=$sloodle->request->optional_param('data'); 
            //&data=SOURCE_UUID:uuid|AVUUID:uuid|AVNAME:string|SEARCHSTRING:string
            $bits = explode("|", $data);
            $source_uuid = getFieldData($bits[0]);         
            $avuuid = getFieldData($bits[1]);
            $avName = getFieldData($bits[2]);
            $searchString = $bits[3];
            $foundRecs = $awardsObj->findTransaction($avuuid,$searchString);
            if ($foundRecs){
                $sloodle->response->set_status_code(1);             //line 0 
                $sloodle->response->set_status_descriptor('OK'); //line 0 
                $sloodle->response->add_data_line("SENDERUUID:".$source_uuid);//line 1  
                $sloodle->response->add_data_line("COMMAND:SEARCH RESPONSE");//line 2  
                addDefaultStats();   // LINE 3,4,5,6,7,8
                foreach ($foundRecs as $recs){
                  $sloodle->response->add_data_line("itype:".$recs->itype+"|amount:".$recs->amount."|".$recs->idata."|time:".$recs->timemodified);                                                 
                }                
            }else{
               $sloodle->response->set_status_code(-777000);             //line 0    
               $sloodle->response->set_status_descriptor('TRANSACTION');    //line 0 
               $sloodle->response->add_data_line("SENDERUUID:".$source_uuid);//line 1  
               $sloodle->response->add_data_line("COMMAND:SEARCH RESPONSE");//line 2  
               addDefaultStats();   // LINE 3,4,5,6,7,8 
            }
            //now render output
            $sloodle->response->render_to_output();
        break;
        /* Get Balance response WE send back
        *
        0* 1|OK|||||2102f5ab-6854-4ec3-aec5-6cd6233c31c6
        1* CALLERID:b9aee0a3-9412-0d4a-219f-84483812b541
        2* COMMAND:GET BALANCE RESPONSE
        3* NUM USERS:22
        4* COURSE NAME:Awards Demo
        5* AWARDS NAME:awards: get your ipoints!
        6* AWARDS INTRO:Test
        7* ICURRENCY:iPoints
        8* AWARDSID:114
        9* SOURCE_UUID:b9aee0a3-9412-0d4a-219f-84483812b541|AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6|AVNAME:Fire Centaur|DEBITS:1000|CREDITS:4999|ACTION:action
        */    
         //&data=SOURCE_UUID:sourceUuid|AVUUID:uuid|AVNAME:avname|ACTION:getbalance|VAR:somevar|SECRETWORD:secret
         case "GET BALANCE":
        $data=$sloodle->request->optional_param('data'); 
        $action=$sloodle->request->optional_param('action');
       
        $bits = explode("|", $data);
        $source_uuid = getFieldData($bits[0]);         
        $avuuid = getFieldData($bits[1]);
        $avName = getFieldData($bits[2]);
        
        //check to see if user sent is a user of our site     
              
        $avuser = new SloodleUser( $sloodle );  
        //start creating our data line to send back        
        $dataLine="SOURCE_UUID:".$source_uuid;
        $dataLine.="|AVUUID:".$avuuid;
        $dataLine.="|AVNAME:".$avName;
        
        //build avuser       
        if (!$avuser->load_avatar($avuuid, null)) {
            //if avatar doesnt exist in our db, send back NULL for idata
           $sloodle->response->set_status_code(-331 );             //line 0 
           $sloodle->response->set_status_descriptor('USER NOT FOUND'); //line 0 
           $dataLine.="|0|0|0";
        }else {
            $avuser->load_linked_user();            
     
            if (!$avuser->is_enrolled($sloodle->course->get_course_id())){
            
                //if avatar is not enrolled in the course, send back NULL for idata
                //user not enrolled
                $sloodle->response->set_status_code(-321 );             //line 0 
                $sloodle->response->set_status_descriptor('USER');//line 0 
                $dataLine.="|0|0|0";
            }else {
                //user is enrolled                                           
                //get credits and debits
                
                $rec= $awardsObj->awards_getBalanceDetails($avuser->get_user_id());
                if (!empty($rec)) {   
                    $balance=$rec->balance;
                    $credits = $rec->credits;                     
                    $debits = $rec->debits;
                    $sloodle->response->set_status_code(1);             //line 0    
                    $sloodle->response->set_status_descriptor('OK');    //line 0 
                    $dataLine.="|BALANCE:".$balance; 
                    $dataLine.="|CREDITS:".$credits;    
                    $dataLine.="|DEBITS:".$debits;
                     
                } 
            }
            
        }
       $dataLine.="|action:".$sloodle->request->optional_param('action'); ;                
       $dataLine.="|".$sloodle->request->optional_param('var'); 
       $dataLine.="|SECRETWORD:".$sloodle->request->optional_param('secretword'); 
        //add other details to message
        $sloodle->response->add_data_line("SOURCEID:".$source_uuid);//line 1  
        $sloodle->response->add_data_line("COMMAND:GET BALANCE RESPONSE");//line 2  
        addDefaultStats();   // LINE 3,4,5,6,7,8
        $sloodle->response->add_data_line($dataLine);//line 9
        //now render output
        $sloodle->response->render_to_output();
        return;
        break;
        /*****************************************
        * "get last transaction" retrieves the last transaction made by the user
        * To do this, it analyises the message sent in the data field
        * &data=SCOREBOARD_UUID:d2ce06b2-9998-241e-9b53-ad02904c287e|CALLER_UUID:b9aee0a3-9412-0d4a-219f-84483812b541|AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6|POINTS:9999
        * 
        * The message we will send back to the awards is:
        * Message Built will look like:
        * we have a transaction for the user.  send message back
        *   LINE   MESAGE
        *     0)     1 | OK
        *     1)     CALLER_ID user who sent the comand
        *     2)     LAST TRANSACTION INFO 
        *     3)     num users
        *     4)     full course name 
        *     5)     sloodle name 
        *     6)     sloodle intro 
        *     7)     iCurrencyType 
        *     8)     awardsId
        *     9)     SCOREBOARD_UUID:scoreboard|CALLER_UUID:caller|AVUUID:avuuid|POINTS:points|IDATA:idata                      
        */
        case "get last transaction":
        //data received=
        //&data=SCOREBOARD_UUID:|SOURCE_UUID: |AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6|POINTS:9999
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        //DETAILS:Attendance,SOURCE_UUID:b9aee0a3-9412-0d4a-219f-84483812b541,SIGNIN_TIME:1247999150
        $source_uuid = getFieldData($bits[0]);         
        $avuuid = getFieldData($bits[1]);
        $avName = getFieldData($bits[2]);
        $points = getFieldData($bits[3]);
        
        $dataLine ="SOURCE_UUID:".$source_uuid;
        $dataLine.="|AVUUID:".$avuuid;
        $dataLine.="|AVNAME:".$avName;
        $dataLine.="|POINTS:".$points;   
        
        
        //check to see if user sent is a user of our site           
        //build avuser
        $avuser = new SloodleUser( $sloodle );
        if (!$avuser->load_avatar($avuuid, null)) {
            //if avatar doesnt exist in our db, send back NULL for idata
           $sloodle->response->set_status_code(-331 );             //line 0 
           $sloodle->response->set_status_descriptor('USER NOT FOUND'); //line 0 
           $dataLine.="|NULL";
        }else {
            $avuser->load_linked_user();
            
          // if (!$avuser->is_really_enrolled($sloodle->course->get_course_id(),$avuser->userid)){
           if (!$avuser->is_really_enrolled($sloodle->course->get_course_id())){
                //if avatar is not enrolled in the course, send back NULL for idata
                //user not enrolled
                $sloodle->response->set_status_code(-321 );             //line 0 
                $sloodle->response->set_status_descriptor('USER');//line 0 
                $dataLine.="|NULL";
            }else {
                //user is enrolled                                           
                //get last transaction
                $rec= $awardsObj->getLastTransaction($avuuid,"attendance"); 
                if (!empty($rec)) {                        
                    $sloodle->response->set_status_code(1);             //line 0    
                    $sloodle->response->set_status_descriptor('OK');    //line 0 
                    $dataLine.="|".$rec->idata; 
                } 
                else {
                    $sloodle->response->set_status_code(-777000);             //line 0    
                    $sloodle->response->set_status_descriptor('TRANSACTION');    //line 0 
                       
                }
                
            }
        }
        //add other details to message
        $sloodle->response->add_data_line("CALLERID:".$source_uuid);//line 1  
        $sloodle->response->add_data_line("COMMAND:LAST TRANSACTION INFO");//line 2  
        addDefaultStats();   // LINE 3,4,5,6,7,8
        $sloodle->response->add_data_line($dataLine);//line 9
        //now render output
        $sloodle->response->render_to_output();
        return;
        break;
         case "makeTransaction":
         
        //disect the data
        //data
        // if its from the owner updating points manually
        //*  |-->SOURCE_UUID: |AVUUID:|AVNAME:|POINTS:|DETAILS:owner modify ipoints,OWNER:,SCOREBOARD:,SCOREBOARDNAME
        // if its from the attendance checker
        //*  |-->SOURCE_UUID: |AVUUID:|AVNAME:|POINTS:|DETAILS:Attendance,SOURCE_UUID:,SIGNIN_NAME:,SIGNIN_KEY:,",SIGNIN_MOODLEID:,SIGNIN_POINTS:,SIGNIN_TIME:"+(string)llGetUnixTime();
        $data = $sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $source_uuid        = getFieldData($bits[0]);
        $avuuid             = getFieldData($bits[1]);        
        $avName             = getFieldData($bits[2]);    
        $points             = getFieldData($bits[3]);   
        $details            = $bits[4]; 
        
      
        
        //get moodleId for the avatar which was sent
        $avuser = new SloodleUser( $sloodle );
        $avuser->load_avatar($avuuid,$avName);
        $avuser->load_linked_user();
        $moodleId = $avuser->get_user_id();

        //details were sent along with this transaction in csv format
        //example: DETAILS:modify ipoints,AV:Fire Centaur,OBJ:d2ce06b2-9998-241e-9b53-ad02904c287e
        $detailbits = explode(",", $details);
        $trans_description  = getFieldData($detailbits[0]);
        $trans_senderAv =   getFieldData($detailbits[1]);
        $trans_objectSource = getFieldData($detailbits[2]);
       
        
        //build transaction record 
        $trans = new stdClass();
        $trans->sloodleid=$sloodlemoduleid;
        $trans->avuuid=$avuuid;        
        $trans->userid = $moodleId;
        $trans->avname=$avName; 
          
        $trans->idata=$details;
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
          // assumes $row is returned value by *_fetch_array()
                   
        $awardsObj->awards_makeTransaction($trans,$sCourseObj);
        //retrieve new balance
        $rec = $awardsObj->awards_getBalanceDetails($moodleId);
        $balance = $rec->balance;
        
     //send message back to awards
       //   LINE   MESAGE
       //    0)     1 | OK
       //    1)     SENDERUUID:senderUuid user who sent the comand
       //    2)     COMMAND:transactionComplete 
       //    3)     num users
       //    4)     full course name 
       //    5)     sloodle name 
       //    6)     sloodle intro 
       //    7)     iCurrencyType 
       //    8)     moduleid - module id of the awards
       //    9)     userName|Avatar Name | newStipendBudget |DETAILS:modify ipoints,AV:Fire Centaur,OBJ:d2ce06b2-9998-241e-9b53-ad02904c287e  
        //line 1
        $sloodle->response->set_status_code(1);             //line 0    1
        $sloodle->response->set_status_descriptor('OK'); 
        //line2: uuid who made the transaction
        $sloodle->response->add_data_line("SENDERUUID:".$avuuid);//line 1  
        //add command
        $sloodle->response->add_data_line("COMMAND:transactionComplete");//line 2 
        
         addDefaultStats();
       
        //    3)     num users
        //    4)     full course name 
        //    5)     sloodle name 
        //    6)     sloodle intro 
        //    7)     iCurrencyType 
        //9* SOURCE_UUID:b9aee0a3-9412-0d4a-219f-84483812b541|AVUUID:2102f5ab-6854-4ec3-aec5-6cd6233c31c6|AVNAME:Fire Centaur|POINTS:9999|DETAILS:ATTENDANCE,AV:Fire Centaur,OBJ:d2ce06b2-9998-241e-9b53-ad02904c287e
        $responseText = "SOURCE_UUID:".$source_uuid;
        $responseText .="|AVUUID:".$avuuid;
        $responseText .="|AVNAME:".$avName;
        $responseText .="|POINTS:".$balance;
        $responseText .="|".$details ."|ACTION:".$sloodle->request->optional_param('action');;       
        $responseText.="|SECRETWORD:".$sloodle->request->optional_param('secretword');
        $sloodle->response->add_data_line($responseText);//line 9
        $sloodle->response->render_to_output();   
       
                
                     
        break;
        
         

       case "getClassList":
           
            $data = $sloodle->request->optional_param('data'); 
            $bits = explode("|", $data);
                 $index        = getFieldData($bits[0]);
            $sortMode     = getFieldData($bits[1]);        
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
           //    9)     iCurrency Type    
           //    10)    awardsId
           //    11+)   stipend data for each user |  
                              
            $sloodle->response->set_status_code(1);             //line 0    1
            $sloodle->response->set_status_descriptor('OK');    //line 0    OK  
            $sloodle->response->add_data_line($senderUuid);//line 1                   

            //add command to send back into sl
            $sloodle->response->add_data_line("COMMAND:getClassListResponse");//line 1  
                          
           addDefaultStats();
            
           addUserData($index,$sortMode);
                
            // Output our response
                
            $sloodle->response->render_to_output();        
       break;
    }
?>
