<?php
require_once('../../config.php');
require_once('../../locallib.php');
require_once('../../login/sl_authlib.php');

sloodle_prim_require_script_authentication(); // make sure the client that's talking to us is allowed to do so.
sloodle_prim_require_user_login(); // check the avatar name and/or uuid arguments, and log the user in (creating a $USER) variable, or return errors to the client.

// the rest is up to you...
$sl_userid = $USER->id;

print "Moodle user ID is $sl_userid";
?>        
