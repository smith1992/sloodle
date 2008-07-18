// SL Sloodle Server / Course Setup
// Copyright Edmund Edgar, 2007-04-18

// This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

// This script allows the administrator to configure the Moodle server, authenticate the object and choose the course to use. 
// Once everything has been configured, it sends linked messages to other scripts in the same prim telling them the above.
// The code here was originally used by the Sloodle Box.
// It's been separated out here so that you can use the objects on their own, without rezzing them using the box.

string sloodleserverroot = ""; //"http://moodle.edochan.com";
string pwd = "";
string pwdcode = "";

string sloodleregurlbase = "/mod/sloodle/login/sl_reg_linker.php";
string sloodlewelcomebase = "/mod/sloodle/login/sl_welcome_reg.php";
string sloodleteachercoursesbase = "/mod/sloodle/login/sl_teacher_courses_linker.php";
string sloodleobjectvalidationbase = "/mod/sloodle/sl_classroom/sl_object_auth.php";

integer object_dialog_channel = -3857343;
integer avatar_dialog_channel = 3857343;
integer avatar_chat_channel = 1;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

key SLOODLE_TEXTURE_TOUCH_TO_SET = "618aeb0c-ac25-7010-741c-ba9954c2d09b";
key SLOODLE_TEXTURE_READY = "a9bbca6f-4fd6-6cc5-e13b-3faffb4f4672";

integer SLOODLE_RESTRICT_TO_OWNER = 1;

key http_id; 
integer listen_id;

string toucheravname;
key toucheruuid;

string toucherurl = "";
string toucherloginurl = "";

integer sloodle_courseid = 0;
string sloodle_course_title = "";

list courseids;
list coursetitles;
list coursecodes;

string usingnotecard = "no";

sloodle_tell_other_scripts_in_prim(string msg)
{
    sloodle_debug("sending message to other scripts: "+msg);
    llMessageLinked(LINK_THIS, object_dialog_channel, msg, NULL_KEY);   
}

sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, object_dialog_channel, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
     //llWhisper(0,msg);
}

sloodle_set_text(string msg) 
{
    llSetText(msg,<0,0,1>,1);     
}

sloodle_display_server_course_status()
{
    if (sloodleserverroot == "") {
        llSetTexture(SLOODLE_TEXTURE_TOUCH_TO_SET,0);
        sloodle_set_text("Moodle URL not set.");
    } else if (pwd == "") {
        llSetTexture(SLOODLE_TEXTURE_TOUCH_TO_SET,0);
        sloodle_set_text(sloodleserverroot + "\nWaiting for authorization.");        
    } else if (sloodle_courseid == 0) {
        llSetTexture(SLOODLE_TEXTURE_TOUCH_TO_SET,0);
        sloodle_set_text(sloodleserverroot + "\nNeed to select course...");                
    } else {
        llSetTexture(SLOODLE_TEXTURE_READY,0);
        sloodle_set_text(sloodleserverroot + "\n" + sloodle_course_title);                        
    }
}

display_menu()
{
        list menu_options = [];
        string text;
        if (usingnotecard == "yes") {
            text = "\nThis object has been configured using a notecard. \nTo change your Moodle server or course, change the notecard and reset. \nTo setup without using a notecard, delete it.";
            menu_options = ["Reset","Cancel"];            
        } else {
            text = "Menu option";
            if ( (sloodleserverroot == "") || (pwd == "") ) {
                sloodle_debug("install is "+sloodleserverroot+" and pwd is "+pwd);
                menu_options = ["Set Moodle","Reset","Cancel"];
            } else {
                menu_options = ["Set Moodle","Reset","Cancel"];   
            }
        }
        llDialog(toucheruuid, text, menu_options, avatar_dialog_channel);
        listen_id = llListen(avatar_dialog_channel, "", toucheruuid, "");
}

// Moodle selection
// For when the object doesn't yet know which server it should be using...
// ... and the server doesn't yet know whether it should trust this object.
require_sloodle_install_input_and_validation()
{
    sloodleserverroot = "";
    pwd = "";  
    pwdcode = "";  
    llWhisper(0,toucheravname+", please tell me the URL of your Moodle installation, eg. /1 http://moodle.edochan.com\n\nIf you don't have a Moodle server with an up-to-date version of the SLoodle module installed, you can make a trial install at http://moodlefarm.socialminds.jp");  
    llListen(avatar_chat_channel,"",toucheruuid,"");
}   

integer handle_server_selection_input(string msg)
{
    toucherurl = msg;
    sloodle_debug("handling server input "+msg);
    string url = msg + sloodleobjectvalidationbase;
    http_id = llHTTPRequest(url,[],"");     
    sloodle_set_text("Checking URL "+msg);
    return 1;
}

