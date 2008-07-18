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
// This version will create one colored horizontal block for each choice - the blocks grow as choices increase.
//
// The back-end handles all of the Moodle interactions, and processing the requests/responses.
// It will communicate with the front-end script(s) via link message.
//
////////////


///// DATA /////

// What channel should choice control operate over?
integer SLOODLE_CHANNEL_OBJECT_CHOICE = -1639270051;
// Channel used to communicate between the front-end and the options
integer SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION = -1639270052;
// What channel should configuration data be received on?
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
// This channel will be used for the avatar setting options
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
// Channel used for avatar dialogs that we want to ignore
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;


// The maximum number of options we can support
// (This can be based on many factors, including number of available colors)
integer MAX_NUM_OPTIONS = 15;

// The text of the choice question
string question_text = "";

// A list of option ID numbers
list optionids = [];
// A list of option texts
list optiontexts = [];
// A list of number of selections of each option (-1 if it is not to be shown)
list optionsels = [];
// A list of colors for the options
list optioncolors = [
    <1.0, 0.0, 0.0>,
    <0.0, 1.0, 0.0>,
    <0.0, 0.0, 1.0>,
    <1.0, 1.0, 0.0>,
    <1.0, 0.0, 1.0>,
    <0.0, 1.0, 1.0>,
    <0.5, 0.0, 0.0>,
    <0.0, 0.5, 0.0>,
    <0.0, 0.0, 0.5>,
    <0.5, 0.5, 0.0>,
    <0.5, 0.0, 0.5>,
    <0.0, 0.5, 0.5>
];

// The number of users who have not selected an option (-1 if it is not to be shown)
integer num_unanswered = -1;
// Is the choice currently accepting answers?
integer accepting_answers = TRUE;

// The number of options created so far (regardless of those deleted)
integer numoptionscreated = 0;

// Command texts
string CMD_RESET = "reset";
string CMD_QUESTION = "question";
string CMD_NUM_UNANSWERED = "num_unanswered";
string CMD_ACCEPTING_ANSWERS = "accepting_answers";
string CMD_OPTION = "option";
string CMD_SELECTION_RESPONSE = "selection_response";
string CMD_SELECTION_REQUEST = "selection_request";
string CMD_UPDATE_COMPLETE = "update_complete";

// Commands specific to this front-end
string CMD_KILL_OPTION = "kill_option";
string CMD_OPTION_SELECTED = "option_selected";
string CMD_OPTION_TEXT = "option_text"; // data = the text of this option
string CMD_OPTION_COLOR = "option_color"; // data = a color vector for this option
string CMD_OPTION_SIZE = "option_size"; // data = the size (on X axis) of this option
string CMD_OPTION_POSITION = "option_position"; // data = the position vector for this option


// Distance from root up to first option
float distance_to_first_option = 0.3;
// Separate between option objects
float option_separation = 0.4;
// Minimum allowable size of an option
float MIN_OPTION_SIZE = 0.04;


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
    // Clear the question text
    llSetText("", <0.0,0.0,0.0>, 0.0);
    // Completely reset the script
    llResetScript();
}


// Update the question text
// (Note: the text will already be stored in variable "question_text")
update_question()
{
    // Update the display
    llSetText(question_text, <1.0, 1.0, 1.0>, 1.0);
}

// Delete the option with the specified key
kill_option(integer optionnum)
{
    // Find the specified option data, and send the kill message
    if (optionnum < llGetListLength(optionids)) {
        llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_KILL_OPTION + "|" + (string)llList2Integer(optionids, optionnum));
    }
    
    // Reposition the other options
    reposition_options();
}

// Delete all option objects
kill_all_options()
{
    // Go through each option in the list
    integer num = llGetListLength(optionids);
    integer i = 0;
    for (; i < num; i++) {
        // Send the kill message
        llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_KILL_OPTION + "|" + (string)llList2Integer(optionids, i));
    }
}

