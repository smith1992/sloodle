<?php
    /**
    * SLOODLE for Schools.
    * This script allows an object in-world to lookup a course by external ID, database ID, short name, or full name.
    *
    * @package sloodle
    * @copyright Copyright (c) 2010 (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script should be called with the authentication token in the header.
    // It also requires the following HTTP parameter (GET or POST):
    //
    //  search = the term to search by
    //
    // The following HTTP parameter is optional:
    //
    //  mode = how to search
    //
    // The 'mode' parameter can indicate that a specific field is to be searched.
    // This includes 'id', 'databaseid', 'shortname', 'fullname'.
    // Note that 'id' is the external ID of the course, while 'databaseid' is the record key.
    // The 'mode' can also be 'flexible', which will search each field in turn.
    // 'id' and 'databaseid' will be checked for exact matches only, while 'short-name' and 'full-name' will be checked for partial matches.
    // 'flexible' is the default value if it is left unspecified.
    //
    
    // If a search was successfully carried out then the first data line will contain the number of matches.
    // Each subsequent data line will contain details of a matching course, with the following structure:
    //
    //  databaseid|id|full-name
    //
    // Note that several results may cause the response to be truncated in-world.
    // It is worth checking how many lines were returned compared to the number of results reported.
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Verify that this request is coming from a legitimate source
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    
    // Fetch the expected data
    $search = sloodle_clean_for_db($sloodle->request->required_param('search'));
    $mode = strtolower(optional_param('mode', 'flexible', PARAM_RAW));
    
    // Determine some DB-specific SQL syntax
    $SQL_LIKE = '';
    switch ($CFG->dbfamily)
    {
    case 'postgres':
        $SQL_LIKE = "ILIKE";
        break;
    case 'mysql': default:
        $SQL_LIKE = "LIKE";
        break;
    }


    // Are we searching for specifics?
    if ($mode == 'id' || $mode == 'databaseid' || $mode == 'shortname' || $mode == 'fullname')
    {
        // Do the requested search
        $recs = null;
        switch ($mode)
        {
        case 'id': $recs = get_records('course', 'idnumber', $search, 'fullname'); break;
        case 'databaseid': $recs = get_records('course', 'id', $search, 'fullname'); break;
        case 'shortname':
            $recs = get_records('course', 'shortname', $search, 'fullname');
            if (!$recs) $recs = get_records_sql("
                SELECT * FROM {$CFG->prefix}course
                WHERE shortname {$SQL_LIKE} '%{$search}%'
                ORDER BY fullname
            ");
            break;
        case 'fullname':
            $recs = get_records('course', 'fullname', $search, 'fullname');
            if (!$recs) $recs = get_records_sql("
                SELECT * FROM {$CFG->prefix}course
                WHERE fullname {$SQL_LIKE} '%{$search}%'
                ORDER BY fullname
            ");
            break;
        }
        
        // Did we find anything?
        if (!empty($recs))
        {
            $sloodle->response->add_data_line(count($recs));
            foreach ($recs as $r)
            {
                $sloodle->response->add_data_line(array($r->id, $r->idnumber, $r->fullname));
            }
        } else {
            $sloodle->response->add_data_line(0);
        }
        
        // Output our response
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // We're going to do a flexible search.
    // Search for exact matches on the IDs, and exact and partial matches on short and full-names.
    $recs_id = get_records('course', 'idnumber', $search, 'fullname');
    $recs_databaseid = get_records('course', 'id', $search, 'fullname');
    $recs_shortname_exact = get_records('course', 'shortname', $search, 'fullname');
    $recs_fullname_exact = get_records('course', 'fullname', $search, 'fullname');
    $recs_shortname_partial = get_records_sql("
                SELECT * FROM {$CFG->prefix}course
                WHERE shortname {$SQL_LIKE} '%{$search}%'
                ORDER BY fullname
    ");
    $recs_fullname_partial = get_records_sql("
                SELECT * FROM {$CFG->prefix}course
                WHERE fullname {$SQL_LIKE} '%{$search}%'
                ORDER BY fullname
    ");
    
    // If there was a single exact match on any of the fields then use it - prioritise IDs
    $rec = null;
    if (is_array($recs_id) && count($recs_id) == 1)
    {
        $rec = current($recs_id);
    }
    else if (is_array($recs_databaseid) && count($recs_databaseid) == 1)
    {
        $rec = current($recs_databaseid);
    }
    else if (is_array($recs_shortname_exact) && count($recs_shortname_exact) == 1)
    {
        $rec = current($recs_shortname_exact);
    }
    else if (is_array($recs_fullname_exact) && count($recs_fullname_exact) == 1)
    {
        $rec = current($recs_fullname_exact);
    }
    
    // Did a single exact match come through?
    if (!empty($rec))
    {
        // Output our response
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        $sloodle->response->add_data_line(1);
        $sloodle->response->add_data_line(array($rec->id, $rec->idnumber, $rec->fullname));
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Compile a list of all matches
    $recs = array();
    if (is_array($recs_id))
    {
        foreach ($recs_id as $r) $recs[$r->id] = $r;
    }
    if (is_array($recs_databaseid))
    {
        foreach ($recs_databaseid as $r) $recs[$r->id] = $r;
    }
    if (is_array($recs_shortname_exact))
    {
        foreach ($recs_shortname_exact as $r) $recs[$r->id] = $r;
    }
    if (is_array($recs_fullname_exact))
    {
        foreach ($recs_fullname_exact as $r) $recs[$r->id] = $r;
    }
    if (is_array($recs_shortname_partial))
    {
        foreach ($recs_shortname_partial as $r) $recs[$r->id] = $r;
    }
    if (is_array($recs_fullname_partial))
    {
        foreach ($recs_fullname_partial as $r) $recs[$r->id] = $r;
    }
    
    // Output all the results
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    $sloodle->response->add_data_line(count($recs));
    foreach ($recs as $r)
    {
        $sloodle->response->add_data_line(array($r->id, $r->idnumber, $r->fullname));
    }
    $sloodle->response->render_to_output();
    
?>