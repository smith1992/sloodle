/**********************************************************************************************
*  scoreBoardRezzer.lsl
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL as part of the Sloodle.org project
*  Scripts used: XY Text - Written by Tdub Dowler
*
*  Contributors:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com
*  
*  PURPOSE
*  The Purpose of this script is to manage all of the Scoreboard ROW Prims
*  It will rezz a new ROW Prim for each new player and copy scoreboard.lsl and the settings notecard\
*  into each new rezzed row prim.
*  It will also reorder the points putting the highest scorers on top.
*
*  INTENT
*  This script was created so that we can add a gaming element to the SLOODLE Educational Project
*  See http://sloodle.org 
*  Hopefully, adding a scoreboard into SL, and connecting it to educational activities, we can use Gaming
*  to motivate students.
*  
*  LISTEN
*  The Scoreboard Rezzer listens for linked messages from the ibank scripts
*  Typical messages sent look like: 
*  To add a player:   
*     llMessageLinked(LINK_SET,IBANK_DATA_CHANNEL,"COMMAND:ADDPLAYER|MOODLEID:"+llList2String(playerData,0)+"|AVATARNAME:"+llList2String(playerData,2)+"|ALLOCATION:"+llList2String(playerData,3)+"|USERDEBITS:"+llList2String(playerData,3),NULL_KEY);
*  To update points:
*     llMessageLinked(LINK_SET, IBANK_DATA_CHANNEL,"COMMAND:ADDPOINTS|AVATARNAME:"+avName+"|MODIFYAMOUNT:"+modifyAmount, NULL_KEY);
*  

*  FUNCTIONS
*  The ScoreBoard Rezzer Script will send the following commands to this scoreboard.lsl:
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
vector topPos;
vector bottomPos;
string  LOAD_MSG            = "Loading...";
///////////// END CONSTANTS ////////////////
integer GAME_SERVER_CHANNEL=66;
string commandMessage;
list sortedPoints;
list sortedNames ;
key playerKey;
list scoreBoard;
list playerPoints;
list playerNames;
list positionIndex; 
integer prims;
integer numPlayers=0;
list MENU_MAIN;
vector ERROR = <0.88867, 0.00000, 0.13743>; //RED
vector      myColor;     
integer MENU_CHANNEL;
string  gSetupNotecardName = "settings";
integer gSetupNotecardLine;
integer SCOREBOARD_CHANNEL;
integer IBANK_DATA_CHANNEL=890;
key gSetupQueryId;
list    gInventoryList;
list players;
list playerKeys;
integer strideLength=3;
vector basePos;
rotation myRot;
string playerName;
integer pPoints;
key GAME_SERVER_UUID;
integer API_CHANNEL=100098;
list commandList;
string command;
list tmpList;
list messageData;
integer points;
integer index;
integer playerScore;
integer searchPlayers;
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
float getMultiplyer(){
    return numPlayers*0.3+0.6;
}
/***********************************************
*  SetText()
*  used to set the XY Text of a Row Prim
***********************************************/
SetText(string msg)
{
    llMessageLinked(LINK_SET, SET_LINE_CHANNEL, msg, "");
}
/***********************************************
*  getInventoryist()
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
*  setTopPos()
*  Chats the top position so the Border Prim can position itself correctly 
***********************************************/
setTopPos(){
        string commandMessage = "setTop";   
        basePos = llGetPos();              
        vector newScorePos = basePos + <0,0,1>;
        topPos= basePos + <0,0,getMultiplyer()>;
        //setTop|x|y|z|z|MyRot
        commandMessage += "|"+(string)newScorePos.x+"|";
        commandMessage += (string)newScorePos.y+"|";
        commandMessage += (string)newScorePos.z+"|";
        commandMessage += (string)getMultiplyer() +"|";
        commandMessage += (string)llGetKey();                       
        llSay(SCOREBOARD_CHANNEL,commandMessage);
        
        //set middle glass position

}
/***********************************************
*  setMiddlePos()
*  Chats the middle position so the glass Prim that fits between the rows to make it look pretty 
***********************************************/
setMiddlePos(){
        basePos = llGetPos();
        string commandMessage = "setMiddle|";
        vector middlePos= <basePos.x,basePos.y,(topPos.z-basePos.z)/2>;
        vector size = <0,0,topPos.z-basePos.z>;
        commandMessage += (string)basePos.x+"|";
        commandMessage += (string)basePos.y+"|";
        commandMessage += (string)basePos.z+"|";
        
        commandMessage += (string)middlePos.x+"|";
        commandMessage += (string)middlePos.y+"|";
        commandMessage += (string)middlePos.z+"|";
        
        commandMessage += (string)size.z+"|";
        commandMessage += (string)llGetKey();               
        llSay(SCOREBOARD_CHANNEL,commandMessage);

}