integer handle_server_selection_response(string body)
{
    //llSetTimerEvent(emailpollinterval); // start checking to see if the server sends us an e-mail... 
    llOpenRemoteDataChannel(); // create an XML-RPC channel
    return 1;    
}

integer handle_server_selection_404()
{
    string url = toucherurl+"/version.php";
    http_id = llHTTPRequest(url,[],"");     
    return 1;    
}

handle_site_missing_sloodle() 
{
    llWhisper(0, "Could not find the Sloodle files at "+toucherurl+". Please make sure that you have typed the URL correctly, and that the Sloodle module is installed as mod/sloodle.");
    sloodle_display_server_course_status();
}

handle_site_missing() {
    llWhisper(0, "Could not get a response from the URL"+toucherurl+". Please check and try again.");       
    sloodle_display_server_course_status();    
}

require_sloodle_registration()
{
    string sloodleregurl = sloodleserverroot + sloodleregurlbase + "?sloodlepwd=" + pwd;
    string url = sloodleregurl+"&sloodleavname="+llEscapeURL(toucheravname)+"&sloodleuuid="+(string)toucheruuid;
    sloodle_debug("Requesting login URL for "+(string)toucheruuid);
    sloodle_debug(url);
    sloodle_set_text(sloodleserverroot+"\nAuthenticating "+toucheravname);
    http_id = llHTTPRequest(url,[],"");
}

integer handle_already_registered()
{
    sloodle_debug("User already registered in Sloodle...");
    return 1;
}

integer handle_authentication_response(string body) 
{
    // Split the response into lines, and extract some key fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    string dataline = "";
    if (numlines > 1) dataline = llList2String(lines, 1);
    key checkuuid = NULL_KEY;
    if (llGetListLength(statusfields) >= 7) checkuuid = llList2Key(statusfields, 6);
    
    // Check the status code
    if(statuscode <= 0) {
        sloodle_debug("authentication response:"+body);
        llWhisper(0,"An error occurred while trying to register your avatar.");
        return 0;
    }
    
    if (statuscode == 301) {
        // User is already fully enrolled - no need to do anything else
        return 2;
    }
    
    // Everything is fine
    toucherloginurl = dataline;
    sloodle_set_text(sloodleserverroot+"\nWaiting for "+toucheravname + "to login to Moodle.");        
    llOpenRemoteDataChannel(); // create an XML-RPC channel so that the server can tell us when it's done. 
    return 1;
}

request_teacher_courses()
{
    string url = sloodleserverroot + sloodleteachercoursesbase + "?sloodlepwd=" + pwd;
    url = url+"&sloodleavname="+llEscapeURL(toucheravname)+"&sloodleuuid="+(string)toucheruuid;
    sloodle_set_text(sloodleserverroot+"\nLooking up courses for "+toucheravname);       
    sloodle_debug("courses request:");
    sloodle_debug(url);
    // llWhisper(0,"Requesting login URL for "+llDetectedName(0));
    http_id = llHTTPRequest(url,[],"");    
}

handle_teacher_courses_response(string body)
{
    // Split the response into lines, and extract some key fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer resultCode = llList2Integer(statusfields, 0);
    key responseuuid = NULL_KEY;
    if (llGetListLength(statusfields) >= 7) responseuuid = llList2Key(statusfields, 6);
    
    // Make sure it's the correct agent we're dealing with
    if (responseuuid == toucheruuid) {            
        if(resultCode <= 0) {
            llWhisper(0,"Failed to retrieve teacher courses - response had status code " + (string)resultCode);
        } else {
        
            // Reset our course data
            courseids = [];
            coursetitles = [];
            coursecodes = [];
            
            // Each subsequent line contains course data
            integer i = 1;
            for (i = 1; i < numlines; i++) {
                // Split the current line into fields
                list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
                // Make sure we've got enough items, and store the data
                if (llGetListLength(fields) >= 3) {
                    courseids += [llList2Integer(fields, 0)];
                    coursecodes += [llList2String(fields, 1)];
                    coursetitles += [llList2String(fields, 2)];
                }
            }
            sloodle_debug("Loaded courses");

            offer_course_select();
                                                                                        
        }           
    } else {
        sloodle_debug("User has changed - ignoring old response");
    }   
}

offer_course_select() // TODO: Make sure the avatar getting this is the one who requested the course list...
{
    llDialog(toucheruuid, "Choose your course",coursecodes, avatar_dialog_channel);
    llListen(avatar_dialog_channel, "", toucheruuid, "");
}

