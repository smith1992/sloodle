// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
// mem_stick.lsl 
// 
// Each mem_stick.lsl can hold up to 12 students downloaded rows of student data
// By default 17 copies of this script is included with the iBank - this enables a class size
// of 204 avatars.  If you require more, copy mem_stick.lsl into your inventory, and then drag it back
// into the iBank to add another copy.  then click Reload class in the iBank menu, or reset the iBank
//
// Please view our wiki for more information, and also our discussion forums at http://www.sloodle.org
//
// Contributors:
//  Paul Preibisch


string     moneyMessage;
string     hoverText;
string     linkCommand; //used for commands sent back from the server
string     tempStringA;
string     avname;
string     parseData;
string     menuText;
string     modifyAmount;
string     avName;
string     avFullName;
string       status;
string     iCurrencyType;
list       linkMessageList;
list       menuButs1;
list       parseList;
list       menuButs;
list       tempList;
list       tempList1;
list       statusData;
list       transactionData=[];
integer avNameListLen;
integer channelListLen;
integer userIndex;
integer result;
integer change;
integer newAmount;
integer oldAmount;
integer tempIntB;
integer tempIntA;
integer searchIndex;
integer counter1;
integer numLines;
integer withdrawAmount;       
integer DEBUG=0;
key     myKey;
key     senderUuid;
// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;
integer MIN_MEMORY_BOUNDRY=2000;
// This string identifies the location of the linker script relative to this Moodle root
integer newChannel;
integer numMenuButtons=0;
integer prevMenuButtons=0;
integer masterMenuIndex=0;//this is a index which points to the current student in the menu, this will be sent to all menus
integer wakeUpMenu=FALSE; //this is just a boolean to display the student menu if another mem stick wakes this script up when the admin is browsing students
integer eof= FALSE;
string  SLOODLE_STIPENDGIVER_LINKER = "/mod/sloodle/mod/ibank-1.0/linker.php";
integer isconfigured= FALSE;
integer myNum;
key     http = NULL_KEY;
key     avuuid;

// These are common configuration settings

string  sloodleserverroot = "";
string  sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
integer sloodleautodeactivate = 1; // Should the WebIntercom auto-deactivate when not in use?
// *************************************************** AUTHENTICATION CONSTANTS
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string  SLOODLE_EOF = "sloodleeof";
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
string  SLOODLE_OBJECT_TYPE = "stipendgiver-1.0";
// *************************************************** MENU BUTTONS
list    ADMIN_MENU;
list    STUDENT_MENU;
list    CONFIG_MENU;
// *************************************************** MENU TEXT
string  ADMIN_MENU_TEXT;
string  STUDENT_MENU_TEXT;
integer currentMenuIndex=0;
// *************************************************** MENU CHANNELS
integer USER_CHANNELS;
integer MENU_CHANNEL;
integer ADMIN_CHANNEL;
integer CONFIG_CHANNEL;
// *************************************************** FIELD VALUES OF A STIPENDGIVER RECORDSET
string  fullCourseName;
string  sloodleName;
string  sloodleIntro;
integer defaultStipend;
integer totalStipends;
integer modifiedDefaultStipend=0;

integer numStudents; //total avatars in class
integer memStickNumStudents=0; //total number of avatars on this memory stick
list    userList;
integer SIZE=10;
string  myName;

key     ownerKey;
list    moodleIdList             = [];
list    moodleNameList         = [];
list    avatarNameList         = [];
list    channelList            = [];
list    avatarAllocationList   = [];
list    userDebitsList         = [];
list    modifiedStipendAmounts = [];
list    updateData             = [];
integer MAX_BUTTONS            = 6; //dont change this or menus wont be good
list     studentRowData            = [];
list     lines                    = [];

integer MY_SCRIPT_CHANNEL;
integer BASE_MEMORY                = 7999;
integer ALL_MEMORY               = 9000;
integer STIPEND_GIVER_CHANNEL  = -7888;

debugMessage(string message){
 //llSay(0,"~----- " + (string)myNum+" --- FreeMem: "+(string)llGetFreeMemory()+"-~ "+message);
}
//sets the text after config has been received or new data arrives
setHoverText(){
    hoverText = "\n" + fullCourseName + "\n" + sloodleName;
    if (iCurrencyType=="Lindens") {
        hoverText += "\n" + "Total Stipends:" + (string)totalStipends + " " + iCurrencyType;
        hoverText += "\n Default Stipend: " + (string)defaultStipend + " " + iCurrencyType;
    }
    else if (iCurrencyType=="iPoints") {
        hoverText += "\n" + "Total iPoints Awarded:" + (string)totalStipends + " " + iCurrencyType;
   }
    hoverText += "\n Total Students: " + (string)numStudents;
    debugMessage("jjjjjjjjjjjj setting hover text: " + hoverText);
    llSetText(hoverText,<0.00000, 0.77722, 0.01556>,1);//light green 
    //for a great color picker, use: EM Tech - Color Picker (2 script) 1.0 on slexchange -very cool!
}


