// LSL script generated: avatar_classroom2.secondlife_lsl_code_port.scoreboard._responseHandler1.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010
//* response_handlers1.lsl
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* response_handlers1.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer ROW_CHANNEL;
string stringToPrint;
list userDetails;
integer index;
integer gameid;
integer index_teamScores;
string SCRIPT_NAME;
integer index_selectTeams;
integer DISPLAY_DATA = -774477;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer SETTEXT_CHANNEL = -776644;
integer XY_TEXT_CHANNEL = 100100;
integer XY_DETAILS_CHANNEL = 700100;
integer UI_CHANNEL = 89997;
integer PRIM_PROPERTIES_CHANNEL = -870870;
integer DISPLAY_BOX_CHANNEL = -870881;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer MAX_XY_LETTER_SPACE = 30;
string authenticatedUser;
integer counter;
string connected;
list awardGroups;
list courseGroups;
integer currentAwardId;
string current_grp_membership_group;
integer current_grp_mbr_index;
list dataLines;
key owner;
string currentView;
list rows_teamScores;
list rows_getAwardGrps;
list rows_getAwardGrpMbrs;
list rows_selectTeams;
list rows_selectAward;
integer previousAwardId;
integer selectedAwardId = 0;
integer currentIndex;
string currentGroup;
string sortMode = "balance";
list modifyPointList;
string displayData;
list facilitators;
// *************************************************** HOVER TEXT COLORS

vector RED = <0.77278,4.391e-2,0.0>;
vector ORANGE = <0.8713,0.41303,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
vector GREEN = <0.12616,0.77712,0.0>;
vector BLUE = <0.0,5.804e-2,0.98688>;
vector PINK = <0.83635,0.0,0.88019>;
vector PURPLE = <0.39257,0.0,0.71612>;
string SLOODLE_EOF = "sloodleeof";
 string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
