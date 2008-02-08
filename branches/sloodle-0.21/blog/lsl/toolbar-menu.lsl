//////////
//
// Sloodle Toolbar menu script
// Controls the Toolbar as a whole, and runs the Classroom Gestures
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  - <unknown>
//
//////////
//
// Versions:
//  - <history unknown>
//
//////////


// Is the toolbar flipped over? (i.e. is it on gestures?)
integer flipped = 0;
// Sound to be played when the toolbar is touched
string touchSound = "";

// This list stores information in sets of 4:
//  {string:name of gesture button} {string:name of animation} {string:text to each in chat} {integer:playing?}
// Note that the echo text will immediately follow the avatar's name.
// E.g. if the echo text is "raises his/her hand", then the output might be: "Pedro McMillan raises his/her hand".
// The "playing?" item can have one of 3 values. If the animation only plays once at a time, it should be -1.
//  If the animation loops, but it is *not* currently playing, it should be 0. If it loops and is currently playing, it should be 1.
list animdata = [   "gesture:handup", "LongRaise", "raises his/her hand.", 0,
                    "gesture:wave", "Wave", "waves.", 0,
                    "gesture:clap", "clap", "claps.", -1,
                    "gesture:nodoff", "Nodoff", "falls asleep.", -1,
                    "gesture:huh", "IDontUnderstand", "scratches his/her head.", -1,
                    "gesture:gotit", "gotit", "has got it!", -1,
                    "gesture:yes", "Yes", "nods his/her head.", -1,
                    "gesture:no", "No", "shakes his/her head.", -1
                ];


default
{
    state_entry()
    {
        // We need to get animation permissions
        llRequestPermissions(llGetOwner(), PERMISSION_TRIGGER_ANIMATION);
        // Preload the touching sound
        if(touchSound!=""){
            llPreloadSound(touchSound); 
        }

    }
    
    on_rez(integer param)
    {
        llSetRot(ZERO_ROTATION);
        llResetScript();
    }
    
    run_time_permissions(integer id)
    {
    }

    touch_start(integer total_number)
    {
        // Which link was touched?
        integer linknumber = llDetectedLinkNumber(0);
        string name = llGetLinkName(linknumber);
        if (name == llGetObjectName()) {
            // Toggle the rotation between gestures and blog
            if (!flipped)
            {
                llSetRot(llEuler2Rot(<0,PI,0>));
                flipped = 1;
            }
            else
            {
                llSetRot(ZERO_ROTATION);
                flipped = 0;
            }
            return;
        }
        
        // Was this a gesture command?
        integer pos = llListFindList(animdata, [name]);
        if (pos >= 0) {
            // Make sure there is enough data (there should be 3 more elements beyond the button name)
            if ((pos + 3) >= llGetListLength(animdata)) return;
            // Extract the animation data
            string animname = llList2String(animdata, pos + 1);
            string animtext = llList2String(animdata, pos + 2);
            integer playing = llList2Integer(animdata, pos + 3);
            string avname = llKey2Name(llGetOwner());
            
            // What do we do?
            if (playing < 0) {
                // Play the animation once and echo the gesture to chat
                llStartAnimation(animname);
                llSay(0, avname + " " + animtext);
                
            } else if (playing == 0) {
                // Start playing the animation and echo the gesture to chat
                llStartAnimation(animname);
                llSay(0, avname + " " + animtext);
                // Set the "playing" flag to 1
                animdata = llListReplaceList(animdata, [1], (pos + 3), (pos + 3));
                // Highlight the button
                llSetLinkColor(linknumber, <1.0,1.0,0.0>, ALL_SIDES);
                
            } else if (playing > 0) {
                // Stop playing the animation
                llStopAnimation(animname);
                // Set the "playing" flag back to 0
                animdata = llListReplaceList(animdata, [0], (pos + 3), (pos + 3));
                // Deactivate the button highlight
                llSetLinkColor(linknumber, <1.0,1.0,1.0>, ALL_SIDES);
            }
        }
    }
}
