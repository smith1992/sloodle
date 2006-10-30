<?php

	require('config.php');

	function sloodle_prim_render_errors($errors) {
		print 'ERROR|'.join('|',$errors);
	}

	function sloodle_prim_render_output($arr) {
	// Returns content in a form our prim can understand it
	// For now, a pipe-delimited list.
		print 'OK|'.join('|',$arr);
	}

?>
