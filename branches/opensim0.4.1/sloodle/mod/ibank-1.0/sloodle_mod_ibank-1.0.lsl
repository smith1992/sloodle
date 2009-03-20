// Part of the SLOODLE project (www.sloodle.org)
// sloodle_mod_iBank-1.0 
//
// Copyright (c) 2009 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Preibisch - aka Fire Centaur
//

integer amount;
key avUuid;
integer debug=1;
integer ON = 1;
integer OFF = 0;
key http = NULL_KEY;
integer CHUNK_SIZE=10;
integer startupValue;
integer currIndex=0;
integer READ_SIZE=10;
integer chunk=0;
string  iCurrencyType;
//*************************************************** OTHER SCRIPTS IN THIS PRIM LISTEN FOR SPECIFIC LINK IDENTIFIERS
integer STIPEND_GIVER_CHANNEL=-7888;
integer BASE_MEMORY=7999;
integer HTTP_SCRIPT = 101;
integer ALL_MEMORY=9000;
integer currentMemoryStick;
//*************************************************** AUTHENTICATION CONSTANTS
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string  SLOODLE_EOF = "sloodleeof"; 
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0; 
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
string     SLOODLE_OBJECT_TYPE = "iBank-1.0";
// This string identifies the location of the linker script relative to this Moodle root
string SLOODLE_IBANK_LINKER = "/mod/sloodle/mod/ibank-1.0/linker.php"; 
// These are common configuration settings
string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0; 
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
integer sloodleautodeactivate = 1; // Should the WebIntercom auto-deactivate when not in use?
// *************************************************** CONFIGURATION VARS
// These are used when receiving configuration so we know when to move on
integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?
// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     ORANGE         = <1.43131, 0.39880, 0.00000>;//ORANGE
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     LIGHTBLUE     = <0.00000, 1.05512, 1.43188>;//BLUE
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     PURPLE         = <0.43909, 0.00000, 0.46274>;//VIOLET
vector     DARKBLUE        = <0.00000, 0.02192, 0.47989>;//DARKBLUE
// *************************************************** HOVER TEXT VARIABLES
string  sloodlestipend = "";
integer sloodleshowhovertext = 1;

// *************************************************** TRANSLATION VARIABLES
// This is common translation code.
// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;
// *************************************************** TRANSLATION OUTPUT METHODS
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
integer ADMIN_CHANNEL =82;  //used for dialog messages during setup
string ownerKey;
// *************************************************** LISTS TO HOLD FIELD VALUES OF DATAROW RECORD SETS
list     moodleIdList             = [];
list     moodleNameList         = [];
list     avatarNameList         = [];
list     channelList             = [];
list     sortedStudentList        = [];
list     avatarAllocationList    = [];
list     userDebitsList         = [];
list     modifiedStipendAmounts = [];
list userLines=[];
// *************************************************** FIELD VALUES OF A STIPENDGIVER RECORDSET
string      fullCourseName;
string   sloodleName;
string   sloodleIntro;
integer  defaultStipend;
integer  totalStipends;
integer numStudents;
key myKey;
integer mainIndex=1;
// *************************************************** CLASS LIST MENU INDEX (WHERE WE ARE IN THE LIST OF STUDENTS
integer currentMenuIndex=0;

// ******************************************************************************************************
// ******************************************************************************************************
// ******************************************************************************************************
// ******************************************************************************************************
//                                         BELOW IS FOR FUNCTIONS  
// ******************************************************************************************************
// ******************************************************************************************************
// ******************************************************************************************************
// ******************************************************************************************************
//this is for developers.  set to OFF when finished debugging
debugMessage(string message){
 if (debug==ON) llOwnerSay(" ~~~StipendGiver_mod script debug message ~~~ " + message);
}
integer random_integer( integer min, integer max )
{
  return min + (integer)( llFrand( max - min + 1 ) );
}
// *************************************************** SLOODLE TRANSLATION
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

// *************************************************** SLOODLE DEBUG
sloodle_debug(string msg){
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);    
}      


// Configure by receiving a linked message from another script in the object.
// Returns TRUE if the object has all the data it needs.
// Copy the basic structure for other objects, but add/remove specific configuration settings as necessary.

integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if (numbits > 1) value1 = llList2String(bits,1);
    if (numbits > 2) value2 = llList2String(bits,2);
    if (name == "set:sloodleserverroot") sloodleserverroot = value1;
    else if (name == "set:sloodlepwd") {
        
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
      
    } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
    else if (name == "set:sloodlemoduleid") {
        sloodlemoduleid = (integer)value1;

    }
    
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == "set:$sloodlestipend") sloodlestipend = value1;
    else if (name == "set:sloodleshowhovertext") sloodleshowhovertext = (integer)value1;
    // TODO: Add additional configuration parameters here
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    // This line figures out if we have all the core data we need.
    // TODO: If you absolutely need any other core data in the configuration, then add it to this condition.
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}

// Checks if the given agent is permitted to control this object.
// Returns TRUE if so, or FALSE if not.
// You can leave this out if you don't need to check for control authority.
integer sloodle_check_access_ctrl(key id)
{
    // Check the access mode
    if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to use this object.
// Returns TRUE if so, or FALSE if not.
// You can leave this out if you don't need to check for usage authority.
integer sloodle_check_access_use(key id)
{    // Check the access mode
    if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}


//****************************************************************************************************
//sendCommand wraps the stipend giver command and data into something the linker.php can read
integer sendCommand(string command, string data,key senderUuid){

 string body ="";
         body += "sloodlecontrollerid=" + (string)sloodlecontrollerid;
         body += "&sloodlepwd=" + (string)sloodlepwd;
        body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
        
        body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
     
        // Add our other data
        body += "&sloodleavname=" + llEscapeURL(llKey2Name(llGetOwnerKey(llGetKey())));
        body += "&sloodleuuid=" + (string)llGetOwnerKey(llGetKey());
       
        
        body += "&senderuuid=" + (string)senderUuid;
      
        body += "&command=" +command;
      
        body += "&data=" +data;
        // Now send the data
        
        debugMessage("httpscript: Freemem: " + (string)llGetFreeMemory());
        debugMessage("HttpScript:  sending this to linker.php\n"+sloodleserverroot + SLOODLE_IBANK_LINKER+ body);
        http = llHTTPRequest(sloodleserverroot + SLOODLE_IBANK_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        llSetTimerEvent(10.0);
        return 0;
}

///// STATES /////

// Default state - waiting for configuration.
// The first state of your main script should ALWAYS be something like this.
// However, you'll need to tweak it for each object.
default{
     state_entry()
        {
          myKey=llGetKey();  
        currentMemoryStick= BASE_MEMORY;
        debugMessage("Base memory channel is: " +(string)BASE_MEMORY);
            llSetText("Touch to Configure", LIGHTBLUE, 0.0);
               //set up money withdraw authorization
            llRequestPermissions(llGetOwner(), PERMISSION_DEBIT );  
           
     
        
        }
  run_time_permissions (integer perm)
    {
        if(perm & PERMISSION_DEBIT)
        {
            state go;     
        }
    }
    on_rez(integer start_param){
        //make nice startup sound
         llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "playsound:rez|", NULL_KEY);
        llResetScript();   
    }    
    
}
state go
{
    state_entry()
{
        //play nice start up sound        
        llSetText("Getting Configuration from Server", LIGHTBLUE, 1);
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "playsound:startup|", NULL_KEY);
        // Startig again with a new configuration
        
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
        sloodlemoduleid = 0;
        sloodleobjectaccessleveluse = 0;
        sloodleobjectaccesslevelctrl = 0;
        sloodleserveraccesslevel = 0;
        sloodlestipend = "";
        sloodleshowhovertext = 0;
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
        llSetText("Requesting Configuration", DARKBLUE, 0.0);
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "playsound:userclick", NULL_KEY);
         
        
        // TODO: Add other custom reset stuff here...
    }
   
   
    
    link_message( integer sender_num, integer num, string str, key id)
    {
        // Received a link message possibly containing configuration data.
        // Split it up and process it.
    
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; i < numlines; i++) {
                isconfigured = sloodle_handle_command(llList2String(lines, i));
            }
            
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");
                    
                    // TODO: customize the state change if you need to
                    llSetTimerEvent(3.0);
                    return;
                    
                } else {
                    // Go all configuration but, it's not complete... request reconfiguration
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);
                    eof = FALSE;
                }
            }
        }
    }
    timer() {
        //wait for memstick scripts to catch up
        llSetTimerEvent(0.0);
        state readUserListChunk;
    }
    
    
    
    on_rez(integer par)
    {
        llResetScript();
    }
}
//this state will send an http request to send back CHUNK_SIZE number of students 
//it will then parse the returned body into userLines list
//then loadChunk is called, and each line is sent via a Linked Message to waiting memory stick scripts
//these memory stick scripts will store the user data and give menu's to the admin based on which one is in an active state
state readUserListChunk
{
  
    
    state_entry()
    {
        //send an http request to get the CHUNK_SIZE number of users from linker.php
        sendCommand("getClassList",(string)(chunk*(CHUNK_SIZE+1)),llGetOwnerKey(llGetKey()));
        debugMessage("in readUserListChunk");             
      //  llSetText("Loading Class List", GREEN, 1.0); 
      llListen(ADMIN_CHANNEL,"", llGetOwnerKey(llGetKey()),"");
    }
   
    listen(integer channel, string name, key id, string message) {
            if (channel==ADMIN_CHANNEL){
                if (message=="Reset") llResetScript();
       }
    }
    http_response(key request_id, integer status, list metadata, string body) {
         
         list studentRowData = [];
        // Split the response into several lines
        debugMessage(body);
        list lines = llParseStringKeepNulls(body, ["\n"], []); 
         
        //The rest of the lines are data

        integer numLines = llGetListLength(lines)-1;
        debugMessage("readUserListChunk:num lines: " + (string)numLines + " " + body);        
       //retrieved messages from linker.php 
       //   LINE   MESSAGE
       //    0)     1 | OK
       //    1)     senderUuid
       //    2)     (COMMAND)  |
       //    3)     full course name |
       //    4)     sloodle name |
       //    5)     sloodle intro |
       //    6)     default Stipend |       
       //    7)     Total Stipends Allocated |
       //    8)     iCurrencyType   
       //    9)     update data    
       //    10+)    studentRowData                     
        
        integer status = llList2Integer(llParseString2List(llList2String(lines,0),["|"],[""]),0);  
        if (status == -321) {
             llSetTimerEvent(0.0);
            llMessageLinked(LINK_THIS,ALL_MEMORY, "RESET","");
            llSetText("Owner's avatar needs to be authorized with the Moodle Site!", <0.86456, 1.00618, 0.00000>, 1.0);
            llDialog(llGetOwnerKey(llGetKey()),"Please rez a RegEnrol booth first, and enrol your avatar to your moodle site",["Reset"],ADMIN_CHANNEL);        
           
        }
        else {
                //line 1      
                key origionalSenderUuid = llList2String(lines,1);
                //llSay(0,"origionalSenderUuid: " + (string)origionalSenderUuid);
                //line 2      
                string command = llList2String(lines,2);
                //llSay(0,"Command: " + (string)command);       
                //line 3
                numStudents = llList2Integer(lines,3);
                fullCourseName = llList2String(lines,4);
                //llSay(0,"coursefullname: " + (string)fullCourseName);
                //line 4
                sloodleName = llList2String(lines,5);
                //llSay(0,"sloodleName :" + (string)sloodleName);  
                //line 5
                sloodleIntro = llList2String(lines,6);
                //llSay(0,"sloodleIntro :" + (string)sloodleIntro); 
                //line 6
                defaultStipend = llList2Integer(lines,7);
                //llSay(0,"defaultStipend :" + (string)defaultStipend);
                //line 7
                totalStipends = llList2Integer(lines,8);
                //llSay(0,"totalStipends :" + (string)totalStipends);  
                //line 8+    
                //send default stats to all memory
                iCurrencyType = llList2String(lines,9);
                llMessageLinked(LINK_THIS, ALL_MEMORY, "STATS|"+iCurrencyType+"|"+(string)defaultStipend+"|"+(string)totalStipends+"|"+(string)numStudents+"|"+fullCourseName+"|"+sloodleName+"|"+sloodleIntro, "");
                userLines =[];        
                //get body and put each line returned into userLiens
                userLines= llList2List((lines = []) + lines, 10, llGetListLength(lines)-1);
                //get stats data
                
                
                //llMessageLinked(LINK_THIS, ALL_MEMORY,"CLEAR","");
                currIndex=0;
                //now read one line of the userLines        
                llSetTimerEvent(1.0);
        }
    
    }

    timer(){
            llSetTimerEvent(0.0);
            state readOneLine;
    }

      
    
}
//this state is used so we can jump to loadChunk again for each set of users downloaded
state readAgain{
    state_entry() {
        debugMessage("in readAgain");
        
        currIndex++;
        string dataLine = llList2String(userLines,currIndex);
    //    llSay(0," currIndex is: "+(string)currIndex+" "+"READ_SIZE is " +(string)READ_SIZE);
        if (currIndex > READ_SIZE) {
            debugMessage("Getting next chunk -dataLine"+dataLine);
            if (dataLine=="EOF") state ready; 
            chunk++;
            state readUserListChunk;
        }else {
            state readOneLine;
        }
        
    }
}
//this state sends one line at a time of the userLines list via linked 
//messages to the memory sticks waiting.

