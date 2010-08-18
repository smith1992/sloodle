// LSL script generated: avatar_classroom.secondlife_port.choice.mod_choice.lslp Tue Aug 17 22:10:57 Pacific Daylight Time 2010
// Sloodle Choice (for Sloodle 0.3)
// Allows avatars to interact graphically with a Moodle choice.
//
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
string SLOODLE_CHOICE_LINKER = "/mod/sloodle/mod/choice-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";
vector RED = <0.77278,4.391e-2,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
string hoverText = "";
integer counter = 0;

// Choice commands
// Update the specified option. Followed by "|num|text|colour|count|prop"
//  - num is a local option identifier
//  - text is the caption to display for this option
//  - colour is a colour vector (cast to a string)
//  - count is the number selected so far (or -1 if we don't want to display any)
//  - prop is the proportion of maximum size to show (between 0 and 1)
string SLOODLE_CHOICE_UPDATE_OPTION = "do:updateoption";
// Update the choice text. Followed by "|text"
string SLOODLE_CHOICE_UPDATE_TEXT = "do:updatetext";
// Select the specified option. Followed by "|num" (num is a local option identifier).
// The UUID of the toucher should be passed as the key value.
string SLOODLE_CHOICE_SELECT_OPTION = "do:selectoption";

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodlerefreshtime = 600;
integer sloodlerelative = FALSE;
integer sloodleobjectaccessleveluse = 0;
integer sloodleserveraccesslevel = 0;

integer isconfigured = FALSE;
integer eof = FALSE;

key httpstatus = NULL_KEY;
list httpselect = [];

string choicetext = "";
list optionids = [];
list optiontexts = [];
list optionselections = [];
integer numunanswered = -1;

// A list of colors for the options (note: after running out of colours, the list will wrap around)
list optioncolours = [<1.0,0.0,0.0>,<0.0,1.0,0.0>,<0.0,0.0,1.0>,<1.0,1.0,0.0>,<1.0,0.0,1.0>,<0.0,1.0,1.0>,<0.5,0.0,0.0>,<0.0,0.5,0.0>,<0.0,0.0,0.5>,<1.0,0.5,0.0>,<0.5,0.0,0.5>,<0.0,0.5,0.5>];


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";

// Send a translation request link message
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}

///// ----------- /////


///// FUNCTIONS /////

sloodle_debug(string msg){
    llMessageLinked(LINK_THIS,DEBUG_CHANNEL,msg,NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str){
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
    else  if ((name == "set:sloodlerelative")) (sloodlerelative = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == "set:sloodleserveraccesslevel")) (sloodleserveraccesslevel = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    return ((((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0)) && (sloodlemoduleid > 0));
}

// Gets the colour for a specified option (local option number)
vector get_option_colour(integer num){
    (num = (num % llGetListLength(optioncolours)));
    return llList2Vector(optioncolours,num);
}

// Send a reset command to all options
send_reset(){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_CHOICE,"do:reset",NULL_KEY);
}

// Send an update to all parts of the display
send_update(){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_CHOICE,((SLOODLE_CHOICE_UPDATE_TEXT + "|") + choicetext),NULL_KEY);
    integer fullbar = 0;
    integer num_options = llGetListLength(optionids);
    integer i = 0;
    integer numsels = 0;
    for ((i = 0); (i < num_options); (i++)) {
        (numsels = llList2Integer(optionselections,i));
        if (sloodlerelative) {
            if ((numsels > fullbar)) (fullbar = numsels);
        }
        else  {
            (fullbar += numsels);
        }
    }
    if (((sloodlerelative == 0) && (numunanswered > 0))) (fullbar += numunanswered);
    string data = "";
    for ((i = 0); (i < num_options); (i++)) {
        (data = SLOODLE_CHOICE_UPDATE_OPTION);
        (data += ("|" + ((string)i)));
        (data += ("|" + llList2String(optiontexts,i)));
        (data += ("|" + ((string)get_option_colour(i))));
        (data += ("|" + ((string)llList2Integer(optionselections,i))));
        (numsels = llList2Integer(optionselections,i));
        if (((numsels > 0) && (fullbar > 0))) {
            (data += ("|" + ((string)(((float)numsels) / ((float)fullbar)))));
        }
        else  {
            (data += "|0.0");
        }
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_CHOICE,data,NULL_KEY);
    }
}

// Request an update of the choice status.
// Returns the HTTP key.
key request_status(){
    string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
    (body += ("&sloodlepwd=" + sloodlepwd));
    (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
    return llHTTPRequest((sloodleserverroot + SLOODLE_CHOICE_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body);
}


///// STATES /////

// Default state - waiting for configuration
default {

	on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetTimerEvent(0.25);
        llTriggerSound("STARTINGUP",1.0);
        llSetText("",<0.0,0.0,0.0>,0.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodlerefreshtime = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleserveraccesslevel = 0);
        send_reset();
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; (i < numlines); (i++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,i)));
            }
            if ((eof == TRUE)) {
                if ((isconfigured == TRUE)) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],NULL_KEY,"");
                    state ready;
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

