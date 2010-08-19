// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.vendingmachine.mod_vending.lslp Wed Aug 18 19:07:06 Pacific Daylight Time 2010
// Sloodle object distributor.
// Allows Sloodle objects to be distributed in-world to Second Life users,
//  either by an in-world user touching it and using a menu,
//  or via XMLRPC from Moodle.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//

// When configured, opens an XMLRPC channel, and reports the channel key and inventory list to the Moodle server.
// Note that non-copyable items are NOT made available, and neither will scripts or items whose name is on the ignore list below.


// ***** IGNORE LIST *****
//
// This is a list of names of items which should NOT be handed out
string MENU_BUTTON_PREVIOUS = "PREVIOUS";
list ignorelist = ["sloodle_config","sloodle_object_distributor","sloodle_setup_notecard","sloodle_slave_object","sloodle_debug","awards_sloodle_config","click","STARTINGUP","loadingcomplete"];
//
// ***** ----------- *****
// Returns number of Strides in a List
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;

integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
integer UI_CHANNEL = 89997;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG;
string SLOODLE_DISTRIB_LINKER = "/mod/sloodle/mod/distributor-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";
string hoverText = "";
integer counter = 0;
vector YELLOW = <0.82192,0.86066,0.0>;
vector PINK = <0.83635,0.0,0.88019>;

string sloodleserverroot = "";
integer sloodlecontrollerid = 0;
string sloodlepwd = "";
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0;
integer sloodleobjectaccesslevelctrl = 0;
integer sloodlerefreshtime = 0;

integer lastrefresh = 0;

integer isconfigured = FALSE;
integer eof = FALSE;
integer isconnected = FALSE;

key ch = NULL_KEY;
key httpupdate = NULL_KEY;

list inventory = [];
string inventorystr = "";
list cmddialog = [];


// Menu button texts
string MENU_BUTTON_RECONNECT = "A";
string MENU_BUTTON_RESET = "B";
string MENU_BUTTON_SHUTDOWN = "C";

string MENU_BUTTON_NEXT = ">>";
string MENU_BUTTON_CMD = "cmd";
string MENU_BUTTON_WEB = "web";
///// TRANSLATION /////
// These items are standard... do not change them!

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_DIALOG = "dialog";
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";
string SLOODLE_TRANSLATE_IM = "instantmessage";


/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}

// Send a translation request link message
// Parameter: output_method = should identify an output method, as given by the "SLOODLE_TRANSLATE_..." constants above
// Parameter: output_params = a list of parameters which controls the output, such as chat channel or buttons for a dialog
// Parameter: string_name = the name of the localization string to output
// Parameter: string_params = a list of parameters which will be included in the translated string (or an empty list if none)
// Parameter: keyval = a key to send in the link message
// Parameter: batch = the name of the localization batch which should handle this request
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
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
///// FUNCTIONS /////

sloodle_debug(string msg){
    llMessageLinked(LINK_THIS,DEBUG_CHANNEL,msg,NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str){
    if ((str == "do:requestconfig")) llResetScript();
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlepwd")) {
        if ((value2 != "")) (sloodlepwd = ((value1 + "|") + value2));
        else  (sloodlepwd = value1);
    }
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlemoduleid")) (sloodlemoduleid = ((integer)value1));
    else  if ((name == "set:sloodlerefreshtime")) (sloodlerefreshtime = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccesslevelctrl")) (sloodleobjectaccesslevelctrl = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    return (((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0));
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id){
    if ((sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP)) {
        return llSameGroup(id);
    }
    else  if ((sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC)) {
        return TRUE;
    }
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id){
    if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP)) {
        return llSameGroup(id);
    }
    else  if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC)) {
        return TRUE;
    }
    return (id == llGetOwner());
}

// Add the given agent to our command dialog list
sloodle_add_cmd_dialog(key id,integer page){
    integer pos = llListFindList(cmddialog,[id]);
    if ((pos < 0)) {
        (cmddialog += [id,llGetUnixTime(),page]);
    }
    else  {
        (cmddialog = llListReplaceList(cmddialog,[llGetUnixTime(),page],(pos + 1),(pos + 2)));
    }
}

