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
integer DEBUG=FALSE;
string  SLOODLE_HQ_LINKER = "/mod/sloodle/mod/hq-1.0/linker.php";
key http; 
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer gameid;
//linked message channels we use to communicate with the other scripts 
integer PLUGIN_CHANNEL=998821; //channel api commands come from  
integer PLUGIN_RESPONSE_CHANNEL=998822; //channel the api responds on
integer RESET_CHANNEL= 998823; //channel used to reset the _sloodle_api script
integer REGISTRATION_CHANNEL= 9988224; 
//variables used to gain access through the sloodle authentication layer
string  sloodleserverroot = ""; 
string  sloodlepwd = ""; //password of the controller who's activites we wish access to
integer sloodlecontrollerid = 0;//id of the controller
integer sloodlemoduleid = 0;//course module id 
integer sloodleid;//module id
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
string sloodleCourseName;
integer coursemoduleid;
// *************************************************** TRANSLATION VARIABLES
// Translation channel that we send translation requests on
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
string  SLOODLE_EOF = "sloodleeof";//end of file, should be the end of a sloodle_config file
integer eof= FALSE;
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
//variables we use to read notecards / http responses
list dataLines;
integer numLines;
integer isconfigured;
string sloodledata;
integer ON=0;
integer OFF=1;
key owner;
integer debugCheck(){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        return TRUE;
    }
        else return FALSE;
    
}
debug(string str){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        llOwnerSay(str);
    }
}
reinitialise()
        {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
            llResetScript();
}
// *************************************************** SLOODLE TRANSLATION
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}
/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}//end function
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}//end function
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}//end function
vector v (string vv){
    return llList2Vector(llParseString2List(vv, [":"], []),1);
}//end function

/*******************************************************************************************************
* sendCommand is a function to make it easier to communicate with the server.
*
* In order to establish a SloodleSession, there are a few http vars that need to be passed
* to the Sloodle API's linker.php
*
*  These variables include:  sloodlecontrolerid, sloodlepwd, &sloodleserveraccesslevel
*  In addition, the uuid of the user trying to access the commands are also needed 
*  by Sloodle, because if a user is trying to access a command on Moodle, say - to view Course Groups,
*  we have to first check to see what permissions that user has on the moodle system - ie:
*  are they allowed to view course groups.
*
* NOTE!!!!!
* It is important for plugin developers to  take careful considerations of users capabilities of the user 
*  when performing functions in MOODLE through this API
*     
********************************************************************************************************/
sendCommand(string plugin,string function, string vars){
        //add important sloodle variables that are required to establish a connection into SLOODLE
            string requiredVars  = "&sloodlecontrollerid=" + (string)sloodlecontrollerid;    
            requiredVars+= "&sloodlepwd=" + (string)sloodlepwd;
            requiredVars += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;       
            vars+="&gameid="+(string)gameid;
        //set timer to detect timeouts
            llSetTimerEvent(20);
        //send the request
             http = llHTTPRequest(sloodleserverroot + SLOODLE_HQ_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],  sloodleserverroot + SLOODLE_HQ_LINKER+"?"+"&plugin="+plugin+ "&function="+function+requiredVars+"&"+vars);
         //debug

            debug("********** _sloodle_api.lsl SENDING TO SERVER *********************");                  
debug(sloodleserverroot + SLOODLE_HQ_LINKER+"?"+"&plugin="+plugin+ "&function="+function+requiredVars+"&"+vars);         

}//sendCommand

list facilitators;
string sloodlecoursename_short;
string sloodlecoursename_full;
/*******************************************************************************************************************
*   sloodle_handle_command is used to parse all configuration data read from sloodle_config by sloodle_setup_notecard.  
*   Once sloodle_config is read, all lines are output to:  SLOODLE_CHANNEL_OBJECT_DIALOG, and handled by this function
/*******************************************************************************************************************/ 
integer sloodle_handle_command(string str) {         
		if (str=="do:requestconfig"||str=="do:reset")llResetScript();
        list bits = llParseString2List(str,["|"],[]);
        integer numbits = llGetListLength(bits);
        string name = llList2String(bits,0);
        string value1 = "";
        string value2 = "";
        if (numbits > 1) value1 = llList2String(bits,1);
        if (numbits > 2) value2 = llList2String(bits,2);        
        if (name == "facilitator")facilitators+=llStringTrim(llToLower(value1),STRING_TRIM);else
        if (name =="set:sloodleserverroot") sloodleserverroot= value1; else
        if (name =="set:sloodlecontrollerid") sloodlecontrollerid= (integer)value1; else 
        if (name =="set:sloodlecoursename_short") sloodlecoursename_short= value1; else
        if (name =="set:sloodlecoursename_full") sloodlecoursename_full= value1; else
        if (name =="set:sloodlepwd") sloodlepwd= value1; else
        if (name =="set:sloodleid") {
            sloodleid= (integer)value1; 
        }
        else 
         if (name == SLOODLE_EOF) return TRUE;
         return FALSE;
    }

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}//end random_integer