//****************************************************************************************************
//Handles translations
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}
displayHelpNotecard(key userClickedUuid, string help_file){
    llGiveInventory(userClickedUuid, help_file);
}
//****************************************************************************************************
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
//handles sloodle configuration
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
    // TODO: Add additional configuration parameters here
    else if (name == SLOODLE_EOF) eof = TRUE;
    // This line figures out if we have all the core data we need.
    // TODO: If you absolutely need any other core data in the configuration, then add it to this condition.
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}
//****************************************************************************************************
//stipendHandleResponse handles all http response data 
stipendHandleResponse(string body){   
    debugMessage(body);   
    lines = (lines=[])+llParseStringKeepNulls(body,["\n"],[]);
    tempIntA = llList2Integer(lines,0); 
    numLines = (llGetListLength(lines) - 1);
    senderUuid = llList2Key(lines,1);
    string command = llList2String(lines,2);
    if (command == "updateStipendResponse") {
        //format expeCted: moodleId|avName|avUuid|modifyAmount
        updateData = (updateData=[])+llParseString2List(llList2String(lines,10),["|"],[""]);        
        llMessageLinked(LINK_SET,MY_SCRIPT_CHANNEL,"UPDATE CONFIRMED|"+llList2String(updateData,1)+"|"+llList2String(updateData,2),"");
         llMessageLinked(LINK_THIS, ALL_MEMORY, "STATS|"+llList2String(lines,9)+"|"+llList2String(lines,7)+"|"+llList2String(lines,8)+"|"+llList2String(lines,3)+"|"+llList2String(lines,4)+"|"+llList2String(lines,5)+"|"+llList2String(lines,6), "");
        totalStipends = llList2Integer(lines,8);        
    }
    else  if (command == "setDefaultStipendResponse"){
        //The datalines returned are:    
        //status messages                = line 0
        //senderUuid that sent request    = line 1
        //setDefaultStipendResponse        = line 2
        //numStudents                     = line 3
        //fullCourseName                = line 4
        //sloodleName                    = line 5
        //sloodleIntro                    = line 6
        //defaultStipend                = line 7
        //totalStipends                    = line 8
        //iCurrencyType                    = line 9
        llMessageLinked(LINK_THIS, ALL_MEMORY, "STATS|"+llList2String(lines,9)+"|"+llList2String(lines,7)+"|"+llList2String(lines,8)+"|"+llList2String(lines,3)+"|"+llList2String(lines,4)+"|"+llList2String(lines,5)+"|"+llList2String(lines,6), "");
        llOwnerSay("Default stipend changed to: "+llList2String(lines,7)+" "+ llList2String(lines,9));

        displayConfigureMenu(ownerKey);

    }else 
       if (command == "withdrawStipendResponse"){
        parseList = llParseString2List(llList2String(lines,10),["|"],[""]);       
        avName = llList2String(parseList,1);        
        status = llList2String(llParseString2List( llList2String(lines,0),["|"],[""]),1);
        if (status=="OK"){
            if (iCurrencyType =="Lindens"){
                //moodleId                        = parseList 0
                //senderAvatarName                = parseList 1
                //senderUuid                    = parseList 2
                //withdrawAmount                 = parseList 3
                //iCurrencyType                    = parseList 4
                llMessageLinked(LINK_THIS,ALL_MEMORY,"WITHDRAW ATTEMPT|"+llList2String(parseList,0)+"|"+avName+"|"+llList2String(parseList,2)+"|"+llList2String(parseList,3),"");
            }
        }else if (status=="ZERO BALANCE"){ //student has zero balance
                //senderUuid                    = parseList 2         
            llDialog(llList2Key(parseList,2),"Sorry, you have zero balance in the Bank!", ["Ok"],MENU_CHANNEL);
        }
        //alert for error status's returned
    }else if (tempIntA==421) //not enrolled message
                llDialog(llList2Key(parseList,2),"Sorry, you are NOT enrolled in this course, and auto enrol has been disabled", ["Ok"],MENU_CHANNEL);
else if (tempIntA==513) //class not available to students - moodle setting 
                llDialog(llList2Key(parseList,2),"Sorry, this course is not available to students", ["Ok"],MENU_CHANNEL);                
    setHoverText();
    lines = [];
}

// ****************************************************************************************************
// This function modifyStipendAmount will modify the value at the userIndex specified
modifyDefaultStipendAmount(integer amount,key menuUserUuid){
    change = amount + modifiedDefaultStipend;     
    if ((change + defaultStipend) < 0) change = 0;
    modifiedDefaultStipend = change;
    debugMessage("modifiedAmount is: " + (string)modifiedDefaultStipend);
    setDefaultStipendDialog(menuUserUuid);
}

