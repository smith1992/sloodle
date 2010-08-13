// LSL script generated: avatar_classroom.QUIZCHAIR.rezzer.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
/*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the Quiz Build Project for Skoolaborate
*
* chair_rezzer.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
* 
* 
*/



integer MENU_CHANNEL;
string type;
integer chairId;
string listen_cmd;
list chairs;
list activeBlackChairs;
string sitter;
integer placeinlist;
list listen_cmdList;
list activeWhiteChairs;
list activeWhiteChairSitters;
list activeBlackChairSitters;
integer DEBUG_CHAN = -32212;
list facilitators;
integer REZNUM;
integer SERVER_CHANNEL;

vector RED = <0.77278,4.391e-2,0.0>;
vector GREEN = <0.12616,0.77712,0.0>;
integer whiteRezzedNum = 0;
integer blackRezzedNum = 0;

key gSetupQueryId;
string gSetupNotecardName = "0_config";
integer gSetupNotecardLine;
integer counter;
vector startPos;
list path;
debug(string debugVar,string s){
    llMessageLinked(LINK_SET,DEBUG_CHAN,((((((("DEBUGVAR:" + debugVar) + "%%script:") + llGetScriptName()) + "%%mem:") + ((string)llGetFreeMemory())) + "%%") + s),NULL_KEY);
}
list list_cast(list in){
    list out;
    integer i = 0;
    integer l = llGetListLength(in);
    while ((i < l)) {
        string d = llStringTrim(llList2String(in,i),STRING_TRIM);
        if ((d == "")) (out += "");
        else  {
            if ((llGetSubString(d,0,0) == "<")) {
                if ((llGetSubString(d,(-1),(-1)) == ">")) {
                    list s = llParseString2List(d,[","],[]);
                    integer sl = llGetListLength(s);
                    if ((sl == 3)) {
                        (out += ((vector)d));
                    }
                    else  if ((sl == 4)) {
                        (out += ((rotation)d));
                    }
                }
                jump end;
            }
            if ((llSubStringIndex(d,".") != (-1))) {
                (out += ((float)d));
            }
            else  {
                integer lold = ((integer)d);
                if ((((string)lold) == d)) (out += lold);
                else  {
                    key kold = ((key)d);
                    if (kold) (out += [kold]);
                    else  (out += [d]);
                }
            }
        }
        @end;
        (i += 1);
    }
    return out;
}
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if ((llListFindList(facilitators,[llStringTrim(llToLower(avName),STRING_TRIM)]) == (-1))) return FALSE;
    else  return TRUE;
}
//gets a vector from a string
vector getVector(string vStr){
    (vStr = llGetSubString(vStr,1,(llStringLength(vStr) - 2)));
    list vStrList = llParseString2List(vStr,[","],["<",">"]);
    vector output = <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
    return output;
}
string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}
key _k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
   //gets a vector from a string

    
/***********************************************
*  readSettingsNotecard()
*  |-->used to read notecard settings
***********************************************/
readSettingsNotecard(){
    (gSetupNotecardLine = 0);
    (gSetupQueryId = llGetNotecardLine(gSetupNotecardName,gSetupNotecardLine));
}