/************************************************************************
 ************************************************************************
                                     BEGIN STATE DEFINITION
 ************************************************************************
************************************************************************/
default {

    //on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();       
    }
    state_entry() {
        //get owner key
         owner = llGetOwnerKey(llGetKey());
        //request variables from sloodle_config       
    }
    //link_message - handle any config parameters that come on the object dialog channel from the sloodle_notecard_setup script
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
                    if (sloodle_handle_command(llList2String(dataLines,i))==TRUE) state ready;
                }//endfor
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
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "apiconfigurationreceived", [], NULL_KEY, "hq");
debug("Api is ready");
       llMessageLinked(LINK_SET, PLUGIN_RESPONSE_CHANNEL, "COMMAND:API READY|SOURCE:sloodle_api_new.lsl", NULL_KEY);
    }//state_entry
    
    
    /*******************************************************************************************
    * link_message receives commands from other scripts wishing to access the API on channel PLUGIN_CHANNEL
    * Once receieved, it parses the incoming string to learn:
    * 1) The plugin filename
    * 2) The function name in the file that is being requested
    * 3) Extra variables to place on the url request
    * 4) A data string from SL that contains the parameters destined for the function
    *
    * A few typical api function calls in LSL would look like 
    * 
    * llMessageLinked(LINK_SET,PLUGIN_CHANNEL, "general->getSloodleObjects\n\ntype:presenter|index:0|itemsperpage:10", id); //gets all the sloodle presenters in the controllers course
    * llMessageLinked(LINK_SET,PLUGIN_CHANNEL, "general->getSloodleObjects\n\ntype:distributer|index:0|itemsperpage:10", id); //gets all the sloodle distributers in the controllers course
    * llMessageLinked(LINK_SET,PLUGIN_CHANNEL, "general->getSloodleObjects\n\ntype:awards|index:0|itemsperpage:10", id); //gets all the sloodle awards  in the controllers course
    * llMessageLinked(LINK_SET,PLUGIN_CHANNEL, "user->getClassList\nsloodleid=183\nsenderuuid:uuid|index:0|sortmode:balance", id); //gets a list of users in the course along with award data, starting at index 0, sorted by balance
    *******************************************************************************************/
    link_message(integer sender_num, integer channel, string str, key id) {        
    		if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
    			sloodle_handle_command(str);
    		}
            if (channel==PLUGIN_CHANNEL){
                //debug
                    debug("******************************************************");
                    debug("********** _sloodle_api.lsl got message: ******************** \n\n" + str+"\n\n");
                    debug("******************************************************"); 
               //string will look like:
               //groups->addgrp?extraVar1=val&extraVar2=val2&extraVar3=val3&data=team|...
               integer varStartIndex =llSubStringIndex(str,"&");
              //parse the plugin and function out    
               string cmdStr = llGetSubString(str, 0, varStartIndex-1);
                       list cmdLine = llParseString2List(cmdStr,["->"],[]); //plugin:groups,function:checkEnrols
                //the plugin var determines what .php plugin file our function is located in ie: www.yoursite.com/moodle/mod/sloodle/plugins/general.php      
                    string plugin= llList2String(cmdLine,0);
                //function is the name of the function in the file    
                    string function = llList2String(cmdLine,1);
                //extra variables are all the variables passed in that are to be placed in the url request     
                    string vars = llGetSubString(str,varStartIndex+1,llStringLength(str)-1);                                        
                    sendCommand(plugin,function,vars);                        
            }//end PLUGIN_CHANNEL 
            else  
            if (channel==UI_CHANNEL){
                 list cmdList = llParseString2List(str,["|"],[]);
                 string cmd= s(llList2String(cmdList,0));
                //check to see if any commands are currently being processed
                    if (cmd=="GAMEID")
                        gameid=i(llList2String(cmdList,1));
            }//end PLUGIN_CHANNEL
            else
            if (channel==RESET_CHANNEL){
                if (str=="RESET") llResetScript();    
            } //end RESET_CHANNEL
            else 
            if (channel==REGISTRATION_CHANNEL){
                //function:regenrol|avName:fire|avuuid:uuid
                 list cmd = llParseString2List(str, ["|"], []);
                 string fnc = s(llList2String(cmd,0));
                 if (fnc=="regenrol"){
                     string avuuid =s(llList2String(cmd,2));
                     llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:regenrol|" + sloodleserverroot + "|" + (string)sloodlecontrollerid + "|" + sloodlepwd,avuuid);
                 }//end if fnc=regenrol
            }//REGISTRATION_CHANNEL          
    }//link message
         http_response(key id,integer status,list meta,string body) {
                   if (debugCheck()) {
                           llOwnerSay("************* SERVER RESPONSE ****************************");
                           list result= llParseString2List(body, ["\n"], []); //parse the message into a list
                        integer len = llGetListLength(result);
                        integer j=0;
                        for (j=0;j< len;j++){
                           llSay(0,"* "+(string)j+") "+llList2String(result,j ));
                        }//end for
                        llSay(0,"******************************************************\n");
                   }//end debug
        if ((id != http)) return;
        http = NULL_KEY;
        if ((status != 200)) {
            return;
        }//endif
        //reset timeout timer
        llSetTimerEvent(0.0);
        //retrieve lines from the http body   
        llMessageLinked(LINK_SET, PLUGIN_RESPONSE_CHANNEL, body, NULL_KEY);  
        body="";//VERY IMPORTANT - LAGE UNEMPTIED STRINGS ARE SOURCES OF MEMORY LEAKS!!!
     }//end http
     
     /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script, remove this if you need to
    *        
    ***********************************************/
    changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//end if
      if (change==CHANGED_LINK){
     	if (llAvatarOnSitTarget()==NULL_KEY) llResetScript();
     }
    }//end changed
}//end state
  
