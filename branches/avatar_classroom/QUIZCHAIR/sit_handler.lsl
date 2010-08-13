// LSL script generated: avatar_classroom.QUIZCHAIR.sit_handler.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
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


integer SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR = -1639271102;
        
key sitter;

default {

        
    changed(integer change) {
        if ((change & CHANGED_LINK)) {
            llSleep(0.5);
            if ((llAvatarOnSitTarget() != NULL_KEY)) {
                (sitter = llAvatarOnSitTarget());
                llMessageLinked(LINK_SET,SLOODLE_CHANNEL_QUIZ_START_FOR_AVATAR,"START QUIZ",sitter);
            }
        }
    }
}
