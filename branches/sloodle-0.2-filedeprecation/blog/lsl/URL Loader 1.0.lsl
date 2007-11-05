//////////
//
// URL Loader
// Version 1.0
//
// Written by: Pedro McMillan, October 2007
// (RL: Peter R. Bloomfield, University of Paisley)
//
//
// When receiving a link message, this script will load a URL for a given user.
// The URL should be in the string, and the user specified by their key.
// The integer number of the link message can be customized using the INT_CODE variable.
//
// The purpose of this script is to allow the continued execution of another script,
// even after a link has been presented.
//
//////////

///// DATA /////

// The link message integer code identifying the message to load a URL
integer INT_CODE = 0;

///// ---- /////

default
{
    link_message( integer sender_num, integer msg_num, string str, key id )
    {
        // Is this the correct message number and are the variables non-empty?
        if (msg_num == INT_CODE && str != "" && id != NULL_KEY)
        {
            // Load the URL
            llLoadURL(id, "", str);
        }
    }
}
