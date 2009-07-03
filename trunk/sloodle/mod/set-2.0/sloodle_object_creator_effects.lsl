integer SLOODLE_CHANNEL_OBJECT_CREATOR_REZZING_STARTED = -1639270082;
integer SLOODLE_CHANNEL_OBJECT_CREATOR_REZZING_FINISHED = -1639270083;
integer SLOODLE_CHANNEL_OBJECT_CREATOR_WILL_REZ_AT_POSITION = -1639270084;
integer SLOODLE_CHANNEL_SET_CONFIGURED = -1639270091;
integer SLOODLE_CHANNEL_SET_RESET = -1639270092;


open_cargo_bay()
{
        llSetPos(<0, 0, -0.4>);
        llSleep(3);
        llSetPrimitiveParams([PRIM_TYPE, PRIM_TYPE_BOX, 0, <0.0, 1.0, 0.0>, 0.55, <0.0, 0.0, 0.0>, <0.8, 0.8, 0.0>, <0.0, 0.0, 0.0>]);
        llSetText("Touch cargo bay to rez objects", <0,0,1.0>, 1.0);
}

close_cargo_bay()
{
        llSetText("", <0,0,0>, 0.0);
        llSetPrimitiveParams([PRIM_TYPE, PRIM_TYPE_BOX, 0, <0.0, 1.0, 0.0>, 0.55, <0.0, 0.0, 0.0>, <1.6, 1.6, 0.0>, <0.0, 0.0, 0.0>]);  
        llSleep(2);
        llSetPos(<0, 0, -0.2>);
}

default
{
     
    timer()
    {
        llParticleSystem([]);
    }
    link_message(integer sender_num, integer num, string str, key id) {
        if (num == SLOODLE_CHANNEL_OBJECT_CREATOR_REZZING_STARTED) {
            llParticleSystem([      PSYS_SRC_ACCEL, <0.0, 0.0, -1.0>,
                        PSYS_PART_START_SCALE, <0.1, 0.1, 0.1>,     
                        PSYS_PART_END_SCALE, <0.05, 0.05, 0.05>,        
                        PSYS_PART_FLAGS, PSYS_PART_INTERP_SCALE_MASK | PSYS_PART_INTERP_COLOR_MASK,
                        PSYS_PART_MAX_AGE, 3.0,                     //This gives us the lifetime of the particles
                        PSYS_SRC_BURST_RATE, 0.2,                    //There's a new burst every 1.0 seconds
                        PSYS_SRC_BURST_SPEED_MIN, 4.0,                //The minimum speed of the particles (in m/s)
                        PSYS_SRC_BURST_SPEED_MAX, 6.0,                //The maximum speed - so they move slowly
                        PSYS_SRC_BURST_PART_COUNT, 400,                //How many particles to make
                        PSYS_SRC_PATTERN, PSYS_SRC_PATTERN_ANGLE_CONE, 
                        PSYS_SRC_ANGLE_BEGIN, 2.8,
                        PSYS_SRC_ANGLE_END, 3.2,
                        PSYS_PART_START_COLOR, <1.0, 1.0, 0.0>, //Starts it out normal colour (white)
                        PSYS_PART_END_COLOR, <1.0, 1.0, 0.0>//,
                    ]);   
        llSetTimerEvent(5.0);         
        } else if (num == SLOODLE_CHANNEL_OBJECT_CREATOR_REZZING_FINISHED) {
          //  llParticleSystem([]);
        } else if (num == SLOODLE_CHANNEL_SET_CONFIGURED) {
            open_cargo_bay();
        } else if (num == SLOODLE_CHANNEL_SET_RESET) {
            close_cargo_bay();
        }
    }
    state_entry()
    {
        close_cargo_bay();
    }
}

