<?php
    /**
    * SLOODLE for Schools.
    * This script allows an object in-world to lookup module instances by type, name, and ID.
    *
    * @package sloodle
    * @copyright Copyright (c) 2010 (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    // This script should be called with the authentication token in the header.
    // It also requires the following HTTP parameters (GET or POST):
    //
    //  sloodlecourseid = database record ID of the course to search in
    //  type = the type of module to search for
    //
    // The following parameters are optional (only one should be provided, ID takes priority):
    //
    //  name = the name of module to search for
    //  sloodlemoduleid = the site-specific ID of the module instance to lookup
    //
    // If the name and sloodlemoduleid parameters are not specified, then all module instances of a particular type in the specifeid course will be returned.
    // If name is specified, then any names partially or fully matching the module will be returned.
    // If sloodlemoduleid is specified then the data about a specific module instance will be returned.
    //
    // The 'sloodlecourseid' parameter is not required if 'sloodlemoduleid' is specified, but it is recommended anyway.
    //
    // Some modules have a sub-type, such as SLOODLE modules.
    // The primary and secondary parts of the type should be delimited by a colon in the type parameter, such as "sloodle:presenter".
    //
    
    // If a search was successfully carried out then the first data line will contain the number of matches.
    // Each subsequent data line will contain details of a matching module instance, with the following structure:
    //
    //  id|type|name
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
    $type = strtolower(sloodle_clean_for_db($sloodle->request->required_param('type')));
    $name = sloodle_clean_for_db(optional_param('name', '', PARAM_RAW));
    $sloodlemoduleid = (integer)$sloodle->request->get_module_id(false);
    
    $courseid = 0;
    if (empty($sloodlemoduleid)) $courseid = (int)$sloodle->request->get_course_id(true);
    else $courseid = $sloodle->courseobj->id;
    
    // Make sure the specified course can be found
    if (!record_exists('course', 'id', $courseid))
    {
        $sloodle->response->set_status_code(-512);
        $sloodle->response->set_status_descriptor('COURSE');
        $sloodle->response->add_data_line('Requested course could not be found.');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Split up our type, if necessary
    $typeParts = explode(':', $type, 2);
    $mainType = $typeParts[0];
    $subType = '';
    if (count($typeParts) > 1) $subType = $typeParts[1];
    
    // Make absolutely sure the main type parameter is safe
    if (!ctype_alnum($mainType))
    {
        $sloodle->response->set_status_code(-811);
        $sloodle->response->set_status_descriptor('REQUEST');
        $sloodle->response->add_data_line('Main part of module type parameter must be alphanumeric.');
        $sloodle->response->render_to_output();
        exit();
    }
    // Determine the name of the module instance table
    $moduleInstanceTable = $CFG->prefix.$mainType;
    
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
    
    // We want to obtain and search by the sub-type as well, if applicable
    $subTypeSelect = '';
    $subTypeCondition = '';
    if ($mainType == 'sloodle')
    {
        $subTypeSelect = ', mit.type';
        $subTypeCondition = " AND mit.type = '{$subType}' ";
    }
    else if ($mainType == 'assignment')
    {
        $subTypeSelect = ', mit.assignmenttype';
        $subTypeCondition = " AND mit.assignmenttype = '{$subType}' ";
    }
    
    // What kind of search are we doing?
    $recs = null;
    if (!empty($sloodlemoduleid))
    {
        // ID lookup
        $sql = "
            SELECT cm.id, mit.name {$subTypeSelect}
            FROM {$CFG->prefix}course_modules cm
            
            LEFT JOIN {$CFG->prefix}modules m
            ON cm.module = m.id
            
            LEFT JOIN {$moduleInstanceTable} mit
            ON cm.instance = mit.id
            
            WHERE cm.course = $courseid
             AND m.name = '{$mainType}'
             {$subTypeCondition}
             AND cm.id = {$sloodlemoduleid}
            ORDER BY mit.name
        ";
        $recs = get_records_sql($sql);
    }
    else if (!empty($name))
    {
        // Name search - try an exact match first
        $sql = "
            SELECT cm.id, mit.name {$subTypeSelect}
            FROM {$CFG->prefix}course_modules cm
            
            LEFT JOIN {$CFG->prefix}modules m
            ON cm.module = m.id
            
            LEFT JOIN {$moduleInstanceTable} mit
            ON cm.instance = mit.id
            
            WHERE cm.course = $courseid
             AND m.name = '{$mainType}'
             {$subTypeCondition}
             AND mit.name {$SQL_LIKE} '{$name}'
            ORDER BY mit.name
        ";
        $recs = get_records_sql($sql);
        
        if ($recs == false || (is_array($recs) && count($recs) > 1))
        {
            // No exact match was found, or there were multiple exact matches.
            // Do a partial search instead.
            $sql = "
                SELECT cm.id, mit.name {$subTypeSelect}
                FROM {$CFG->prefix}course_modules cm
                
                LEFT JOIN {$CFG->prefix}modules m
                ON cm.module = m.id
                
                LEFT JOIN {$moduleInstanceTable} mit
                ON cm.instance = mit.id
                
                WHERE cm.course = $courseid
                 AND m.name = '{$mainType}'
                 {$subTypeCondition}
                 AND mit.name {$SQL_LIKE} '{$name}%'
                ORDER BY mit.name
            ";
            $recs = get_records_sql($sql);
        }
    }
    else
    {
        // Type search
        $sql = "
            SELECT cm.id, mit.name {$subTypeSelect}
            FROM {$CFG->prefix}course_modules cm
            
            LEFT JOIN {$CFG->prefix}modules m
            ON cm.module = m.id
            
            LEFT JOIN {$moduleInstanceTable} mit
            ON cm.instance = mit.id
            
            WHERE cm.course = $courseid
             AND m.name = '{$mainType}'
             {$subTypeCondition}
            ORDER BY mit.name
        ";
        $recs = get_records_sql($sql);
    }
    
    
    // Output our response
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    if (is_array($recs))
    {
        $sloodle->response->add_data_line(count($recs));
        foreach ($recs as $r)
        {
            // Extract the sub-type where necessary
            $fullType = $mainType;
            if (!empty($subType))
            {
                $fullType .= ':'.$subType;
            } else {
                if (!empty($r->type)) $fullType .= ':'.$r->type;
                else if (!empty($r->assignmenttype)) $fullType .= ':'.$r->assignmenttype;
            }
        
            $sloodle->response->add_data_line(array($r->id, $fullType, $r->name));
        }
    }
    else
    {
        $sloodle->response->add_data_line(0);
    }
    $sloodle->response->render_to_output();
    
?>