list ListStridedRemove(list src, integer start, integer end, integer stride) {
    return llDeleteSubList(src, start * stride, (stride * (end + 1)) - 1);
}

list ListItemDelete(list mylist,string element_old) {
    integer placeinlist = llListFindList(mylist, [element_old]);
    if (placeinlist != -1)
        return llDeleteSubList(mylist, placeinlist, placeinlist);
    return mylist;
}
/***********************************************
*  reOrderNames()
*  reorders the names with highest scores on top
***********************************************/
reOrderNames(){
        sortedNames = llListSort(playerNames, 1, TRUE);
       integer i;
       for (i=0; i <llGetListLength(playerNames);i++){
               //go through each playerName, and find out which location it is in the sorted list
               integer myIndex = llListFindList(sortedNames,[llList2String(playerNames,i)]);               
               //then set the position index appropriately so we can properly place the scoreBoard in world
               //positionIndex = llListReplaceList( positionIndex, [myIndex],i,i);               
                commandMessage = "COMMAND:SET PLAYER SCOREBOARD POSITION"; 
                commandMessage += "|SCOREBOARDUUID:"+ llList2String(scoreBoard,i);
                vector newScorePos = basePos+ <0,0,1>;
                commandMessage += "|BASEPOSITIONX:"+(string)newScorePos.x;
                commandMessage += "|BASEPOSITIONY:"+(string)newScorePos.y;
                commandMessage += "|BASEPOSITIONZ:"+(string)newScorePos.z;
                commandMessage += "|NEWPOSITIONX:0";
                commandMessage += "|NEWPOSITIONY:0";
                commandMessage += "|NEWPOSITIONZ:" + (string)(myIndex*0.3);
                commandMessage += "|AVATARNAME:"+llList2String(playerNames,i);
            //    llSay(0,"******************************************* "+llList2String(playerNames,i)+"'s Index is: " + (string)myIndex);
            //    llSay(0,(string)<0,0,(float)myIndex>);     
                llSay(SCOREBOARD_CHANNEL,commandMessage);
       }
}