integer handle_course_selection_response(string msg) 
{
    sloodle_debug("course selection is "+msg);
    
    integer found = 0;
    integer i;   
    for (i=0;i<llGetListLength(coursecodes);i++) {
        if (llList2String(coursecodes, i) == msg) {            
            sloodle_debug("Set courseid "+(string)llList2Integer(courseids,i));
            sloodle_courseid = llList2Integer(courseids, i);
            sloodle_course_title = msg;     
            found = 1;
            sloodle_display_server_course_status();
        }        
    }
    if (found == 0) {
        llWhisper(0,"Could not find a course for the code "+msg+". Maybe something is being updated at the moment?");
    }
    
    // cleanup
    coursecodes = [];
    coursetitles = [];
    courseids = [];
    
    return found;
}


sloodle_handle_command(string str) 
{
    sloodle_debug("handling command "+str);    

    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        pwd = value;
    } else if (name == "set:sloodle_courseid") {
        sloodle_courseid = (integer)value;
    } else if (name == "set:usingnotecard") {
        usingnotecard = value;
        sloodle_debug("dialog setting usingnotecard to "+value);
    } else {
        sloodle_debug("dialog ignoring message "+str);
    }
    
    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
        sloodle_debug("going to state next_step");
        state next_step;
    }
}

integer handle_touch(key thistoucher) 
{

    if ("usingnotecard" == "yes") {
        llDialog(thistoucher, "Server and course have been already been set using a notecard.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return 0;
    }

    if (toucheruuid != NULL_KEY) {
        if ( (SLOODLE_RESTRICT_TO_OWNER == 1) || (thistoucher != toucheruuid) ) {
            if (thistoucher != llGetOwner()) {
                llSay(0,llKey2Name(thistoucher)+", this object is currently in use by "+llKey2Name(toucheruuid)+".");
                return 0;
            }
        }
    }
    toucheruuid = thistoucher;
    toucheravname = llKey2Name(toucheruuid);
    return 1;
}

reset_all_scripts()
{
    sloodle_debug("resetting all scripts");
    sloodle_tell_other_scripts("do:reset");
    llResetScript();    
}

default 
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }    
    state_entry() 
    {
        sloodle_display_server_course_status();
    }
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        }
    }
    link_message(integer sender_num, integer num, string str, key id) {
        sloodle_debug("dialog got message "+(string)sender_num+str);
       // if ( (sender_num == LINK_THIS) && (num == sloodle_command_channel) ){
            sloodle_handle_command(str);
        //}   
    }    
}

state menu
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }     
    state_entry()
    {
        display_menu();   
    }
    
    listen(integer channel, string name, key id, string message)
    {
        //llWhisper(0,"message was "+message);
        if (message == "Set Moodle")  {    
            state server_selection;   
        } else if (message == "Reset Moodle")  {
            state server_selection;             
        } else if (message == "Setup Class") {
            state authentication;            
        } else if (message == "Reset") {
            reset_all_scripts();
        } else {
            llListenRemove(listen_id);
            state default;
        }
    }
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        } 
    }    
    link_message(integer sender_num, integer num, string str, key id) {
            sloodle_handle_command(str);
    }    

}

state server_selection 
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }     
    touch_start(integer total_number) {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        } 
    }
    state_entry() {
        require_sloodle_install_input_and_validation();  
    }   
    http_response(key request_id, integer status, list metadata, string body) {
        if(status == 200) {
            if (request_id == http_id) {
                handle_server_selection_response(body);
                
            }
        } else if (status == 404) {
            state server_404_diagnosis;
        }
    }
    listen(integer channel, string name, key id, string message) {
        handle_server_selection_input(message);   
    }

    remote_data(integer type, key channel, key message_id, string sender, integer ival, string sval) {
        if (type == REMOTE_DATA_CHANNEL) { // channel created
            sloodle_debug("Channel opened");
            sloodle_debug("Ready to receive requests on channel \"" + (string)channel +"\"");
//            llLoadURL(toucheruuid,"Login to Moodle and confirm that you trust this object",toucherurl+sloodleobjectvalidationbase+"?sloodleobjuuid="+(string)llGetKey()+"&sloodleobjname="+llEscapeURL(llGetObjectName())+"&sloodlechannel="+(string)channel);
            llLoadURL(toucheruuid,"Login to Moodle and confirm that you trust this object",toucherurl+sloodleobjectvalidationbase+"?sloodleobjuuid="+(string)llGetKey()+"&sloodlechannel="+(string)channel);
            sloodle_set_text(toucherurl + "\nWaiting for Moodle to send me authorization...");;
        } else if (type == REMOTE_DATA_REQUEST) { // handle requests sent to us
            sloodle_debug("Request received"+sval);
            // handle request
            sloodleserverroot = toucherurl;
            pwdcode =  (string)sval;
            pwd = (string)llGetKey()+"|"+pwdcode;        
            sloodle_debug("Got pwd "+pwd);
            sloodle_display_server_course_status();
            llRemoteDataReply(channel, message_id, "1", 1); 
            llCloseRemoteDataChannel(channel);      
            state authentication; 
        }  
    }
    link_message(integer sender_num, integer num, string str, key id) {
        sloodle_handle_command(str);
    }  
}

