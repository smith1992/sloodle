// Sloodle quiz chair UI
// Controls the movement of the quiz chair, based on linked messages from the main script.
// It should be possible to radically alter the object, eg. change it into an aeroplane etc - by altering this script.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-9 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG= -3857343;

integer SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR = -1639271102; //Tells us to start a quiz for the avatar, if possible.; Ordinary quiz chair will have a second script that detects and avatar sitting on it and sends it. Awards-integrated version waits for a game ID to be set before doing this.
integer SLOODLE_CHANNEL_QUIZ_STARTED_FOR_AVATAR = -1639271103; //Sent by main quiz script to tell UI scripts that quiz has started for avatar with key
integer SLOODLE_CHANNEL_QUIZ_COMPLETED_FOR_AVATAR = -1639271104; //Sent by main quiz script to tell UI scripts that quiz has finished for avatar with key, with x/y correct in string
integer SLOODLE_CHANNEL_QUESTION_ASKED_AVATAR = -1639271105; //Sent by main quiz script to tell UI scripts that question has been asked to avatar with key. String contains question ID + "|" + question text
integer SLOODLE_CHANNEL_QUESTION_ANSWERED_AVATAR = -1639271106;  //Sent by main quiz script to tell UI scripts that question has been answered by avatar with key. String contains selected option ID + "|" + option text + "|"
integer SLOODLE_CHANNEL_QUIZ_LOADING_QUESTION = -1639271107; 
integer SLOODLE_CHANNEL_QUIZ_LOADED_QUESTION = -1639271108;
integer SLOODLE_CHANNEL_QUIZ_LOADING_QUIZ = -1639271109;
integer SLOODLE_CHANNEL_QUIZ_LOADED_QUIZ = -1639271110;
integer SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION = -1639271111;            
integer SLOODLE_CHANNEL_QUIZ_ASK_QUESTION = -1639271112; // Tells the question handler scripts to ask the question with the ID in str to the avatar with key.
integer SLOODLE_CHANNEL_ANSWER_SCORE_FOR_AVATAR = -1639271113; // Tells anyone who might be interested that we scored the answer. Score in string, avatar in key.

integer doPlaySound = 0;

move_to_start( vector startingposition )
{
    vector position = llGetPos();
    position.z = startingposition.z;
    llSetPos(position);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);                
    if (name == "set:sloodleplaysound") doPlaySound = (integer)llList2String(bits,1);
    return 1;
}

// Move the chair up or down as visual feedback
move_vertical(float multiplier)
{
    vector position = llGetPos();
    position.z += 0.5 * multiplier;
    llSetPos(position);
}

// Play a sound as audio feedback
play_sound(float multiplier)
{
    // Do nothing if sound is disabled
    if (doPlaySound == 0) return;
    string sound_file;
    float volume;
        
    // Determine what our sound file and volume should be
    if (multiplier > 0) {
        sound_file = "Correct";
    } else {
        sound_file = "Incorrect";
        multiplier = multiplier * -1;
    }

    // Cap our volume
    if (multiplier > 1) {
        volume = 1.0;
    } else {
        volume = (float)multiplier;
    }    
            
    // Make sure the sound file exists, and then play it
    if (llGetInventoryType(sound_file) == INVENTORY_SOUND) llPlaySound(sound_file,multiplier);
}

default
{
    link_message(integer sender_num, integer num, string str, key id)
    {
        if (num == SLOODLE_CHANNEL_QUIZ_GO_TO_STARTING_POSITION) {
            move_to_start( (vector)str );
        } else if (num == SLOODLE_CHANNEL_ANSWER_SCORE_FOR_AVATAR) {
            move_vertical( (float)str );
            play_sound( (float)str );   
        } else if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; i < numlines; i++) {
                sloodle_handle_command(llList2String(lines, i));
            }
        }
    }
}

