// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.buzzer._buzzer_response.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* response_handlers2.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer ROW_CHANNEL;
integer index;
integer scoreboardchannel = -1;
integer UI_CHANNEL = 89997;
integer gameid = -1;
string myQuizName;
integer myQuizId;
list groups;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
list wNames;
string authenticatedUser;
integer currentAwardId;
list dataLines;
key owner;
list rows_getClassList;
list modifyPointList;
integer modPoints;
list facilitators;
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_EOF = "sloodleeof";
vector YELLOW = <0.82192,0.86066,0.0>;
integer DEBUG = FALSE;

string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
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
debug(string s){
    if ((DEBUG == TRUE)) llOwnerSay(((((((string)llGetFreeMemory()) + " ") + llGetScriptName()) + "*** ") + s));
    (s = "");
}

/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if ((llListFindList(facilitators,[llStringTrim(llToLower(avName),STRING_TRIM)]) == (-1))) return FALSE;
    else  return TRUE;
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
/***********************************************
*  displayModMenu(string userName,integer userPoints, integer row_channel)
*  Is used to display a dialog menu so owner can modify the points awarded 
***********************************************/
displayModMenu(string name,string userPoints,string row_channel,key avKey){
    integer points = i(userPoints);
    integer channel = i(row_channel);
    string userName = s(name);
    integer rowNum = (channel - ROW_CHANNEL);
    key av_key = k(avKey);
    (modPoints = llList2Integer(modifyPointList,rowNum));
    if ((modPoints < 0)) (modPoints = 0);
    if ((isFacilitator(llKey2Name(k(avKey))) == FALSE)) return;
    llDialog(k(avKey),((((" -~~~ Award Points: " + " ~~~-\n") + userName) + "\nPoints: ") + ((string)modPoints)),["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],channel);
}
/***********************************************
*  makeTransaction(string userName,integer userPoints, integer row_channel)
*  makes a transaction for the user to the current award
******************************************************/

makeTransaction(string avname,key avuuid,integer points){
    (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((((((("awards->addTransaction" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&sourceuuid=") + ((string)llGetOwner())) + "&avuuid=") + ((string)avuuid)) + "&avname=") + llEscapeURL(llKey2Name(avuuid))) + "&amount=") + ((string)points)) + "&currency=Credits&details=") + llEscapeURL(("Game BUZZER Points," + llKey2Name(avuuid)))),NULL_KEY);
}

 integer sloodle_handle_command(string str){
    debug(str);
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "set:scoreboardchannel")) {
        (scoreboardchannel = ((integer)value1));
    }
    else  if ((name == "facilitator")) (facilitators += llStringTrim(llToLower(value1),STRING_TRIM));
    else  if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlecoursename_short")) (sloodlecoursename_short = value1);
    else  if ((name == "set:sloodlecoursename_full")) (sloodlecoursename_full = value1);
    else  if ((name == "set:sloodleid")) {
        (sloodleid = ((integer)value1));
        (currentAwardId = sloodleid);
        (scoreboardname = value2);
    }
    else  if ((name == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
    sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}
default {

	 on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
    }

     link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == TRUE)) state ready;
        }
    }
}
state ready {

	 on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],NULL_KEY,"buzzer");
        llListen(scoreboardchannel,"","","");
        debug(("listening to: " + ((string)scoreboardchannel)));
        llRegionSay(scoreboardchannel,("CMD:REQUEST GAME ID|UUID:" + ((string)llGetKey())));
        (wNames = ["none","none","none"]);
        (owner = llGetOwner());
        (ROW_CHANNEL = random_integer((-2483000),(-3483000)));
        integer c = 0;
        for ((c = 0); (c < 3); (c++)) {
            llListen((ROW_CHANNEL + c),"","","");
        }
        (modifyPointList = [0,0,0]);
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
        (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == UI_CHANNEL)) {
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if ((command == "RowEntry")) {
                integer r = i(llList2String(dataBits,2));
                (wNames = llListReplaceList(wNames,[id],r,r));
            }
            else  if ((command == "reset points")) {
                (modifyPointList = [0,0,0]);
                (wNames = ["none","none","none"]);
            }
            else  if ((command == "DISPLAY MENU")) {
                integer rowNum = i(llList2String(dataBits,1));
                key av = k(llList2String(dataBits,2));
                if (isFacilitator(llKey2Name(av))) {
                    displayModMenu(("NAME:" + llKey2Name(llList2String(wNames,rowNum))),"POINTS:0",("CHANNEL:" + ((string)(ROW_CHANNEL + rowNum))),("AVKEY:" + ((string)av)));
                }
            }
        }
        else  if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            (dataLines = llParseStringKeepNulls(str,["\n"],[]));
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            key authUserUuid = llList2Key(statusLine,6);
            string response = s(llList2String(dataLines,1));
            (index = i(llList2String(dataLines,2)));
            integer totalGroups = i(llList2String(dataLines,3));
            string data = llList2String(dataLines,4);
            (authenticatedUser = ((("&sloodleuuid=" + ((string)authUserUuid)) + "&sloodleavname=") + llEscapeURL(llKey2Name(authUserUuid))));
            if ((response == "awards|addTransaction")) {
                key avUuid = k(llList2String(dataLines,3));
                string avName = s(llList2String(dataLines,2));
                integer points = i(llList2String(dataLines,6));
                integer rowNum = (llListFindList(rows_getClassList,[avName]) / 4);
                integer rowChannel = (ROW_CHANNEL + rowNum);
                (modifyPointList = [0,0,0]);
            }
        }
    }

     listen(integer channel,string name,key id,string str) {
        debug(str);
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
                debug(("********************************" + str));
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
        if (((channel >= ROW_CHANNEL) && (channel <= (ROW_CHANNEL + 3)))) {
            if ((isFacilitator(llKey2Name(id)) == FALSE)) return;
            integer rowNum = (channel - ROW_CHANNEL);
            if ((llListFindList(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],[str]) != (-1))) {
                key avKey = llList2String(wNames,rowNum);
                makeTransaction(llKey2Name(avKey),avKey,((integer)str));
            }
        }
    }
}
