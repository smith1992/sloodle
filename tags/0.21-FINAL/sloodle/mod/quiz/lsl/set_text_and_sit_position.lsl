// set_text_and_sit_position
// Edmund Edgar, 2008-03-06
// You can change this script to make your avatar sit at a different angle or height.

set_text_and_sit_position()
{
        
        rotation rot = ZERO_ROTATION;

        // Use this instead if you need the avatar to sit at a non-upright angle.
        //vector eul = <0,270,0>; //45 degrees around the z-axis, in Euler form
        //eul *= DEG_TO_RAD; //convert to radians
        //rotation rot = llEuler2Rot(eul); //convert to quaternion        
                
        llSitTarget(<0,0,.5>, rot);
        llSetSitText("Ride");
         
}

default
{
    state_entry()
    {
        set_text_and_sit_position();
    }
    on_rez(integer start_param)
    {
        set_text_and_sit_position();        
    }
}

