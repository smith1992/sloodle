// Sloodle profile manager
// Organises classroom profiles for a Sloodle Set
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//

string sloodleserverroot = "";
string pwd = "";
string pwdcode = "";

integer object_dialog_channel = -3857343;
integer object_creator_channel = -3857361;

string sloodleprofilebase = "/mod/sloodle/sl_classroom/sl_profile_linker.php";

integer avatar_dialog_channel = 3857362;
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;
integer SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_DO_SAVE = -1639270012;
integer SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL = -1639270022;

integer SLOODLE_RESTRICT_TO_OWNER = 1;

key http_id; 
integer listen_id;

string toucheravname;
key toucheruuid = NULL_KEY;

string toucherurl = "";

integer sloodle_courseid = 0;

integer is_ready = 0;

string object_state = "";

list objectprofileids;
list objectprofilenames;

integer objectbeingrezzedindex;
list objectnames;
list objectuuids;
list objectrelativepositions;
list objectentryids;
integer objectsallrezzed = 0;

integer objectprofileid = 0;
string objectprofilename = "";

list savemeuuids;
list savemenames;
list savemepos;

list ignoremeuuids;
list ignoremenames;

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, object_dialog_channel, msg, NULL_KEY);   
}

// Controlling objects
object_command(key uuid, string msg) {
    // TODO: say or shout or whatever depending on distance
    llSay(object_dialog_channel, (string)uuid+"|"+msg);    
    sloodle_debug("COMMAND SENT:"+(string)uuid+":"+msg);
}

// Controlling objects
single_object_command(key uuid, string msg, integer channel) {
    // TODO: say or shout or whatever depending on distance
    llSay(object_dialog_channel, (string)uuid+"|"+msg);    
    sloodle_debug("COMMAND SENT:"+(string)uuid+":"+msg);
}

request_course_profiles() 
{
    sloodle_debug("Fetching data for course "+(string)sloodle_courseid);
    string url = sloodleserverroot + sloodleprofilebase + "?sloodlecmd=listprofiles&sloodlepwd=" + pwd;
    url = url+"&sloodleavname="+llEscapeURL(toucheravname)+"&sloodleuuid="+(string)toucheruuid+"&sloodlecourseid="+(string)sloodle_courseid;
    http_id = llHTTPRequest(url,[],"");
}

// This function is called with our list of profiles in the course
handle_course_profile_response(string body)
{    
    sloodle_debug("course_profile_response:"+body);
    
    // Split the response into each line, and extract the status fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    integer numlines = llGetListLength(lines);

    // Check the status code
    if (statuscode > 0) {
        // Success!
        // Reset our profile lists
        objectprofileids = [];
        objectprofilenames = [];
        
        // Go through each profile
        integer i = 1;
        for (i = 1; i < numlines; i++) {
            // Split the current line into fields
            list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
            // Make sure we have the correct number of parts, and extract them
            if (llGetListLength(fields) >= 2) {
                objectprofileids = objectprofileids + [llList2Integer(fields,0)];
                objectprofilenames = objectprofilenames + [llList2String(fields,1)];
            }
        }
                        
        sloodle_debug("Loaded profiles");        
        offer_profile_select();
    } else {
        
        // An error occurred - extract the error message
        string errormsg = "";
        if (numlines > 1) errormsg = llList2String(lines, 1);
        string msg = "";
        
        // Check for profile error codes for standard profile errors first
        if (statuscode == -901) msg = "(" + (string)statuscode + ") Unknown profile error. " + errormsg;
        else if (statuscode == -902) msg = "(" + (string)statuscode + ") Profile does not exist. " + errormsg;
        else if (statuscode == -903) msg = "(" + (string)statuscode + ") Profile already exists. " + errormsg;
        else if (statuscode == -904) msg = "(" + (string)statuscode + ") Unknown profile command. " + errormsg;
        else msg = "(" + (string)statuscode + ") General error. " + errormsg;
        
        // Report the error
        llSay(0, msg);
    }        
      
}