state readOneLine{

    state_entry() {    
        debugMessage("in readOneLine");
        //get one line of data from userLines
        string dataLine = llList2String(userLines,currIndex);
        llSetText("Loading - %" + llGetSubString((string)(((float)mainIndex++ * 100.00)/(float)numStudents),0,2), <0.99598, 0.00000, 0.89627>,1);//light pink
        //check to see if this is the last line
            
        if (dataLine=="EOF") {
            debugMessage("------------------------------Read the end!!!!!!!!!        !!!!");    
            state ready;
            
        }
        else {
        debugMessage((string)currIndex+"-------------------------------readOneLine read: "+dataLine);    
        //send dataline to the current memory channel
            llMessageLinked(LINK_THIS,currentMemoryStick, dataLine,llGetKey());
        }
      
        
    }
       state_exit() {
        llSetTimerEvent(0.0);
    }   
    link_message(integer sender_num, integer script_channel, string str, key id) {
         if (script_channel==STIPEND_GIVER_CHANNEL){
             debugMessage("readOneLine Got linked message: " + str);
             list message = llParseString2List(str, ["|"],[""]);
             string command = llList2String(message,0); 
             //Message was sent from memory sticks                                     
             if (command =="MEMORY FULL"){
                 //if memory stick is full, fill to next memory stick
                 currentMemoryStick++;
                 debugMessage("readOneLine: Memory full, changing to memory stick#:"+ (string)currentMemoryStick);
                 state readAgain; 
             }else if (command=="READ OK"){
                 //now read next line
                 state readAgain;
             }else if (command=="RELOAD"){
                 //now read next line
                 llMessageLinked(LINK_THIS,ALL_MEMORY, "RESET", "");
                 debugMessage("Reseting Memory Sticks");
                 llSetTimerEvent(2.0);
             }
             
         }
    }
    timer() {
        //this timer gets called when RELOAD message is received
        llSetTimerEvent(0.0);
        state readUserListChunk;
    }
    
    
}

state ready
{ 
    state_entry()
    {
    
       debugMessage("&&&&&&&&&&&$$$$$$$$$$$$$$$$Loading Class List finished!");
       llMessageLinked(LINK_THIS,ALL_MEMORY, "LOADING DONE","");
       llListen(55, "",llGetOwnerKey(llGetKey()),"");
    }    
    link_message(integer sender_num, integer num, string str, key id) {
       debugMessage("*****stipendgiver main script  got linked message : "+str);
        list strData = llParseString2List(str,["|"],[""]);
        string command=llList2String(strData, 0);
        if (command=="RELOAD"){
            currentMenuIndex=0;
            currentMemoryStick=BASE_MEMORY;
            chunk=0;
            llMessageLinked(LINK_THIS,ALL_MEMORY, "RESET","");
            llSetTimerEvent(2.0);
            
        }else if ((command=="GIVE MONEY")&& (id==myKey)){
            //                              (1=senderUuid)                    (2=amount)
            
            
            avUuid=llList2Key(strData,1);
            amount=llList2Integer(strData,2);
            llGiveMoney(avUuid, amount);
        }
    }
    
    
    timer() {
        llSetTimerEvent(0.0);
        llResetScript();
    }
}