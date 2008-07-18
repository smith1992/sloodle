//////////
//
// Sloodle Choice Option: Horizontal Block
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) Sloodle 2008 (various contributors)
// Released under the GNU GPL
//
//
// Constributors:
//  Peter R. Bloomfield - original design and implementation
//
//
// This script is designed to provide one version of the option object functionality for a Sloodle Choice.
// It must be used with the "Horizontal Blocks" front-end.
// Each choice option is represented as a differently-colored horizontal block.
// If results are being displayed, then each block will grow to show the number of selections.
// (Note: in order to prevent the blocks becoming too big, the sizes will typically be relative to the
//   maximum number of selections made.)
// (Note: the objects grow outwards in both directions).
//
// The back-end handles all of the Moodle interactions, and processing the requests/responses.
// It will communicate with the front-end script(s) via link message.
// The front-end script(s) manage the rezzing and updating of individual option objects, like this one.
// (Communications from front-end to this object occur via whisper only)
//
////////////


///// DATA /////

// Channel used to communicate between the front-end and the options
integer SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION = -1639270052;

// The ID of this option (directly relates to an ID number in the Moodle database?)
integer myoptionid = 0;
// The text of this option (may or may not be displayed by this object)
string mytext = "";
// The current color of this option
vector mycolor = <1.0,1.0,1.0>;

// The size of this object (on the local X axis)
float mysize = 0.01;

// The alpha value of hover text
float TEXT_ALPHA = 0.8;


// INCOMING COMMANDS NOTE:
//  All commands arrive in this format:
//   cmd|id|data
//
// The "id" is the option ID specified as the starting parameter for this script.
// NOTE: this presents proximity issues if multiple choices, from DIFFERENT sites, owned by the same avatar, are within whisper range of each other!
// (OptionIDs should be unique to a site)
//
// Object should check that it matches the "uuid" parameter.
// The text for the "cmd" values is given below, with an explanation of the data expected
// ALL COMMANDS ARRIVE BY CHAT!

// Incoming commands (from front-end)
string CMD_OPTION_TEXT = "option_text"; // data = the text of this option
string CMD_OPTION_COLOR = "option_color"; // data = a color vector for this option
string CMD_OPTION_SIZE = "option_size"; // data = the size (on X axis) of this option
string CMD_OPTION_POSITION = "option_position"; // data = the position vector for this option
// Special-case incoming command
// Format: cmd|uuid (where uuid = key of this object)
string CMD_KILL_OPTION = "kill_option";

// Outgoing commands (to front-end, by link message)
string CMD_OPTION_SELECTED = "option_selected"; // format: "cmd|id|uuid" (where "uuid" is the key of the avatar making the selection)




///// FUNCTIONS /////

// Output debug information to a linked debug script
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}


///// STATES /////

// This is the only state of the script
default
{
    state_entry()
    {
        // Listen for commands
        llListen(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, "", NULL_KEY, "");
    }
    
    state_exit()
    {
    }
    
    on_rez(integer param)
    {
        myoptionid = param;
    }
    
    touch_start(integer num_detected)
    {
        // Go through each toucher
        integer i = 0;
        for (; i < num_detected; i++) {
            // Send the chat message to the front-end
            llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_SELECTED + "|" + (string)myoptionid + "|" + (string)llDetectedKey(i));
        }
    }
    
    listen(integer channel, string name, key id, string message)
    {
        // Check the channel number
        if (channel == SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION) {
            // Make sure the sender is owned by the same person as owns this object
            if (llGetOwner() != llGetOwnerKey(id)) return;
            
            // Option command - split it by pipes
            list parts = llParseStringKeepNulls(message, ["|"], []);
            integer numparts = llGetListLength(parts);
            if (numparts < 2) return;
            // Extract the basic parts
            string cmd = llList2String(parts, 0);
            integer optionid = (integer)llList2String(parts, 1);
            string data = "";
            if (numparts >= 3) data = llList2String(parts, 2);
            
            // Check that this object matches the specified ID
            if (myoptionid != optionid) return;
            
            // Check the command type
            if (cmd == CMD_OPTION_TEXT) {
                // Update the option text
                mytext = data;
                llSetText(mytext, mycolor, TEXT_ALPHA);
                
            } else if (cmd == CMD_OPTION_COLOR) {
                // Update the color
                mycolor = (vector)data;
                llSetColor(mycolor, ALL_SIDES);
                // Update the text color
                llSetText(mytext, mycolor, TEXT_ALPHA);
            
            } else if (cmd == CMD_OPTION_SIZE) {
                // Update the size
                mysize = (float)data;
                vector cursize = llGetScale();
                cursize.x = mysize;
                llSetScale(cursize);
                
            } else if (cmd == CMD_OPTION_POSITION) {
                // Update the position
                llSetPos((vector)data);
            
            } else if (cmd == CMD_KILL_OPTION) {
                // Delete self
                llDie();
            }
        }
    }
}




