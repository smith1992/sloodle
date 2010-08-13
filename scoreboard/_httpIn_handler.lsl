// LSL script generated: avatar_classroom.scoreboard._httpIn_handler.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010

integer gameid;
string SLOODLE_EOF = "sloodleeof";
integer DISPLAY_DATA = -774477;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer UI_CHANNEL = 89997;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer WEB_UPDATE_CHANNEL = -64000;
integer currentAwardId;
key owner;
string currentView;
string myUrl;
string displayData;
string authenticatedUser;
string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
list facilitators;
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
 integer sloodle_handle_command(string str){
    if ((str == "do:requestconfig")) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "facilitator")) (facilitators += llStringTrim(llToLower(value1),STRING_TRIM));
    else  if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlecoursename_short")) (sloodlecoursename_short = value1);
    else  if ((name == "set:sloodlecoursename_full")) (sloodlecoursename_full = value1);
    else  if ((name == "set:sloodleid")) {
        (sloodleid = ((integer)value1));
        (currentAwardId = sloodleid);
    }
    else  if ((name == "set:sloodleid")) (scoreboardname = value2);
    else  if ((str == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
    
getUrl(){
    string oldUrl = llGetObjectDesc();
    if ((oldUrl != "")) {
        (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((("awards->deregisterScoreboard" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&url=") + oldUrl) + "&name=") + llEscapeURL(llGetObjectName())),NULL_KEY);
    }
    else  llRequestURL();
}
default {

    on_rez(integer start_param) {
        llSetObjectDesc("");
        llResetScript();
    }

    state_entry() {
        (owner = llGetOwner());
        (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == FALSE)) return;
            (str = "");
            (channel = (-1));
        }
        else  if ((channel == DISPLAY_DATA)) {
            (displayData = str);
        }
        else  if ((channel == UI_CHANNEL)) {
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if ((command == "GAMEID")) {
                (gameid = i(llList2String(dataBits,1)));
                getUrl();
            }
            else  if ((command == "GET URL")) {
                getUrl();
            }
            else  if ((command == "SET CURRENT BUTTON")) {
                (currentView = s(llList2String(dataBits,2)));
            }
            else  if ((command == "UPDATE ARROWS")) {
                (currentView = s(llList2String(dataBits,1)));
            }
        }
        else  if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            list dataLines = llParseString2List(str,["\n"],[]);
            string response = s(llList2String(dataLines,1));
            if ((response == "awards|registerScoreboard")) {
                llTriggerSound("3a109147-5565-4843-e647-20addd884fe7",1.0);
                string url = llList2String(dataLines,2);
                llMessageLinked(LINK_SET,PLUGIN_RESPONSE_CHANNEL,("COMMAND:REGISTERED SCOREBOARD|" + url),NULL_KEY);
                llMessageLinked(LINK_SET,UI_CHANNEL,"COMMAND:UPDATE DISPLAY",NULL_KEY);
                llTriggerSound("3a109147-5565-4843-e647-20addd884fe7",1.0);
            }
            else  if ((response == "awards|deregisterScoreboard")) {
                llRequestURL();
            }
        }
    }

     http_request(key id,string method,string body) {
        if ((method == URL_REQUEST_GRANTED)) {
            (myUrl = body);
            llSetObjectDesc(myUrl);
            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((("awards->registerScoreboard" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&url=") + myUrl) + "&type=scoreboard&name=") + llEscapeURL(llGetObjectName())),NULL_KEY);
        }
        else  if ((method == "POST")) {
            debug(("------------------------------------------------------------got post: " + body));
            list bodyData = llParseString2List(body,["\n"],[]);
            string cmd = s(llList2String(bodyData,0));
            if (((cmd == "UPDATE DISPLAY") || (cmd == "GET DISPLAY DATA"))) {
                llTriggerSound("sound bleepy computer",1.0);
                llMessageLinked(LINK_SET,UI_CHANNEL,"COMMAND:UPDATE DISPLAY",NULL_KEY);
                string responseText = "UPDATED";
                llHTTPResponse(id,200,responseText);
                llMessageLinked(LINK_SET,WEB_UPDATE_CHANNEL,body,NULL_KEY);
            }
        }
        else  {
        }
    }

     /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
