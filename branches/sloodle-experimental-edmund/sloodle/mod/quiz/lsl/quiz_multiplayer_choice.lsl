// Quiz multiplayer choice choice
// Copyright 2008 Edmund Edgar
// Licensed under the GPL as part of the Sloodle project

integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_SETTING = 77654983; // for dialog about what number we are
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_SETTING = -77654983; // for object commands about what number we are
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_VALUE = 3232; // for dialog about what our value is 
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_VALUE = -77654982; // 

// -> command to issue right answers /3234 1 4
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_ANSWERS = 3234; // for dialog about what our value is 
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_ANSWERS = -77654984; // 

// -> command to issue right answers /3234 1 4
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_WRONG_ANSWERS = 3235; // for dialog about what our value is 
integer SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_WRONG_ANSWERS = -77654985; // 

integer SLOODLE_QUIZ_MULTIPLE_LINK_MY_NUMBER = 1;
integer SLOODLE_QUIZ_MULTIPLE_LINK_QUESTION = 2;
integer SLOODLE_QUIZ_MULTIPLE_LINK_RIGHT = 3;
integer SLOODLE_QUIZ_MULTIPLE_LINK_WRONG = 4;

integer DO_CONFIGURE_USING_AVATAR_DIALOG = 1;
integer DO_CONFIGURE_USING_OBJECT_COMMAND = 1;
integer DO_CONFIGURE_USING_LINK_MESSAGE = 1;

integer listener_avatar_choice_dialog_chair_setting = -1;
integer listener_avatar_choice_object_command_chair_setting = -1;

integer listener_avatar_choice_dialog_chair_value = -1;
integer listener_avatar_choice_object_command_chair_value = -1;

integer listener_avatar_choice_dialog_chair_answers = -1;
integer listener_avatar_choice_object_command_answers = -1; 

integer listener_avatar_choice_dialog_chair_wrong_answers = -1;
integer listener_avatar_choice_object_command_wrong_answers = -1; 

string g_choice_number = "";
string g_value = "";
key g_sitter = NULL_KEY;

refresh_text()
{
    string text = "("+(string)g_choice_number+") "+g_value;
    llSetText( text, <0,0,1.0>, 1.0 );
}

integer handle_success()
{
    llSleep(3);
    if (g_sitter != NULL_KEY) {
        llPlaySound("ed124764-705d-d497-167a-182cd9fa2e6c",1);
        llSay(0,"Correct!");
        //victory_roll();
        vector origpos = llGetPos();
        vector newpos = origpos;
        newpos.z = origpos.z + 2;
        llSetPos(newpos);
        llSleep(5);
        llUnSit(g_sitter);
        llSetPos(origpos);
    }
    return 1;
}

integer handle_failure()
{
    if (g_sitter != NULL_KEY) {
        llSay(0,"Wrong!");
        llPlaySound("85cda060-b393-48e6-81c8-2cfdfb275351",1);
        llUnSit(g_sitter);
    }
    return 1;
}

victory_roll()
{

}


