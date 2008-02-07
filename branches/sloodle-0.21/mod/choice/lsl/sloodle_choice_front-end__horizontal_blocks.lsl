//////////
//
// Sloodle Choice Front-End: horizontal blocks
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
// This script is designed to provide one version of the front-end functionality for the Sloodle Choice.
// It should be housed in an object which is *linked* to a Sloodle Choice back-end object.
// This version will create one coloured horizontal block for each choice - the blocks grow as choices increase.
//
// The back-end handles all of the Moodle interactions, and processing the requests/responses.
// It will communicate with the front-end script(s) via link message.
//
////////////


///// DATA /////

// What channel should choice control operate over?
integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
// This channel will be used for the avatar setting options
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
// Channel used for avatar dialogs that we want to ignore
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;


// The maximum number of options we can support
// (This can be based on many factors, including number of available colours)
integer MAX_NUM_OPTIONS = 15;

// The text of the choice question
string question_text = "";

// A list of UUIDs for option blocks
list optionkeys = [];
// A list of option ID numbers
list optionids = [];
// A list of option texts
list optiontexts = [];
// A list of number of selections of each option (-1 if it is not to be shown)
list optionsels = [];
// A list of colours for the options
list optioncolours = [];

// The number of users who have not selected an option (-1 if it is not to be shown)
integer num_unanswered = -1;
// Is the choice currently accepting answers?
integer accepting_answers = TRUE;

// Colour constants
vector RED = <1.0,0.0,0.0>;
vector GREEN = <0.0,1.0,0.0>;
vector BLUE = <0.0,0.0,1.0>;
vector YELLOW = <1.0,1.0,0.0>;
vector PURPLE = <1.0,0.0,1.0>;
vector CYAN = <0.0,1.0,1.0>;
vector WHITE = <1.0,1.0,1.0>;
vector GREY = <0.6,0.6,0.6>;
vector DARKRED = <0.5,0.0,0.0>;
vector DARKGREEN = <0.0,0.5,0.0>;
vector DARKBLUE = <0.0,0.0,0.5>;
vector DARKYELLOW = <0.5,0.5,0.0>;
vector DARKPURPLE = <0.5,0.0,0.5>;
vector DARKCYAN = <0.0,0.5,0.5>;
vector DARKGREY = <0.3,0.3,0.3>;

// Command texts
string CMD_RESET = "reset";
string CMD_QUESTION = "question";
string CMD_NUM_UNANSWERED = "num_unanswered";
string CMD_ACCEPTING_ANSWERS = "accepting_answers";
string CMD_OPTION = "option";
string CMD_SELECTION_RESPONSE = "selection_response";
string CMD_SELECTION_REQUEST = "selection_request";

// Commands specific to this front-end
string CMD_KILL_OPTION = "kill_option";
string CMD_OPTION_SELECTED = "option_selected";


///// FUNCTIONS /////

// Output debug information to a linked debug script
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Reset the whole script
resetScript()
{
    // Delete all option objects
    kill_all_options();
    // Completely reset the script
    llResetScript();
}


// Update the question text
// (Note: the text will already be stored in variable "question_text")
update_question()
{
    // Update the display
    //...
}

// Delete the option with the specified key
kill_option(key uuid)
{
    // Favour a link message
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_KILL_OPTION, uuid);
    // But let's use a chat message as well... just to make sure
    llSay(SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_KILL_OPTION + "|" + (string)uuid);
}

// Delete all option objects
kill_all_options()
{
    // Go through each option in the list
    integer num = llGetListLength(optionkeys);
    integer i = 0;
    for (; i < num; i++) {
        // Try both link and chat messages
        llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_KILL_OPTION, llList2Key(optionkeys, i));
        llSay(SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_KILL_OPTION + "|" + (string)llList2Key(optionkeys, i));
    }
}

// Update an option display
// (Parameter "optionnum" gives the position in the option lists)
integer update_option(integer optionnum)
{
    // Make sure the option number is valid
    if (optionnum >= llGetListLength(optionids)) return FALSE;
    
    // Update the display
    //...
    
    return FALSE;
}

// Add a new option (data will already be in lists)
// (Parameter "optionnum" gives the position in the option lists)
integer add_option(integer optionnum)
{
    // Make sure the option number is valid
    if (optionnum >= llGetListLength(optionids)) return FALSE;
    
    // Add the object
    //...
    
    return FALSE;
}