offer_profile_select()
{
    listen_id = llListen(avatar_dialog_channel, "", toucheruuid, "");
    
    if (llGetListLength(objectprofilenames) > 0) {    
        object_state = "offer_profile_select";
        llDialog(toucheruuid, "Choose your profile", objectprofilenames, avatar_dialog_channel);
    } else {
        object_state = "offer_create_profile";
        llDialog(toucheruuid, "There are currently no profiles to select. Do you want to create a new one?.", ["Create", "Cancel"], avatar_dialog_channel);
    }
}

sloodle_handle_command(string str) 
{
    //llWhisper(0,"handling command "+str);    

    sloodle_debug("classroom creator handing command "+str);
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        pwd = value;
        if (llGetListLength(bits) == 3) {
            pwdcode = llList2String(bits,2);
            pwd = pwd + "|" + pwdcode;
        }
    } else if (name == "set:sloodle_courseid") {
        sloodle_courseid = (integer)value;
    } else if (name == "set:toucheruuid") {
        toucheruuid = (key)value;            
    } else if (name == "do:reset") {
        llResetScript(); 
    }

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0)  && (toucheruuid != NULL_KEY) ) {
        sloodle_debug("ready");
        is_ready = 1;
    }
}

// This function is called with a list of entries in a profile which we wish to rez
integer handle_profile_entry_reponse(string body)
{
    sloodle_debug("Got profile entries "+body);
    
    // Split the response into each line, and extract the status fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    integer numlines = llGetListLength(lines);

    // Check the status code
    if (statuscode <= 0) {        
        // An error occurred - extract the error message
        string errormsg = "";
        if (numlines > 1) errormsg = llList2String(lines, 1);
        string msg = "";
        
        // Check for profile error codes for standard profile errors first
        if (statuscode == -901) msg = "(" + (string)statuscode + ") Unknown profile error. " + errormsg;
        else if (statuscode == -902) msg = "(" + (string)statuscode + ") Profile does not exist. " + errormsg;
        else if (statuscode == -903) msg = "(" + (string)statuscode + ") Profile already exists. " + errormsg;
        else if (statuscode == -904) msg = "(" + (string)statuscode + ") Unknown profile command. " + errormsg;
        else msg = "(" + (string)statuscode + ") General error. " + errormsg;
        
        // Report the error
        llSay(0, msg);
        return 0;
    }
    
    
    // Reset our rezzing variables
    objectbeingrezzedindex = 0;                
    objectentryids = [];
    objectnames = [];         
    objectuuids = [];
    objectrelativepositions = [];
    
    sloodle_debug("ready to populate objects");
    
    // Go through each entry in the response
    integer i = 1;
    for (i = 1; i < numlines; i++) {
        // Split the current line into fields
        list fields = llParseStringKeepNulls(llList2String(lines,i), ["|"], []);
        // Make sure we have enough items
        if (llGetListLength(fields) >= 3) {
            // We'll fill in objectuuids when we rez the objets
            objectentryids = objectentryids + [llList2Integer(fields,0)];
            objectnames = objectnames + [llList2String(fields,1)];
            objectrelativepositions = objectrelativepositions + [(vector)llList2String(fields,2)];
        }
    }

    return 1;
}

rez_all_objects()
{
    
    objectbeingrezzedindex = -1;
    rez_next_object(); // rezzing the following object will be called by the object_rez event 

}

// rez the next object in the list, return 1 if it rezzes something, 0 if there's nothing left to rez
// TODO: Set a timer to deal with if the object fails to rez...
integer rez_next_object()
{
    objectbeingrezzedindex++;
    if (llGetListLength(objectnames) > objectbeingrezzedindex) {
        
        string name = llList2String(objectnames,objectbeingrezzedindex);
        string pos = (string)llList2Vector(objectrelativepositions,objectbeingrezzedindex);
        
        sloodle_debug("Rezzing object "+name+" at "+pos);
        llMessageLinked(LINK_SET, object_creator_channel, "do:rez|"+name+"|"+pos,NULL_KEY );
        // TODO: Check for success...
        llSleep(2);
        rez_next_object();
    }
    objectsallrezzed = 1;
    return 0;
}

