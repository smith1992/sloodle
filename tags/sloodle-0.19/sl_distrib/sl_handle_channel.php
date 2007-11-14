<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('../login/sl_authlib.php');

	$sloodleerrors = array();

    sloodle_prim_require_script_authentication();

	// When the object is created or finds its channel isn't working, it should open a channel and tell us what it is.
	// sl_handle_channel.php?pwd=297343293&ch=asdf-asdf-adsf-asdf
	$ch = optional_param('ch',null,PARAM_RAW);
	if ( ($ch != null) ) {
		if (sloodle_set_config('distribchannel',$ch)) {
			$data = array(sloodle_get_config('distribchannel'));
			sloodle_prim_render_output($data);
		} else {
			sloodle_prim_render_error('Setting channel failed');
		}
		exit;
	} 

	sloodle_prim_render_error('Channel parameter missing');

?>
