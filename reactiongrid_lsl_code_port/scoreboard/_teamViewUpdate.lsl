// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.scoreboard._teamViewUpdate.lslp Wed Aug 18 19:07:06 Pacific Daylight Time 2010
// teamViewUpdate.lsl
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* teamViewUpdate.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer ROW_CHANNEL;
string stringToPrint;
integer index;
integer index_teamScores;
string SCRIPT_NAME;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer PLUGIN_CHANNEL = 998821;
integer XY_TEAM_CHANNEL = -9110;
integer XY_DETAILS_CHANNEL = 700100;
integer UI_CHANNEL = 89997;
integer PRIM_PROPERTIES_CHANNEL = -870870;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer MAX_XY_LETTER_SPACE = 30;
string authenticatedUser;
integer counter;
integer currentAwardId;
list dataLines;
key owner;
string currentView;
list rows_teamScores;
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
vector WHITE = <1.0,1.0,1.0>;
list colors = [GREEN,YELLOW,ORANGE,BLUE,RED,PINK,PURPLE];
string SLOODLE_EOF = "sloodleeof";
 string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
string currentAwardName;
vector getVector(string vStr){
    (vStr = llGetSubString(vStr,1,(llStringLength(vStr) - 2)));
    list vStrList = llParseString2List(vStr,[","],["<",">"]);
    vector output = <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
    return output;
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

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}

centerDetails(string str){
    integer len = llStringLength(str);
    string spaces = "                    ";
    integer numSpacesForMargin = ((30 - len) / 2);
    string margin = llGetSubString(spaces,0,numSpacesForMargin);
    string stringToPrint = ((margin + str) + margin);
    llMessageLinked(LINK_SET,XY_DETAILS_CHANNEL,stringToPrint,NULL_KEY);
}
 integer sloodle_handle_command(string str){
    if ((str == "do:requestconfig")) {
        llResetScript();
    }
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
        centerDetails(currentAwardName);
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
        integer c;
        for ((c = 0); (c <= 4); (c++)) {
            llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,((("COMMAND:HIGHLIGHT|ROW:" + ((string)(11 + c))) + "|POWER:ON|") + ((string)WHITE)),NULL_KEY);
        }
        llMessageLinked(LINK_SET,XY_TEAM_CHANNEL,"                                                                                       ",NULL_KEY);
        (SCRIPT_NAME = llGetScriptName());
        (owner = llGetOwner());
        (ROW_CHANNEL = random_integer((-2483000),3483000));
        for ((c = 0); (c < 4); (c++)) {
            llListen((ROW_CHANNEL + c),"","","");
        }
        (modifyPointList = [0,0,0,0,0,0,0,0,0,0]);
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == TRUE)) state go;
        }
    }
}
state go {

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        (currentView = "Team Top Scores");
    }

    
     link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        if ((channel == UI_CHANNEL)) {
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if ((command == "AWARD SELECTED")) {
                (currentAwardId = i(llList2String(dataBits,1)));
            }
            else  if ((command == "GAMEID")) {
                (index_teamScores = 0);
                (authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner()))));
                llMessageLinked(LINK_SET,(PLUGIN_CHANNEL + 5),(((((("awards->getTeamPlayerScores&currency=Credits" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_teamScores)) + "&maxitems=4&sortmode=name"),"http2");
            }
            else  if (((command == "UPDATE VIEW CLASS LIST") || (command == "UPDATE DISPLAY"))) {
                if ((currentView == "Team Top Scores")) {
                    (authenticatedUser = ((("&sloodleuuid=" + ((string)owner)) + "&sloodleavname=") + llEscapeURL(llKey2Name(owner))));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((("awards->getTeamPlayerScores&currency=Credits" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&index=") + ((string)index_teamScores)) + "&maxitems=4&sortmode=name"),"http2");
                }
            }
        }
        else  if (((channel == (PLUGIN_RESPONSE_CHANNEL + 5)) || PLUGIN_RESPONSE_CHANNEL)) {
            (dataLines = llParseStringKeepNulls(str,["\n"],[]));
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            key authUserUuid = llList2Key(statusLine,6);
            string response = s(llList2String(dataLines,1));
            (index = i(llList2String(dataLines,2)));
            integer totalGroups = i(llList2String(dataLines,3));
            string data = llList2String(dataLines,4);
            if ((response == "awards|getTeamPlayerScores")) {
                if ((status == 1)) {
                    list grpsData = llParseString2List(data,["|"],[]);
                    (rows_teamScores = []);
                    (index_teamScores = index);
                    (stringToPrint = "");
                    (displayData = ("CURRENT VIEW:" + currentView));
                    for ((counter = 0); (counter < totalGroups); (counter++)) {
                        list grpData = llParseString2List(llList2String(grpsData,counter),[","],[]);
                        string grpName = s(llList2String(grpData,0));
                        integer grpPoints = i(llList2String(grpData,1));
                        (displayData += ((("\n" + grpName) + "|") + ((string)grpPoints)));
                        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,((("COMMAND:HIGHLIGHT|ROW:" + ((string)(11 + counter))) + "|POWER:ON|") + ((string)getVector(llList2String(colors,counter)))),NULL_KEY);
                        integer spaceLen = (MAX_XY_LETTER_SPACE - (((llStringLength(((string)(counter + 1))) + 2) + llStringLength(grpName)) + llStringLength(((string)grpPoints))));
                        string text = ((((string)((index + counter) + 1)) + ") ") + grpName);
                        (text += (llGetSubString("                              ",0,(spaceLen - 1)) + ((string)grpPoints)));
                        (rows_teamScores += ([] + grpName));
                        (stringToPrint += text);
                    }
                    llMessageLinked(LINK_SET,XY_TEAM_CHANNEL,stringToPrint,NULL_KEY);
                    (stringToPrint = "");
                }
                else  if ((status == (-500700))) {
                    (stringToPrint = "No teams have been added yet. Please select teams first.");
                    llMessageLinked(LINK_SET,XY_TEAM_CHANNEL,stringToPrint,NULL_KEY);
                    (stringToPrint = "");
                }
            }
        }
    }
}