// Get the number of the page the current user is on in the dialogs
// (Returns 0 if they are not found)
integer sloodle_get_cmd_dialog_page(key id){
    integer pos = llListFindList(cmddialog,[id]);
    if ((pos >= 0)) {
        return llList2Integer(cmddialog,(pos + 2));
    }
    return 0;
}

// Remove the given agent from our command dialog list
sloodle_remove_cmd_dialog(key id){
    integer pos = llListFindList(cmddialog,[id]);
    if ((pos >= 0)) {
        (cmddialog = llDeleteSubList(cmddialog,pos,(pos + 2)));
    }
}

// Purge the command dialog list of old activity
sloodle_purge_cmd_dialog(){
    integer curtime = llGetUnixTime();
    integer i = 0;
    while ((i < llGetListLength(cmddialog))) {
        if (((curtime - llList2Integer(cmddialog,(i + 1))) > 12)) {
            (cmddialog = llDeleteSubList(cmddialog,i,(i + 2)));
        }
        else  {
            (i += 3);
        }
    }
}


// Update our inventory list
update_inventory(){
    (inventory = []);
    (inventorystr = "");
    integer numitems = llGetInventoryNumber(INVENTORY_ALL);
    string itemname = "";
    integer numavailable = 0;
    integer i = 0;
    for ((i = 0); (i < numitems); (i++)) {
        (itemname = llGetInventoryName(INVENTORY_ALL,i));
        if ((((llGetInventoryPermMask(itemname,MASK_OWNER) & PERM_COPY) && (llGetInventoryType(itemname) != INVENTORY_SCRIPT)) && (llListFindList(ignorelist,[itemname]) == (-1)))) {
            (inventory += [itemname]);
            if ((numavailable > 0)) (inventorystr += "|");
            (inventorystr += llEscapeURL(itemname));
            (numavailable++);
        }
    }
}

// Update the server with our channel and inventory.
// Returns the key of the HTTP request.
key update_server(){
    string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
    (body += ("&sloodlepwd=" + sloodlepwd));
    (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
    (body += ("&sloodlechannel=" + ((string)ch)));
    (body += ("&sloodleinventory=" + inventorystr));
    return llHTTPRequest((sloodleserverroot + SLOODLE_DISTRIB_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body);
}

// Shows a command dialog to the user, with options for reconnect, reset, and shutdhown
sloodle_show_command_dialog(key id){
    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,[SLOODLE_CHANNEL_AVATAR_DIALOG,"A","B","C"],"dialog:distributorcommandmenu",["A","B","C"],id,"distributor");
}

// Shows the given user a dialog of objects, starting at the specified page
// If parameter "showcmd" is TRUE, then the "command" menu option will be shown.
sloodle_show_object_dialog(key id,integer page,integer showcmd){
    integer numobjects = llGetListLength(inventory);
    integer numpages = ((integer)(((float)numobjects) / 9.0));
    if (((numobjects % 9) > 0)) (numpages += 1);
    if ((page < 0)) (page == 0);
    else  if ((page >= numpages)) (page = (numpages - 1));
    list buttonlabels = [];
    string buttondef = "";
    integer numbuttons = 0;
    integer itemnum = 0;
    if ((page < 0)) (page = 0);
    for ((itemnum = (page * 9)); ((itemnum < numobjects) && (numbuttons < 9)); (itemnum++),(numbuttons++)) {
        (buttonlabels += [((string)(itemnum + 1))]);
        (buttondef += (((((string)(itemnum + 1)) + " = ") + llList2String(inventory,itemnum)) + "\n"));
    }
    if ((page > 0)) {
        (buttonlabels += [MENU_BUTTON_PREVIOUS]);
    }
    if ((page < (numpages - 1))) (buttonlabels += [MENU_BUTTON_NEXT]);
    if (showcmd) {
        (buttonlabels += [MENU_BUTTON_CMD]);
    }
    (buttonlabels += [MENU_BUTTON_WEB]);
    list box1 = [];
    list box2 = [];
    list box3 = [];
    list box4 = [];
    integer i;
    string lab = "";
    (buttonlabels = llListSort(buttonlabels,1,FALSE));
    for ((i = 0); (i < llGetListLength(buttonlabels)); (i++)) {
        (lab = llList2String(buttonlabels,i));
        if ((llGetListLength(box1) < 3)) (box1 += lab);
        else  if ((llGetListLength(box2) < 3)) (box2 += lab);
        else  if ((llGetListLength(box3) < 3)) (box3 += lab);
        else  if ((llGetListLength(box4) < 3)) (box4 += lab);
    }
    (box1 = llListSort(box1,1,TRUE));
    (box2 = llListSort(box2,1,TRUE));
    (box3 = llListSort(box3,1,TRUE));
    (box4 = llListSort(box4,1,TRUE));
    (buttonlabels = (((box1 + box2) + box3) + box4));
    if (showcmd) {
        sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,([SLOODLE_CHANNEL_AVATAR_DIALOG] + buttonlabels),"dialog:distributorobjectmenu:cmd",[buttondef,MENU_BUTTON_CMD,MENU_BUTTON_WEB],id,"distributor");
    }
    else  {
        sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,([SLOODLE_CHANNEL_AVATAR_DIALOG] + buttonlabels),"dialog:distributorobjectmenu",[buttondef,MENU_BUTTON_WEB],id,"distributor");
    }
}