reOrderPoints(){
    integer i;
    //build stride
    sortedPoints=[];
    //get all player names and store in a strided list
   //list will [point,name],[point,name1],[point,name2] etc
    for (i=0; i <llGetListLength(playerNames);i++){        
        //reOrder sortedPoints to look like [points,name];
        sortedPoints+= [llList2Integer(playerPoints,i),llList2String(playerNames,i)];        
    }
    //now that we have built our strided list, sort the list
    sortedPoints = llListSort(sortedPoints, 2, TRUE);
    sortedNames=[];
    for (i=0; i <llGetListLength(playerNames);i++)
    {
        sortedNames+=llList2String(sortedPoints,i+i+1);
    //    llSay(0,"Sorted : " + llList2String(sortedNames, i));
    }
    // now send SetPos messags out to display the highest points first
       for (i=0; i <llGetListLength(playerNames);i++){
               //go through each playerName, and find out which location it is in the sorted list
               integer myIndex = llListFindList(sortedNames,[llList2String(playerNames,i)]);               
               //then set the position index appropriately so we can properly place the scoreBoard in world
               //positionIndex = llListReplaceList( positionIndex, [myIndex],i,i);               
                commandMessage = "COMMAND:SET PLAYER SCOREBOARD POSITION"; 
                commandMessage += "|SCOREBOARDUUID:"+ llList2String(scoreBoard,i);
                vector newScorePos = basePos+ <0,0,1>;
                commandMessage += "|BASEPOSX:"+(string)newScorePos.x;
                commandMessage += "|BASEPOSY:"+(string)newScorePos.y;
                commandMessage += "|BASEPOSZ:"+(string)newScorePos.z;
                commandMessage += "|NEWPOSX:0";
                commandMessage += "|NEWPOSY:0";
                commandMessage += "|NEWPOSZ:"+ (string)(myIndex*0.3);
                commandMessage += "|AVATARNAME:"+llList2String(playerNames,i);
              //  llSay(0,"******************************************* "+llList2String(playerNames,i)+"'s Index is: " + (string)myIndex);
              //  llSay(0,(string)<0,0,(float)myIndex>);     
                llSay(SCOREBOARD_CHANNEL,commandMessage);
       }
       //setTop board position
        setTopPos();
        setMiddlePos();
}
default
{
    on_rez(integer start)
    {
        llResetScript();
        myRot = llGetRot();
        basePos= llGetPos();
  
    }
    
    state_entry()
    {
        myRot = llGetRot();
        // Determin the number of prims.
        bottomPos = llGetPos();
        prims = llGetNumberOfPrims();
        
        // Clear the screen.
        llMessageLinked(LINK_SET, DISPLAY_STRING, LOAD_MSG, "");
        
        integer StartLink = llGetLinkNumber() + 1;
        // Configure the board.
        integer i = 0;
        do
            llMessageLinked(StartLink + i, SET_CELL_INFO, llList2CSV([SET_LINE_CHANNEL, i * 10]), "");
        while( ++i < prims );
        
        // Build this script in world to reveal the secret message!
        
       
         readSettingsNotecard();
           setTopPos();
    }
    
 

    dataserver(key queryId, string data)
    {
        if(queryId == gSetupQueryId) 
        {
            if(data != EOF)
            {    
                list tmp= llParseString2List(data, ["|"], []);
                //llOwnerSay("Read: " + data);
                
                if (llList2String(tmp,0)=="SCOREBOARD_CHANNEL"){
                     SCOREBOARD_CHANNEL=llList2Integer(tmp, 1);
                 //    llOwnerSay("Found Scoreboard Data: " + data);
                }
                gSetupQueryId = llGetNotecardLine(gSetupNotecardName,++gSetupNotecardLine); 
            }
            else
            {
                state running;   
            }
        }
    }      
}