state server_404_diagnosis
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }     
    touch_start(integer total_number) {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        }  
    }    
    state_entry() {
        handle_server_selection_404();
    }   
    http_response(key request_id, integer status, list metadata, string body) {
        //TODO: if top page is a 404 as well, tell them the server's wrong.
        //      if not, tell them they may be missing the sloodle module.
        if(status == 200) {
            if(request_id == http_id) {
                handle_site_missing_sloodle();
            }
        } else {
            handle_site_missing();   
        }
        state server_selection;
    }
    link_message(integer sender_num, integer num, string str, key id) {
            sloodle_handle_command(str);
    }      
}
state authentication
{
    touch_start(integer total_number) {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        }
    } 
    state_entry() {
        require_sloodle_registration();    
    }
    http_response(key request_id, integer status, list metadata, string body) {
        if(status == 200) {
            if(request_id == http_id) {
                integer ok = handle_authentication_response(body);
                // Check the return code
                // 0 = error, 1 = registration process start, 2 = already fully registered
                if (ok == 0) {
                    llResetScript();
                } else if (ok == 2) {
                    state course_selection;
                }
            }
        }
    }
    remote_data(integer type, key channel, key message_id, string sender, integer ival, string sval) {
        if (type == REMOTE_DATA_CHANNEL) { // channel created
            sloodle_debug("Channel opened");
            sloodle_debug("Ready to receive requests on channel \"" + (string)channel +"\"");
            
            //string sloodlewelcomeurl = sloodleserverroot + sloodlewelcomebase;
            //llLoadURL(toucheruuid,"Go here and login to Moodle",sloodlewelcomeurl+"?sloodleuuid="+(string)toucheruuid+"&sloodlelst="+sloodle_toucher_code+"&sloodlechannel="+(string)channel);
            llLoadURL(toucheruuid, "Go here and login to Moodle", toucherloginurl + "&sloodlechannel="+(string)channel);

            sloodle_set_text(toucherurl + "\nWaiting for Moodle to send me authorization...");;
        } else if (type == REMOTE_DATA_REQUEST) { // handle requests sent to us
            sloodle_debug("Request received"+sval);
            
            // Split the response at each line (note: double-escaped newlines)
            list lines = llParseStringKeepNulls(sval, ["\\n"], []);
            integer numlines = llGetListLength(lines);
            list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
            integer statuscode = llList2Integer(statusfields, 0);
            key checkuuid = NULL_KEY;
            if (llGetListLength(statusfields) >= 7) checkuuid = llList2Key(statusfields, 6);
           
            // CHeck the status code
            if (statuscode > 0 && checkuuid == toucheruuid) {
               sloodle_display_server_course_status();
               llRemoteDataReply(channel, message_id, "OK", 1); 
               llCloseRemoteDataChannel(channel);      
               sloodle_debug("Authenticated toucher "+(string)toucheruuid);
               state course_selection; 
            } else {
               sloodle_debug("Ignoring message"+sval+" for toucher "+(string)toucheruuid);
            }
        }  
    }    
    link_message(integer sender_num, integer num, string str, key id) {
            sloodle_handle_command(str);
    }      
}

state course_selection
{
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }     
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        } 
    }    
    state_entry() {
        request_teacher_courses();
    }    
    http_response(key request_id, integer status, list metadata, string body) {
        if(status == 200) {
            if (request_id == http_id) {
                handle_teacher_courses_response(body);              
            }
        }
    }
    listen(integer channel, string name, key id, string message)
    {
        integer ok = handle_course_selection_response(message);
        if (ok == 1) {
            sloodle_debug("course selection response ok - going to next step");
            state next_step;   
        }
    }
    link_message(integer sender_num, integer num, string str, key id) {
            sloodle_handle_command(str);
    }      
}

state next_step
{
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0)) == 1) {
            state menu;  
        } 
    }  
    on_rez(integer start_param)
    {
        sloodle_set_text("");
        llResetScript();
    }     
    state_entry() {
        sloodle_tell_other_scripts("set:sloodleserverroot|"+sloodleserverroot);
        sloodle_tell_other_scripts("set:sloodle_courseid|"+(string)sloodle_courseid);
        sloodle_tell_other_scripts("set:pwd|"+pwd);
        sloodle_tell_other_scripts("set:toucheruuid|"+(string)toucheruuid);        
        sloodle_debug("course and server set - ready for next step");
        llSetTexture(SLOODLE_TEXTURE_READY,0);
    } 
    link_message(integer sender_num, integer num, string str, key id) {
        //sloodle_debug("got message "+(string)sender_num+str);
       // if ( (sender_num == LINK_THIS) && (num == sloodle_command_channel) ){
            sloodle_handle_command(str);
        //}   
    }       
}

