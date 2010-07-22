// LSL script generated: _SLOODLE_HOUSE.scoreboard._responseHandler2.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010
//* response_handlers2.lsl
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
integer ROW_CHANNEL;
string stringToPrint;
integer numStudents;
integer index;
integer index_getClassList;
integer DISPLAY_DATA = -774477;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer SETTEXT_CHANNEL = -776644;
integer XY_TITLE_CHANNEL = 600100;
integer XY_TEXT_CHANNEL = 100100;
integer UI_CHANNEL = 89997;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer MAX_XY_LETTER_SPACE = 30;
string authenticatedUser;
integer counter;
integer currentAwardId;
list dataLines;
key owner;
string currentView;
list rows_getClassList;
integer currentIndex;
string currentGroup;
list modifyPointList;
integer modPoints;
string displayData;
list facilitators;
vector PINK = <0.83635,0.0,0.88019>;

string SLOODLE_EOF = "sloodleeof";
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
        debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == PRIM_MATERIAL_FLESH)) {
        llOwnerSay(((llGetScriptName() + " ") + str));
    }
}

/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if ((llListFindList(facilitators,[llStringTrim(llToLower(avName),STRING_TRIM)]) == (-1))) return FALSE;
    else  return TRUE;
}
left(string str){
    llMessageLinked(LINK_SET,XY_TITLE_CHANNEL,str,NULL_KEY);
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
    (modPoints = (points + llList2Integer(modifyPointList,rowNum)));
    if ((modPoints < 0)) (modPoints = 0);
    if ((isFacilitator(llKey2Name(k(avKey))) == FALSE)) return;
    llDialog(k(avKey),(((((" -~~~ Modify iPoints awarded: " + ((string)userPoints)) + " ~~~-\n") + userName) + "\nModify Points to: ") + ((string)modPoints)),["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],channel);
}
/***********************************************
*  makeTransaction(string userName,integer userPoints, integer row_channel)
*  makes a transaction for the user to the current award
******************************************************/
makeTransaction(string avname,key avuuid,integer points){
    (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((((((("awards->addTransaction" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&sourceuuid=") + ((string)llGetOwner())) + "&avuuid=") + ((string)avuuid)) + "&avname=") + llEscapeURL(llKey2Name(avuuid))) + "&amount=") + ((string)points)) + "&currency=Credits&details=") + llEscapeURL(("Game Points," + llKey2Name(avuuid)))),NULL_KEY);
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
    else  if ((name == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
default {

//on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();
    }

 
    state_entry() {
        (owner = llGetOwner());
        (ROW_CHANNEL = random_integer((-2483000),(-3483000)));
        integer c = 0;
        for ((c = 0); (c < 10); (c++)) {
            llListen((ROW_CHANNEL + c),"","","");
        }
        (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        else  if ((channel == UI_CHANNEL)) {
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if ((command == "AWARD SELECTED")) {
                (currentAwardId = i(llList2String(dataBits,1)));
            }
            else  if ((command == "SET CURRENT BUTTON")) {
                (currentView = s(llList2String(dataBits,2)));
            }
            else  if ((command == "UPDATE ARROWS")) {
                (currentView = s(llList2String(dataBits,1)));
                (currentIndex = i(llList2String(dataBits,2)));
            }
            else  if ((command == "SET CURRENT GROUP")) {
                (currentGroup = s(llList2String(dataBits,1)));
            }
            else  if ((command == "DISPLAY MENU")) {
                integer rowNum = i(llList2String(dataBits,1));
                key av = k(llList2String(dataBits,2));
                if (isFacilitator(llKey2Name(av))) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)av)) + "&sloodleavname=") + llEscapeURL(llKey2Name(av))));
                    if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                        (rowNum = i(llList2String(dataBits,1)));
                        list user = llList2List(rows_getClassList,(rowNum * 4),((rowNum * 4) + 3));
                        debug(((((((("NAME:" + llList2String(user,0)) + " POINTS:") + llList2String(user,1)) + " CHANNEL:") + llList2String(user,2)) + " AVKEY:") + llList2String(user,3)));
                        displayModMenu(("NAME:" + llList2String(user,0)),("POINTS:" + llList2String(user,1)),("CHANNEL:" + llList2String(user,2)),("AVKEY:" + ((string)av)));
                        (user = []);
                        (dataBits = []);
                        (command = "");
                    }
                }
                else  llOwnerSay("******************************* Sorry, not a facilitator");
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
            if ((response == "awards|getPlayerScores")) {
                llMessageLinked(LINK_SET,SETTEXT_CHANNEL,(("DISPLAY::userUpdate display|STRING::                                   |COLOR::" + ((string)PINK)) + "|ALPHA::1.0"),NULL_KEY);
                (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
                (index_getClassList = i(llList2String(dataLines,2)));
                (numStudents = i(llList2String(dataLines,3)));
                list userLines = llList2List(dataLines,4,(llGetListLength(dataLines) - 1));
                if ((status == 80002)) {
                    (stringToPrint = "No players have joined the     game yet.");
                    llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                    llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                }
                else  if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                    left("Top Scores");
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index_getClassList)) + "|TOTALITEMS:") + ((string)numStudents)),NULL_KEY);
                    string stringToPrint = "";
                    integer len = llGetListLength(userLines);
                    (displayData = (("CURRENT VIEW:" + currentView) + "\n"));
                    (rows_getClassList = []);
                    for ((counter = 0); (counter < len); (counter++)) {
                        list user = llParseString2List(llList2String(userLines,counter),["|"],[]);
                        integer userPoints = i(llList2String(user,2));
                        string userName = s(llList2String(user,0));
                        key userKey = k(llList2String(user,1));
                        (displayData += ((((((string)userKey) + "|") + userName) + "|") + ((string)userPoints)));
                        if ((counter != len)) (displayData += "\n");
                        (rows_getClassList = llListReplaceList(rows_getClassList,[userName,userPoints,(ROW_CHANNEL + counter),llStringTrim(userKey,STRING_TRIM)],(counter * 4),((counter * 4) + 4)));
                        if ((llStringLength(userName) > 20)) {
                            (userName = llGetSubString(userName,0,19));
                        }
                        integer spaceLen = (MAX_XY_LETTER_SPACE - (((llStringLength(((string)((index_getClassList + counter) + 1))) + 2) + llStringLength(userName)) + llStringLength(((string)userPoints))));
                        string text = ((((string)((index_getClassList + counter) + 1)) + ") ") + userName);
                        (text += (llGetSubString("                              ",0,(spaceLen - 1)) + ((string)userPoints)));
                        (stringToPrint += text);
                        (text = "");
                    }
                    llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                    llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                }
                (stringToPrint = "");
                return;
            }
            else  if ((response == "awards|makeTransaction")) {
                key sourceUuid = k(llList2String(dataLines,2));
                key avUuid = k(llList2String(dataLines,3));
                string avName = s(llList2String(dataLines,4));
                integer points = i(llList2String(dataLines,5));
                integer rowNum = (llListFindList(rows_getClassList,[avName]) / 4);
                integer rowChannel = (ROW_CHANNEL + rowNum);
                if ((rowNum != (-1))) {
                    (rows_getClassList = llListReplaceList(rows_getClassList,[avName,points,rowChannel,avUuid],(rowNum * 4),((rowNum * 4) + 3)));
                }
            }
        }
    }

     listen(integer channel,string name,key id,string str) {
        if (((channel >= ROW_CHANNEL) && (channel <= (ROW_CHANNEL + 10)))) {
            if ((isFacilitator(llKey2Name(id)) == FALSE)) return;
            integer rowNum = (channel - ROW_CHANNEL);
            if ((llListFindList(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],[str]) != (-1))) {
                list user = llList2List(rows_getClassList,(rowNum * 4),((rowNum * 4) + 3));
                string avKey = llList2String(user,3);
                integer currentPoints = llList2Integer(user,1);
                makeTransaction(llKey2Name(avKey),avKey,((integer)str));
                (user = []);
            }
        }
    }
}