// ****************************************************************************************************
//This function getStats returns a string that lists how much of a stipend has been allocated to the user
//as well as debits made
string getStats(integer userIndex){
        string  userName = llList2String(avatarNameList,userIndex);
        integer channel = llList2Integer(channelList,userIndex);
        //set menu text
        if (iCurrencyType=="Lindens"){
             menuText = "-~~~ Modify Stipend ~~~-\n"+userName + "\nStipend allocated: "+llList2String(avatarAllocationList,userIndex)+ " " + iCurrencyType;
             menuText += "\nand has withdrawn: " + llList2String(userDebitsList,userIndex) + " " + iCurrencyType;
        }else
        if (iCurrencyType=="iPoints"){
             menuText = "-~~~ Modify iPoints awarded ~~~-\n"+userName + "\niPoints awarded: "+llList2String(avatarAllocationList,userIndex)+ " " + iCurrencyType;
        }           
        //see if this record is being modified
        integer newValue = llList2Integer(modifiedStipendAmounts,userIndex) + llList2Integer(avatarAllocationList,userIndex);
        integer savedValue =llList2Integer(avatarAllocationList,userIndex);
        if (savedValue != newValue)
            menuText += "\nModify to: " + (string)newValue + " " + iCurrencyType + "\n\nPress (~~ SAVE ~~) when done";   
        return menuText;
}

//****************************************************************************************************
//sendCommand wraps the stipend giver command and data into something the linker.php can read
integer sendCommand(string command, string data,key senderUuid){
        llSetText("Please wait..",<0.70877, 0.00000, 0.69987>,1);//light pink    
         string body ="";
        body += "sloodlecontrollerid=" + (string)sloodlecontrollerid;
        body += "&sloodlepwd=" + (string)sloodlepwd;
        body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
        body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
        // Add our other data
        body += "&sloodleavname=" + llEscapeURL(llKey2Name(senderUuid));
        body += "&sloodleuuid=" + (string)senderUuid;
        body += "&senderuuid=" + (string)senderUuid;
        body += "&command=" +command;
        body += "&data=" +data;
        // Now send the data
        debugMessage("mem stick: Freemem: " + (string)llGetFreeMemory());
        debugMessage("HttpScript:  sending this to linker.php\n"+sloodleserverroot + SLOODLE_STIPENDGIVER_LINKER+"?"+body);
        http = llHTTPRequest(sloodleserverroot + SLOODLE_STIPENDGIVER_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        llSetTimerEvent(10.0);
        return 0;
}
// ****************************************************************************************************
// This function saveDefaultAmountModification will send the new default stipend amount to linker.php
saveDefaultAmountModification(key senderUuid){
    change = modifiedDefaultStipend;
    newAmount = defaultStipend + change;
    defaultStipend = newAmount;
    debugMessage("******* newAmount: "+ (string)newAmount); 
    //data:  userid,new amount
    //send command will send an http request to the server. Response will be upDateStipend    
    sendCommand("setDefaultStipend", (string)defaultStipend,senderUuid);   
}
// ****************************************************************************************************
//This function displayUserModMenu displays a set of number increments that the admin can change a stipend
//                    
displayUserModMenu(integer channel,key avuuid){
    llDialog(avuuid, getStats(llListFindList(channelList,[channel])), ["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000","(~~ SAVE ~~)"], channel);
}
// ****************************************************************************************************
//This function DISPLAYS a special menu for the admin with functions that can be applied to the particular student
//Modify (Stipend) Back               
displayUserMenu(string user,key avuuid){
        //search through our lists, and find the index of this user so we can access other info we stored in lsl about thise user       
        tempIntA = findUser(user);
        llDialog(avuuid, getStats(tempIntA), ["Modify","(-~ MENU ~-)"], llList2Integer(channelList,tempIntA));
}
//just returns a random integer - used for setting channels
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}
//****************************************************************************************************
//This function returns a list of 6 menu buttons of a list of students, based on an index
//Output: 
//        list of buttons
//        index button

list getMenu(integer cIndex){
    tempIntA = cIndex;
    menuButs1=[];
    tempList=[];
    tempList1=[];
    tempIntB=0;
    numMenuButtons=0;
    //add buttons from student list to menu
    while ((tempIntA< cIndex+MAX_BUTTONS) && (tempIntA < memStickNumStudents)){
        tempIntB++;//used to split lists into two
        numMenuButtons++;
        if (tempIntB<=3)
           menuButs1 += llGetSubString(llList2String(avatarNameList,tempIntA), 0, 11);
        else tempList +=llGetSubString(llList2String(avatarNameList,tempIntA), 0, 11);
        tempIntA++; //used to count current pointer
    }
    
    //menuButs1 = llListSort(menuButs1, 0, FALSE);//put into decending order 
    
   //show previous button only if we are not on the first page, or if we are in a memory stick other than the first memory stick
    if (((0 + MAX_BUTTONS) <= cIndex)|| (myNum > 0))
        tempList1 += "<<";
    if (((masterMenuIndex + MAX_BUTTONS) < numStudents))       
            tempList1 += ">>";
    tempList1 += "(-~ MENU ~-)";
    return tempList + menuButs1 + tempList1;

}
//****************************************************************************************************
//This function displayClassListMenu displays a dialog with students as the buttons
//
displayClassListMenu(key avuuid){
        menuText = "-~~~ View Class ~~~-";
        menuText+="\nPage " + (string)(masterMenuIndex/MAX_BUTTONS+1) +" of "+ (string)((integer)((float)numStudents/(float)MAX_BUTTONS)+1);
        menuText+="\nTotal Students: "+(string)numStudents;
        //classListMenuText += "\nYou have " + (string)llGetListLength(avatarNameList) + " students in your class\nSelect a Student";    
        menuText += "\nSelect a Student";
        llDialog(avuuid, menuText, getMenu(currentMenuIndex), ADMIN_CHANNEL);
}