fetch_profile_entry_data()
{
    sloodle_debug("Fetching data for profileid "+(string)objectprofileid);        
    string url = sloodleserverroot + sloodleprofilebase + "?sloodlecmd=listentries&sloodlepwd=" + pwd;
    url = url+"&sloodleavname="+llEscapeURL(toucheravname)+"&sloodleuuid="+(string)toucheruuid+"&sloodleprofilename="+llEscapeURL(objectprofilename)+"&sloodlecourseid="+(string)sloodle_courseid;
    // llWhisper(0,"Requesting login URL for "+llDetectedName(0));
    http_id = llHTTPRequest(url,[],"");
}

integer display_menu() // return listen_id if waiting for a response, 0  if not
{
        
    object_state = "display_menu";

    if ( (sloodleserverroot == "") || (pwd == "") ) {
        sloodle_debug("Waiting for configuration");
        is_ready = 0;
        llDialog(toucheruuid, "Can't setup any classes yet - server and course aren't set yet.\nUse the control panel next to me to set the server and course, then click me again to rez an object.", [], avatar_dialog_channel);
        return 0;
    } else {
        is_ready = 1;
    } 
        
    list menu_options = [];
    if (objectprofilename != "") {
        menu_options = ["Load", "Cleanup", "Save", "Save As","Cancel"]; 
    } else {
        menu_options = ["Load", "Cleanup", "Save As","Cancel"];
    }  
    sloodle_debug("showing menu to toucher "+(string)toucheruuid);
    llDialog(toucheruuid, "Menu Options", menu_options, avatar_dialog_channel);
    listen_id = llListen(avatar_dialog_channel, "", toucheruuid, "");            
    return listen_id;

}

integer handle_new_profile_reponse(string body)
{
    // Split the response into each line, and extract the status fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    integer numlines = llGetListLength(lines);
    
    // Check for a success code
    // We also don't care if the profile already existed (code -903)... so long as it exists now!
    // Also ensure that we have enough lines
    if ((statuscode > 0 || statuscode == -903) && numlines >= 2) {
        // Extract the fields from our data line
        list fields = llParseStringKeepNulls(llList2String(lines,1), ["|"], []);
        objectprofileid = llList2Integer(fields,0);
        objectprofilename = llList2String(fields,1);        
        return 1;
    }

    // If we reach here, then an error occurred - extract the error message
    string errormsg = "";
    if (numlines > 1) errormsg = llList2String(lines, 1);
    string msg = "";
    
    // Check for profile error codes for standard profile errors first
    if (statuscode == -901) msg = "(" + (string)statuscode + ") Unknown profile error. " + errormsg;
    else if (statuscode == -902) msg = "(" + (string)statuscode + ") Profile does not exist. " + errormsg;
    else if (statuscode == -904) msg = "(" + (string)statuscode + ") Unknown profile command. " + errormsg;
    else msg = "(" + (string)statuscode + ") General error. " + errormsg;
    
    // Report the error
    llSay(0, msg);
    return 0;
}

integer handle_touch(key thistoucher) 
{

    if (toucheruuid != NULL_KEY) {
        if ( (SLOODLE_RESTRICT_TO_OWNER == 1) || (thistoucher != toucheruuid) ) {
            if (thistoucher != llGetOwner()) {
                if (SLOODLE_RESTRICT_TO_OWNER == 1) {
                    llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);
                } else {
                    //llSay(0,llKey2Name(thistoucher)+", this object is currently in use by "+llKey2Name(toucheruuid)+".");   
                    llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE); 
                }
                return 0;
            }
        }
    }
    toucheruuid = thistoucher;
    toucheravname = llKey2Name(toucheruuid);
    return 1;
}

