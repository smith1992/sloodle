// LSL script generated: avatar_classroom2.secondlife_lsl_code_port.scoreboard.scoreboard_public_data.lslp Wed Aug 18 19:07:06 Pacific Daylight Time 2010
//scoreboard_public_data
    integer gameid;
    integer scoreboardchannel = -1;
    integer UI_CHANNEL = 89997;
    integer PLUGIN_RESPONSE_CHANNEL = 998822;
    integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
    integer XY_GAMEID_CHANNEL = 1700100;
    integer XY_QUIZ_CHANNEL = -1800100;
    integer myQuizId;
    string myQuizName;
    list groups;
    integer isconfigured;
    list dataLines;
     debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == PRIM_MATERIAL_FLESH)) {
        llOwnerSay(((llGetScriptName() + " ") + str));
    }
}
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
    integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}
    left(string str){
    llMessageLinked(LINK_SET,XY_GAMEID_CHANNEL,str,NULL_KEY);
}
          // Configure by receiving a linked message from another script in the object
            // Returns TRUE if the object has all the data it needs
            integer sloodle_handle_command(string str){
    if ((str == "do:requestconfig")) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value1 = llList2String(bits,1);
    if ((name == "set:scoreboardchannel")) (scoreboardchannel = ((integer)value1));
    return (scoreboardchannel < (-100));
}
    
    default {

        on_rez(integer start_param) {
        llResetScript();
    }

        link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "GAMEID")) {
                (gameid = i(llList2String(cmdList,1)));
                (myQuizId = i(llList2String(cmdList,2)));
                (myQuizName = s(llList2String(cmdList,3)));
                left(("Game: " + ((string)gameid)));
            }
            else  if ((cmd == "GOT QUIZ ID")) {
                (myQuizId = i(llList2String(cmdList,1)));
                (myQuizName = s(llList2String(cmdList,2)));
            }
        }
        else  if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            (dataLines = []);
            (dataLines = llParseString2List(str,["\n"],[]));
            (isconfigured = FALSE);
            integer numLines = llGetListLength(dataLines);
            integer i;
            for ((i = 0); (i < numLines); (i++)) {
                if ((sloodle_handle_command(llList2String(dataLines,i)) == TRUE)) state ready;
            }
        }
    }
}
    
    state ready {

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        debug(("(((((((((((((((((((((( listening to: " + ((string)scoreboardchannel)));
        llListen(scoreboardchannel,"","","");
    }

    listen(integer channel,string name,key id,string str) {
        if ((channel == scoreboardchannel)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "REQUEST GAME ID")) {
                debug(((((((((("CMD:SCOREBOARD SENDING GAME ID|ID:" + ((string)gameid)) + "|UUID:") + s(llList2String(cmdList,1))) + "|groups:") + llList2CSV(groups)) + "|myQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)));
                llRegionSay(scoreboardchannel,((((((((("CMD:SCOREBOARD SENDING GAME ID|ID:" + ((string)gameid)) + "|UUID:") + s(llList2String(cmdList,1))) + "|groups:") + llList2CSV(groups)) + "|myQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)));
            }
        }
    }

     link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        list dataLines = llParseString2List(str,["\n"],[]);
        string responseLine = s(llList2String(dataLines,1));
        list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
        integer status = llList2Integer(statusLine,0);
        if ((channel == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "GOT QUIZ ID")) {
                (myQuizName = s(llList2String(cmdList,2)));
                (myQuizId = i(llList2String(cmdList,1)));
            }
        }
        if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            if ((responseLine == "quiz|newQuizGame")) {
                (gameid = i(llList2String(dataLines,2)));
                (myQuizName = s(llList2String(dataLines,3)));
                (myQuizId = i(llList2String(dataLines,4)));
                llMessageLinked(LINK_SET,XY_QUIZ_CHANNEL,myQuizName,"0");
                left(("Game: " + ((string)gameid)));
                llMessageLinked(LINK_SET,UI_CHANNEL,((((("CMD:GAMEID|ID:" + ((string)gameid)) + "|qname:") + myQuizName) + "|id:") + ((string)myQuizId)),NULL_KEY);
                llMessageLinked(LINK_SET,UI_CHANNEL,("CMD:BUTTON PRESS|BUTTON:Students Tab|UUID:" + ((string)llGetOwner())),NULL_KEY);
                llRegionSay(scoreboardchannel,((((((("CMD:NEW GAME|ID:" + ((string)gameid)) + "|GROUPS:") + llList2CSV(groups)) + "|MyQuizName:") + myQuizName) + "|QUIZID:") + ((string)myQuizId)));
            }
        }
        if ((responseLine == "awards|getTeamPlayerScores")) {
            (groups = []);
            string data = llList2String(dataLines,4);
            integer totalGroups = i(llList2String(dataLines,3));
            list grpsData = llParseString2List(data,["|"],[]);
            integer len = llGetListLength(grpsData);
            integer counter;
            debug(("--------------------------------------------------" + str));
            debug(("--------------------------------------------------" + llList2CSV(grpsData)));
            for ((counter = 0); (counter < len); (counter++)) {
                list grpData = llParseString2List(llList2String(grpsData,counter),[","],[]);
                string grpName = s(llList2String(grpData,0));
                (groups += grpName);
                integer grpPoints = i(llList2String(grpData,1));
            }
            debug(("--------------------------------------------------" + llList2CSV(groups)));
        }
    }
}