//****************************************************************************************************
//This function displayConfigureMenu displays a dialog with Default $$$ , Reload Class, Reset, Help
//
displayConfigureMenu(key avuuid){
        menuText = "-~~~ Configure iBank ~~~-";
        if (iCurrencyType == "Lindens") {
                menuText += "\nSet Default - sets default stipend";
                menuButs = ["Set Default","Reload Class","Reset","(-~ MENU ~-)"];
        }else if (iCurrencyType == "iPoints") {
                
                menuButs=["Reload Class","Reset","(-~ MENU ~-)"];
        } 
        menuText += "\nReload Class - Downloads Student names from Moodle";
        menuText += "\nReset - Resets the iBank";    
        
        llDialog(avuuid, menuText, menuButs, CONFIG_CHANNEL);
}
//****************************************************************************************************
//listenUserChannels sets up listen callbacks for students
listenUserChannels(){
    tempIntB =0;
    tempIntA =llGetListLength(channelList);
    for (tempIntB; tempIntB<= tempIntA; tempIntB++){
        llListen(USER_CHANNELS+tempIntB, "", "", "");
    }
}
// ****************************************************************************************************
// This function findUser looks in the userList for the user specified by userName
// Since buttons concat names to 12 characters, we must first create a temporary list of avatarNames
// to ensure search finds a match
// Output-  Returns index of av

integer findUser(string userName){
   
    tempIntB =0;
    tempList=[];
    avNameListLen=llGetListLength(avatarNameList);
    for (tempIntB; tempIntB< avNameListLen; tempIntB++){
            avFullName = llList2String(avatarNameList,tempIntB);
            if (llStringLength(avFullName)>12)
            avFullName = llGetSubString(avFullName, 0, 11);
            tempList+=avFullName;
    }
    userName = llGetSubString(userName, 0, 11);    
    result= llListFindList(tempList,[userName]);
    tempList=[];
    return result;

}

