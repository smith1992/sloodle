 /*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
* 
*
*  This script is part of the SLOODLE Project see http://sloodle.org
*
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
* _sloodle_api.lsl 
*
/**********************************************************************************************/
string  SLOODLE_HQ_LINKER = "/mod/sloodle/mod/hq-1.0/linker.php";
integer handle;
key http; 

// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
// *************************************************** HOVER TEXT VARIABLES
string newGrp;
integer PLUGIN_CHANNEL=998821;
integer PLUGIN_RESPONSE_CHANNEL=998822;
integer RESET_CHANNEL= 998823;
integer REGISTRATION_CHANNEL= 9988224;
string  sloodleserverroot = "";
string  sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleid;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
// *************************************************** TRANSLATION VARIABLES
// This is common translation code.
// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// *************************************************** TRANSLATION OUTPUT METHODS
string SLOODLE_TRANSLATE_HOVER_TEXT_BASIC = "hovertextbasic";
string  SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string  SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string  SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string  SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string  SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string  SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
string  SLOODLE_EOF = "sloodleeof";
integer ADMIN_CHANNEL =82;  //used for dialog messages during setup
 integer MENU_CHANNEL;
key ownerKey;
// *************************************************** LISTS TO HOLD FIELD VALUES OF DATAROW RECORD SETS
// *************************************************** AUTHENTICATION CONSTANTS
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
integer eof= FALSE;
list dataLines;
integer numLines;
integer isconfigured;
string sloodledata;
integer DEBUG=0;
integer ON=0;
integer OFF=1;
key owner;
integer INPUT_CHANNEL;
string sloodlecoursename;
integer SETTEXT_CHANNEL=-776644;
// *************************************************** SLOODLE TRANSLATION
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

/***********************************************
*  s()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return a string
***********************************************/
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}
/***********************************************
*  k()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return a key
***********************************************/
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}
/***********************************************
*  i()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return an integer
***********************************************/
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}
/****************************************************************************************************
* sendCommand(string command, string data,api)
*  This command wraps the award command and data into something the linker.php can read
*
*  
****************************************************************************************************/
sendCommand(string plugin,string function, integer sloodleid,string data){
        sloodledata  ="sloodlecontrollerid=" + (string)sloodlecontrollerid;
        sloodledata  +="&sloodleid=" + (string)sloodleid;
        sloodledata  += "&sloodlepwd=" + (string)sloodlepwd;
        sloodledata += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;       
        sloodledata +="&sloodleuuid="+(string)llGetOwner();
        sloodledata +="&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
        data = llEscapeURL(data);   
        llSetTimerEvent(20);
         http = llHTTPRequest(sloodleserverroot + SLOODLE_HQ_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],  "?"+sloodledata+"&plugin="+plugin+"&function="+function+"&data="+data+"&sloodlecontrollerid=" + (string)sloodlecontrollerid);
           if (DEBUG==ON){
           //llOwnerSay("sloodleapi script:  sending this to linker.php\n"+sloodleserverroot + SLOODLE_HQ_LINKER+"?"+sloodledata+"&plugin="+plugin+ "&function="+function+"&data="+data);
        }      
        llMessageLinked(LINK_SET,SETTEXT_CHANNEL,"DISPLAY::top display|STRING::Contacting MOODLE:\nFunction: "+function+ "\nPlease wait|COLOR::"+(string)PINK+"|ALPHA::1.0",NULL_KEY); 
}