state running{

 state_entry()
    {  //  llSay(0,llGetKey());     
         MENU_CHANNEL = (integer)(llFrand(-1000000000.0) - 1000000000.0);       
         basePos = llGetPos();
         llSay(SCOREBOARD_CHANNEL,"die|"+(string)basePos.x+"|"+(string)basePos.y+"|"+(string)basePos.z+"|"+(string)llGetKey());         
        
         llListen(SCOREBOARD_CHANNEL,"",GAME_SERVER_UUID, "");
         llListen(MENU_CHANNEL,"",llGetOwner(),"");
         llListen(GAME_SERVER_CHANNEL, "", "","");
        //set the top position
         vector basePos = llGetPos();         
        //set the top position 
         string resetCommand="COMMAND:DELETE PLAYER SCOREBOARDS|";
         resetCommand+=(string)basePos.x+"|";
         resetCommand+=(string)basePos.y+"|";
         resetCommand+=(string)basePos.z+"|";          
         resetCommand+=(string)llGetKey()+"|";
         llSay(SCOREBOARD_CHANNEL,resetCommand);             
    }
    
    link_message(integer sender_num, integer num, string str, key id) {
        if (num==IBANK_DATA_CHANNEL){
            //llSay(0,"scoreboardRezzer script: "+str);
            messageData = llParseString2List(str, ["|"],[]);
            command = getCommand(llList2String(messageData,0));
            if (command=="ADDPLAYER"){
                //COMMAND|MOODLEID|AVNAME|ALLOCATION|USERDEBITS 
                string moodleId = getCommand(llList2String(messageData,1));
                playerName = getCommand(llList2String(messageData,2));
                playerScore = 0;
                playerScore = (integer)getCommand(llList2String(messageData,3));
                searchPlayers = llListFindList( playerNames, [playerName]);
                if (searchPlayers==-1){   //only add if already exists      
                        playerNames+=playerName;
                        positionIndex+=0;
                        playerPoints+=playerScore;                                                
                        llRezObject("scoreBoard", llGetPos() + <0.0, 0.0, 1+.3*numPlayers>, ZERO_VECTOR, llGetRot(), 0);  //rez the 1st object
                        //addMiddle|x|y|z
                        basePos = llGetPos();                        
                    }else llSay(0,playerName + " is already a player of this game!");            
           }else if (command=="REMOVEPLAYER"){
                    playerName = llList2String(messageData,1);
                    index= llListFindList(playerNames,[playerName]); 
                    if (index==-1) llSay(0,"Scoreboard Delete error");
                     else {
                         playerNames= llDeleteSubList(playerNames, index, index);            
                         playerPoints= llDeleteSubList(playerPoints, index, index);                         
                         commandMessage +="COMMAND:DIE|SCOREBOARDUUID:" + llList2String(scoreBoard,index);                        
                      //   llSay(0,"Sending: " + commandMessage);
                         llSay(SCOREBOARD_CHANNEL,commandMessage);
                            numPlayers--;
                         scoreBoard= llDeleteSubList(scoreBoard, index, index);
                        
                          reOrderPoints();
                    }                                        
                }else 
                if (command=="ADDPOINTS")
                { //addPoints|playerName|points
                    
                    playerName = getCommand(llList2String(messageData,1));                    
                    points = (integer)getCommand(llList2String(messageData,2));                    
                    index= llListFindList(playerNames,[playerName]);                     
                    if (index==-1) llSay(0,"Scoreboard addPoint error - can't find user "+playerName+" on the scoreboard!");
                     else {                        
                        string scoreboard = llList2String(scoreBoard,index);
                        //update points list
                        //update|scoreboardKey|playerName|points
                        integer newPoints = points;
                        playerPoints = llListReplaceList( playerPoints, [newPoints],index,index );
                         commandMessage = "COMMAND:UPDATE|SCOREBOARDUUID:" + scoreboard;                        
                         commandMessage +="|AVATARNAME:" + playerName;
                         commandMessage +="|POINTS:" + (string)newPoints; 
                                                                   
                         llSay(SCOREBOARD_CHANNEL,commandMessage);

                         reOrderPoints();
                    }                                        
                } 
        }
    }
         
     changed(integer change) {
          //if the inventory changed, read the notecard soodle_config and see if the user properly
          //added the sloodle of this server to the notecard
          if (change ==CHANGED_INVENTORY)
                llResetScript();
         }
    object_rez(key id)
    {
        scoreBoard+=id;
        llGiveInventory(id,"settings");  //give 2nd object to 1st object when rezzed
        llRemoteLoadScriptPin( id, "scoreBoard.lsl", 5577, 1, numPlayers);
       //send message to new scoreboard "ScoreBoardKey|playerName|playerPoints"
        llSleep(1.0);
        commandMessage = "COMMAND:ADD|SCOREBOARDUUID:"+llList2String(scoreBoard,numPlayers);
        commandMessage +="|AVATARNAME:"+llList2String(playerNames,numPlayers);
        commandMessage +="|POINTS:"+llList2String(playerPoints,numPlayers);
     //   llSay(0,"Sending: " + commandMessage);
     
        llSay(SCOREBOARD_CHANNEL,commandMessage);
     numPlayers++;  
       //now position idex in corect veritcal placement
       reOrderPoints();
            
       
                
    }
}   
