/**********************************************************************************************
*  scoreBoard.lsl
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL
*
*  Contributors:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com
*  
*  PURPOSE
*  The Purpose of this script is to display an individual Player's game Data on XY Text
*  as a ROW of data.  
*  This Script will work inconjunction with several other ROWS in the entire Scoreboard
*  When a new Player joins the game, or gets added to the Scoreboard, this script is 
*  is copied from the ScoreBoard Rezzer Prim into a Scoreboard Row Prim and acts 
*  as an individual Row of the Scoreboard.  
*  A Settings Notecard is also copied into the Row Prim from which this script will get its SCOREBOARD CHANNEL FROM
*
*  INTENT
*  This script was created so that we can add a gaming element to the SLOODLE Educational Project
*  See http://sloodle.org 
*  Hopefully, adding a scoreboard into SL, and connecting it to educational activities, we can use Gaming
*  to motivate students.
*
*  FUNCTIONS
*  The ScoreBoard Rezzer Script will send the following commands to this script:
*  --- Update   --- changes the XY text Displayed
*  --- Add        --- changes the XY text Displayed
*  --- setPos    --- changes the position of this row
*  --- die_all  --- when sent by the ScoreBoard Rezzer, this row will be deleted
*  --- die         --- when sent by the ScoreBoard Rezzer, this row will be deleted
*   
**********************************************************************************************/

/////////////// CONSTANTS /////////////////
// XyText Message Map.
integer SET_LINE_CHANNEL    = 100100;
integer DISPLAY_STRING      = 204000;
integer DISPLAY_EXTENDED    = 204001;
integer REMAP_INDICES       = 204002;
integer RESET_INDICES       = 204003;
integer SET_CELL_INFO       = 204004;
string  LOAD_MSG            = "Loading...";
///////////// END CONSTANTS ////////////////
string playerName;
string playerPoints; 
integer prims;
integer numPlayers;
list tmp;
string command;
list MENU_MAIN;
vector ERROR = <0.88867, 0.00000, 0.13743>; //RED
vector      myColor;     
integer MENU_CHANNEL;
string  gSetupNotecardName = "settings";
integer gSetupNotecardLine;
integer SCOREBOARD_CHANNEL;
list players;
list playerKeys;
string keySent;
integer strideLength=3;
list    gInventoryList;
key gSetupQueryId;
integer MIDDLE_CHANNEL;
key myMiddleKey;
list tmpList;
integer maxLetterSpace=30;//the maximum characters that can fit on the board
integer spaceLen; //the number of spaces we need to insert to align Player name and points
string text; //the text to insert on the board

/***********************************************
*  getCommand()
*  Is used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  PLAYSOUND|50091bcd-d86d-3749-c8a2-055842b33484|soundPlayer 3|0.8  we send it instead like this:
*  COMMAND:PLAYSOUND|SOUND_UUID:50091bcd-d86d-3749-c8a2-055842b33484|SCRIPT_NAME:soundPlayer 3|VOLUME:0.8
*  By adding a context to the messages, the programmer can understand whats going on when debugging
*  All this function does is strip off the text before the ":" char
***********************************************/
string getCommand(string cmd){
     tmpList = llParseString2List(cmd, [":"],[]);
     return llList2String(tmpList,1);
}
SetText(string msg)
{
    llMessageLinked(LINK_SET, SET_LINE_CHANNEL, msg, "");
}

XytstOrder()
{ 
    // Fills each cell of the board with it's number.
    string  str = "";
    integer i = 0;
    do
    {
        str += llGetSubString("          " + (string)i,-10,-1);
        llSetText("Generating Pattern: " + (string)i, <0,1,0>, 1.0);
    }while(++i < prims);
    
    llSetText("Displaying Order Test...", <0,1,0>, 1.0);

    // Send the message
    llMessageLinked(LINK_SET, SET_LINE_CHANNEL, str, "");
    
    llSetText("", <0,1,0>, 0);
}

/***********************************************
*  getInventoryList()
*  used to read notecard settings
***********************************************/

   list getInventoryList()
    {
    list       result = [];
    integer    n = llGetInventoryNumber(INVENTORY_ALL);
    integer    i = 0;

    while(i < n)
    {
        result += llGetInventoryName(INVENTORY_ALL, i);
        ++i;
    }
    return result;
 }

/***********************************************
*  readSettingsNotecard()
*  used to read notecard settings
***********************************************/
readSettingsNotecard()
{
   gSetupNotecardLine = 0;
   gSetupQueryId = llGetNotecardLine(gSetupNotecardName,gSetupNotecardLine); 
}

/***********************************************
*  ListStridedRemove()
*  Deletes a strided item from a list
***********************************************/