default
{
    state_entry()
    {
        
        refresh_text();
        // start listening for an object to configure us. we'll keep listening until we get an answer, then stop.
        // we'll start listening again if the owner touches us.
        // nb. we'll also ask the user with a dialog.
        if (DO_CONFIGURE_USING_OBJECT_COMMAND == 1) {
            listener_avatar_choice_object_command_chair_setting = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_SETTING,"",NULL_KEY,""); 
            listener_avatar_choice_object_command_chair_value = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_VALUE,"",NULL_KEY,"");   
            listener_avatar_choice_object_command_answers = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_ANSWERS,"",NULL_KEY,"");

        }
        if (DO_CONFIGURE_USING_AVATAR_DIALOG == 1) { 
            listener_avatar_choice_dialog_chair_value = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_VALUE,"",llGetOwner(),"");
            listener_avatar_choice_dialog_chair_answers = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_ANSWERS,"",llGetOwner(),"");
            listener_avatar_choice_dialog_chair_wrong_answers = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_WRONG_ANSWERS,"",llGetOwner(),"");
        }
        llSitTarget(<0.0, 0.0, 0.8>, ZERO_ROTATION);
    }
    touch(integer num_detected) {
        if (llDetectedKey(0) == llGetOwner()) {
            if (DO_CONFIGURE_USING_OBJECT_COMMAND == 1) {
                       
            }
            if (DO_CONFIGURE_USING_AVATAR_DIALOG == 1) { 
                listener_avatar_choice_dialog_chair_setting = llListen(SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_SETTING,"",llGetOwner(),"");
                llDialog(llGetOwner(),"Pick my number",["1","2","3","4","5","6","7","8","9","10"],SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_SETTING);
                llSetTimerEvent(15);
            }
        }
    }
    timer() {
        llListenRemove(listener_avatar_choice_dialog_chair_setting);
    }
    listen( integer channel, string name, key id, string message ) {
        if ( (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_SETTING) || (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_SETTING) ) {
    
            g_choice_number = message;
            
            // OK, we've got the number - now listen for the value.
            if (DO_CONFIGURE_USING_OBJECT_COMMAND == 1) {
                         
            }

            if (DO_CONFIGURE_USING_AVATAR_DIALOG == 1) {
                llListenRemove(listener_avatar_choice_dialog_chair_setting);
            }
            //llListenRemove(listener_avatar_choice_dialog_chair_setting);
            //llListenRemove(listener_avatar_choice_object_command_chair_setting);
            
            refresh_text();
            
        } else if ( (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_VALUE) || (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_VALUE) ) {
    
            // expect a message like: /3232 1:up|2:down|3:in|4:under
            list bits = llParseStringKeepNulls(message,["|"],[]);
            integer mypos = llListFindList(bits,[g_choice_number]);
            integer i;
            
            for (i=0; i<llGetListLength(bits); i++) {
                string thisbit = llList2String(bits,i);
                list bitlets = llParseStringKeepNulls(thisbit,[":"],[]);
                if (g_choice_number == llList2String(bitlets,0)) {
                    g_value = llList2String(bitlets,1);
                    refresh_text();
                    
                }
            }
            
        } else if ( (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_ANSWERS) || (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_ANSWERS) ) {
        // Use this to handle correct answers - assumes you've got them all.
        // If it's not correct, drop them.
            // expect a message like this: /3234 1|4
            list bits = llParseStringKeepNulls(message,["|"],[]);
            integer find_result = llListFindList(bits,[g_choice_number]);
            if (find_result > -1) {
                handle_success();
            } else {
                handle_failure();
            }
           // g_value = "";
           // refresh_text();
        } else if ( (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_DIALOG_CHAIR_WRONG_ANSWERS) || (channel == SOCIAL_MINDS_CHANNEL_AVATAR_CHOICE_OBJECT_COMMAND_CHAIR_WRONG_ANSWERS) ) {
        // This handles wrong answers.
        // For when you want to drop the wrong ones 1 by 1 to make it more suspenseful...
            // expect a message like this: /3234 2|3
            list bits = llParseStringKeepNulls(message,["|"],[]);
            integer find_result = llListFindList(bits,[g_choice_number]);
            if (find_result > -1) {
                handle_failure();
            } 
        } 
    }
    changed(integer change) {
        if (change & CHANGED_LINK) { 
            g_sitter = llAvatarOnSitTarget();
        }
    }
    link_message(integer source, integer num, string str, key id) {
        if (DO_CONFIGURE_USING_LINK_MESSAGE == 1) {     
            if (num == SLOODLE_QUIZ_MULTIPLE_LINK_MY_NUMBER) {
                g_choice_number = str;
                refresh_text();                
            } else if (num == SLOODLE_QUIZ_MULTIPLE_LINK_QUESTION) { 
                g_value = str;
                refresh_text();
            } else if (num == SLOODLE_QUIZ_MULTIPLE_LINK_RIGHT) {
                handle_success();
            } else if (num == SLOODLE_QUIZ_MULTIPLE_LINK_WRONG) {
                handle_failure();
            }
        }
    }
}

