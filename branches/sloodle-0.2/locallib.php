<?php
// Local library functions for the Sloodle module
// Part of the Sloodle project
// See www.sloodle.org for more information
//
// Copyright (c) 2007 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  various
//
	

	function sloodle_set_config($name,$value) {
        return set_config(strtolower($name), $value);
	}

	function sloodle_get_config($name) {
        $val = get_config(NULL, strtolower($name));
        // Older Moodle versions return an object instead of the value directly
        if (is_object($val))
            return $val->value;
        return $val;
	}
    
    function sloodle_prim_password() {
		return sloodle_get_config('sloodle_prim_password');
	}
    
    function sloodle_is_automatic_registration_on() {
		$method = sloodle_get_config('sloodle_auth_method');
		return ($method == 'autoregister');
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
		if (!isset($response->val) || empty($response->val) || is_null($response->val)) {
			print '<p align="center">Not getting the expected XMLRPC response. Is Second Life broken again?<br />';
			print "XMLRPC Error - ".$response->errstr."</p>";
			return false;
		}
		//TODO: Check the details of the response to see if this was successful or not...
		return true;

	}

    function sloodle_add_to_log($courseid = null, $module = null, $action = null, $url = null, $cmid = null, $info = null) {

       global $CFG;

       // TODO: Make sure we set this in the calling function, then remove this bit
       if ($courseid == null) {
          $courseid = optional_param('sloodle_courseid',0,PARAM_RAW);
       }

       // if no action is specified, use the object name
       if ($action == null) {
          $action = $_SERVER['X-SecondLife-Object-Name'];
       }

       $region = $_SERVER['X-SecondLife-Region'];
       if ($info == null) {
          $info = $region;
       }

       $slurl = '';
       if (preg_match('/^(.*)\(.*?\)$/',$region,$matches)) { // strip the coordinates, eg. Cicero (123,123)
          $region = $matches[1];
       }

       $xyz = $_SERVER['X-SecondLife-Local-Position'];
       if (preg_match('/^\((.*?),(.*?),(.*?)\)$/',$xyz,$matches)) {
          $xyz = $matches[1].'/'.$matches[2].'/'.$matches[3];
       }

       return add_to_log($courseid, null, $action, $CFG->wwwroot.'/mod/sloodle/toslurl.php?region='.urlencode($region).'&xyz='.$xyz, $userid, $info );
       //return add_to_log($courseid, null, "ok", "ok", $userid, "ok");

    }


    // Check whether or not Sloodle is installed
    // Returns true if so, or false if not
    function sloodle_is_installed()
    {
        // Is there a Sloodle entry in the modules table?
        return record_exists("modules", "name", "sloodle");
    }
	
?>