///// STATES /////

// In this state, we are uninitialised, waiting for configuration
default {

    state_entry() {
        llSetTimerEvent(0.25);
        llTriggerSound("STARTINGUP",1.0);
        (SLOODLE_CHANNEL_AVATAR_DIALOG = random_integer((-60000),50000));
        sloodle_debug("Distributor: default state");
        (sloodleserverroot = "");
        (sloodlecontrollerid = 0);
        (sloodlepwd = "");
        (sloodlemoduleid = 0);
        (sloodlerefreshtime = 0);
        (ch = NULL_KEY);
        (inventory = []);
        (inventorystr = "");
        llSetText("",<0.0,0.0,0.0>,0.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (isconnected = FALSE);
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for ((i = 0); (i < numlines); (i++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,i)));
            }
            if ((eof == TRUE)) {
                if ((isconfigured == TRUE)) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],NULL_KEY,"");
                    state connecting;
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configdatamissing",[],NULL_KEY,"");
                    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reconfigure",NULL_KEY);
                    (eof = FALSE);
                }
            }
        }
    }

     timer() {
        (counter++);
        if ((counter > 20)) {
            (hoverText = "|");
            (counter = 0);
        }
        llSetText((hoverText += "||||"),YELLOW,1.0);
    }
}

// Dummy state to jump straight back into "connecting"
state reconnect {

    state_entry() {
        state connecting;
    }
}


// Open an XMLRPC channel, and notify the Moodle site
state connecting {

    state_entry() {
        sloodle_debug("Distributor: connecting state");
        update_inventory();
        if ((sloodlemoduleid <= 0)) {
            state ready;
            return;
        }
        (isconnected = FALSE);
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.01742,0.0,1.07755>,0.9],"openingxmlrpc",[],NULL_KEY,"distributor");
        llOpenRemoteDataChannel();
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",NULL_KEY,"");
        (counter = 0);
        (hoverText = "");
        llSetTimerEvent(0.25);
    }

    
    on_rez(integer start_param) {
        state default;
    }

    
    remote_data(integer type,key channel,key message_id,string sender,integer ival,string sval) {
        if ((type == REMOTE_DATA_CHANNEL)) {
            (ch = channel);
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.01742,0.0,1.07755>,0.9],"establishingconnection",[],NULL_KEY,"distributor");
            sloodle_debug(("Opened XMLRPC channel " + ((string)ch)));
            sloodle_debug("Getting inventory...");
            sloodle_debug(("Inventory list = " + inventorystr));
            sloodle_debug("Reporting to Moodle server...");
            (httpupdate = update_server());
        }
    }

    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                if (sloodle_check_access_ctrl(userKey)) {
                    sloodle_show_command_dialog(userKey);
                    sloodle_add_cmd_dialog(userKey,0);
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"establishingconnection",[llKey2Name(userKey)],NULL_KEY,"distributor");
                    state reconnect;
                }
            }
        }
    }

    listen(integer channel,string name,key id,string msg) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((llListFindList(cmddialog,[id]) == (-1))) return;
            sloodle_remove_cmd_dialog(id);
            if ((msg == MENU_BUTTON_RECONNECT)) {
                state reconnect;
                return;
            }
            else  if ((msg == MENU_BUTTON_RESET)) {
                llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reconfigure",NULL_KEY);
                state default;
                return;
            }
            else  if ((msg == MENU_BUTTON_SHUTDOWN)) {
                state shutdown;
                return;
            }
        }
    }

    
    timer() {
        (counter++);
        if ((counter > 48)) {
            (hoverText = "|");
            (counter = 0);
            llSetText((hoverText += "||||"),PINK,1.0);
            state default;
        }
    }

    
    http_response(key request_id,integer status,list metadata,string body) {
        if ((request_id != httpupdate)) return;
        (httpupdate = NULL_KEY);
        (isconnected = FALSE);
        if ((status != 200)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httperror:code",[status],NULL_KEY,"");
            state ready;
            return;
        }
        if ((body == "")) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httpempty",[],NULL_KEY,"");
            state ready;
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = llList2Integer(statusfields,0);
        if ((statuscode > 0)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"connected",[],NULL_KEY,"");
            (isconnected = TRUE);
            state ready;
        }
        else  {
            if ((llGetListLength(lines) > 1)) {
                string errmsg = llList2String(lines,1);
                sloodle_debug(((("ERROR " + ((string)statuscode)) + ": ") + errmsg));
            }
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"servererror",[statuscode],NULL_KEY,"");
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.0,0.0>,1.0],"connectionfailed",[],NULL_KEY,"");
        }
        state ready;
    }
}