// Update the display showing the number who have not answered
// (Note: the value will be in variable "num_unanswered")
update_num_unanswered()
{
    // Update the display
    //...
}

// Update the display showing whether or not the choice is accepting answers
// (Note: the vlaue will already be in variable "accepting_answers")
update_accepting_answers()
{
    // Update the display
    //...
}



///// STATES /////

// This is the only state of the script
default
{
    state_entry()
    {
    }
    
    state_exit()
    {
    }
    
    on_rez(integer param)
    {
        resetScript();
    }
    
    link_message(integer sender_num, integer num, string msg, key id)
    {
        // Check which channel this is on
        if (num == SLOODLE_CHANNEL_OBJECT_CHOICE && sender_num != llGetLinkNumber()) {
            // This is a choice command, and it didn't come from this object
            // Split the command at pipe characters
            list parts = llParseStringKeepNulls(msg, ["|"], []);
            string cmd = llList2String(parts, 0);
            
            // Check what the command is
            if (cmd == CMD_RESET) {
                // Complete reset
                resetScript();
                return;
            
            } else if (cmd == CMD_QUESTION) {
                // Update our question text
                question_text = llList2String(parts, 1);
                update_question();
            
            } else if (cmd == CMD_NUM_UNANSWERED) {
                // Update the number of unanswered users
                num_unanswered = (integer)llList2String(parts, 1);
                update_num_unanswered();
            
            } else if (cmd == CMD_ACCEPTING_ANSWERS) {
                // Update whether or not we are accepting answers
                string acceptingstr = llList2String(parts, 1);
                if (acceptingstr == "true") accepting_answers = TRUE;
                else accepting_answers = FALSE;
                update_accepting_answers();
            
            } else if (cmd == CMD_OPTION) {
                // This is an option update - get the values out
                integer optionid = (integer)llList2String(parts, 1);
                string optiontext = llList2String(parts, 2);
                integer optionsel = (integer)llList2String(parts, 3);
                // Does this option already exist?
                integer optionfound = llListFindList(optionids, [optionid]);
                if (optionfound >= 0) {
                    // Yes - update the details
                    optiontexts = llListReplaceList(optiontexts, [optiontext], optionfound, optionfound);
                    optionsels = llListReplaceList(optionsels, [optionsel], optionfound, optionfound);
                    update_option(optionfound);
                    
                } else {
                    // Add the details to the list
                    optionids += [optionid];
                    optiontexts += [optiontext];
                    optionsels += [optionsel];
                    // Add a new option
                    add_option(llGetListLength(optionids) - 1);                
                }                
            
            } else if (cmd == CMD_SELECTION_RESPONSE) {
                // We have had a response to a selection
                
                // Check what the message should be
                integer status_code = (integer)llList2String(parts, 1);
                string text = "";
                string name = "";
                if (id != NULL_KEY) name = " " + llKey2Name(id);
                
                if (status_code == 10012) text = "Thank you " + name + ". Your choice selection has been updated.";
                else if (status_code > 0) text = "Thank you " + name + ". Your choice selection has been made.";
                else if (status_code == -10011) text = "Sorry " + name + ". You have already made a choice, and it cannot be changed.";
                else if (status_code == -10012) text = "Sorry " + name + ". The maximum number of selections for this option has already been made. Please select another.";
                else if (status_code == -10013) text = "Sorry " + name + ". This choice is not yet open. Please try again later.";
                else if (status_code == -10014) text = "Sorry " + name + ". This choice is now closed.";
                else if (status_code == -10015) text = "Sorry " + name + ". Your selection has not been recognised. There may be an internal error. Please try again.";
                else text = "Sorry, " + name + ". An unknown error occurred so your selection could not be made.";
                
                // Output the information - use a dialog if possible, but a chat message if no avatar was identified
                if (id == NULL_KEY) {
                    llSay(0, text);
                } else {
                    llDialog(id, text, ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
                }
            } else if (cmd == CMD_OPTION_SELECTED) {
                // An option has been selected
                // Make sure an avatar has been identified
                if (id == NULL_KEY) return;
                
                // Find out what the option ID was specified
                string optionid = llList2String(parts, 1);
                // Send the selection request
                llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_SELECTION_REQUEST + "|" + optionid, id);
            }
        }
    }
}




