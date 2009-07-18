integer SLOODLE_CHANNEL_SET_SIMPLE_DOOR_OPEN =-1639270101;
integer SLOODLE_CHANNEL_SET_SIMPLE_DOOR_CLOSED = -1639270102;

default
{
    link_message( integer sender_num, integer num, string str, key id ){ 
        if ( num == SLOODLE_CHANNEL_SET_SIMPLE_DOOR_OPEN ) {
            llSetAlpha(0.0, ALL_SIDES); // set entire prim 100% invisible.
        } else if ( num == SLOODLE_CHANNEL_SET_SIMPLE_DOOR_CLOSED ) {
            llSetAlpha(1.0, ALL_SIDES); // set entire prim 100% visible.
        }
    }
}

