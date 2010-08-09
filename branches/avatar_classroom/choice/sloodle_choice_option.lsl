// LSL script generated: _SLOODLE_HOUSE.choice.sloodle_choice_option.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010
// Sloodle Choice (for Sloodle 0.3)
// This script represents a single bar on the bar graph display.
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield
//


integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;


// Choice commands
// Update the specified option. Followed by "|num|text|colour|count|prop"
//  - num is a local option identifier
//  - text is the caption to display for this option
//  - colour is a colour vector (cast to a string)
//  - count is the number selected so far (or -1 if we don't want to display any)
//  - prop is the proportion of maximum size to show (between 0 and 1)
string SLOODLE_CHOICE_UPDATE_OPTION = "do:updateoption";
// Select the specified option. Followed by "|num" (num is a local option identifier) 
string SLOODLE_CHOICE_SELECT_OPTION = "do:selectoption";


// The option scales on on the X axis.
// Define the boundary sizes of this option, on the X axis.
// (This actually represents double the visible size: only half will be visible due to path cutting).
// (That easily allows a bar graph that grows only in one direction.)
float MAX_SIZE = 5.0;
float MIN_SIZE = 0.1;

// This value determines if a number should be shown on this bar to indicate the number of selections so far.
integer SHOW_SELECTIONS_NUM = FALSE;


// The local number of this option
integer myoptionnum = -1;


// Set the scale of this option to the specified proportion
set_scale(float prop){
    if ((prop < 0.0)) (prop = 0.0);
    if ((prop > 1.0)) (prop = 1.0);
    vector myscale = llGetScale();
    (myscale.x = (MIN_SIZE + ((MAX_SIZE - MIN_SIZE) * prop)));
    llSetScale(myscale);
}

// Process an update command.
// (parts should be a list of parts from the incoming command).
// Returns true if successful, or false otherwise.
integer process_update_command(list parts){
    (myoptionnum = ((integer)llGetObjectDesc()));
    if ((llGetListLength(parts) < 6)) return FALSE;
    if ((myoptionnum != ((integer)llList2String(parts,1)))) return FALSE;
    vector mycol = ((vector)llList2String(parts,3));
    integer mycount = ((integer)llList2String(parts,4));
    float myprop = ((float)llList2String(parts,5));
    llSetColor(mycol,ALL_SIDES);
    if (((mycount >= 0) && SHOW_SELECTIONS_NUM)) llSetText(((string)mycount),mycol,0.9);
    else  llSetText("",mycol,0.0);
    set_scale(myprop);
    return TRUE;
}

// Reset this option back to defaults
reset_option(){
    (myoptionnum = (-1));
    llSetText("",<0.0,0.0,0.0>,0.0);
    llSetColor(<1.0,1.0,1.0>,ALL_SIDES);
    set_scale(0.0);
}


// Uninitialised
default {

    state_entry() {
        reset_option();
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_CHOICE)) {
            list parts = llParseString2List(sval,["|"],[]);
            string cmd = llList2String(parts,0);
            if ((cmd == "do:reset")) {
                reset_option();
            }
            else  if ((cmd == SLOODLE_CHOICE_UPDATE_OPTION)) {
                if (process_update_command(parts)) state ready;
            }
        }
    }
}


// Ready and responding to interactions
state ready {

    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_CHOICE)) {
            list parts = llParseString2List(sval,["|"],[]);
            string cmd = llList2String(parts,0);
            if ((cmd == "do:reset")) {
                state default;
            }
            else  if ((cmd == SLOODLE_CHOICE_UPDATE_OPTION)) {
                process_update_command(parts);
            }
        }
    }

    
    touch_start(integer num) {
        integer i = 0;
        for (; (i < num); (i++)) {
            llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_CHOICE,((SLOODLE_CHOICE_SELECT_OPTION + "|") + ((string)myoptionnum)),llDetectedKey(i));
        }
        llTriggerSound("confirmed",1.0);
    }
}