debugMessage(string s){
   llOwnerSay((string)llGetFreeMemory()+"********************** "+s);
   s="";
}
/****************************************************************************************************
* handles sloodle configuration
****************************************************************************************************/
integer sloodle_handle_command(string str){
    dataLines = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(dataLines);
    string name = llList2String(dataLines,0);
    string value1 = "";
    string value2 = "";
    if (numbits > 1) value1 = llList2String(dataLines,1);
    if (numbits > 2) value2 = llList2String(dataLines,2);
    if (name == "set:sloodleserverroot") sloodleserverroot = value1;
    else if (name == "set:sloodlepwd") {       
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
    } else 
    if (name == "set:sloodlecontrollerid") {
        sloodlecontrollerid = (integer)value1;     
    }//endif
   else 
   if (name=="set:sloodlecoursename_full"){
        sloodlecoursename = (string)value1;        
   }//endif
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    
    // TODO: Add additional configuration parameters here
    else if (name == SLOODLE_EOF) eof = TRUE;
    // This line figures out if we have all the core data we need.
    // TODO: If you absolutely need any other core data in the configuration, then add it to this condition.
    dataLines = [];
    str="";
    name="";
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0);
}
/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max )
{
  return min + (integer)( llFrand( max - min + 1 ) );
}
default {
    /***********************************************
    *  on_rez event
    *  |--> Reset Script to ensure proper defaults on rez
    ***********************************************/
    on_rez(integer start_param) {
        llResetScript();       
    }
    state_entry() {
         owner = llGetOwnerKey(llGetKey());
         //request configuration
         llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", "0"); 
    }
    link_message(integer sender_num, integer link_channel, string str, key id) {
         if (link_channel == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            dataLines=[];
            //parse each line into the list
            dataLines = llParseString2List(str, ["\n"], []);                       
            isconfigured=FALSE;
            //get number of lines received
            numLines =  llGetListLength(dataLines);
            integer i;
            for (i=0; i<numLines; i++) {
                isconfigured = sloodle_handle_command(llList2String(dataLines,i));
            }//endfor
            dataLines = [];          
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");                    
                       state ready;
                } //endif
                else {
                    // Got all configuration but, it's not complete... request reconfiguration
                    //sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);                    
                    eof = FALSE;
                }//end else
            }//endif
        dataLines=[];
    }//endif
}//end linked message
    
    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//endif
    }//end changed
}//end default state
state ready{
      /***********************************************
    *  on_rez event
    *  |--> Reset Script to ensure proper defaults on rez
    ***********************************************/
    on_rez(integer start_param) {
        llResetScript();
    }//end on_rez
    state_entry() {
      //tell other scripts the API is ready
       llMessageLinked(LINK_SET, PLUGIN_RESPONSE_CHANNEL, "COMMAND:API READY|SOURCE:sloodle_api.lsl", NULL_KEY);
    }//
    
    link_message(integer sender_num, integer channel, string str, key id) {
            if (channel==PLUGIN_CHANNEL){
                  //  llOwnerSay("sloodleapi received linked message: "+str);
                    //PLUGIN:groups|FUNCTION:addgrp\nSLOODLEID:sloodleid\nteam:team|...
                    list lines =llParseString2List(str,["\n"], []);
                    list cmdLine = llParseString2List(llList2String(lines,0),[","],[]); //plugin:groups,function:checkEnrols 
                    string plugin= s(llList2String(cmdLine,0));
                    string function = s(llList2String(cmdLine,1));
                    integer sloodleid=i(llList2String(lines,1));
                    string data= llList2String(lines,2);
                    if (llGetListLength(lines)>=2){
                           sendCommand(plugin,function,sloodleid,data);
                    }      
            } else  if (channel==RESET_CHANNEL){
                    if (str=="RESET") llResetScript();    
            } else if (channel==REGISTRATION_CHANNEL){
                //function:regenrol|avName:fire|avuuid:uuid
                 list cmd = llParseString2List(str, ["|"], []);
                 string fnc = s(llList2String(cmd,0));
                 if (fnc=="regenrol"){
                     string avuuid =s(llList2String(cmd,2));
                     llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:regenrol|" + sloodleserverroot + "|" + (string)sloodlecontrollerid + "|" + sloodlepwd,avuuid);
                 }
            }                 
    }
         http_response(key id,integer status,list meta,string body) {
             llMessageLinked(LINK_SET,SETTEXT_CHANNEL,"DISPLAY::top display|STRING::Sloodle HQ:\nCourse: "+sloodlecoursename+" \nReady|COLOR::"+(string)GREEN+"|ALPHA::1.0",NULL_KEY);
                //   llOwnerSay("sloodleapi received http message: \n"+body);
        if ((id != http)) return;
        (http = NULL_KEY);
       
        if ((status != 200)) {
            return;
        }
        
             llSetTimerEvent(0.0);
            //retrieve lines from the http body   
            llMessageLinked(LINK_SET, PLUGIN_RESPONSE_CHANNEL, body, NULL_KEY);  
            body="";//VERY IMPORTANT - LAGE UNEMPTIED STRINGS ARE SOURCES OF MEMORY LEAKS!!!
        }
           /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }
    }
}
  