// Add a new option (data will already be in lists)
// (Parameter "optionnum" gives the position in the option lists)
integer add_option(integer optionnum)
{
    // Make sure the option number is valid
    if (optionnum >= llGetListLength(optionids)) return FALSE;
    
    // Calculate the position of the first option
    vector pos = llGetPos();
    pos.z += distance_to_first_option;
    // Calculate the position for the new option
    pos.z += ((float)numoptionscreated * option_separation);
    
    // Get the data for the new option object
    integer optionid = llList2Integer(optionids, optionnum);
    string optiontext = llList2String(optiontexts, optionnum);
    
    // Create the object, and set its basic information
    llRezObject("Sloodle Choice Horizontal Bar", pos, <0.0,0.0,0.0>, llGetRot(), optionid);
    llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_TEXT + "|" + (string)optionid + "|" + optiontext);
    // Pick a color for this object (wrap round to the first colour if they have all been used)
    integer numcols = llGetListLength(optioncolors);
    vector optioncol = llList2Vector(optioncolors, (numoptionscreated % numcols));
    llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_COLOR + "|" + (string)optionid + "|" + (string)optioncol);
    
    // Update the sizes of all the options
    update_options();
    // Increment the number created so far
    numoptionscreated++;
    
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
// (Note: the value will already be in variable "accepting_answers")
update_accepting_answers()
{
    // Update the display
    //...
}


// Reposition all the options according to their current positions in the lists
reposition_options()
{
    // The 'working position' (i.e. position of the current option as we go along)
    // Start just above the root
    vector workingpos = llGetPos();
    workingpos.z += distance_to_first_option;
    
    // Go through each option
    integer num = llGetListLength(optionids);
    integer i = 0;
    for (; i < num; i++) {
        // Send the position command
        llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_POSITION + "|" + (string)llList2Integer(optionids, i) + "|" + (string)workingpos);
        // Advance to the next position
        workingpos.z += option_separation;
    }
}

// Update the display of all the options in one go
update_options()
{
    // The maximum size should be the same as the front-end object
    vector rootsize = llGetScale();
    float maxsize = rootsize.x - MIN_OPTION_SIZE;
    // Determine the maximum number of selections so far, if any
    integer maxnumselections = -1;
    integer numoptions = llGetListLength(optionsels);
    integer i = 0;
    for (; i < numoptions; i++) {
        if (llList2Integer(optionsels, i) > maxnumselections) maxnumselections = llList2Integer(optionsels, i);
    }
    
    // Calculate the relative size per selection, based on the maximum number
    float sizeperselection = maxsize;
    if (maxnumselections > 0) {
        sizeperselection = maxsize / (float)maxnumselections;
    }
    
    // Go through the options again
    integer curnum = 0;
    integer curid = 0;
    float cursize = 0.0;
    for (i = 0; i < numoptions; i++) {
        // If the option results are to be displayed, then calculate the size
        curnum = llList2Integer(optionsels, i);
        curid = llList2Integer(optionids, i);
        if (curnum >= 0) {
            cursize = MIN_OPTION_SIZE + ((float)curnum * sizeperselection);
            llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_SIZE + "|" + (string)curid + "|" + (string)cursize);
        } else {
            llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_SIZE + "|" + (string)curid + "|" + (string)maxsize);
        }
        
        // Send the question text with the number of votes appended
        llWhisper(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, CMD_OPTION_TEXT + "|" + (string)curid + "|" + llList2String(optiontexts, i) + " [" + (string)curnum + "]");
    }
    
}



///// STATES /////

