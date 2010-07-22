       // Sloodle quiz chair
        // Allows SL users to take Moodle quizzes in-world
        // Part of the Sloodle project (www.sloodle.org)
        //
        // Copyright (c) 2006-9 Sloodle (various contributors)
        // Released under the GNU GPL
        //
        // Contributors:
        //  Edmund Edgar
        //  Peter R. Bloomfield
        //
        
        // Memory-saving hacks!
        key null_key = NULL_KEY;
integer listenHandle;
		integer 	joingame=FALSE;
        integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST=-1828374651;
        integer doRepeat = 0; // whether we should run through the questions again when we're done
        integer doDialog = 1; // whether we should ask the questions using dialog rather than chat
        integer doPlaySound = 1; // whether we should play sound
        integer doRandomize = 1; // whether we should ask the questions in random order
        integer DISPLAY_CHANNEL=-870881;
        string sloodleserverroot = "";
        integer sloodlecontrollerid = 0;
        string sloodlepwd = "";
        integer sloodlemoduleid = 0;
        integer sloodleobjectaccessleveluse = 0; // Who can use this object?
        integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
        integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
        list groups;
        string authenticatedUser;
        integer isconfigured = FALSE; // Do we have all the configuration data we need?
        integer eof = FALSE; // Have we reached the end of the configuration data?
        integer points=10;
        integer SLOODLE_CHANNEL_AVATAR_DIALOG;
        integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343; // an arbitrary channel the sloodle scripts will use to talk to each other. Doesn't atter what it is, as long as the same thing is set in the sloodle_slave script. 
        integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;
        integer currentAwardId;
        integer SLOODLE_CHANNEL_QUIZ_FETCH_FEEDBACK = -1639271101;
        integer SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR = -1639271102;
        integer SLOODLE_CHANNEL_QUIZ_STARTED_FOR_AVATAR = -1639271103;
        integer SLOODLE_CHANNEL_QUIZ_COMPLETED_FOR_AVATAR = -1639271104;
        integer SLOODLE_CHANNEL_QUESTION_ASKED_AVATAR = -1639271105;
        integer SLOODLE_CHANNEL_QUESTION_ANSWERED_AVATAR = -1639271106;
        integer SLOODLE_CHANNEL_QUIZ_LOADING_QUESTION = -1639271107;
        integer SLOODLE_CHANNEL_QUIZ_LOADED_QUESTION = -1639271108;
        integer SLOODLE_CHANNEL_QUIZ_LOADING_QUIZ = -1639271109;
        integer SLOODLE_CHANNEL_QUIZ_LOADED_QUIZ = -1639271110;
        integer SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION = -1639271111;            
        integer SLOODLE_CHANNEL_QUIZ_ASK_QUESTION = -1639271112;                

        integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
        
        string SLOODLE_OBJECT_TYPE = "quiz-1.0";
        string SLOODLE_EOF = "sloodleeof";
        
        string sloodle_quiz_url = "/mod/sloodle/mod/quiz-1.0/linker.php";
          integer ANIM_CHANNEL=-99;
                integer XY_FLAG_CHANNEL = -92811;
                        integer scoreboardchannel;
        
                integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
        integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
                string myQuizName;
                integer myQuizId;

                string myGroupName;
                integer gameid;

                integer UI_CHANNEL                                                            =89997;//UI Channel - channel used to trigger awards_notecard reading
                
                // ID and name of the current quiz
                integer quizid = -1;
                string quizname = "";

            integer MENU_CHANNEL;
            
                               
        key httpquizquery = null_key;
        
        float request_timeout = 20.0;
              vector     RED            = <0.77278,0.04391,0.00000>;//RED
        vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
        vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
        vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
        vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
        vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
        vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
        vector     WHITE        = <1.000,1.000,1.000>;//WHITE
        vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
         /***********************************************
    *  random_integer()
    *  |-->Produces a random integer
    ***********************************************/ 
    integer random_integer( integer min, integer max ){
     return min + (integer)( llFrand( max - min + 1 ) );
    }
    ///// FUNCTIONS /////
            /***********************************************************************************************
            *  s()  k() i() and v() are used so that sending messages is more readable by humans.  
            * Ie: instead of sending a linked message as
            *  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
            *  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
            *  All these functions do is strip off the text before the ":" char and return a string
            ***********************************************************************************************/
            string s (string ss){
                return llList2String(llParseString2List(ss, [":"], []),1);
            }//end function
            key k (string kk){
                return llList2Key(llParseString2List(kk, [":"], []),1);
            }//end function
            integer i (string ii){
                return llList2Integer(llParseString2List(ii, [":"], []),1);
            }//end function
            vector v (string vv){
                return llList2Vector(llParseString2List(vv, [":"], []),1);
            }//end function
           integer debugCheck(){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
                return TRUE;
            }
                else return FALSE;
            
        }
        debug(string str){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
                llOwnerSay(llGetScriptName()+" " +str);
           }
        }
        // ID and name of the current quiz
        // This stores the list of question ID's (global ID's)
        list question_ids = [];
        integer num_questions = 0;
        // Identifies the active question number (index into question_ids list)
        // (Next question will always be this value +1)
        integer active_question = -1;

        // Avatar currently using this cahir
        key sitter = null_key;
        // The position where we started. The Chair will use this to get the lowest vertical position it used.
        vector startingposition;
        
        // Stores the number of questions the user got correct on a given attempt
        integer num_correct = 0;
        
        
        ///// FUNCTIONS /////
        /******************************************************************************************************************************
        * sloodle_error_code - 
        * Author: Paul Preibisch
        * Description - This function sends a linked message on the SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST channel
        * The error_messages script hears this, translates the status code and sends an instant message to the avuuid
        * Params: method - SLOODLE_TRANSLATE_SAY, SLOODLE_TRANSLATE_IM etc
        * Params:  avuuid - this is the avatar UUID to that an instant message with the translated error code will be sent to
        * Params: status code - the status code of the error as on our wiki: http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes
        *******************************************************************************************************************************/
        sloodle_error_code(string method, key avuuid,integer statuscode){
                    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST, method+"|"+(string)avuuid+"|"+(string)statuscode, NULL_KEY);
        }        
        sloodle_debug(string msg)
        {
            llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, null_key);
        }        

        // Configure by receiving a linked message from another script in the object
        // Returns TRUE if the object has all the data it needs
        integer sloodle_handle_command(string str) 
        {
            if (str=="do:requestconfig"||str=="do:reset") llResetScript();
            list bits = llParseString2List(str,["|"],[]);
            integer numbits = llGetListLength(bits);
            string name = llList2String(bits,0);
            string value1 = "";
            string value2 = "";
            
            if (numbits > 1) value1 = llList2String(bits,1);
            if (numbits > 2) value2 = llList2String(bits,2);
            
            if (name == "set:sloodleserverroot") sloodleserverroot = value1;
            else if (name == "set:sloodlepwd") {
                // The password may be a single prim password, or a UUID and a password
                if (value2 != "") sloodlepwd = value1 + "|" + value2;
                else sloodlepwd = value1;
                
            } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
            else if (name == "set:sloodlemoduleid") sloodlemoduleid = (integer)value1;
            else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
            else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
            else if (name == "set:scoreboard") {
                currentAwardId = (integer)value1;                
           } 
            else if (name == "points") points = (integer)value1;       
            else if (name == "set:scoreboardchannel") scoreboardchannel= (integer)value1;              
            else if (name == "set:sloodlerepeat") doRepeat = (integer)value1;
            else if (name == "set:sloodlerandomize") doRandomize = (integer)value1;
            else if (name == "set:sloodledialog") doDialog = (integer)value1;
            else if (name == "set:sloodleplaysound") doPlaySound = (integer)value1;
            else if (name == SLOODLE_EOF) return TRUE;
            return FALSE;
    }
        // Checks if the given agent is permitted to user this object
        // Returns TRUE if so, or FALSE if not
        integer sloodle_check_access_use(key id)
        {
            // Check the access mode
            if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
                return llSameGroup(id);
            } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
                return TRUE;
            }
            
            // Assume it's owner mode
            return (id == llGetOwner());
        }

        
        // Report completion to the user
        finish_quiz() 
        {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM, [0], "complete", [llKey2Name(sitter), (string)num_correct + "/" + (string)num_questions], sitter, "quiz");
            //move_to_start(); // Taking this out here leaves the quiz chair at its final position until the user stands up.
            
            // Notify the server that the attempt was finished
            string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
            body += "&sloodlepwd=" + sloodlepwd;
            body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
            body += "&sloodleuuid=" + (string)sitter;
            body += "&sloodleavname=" + llEscapeURL(llKey2Name(sitter));
            body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
            body += "&finishattempt=1";
            
            llHTTPRequest(sloodleserverroot + sloodle_quiz_url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
            
            llMessageLinked(LINK_SET, SLOODLE_CHANNEL_QUIZ_COMPLETED_FOR_AVATAR, (string)num_correct + "/" + (string)num_questions, sitter);
            
        }
        
        // Reinitialise (e.g. after one person has finished an attempt)
        reinitialise()
        {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "resetting", [], null_key, "");
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", null_key);
            llResetScript();
        }
        
        
        ///// TRANSLATION /////
        
        // Link message channels
        integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
        
        // Translation output methods
        string SLOODLE_TRANSLATE_WHISPER = "whisper";               // 1 output parameter: chat channel number
        string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
        string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
        string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
        string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
        string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
        
        // Send a translation request link message
        
        sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
        {
            
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
        }
        
        ///// ----------- /////
        
        
        ///// STATES /////
        
        // Waiting on initialisation
        default
        {
            state_entry()
            {
                    SLOODLE_CHANNEL_AVATAR_DIALOG=random_integer(-20000,-30000);
                   authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                //trigger startup sounds
                     llTriggerSound("flag_down", 1.0);
                     llTriggerSound("5992af3b-fde1-309f-4077-2c8d5cb7153c",1.0);//starting up
                 //trigger flag animation
                     llMessageLinked(LINK_SET,ANIM_CHANNEL,"p0", NULL_KEY);
                 //set xy text
                     llMessageLinked(LINK_SET, XY_FLAG_CHANNEL,"Resetting...", NULL_KEY);
                 //unset any seated avatars
                     llUnSit(llGetLinkKey(9));
                //change color of chair to indicate loading                     
                     llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)RED,NULL_KEY);                 
                     llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_flag|"+(string)RED,NULL_KEY);
                 //reset groups
                     groups=[];                     
                //reset hover text
                    llSetText("", <0.0,0.0,0.0>, 0.0);
                //reset variables
                    isconfigured = FALSE;
                    eof = FALSE;
                    sloodleserverroot = "";
                    sloodlepwd = "";
                    sloodlecontrollerid = 0;
                    sloodlemoduleid = 0;
                    sloodleobjectaccessleveluse = 0;
                    sloodleserveraccesslevel = 0;
                    doRepeat = 1;
                    doDialog = 1;
                    doPlaySound = 1;
                    doRandomize = 1;
            }
            //chair will go to ready state when SLOODLE_EOF is read from notecard
            link_message( integer sender_num, integer num, string str, key id)
            {
                   
                // Check the channel
                if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {      
                      
                    if (sloodle_handle_command(str)==TRUE)state ready;
                }//sloodlechannel            
             if (num==UI_CHANNEL){
                        list cmdList = llParseString2List(str, ["|"], []);        
                        string cmd = s(llList2String(cmdList,0));
                        if (cmd=="BUTTON PRESS"){
                            if (k(llList2String(cmdList,2)) == llGetOwner()) {
                                llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", null_key);
                            }
                        }
             }
        }
    } 
        
        // Ready state - waiting for a user to climb aboard!
        state ready
        {
            state_entry()
            {
                debug("ready");
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], null_key, "");
                llMessageLinked(LINK_SET, XY_FLAG_CHANNEL,"Ready...", NULL_KEY);
                llListenRemove(listenHandle);
                llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_desk|"+(string)WHITE,NULL_KEY);
                llMessageLinked(LINK_SET,DISPLAY_CHANNEL,"CMD:COLOR|name:btn_flag|"+(string)WHITE,NULL_KEY);
                llTriggerSound("2622d9a8-0582-d7fc-c340-45d9a844d12f", 1.0);//sit on chair
            }
            
            // Wait for the script that handles the sitting to tell us that somebody has sat on us.
            // Normally a sit will immediately produce a link message
            // But variations on the script may do things differently, 
            // eg. the awards script doesn't want to start the quiz until it's got a Game ID
            link_message(integer sender_num, integer num, string str, key id)            {
                if (num==SLOODLE_CHANNEL_OBJECT_DIALOG){
                    sloodle_handle_command(str);
                }
                 if (num == SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR) {
                    sitter = id;
                    // Make sure the given avatar is allowed to use this object
                    if (!sloodle_check_access_use(sitter)) {
                        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llKey2Name(sitter)], null_key, "");
                        llUnSit(sitter);
                        sitter = null_key;
                        return;
                    }                
                    // Our current position
                    // We'll report this to the UI scripts when they may need to move to it, etc.
                        startingposition = llGetPos();             
                        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "starting", [llKey2Name(sitter)], null_key, "quiz");                                                     
                    // Start the quiz
                    		llSetTimerEvent(10);
                    			joingame=FALSE;
                            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "joinattempt", [llKey2Name(sitter)], null_key, "quiz");
                    //The command below "GET GAMEID"  gets heard by the _quizchair_datahandler.  When it hears this message, it sends out a regionsay to the scoreboard
                    //to get a game id - which is basically the id of the game as specified in sloodle_awards_games and signifies the current "round" 
                        llMessageLinked(LINK_THIS, UI_CHANNEL, "cmd:GET GAMEID", null_key);
                    //The server will return to the datahandler the message"SCOREBOARD SENDING GAME ID" and the datahandler will pass a linked message GAMEID back on the UI_CHANNEL
                    //when this script hears GAMEID, it will send a groups_getUserGrps command to the API                            
                }     
                if (num == UI_CHANNEL) {
                        // Split the message into lines
                                list cmdList = llParseString2List(str, ["|"], []);        
                                string cmd = s(llList2String(cmdList,0));
                                //this command is send in response to the linked message above: "cmd:getgameid"
                                //the game id is received from gameid.lsl script which listens to the scoreboard channel for newgame messages from the scoreboard when a new game is created
                                if (cmd=="GAMEID") {
                                    gameid=i(llList2String(cmdList,1));
                                    myQuizName =s(llList2String(cmdList,3));
                                    myQuizId =i(llList2String(cmdList,4));     
                                    authenticatedUser= "&sloodleuuid="+(string)sitter+"&sloodleavname="+llEscapeURL(llKey2Name(sitter));                   
                                    llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->joingame"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&gameid="+(string)gameid+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter)), llGetScriptName());
                                
                                }//gameid
                                else 
                                //check quiz message comes from _getGroup.lsl script.  
                                if (cmd=="check quiz") state check_quiz;
                    }//chan
                    else
                    if (num==PLUGIN_RESPONSE_CHANNEL){
                        list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                        //get status code
                        list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
                        integer status =llList2Integer(statusLine,0);
                        string descripter = llList2String(statusLine,1);
                        string response = s(llList2String(dataLines,1));
                        if (response=="awards|joingame"){
                            if (status==1){
                            	joingame=TRUE;
                                llTriggerSound("98f60cae-636b-71c3-eff5-d6ac1201ff33", 1.0);//new player
                                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "groups->getUsersGrps"+authenticatedUser+"&avname="+llEscapeURL(llKey2Name(sitter))+"&avuuid="+(string)sitter, NULL_KEY);                         
                            }//statis
                            else {
                                sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY,status); //send message to error_message.lsl
                                llUnSit(sitter);
                                reinitialise();
                            }//else
                     }//if
                      if (response=="groups|getUsersGrps"||response=="groups|addToRandomGrp"){
                                if (status==1) state check_quiz;
                      }
                }//chan
                          
            }
             timer() {
                      	llSetTimerEvent(0);  
                        if (!joingame) llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG , "do:requestconfig", NULL_KEY);  
    
                }

            on_rez(integer par)
            {
                llResetScript();
            }            
                                    
        }
        
        
        // Fetching the general quiz data
        state check_quiz
        {
            state_entry()
            {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "fetchingquiz", [], null_key, "quiz");
                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->addTransaction"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&sourceuuid="+(string)llGetOwner()+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter))+"&amount="+(string)0+"&currency=Credits&details="+llEscapeURL(llKey2Name(sitter) +" is entering game"), NULL_KEY);
                // Clear existing data
                quizname = "";
                question_ids = [];
                num_questions = 0;
                active_question = -1;                
                
                // Request the quiz data from Moodle
                string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
                body += "&sloodlepwd=" + sloodlepwd;
                body += "&sloodlemoduleid=" + (string)myQuizId;
                body += "&sloodleuuid=" + (string)sitter;
                body += "&sloodleavname=" + llEscapeURL(llKey2Name(sitter));
                body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
                
                httpquizquery = llHTTPRequest(sloodleserverroot + sloodle_quiz_url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
                debug(sloodleserverroot + sloodle_quiz_url+"?"+body);
                llSetTimerEvent(0.0);
                llSetTimerEvent((float)request_timeout);
            }
             link_message(integer sender_num, integer num, string str, key id)            {
                if (num==SLOODLE_CHANNEL_OBJECT_DIALOG){
                    sloodle_handle_command(str);
                }
             }
            state_exit()
            {
                llSetTimerEvent(0.0);
            }
            
            timer()
            {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "httptimeout", [], null_key, "");
                state ready;
            }
            
            http_response(key id, integer status, list meta, string body)
            {
                 debug("http body is: "+body);
                // Is this the response we are expecting?
                if (id != httpquizquery) return;
                httpquizquery = null_key;
                // Make sure the response was OK
                if (status != 200) {
                        sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY,status); //send message to error_message.lsl
                    state default;
                }
               
                // Split the response into several lines
                list lines = llParseString2List(body, ["\n"], []);
                integer numlines = llGetListLength(lines);
                body = "";
                list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
                integer statuscode = (integer)llStringTrim(llList2String(statusfields, 0), STRING_TRIM);
                
                // Was it an error code?
                if (statuscode == -10301) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "noattemptsleft", [llKey2Name(sitter)], null_key, "");
                    state ready;
                    return;
                    
                } else if (statuscode == -10302) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "noquestions", [], null_key, "");
                    state ready;
                    return;
                    
                } else if (statuscode <= 0) {
                    //sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "servererror", [statuscode], null_key, "");
                     sloodle_error_code(SLOODLE_TRANSLATE_IM, sitter,statuscode); //send message to error_message.lsl                 
                    // Check if an error message was reported
                    if (numlines > 1) sloodle_debug(llList2String(lines, 1));
                    state ready;
                    return;
                }
                
                // We shouldn't need the status line anymore... get rid of it
                statusfields = [];
        
                // Go through each line of the response
                integer i;
                for (i = 1; i < numlines; i++) {
        
                    // Extract and parse the current line
                    string thislinestr = llList2String(lines, i);
                    list thisline = llParseString2List(thislinestr,["|"],[]);
                    string rowtype = llList2String( thisline, 0 ); 
        
                    // Check what type of line this is
                    if ( rowtype == "quiz" ) {
                        
                        // Get the quiz ID and name
                        quizid = (integer)llList2String(thisline, 4);
                        quizname = llList2String(thisline, 2);
                        
                    } else if ( rowtype == "quizpages" ) {
                        
                        // Extract the list of questions ID's
                        list question_ids_str = llCSV2List(llList2String(thisline, 3));
                        num_questions = llGetListLength(question_ids_str);
                        integer qiter = 0;
                        question_ids = [];
                        // Store all our question IDs
                        for (qiter = 0; qiter < num_questions; qiter++) {
                            question_ids += [(integer)llList2String(question_ids_str, qiter)];
                        }
                        
                        // Are we to randomize the order of the questions?
                        if (doRandomize) question_ids = llListRandomize(question_ids, 1);
                        active_question = 0;
                    }
                }
                
                // Make sure we have all the data we need
                if (quizname == "" || num_questions == 0) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "noquestions", [], null_key, "quiz");
                    debug("no questions");
                    state default;
                    return;
                }
                
                // Report the status to the user
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "ready", [quizname], null_key, "quiz");
                state quizzing;
            }
            
            on_rez(integer par)
            {
                llResetScript();
            }
            
            changed(integer change)
            {
                llMessageLinked(LINK_SET, SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION, (string)startingposition, sitter);                                                        
            //    reinitialise();
            }
        }
        
        
        // Running the quiz
        state quizzing
        {
            on_rez(integer param)
            {
                llResetScript();
            }
            
            state_entry()
            {
                llListen(scoreboardchannel, "", "", "GAMEID");
                llSetText("", <0.0,0.0,0.0>, 0.0);
                num_correct = 0;
               
                llMessageLinked(LINK_SET, SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION, (string)startingposition, sitter);                        
                
                // Make sure we have some questions
                if (num_questions == 0) {
                    sloodle_debug("No questions - cannot run quiz.");
                    state default;
                    return;
                }
                
                // Start from the beginning
                active_question = 0;
                 debug("quizzing "+(string)sitter);
                llMessageLinked( LINK_SET, SLOODLE_CHANNEL_QUIZ_ASK_QUESTION, (string)llList2Integer(question_ids, active_question), sitter );
                llSetTimerEvent( 10.0 ); // The other script should let us know that it's heard us and asked the question. If it doesn't, we'll keep on retrying until it hears us, if it ever does.
            }
            
            link_message(integer sender_num, integer num, string str, key id)
            {
                 if (num==UI_CHANNEL){
                        list cmdList = llParseString2List(str, ["|"], []);        
                        string cmd = s(llList2String(cmdList,0));
                        string button= s(llList2String(cmdList,1));
                        if (cmd=="BUTTON PRESS"){
                            if (button=="btn_desk")
                            if (k(llList2String(cmdList,2)) == sitter) {
                                
                 llMessageLinked( LINK_SET, SLOODLE_CHANNEL_QUIZ_ASK_QUESTION, (string)llList2Integer(question_ids, active_question), sitter );
                     }
                        }
                    }
                if (num == SLOODLE_CHANNEL_QUESTION_ANSWERED_AVATAR) {
                    
                    float scorechange = (integer)str;
                    
                    if (sitter != id) {
                        return;
                    }
                    
                 // Advance to the next question
                    active_question++; 
                    
                    if(scorechange>0) {
                        num_correct++; // SAL added this
                            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->addTransaction"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&sourceuuid="+(string)llGetOwner()+"&avuuid="+(string)sitter+"&avname="+llEscapeURL(llKey2Name(sitter))+"&amount="+(string)points+"&currency=Credits&details="+llEscapeURL("Quiz Chair Points,"+llKey2Name(sitter) ), NULL_KEY);
                    }

                    // Are we are at the end of the quiz?
                    if ((active_question + 1) >= num_questions) {
                        // Yes - finish off
                        finish_quiz();
                        // Do we want to repeat the quiz?
                        if (doRepeat) state quizzing;
                        return;
                    }
                                                                                
                    llMessageLinked( LINK_SET, SLOODLE_CHANNEL_QUIZ_ASK_QUESTION, (string)llList2Integer(question_ids, active_question), sitter );
                    
                } else if (num == SLOODLE_CHANNEL_QUESTION_ASKED_AVATAR) {
                    
                    // Cancel the timer that we used to make sure the question script heard us and asked its question.
                    // At this point the question script should ask the question then wait for a response.
                    // If it doesn't get one, nothing will happen until the sitter touches us, and we'll ask the question script to fetch and ask the question again.
                    llSetTimerEvent(0.0);
                    
                } else if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
                    // Is it a reset command?
                        sloodle_handle_command(str);
               
                    
                    return;
                }                        
                
                // TODO: What happens if loading the question fails?
            }
            
            state_exit()
            {
                llSetTimerEvent(0.0);
            }
            
            timer()
            {       
                llSetTimerEvent( 0.0 );         
                if (active_question > -1) {
                    llMessageLinked( LINK_SET, SLOODLE_CHANNEL_QUIZ_ASK_QUESTION, (string)llList2Integer(question_ids, active_question), sitter );
                    llSetTimerEvent( 10.0 ); // The other script should let us know that it's heard us and answered the question. If it doesn't, we'll keep on retrying until it hears us, if it ever does.
                }
            }
            
          
  
            
            changed(integer change)
           {
               if (change==CHANGED_INVENTORY){                
                llMessageLinked(LINK_SET, SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION, (string)startingposition, sitter);                                        
                reinitialise();
               }  
                   if (change==CHANGED_LINK){
                       if (llAvatarOnSitTarget()==NULL_KEY){
                        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION, (string)startingposition, sitter);                                        
                        reinitialise();
                       } 
                   }                     
           }
        }
