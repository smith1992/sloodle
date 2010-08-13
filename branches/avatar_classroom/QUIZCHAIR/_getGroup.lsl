// LSL script generated: avatar_classroom.QUIZCHAIR._getGroup.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
//_getGroup.lsl
//gets a vector from a string
integer ANIM_CHANNEL = -99;
integer XY_FLAG_CHANNEL = -92811;
integer listenHandle;
integer listenHandle2;
list groups;
key sitter;
integer gameid;
string myQuizName;
integer myQuizId;


integer scoreboardchannel;
            integer sloodlemoduleid;
            string sloodlepwd;
            string sloodleserverroot;
integer DISPLAY_CHANNEL = -870881;
vector RED = <0.77278,4.391e-2,0.0>;
vector ORANGE = <0.8713,0.41303,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
vector GREEN = <0.12616,0.77712,0.0>;
vector BLUE = <0.0,5.804e-2,0.98688>;
vector PINK = <0.83635,0.0,0.88019>;
vector PURPLE = <0.39257,0.0,0.71612>;
vector WHITE = <1.0,1.0,1.0>;
list colors = [GREEN,YELLOW,ORANGE,BLUE,RED,PINK,PURPLE];
integer PLUGIN_CHANNEL = 998821;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer UI_CHANNEL = 89997;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
list myGroups;
string SLOODLE_EOF = "sloodleeof";
integer sloodlecontrollerid;
integer currentAwardId;
integer GROUP_CHANNEL = -91123421;
string myGroupName;
list facilitators;
integer MENU_CHANNEL;
     setFlag(string cmd){
    if ((cmd == "down")) {
        llTriggerSound("flag_down",1.0);
        llMessageLinked(LINK_SET,ANIM_CHANNEL,"p0",NULL_KEY);
        llMessageLinked(LINK_SET,GROUP_CHANNEL,("CMD:set color|" + ((string)WHITE)),NULL_KEY);
        llMessageLinked(LINK_SET,XY_FLAG_CHANNEL,"",NULL_KEY);
        return;
    }
    else  {
        llTriggerSound("flag_up",1.0);
        llMessageLinked(LINK_SET,XY_FLAG_CHANNEL,cmd,NULL_KEY);
        llMessageLinked(LINK_SET,ANIM_CHANNEL,"p1",NULL_KEY);
    }
    setColor(cmd);
}
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == PRIM_MATERIAL_FLESH)) {
        llOwnerSay(str);
    }
}
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
key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}
integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}


        // Configure by receiving a linked message from another script in the object
        // Returns TRUE if the object has all the data it needs
        integer sloodle_handle_command(string str){
    if (((str == "do:requestconfig") || (str == "do:reset"))) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value1 = llList2String(bits,1);
    if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlepwd")) (sloodlepwd = value1);
    if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:quizmoduleid")) (sloodlemoduleid = ((integer)value1));
    else  if ((name == "set:scoreboardchannel")) (scoreboardchannel = ((integer)value1));
    else  if ((name == "set:scoreboard")) {
        (currentAwardId = ((integer)value1));
    }
    else  if ((name == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
        


setColor(string grpName){
    (groups = llListSort(groups,1,TRUE));
    integer found = llListFindList(groups,[llStringTrim(grpName,STRING_TRIM)]);
    vector color;
    if ((found == (-1))) (color = WHITE);
    else  (color = getVector(llList2String(colors,found)));
    llMessageLinked(LINK_SET,GROUP_CHANNEL,("CMD:set color|" + ((string)color)),NULL_KEY);
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
default {

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llUnSit(llGetLinkKey(9));
        llMessageLinked(LINK_SET,GROUP_CHANNEL,("CMD:set text|text:|" + ((string)YELLOW)),NULL_KEY);
        (MENU_CHANNEL = random_integer((-30000),(-20000)));
        llMessageLinked(LINK_SET,GROUP_CHANNEL,("CMD:set color|" + ((string)WHITE)),NULL_KEY);
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
        llListen(MENU_CHANNEL,"",llGetOwner(),"");
    }

  
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            if ((cmd == "GAMEID")) {
                debug(str);
                (gameid = i(llList2String(cmdList,1)));
                (groups = llParseString2List(s(llList2String(cmdList,2)),[","],[]));
                (myQuizName = s(llList2String(cmdList,3)));
                (myQuizId = i(llList2String(cmdList,4)));
                integer len = llGetListLength(groups);
                integer j;
                list tmpGrps;
                for ((j = 0); (j < len); (j++)) {
                    (tmpGrps += llStringTrim(llList2String(groups,j),STRING_TRIM));
                }
                (groups = tmpGrps);
            }
            else  if ((cmd == "BUTTON PRESS")) {
                key userKey = k(llList2String(cmdList,2));
                (sitter = llAvatarOnSitTarget());
                debug((((((" my groups are: " + llList2CSV(myGroups)) + " groups are: ") + llList2CSV(groups)) + " sitter is: ") + ((string)sitter)));
                if (((button == "btn_flag") || (button == "c:0,r:0,d,c:-92811,charPos:0"))) {
                    if ((sitter == NULL_KEY)) return;
                    if (((userKey != sitter) && (userKey != llGetOwner()))) return;
                    llMessageLinked(LINK_SET,GROUP_CHANNEL,((("CMD:set text|text:" + llKey2Name(sitter)) + "|") + ((string)YELLOW)),NULL_KEY);
                    llListenRemove(listenHandle);
                    llListenRemove(listenHandle2);
                    (listenHandle = llListen(MENU_CHANNEL,"",sitter,""));
                    (listenHandle2 = llListen((MENU_CHANNEL + 1),"",sitter,""));
                    if (((userKey != sitter) && (userKey != llGetOwner()))) return;
                    list menu;
                    if ((llGetListLength(myGroups) > 0)) (menu += "Leave group");
                    if ((llGetListLength(myGroups) == 0)) (menu += "Join Group");
                    llDialog(userKey,((("Group Menu\n\n" + llKey2Name(userKey)) + ", is a member of: ") + llList2CSV(myGroups)),menu,MENU_CHANNEL);
                }
            }
            else  if ((cmd == "sitter")) (sitter = id);
        }
        else  if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        else  if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            list dataLines = llParseStringKeepNulls(str,["\n"],[]);
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            string response = s(llList2String(dataLines,1));
            integer totalGrps;
            if ((str == "do:requestconfig")) {
                llResetScript();
            }
            else  if ((response == "user|removeGrpMbr")) {
                if ((status == 1)) {
                    integer j;
                    integer len = llGetListLength(myGroups);
                    list tmpGrps;
                    for ((j = 0); (j < len); (j++)) {
                        (tmpGrps += llStringTrim(llList2String(myGroups,j),STRING_TRIM));
                    }
                    (myGroups = tmpGrps);
                    string gname = llList2String(dataLines,2);
                    integer found = llListFindList(myGroups,[llStringTrim(gname,STRING_TRIM)]);
                    if ((found != (-1))) {
                        (myGroups = llDeleteSubList(myGroups,found,found));
                    }
                    if ((llGetListLength(myGroups) == 0)) {
                        (myGroupName = "");
                        setFlag("down");
                        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)WHITE)),NULL_KEY);
                        return;
                    }
                    else  {
                        (myGroupName = llList2String(myGroups,0));
                        llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)YELLOW)),NULL_KEY);
                    }
                    setFlag(myGroupName);
                    return;
                }
            }
            else  if ((response == "user|addGrpMbr")) {
                if ((status == 1)) {
                    string gname = s(llList2String(dataLines,2));
                    (myGroups += gname);
                    (myGroupName = llList2String(myGroups,0));
                    setFlag(myGroupName);
                    if ((llGetListLength(myGroups) > 1)) llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)YELLOW)),NULL_KEY);
                    else  llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)WHITE)),NULL_KEY);
                    return;
                }
            }
            else  if ((response == "groups|getUsersGrps")) {
                if ((status == 1)) {
                    (totalGrps = i(llList2String(dataLines,2)));
                    string avname = s(llList2String(dataLines,3));
                    key avuuid = k(llList2String(dataLines,4));
                    (myGroups = llList2List(dataLines,5,(llGetListLength(dataLines) - 1)));
                    integer len = llGetListLength(myGroups);
                    integer j;
                    list tmpMyGroups;
                    for ((j = 0); (j < len); (j++)) {
                        (tmpMyGroups += llStringTrim(s(llList2String(myGroups,j)),STRING_TRIM));
                    }
                    (myGroups = tmpMyGroups);
                    if ((llGetListLength(myGroups) > 1)) llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)YELLOW)),NULL_KEY);
                    else  llMessageLinked(LINK_SET,DISPLAY_CHANNEL,("CMD:COLOR|name:btn_desk|" + ((string)WHITE)),NULL_KEY);
                    (myGroupName = llList2String(myGroups,0));
                    setFlag(myGroupName);
                    return;
                }
                else  if ((status == (-55000))) {
                    string avname = s(llList2String(dataLines,2));
                    key avuuid = k(llList2String(dataLines,3));
                    string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
                    llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((("groups->addToRandomGrp" + authenticatedUser) + "&sourceuuid=") + ((string)avuuid)) + "&avuuid=") + ((string)avuuid)) + "&avname=") + llEscapeURL(llKey2Name(avuuid))),NULL_KEY);
                }
            }
            else  if ((response == "groups|addToRandomGrp")) {
                key avuuid = k(llList2String(dataLines,2));
                string avname = llKey2Name(avuuid);
                (myGroupName = s(llList2String(dataLines,3)));
                (myGroups += myGroupName);
                setFlag(myGroupName);
                debug(("added to random group my group name is: " + myGroupName));
                return;
            }
        }
    }

        listen(integer channel,string name,key id,string str) {
        if ((channel == MENU_CHANNEL)) {
            if ((str == "Leave group")) {
                integer len = llGetListLength(myGroups);
                if ((len == 1)) {
                    string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
                    if ((sitter != NULL_KEY)) llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("user->removeGrpMbr" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(llList2String(myGroups,0))) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))),NULL_KEY);
                }
                else  if ((len > 1)) llDialog(id,(("Which group should " + llKey2Name(sitter)) + " leave?"),myGroups,MENU_CHANNEL);
                else  llDialog(id,(("Sorry, " + llKey2Name(sitter)) + " doesn't belong to any groups yet!"),[],MENU_CHANNEL);
            }
            else  if ((str == "Join Group")) {
                list tmpGrps;
                integer len;
                integer j;
                string grpName;
                (len = llGetListLength(groups));
                for ((j = 0); (j < len); (j++)) {
                    (grpName = llList2String(groups,j));
                    if ((llListFindList(myGroups,[grpName]) == (-1))) (tmpGrps += grpName);
                }
                if ((llGetListLength(tmpGrps) > 0)) llDialog(id,("Which group do you want to join?\nAvailable groups are: " + llList2CSV(tmpGrps)),tmpGrps,(MENU_CHANNEL + 1));
                else  llDialog(id,"Sorry, there are no groups you can join",[],(MENU_CHANNEL + 1));
            }
            else  if ((llListFindList(myGroups,[str]) != (-1))) {
                string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
                llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("user->removeGrpMbr" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(str)) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))),NULL_KEY);
            }
        }
        else  if ((channel == (MENU_CHANNEL + 1))) {
            if ((llListFindList(groups,[str]) != (-1))) {
                string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
                llMessageLinked(LINK_SET,PLUGIN_CHANNEL,((((((((("user->addGrpMbr" + authenticatedUser) + "&sloodlemoduleid=") + ((string)currentAwardId)) + "&grpname=") + llEscapeURL(str)) + "&avuuid=") + ((string)sitter)) + "&avname=") + llEscapeURL(llKey2Name(sitter))),NULL_KEY);
            }
        }
    }
}
