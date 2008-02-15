//////////
//
// Sloodle Toolbar menu script (v2.0)
// Controls the Toolbar as a whole, and runs the Classroom Gestures
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  - <unknown>
//  - Peter R. Bloomfield
//
//////////
//
// Versions:
//  2.1 - added auto-hide feature (although it might not be very good yet... I think we need a better method!)
//  2.0 - centralised animation control in this script (rather than separate scripts)
//  - <history unknown>
//
//////////
//
// Usage:
//  This script should be in the *root* prim of an object, and expects that a series of linked
//   child prims are given names such as "gesture:wave". These objects should *not* process their
//   own "touch_start" events, but should pass them back to the parent (this is the default behaviour).
//  When "touch_start" is called, this object will get the name of the prim that was touched, and
//   look it up in a list of animation data. It will start/stop the associated animation as appropriate.
//
//  If the root prim is touched, then it flips between the two modes of operation.
//  If the "minimize_button" is touched, then it auto-hides or unhides itself.
//  
//
//////////

// Name of the button objects
string MINIMIZE_BUTTON = "minimize_button";
string RESTORE_BUTTON = "restore_button";
string HELP_BUTTON = "help_button";

// Name of the help notecard
string HELP_NOTECARD = "Sloodle Toolbar v1.3 Help";

// Is the toolbar flipped over? (i.e. is it on gestures?)
integer flipped = 0;
// Is the toolbar currently hidden ('minimized')?
integer hidden = 0;
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

        // Is the toolbar currently hidden?
        if (hidden == 1) {
            // If the restore button was pressed, then unhide it. Otherwise, ignore the touch.
            if (name == RESTORE_BUTTON) {
                hidden = 0;
                if (flipped) llSetRot(llEuler2Rot(<0,PI,0>));
                else llSetRot(ZERO_ROTATION);
            }
            return;
        }
        // Was the minimize button pressed?
        if (name == MINIMIZE_BUTTON) {
            // Hide it
            hidden = 1;
            llSetRot(llEuler2Rot(<0,PI * 0.5,0>));
            return;
        }

        // Ignore any other touches if we are hidden
        if (hidden == 1) return;
        
        // So what else was touched?
        if (name == llGetObjectName()) {
            // The toggle tabs were touched
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
        } else if (name == HELP_BUTTON) {
            // The help button was touched - give the help notecard
            if (llGetInventoryType(HELP_NOTECARD) == INVENTORY_NOTECARD) {
                llGiveInventory(llDetectedKey(0), HELP_NOTECARD);
            } else {
                // Nothing to give
                llOwnerSay("Cannot give help - notecard \"" + HELP_NOTECARD + "\" not found in my inventory.");
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

