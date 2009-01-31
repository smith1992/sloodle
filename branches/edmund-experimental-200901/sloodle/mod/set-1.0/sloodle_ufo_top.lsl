integer SLOODLE_CHANNEL_SET_CONFIGURED = -1639270091;
integer SLOODLE_CHANNEL_SET_RESET = -1639270092; 

default 
{
    link_message(integer sender_num, integer num, string str, key id) {
        if (num == SLOODLE_CHANNEL_SET_CONFIGURED) {
            llSetPrimitiveParams([PRIM_GLOW, ALL_SIDES, 0.2]);
        } else if (num == SLOODLE_CHANNEL_SET_RESET) {
            llSetPrimitiveParams([PRIM_GLOW, ALL_SIDES, 0.0]);
        }
    }
    
    state_entry()
    {
        llSetTexture(TEXTURE_BLANK,0);
    }
}
 