default 
{
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0))) {        
            display_menu();
        }
    }
    link_message(integer sender_num, integer num, string str, key id) {
        
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        
        sloodle_debug("got message "+(string)sender_num+":"+str);
        if (num == object_dialog_channel) {
            sloodle_handle_command(str);
        }   
    } 
    listen( integer channel, string name, key id, string message ) 
    {
        if (channel == avatar_dialog_channel) {
            
            sloodle_debug("Got listen message on avatar dialog channel: " + message);
            sloodle_debug("object state is "+object_state);
            
            if (object_state == "offer_profile_select") {
                
                objectprofilename = llStringTrim(message, STRING_TRIM);
                integer num = llListFindList(objectprofilenames, [objectprofilename]);                
                if (num >= 0) objectprofileid = llList2Integer(objectprofileids, num);
                else objectprofileid = 0;
                object_state = "fetch_profile_entry_data";
                fetch_profile_entry_data();
                
            } else {

                //llWhisper(0,"message was "+message);
                if (message == "Save") {
                    sloodle_debug("sending save message");
                    string baseurl = sloodleserverroot+sloodleprofilebase+"?sloodlepwd="+pwd+"&sloodlecmd=saveentries&sloodleprofilename="+llEscapeURL(objectprofilename)+"&sloodleavname="+toucheravname+"&sloodleuuid="+(string)toucheruuid+"&sloodlecourseid="+(string)sloodle_courseid;
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_DO_SAVE, baseurl, NULL_KEY);   // send a message to the saving script
                } else if (message == "Save As" || message == "Create") {
                   object_state = "new_profile";
                   llDialog(toucheruuid,"Type /"+(string)SLOODLE_CHANNEL_AVATAR_SETTING+", followed by the name of your new profile, eg.\n/"+(string)SLOODLE_CHANNEL_AVATAR_SETTING+" My profile",[],SLOODLE_CHANNEL_AVATAR_IGNORE);
                    listen_id = llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", toucheruuid, "");
                    
                } else if (message == "Cleanup") {
                    llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL,"do:cleanup_all",NULL_KEY);
                    objectsallrezzed = 0;
                    
                    // Close the current profile:
                    objectprofilename = "";
                    objectprofileid = 0;
                    
                } else if (message == "Load") {
                    sloodle_debug("getting course profiles");
                    object_state = "request_course_profiles";
                    request_course_profiles();
                } else if (message == "Cancel") {
                    // Nothing to do
                } else {
                    sloodle_debug("message state not recognized "+message);
                }
              
            }
        } else if (channel == SLOODLE_CHANNEL_AVATAR_SETTING) {
            if (object_state == "new_profile") {
                objectprofilename = llStringTrim(message, STRING_TRIM);
                sloodle_debug("Saving profile "+objectprofilename);
                string url = sloodleserverroot+sloodleprofilebase+"?sloodlepwd="+pwd+"&sloodlecmd=newprofile&sloodleprofilename="+llEscapeURL(llStringTrim(objectprofilename,STRING_TRIM))+"&sloodlecourseid="+(string)sloodle_courseid+"&sloodleavname="+toucheravname+"&sloodleuuid="+(string)toucheruuid;        
                sloodle_debug("new profile:"+url);
                http_id = llHTTPRequest(url,[],"");
                llListenRemove(listen_id);
            }            
        } else {
            sloodle_debug("ignoring message "+message);
        }
    }    
     
    http_response(key request_id, integer status, list metadata, string body) {
        if(status == 200) {
            if (request_id == http_id) {
                if (object_state == "request_course_profiles") {
                    handle_course_profile_response(body);    
                } else if (object_state == "fetch_profile_entry_data") {
                    if (handle_profile_entry_reponse(body) == 1) {
                        rez_all_objects();
                    } else {
                        sloodle_debug("handling profile entry reponse failed");
                    }
                } else if (object_state == "new_profile") {
                    integer ok = handle_new_profile_reponse(body);
                    if (ok == 1) {
                        sloodle_debug("sending save message");       
                        string baseurl = sloodleserverroot+sloodleprofilebase+"?sloodlepwd="+pwd+"&sloodlecmd=saveentries&sloodleprofilename="+(string)objectprofilename+"&sloodleavname="+toucheravname+"&sloodleuuid="+(string)toucheruuid+"&sloodlecourseid="+(string)sloodle_courseid;
                        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_DO_SAVE, baseurl, NULL_KEY); 
                    } else {
                        llWhisper(0,"Failed, please try again");
                        listen_id = llListen(0, "", toucheruuid, "");
                    }                    
                } else {
                    sloodle_debug("ignoring an http response arriving with an unknown object state "+object_state);
                }         
            }
        }
    } 
}