list ListStridedRemove(list src, integer start, integer end, integer stride) {
    return llDeleteSubList(src, start * stride, (stride * (end + 1)) - 1);
}
/***********************************************
*  ListItemDelete()
*  Deletes an item from a list
***********************************************/

list ListItemDelete(list mylist,string element_old) {
    integer placeinlist = llListFindList(mylist, [element_old]);
    if (placeinlist != -1)
        return llDeleteSubList(mylist, placeinlist, placeinlist);
    return mylist;
}
default
{
    on_rez(integer start)
    {
        llResetScript();
        
    }
    
    state_entry()
    {
        // Determin the number of prims.
       
        prims = llGetNumberOfPrims();
        
        // Clear the screen.
        llMessageLinked(LINK_SET, DISPLAY_STRING, LOAD_MSG, "");
        
        integer StartLink = llGetLinkNumber() + 1;
        // Configure the board.
        integer i = 0;
        do
            llMessageLinked(StartLink + i, SET_CELL_INFO, llList2CSV([SET_LINE_CHANNEL, i * 10]), "");
        while( ++i < prims );
        readSettingsNotecard();      
        SetText( "Loading" );
         
    }
    
/***********************************************
*  dataserver()
*  Used to read settings notecard
***********************************************/

    dataserver(key queryId, string data)
    {
        if(queryId == gSetupQueryId) 
        {
            if(data != EOF)
            {    
                tmp= llParseString2List(data, ["|"], []);
                //llOwnerSay("Read: " + data);
                    if (llList2String(tmp,0)=="SCOREBOARD_CHANNEL") SCOREBOARD_CHANNEL=llList2Integer(tmp, 1);
                    else if (llList2String(tmp,0)=="MIDDLE_CHANNEL") MIDDLE_CHANNEL=llList2Integer(tmp, 1);                     
                gSetupQueryId = llGetNotecardLine(gSetupNotecardName,++gSetupNotecardLine); 
            }
            else state running;   
        }
    }      
}
state running{

 state_entry() {      
     SetText( "iBank Loading Player..." );
         llListen(SCOREBOARD_CHANNEL,"","", "");
 }
 
/***********************************************
*  listen()
*  This ScoreBoard Row Prim will listen to all messages from the SCOREBOARD Channel
*  Possible Messages are:
*  --- Update   --- changes the XY text Displayed
*  --- Add        --- changes the XY text Displayed
*  --- setPos    --- changes the position of this row
*  --- die_all  --- when sent by the ScoreBoard Rezzer, this row will be deleted
*  --- die         --- when sent by the ScoreBoard Rezzer, this row will be deleted
***********************************************/
 listen( integer chan, string name, key id, string msg)
    {
          
           if (chan == SCOREBOARD_CHANNEL){
                tmp = llParseStringKeepNulls(msg,["|"],[]);
                //ScoreBoardKey|playerName|playerKey"
                command = getCommand(llList2String(tmp,0));
                keySent = (key)getCommand(llList2String(tmp,1));
             
                if((command=="DELETE PLAYER SCOREBOARDS") || (command=="dial_all"))//  --- die_all -- when sent by the ScoreBoard Rezzer, this row will be deleted 
                    llDie();
                if (keySent==llGetKey())
                {
                    
                    if ((command=="ADD")||(command=="UPDATE")){ //add the plater states to the xy text
                        //Message Format:  "COMMAND:ADD/UPDATE|SCOREBOARDUUID:ScoreBoardKey|AVATARNAME:playerName|POINTS:playerPoints"                        
                        playerName = getCommand(llList2String(tmp,2));                                          
                        playerPoints = getCommand(llList2String(tmp,3));
                        maxLetterSpace=30;
                        spaceLen=maxLetterSpace - (llStringLength(playerName)+llStringLength((string)playerPoints));
                        text=playerName;
                        integer i=0;
                        for (i=0;i<spaceLen;i++)
                            text+=" ";
                        text+=playerPoints;
                        SetText(text);
                    }
                    else if ((command=="die") || (command=="dial_all")){
                        llDie();                         
                    }else
                    if (command=="SET PLAYER SCOREBOARD POSITION"){                     
                        string name= getCommand(llList2String(tmp,8));
                        vector origPos = llGetPos();
                        vector newPos;
                        vector basePos;
                        basePos.x = (float)getCommand(llList2String(tmp, 2));
                        basePos.y = (float)getCommand(llList2String(tmp, 3));
                        basePos.z = (float)getCommand(llList2String(tmp, 4));
                        newPos.x = (float)getCommand(llList2String(tmp, 5));
                        newPos.y = (float)getCommand(llList2String(tmp, 6));
                        newPos.z = (float)getCommand(llList2String(tmp, 7));
                        vector finalPos = basePos + newPos;
                        llSetPos(basePos + newPos);
                    }
                }
           }
    }    
   
}