string currentAwardName;
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
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay((((((llGetScriptName() + " ") + " freemem: ") + ((string)llGetFreeMemory())) + " ==== ") + str));
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

        
/***********************************************
*  clearHighlights -- makes sure all highlight rows are set to 0 alpha
***********************************************/
clearHighlights(){
    integer c;
    for ((c = 0); (c <= 9); (c++)) {
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,(("COMMAND:HIGHLIGHT|ROW:" + ((string)c)) + "|POWER:OFF|COLOR:GREEN"),NULL_KEY);
        llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)c)) + "|col:0|TEXTURE:totallyclear"),NULL_KEY);
    }
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
 integer sloodle_handle_command(string str){
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
        (currentAwardName = value2);
        llMessageLinked(LINK_SET,XY_DETAILS_CHANNEL,currentAwardName,NULL_KEY);
        (scoreboardname = value2);
    }
    else  if ((str == SLOODLE_EOF)) {
        return TRUE;
    }
    return FALSE;
}
default {

 //on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        (SCRIPT_NAME = llGetScriptName());
        (owner = llGetOwner());
        (ROW_CHANNEL = random_integer((-2483000),3483000));
        (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
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
        integer c = 0;
        for ((c = 0); (c < 10); (c++)) {
            llListen((ROW_CHANNEL + c),"","","");
        }
        llMessageLinked(LINK_SET,UI_CHANNEL,"COMMAND:ENGAGE CLICK HANDLER",NULL_KEY);
        (currentView = "Top Scores");
        llMessageLinked(LINK_SET,UI_CHANNEL,("CMD:SET CURRENT BUTTON|BUTTON:s0|DESCRIPTION:" + currentView),NULL_KEY);
        (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((("quiz->newQuizGame" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)),SCRIPT_NAME);
    }

        link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == UI_CHANNEL)) {
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if ((command == "NEWGAME")) {
                (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
                llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((("quiz->newQuizGame" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)),SCRIPT_NAME);
            }
            if ((command == "GAMEID")) {
                (gameid = i(llList2String(dataBits,1)));
            }
            else  if ((command == "AWARD SELECTED")) {
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
                    key avuuid = llGetOwner();
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)avuuid)) + "&sloodleavname=") + llEscapeURL(llKey2Name(avuuid))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((("awards->getPlayerScores" + authenticatedUser) + "&index=") + ((string)index)) + "&gameid=") + ((string)gameid)),SCRIPT_NAME);
                    return;
                }
            }
            else  if (((command == "UPDATE VIEW CLASS LIST") || (command == "UPDATE DISPLAY"))) {
                if (((currentView == "Top Scores") || (currentView == "Sort by Name"))) {
                    key avuuid = llGetOwner();
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)avuuid)) + "&sloodleavname=") + llEscapeURL(llKey2Name(avuuid))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((("awards->getPlayerScores" + authenticatedUser) + "&index=") + ((string)index)) + "&gameid=") + ((string)gameid)),SCRIPT_NAME);
                }
                else  if ((currentView == "Team Top Scores")) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getTeamPlayerScores&currency=Credits" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_teamScores)) + "&maxitems=9&sortmode=name"),llGetScriptName());
                }
                else  if ((currentView == "Select Teams")) {
                    (owner = llGetOwner());
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getAwardGrps" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_selectTeams)) + "&maxitems=9"),SCRIPT_NAME);
                }
            }
            else  if ((command == "DISPLAY MENU")) {
                debug(((str + " currentview: ") + currentView));
                integer rowNum = i(llList2String(dataBits,1));
                key av = k(llList2String(dataBits,2));
                if (isFacilitator(llKey2Name(av))) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)av)) + "&sloodleavname=") + llEscapeURL(llKey2Name(av))));
                    if ((currentView == "Select Award")) {
                        (previousAwardId = selectedAwardId);
                        clearHighlights();
                        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,((("COMMAND:HIGHLIGHT|ROW:" + ((string)rowNum)) + "|POWER:ON|") + ((string)GREEN)),SCRIPT_NAME);
                        integer awardId = llList2Integer(rows_selectAward,(rowNum * 2));
                        string awardName = llList2String(rows_selectAward,((rowNum * 2) + 1));
                        llMessageLinked(LINK_SET,UI_CHANNEL,((("COMMAND:AWARD SELECTED|AWARDID:" + ((string)awardId)) + "|NAME:") + awardName),SCRIPT_NAME);
                        (selectedAwardId = awardId);
                    }
                    else  if ((currentView == "Group Membership Users")) {
                        list clickedUser = llList2List(rows_getAwardGrpMbrs,(rowNum * 4),((rowNum * 4) + 4));
                        key useruuid = k(llList2String(clickedUser,0));
                        string userName = llEscapeURL(s(llList2String(clickedUser,1)));
                        string mbr = s(llList2String(clickedUser,3));
                        if ((mbr == "yes")) {
                            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("user->removeGrpMbr" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(currentGroup)) + "&avuuid=") + ((string)useruuid)) + "&avname=") + userName),SCRIPT_NAME);
                        }
                        else  {
                            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("user->addGrpMbr" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(currentGroup)) + "&avuuid=") + ((string)useruuid)) + "&avname=") + userName),SCRIPT_NAME);
                        }
                    }
                    else  if ((currentView == "Group Membership")) {
                        string clickedGroup = llEscapeURL(llList2String(rows_getAwardGrps,rowNum));
                        llMessageLinked(LINK_SET,UI_CHANNEL,"CMD:SET CURRENT BUTTON|BUTTON:s3|DESCRIPTION:Group Membership Users",SCRIPT_NAME);
                        (current_grp_membership_group = clickedGroup);
                        llMessageLinked(LINK_SET,UI_CHANNEL,("CMD:SET CURRENT GROUP|GRPNAME:" + current_grp_membership_group),NULL_KEY);
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("user->getAwardGrpMbrs" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + clickedGroup) + "&index=") + ((string)index)) + "&sortmode=name"),SCRIPT_NAME);
                    }
                    else  if ((currentView == "Select Teams")) {
                        string clickedGroup = llList2String(rows_selectTeams,rowNum);
                        if ((llListFindList(awardGroups,[clickedGroup]) == (-1))) {
                            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((("awards->addAwardGrp" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(clickedGroup)),SCRIPT_NAME);
                        }
                        else  {
                            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((("awards->removeAwardGrp" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(clickedGroup)),SCRIPT_NAME);
                        }
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
            if ((response == "awards|getAwards")) {
                if ((currentView == "Select Award")) {
                    integer totalAwards = i(llList2String(dataLines,3));
                    if ((status == 1)) {
                        (rows_selectAward = []);
                        clearHighlights();
                        llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index)) + "|TOTALITEMS:") + ((string)totalAwards)),SCRIPT_NAME);
                        (stringToPrint = "");
                        list award_activities = llList2List(dataLines,4,llGetListLength(dataLines));
                        integer len = llGetListLength(award_activities);
                        (displayData = (("CURRENT VIEW:" + currentView) + "\n"));
                        for ((counter = 0); (counter < len); (counter++)) {
                            list awardData = llParseString2List(llList2String(award_activities,counter),["|"],[]);
                            integer awardId = i(llList2String(awardData,0));
                            if ((awardId == selectedAwardId)) {
                                llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:yes"),NULL_KEY);
                            }
                            else  llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:no"),NULL_KEY);
                            string awardName = s(llList2String(awardData,1));
                            (displayData += ("AWARDNAME:" + awardName));
                            if ((counter != len)) (displayData += "\n");
                            (rows_selectAward += [awardId,awardName]);
                            if ((llStringLength(awardName) > 25)) {
                                (awardName = llGetSubString(awardName,0,24));
                            }
                            integer spaceLen = (MAX_XY_LETTER_SPACE - ((llStringLength(((string)(counter + 1))) + 2) + llStringLength(awardName)));
                            string text = ((((string)((index + counter) + 1)) + ") ") + awardName);
                            (text += llGetSubString("                              ",0,(spaceLen - 1)));
                            (stringToPrint += text);
                        }
                        llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                        llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                        (stringToPrint = "");
                    }
                }
            }
            else  if ((response == "awards|addAwardGrp")) {
                list grps = llParseString2List(data,["|"],[]);
                integer counter = 0;
                list mbrData;
                string grpName;
                if ((currentView == "Select Teams")) {
                    if ((status == 1)) {
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getAwardGrps" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index)) + "&maxitems=10"),SCRIPT_NAME);
                    }
                    else  if ((status == (-500100))) {
                        llInstantMessage(owner,"Sorry, tried to add the group to this award but had troubles inserting into the Moodle database");
                    }
                    else  if ((status == (-500200))) {
                        llInstantMessage(owner,"Sorry, that group doesn't exist in moodle");
                    }
                    else  if ((status == (-500300))) {
                        llInstantMessage(owner,"Sorry, group has already been added!");
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getAwardGrps" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index)) + "&maxitems=10"),SCRIPT_NAME);
                    }
                }
            }
            else  if ((response == "awards|removeAwardGrp")) {
                list grps = llParseString2List(data,["|"],[]);
                integer counter = 0;
                list mbrData;
                string grpName;
                if ((currentView == "Select Teams")) {
                    if ((status == 1)) {
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getAwardGrps" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index)) + "&maxitems=10"),SCRIPT_NAME);
                    }
                    else  if ((status == (-500200))) {
                        llInstantMessage(owner,"Sorry, that group doesn't exist in moodle");
                    }
                    else  if ((status == (-500400))) {
                        llInstantMessage(owner,"Sorry, group doesnt exist for this award");
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getAwardGrps" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index)) + "&maxitems=10"),SCRIPT_NAME);
                    }
                    else  if ((status == (-500500))) {
                        llInstantMessage(owner,"Sorry, could not delete the group from the sloodle_awards_teams table");
                    }
                    else  if ((status == (-500600))) {
                        llInstantMessage(owner,"Sorry,  group does not exist in the sloodle_awards_teams table");
                    }
                }
            }
            else  if ((response == "awards|getPlayerTeamScores")) {
                llMessageLinked(LINK_SET,SETTEXT_CHANNEL,(("DISPLAY::userUpdate display|STRING::                                   |COLOR::" + ((string)PINK)) + "|ALPHA::1.0"),NULL_KEY);
                if ((status == 1)) {
                    list grpsData = llParseString2List(data,["|"],[]);
                    (rows_teamScores = []);
                    (index_teamScores = index);
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index_teamScores)) + "|TOTALITEMS:") + ((string)totalGroups)),SCRIPT_NAME);
                    (stringToPrint = "");
                    (displayData = ("CURRENT VIEW:" + currentView));
                    for ((counter = 0); (counter < totalGroups); (counter++)) {
                        list grpData = llParseString2List(llList2String(grpsData,counter),[","],[]);
                        string grpName = s(llList2String(grpData,0));
                        integer grpPoints = i(llList2String(grpData,1));
                        (displayData += ((("\n" + grpName) + "|") + ((string)grpPoints)));
                        integer spaceLen = (MAX_XY_LETTER_SPACE - (((llStringLength(((string)(counter + 1))) + 2) + llStringLength(grpName)) + llStringLength(((string)grpPoints))));
                        string text = ((((string)((index + counter) + 1)) + ") ") + grpName);
                        (text += (llGetSubString("                              ",0,(spaceLen - 1)) + ((string)grpPoints)));
                        (rows_teamScores += ([] + grpName));
                        (stringToPrint += text);
                    }
                    llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                    llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                    (stringToPrint = "");
                }
                else  if ((status == (-500700))) {
                    (stringToPrint = "No teams have been added yet. Please select teams first.");
                    llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                    llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                    (stringToPrint = "");
                }
            }
            else  if ((response == "user|getAwardGrpMbrs")) {
                if ((currentView == "Group Membership Users")) {
                    integer totalUsers = i(llList2String(dataLines,3));
                    integer totalMembers = i(llList2String(dataLines,4));
                    string groupName = s(llList2String(dataLines,5));
                    (index = i(llList2String(dataLines,2)));
                    llMessageLinked(LINK_SET,XY_DETAILS_CHANNEL,groupName,"0");
                    (current_grp_mbr_index = i(llList2String(dataLines,2)));
                    (counter = 0);
                    integer len = llGetListLength(dataLines);
                    llMessageLinked(LINK_SET,XY_DETAILS_CHANNEL,(groupName + " members:"),NULL_KEY);
                    (stringToPrint = "");
                    (rows_getAwardGrpMbrs = []);
                    (displayData = "");
                    list userList = llList2List(dataLines,6,(len - 1));
                    (len = llGetListLength(userList));
                    (displayData = (("CURRENTVIEW:" + currentView) + "\n"));
                    for ((counter = 0); (counter < len); (counter++)) {
                        (data = llList2String(userList,counter));
                        if ((data != "EOF")) {
                            (userDetails = llParseString2List(data,["|"],[]));
                            key avuuid = k(llList2String(userDetails,0));
                            string avName = s(llList2String(userDetails,1));
                            debug("--------------------------------");
                            if ((llStringLength(avName) > 20)) {
                                (avName = llGetSubString(avName,0,20));
                            }
                            integer balance = i(llList2String(userDetails,2));
                            string membershipStatus = s(llList2String(userDetails,3));
                            integer spaceLen = (MAX_XY_LETTER_SPACE - (((llStringLength(((string)((current_grp_mbr_index + counter) + 1))) + 2) + llStringLength(avName)) + llStringLength(((string)balance))));
                            string text = ((((string)((current_grp_mbr_index + counter) + 1)) + ") ") + avName);
                            (text += (llGetSubString("                              ",0,(spaceLen - 1)) + ((string)balance)));
                            if ((membershipStatus == "yes")) {
                                llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:yes"),NULL_KEY);
                            }
                            else  {
                                llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:no"),NULL_KEY);
                            }
                            (rows_getAwardGrpMbrs += ([] + userDetails));
                            (userDetails = []);
                            (stringToPrint += text);
                            (displayData += ((((((((("AVUUID:" + ((string)avuuid)) + "|AVNAME:") + avName) + "|BALANCE:") + ((string)balance)) + "|GROUP:") + currentGroup) + "|MBR:") + membershipStatus));
                            if ((counter != len)) (displayData += "\n");
                        }
                    }
                    llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                    llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                    (stringToPrint = "");
                    llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index)) + "|TOTALITEMS:") + ((string)totalUsers)),SCRIPT_NAME);
                }
            }
            else  if ((response == "user|addGrpMbr")) {
                if ((currentView == "Group Membership Users")) {
                    if ((status == 1)) {
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("user->getAwardGrpMbrs" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + currentGroup) + "&index=") + ((string)currentIndex)) + "&sortmode=name"),SCRIPT_NAME);
                    }
                }
            }
            else  if ((response == "user|removeGrpMbr")) {
                if ((currentView == "Group Membership Users")) {
                    if ((status == 1)) {
                        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("user->getAwardGrpMbrs" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + currentGroup) + "&index=") + ((string)currentIndex)) + "&sortmode=name"),SCRIPT_NAME);
                    }
                }
            }
            else  if ((response == "awards|getAwardGrps")) {
                list grps = llParseString2List(data,["|"],[]);
                integer counter = 0;
                list mbrData;
                string grpName;
                (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
                llMessageLinked(LINK_SET,(PLUGIN_CHANNEL + 5),(((((("awards->getTeamPlayerScores&currency=Credits" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_teamScores)) + "&maxitems=4&sortmode=name"),"http2");
                (rows_getAwardGrps = []);
                if ((currentView == "Group Membership")) {
                    if ((status == 1)) {
                        for ((counter = 0); (counter < totalGroups); (counter++)) {
                            (mbrData = llParseString2List(llList2String(grps,counter),[","],[]));
                            (grpName = s(llList2String(mbrData,0)));
                            llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index)) + "|TOTALITEMS:") + ((string)totalGroups)),SCRIPT_NAME);
                            (connected = s(llList2String(mbrData,2)));
                            if ((connected == "yes")) {
                                integer spaceLen = (MAX_XY_LETTER_SPACE - ((llStringLength(((string)(counter + 1))) + 2) + llStringLength(grpName)));
                                string text = ((((string)((index + counter) + 1)) + ") ") + grpName);
                                (text += llGetSubString("                              ",0,(spaceLen - 1)));
                                (awardGroups += grpName);
                                (rows_getAwardGrps += grpName);
                                (stringToPrint += text);
                            }
                            else  {
                                (courseGroups += grpName);
                            }
                        }
                        llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                        llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                        (stringToPrint = "");
                    }
                }
                else  if ((currentView == "Select Teams")) {
                    (awardGroups = []);
                    (rows_selectTeams = []);
                    (courseGroups = []);
                    (index_selectTeams = index);
                    if ((status == 1)) {
                        (displayData = (("CURRENTVIEW:" + currentView) + "\n"));
                        for ((counter = 0); (counter < totalGroups); (counter++)) {
                            (mbrData = llParseString2List(llList2String(grps,counter),[","],[]));
                            (grpName = s(llList2String(mbrData,0)));
                            (connected = s(llList2String(mbrData,2)));
                            integer spaceLen = (MAX_XY_LETTER_SPACE - ((llStringLength(((string)(counter + 1))) + 2) + llStringLength(grpName)));
                            string text = ((((string)((index + counter) + 1)) + ") ") + grpName);
                            (text += llGetSubString("                              ",0,(spaceLen - 1)));
                            if ((connected == "yes")) {
                                llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:yes"),NULL_KEY);
                                (awardGroups += grpName);
                            }
                            else  {
                                llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,(("CMD:TEXTURE|row:" + ((string)counter)) + "|col:0|TEXTURE:no"),NULL_KEY);
                                (courseGroups += grpName);
                            }
                            (rows_selectTeams += grpName);
                            (stringToPrint += text);
                            (displayData += ((("GRPNAME:" + grpName) + "|Connected:") + connected));
                            if ((counter != totalGroups)) (displayData += "\n");
                        }
                        llMessageLinked(LINK_SET,XY_TEXT_CHANNEL,stringToPrint,NULL_KEY);
                        llMessageLinked(LINK_SET,DISPLAY_DATA,displayData,NULL_KEY);
                        (stringToPrint = "");
                        llMessageLinked(LINK_SET,UI_CHANNEL,((((("COMMAND:UPDATE ARROWS|VIEW:" + currentView) + "|INDEX:") + ((string)index)) + "|TOTALITEMS:") + ((string)totalGroups)),SCRIPT_NAME);
                    }
                }
            }
        }
    }
}