state ready {

    on_rez(integer param) {
        llResetScript();
    }

    
    state_entry() {
        llSetText("",RED,1.0);
        llTriggerSound("loadingcomplete",1.0);
        (httpstatus = request_status());
        (httpselect = []);
        llSetTimerEvent(0.0);
        if ((sloodlerefreshtime > 0)) {
            if ((sloodlerefreshtime < 10)) (sloodlerefreshtime = 10);
            llSetTimerEvent(((float)sloodlerefreshtime));
        }
    }

    
    timer() {
        (httpstatus = request_status());
    }

    
    touch_start(integer total_number) {
        if ((llDetectedLinkNumber(0) == llGetLinkNumber())) {
            (httpstatus = request_status());
        }
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id == httpstatus)) {
            (httpstatus = NULL_KEY);
            if ((status != 200)) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httperror",[status],NULL_KEY,"");
                return;
            }
            list lines = llParseStringKeepNulls(body,["\n"],[]);
            integer numlines = llGetListLength(lines);
            list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
            integer statuscode = ((integer)llList2String(statusfields,0));
            if ((statuscode <= 0)) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"servererror",[statuscode],NULL_KEY,"");
                sloodle_debug(body);
                return;
            }
            if ((numlines < 5)) {
                sloodle_debug("Not enough response data.");
                return;
            }
            (choicetext = (((llList2String(lines,1) + "\n\"") + llList2String(lines,2)) + "\""));
            (numunanswered = ((integer)llList2String(lines,4)));
            integer numoptions = (numlines - 5);
            if ((numoptions != llGetListLength(optionids))) {
                send_reset();
            }
            (optionids = []);
            (optiontexts = []);
            (optionselections = []);
            integer i = 5;
            list fields = [];
            for (; (i < numlines); (i++)) {
                (fields = llParseStringKeepNulls(llList2String(lines,i),["|"],[]));
                if ((llGetListLength(fields) >= 3)) {
                    (optionids += [((integer)llList2String(fields,0))]);
                    (optiontexts += [llList2String(fields,1)]);
                    (optionselections += [((integer)llList2String(fields,2))]);
                }
            }
            sloodle_debug(("Number of options received: " + ((string)llGetListLength(optionids))));
            send_update();
            return;
        }
        integer pos = llListFindList(httpselect,[id]);
        if ((pos >= 0)) {
            (httpselect = llDeleteSubList(httpselect,pos,pos));
            if ((status != 200)) {
                sloodle_debug(("HTTP request failed with status code " + ((string)status)));
                return;
            }
            list lines = llParseStringKeepNulls(body,["\n"],[]);
            integer numlines = llGetListLength(lines);
            list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
            integer numstatusfields = llGetListLength(statusfields);
            if ((numstatusfields < 7)) return;
            integer statuscode = ((integer)llList2String(statusfields,0));
            key uuid = ((key)llList2String(statusfields,6));
            string name = llKey2Name(uuid);
            if ((statuscode == (-10011))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"noupdate",[name],NULL_KEY,"choice");
            else  if ((statuscode == (-10012))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"maxselections",[name],NULL_KEY,"choice");
            else  if ((statuscode == (-10013))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"notopen",[name],NULL_KEY,"choice");
            else  if ((statuscode == (-10014))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"closed",[name],NULL_KEY,"choice");
            else  if ((statuscode == (-10016))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"selectionerror",[name],NULL_KEY,"choice");
            else  if ((statuscode <= 0)) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"servererror",[statuscode],NULL_KEY,"");
            if ((statuscode <= 0)) {
                sloodle_debug(body);
                return;
            }
            if ((statuscode == 10012)) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"selectionupdated",[name],NULL_KEY,"choice");
            else  if ((statuscode == 10013)) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"selectionalreadymade",[name],NULL_KEY,"choice");
            else  sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"selectionmade",[name],NULL_KEY,"choice");
            (httpstatus = request_status());
            return;
        }
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_CHOICE)) {
            list parts = llParseString2List(sval,["|"],[]);
            integer numparts = llGetListLength(parts);
            string cmd = llList2String(parts,0);
            if ((((cmd == SLOODLE_CHOICE_SELECT_OPTION) && (kval != NULL_KEY)) && (numparts > 1))) {
                integer optionnum = ((integer)llList2String(parts,1));
                if (((optionnum < 0) || (optionnum >= llGetListLength(optionids)))) return;
                integer optionid = llList2Integer(optionids,optionnum);
                string name = llKey2Name(kval);
                sloodle_debug(((("Selecting option ID " + ((string)optionid)) + " for ") + name));
                string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
                (body += ("&sloodlepwd=" + sloodlepwd));
                (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
                (body += ("&sloodleuuid=" + ((string)kval)));
                (body += ("&sloodleavname=" + llEscapeURL(name)));
                (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
                (body += ("&sloodleoptionid=" + ((string)optionid)));
                key newhttp = llHTTPRequest((sloodleserverroot + SLOODLE_CHOICE_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body);
                (httpselect += [newhttp]);
            }
        }
        else  if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) llResetScript();
        }
    }
}