// ****************************************************************************************************
// This function saveAmountModification will save the modifiedStipendAmount of the user
// to our moodle db.  it will then clear the modifiedStipendAmount to zero
// and re-download all userLists using buildUserLists
saveAmountModification(integer channel,key senderUuid){
    searchIndex = llListFindList(channelList,[channel]);
    newAmount = llList2Integer(avatarAllocationList,searchIndex)+ llList2Integer(modifiedStipendAmounts,searchIndex);
    //data:  userid,new amount
    //send command will send an http request to the server. Response will be upDateStipend
    tempStringA = llList2String(moodleIdList,searchIndex) +  "|" + llList2String(avatarNameList,searchIndex) + "|"  + (string)newAmount;
    modifiedStipendAmounts = llListReplaceList( (modifiedStipendAmounts = []) + modifiedStipendAmounts, [0], searchIndex, searchIndex);
    sendCommand("updateStipend", tempStringA,senderUuid);
}
//displayMainMenuDialog shows the main menu
displayMainMenuDialog(key userClickedKey){
    ADMIN_MENU_TEXT= "-~~~ MAIN MENU ~~~-\n";
    ADMIN_MENU_TEXT+= sloodleName;
    
    string amountAllocated;
  if (userClickedKey ==ownerKey){
           if (iCurrencyType=="Lindens") {
               menuButs=["Withdraw","Set Stipends", "Configure"];
               ADMIN_MENU_TEXT+= "\nTotal Stipends: " + (string)totalStipends + " " + iCurrencyType;
            ADMIN_MENU_TEXT+= "\nDefault Stipend: " + (string)defaultStipend + " " + iCurrencyType;   
            }else if (iCurrencyType=="iPoints") {
               menuButs=["Set iPoints", "Configure"];
               ADMIN_MENU_TEXT+= "\nTotal iPoints: " + (string)totalStipends + " " + iCurrencyType;
            }
            ADMIN_MENU_TEXT+= "\nTotal Students: " +  (string)numStudents;
           
            llDialog(userClickedKey,ADMIN_MENU_TEXT,menuButs,MENU_CHANNEL);}
        else //user menu
            if (sloodle_check_access_use(userClickedKey)){
                if (iCurrencyType=="Lindens"){
                    menuButs = ["Withdraw"];
                    STUDENT_MENU_TEXT= llKey2Name(userClickedKey) + " you have been allocated: " + llList2String(avatarAllocationList,findUser(userClickedKey)) + " " + iCurrencyType;
                       
                }else {
                    STUDENT_MENU_TEXT="-~~~ iPoint Balance ~~~-";
                    STUDENT_MENU_TEXT="\nClick below to view your Points!";
                    menuButs = ["Points"];                
                }
   
       llDialog(userClickedKey,STUDENT_MENU_TEXT,menuButs,MENU_CHANNEL);
                
            }
}
// ****************************************************************************************************
// This function modifyStipendAmount will modify the value at the userIndex specified
modifyStipendAmount(integer amount,integer channel,key menuUserUuid){
    userIndex = llListFindList(channelList,[channel]);
    oldAmount = llList2Integer(avatarAllocationList,userIndex);
    change = amount + llList2Integer(modifiedStipendAmounts,userIndex);
    if ((change + oldAmount) < 0) change = 0;
    //llListReplaceList( list dest, list src, integer start, integer end );
    //Returns a list that is dest with start through end removed and src inserted at start.
    //note - I did : (modifiedStipendAmounts = []) + modifiedStipendAmounts to conserve memory - see:
    //http://wiki.secondlife.com/wiki/LlListReplaceList
    modifiedStipendAmounts = (modifiedStipendAmounts = []) +llListReplaceList( (modifiedStipendAmounts = []) + modifiedStipendAmounts, [change], userIndex, userIndex);
    debugMessage("Old amount is: " + (string)oldAmount);    
    debugMessage("Change amount is: " + (string)change);
    debugMessage("modifiedAmount is: " + llList2String(modifiedStipendAmounts,userIndex));
    displayUserModMenu(channel,menuUserUuid);
}
// ****************************************************************************************************
// displays the set default stipend dialog
setDefaultStipendDialog(key avuuid){  
    string sMenuText = "-~~~ Set Default Stipend ~~~-\n";
    sMenuText += "Use this menu to set a default stipend for all exisiting students";
    sMenuText += "\nDefault Stipend is: "+(string)defaultStipend + " " + iCurrencyType;
    sMenuText += "\nChange to: "+(string)(defaultStipend + modifiedDefaultStipend) + " " + iCurrencyType;
    sMenuText += "\n\nPress (~~ SAVE ~~) when done";
    list sMenu = ["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000","(~~ SAVE ~~)"];
    llDialog(avuuid, sMenuText, sMenu, CONFIG_CHANNEL);
}
// ****************************************************************************************************
default{  
    state_entry() {
        myKey=llGetKey();
        ownerKey = llGetOwnerKey(llGetKey());       
        myName = llGetScriptName();
        myNum = (integer)llDeleteSubString(myName, 0, 13);
        debugMessage((string)myNum+ " waiting for initialization"+(string)llGetFreeMemory());
        //the below code ensures that the menu channels of each each memory stick live in separate memory space
        //not entirely necessary since only one memory stick is awake at any one given time               
        MENU_CHANNEL = random_integer((1+5*myNum)*1000,(1+5*myNum)*1000+999);
        ADMIN_CHANNEL = random_integer((2+5*myNum)*1000,(2+5*myNum)*1000+999);
        CONFIG_CHANNEL = random_integer((2+5*myNum)*1000,(2+5*myNum)*1000+999);
        USER_CHANNELS  = random_integer((2+5*myNum)*1000,(2+5*myNum)*1000+999);
        //each memory stick must have its own script channel, so when the main script
        //is reading the student list it can send the batches to the intended memory stick 
        MY_SCRIPT_CHANNEL = BASE_MEMORY+myNum;
    }
    link_message(integer sender_num, integer script_channel, string str, key id) {       
       linkMessageList = llParseString2List(str,["|"], [""]); 
       linkCommand = llList2String(linkMessageList,0);
        if (script_channel == MY_SCRIPT_CHANNEL){     
                    //in the default state, this script channel waits for incoming student data sent
                    //by the main script while it is loading the student lists
                    moodleIdList += llList2Integer(linkMessageList,0);// store the moodleId  
                  //  llSay(0,(string)myNum+" moodleid=" + llList2String(linkMessageList,0)); 
                    //moodleNameList += llList2String(data,1); dont need this in the beta version, comment out to conserve memory
                    avatarNameList += llList2String(linkMessageList,2); //store avatar name
                    avatarAllocationList += llList2Integer(linkMessageList,3);//store allocated stipend
                    userDebitsList += llList2String(linkMessageList,4);   //store total debits by this user        
                    channelListLen = llGetListLength(channelList);
                    newChannel = USER_CHANNELS+ channelListLen+1;//store a unique channel id for this user
                    channelList += newChannel;
                    llListen(channelListLen+2, "", "", "");
                    modifiedStipendAmounts += 0;
                    debugMessage((string)memStickNumStudents++ +"-----------------> mem stick #"+(string)myNum+" READ: "+llList2String(linkMessageList,2) + " FreeMem is: " + (string)llGetFreeMemory());
              //now test to see if we have run out of memory for this userlist. 
              //if we have 12 items, move rest of users to next memory stick
              //I tested with more than 12 items, but in doing so, we get stack-heap overflows, so keep at 11, or even 6 if more programming is added
            if ((llGetFreeMemory() < MIN_MEMORY_BOUNDRY)||(memStickNumStudents>11))
                 llMessageLinked(LINK_THIS,STIPEND_GIVER_CHANNEL,"MEMORY FULL", "");
            else llMessageLinked(LINK_THIS,STIPEND_GIVER_CHANNEL,"READ OK", "");
         }else
        if (script_channel==ALL_MEMORY){
            //main script sends loading done message after every student has been downloaded 
            if (linkCommand =="LOADING DONE"){                             
                if (MY_SCRIPT_CHANNEL==BASE_MEMORY) state active;
                else state waiting;
            }else if (linkCommand =="STATS"){
                //llMessageLinked(LINK_THIS, ALL_MEMORY, "STATS|"+iCurrencyType+"|"+(string)defaultStipend+"|"+(string)totalStipends+"|"+(string)numStudents+"|"+fullCourseName+"|"+sloodleName+"|"+sloodleIntro, "");
                iCurrencyType= llList2String(linkMessageList,1);
                defaultStipend=llList2Integer(linkMessageList,2);
                totalStipends=llList2Integer(linkMessageList,3);
                numStudents=llList2Integer(linkMessageList,4);
                fullCourseName=llList2String(linkMessageList,5);
                sloodleName=llList2String(linkMessageList,6);
                sloodleIntro=llList2String(linkMessageList,7);
            }
        }else
        if (script_channel == SLOODLE_CHANNEL_OBJECT_DIALOG) {
           
            // Split the message into lines
            lines=[];
            lines = llParseString2List(str, ["\n"], []);
            numLines = llGetListLength(lines);
            counter1 = 0;
            for (counter1; counter1< numLines; counter1++) {
                isconfigured = sloodle_handle_command(llList2String(lines,counter1));
            }
           
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    //sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");
                    debugMessage("config received");
                    // TODO: customize the state change if you need to             
                    return;
                   
                } else {
                    // Go all configuration but, it's not complete... request reconfiguration
                    //sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    debugMessage("config not received");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);
                    eof = FALSE;
                }
           
        }
    }
       
}
}
// ****************************************************************************************************
//This Memory Stick goes into waiting when it is not the current active menu.
//It will change to the active state if the active script wakes it up
//this can happen when the user presses << or >> in the active scripts menu.  If the active script is 
//at its memory boundry, it sends a linked message to the next script to display the previous or next menu  
state waiting{

    state_entry()
    {

    }
    link_message(integer sender_num, integer num, string str, key id) {
        debugMessage(str);
       linkMessageList = llParseString2List(str, ["|"],[]);
       linkCommand = llList2String(linkMessageList,0);
        if (num == MY_SCRIPT_CHANNEL){
            if (linkCommand=="WAKE UP") {
                masterMenuIndex=llList2Integer(linkMessageList,1); //get the current menu index - this is used so we know if to show a Next button
                wakeUpMenu=TRUE; //If woken up, display the displayClassListMenu menu
                state active;
            }
        }
        else if (num== ALL_MEMORY){
           if (linkCommand=="RESET") llResetScript();
            else if (linkCommand =="STATS"){               
                iCurrencyType= llList2String(linkMessageList,1);
                defaultStipend=llList2Integer(linkMessageList,2);
                totalStipends=llList2Integer(linkMessageList,3);
                numStudents=llList2Integer(linkMessageList,4);
                fullCourseName=llList2String(linkMessageList,5);
                sloodleName=llList2String(linkMessageList,6);
                sloodleIntro=llList2String(linkMessageList,7);
            } else   
                if (linkCommand=="WITHDRAW ATTEMPT"){
                     // A Withdraw attempt happens after a user has pressed withdraw on the menu, and the script
                     // received an http response with a status=OK message meaning - the user is authorized to withdraw the cash
                     // search this memory sticks moodleIdList to see if this user is stored here.    
                     result = llListFindList(moodleIdList, [llList2Integer(linkMessageList,1)]);
                     if (result!=-1) {
                        //linkMessage 3 = senderUuid
                        //linkMessage 4 = amount
                        if (iCurrencyType=="Lindens"){
                            moneyMessage= "GIVE MONEY|"+llList2String(linkMessageList,3)+"|"+llList2String(linkMessageList,4);    
                            llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, moneyMessage, llGetKey());
                        }
                       // searchIndex = llListFindList(moodleIdList,[llList2Integer(linkMessageList,0)]);
                        //update amount
                        userDebitsList = (userDebitsList=[])+llListReplaceList( (userDebitsList=[])+userDebitsList, [llList2Integer(linkMessageList,3)], result, result);
                        debugMessage("withdraw attempt: " + str + " searching for moodle id: "+llList2String(linkMessageList,1)+" result is: "+ (string)result);     
                    }
                   
            }else if (linkCommand=="GET POINTS"){
                //message is in format: GET POINTS|avuuid|avname
                    userIndex = llListFindList(avatarNameList, [llList2String(linkMessageList,2)]);
                    if (userIndex!=-1) {
                            llMessageLinked(LINK_SET,ALL_MEMORY,"FOUND USER|"+llList2String(linkMessageList,1)+"|"+llList2String(linkMessageList,2)+"|"+llList2String(avatarAllocationList,userIndex),llGetKey());
                            llSay(0,"Found user");
                    }
                    else debugMessage("User "+llList2String(linkMessageList,2)+" Not found");
            }
        }
    }
    
}
// ****************************************************************************************************
state active{

    state_entry()
    {
         setHoverText();
            debugMessage((string)myNum + " is active");
            debugMessage("********* i am active!!!!! My USER_CHANNELS are: " + (string)USER_CHANNELS);
            CONFIG_MENU= ["Set Default", "Reload Class", "Reset"];
            llListen(MENU_CHANNEL, "", "", "");
            llListen(ADMIN_CHANNEL, "", "", "");
            llListen(CONFIG_CHANNEL, "", "", "");
            listenUserChannels();
            if (wakeUpMenu==TRUE) {
                displayClassListMenu(ownerKey);
                wakeUpMenu==FALSE;
            }

        }
        link_message(integer sender_num, integer num, string str, key id) {
            debugMessage(str);
            if (llGetFreeMemory()<500) {
                llSay(0,"Memory too low in Memory Stick#:"+(string)myNum+".  Rebooting Stipendgiver... Please wait...");    
                llMessageLinked(LINK_SET,STIPEND_GIVER_CHANNEL,"RELOAD|","");
            }
            linkMessageList = llParseString2List(str, ["|"],[""]);
                linkCommand = llList2String(linkMessageList,0);
            if (num == MY_SCRIPT_CHANNEL){
                
                if (linkCommand=="SLEEP") state waiting;
                else if (linkCommand =="UPDATE CONFIRMED"){
                                  
                       string avName = llList2String(linkMessageList,1);
                       string modifyAmount = llList2String(linkMessageList,2);
                         //llSay(0,"--------Updating " + avName + "'s allocation");      
                       integer userIndex = findUser(avName);
                       avatarAllocationList = (avatarAllocationList = []) +llListReplaceList( (avatarAllocationList = []) + avatarAllocationList, [modifyAmount], userIndex, userIndex);
                       displayClassListMenu(ownerKey);
                }
            }else 
            if (num==ALL_MEMORY)
                if (linkCommand=="RESET") llResetScript();
                else
                       if (linkCommand=="WITHDRAW ATTEMPT"){
                     // A Withdraw attempt happens after a user has pressed withdraw on the menu, and the script
                     // received an http response with a status=OK message meaning - the user is authorized to withdraw the cash
                     // search this memory sticks moodleIdList to see if this user is stored here.    
                        result = llListFindList(moodleIdList, [llList2Integer(linkMessageList,1)]);
                        if (result!=-1) {
                            //linkMessage 3 = senderUuid
                            //linkMessage 4 = amount
                            moneyMessage= "GIVE MONEY|"+llList2String(linkMessageList,3)+"|"+llList2String(linkMessageList,4);    
                            llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, moneyMessage, llGetKey());
                           // searchIndex = llListFindList(moodleIdList,[llList2Integer(linkMessageList,0)]);
                            //update amount
                            userDebitsList = (userDebitsList=[])+llListReplaceList( (userDebitsList=[])+userDebitsList, [llList2Integer(linkMessageList,3)], result, result);
                        
                        }
                    debugMessage("withdraw attempt: " + str + " searching for moodle id: "+llList2String(linkMessageList,1)+" result is: "+ (string)result);
            
            }else if  (linkCommand=="FOUND USER") 
            //message is in format  FOUND USER|avuuid|avname|points
                llDialog(llList2Key(linkMessageList,1),llList2String(linkMessageList,2) + ", you have "+llList2String(linkMessageList,3) + " points!",[],MENU_CHANNEL);
        }
        
    

        touch_start( integer total_number)
        {


            //CONSTRUCT ADMIN MENU
            //--------------------
            //MENU TEXT:  Stipend Giver Admin Menu
            //
            //menu BUTTONS:  HELP, VIEW STUDENTS
            avuuid = llDetectedKey(0);
            avname = llDetectedName(0);
            //decided which dialog to display - admin or user
            displayMainMenuDialog(avuuid);
        }
        listen( integer channel, string name, key avuuid, string command )
        {
            if (channel==CONFIG_CHANNEL){

                    if (command=="Set Default"){
                        setDefaultStipendDialog(avuuid);
                    }else if (command=="Reload Class"){
                           llMessageLinked(LINK_SET,STIPEND_GIVER_CHANNEL,"RELOAD|","");
                    }else if (command=="Reset"){
                            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reset", "");
                    }else if (command=="(~~ SAVE ~~)"){
                            //now apply saved modification
                            saveDefaultAmountModification(avuuid);
                        modifiedDefaultStipend = 0;

                    }else if (command=="(-~ MENU ~-)"){
                            //now apply saved modification
                            displayMainMenuDialog(avuuid);


                    }
                else {//***********  message is a number so change modification
                    debugMessage("listen from menu in HTTP Script - ready state - command: " + command);
                    integer amount = (integer)command;

                    //this will change the StipendModification to the amount sent in the menu
                    modifyDefaultStipendAmount(amount,avuuid);

                    }
               
                }else
            if (channel==MENU_CHANNEL){
                if (command=="Withdraw"){
                    debugMessage("Avatar who clicked: " + (string)avuuid);
                        sendCommand("WITHDRAW","none&senderavatarname="+llKey2Name(avuuid),avuuid);
                }
                if ((command=="Set Stipends")||(command=="Set iPoints")){
                         displayClassListMenu(avuuid);
                }else
                if (command=="Configure")
                    displayConfigureMenu(avuuid);
                    else
                if (command=="Points"){
                    debugMessage("Searching list for user: " + llKey2Name(avuuid));
                    userIndex = llListFindList(avatarNameList, [llKey2Name(avuuid)]);
                    if (userIndex!=-1) llDialog(avuuid,"You have "+llList2String(avatarAllocationList,userIndex)+ " points!",[],MENU_CHANNEL);
                    else {
                        llMessageLinked(LINK_SET, ALL_MEMORY, "GET POINTS|" +(string)avuuid+"|"+llKey2Name(avuuid) , llGetKey());
                        
                    }
                    
                    
                }else if (command=="Help")
                    {
                        if (avuuid==llGetOwnerKey(llGetKey())) displayHelpNotecard(llDetectedKey(0),"stipend_giver_1.0_admin_menu_help.txt");
                        else displayHelpNotecard(llDetectedKey(0),"iBank_1.0_student_menu_help.txt");
                    }
            }
            else if  (channel==ADMIN_CHANNEL)
            {

                if (command==">>"){
                    
                    prevMenuButtons=numMenuButtons;
                    masterMenuIndex+=numMenuButtons;
                    debugMessage("masterMenuIndex: "+(string)masterMenuIndex+ " currentMenuIndex: "+(string)currentMenuIndex +" prevMenuButtons: "+(string)prevMenuButtons);
                    //if the next button is available, that means there are more students to be seen
                    //but if those students arent loaded in this mem stick
                    //we must wake up the next memory stick
                   
                    if ((currentMenuIndex+MAX_BUTTONS)>=memStickNumStudents){
                        llMessageLinked(LINK_SET, MY_SCRIPT_CHANNEL+1, "WAKE UP|"+(string)masterMenuIndex, "");
                        state waiting;
                    }else{
                        currentMenuIndex+=numMenuButtons;
                        displayClassListMenu(avuuid);
                    }
                }else if (command=="<<"){
                    masterMenuIndex-=MAX_BUTTONS;
                    debugMessage("masterMenuIndex: "+(string)masterMenuIndex+ " currentMenuIndex: "+(string)currentMenuIndex +" prevMenuButtons: "+(string)prevMenuButtons);

                    //if the previous button is available, that means there are more students to be seen
                    //but if those students arent loaded in this mem stick
                    //we must wake up the previous memory stick
                    if ((currentMenuIndex==0) && (myNum > 0)){
                        llMessageLinked(LINK_SET, MY_SCRIPT_CHANNEL-1, "WAKE UP|"+(string)masterMenuIndex, "");
                        state waiting;
                    }else {   
                        currentMenuIndex-=prevMenuButtons;
                        displayClassListMenu(avuuid);                   
                    }
                }else if(command=="(-~ MENU ~-)"){
                            displayMainMenuDialog(avuuid);
                }             
              else{
                    //DISPLAYS a special menu for the admin with functions that can be applied to the particular student
                    //Modify (Stipend) Back
                    string userSelected = command;

                    displayUserMenu(userSelected,avuuid);
                }
            }else
            if (channel >= USER_CHANNELS){//messge came from a response from admin from userMenu
                //If modify was chosen, show a list of modification increments +5 +10 +100 + 1000 etc
                if (command=="Modify")
                    displayUserModMenu(channel,avuuid);
                else
                    if (command=="(~~ SAVE ~~)"){
                        //now apply saved modification
                        saveAmountModification(channel,avuuid);
                    }else if(command=="(-~ MENU ~-)"){
                            displayClassListMenu(avuuid);
                    }
                else {//***********  message is a number so change modification
                    debugMessage("listen from menu in HTTP Script - ready state - command: " + command);
                    integer amount = (integer)command;

                    //this will change the usersModification to the amount sent in the menu
                   

                    integer userIndex = llListFindList(channelList,[channel]);
                    string avatarName = llList2String(avatarNameList,userIndex);
                    integer modAmount = llList2Integer(modifiedStipendAmounts,userIndex)+llList2Integer(avatarAllocationList,userIndex);
                    modifyStipendAmount(amount,channel,avuuid);
                    //  llOwnerSay(avatarName + "'s stipend will be modified to " + (string)modAmount+ " Press ( ~~ Save ~~ ) when done modifying");
                }

            }else if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                    if (command=="do:reset") llResetScript();

            }
           
        }
    http_response(key id,integer status,list meta,string body) {
        if ((id != http)) return;
        (http = NULL_KEY);
        llSetTimerEvent(0.0);
        if ((status != 200)) {
            debugMessage(("HTTP request failed with status code " + ((string)status)));
            return;
        }
        stipendHandleResponse(body);               
    }
    timer() {
        llSetTimerEvent(0.0);
        debugMessage("http_script: HTTP Timeout");
    }



    }