init(){
    integer ii = 0;
    integer iii = 0;
    (whiteRezzedNum = 0);
    (blackRezzedNum = 0);
    (chairs = []);
    if ((llGetObjectDesc() == "Black Chair")) {
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRegionSay(SERVER_CHANNEL,"CMD:die");
        llRezObject("Teacher Chair",(llGetPos() + <0,4,3>),ZERO_VECTOR,<(-0.0),(-0.0),(-1.0),0.0>,0);
    }
    string chairToRez = llGetObjectDesc();
    vector myPos = llGetPos();
    for ((iii = 0); (iii < REZNUM); (iii++)) {
        llRezObject(chairToRez,(myPos + <2,2,5>),ZERO_VECTOR,ZERO_ROTATION,0);
        llSleep(2);
    }
}
default {



    on_rez(integer start_param) {
        llResetScript();
    }


    state_entry() {
        llSetStatus((STATUS_PHYSICS | STATUS_PHANTOM),FALSE);
        debug("rezzer","Chair Rezzer is ready!");
        llSetText("Ready",GREEN,1.0);
        llListen(MENU_CHANNEL,"",llGetOwner(),"");
        (startPos = llGetPos());
        (counter = 0);
        llSetText("Loading",RED,1.0);
        readSettingsNotecard();
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
    }

    dataserver(key queryId,string data) {
        if ((queryId == gSetupQueryId)) {
            if ((data != EOF)) {
                if ((llGetSubString(data,0,1) == "/")) return;
                list fieldData = llParseString2List(data,["|"],[]);
                string field = llList2String(fieldData,0);
                if ((field == "channel")) (SERVER_CHANNEL = llList2Integer(fieldData,1));
                else  if ((field == "facilitator")) {
                    (facilitators += llStringTrim(llToLower(llList2String(fieldData,1)),STRING_TRIM));
                }
                else  if ((field == "path")) {
                    (path += getVector(llList2String(fieldData,1)));
                    llSetText(("Reading Paths: " + ((string)llGetListLength(path))),GREEN,1.0);
                }
                else  if ((field == "reznum")) (REZNUM = llList2Integer(fieldData,1));
                (gSetupQueryId = llGetNotecardLine(gSetupNotecardName,(++gSetupNotecardLine)));
            }
            else  {
                if ((SERVER_CHANNEL != (-1))) {
                    state go;
                }
            }
        }
    }
}
state go {

    state_entry() {
        llSetText("Touch to Rez Chairs",GREEN,1.0);
        (MENU_CHANNEL = random_integer((-322122),(-992312)));
        llListen(MENU_CHANNEL,"","","");
        llListen(SERVER_CHANNEL,"","","");
        llRegionSay(SERVER_CHANNEL,((((((("ACTIVE CHAIRS|ALL|" + ((string)llList2CSV(activeWhiteChairs))) + "|") + ((string)llList2CSV(activeWhiteChairSitters))) + "|") + ((string)llList2CSV(activeBlackChairs))) + "|") + ((string)llList2CSV(activeBlackChairSitters))));
    }

    touch_start(integer num_detected) {
        string detectedName = llKey2Name(llDetectedKey(0));
        if (isFacilitator(detectedName)) {
            llDialog(llDetectedKey(0),"Please choose a function",["Rez All Pods","Rez Teacher","Rez Student","Rez Instr."],MENU_CHANNEL);
        }
        else  llInstantMessage(llDetectedKey(0),"Sorry you are not allowed to click this rezzer!");
    }

   listen(integer channel,string name,key id,string str) {
        (listen_cmdList = llParseString2List(str,["|"],[]));
        (listen_cmd = s(llList2String(listen_cmdList,0)));
        if ((listen_cmd == "GET ACTIVE CHAIRS")) {
            if ((llGetObjectName() == "White Rezzer")) return;
            llRegionSay(SERVER_CHANNEL,((((((((("CMD:ACTIVE CHAIRS|" + ((string)id)) + "|") + ((string)llList2CSV(activeWhiteChairs))) + "|") + ((string)llList2CSV(activeWhiteChairSitters))) + "|") + ((string)llList2CSV(activeBlackChairs))) + "|") + ((string)llList2CSV(activeBlackChairSitters))));
        }
        else  if (isFacilitator(llKey2Name(id))) {
            if ((str == "Rez All Pods")) {
                init();
            }
            else  if ((str == "Rez Teacher")) {
                llRezObject("Teacher Chair",(llGetPos() + <0,4,3>),ZERO_VECTOR,<(-0.0),(-0.0),(-1.0),0.0>,0);
            }
            else  if ((str == "Rez Student")) {
                llRezObject("Black Chair",<2,2,5>,ZERO_VECTOR,ZERO_ROTATION,0);
            }
            else  if ((str == "Rez Instr.")) {
                llRezObject("White Chair",<2,2,5>,ZERO_VECTOR,ZERO_ROTATION,0);
            }
        }
        else  if ((listen_cmd == "REGISTER POD SITTER")) {
            (chairId = i(llList2String(listen_cmdList,1)));
            (type = s(llList2String(listen_cmdList,2)));
            (sitter = llKey2Name(_k(llList2String(listen_cmdList,3))));
            if ((type == "White Chair")) {
                (activeWhiteChairs += chairId);
                (activeWhiteChairSitters += sitter);
            }
            else  if ((type == "Black Chair")) {
                (activeBlackChairs += chairId);
                (activeBlackChairSitters += sitter);
            }
        }
        else  if ((listen_cmd == "DEREGISTER POD SITTER")) {
            (chairId = i(llList2String(listen_cmdList,1)));
            (type = s(llList2String(listen_cmdList,2)));
            (sitter = llKey2Name(s(llList2String(listen_cmdList,3))));
            if ((type == "White Chair")) {
                (activeWhiteChairs = list_cast(activeWhiteChairs));
                (placeinlist = llListFindList(activeWhiteChairs,[chairId]));
                if ((placeinlist != (-1))) {
                    (activeWhiteChairs = llDeleteSubList(activeWhiteChairs,placeinlist,placeinlist));
                    (activeWhiteChairSitters = llDeleteSubList(activeWhiteChairSitters,placeinlist,placeinlist));
                }
            }
            else  if ((type == "Black Chair")) {
                (placeinlist = llListFindList(activeBlackChairs,[chairId]));
                (activeBlackChairs = list_cast(activeWhiteChairs));
                if ((placeinlist != (-1))) {
                    (activeBlackChairs = llDeleteSubList(activeBlackChairs,placeinlist,placeinlist));
                    (activeBlackChairSitters = llDeleteSubList(activeBlackChairSitters,placeinlist,placeinlist));
                }
            }
        }
    }

   object_rez(key id) {
        if ((llKey2Name(id) == "White Chair")) {
            llRemoteLoadScriptPin(id,"0_GAMECHAIR",4452,TRUE,(whiteRezzedNum++));
        }
        else  if ((llKey2Name(id) == "Black Chair")) {
            llRemoteLoadScriptPin(id,"0_GAMECHAIR",4452,TRUE,(blackRezzedNum++));
        }
        else  if ((llKey2Name(id) == "Teacher Chair")) {
            llRemoteLoadScriptPin(id,"0_GAMECHAIR",4452,TRUE,0);
        }
        (chairs += id);
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
