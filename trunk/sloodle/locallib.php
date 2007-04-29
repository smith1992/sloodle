<?php

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

	function sloodle_set_config($name,$value) {
		$conf = get_record('sloodle_config','name',$name);
		if ($conf) {
			$conf->value = $value;
			return update_record('sloodle_config',$conf);
		} else {
			$conf = new object();
			$conf->name = $name;
			$conf->value = $value;
			return insert_record('sloodle_config',$conf);
		}
	}

	function sloodle_get_config($name) {
		$conf = get_record('sloodle_config','name',$name);
		if ($conf) {
			return $conf->value;
		}
		return false;
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

	function sloodle_prim_password() {
		return sloodle_get_config('SLOODLE_PRIM_PASSWORD');
	}

	function sloodle_send_xmlrpc_message($channel,$intval,$strval) {

	require_once('../lib/xmlrpc.inc');

    $client = new xmlrpc_client("http://xmlrpc.secondlife.com/cgi-bin/xmlrpc.cgi");

	    $content = '<?xml version="1.0"?><methodCall><methodName>llRemoteData</methodName><params><param><value><struct><member><name>Channel</name><value><string>'.$channel.'</string></value></member><member><name>IntValue</name><value><int>'.$intval.'</int></value></member><member><name>StringValue</name><value><string>'.$strval.'</string></value></member></struct></value></param></params></methodCall>';

	$response = $client->send(
		$content,
		60,
		'http'
	);

	//var_dump($response);
	if ($response->val == 0) {
		print '<p align="center">Not getting the expected XMLRPC response. Is Second Life broken again?<br />';
		print "XMLRPC Error - ".$response->errstr."</p>";
		return false;
	}
	//TODO: Check the details of the response to see if this was successful or not...
	return true;

}



?>
