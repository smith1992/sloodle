// LSL script generated: root_prim_board.response_handlers2.lslp Sat Mar 20 13:27:17 Pacific Daylight Time 2010
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* response_handlers2.lsl
* 
* This script also handles responses received as output from SLOODLE on the 
* PLUGIN_RESPONSE_CHANNEL  once a request has been received from the sloodle_api_new.lsl script.
*
* The reason we have two scripts for response handling to split the memory requirement limits imposed by Second Life 
*
* It also responds to messages sent on the UI_CHANNEL from other scripts in the system. These messages are:
* 
* AWARD SELECTED (Gets triggered during setup when user selects the award to display)
* SET CURRENT BUTTON (Gets triggered when a user clicks a button)
* UPDATE ARROWS (this is when the next/previous button is pressed so we know which page we are on)
* SET CURRENT GROUP (used when users are manipulating groups)
* GET CLASS LIST (A message sent when the class list is requested)
* UPDATE VIEW CLASS LIST 
* DISPLAY MENU  (This gets triggered when someone clicks on an XY_prim in a row of the scoreboard)
* 
* 
* 
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer ROW_CHANNEL;
string stringToPrint;
integer numStudents;
integer index;
integer index_teamScores;
integer index_getClassList;
integer DISPLAY_DATA = -774477;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer SETTEXT_CHANNEL = -776644;
integer XY_TITLE_CHANNEL = 600100;
integer XY_TEXT_CHANNEL = 100100;
integer UI_CHANNEL = 89997;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer PLAYERNAME = 0;
integer AVUUID = 3;
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
string sortMode = "balance";
list modifyPointList;
integer modPoints;
string displayData;
list facilitators;
integer SCOREBOARD_CHANNEL = -1;
vector PINK = <0.83635,0.0,0.88019>;
integer DEBUG = FALSE;
string SLOODLE_EOF = "sloodleeof";
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
/****************************************************************************************************
* center(string str) displays text on the title bar 
****************************************************************************************************/
center(string str){
    integer len = llStringLength(str);
    string spaces = "                    ";
    integer numSpacesForMargin = ((20 - len) / 2);
    string margin = llGetSubString(spaces,0,numSpacesForMargin);
    string stringToPrint = ((margin + str) + margin);
    llMessageLinked(LINK_SET,XY_TITLE_CHANNEL,stringToPrint,NULL_KEY);
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
displayModMenu(string name,string userPoints,string row_channel,string avKey){
    integer points = i(userPoints);
    integer channel = i(row_channel);
    string userName = s(name);
    integer rowNum = (channel - ROW_CHANNEL);
    key av_key = k(avKey);
    (modPoints = (points + llList2Integer(modifyPointList,rowNum)));
    if ((modPoints < 0)) (modPoints = 0);
    llDialog(llGetOwner(),(((((" -~~~ Modify iPoints awarded: " + ((string)userPoints)) + " ~~~-\n") + userName) + "\nModify Points to: ") + ((string)modPoints)),(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"] + ["~~ SAVE ~~"]),channel);
}
/***********************************************
*  makeTransaction(string userName,integer userPoints, integer row_channel)
*  makes a transaction for the user to the current award
******************************************************/
makeTransaction(string avname,key avuuid,integer points){
    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((((((("awards->makeTransaction" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&sourceuuid=") + ((string)owner)) + "&avuuid=") + ((string)avuuid)) + "&avname=") + llEscapeURL(avname)) + "&points=") + ((string)points)) + "&details=") + llEscapeURL(((((("owner modified ipoints,OWNER:" + llKey2Name(owner)) + ",SCOREBOARD:") + ((string)llGetKey())) + ",SCOREBOARDNAME:") + llGetObjectName()))),NULL_KEY);
}
 integer sloodle_handle_command(string str){
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((name == "facilitator")) {
        (facilitators += llStringTrim(llToLower(value1),STRING_TRIM));
    }
    else  if ((name == "SCOREBOARD_CHANNEL")) {
        (SCOREBOARD_CHANNEL = ((integer)value1));
        debug(("*******************GOT SCOREBOARD CHANNEL: " + ((string)SCOREBOARD_CHANNEL)));
        llListen(SCOREBOARD_CHANNEL,"","","");
        debug(("listening to: " + ((string)SCOREBOARD_CHANNEL)));
    }
    else  if ((name == SLOODLE_EOF)) {
        if ((SCOREBOARD_CHANNEL != (-1))) return TRUE;
        else  {
            integer rnd = random_integer(20000,30000);
            llMessageLinked(LINK_SET,UI_CHANNEL,"CMD:SET CURRENT BUTTON|null:null|view:null",NULL_KEY);
            (stringToPrint = ("Please add the following to   sloodle_config:               SCOREBOARD_CHANNEL|" + ((string)rnd)));
            llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
            llOwnerSay(stringToPrint);
            return FALSE;
        }
    }
    return FALSE;
}
default {

    state_entry() {
        (owner = llGetOwner());
        (ROW_CHANNEL = random_integer(2483000,3483000));
        integer c = 0;
        for ((c = 0); (c < 10); (c++)) {
            llListen((ROW_CHANNEL + c),"","","");
        }
        (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
        (facilitators += llKey2Name(llToLower(llGetOwner())));
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
            else  if ((command == "GET CLASS LIST")) {
                if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                    (index = i(llList2String(dataBits,1)));
                    (sortMode = s(llList2String(dataBits,2)));
                    key avuuid = k(llList2String(dataBits,3));
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)avuuid)) + "&sloodleavname=") + llEscapeURL(llKey2Name(avuuid))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((("user->getClassList" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&index=") + ((string)index)) + "&sortmode=") + sortMode),NULL_KEY);
                }
            }
            else  if (((command == "UPDATE VIEW CLASS LIST") || (command == "UPDATE DISPLAY"))) {
                if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((("user->getClassList" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_getClassList)) + "&sortmode=") + sortMode),NULL_KEY);
                }
                else  if ((currentView == "Team Top Scores")) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getTeamScores" + authenticatedUser) + "&sloodleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_teamScores)) + "&maxitems=9&sortmode=balance"),NULL_KEY);
                }
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
                        displayModMenu(("NAME:" + llList2String(user,0)),("POINTS:" + llList2String(user,1)),("CHANNEL:" + llList2String(user,2)),("AVKEY:" + llList2String(user,3)));
                        (user = []);
                        (dataBits = []);
                        (command = "");
                    }
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
            if ((response == "user|getClassList")) {
                llMessageLinked(LINK_SET,SETTEXT_CHANNEL,(("DISPLAY::userUpdate display|STRING::                                   |COLOR::" + ((string)PINK)) + "|ALPHA::1.0"),NULL_KEY);
                (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
                string senderUuid = s(llList2String(dataLines,2));
                (index_getClassList = i(llList2String(dataLines,3)));
                (numStudents = i(llList2String(dataLines,4)));
                (sortMode = s(llList2String(dataLines,5)));
                list userLines = llList2List(dataLines,6,(llGetListLength(dataLines) - 1));
                if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                    if ((sortMode == "name")) {
                        center("Scoreboard");
                    }
                    else  {
                        if ((sortMode == "balance")) center("Top Scores");
                    }
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index_getClassList)) + "|TOTALITEMS:") + ((string)numStudents)),NULL_KEY);
                    string stringToPrint = "";
                    integer len = llGetListLength(userLines);
                    (displayData = (("CURRENT VIEW:" + currentView) + "\n"));
                    (rows_getClassList = []);
                    for ((counter = 0); (counter < len); (counter++)) {
                        list user = llParseString2List(llList2String(userLines,counter),["|"],[]);
                        integer userPoints = i(llList2String(user,2));
                        string userName = s(llList2String(user,1));
                        key userKey = k(llList2String(user,0));
                        (displayData += ((((((string)userKey) + "|") + userName) + "|") + ((string)userPoints)));
                        if ((counter != len)) (displayData += "\n");
                        (rows_getClassList = llListReplaceList(rows_getClassList,[userName,userPoints,(ROW_CHANNEL + counter),llStringTrim(s(llList2String(user,0)),STRING_TRIM)],(counter * 4),((counter * 4) + 4)));
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
                    (stringToPrint = "");
                }
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
        if ((channel == SCOREBOARD_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string avname = s(llList2String(cmdList,1));
            debug(("got button press: " + str));
            if ((cmd == "UPDATE")) {
                if ((llSubStringIndex(displayData,avname) != (-1))) {
                    llMessageLinked(LINK_SET,UI_CHANNEL,("COMMAND:BUTTON PRESS|BUTTON:Students Tab|AVUUID:" + ((string)llGetOwner())),NULL_KEY);
                }
            }
        }
        else  if (((channel >= ROW_CHANNEL) && (channel <= (ROW_CHANNEL + 10)))) {
            integer rowNum = (channel - ROW_CHANNEL);
            if ((llListFindList(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],[str]) != (-1))) {
                list user = llList2List(rows_getClassList,(rowNum * 4),((rowNum * 4) + 3));
                string avKey = llList2String(user,3);
                integer currentPoints = llList2Integer(user,1);
                integer newAmount = (llList2Integer(modifyPointList,rowNum) + ((integer)str));
                (modifyPointList = llListReplaceList(modifyPointList,[newAmount],rowNum,rowNum));
                displayModMenu(("NAME:" + llList2String(user,0)),("POINTS:" + llList2String(user,1)),("CHANNEL:" + llList2String(user,2)),("AVKEY:" + avKey));
                (user = []);
            }
            else  if ((str == "~~ SAVE ~~")) {
                list user = llList2List(rows_getClassList,(rowNum * 4),((rowNum * 4) + 3));
                key avuuid = llList2Key(user,AVUUID);
                string avname = llList2String(user,PLAYERNAME);
                integer points = llList2Integer(modifyPointList,rowNum);
                (modifyPointList = llListReplaceList(modifyPointList,[0],rowNum,rowNum));
                makeTransaction(avname,avuuid,points);
            }
        }
    }
}
// Please leave the following line intact to show where the script lives in Subversion:
// SLOODLE LSL Script Subversion Location: mod/awards-1.0/lsl/root_prim_board/response_handlers2.lsl 
