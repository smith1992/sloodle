/**********************************************************************************************
*  soundPlayer.lsl
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL
*
*  Contributors:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com
*  
*  PURPOSE
*  The Purpose of this script is simply to listen for linked messages and to play a sound
*  this this is a separate script specifically for playing sounds, the programmer can simply dump copies of this script into a prim to have
*  two or more sounds played at the same time.
*
*  
*  LINKEND MESSAGE LISTEN
*  A typical Linked message sent to this script would look like:
*  llMessageLinked(LINK_SET, SOUND_CHANNEL,"COMMAND:PLAYSOUND|SOUND_UUID:676bd8f1-a061-72f4-b56c-93408f9cba46|SCRIPT_NAME:soundPlayer|VOLUME:0.8", NULL_KEY);
*  
**********************************************************************************************/

list commandList;
string command;
list tmpList;
integer SOUND_CHANNEL = -34000;
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

default {
    link_message(integer sender_num, integer linkChannel, string str, key id) {        
        if (linkChannel==SOUND_CHANNEL){            
            commandList = llParseString2List(str, ["|"],[]);
            command= getCommand(llList2String(commandList,0));
            if ((command=="PLAYSOUND")&&llGetScriptName()==getCommand(llList2String(commandList,2))){
                //llPlaySound(getCommand(llList2Key(commandList,1)),(float)getCommand((string)llList2Float(commandList,3)));        
                llPlaySound(getCommand(llList2Key(commandList,1)),1.0);
            }
        }
    }
}
