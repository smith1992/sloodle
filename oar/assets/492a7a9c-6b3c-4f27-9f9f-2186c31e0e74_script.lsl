integer SLOODLE_CHANNEL_SET_CONFIGURED = -1639270091;
integer SLOODLE_CHANNEL_SET_RESET = -1639270092; 
integer SLOODLE_CHANNEL_OBJECT_CREATOR_WILL_REZ_AT_POSITION = -1639270084;
integer SLOODLE_CHANNEL_OBJECT_CREATOR_REZ_FROM_POSITION = -1639270088;

integer SLOODLE_CHANNEL_OBJECT_CREATOR_AUTOREZ_STARTED = -1639270086;
integer SLOODLE_CHANNEL_OBJECT_CREATOR_AUTOREZ_FINISHED = -1639270087;

default 
{
        
    link_message(integer sender_num, integer num, string str, key id) {
        if (num == SLOODLE_CHANNEL_SET_CONFIGURED) {
            llSetColor(<0.0,1.0,0.0>, ALL_SIDES);
        } else if (num == SLOODLE_CHANNEL_SET_RESET) {
            llSetColor(<0.5,0.5,0.5>, ALL_SIDES);      
        } 
    } 
}
// Please leave the following line intact to show where the script lives in Subversion:
// SLOODLE LSL Script Subversion Location: 