// This is the only state of the script
default
{
    state_entry()
    {
        // Listen for option selection commands coming in
        llListen(SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION, "", NULL_KEY, "");
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    state_exit()
    {
    }
    
    on_rez(integer param)
    {
        resetScript();
    }
    
    listen(integer channel, string name, key id, string message)
    {
        // We only expect option selection commands
        if (channel != SLOODLE_CHANNEL_OBJECT_CHOICE_OPTION) return;
        if (llGetOwner() != llGetOwnerKey(id)) return;
        // We expect 3 parts to the message
        list parts = llParseStringKeepNulls(message, ["|"], []);
        if (llGetListLength(parts) < 3) return;
        string cmd = llList2String(parts, 0);
        integer optionid = (integer)llList2String(parts, 1);
        key uuid = (key)llList2String(parts, 2);
        
        // Make sure this is the correct command
        if (cmd != CMD_OPTION_SELECTED) return;
        sloodle_debug("CMD_OPTION_SELECTED");
        
        // Check that we recognise the option ID of the sending object
        integer num = llListFindList(optionids, [optionid]);
        if (num >= 0) {
            llMessageLinked(LINK_ALL_OTHERS, SLOODLE_CHANNEL_OBJECT_CHOICE, CMD_SELECTION_REQUEST + "|" + (string)optionid, uuid);
        }
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
                sloodle_debug("CMD_RESET");
                resetScript();
                return;
            
            } else if (cmd == CMD_QUESTION) {
                // Update our question text
                sloodle_debug("CMD_QUESTION");
                question_text = llList2String(parts, 1);
                update_question();
            
            } else if (cmd == CMD_NUM_UNANSWERED) {
                // Update the number of unanswered users
                sloodle_debug("CMD_NUM_UNANSWERED");
                num_unanswered = (integer)llList2String(parts, 1);
                update_num_unanswered();
            
            } else if (cmd == CMD_ACCEPTING_ANSWERS) {
                // Update whether or not we are accepting answers
                sloodle_debug("CMD_ACCEPTING_ANSWERS");
                string acceptingstr = llList2String(parts, 1);
                if (acceptingstr == "true") accepting_answers = TRUE;
                else accepting_answers = FALSE;
                update_accepting_answers();
            
            } else if (cmd == CMD_OPTION) {
                // This is an option update - get the values out
                sloodle_debug("CMD_OPTION");
                integer optionid = (integer)llList2String(parts, 1);
                string optiontext = llList2String(parts, 2);
                integer optionsel = (integer)llList2String(parts, 3);
                // Does this option already exist?
                integer optionfound = llListFindList(optionids, [optionid]);
                if (optionfound >= 0) {
                    // Yes - update the details
                    optiontexts = llListReplaceList(optiontexts, [optiontext], optionfound, optionfound);
                    optionsels = llListReplaceList(optionsels, [optionsel], optionfound, optionfound);
                    // We don't update the display here, as that can take too long.
                    // Wait until the "update_complete" command comes through
                    
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
                sloodle_debug("CMD_SELECTION_RESPONSE");
                
                // Check what the message should be
                integer status_code = (integer)llList2String(parts, 1);
                string text = "";
                string name = "";
                if (id != NULL_KEY) name = " " + llKey2Name(id);
                
                if (status_code == 10012) text = "Thank you" + name + ". Your choice selection has been updated.";
                else if (status_code > 0) text = "Thank you" + name + ". Your choice selection has been made.";
                else if (status_code == -10011) text = "Sorry" + name + ". You have already made a choice, and it cannot be changed.";
                else if (status_code == -10012) text = "Sorry" + name + ". The maximum number of selections for this option has already been made. Please select another.";
                else if (status_code == -10013) text = "Sorry" + name + ". This choice is not yet open. Please try again later.";
                else if (status_code == -10014) text = "Sorry" + name + ". This choice is now closed.";
                else if (status_code == -10015) text = "Sorry" + name + ". Your selection has not been recognised. There may be an internal error. Please try again.";
                else text = "Sorry" + name + ". An unknown error occurred so your selection could not be made.";
                
                // Output the information - use a dialog if possible, but a chat message if no avatar was identified
                if (id == NULL_KEY) {
                    llSay(0, text);
                } else {
                    llDialog(id, text, ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
                }
            } else if (cmd == CMD_UPDATE_COMPLETE) {
                // Update the display of information
                update_options();
            }
        }
    }
}




