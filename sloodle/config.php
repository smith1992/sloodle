<?php

	// Configure your installation here...

	/* We need to store the avatar name and uuid somewhere...
	*  The easiest thing to do is to steal a couple of fields from the user table. 
	*  TODO: But to do it properly, we should really make our own table and store it there.
	*/
	define('SLOODLE_USER_TABLE', 'user');
	define('SLOODLE_AVATAR_NAME_FIELD', 'yahoo');
	define('SLOODLE_AVATAR_UUID_FIELD', 'skype');

	/* Password that proves to the sloodle module that it is being accessed by an object that is allowed to talk to it.
	*  This password needs to be communicated to the prim that will be accessing sloodle.
	*  TODO: It would be better to set this when the user first installs the module, and keep it in the database
	*/
	define('SLOODLE_PRIM_PASSWORD', 'drUs3-9dE');

	// Configuration ends

?>
