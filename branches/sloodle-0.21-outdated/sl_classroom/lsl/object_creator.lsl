

// SL Classroom Creator
// Copyright Edmund Edgar, 2006-12-23

// This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

string sloodleserverroot = ""; //"http://moodle.edochan.com";
string pwd = "";
string pwdcode = "";
string pwduuid = NULL_KEY;

key controlleruuid = NULL_KEY;

integer object_dialog_channel = -3857343;
integer object_creator_channel = -3857361;

integer avatar_dialog_channel = 3857361;

integer SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_LIST_INVENTORY = -1639270011;
integer SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_LIST_INVENTORY = -1639270021;
integer SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL = -1639270022;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

integer SLOODLE_RESTRICT_TO_OWNER = 1;

key http_id; 
integer listen_id;
 
string toucheravname;
key toucheruuid = NULL_KEY; 

string toucherurl = "";
 
integer sloodle_courseid = 0;

integer is_ready = 0;
vector pos_to_rez = <1.0,1.0,1.0>;

list objectuuids = [];

// a list of how many we have rezzed of each object.
// we'll then have it should that back to the server when it wants to set itself up.
// for example, if a course has 2 quizzes, number 1 will know it's number 1 and number 2 will know it's number 2.
// the server knows that number 1 is quiz id 15, and number 2 is quiz id 3.
// this allows us to get both quizzes in the classroom without confguring them both individually.
list objecttypeobjects = [];
list objecttypecounts = [];
integer sloodleobjecttypecount;

sloodle_debug(string msg)
{
//    llWhisper(0,msg);
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

object_initialize(key uuid)
{
    // Tell the object the sloodle install url    

    if (controlleruuid == NULL_KEY) {
        controlleruuid = llGetKey(); // we'll control the object we've rezzed.
    }    
    string trustmecode = llGetSubString((string)pwdcode,0,3); // pass the first 4 digits of the opwd we gave the object when we rezzed it. This will prove that we are the rezzer.
    object_command(uuid, "set:controllercode|"+trustmecode);
    object_command(uuid, "set:pwduuid|"+pwduuid);        
    object_command(uuid, "set:controlleruuid|"+(string)controlleruuid);    
    object_command(uuid, "set:sloodleserverroot|"+sloodleserverroot);
    object_command(uuid, "set:sloodle_courseid|"+(string)sloodle_courseid);
    object_command(uuid, "set:sloodleobjecttypecount|"+(string)sloodleobjecttypecount); // TODO: Check if there are ways this could get out of sync...    
}

// rez a prim at the position specified.
// return -1 on failure, the number of that object rezzed so far on success.
rez_sloodle_prim(string name, vector relative_position, integer intopwd)
{
    llRezObject(name, llGetPos() + relative_position, ZERO_VECTOR, ZERO_ROTATION, intopwd);   
    integer objin = llListFindList(objecttypeobjects,[name]);
    integer count = 1;
    if (objin == -1) {
        objecttypeobjects = objecttypeobjects + [name];
        objecttypecounts = objecttypecounts + [count];
    } else {
        count = llList2Integer(objecttypecounts,objin);
        count++;
        llListReplaceList(objecttypecounts,[count],objin,objin);
    }
    sloodleobjecttypecount = count;;
}

sloodle_handle_command(string str) 
{
    //llWhisper(0,"handling command "+str);    

    sloodle_debug("classroom creator handing command :"+str+":");
    list bits = llParseString2List(str,["|"],[]);
        string name = llList2String(bits,0);
        string value = llList2String(bits,1);
        if (name == "set:sloodleserverroot") {
            sloodleserverroot = value;
        } else if (name == "set:pwd") {
            if (llGetListLength(bits) == 3) {
                pwduuid = (key)value;                
                pwdcode = llList2String(bits,2);
                pwd = (string)pwduuid + "|" + pwdcode;
            } else {
                pwdcode = value;
                pwd = pwdcode;
            }
        } else if (name == "set:sloodle_courseid") {
            sloodle_courseid = (integer)value;
        } else if (name == "set:toucheruuid") {
            toucheruuid = (key)value;            
        } else if (name == "do:rez") {
            sloodle_debug("doing rez");
            string objecttorez = value;
            if (llGetListLength(bits) == 3) {
                string strpos = llList2String(bits,2);
                rez_sloodle_prim(objecttorez,(vector)strpos,(integer)pwdcode);               
            }
        } else if (name == "list:inventory") {
            sloodle_debug("returning inventory list");
            integer ni = llGetInventoryNumber(INVENTORY_OBJECT);
            integer i = 0;
            list inv = [];
            for (i=0; i<ni; i++) {
                inv = inv + [llGetInventoryName(INVENTORY_OBJECT,i)];   
            }
            llMessageLinked(LINK_ALL_OTHERS,SLOODLE_CHANNEL_OBJECT_PROFILE_SAVER_LIST_INVENTORY,llList2CSV(inv),NULL_KEY);
        } else if ( (name == "do:cleanup_all") || (name == "CLEANUP") ) {
            cleanup_all_objects(); // TODO: Add error handling etc
        } else if (name == "do:reset") {
            sloodle_debug("object_creator got do reset message - resetting");
            llResetScript();        
        } else {
            sloodle_debug("command "+name+" not recognized");
        } 

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0)  && (toucheruuid != NULL_KEY) ) {
        sloodle_debug("ready");
        is_ready = 1;
    }
}

