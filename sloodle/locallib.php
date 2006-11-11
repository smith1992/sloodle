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

	// gets a sloodle user record for a moodle user
	function sloodle_get_sloodle_user_for_moodle_user($mu) {
		// return a sloodle user record if it exists, null if it doesn't.
		$userid = $mu->id;
		return get_record('sloodle_users','userid',$userid);

	}
	
	// Returns the top
	function sloodle_login_zone_coordinates() {
		$pos = sloodle_get_config('loginzonepos');
		$size = sloodle_get_config('loginzonesize');
		if ( ($pos == null) || ($size == null) ) {
			return null;
		}
		$max = array();
		$min = array();
		if ( ( is_array($posarr = sloodle_vector_to_array($pos) ) ) && ( is_array( $sizearr = sloodle_vector_to_array($size) ) ) ) {
			$max['x'] = $posarr['x']+(($sizearr['x'])/2)-2;
			$max['y'] = $posarr['y']+(($sizearr['y'])/2)-2;
			$max['z'] = $posarr['z']+(($sizearr['z'])/2)-2;
			$min['x'] = $posarr['x']-(($sizearr['x'])/2)+2;
			$min['y'] = $posarr['y']-(($sizearr['y'])/2)+2;
			$min['z'] = $posarr['z']-(($sizearr['z'])/2)+2;
		} else {
			return false;
		}
		return array($max,$min);
	}

	function sloodle_finished_login_coordinates() {
	// return a position below the login zone for people whose avaar name we've already got.
		$pos = sloodle_get_config('loginzonepos');
		$size = sloodle_get_config('loginzonesize');
		if ( ($pos == null) || ($size == null) ) {
			return null;
		}
		$max = array();
		$min = array();
		if ( ( is_array($posarr = sloodle_vector_to_array($pos) ) ) && ( is_array( $sizearr = sloodle_vector_to_array($size) ) ) ) {
			$coord['x'] = round($posarr['x'],0);
			$coord['y'] = round($posarr['y'],0);
			$coord['z'] = round(($posarr['z']-(($sizearr['z'])/2)-2),0);
			return $coord;
		} else {
			return false;
		}
		
	}

	function position_is_in_login_zone($pos) {
		$posarr = sloodle_vector_to_array($pos);
		list($maxarr,$minarr) = sloodle_login_zone_coordinates();
		//print '<h1>cheking whtier pos '.$pos.sloodle_array_to_vector($posarr).' is bigger than '.sloodle_array_to_vector($maxarr).' and smaller than '.sloodle_array_to_vector($minarr).'</h1>';
		if ( ($posarr['x'] > $maxarr['x']) || ($posarr['y'] > $maxarr['y']) || ($posarr['z'] > $maxarr['z']) ) {
			return false;
		}
		if ( ($posarr['x'] < $minarr['x']) || ($posarr['y'] < $minarr['y']) || ($posarr['z'] < $minarr['z']) ) {
			return false;
		}
		return true;
	}

	function sloodle_vector_to_array($vector) {
		if (preg_match('/<(.*?),(.*?),(.*?)>/',$vector,$vectorbits)) {
			$arr = array();
			$arr['x'] = $vectorbits[1];
			$arr['y'] = $vectorbits[2];
			$arr['z'] = $vectorbits[3];
			return $arr;
		}
		return false;
	}

	function sloodle_array_to_vector($arr) {
		$ret = '<'.$arr['x'].','.$arr['y'].','.$arr['z'].'>';
		//print "<h1>$ret</h1>";
		return $ret;
	}

	// finds an available landing position
	function sloodle_generate_new_login_position() {
		// need to make a landing position that isn't already in use.
		// 2 possible approaches:
		// - make a position, then check it isn't already in use
		// - get a list of positions already in use, then go through looking for a new one.
		list($max,$min) = sloodle_login_zone_coordinates();

		$maxtries = 10;
		for ($i=0; $i<$maxtries; $i++) {
			$mypos = sloodle_random_position_in_zone($max,$min);
			$taker = get_record('sloodle_users','loginposition',sloodle_array_to_vector($mypos));
			if ($taker == null) {
				return $mypos;
			}
		}

		// TODO: After 10 random tries fail, do it the other way...
		//       We should also start recycling positions of users who have already got their sloodle names
		return false;
	
	}

	function sloodle_random_position_in_zone($zonemax,$zonemin) {
		$pos = array();
		$pos['x'] = rand($zonemin['x'],$zonemax['x']);	
		$pos['y'] = rand($zonemin['y'],$zonemax['y']);	
		$pos['z'] = rand($zonemin['z'],$zonemax['z']);	
		return $pos;
	}

	function sloodle_round_vector_array($pos) {
		foreach($pos as $pk => $pval) {
			$pos[$pk] = round($pval,0);
		}
		return $pos;
	}

?>
