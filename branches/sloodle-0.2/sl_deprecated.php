<?php
// Contains the deprecated functions for the Sloodle module
// Part of the Sloodle project
// See www.sloodle.org for more information
//
// Copyright (c) 2007 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  various (old code!)
//


// NOTE: please do not add code here
// These functions will be removed when no longer needed


    function sloodle_prim_render_errors($errors,$type='MISC',$doDie=true) {
		print 'ERROR|'.$type.'|'.join('|',$errors);
		if ($doDie) {
			exit;
		}
	}
	function sloodle_prim_render_error($error, $type='MISC', $doDie=true) {
		return sloodle_prim_render_errors(array($error),$type,$doDie);
	}

	function sloodle_prim_render_output($arr) {
	// Returns content in a form our prim can understand it
	// For now, a pipe-delimited list.
	// Expects either a single array or an array of arrays.
		if (is_array($arr[0])) {
			$lines = array();
			foreach ($arr as $arrArr) {
				$lines[] = 'OK|'.join('|',$arrArr);
			}
			print join("\n",$lines);
		} else {
			print 'OK|'.join('|',$arr);
		}
	}

    function sloodle_lsl_output($script) { // eg. lsl/sl_auth/ExperimentalLoginClient.txt (relative to SLOODLE_DIRROOT)
		$filename = SLOODLE_DIRROOT.'/'.$script;
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		return $contents;

	}

	function sloodle_lsl_output_substitution($script, $subs) {

		if ($contents = sloodle_lsl_output($script)) {
			foreach ($subs as $k=>$v) {
				$contents = preg_replace('/'.$k.'/',$v,$contents);
			} 
			return $contents;
		} else {
			return false;
		}
	}

	function sloodle_require_setup_done($feature) {

	}
    
?>