// Ready to receive XMLRPC requests (if applicable) or user touches
state ready {

    state_entry() {
        llTriggerSound("loadingcomplete",1.0);
        sloodle_debug("Distributor: ready state");
        if (((sloodlemoduleid > 0) && (isconnected == TRUE))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.1,0.9,0.1>,0.9],"readyconnectedto",[sloodleserverroot],NULL_KEY,"");
        }
        else  {
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.1,0.9,0.1>,0.9],"readynotconnected",[sloodleserverroot],NULL_KEY,"");
        }
        if (((sloodlerefreshtime > 0) && (sloodlerefreshtime < 60))) (sloodlerefreshtime = 60);
        else  if ((sloodlerefreshtime < 0)) (sloodlerefreshtime = 0);
        llSetTimerEvent(12.0);
        (lastrefresh = llGetUnixTime());
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",NULL_KEY,"");
    }

    
    on_rez(integer start_param) {
        state default;
    }

    
    remote_data(integer type,key channel,key message_id,string sender,integer ival,string sval) {
        if ((type == REMOTE_DATA_REQUEST)) {
            sloodle_debug(("Received XMLRPC request: " + sval));
            list lines = llParseStringKeepNulls(sval,["\\n"],[]);
            list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
            list datafields = [];
            if ((llGetListLength(lines) > 1)) {
                (datafields = llParseStringKeepNulls(llList2String(lines,1),["|"],[]));
            }
            integer statuscode = llList2Integer(statusfields,0);
            if ((statuscode < 0)) {
                sloodle_debug(("Error given in status code: " + ((string)statuscode)));
                llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nError given in request",0);
                return;
            }
            if ((llGetListLength(datafields) < 1)) {
                sloodle_debug("ERROR - no fields in data line");
                llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nNo fields in data line",0);
                return;
            }
            string cmd = llToUpper(llList2String(datafields,0));
            if ((cmd == "SENDOBJECT")) {
                if ((llGetListLength(datafields) < 3)) {
                    sloodle_debug("ERROR - not enough fields in data line - expected 3.");
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nNot enough fields in data line - expected 3.",0);
                    return;
                }
                key targetavatar = llList2Key(datafields,1);
                string objname = llList2String(datafields,2);
                if ((llGetInventoryType(objname) == INVENTORY_NONE)) {
                    sloodle_debug((("Object \"" + objname) + "\" not found."));
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nObject not found.",0);
                    return;
                }
                if (((targetavatar == NULL_KEY) || (llGetOwnerKey(targetavatar) != targetavatar))) {
                    sloodle_debug("Could not find identified avatar.");
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nCould not find identified avatar.",0);
                    return;
                }
                llGiveInventory(targetavatar,objname);
                llRemoteDataReply(channel,NULL_KEY,"1|DISTRIBUTOR\nSuccess.",0);
            }
        }
    }

    
    
      link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                if ((sloodle_check_access_use(userKey) || sloodle_check_access_ctrl(userKey))) {
                    sloodle_show_object_dialog(userKey,0,sloodle_check_access_ctrl(userKey));
                    sloodle_add_cmd_dialog(userKey,0);
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[llKey2Name(userKey)],NULL_KEY,"");
                }
            }
        }
    }

    listen(integer channel,string name,key id,string msg) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((llListFindList(cmddialog,[id]) == (-1))) return;
            integer page = sloodle_get_cmd_dialog_page(id);
            if ((msg == MENU_BUTTON_NEXT)) {
                sloodle_show_object_dialog(id,(page + 1),sloodle_check_access_ctrl(id));
                sloodle_add_cmd_dialog(id,(page + 1));
            }
            else  if ((msg == MENU_BUTTON_PREVIOUS)) {
                sloodle_show_object_dialog(id,(page - 1),sloodle_check_access_ctrl(id));
                sloodle_add_cmd_dialog(id,(page - 1));
            }
            else  if ((msg == MENU_BUTTON_CMD)) {
                sloodle_show_command_dialog(id);
                sloodle_add_cmd_dialog(id,0);
            }
            else  if ((msg == MENU_BUTTON_WEB)) {
                string urltoload = ((sloodleserverroot + "/mod/sloodle/view.php?id=") + ((string)sloodlemoduleid));
                string transLookup = "dialog:distributorobjectmenu:visitmoodle";
                key avuuid = id;
                if ((isconfigured && (sloodlemoduleid > 0))) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_LOAD_URL,[urltoload],transLookup,[],avuuid,"distributor");
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_IM,[id],"distributor:notconnected",[],id,"distributor");
                }
            }
            else  if ((msg == MENU_BUTTON_RECONNECT)) {
                sloodle_remove_cmd_dialog(id);
                state reconnect;
                return;
            }
            else  if ((msg == MENU_BUTTON_RESET)) {
                sloodle_remove_cmd_dialog(id);
                llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reconfigure",NULL_KEY);
                state default;
                return;
            }
            else  if ((msg == MENU_BUTTON_SHUTDOWN)) {
                sloodle_remove_cmd_dialog(id);
                state shutdown;
                return;
            }
            else  {
                integer objnum = ((integer)msg);
                if (((objnum > 0) && (objnum <= llGetListLength(inventory)))) {
                    llGiveInventory(id,llList2String(inventory,(objnum - 1)));
                }
                sloodle_remove_cmd_dialog(id);
            }
        }
    }

    
    changed(integer change) {
        if (((change & CHANGED_INVENTORY) == CHANGED_INVENTORY)) {
            update_inventory();
        }
    }

    
    timer() {
        sloodle_purge_cmd_dialog();
        if ((sloodlemoduleid > 0)) {
            if (((llGetUnixTime() - lastrefresh) > sloodlerefreshtime)) {
                state reconnect;
            }
        }
    }
}

state shutdown {

    on_rez(integer param) {
        state default;
    }


    state_entry() {
        sloodle_debug("Distributor: shutdown state");
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.5,0.5,0.5>,1.0],"shutdown",[],NULL_KEY,"");
        llSetTimerEvent(12.0);
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",NULL_KEY,"");
    }

 
     link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                if (sloodle_check_access_ctrl(userKey)) {
                    sloodle_show_command_dialog(userKey);
                    sloodle_add_cmd_dialog(userKey,0);
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:ctrl",[llKey2Name(userKey)],NULL_KEY,"");
                }
            }
        }
    }

    listen(integer channel,string name,key id,string msg) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((llListFindList(cmddialog,[id]) == (-1))) return;
            sloodle_remove_cmd_dialog(id);
            if ((msg == MENU_BUTTON_RECONNECT)) {
                state reconnect;
                return;
            }
            else  if ((msg == MENU_BUTTON_RESET)) {
                llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reconfigure",NULL_KEY);
                state default;
                return;
            }
            else  if ((msg == MENU_BUTTON_SHUTDOWN)) {
                state shutdown;
                return;
            }
        }
    }

    
    timer() {
        sloodle_purge_cmd_dialog();
    }
}
