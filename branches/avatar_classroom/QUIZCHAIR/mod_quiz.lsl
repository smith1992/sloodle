// LSL script generated: _SLOODLE_HOUSE.QUIZCHAIR.mod_quiz2.lslp Thu Jul 22 02:04:01 Pacific Daylight Time 2010
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
		integer joingame = FALSE;
        integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
        integer doRepeat = 0;
        integer doDialog = 1;
        integer doPlaySound = 1;
        integer doRandomize = 1;
        integer DISPLAY_CHANNEL = -870881;
        string sloodleserverroot = "";
        integer sloodlecontrollerid = 0;
        string sloodlepwd = "";
        integer sloodlemoduleid = 0;
        integer sloodleobjectaccessleveluse = 0;
        integer sloodleserveraccesslevel = 0;
        list groups;
        string authenticatedUser;
        integer isconfigured = FALSE;
        integer eof = FALSE;
        integer points = 10;
        integer SLOODLE_CHANNEL_AVATAR_DIALOG;
        integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
        integer currentAwardId;
        integer SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR = -1639271102;
        integer SLOODLE_CHANNEL_QUIZ_COMPLETED_FOR_AVATAR = -1639271104;
        integer SLOODLE_CHANNEL_QUESTION_ASKED_AVATAR = -1639271105;
        integer SLOODLE_CHANNEL_QUESTION_ANSWERED_AVATAR = -1639271106;
        integer SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION = -1639271111;
        integer SLOODLE_CHANNEL_QUIZ_ASK_QUESTION = -1639271112;

        integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
        integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
        string SLOODLE_EOF = "sloodleeof";
        
        string sloodle_quiz_url = "/mod/sloodle/mod/quiz-1.0/linker.php";
          integer ANIM_CHANNEL = -99;
                integer XY_FLAG_CHANNEL = -92811;
                        integer scoreboardchannel;
        
                integer PLUGIN_RESPONSE_CHANNEL = 998822;
        integer PLUGIN_CHANNEL = 998821;
                string myQuizName;
                integer myQuizId;
                integer gameid;

                integer UI_CHANNEL = 89997;
                
                // ID and name of the current quiz
                integer quizid = -1;
                string quizname = "";
            
                               
        key httpquizquery = null_key;
        
        float request_timeout = 20.0;
              vector RED = <0.77278,4.391e-2,0.0>;
        vector WHITE = <1.0,1.0,1.0>;
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
        
        
        ///// TRANSLATION /////
        
        // Link message channels
        integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
        string SLOODLE_TRANSLATE_SAY = "say";
        string SLOODLE_TRANSLATE_IM = "instantmessage";
         /***********************************************
    *  random_integer()
    *  |-->Produces a random integer
    ***********************************************/ 
    integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
    ///// FUNCTIONS /////
            /***********************************************************************************************
            *  s()  k() i() and v() are used so that sending messages is more readable by humans.  
            * Ie: instead of sending a linked message as
            *  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
            *  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
            *  All these functions do is strip off the text before the ":" char and return a string
            ***********************************************************************************************/
            string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
            key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}
            integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}
        debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == PRIM_MATERIAL_FLESH)) {
        llOwnerSay(((llGetScriptName() + " ") + str));
    }
}
        
        
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
        sloodle_error_code(string method,key avuuid,integer statuscode){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST,((((method + "|") + ((string)avuuid)) + "|") + ((string)statuscode)),NULL_KEY);
}
        sloodle_debug(string msg){
    llMessageLinked(LINK_THIS,DEBUG_CHANNEL,msg,null_key);
}

        // Configure by receiving a linked message from another script in the object
        // Returns TRUE if the object has all the data it needs
        integer sloodle_handle_command(string str){
    if (((str == "do:requestconfig") || (str == "do:reset"))) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlepwd")) {
        if ((value2 != "")) (sloodlepwd = ((value1 + "|") + value2));
        else  (sloodlepwd = value1);
    }
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlemoduleid")) (sloodlemoduleid = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == "set:sloodleserveraccesslevel")) (sloodleserveraccesslevel = ((integer)value1));
    else  if ((name == "set:scoreboard")) {
        (currentAwardId = ((integer)value1));
    }
    else  if ((name == "points")) (points = ((integer)value1));
    else  if ((name == "set:scoreboardchannel")) (scoreboardchannel = ((integer)value1));
    else  if ((name == "set:sloodlerepeat")) (doRepeat = ((integer)value1));
    else  if ((name == "set:sloodlerandomize")) (doRandomize = ((integer)value1));
    else  if ((name == "set:sloodledialog")) (doDialog = ((integer)value1));
    else  if ((name == "set:sloodleplaysound")) (doPlaySound = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
        // Checks if the given agent is permitted to user this object
        // Returns TRUE if so, or FALSE if not
        integer sloodle_check_access_use(key id){
    if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP)) {
        return llSameGroup(id);
    }
    else  if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC)) {
        return TRUE;
    }
    return (id == llGetOwner());
}

        
        // Report completion to the user
        finish_quiz(){
    sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"complete",[llKey2Name(sitter),((((string)num_correct) + "/") + ((string)num_questions))],sitter,"quiz");
    string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
    (body += ("&sloodlepwd=" + sloodlepwd));
    (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
    (body += ("&sloodleuuid=" + ((string)sitter)));
    (body += ("&sloodleavname=" + llEscapeURL(llKey2Name(sitter))));
    (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
    (body += "&finishattempt=1");
    llHTTPRequest((sloodleserverroot + sloodle_quiz_url),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body);
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_COMPLETED_FOR_AVATAR,((((string)num_correct) + "/") + ((string)num_questions)),sitter);
}
        
        // Reinitialise (e.g. after one person has finished an attempt)
        reinitialise(){
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],null_key,"");
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:requestconfig",null_key);
    llResetScript();
}
        
        // Send a translation request link message
        
        sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}
        
        ///// ----------- /////
        
        
        ///// STATES /////
        
        // Waiting on initialisation
        default {

            state_entry() {
        (SLOODLE_CHANNEL_AVATAR_DIALOG = random_integer((-20000),(-30000)));
        (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
        llTriggerSound("flag_down",1.0);
        llTriggerSound("5992af3b-fde1-309f-4077-2c8d5cb7153c",1.0);
        llMessageLinked(LINK_SET,ANIM_CHANNEL,"p0",NULL_KEY);
        llMessageLinked(LINK_SET,XY_FLAG_CHANNEL,"Resetting...",NULL_KEY);
        llUnSit(llGetLinkKey(9));
        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)RED)),NULL_KEY);
        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_flag|" + ((string)RED)),NULL_KEY);
        (groups = []);
        llSetText("",<0.0,0.0,0.0>,0.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleserveraccesslevel = 0);
        (doRepeat = 1);
        (doDialog = 1);
        (doPlaySound = 1);
        (doRandomize = 1);
    }

            //chair will go to ready state when SLOODLE_EOF is read from notecard
            link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == TRUE)) state ready;
        }
        if ((num == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "BUTTON PRESS")) {
                if ((k(llList2String(cmdList,2)) == llGetOwner())) {
                    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:requestconfig",null_key);
                }
            }
        }
    }
}
        
        // Ready state - waiting for a user to climb aboard!
        state ready {

            state_entry() {
        debug("ready");
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],null_key,"");
        llMessageLinked(LINK_SET,XY_FLAG_CHANNEL,"Ready...",NULL_KEY);
        llListenRemove(listenHandle);
        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)WHITE)),NULL_KEY);
        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_flag|" + ((string)WHITE)),NULL_KEY);
        llTriggerSound("2622d9a8-0582-d7fc-c340-45d9a844d12f",1.0);
    }

            
            // Wait for the script that handles the sitting to tell us that somebody has sat on us.
            // Normally a sit will immediately produce a link message
            // But variations on the script may do things differently, 
            // eg. the awards script doesn't want to start the quiz until it's got a Game ID
            link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        if ((num == SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR)) {
            (sitter = id);
            if ((!sloodle_check_access_use(sitter))) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[llKey2Name(sitter)],null_key,"");
                llUnSit(sitter);
                (sitter = null_key);
                return;
            }
            (startingposition = llGetPos());
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"starting",[llKey2Name(sitter)],null_key,"quiz");
            llSetTimerEvent(10);
            (joingame = FALSE);
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"joinattempt",[llKey2Name(sitter)],null_key,"quiz");
            llMessageLinked(LINK_THIS,UI_CHANNEL,"cmd:GET GAMEID",null_key);
        }
        if ((num == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "GAMEID")) {
                (gameid = i(llList2String(cmdList,1)));
                (myQuizName = s(llList2String(cmdList,3)));
                (myQuizId = i(llList2String(cmdList,4)));
                (authenticatedUser = ((("&sloodleuuid=" + ((string)sitter)) + "&sloodleavname=") + llEscapeURL(llKey2Name(sitter))));
                llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("awards->joingame" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&gameid=") + ((string)gameid)) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))),llGetScriptName());
            }
            else  if ((cmd == "check quiz")) state check_quiz;
        }
        else  if ((num == PLUGIN_RESPONSE_CHANNEL)) {
            list dataLines = llParseStringKeepNulls(str,["\n"],[]);
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            string response = s(llList2String(dataLines,1));
            if ((response == "awards|joingame")) {
                if ((status == 1)) {
                    (joingame = TRUE);
                    llTriggerSound("98f60cae-636b-71c3-eff5-d6ac1201ff33",1.0);
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((("groups->getUsersGrps" + authenticatedUser) + "&avname=") + llEscapeURL(llKey2Name(sitter))) + "&avuuid=") + ((string)sitter)),NULL_KEY);
                }
                else  {
                    sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
                    llUnSit(sitter);
                    reinitialise();
                }
            }
            if (((response == "groups|getUsersGrps") || (response == "groups|addToRandomGrp"))) {
                if ((status == 1)) state check_quiz;
            }
        }
    }

             timer() {
        llSetTimerEvent(0);
        if ((!joingame)) llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:requestconfig",NULL_KEY);
    }


            on_rez(integer par) {
        llResetScript();
    }
}
        
        
        // Fetching the general quiz data
        state check_quiz {

            state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"fetchingquiz",[],null_key,"quiz");
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((((((("awards->addTransaction" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&sourceuuid=") + ((string)llGetOwner())) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))) + "&amount=") + ((string)0)) + "&currency=Credits&details=") + llEscapeURL((llKey2Name(sitter) + " is entering game"))),NULL_KEY);
        (quizname = "");
        (question_ids = []);
        (num_questions = 0);
        (active_question = (-1));
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)myQuizId)));
        (body += ("&sloodleuuid=" + ((string)sitter)));
        (body += ("&sloodleavname=" + llEscapeURL(llKey2Name(sitter))));
        (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
        (httpquizquery = llHTTPRequest((sloodleserverroot + sloodle_quiz_url),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        debug((((sloodleserverroot + sloodle_quiz_url) + "?") + body));
        llSetTimerEvent(0.0);
        llSetTimerEvent(((float)request_timeout));
    }

             link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
    }

            state_exit() {
        llSetTimerEvent(0.0);
    }

            
            timer() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httptimeout",[],null_key,"");
        state ready;
    }

            
            http_response(key id,integer status,list meta,string body) {
        debug(("http body is: " + body));
        if ((id != httpquizquery)) return;
        (httpquizquery = null_key);
        if ((status != 200)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            state default;
        }
        list lines = llParseString2List(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        (body = "");
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = ((integer)llStringTrim(llList2String(statusfields,0),STRING_TRIM));
        if ((statuscode == (-10301))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"noattemptsleft",[llKey2Name(sitter)],null_key,"");
            state ready;
            return;
        }
        else  if ((statuscode == (-10302))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"noquestions",[],null_key,"");
            state ready;
            return;
        }
        else  if ((statuscode <= 0)) {
            sloodle_error_code(SLOODLE_TRANSLATE_IM,sitter,statuscode);
            if ((numlines > 1)) sloodle_debug(llList2String(lines,1));
            state ready;
            return;
        }
        (statusfields = []);
        integer i;
        for ((i = 1); (i < numlines); (i++)) {
            string thislinestr = llList2String(lines,i);
            list thisline = llParseString2List(thislinestr,["|"],[]);
            string rowtype = llList2String(thisline,0);
            if ((rowtype == "quiz")) {
                (quizid = ((integer)llList2String(thisline,4)));
                (quizname = llList2String(thisline,2));
            }
            else  if ((rowtype == "quizpages")) {
                list question_ids_str = llCSV2List(llList2String(thisline,3));
                (num_questions = llGetListLength(question_ids_str));
                integer qiter = 0;
                (question_ids = []);
                for ((qiter = 0); (qiter < num_questions); (qiter++)) {
                    (question_ids += [((integer)llList2String(question_ids_str,qiter))]);
                }
                if (doRandomize) (question_ids = llListRandomize(question_ids,1));
                (active_question = 0);
            }
        }
        if (((quizname == "") || (num_questions == 0))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"noquestions",[],null_key,"quiz");
            debug("no questions");
            state default;
            return;
        }
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"ready",[quizname],null_key,"quiz");
        state quizzing;
    }

            
            on_rez(integer par) {
        llResetScript();
    }

            
            changed(integer change) {
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION,((string)startingposition),sitter);
    }
}
        
        
        // Running the quiz
        state quizzing {

            on_rez(integer param) {
        llResetScript();
    }

            
            state_entry() {
        llListen(scoreboardchannel,"","","GAMEID");
        llSetText("",<0.0,0.0,0.0>,0.0);
        (num_correct = 0);
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION,((string)startingposition),sitter);
        if ((num_questions == 0)) {
            sloodle_debug("No questions - cannot run quiz.");
            state default;
            return;
        }
        (active_question = 0);
        debug(("quizzing " + ((string)sitter)));
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_ASK_QUESTION,((string)llList2Integer(question_ids,active_question)),sitter);
        llSetTimerEvent(10.0);
    }

            
            link_message(integer sender_num,integer num,string str,key id) {
        if ((num == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            if ((cmd == "BUTTON PRESS")) {
                if ((button == "btn_desk")) if ((k(llList2String(cmdList,2)) == sitter)) {
                    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_ASK_QUESTION,((string)llList2Integer(question_ids,active_question)),sitter);
                }
            }
        }
        if ((num == SLOODLE_CHANNEL_QUESTION_ANSWERED_AVATAR)) {
            float scorechange = ((integer)str);
            if ((sitter != id)) {
                return;
            }
            (active_question++);
            if ((scorechange > 0)) {
                (num_correct++);
                llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((((((("awards->addTransaction" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&sourceuuid=") + ((string)llGetOwner())) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))) + "&amount=") + ((string)points)) + "&currency=Credits&details=") + llEscapeURL(("Quiz Chair Points," + llKey2Name(sitter)))),NULL_KEY);
            }
            if (((active_question + 1) >= num_questions)) {
                finish_quiz();
                if (doRepeat) state quizzing;
                return;
            }
            llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_ASK_QUESTION,((string)llList2Integer(question_ids,active_question)),sitter);
        }
        else  if ((num == SLOODLE_CHANNEL_QUESTION_ASKED_AVATAR)) {
            llSetTimerEvent(0.0);
        }
        else  if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
            return;
        }
    }

            
            state_exit() {
        llSetTimerEvent(0.0);
    }

            
            timer() {
        llSetTimerEvent(0.0);
        if ((active_question > (-1))) {
            llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_ASK_QUESTION,((string)llList2Integer(question_ids,active_question)),sitter);
            llSetTimerEvent(10.0);
        }
    }

            
          
  
            
            changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION,((string)startingposition),sitter);
            reinitialise();
        }
        if ((change == CHANGED_LINK)) {
            if ((llAvatarOnSitTarget() == NULL_KEY)) {
                llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION,((string)startingposition),sitter);
                reinitialise();
            }
        }
    }
}