cleanup_all_objects()
{
    sloodle_debug("planning to cleanup "+(string)llGetListLength(objectuuids));
    integer i;
    for (i=0;i<llGetListLength(objectuuids);i++) {
        object_command(llList2Key(objectuuids,i),"CLEANUP");
    }    
    objectuuids = [];
}

integer handle_touch(key thistoucher) 
{
    // let anyone do this for now...

    
    if (toucheruuid != NULL_KEY) {
        if ( (SLOODLE_RESTRICT_TO_OWNER == 1) || (thistoucher != toucheruuid) ) {
            if (thistoucher != llGetOwner()) {
                if (SLOODLE_RESTRICT_TO_OWNER == 1) {
                    llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);
               } else {
                    llSay(0,llKey2Name(thistoucher)+", this object is currently in use by "+llKey2Name(toucheruuid)+".");   
                    //llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE); 
                }
                return 0;
            }
       }
    }


//    if ( (SLOODLE_RESTRICT_TO_OWNER == 1) || && thistoucher != llGetOwner()) {
//        llDialog(thistoucher,"This object can only be used by its owner.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);
//        return 0;
//    }

    toucheruuid = thistoucher;
    toucheravname = llKey2Name(toucheruuid);
    return 1;
}

default 
{
    touch_start(integer total_number)
    {
        if (handle_touch(llDetectedKey(0))) {
            if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
                sloodle_debug("Waiting for configuration");
                is_ready = 0;
                llDialog(llDetectedKey(0), "Can't give you any objects yet - server and course aren't set yet.\nUse the control panel next to me to set the server and course, then click me again to rez an object.", [], avatar_dialog_channel);
            } else {
                is_ready = 1;
                integer invnum = llGetInventoryNumber(INVENTORY_OBJECT);
                integer i;
                list menu =[];
                string caption = "Choose an object to rez:";
                for (i=0; i<invnum && i<12; i++) {
                    integer disp = i+1;
                    caption = caption +"\n"+ (string)disp+": "+llGetInventoryName(INVENTORY_OBJECT,i);
                    menu = menu + [(string)disp];
                }
                //list revmenu = [];
                // reverse the menu so the buttons come out in the right order
                //integer mentop = llGetListLength(menu) - 1;
                //for (i=mentop;i>=0; i--) {
                  //  revmenu = revmenu + [llList2String(menu,i)];
                //}
                // no good - comes out wrong a different way..
                llListen( avatar_dialog_channel, "", llDetectedKey(0),"");           
                llDialog(llDetectedKey(0), caption, menu, avatar_dialog_channel);
            }
        }
    }
    link_message(integer sender_num, integer num, string str, key id) {
        sloodle_debug("got message "+(string)sender_num+str);
        if ( (num == object_dialog_channel) || (num == object_creator_channel) || (num == SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_LIST_INVENTORY) || (num == SLOODLE_CHANNEL_OBJECT_INVENTORY_VENDOR_DO_CLEANUP_ALL) ) {
            sloodle_handle_command(str);
        } else {
            sloodle_debug("ignoring command "+str+" - num "+(string)num+" is not object_dialog_channel "+(string)object_dialog_channel);
        } 
    } 
    listen( integer channel, string name, key id, string message ) 
    {
        if (channel == avatar_dialog_channel) {
            integer objindex = (integer)message;
            objindex--;
            string objname = llGetInventoryName(INVENTORY_OBJECT,objindex);
            integer type = llGetInventoryType(objname);
            if (type == -1) {
                llDialog(llDetectedKey(0), "Object "+message+" not found.", [], avatar_dialog_channel);                
            } else {
                sloodle_debug("tring to rez prim "+objname);
                rez_sloodle_prim(objname,pos_to_rez,(integer)pwdcode);
            }
        } else {
            sloodle_debug("ignoring message "+message);
        }
    }    
    object_rez(key id) {
        objectuuids = objectuuids + [id];
        object_initialize(id);         
    }     
}