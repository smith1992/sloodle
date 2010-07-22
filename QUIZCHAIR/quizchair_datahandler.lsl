// LSL script generated: _SLOODLE_HOUSE.QUIZCHAIR.quizchair_datahandler.lslp Thu Jul 22 02:04:08 Pacific Daylight Time 2010
//_quizchair_datahandler
        
        
        integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
        key owner;
        string sloodleserverroot = "";
        integer sloodlecontrollerid = 0;
        string sloodlepwd = "";
        integer sloodlemoduleid = 0;
        integer sloodleobjectaccessleveluse = 0;
        integer sloodleserveraccesslevel = 0;
        integer isconfigured = FALSE;
        integer eof = FALSE;
        integer UI_CHANNEL = 89997;
        integer scoreboardchannel = -1;
        integer gameid = -1;
        
        string myQuizName;
        integer myQuizId;
        string authenticatedUser;
        
vector RED = <0.77278,4.391e-2,0.0>;
vector ORANGE = <0.8713,0.41303,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
vector BLUE = <0.0,5.804e-2,0.98688>;
vector PINK = <0.83635,0.0,0.88019>;
vector PURPLE = <0.39257,0.0,0.71612>;
list groups;
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay(((llGetScriptName() + " ") + str));
    }
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

   
         integer sloodle_handle_command(string str){
    if (((str == "do:requestconfig") || (str == "do:reset"))) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value1 = llList2String(bits,1);
    if ((name == "set:scoreboardchannel")) {
        (scoreboardchannel = ((integer)value1));
        return TRUE;
    }
    return FALSE;
}
        
        ///// ----------- /////
        
        
        ///// STATES /////
        
        // Waiting on initialisation
        default {

                on_rez(integer start_param) {
        llResetScript();
    }

            state_entry() {
        (owner = llGetOwner());
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleserveraccesslevel = 0);
    }

            
            link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == TRUE)) {
                state ready;
            }
        }
    }

            changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}

        
        // Ready state - waiting for a user to climb aboard!
        state ready {

                on_rez(integer start_param) {
        llResetScript();
    }

            state_entry() {
        llListen(scoreboardchannel,"","","");
        (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
    }

            
          listen(integer channel,string name,key id,string str) {
        if ((channel == scoreboardchannel)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "NEW GAME")) {
                (gameid = i(llList2String(cmdList,1)));
                (groups = llParseString2List(s(llList2String(cmdList,2)),[","],[]));
                (myQuizName = s(llList2String(cmdList,3)));
                (myQuizId = i(llList2String(cmdList,4)));
                (groups = llListSort(groups,1,TRUE));
                llMessageLinked(LINK_SET,UI_CHANNEL,((((((("CMD:GAMEID|ID:" + ((string)gameid)) + "|groups:") + llList2CSV(groups)) + "|myQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)),llGetScriptName());
            }
            else  if ((cmd == "SCOREBOARD SENDING GAME ID")) {
                if ((k(llList2String(cmdList,2)) == llGetKey())) {
                    (groups = llParseString2List(s(llList2String(cmdList,3)),[","],[]));
                    (myQuizName = s(llList2String(cmdList,4)));
                    (myQuizId = i(llList2String(cmdList,5)));
                    (groups = llListSort(groups,1,TRUE));
                    (gameid = i(llList2String(cmdList,1)));
                    debug(("+++++++++++++++++++++++++++" + str));
                    llSetText(((((("Game Id: " + ((string)gameid)) + "\nQuiz id: ") + ((string)myQuizId)) + "\nQuiz Name: ") + myQuizName),YELLOW,1.0);
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((((("CMD:GAMEID|ID:" + ((string)gameid)) + "|groups:") + llList2CSV(groups)) + "|myQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)),llGetScriptName());
                }
            }
        }
    }

          link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "GET GAMEID")) {
                if ((gameid == (-1))) {
                    llRegionSay(scoreboardchannel,("CMD:REQUEST GAME ID|UUID:" + ((string)llGetKey())));
                    debug(((("request game id: on " + ((string)scoreboardchannel)) + "   CMD:REQUEST GAME ID|UUID:") + ((string)llGetKey())));
                }
                else  {
                    (groups = llListSort(groups,1,TRUE));
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((((("CMD:GAMEID|ID:" + ((string)gameid)) + "|groups:") + llList2CSV(groups)) + "|myQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)),llGetScriptName());
                }
            }
        }
    }

        changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
        if ((change == CHANGED_LINK)) {
            if ((llAvatarOnSitTarget() == NULL_KEY)) llResetScript();
        }
    }
}
