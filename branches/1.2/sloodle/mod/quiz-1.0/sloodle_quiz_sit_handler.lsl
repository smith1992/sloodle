// Sloodle quiz chair sit handler
// Detects the avatar sitting on it, and sends the main script a message to tell it to give them a quiz.
// This is done in a seperate script so that it can be easily switched out for scripts that want to do something else before the quiz starts.
// Specifically, the awards script wants to make sure it has a Game ID before starting the quiz.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-10 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//

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
        
key sitter;

default
{
        
    changed(integer change)
    {
        // Something changed - was it a link?
        if (change & CHANGED_LINK) {
            llSleep(0.5); // Allegedly llUnSit works better with this delay
                    
            // Has an avatar sat down?
            if (llAvatarOnSitTarget() != NULL_KEY) {
                        
                // Store the new sitter
                sitter = llAvatarOnSitTarget();

                llMessageLinked( LINK_SET, SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR, "START QUIZ", sitter ); // the string paramter is just for debugging

            }
        }
    }
    
}


