<?php

/**
OpenSim/SLOODLE Tracker library script.
Contains common functionality for using the tracker.
Requires that the SLOODLE configuration script is already included.
*/


/**
Check the PHP information to check if the server platform is Windows.
Generally we can assume that if it isn't Windows then it is a Unix variant.
@return bool True if the platform is Windows, or false otherwise.
*/
function sloodle_tracker_platform_is_windows()
{
	return (strtoupper(substr(php_uname(), 0, 3)) == 'WIN');
}


/**
Generate a UUID.
@return string A string containing a standard-format UUID
@author Sean Colombo (taken from php.net)
*/
function sloodle_tracker_generate_uuid()
{       
    $pr_bits = null;
    $fp = @fopen('/dev/urandom','rb');
    if ($fp !== false) {
        $pr_bits .= @fread($fp, 16);
        @fclose($fp);
    } else {
        // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
        $pr_bits = "";
        for($cnt=0; $cnt < 16; $cnt++){
            $pr_bits .= chr(mt_rand(0, 255));
        }
    }
   
    $time_low = bin2hex(substr($pr_bits,0, 4));
    $time_mid = bin2hex(substr($pr_bits,4, 2));
    $time_hi_and_version = bin2hex(substr($pr_bits,6, 2));
    $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits,8, 2));
    $node = bin2hex(substr($pr_bits,10, 6));
   
    /*
     * Set the four most significant bits (bits 12 through 15) of the
     * time_hi_and_version field to the 4-bit version number from
     * Section 4.1.3.
     * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
     */
    $time_hi_and_version = hexdec($time_hi_and_version);
    $time_hi_and_version = $time_hi_and_version >> 4;
    $time_hi_and_version = $time_hi_and_version | 0x4000;
   
    /*
     * Set the two most significant bits (bits 6 and 7) of the
     * clock_seq_hi_and_reserved to zero and one, respectively.
     */
    $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
   
    return sprintf('%08s-%04s-%04x-%04x-%012s',
        $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
}

/**
Generates a unique UUID for an avatar.
This will ensure that the UUID generated is not already in the sloodle_users table.
This shouldn't be necessary, but may be helpful in the event that the random number generation on a given platform has low entropy.
@return string A new avatar UUID
*/
function sloodle_tracker_generate_unique_avatar_uuid()
{
    $isUnique = false;
    $uuid = "";
    do
    {
        // Generate a new UUID
        $uuid = sloodle_tracker_generate_uuid();
        // Look for it in the sloodle users table
        $isUnique = (count_records('sloodle_users', 'uuid', $uuid) == 0);
    } while (!$isUnique);
    
    return $uuid;
}

/**
Generate an OpenSim instance based on the given data.
@param int $cmid The course module identifier of a Tracker instance.
@param int $avid The ID of a record from sloodle_users, indicating which avatar this instance will be assigned to.
@return string A new OpenSim instance name (not guaranteed to be unique... but conflicts are unlikely and shouldn't cause a problem).
*/
function sloodle_tracker_generate_opensim_instance_name($cmid, $avid)
{
    return "opensim_instance_{$cmid}_{$avid}_".date('Ymd_His');
}


/**
Allocate a port for the named OpenSim instance.
This only stores the allocation in the database -- it does not modify the OpenSim configuration.
@param string $name The name of the OpenSim instance.
@param string $avuuid The UUID of the avatar this instance is created for.
@return int|bool Returns the allocate port number if successful, false if there were no spare ports, or -1 if the instance was already allocated a port.
*/
function sloodle_tracker_allocate_opensim_port($name, $avuuid)
{
    global $CFG;
    
    // Does the instance name already exist?
    if (get_record('sloodle_opensim_instance', 'name', $name)) return -1;
    // Get all port numbers which have already been allocated
    $ports = get_records_select('sloodle_opensim_instance', '1', 'port', 'port');
    if (!$ports) $ports = array();
    
    // Get our reserved port range
    $minPort = (int)$CFG->sloodle_tracker_opensim_port_min;
    $maxPort = (int)$CFG->sloodle_tracker_opensim_port_max;
    if ($minPort > $maxPort)
    {
        $temp = $minPort;
        $minPort = $maxPort;
        $maxPort = $temp;
    }
    if ($minPort < 0) error('SLOODLE Tracker misconfigured -- port numbers cannot be negative.');

    // Run through each allocated port until a gap is found
    $allocPort = -1; // This will contain our allocated port
    $lastAllocated = $minPort - 1; // The most recent port we've seen that was allocated - this allows us to identify gaps
    foreach ($ports as $portRec)
    {
        $curPort = (int)$portRec->port;
        // Skip any ports which are below the minimum
        if ($curPort < $minPort) continue;
        // Stop searching if the previously seen port was above the maximum reserved port
        if ($lastAllocated > $maxPort) break;
        
        // Has there been a gap between this port and the last one we saw that was allocated?
        if ($curPort > ($lastAllocated + 1))
        {
            // Yes - we can assign the next port after the last allocated one
            $allocPort = $lastAllocated + 1;
            break;
        }
        
        // Move on to the next port
        $lastAllocated = $curPort;
    }
    
    // If a free port wasn't passed then it's possible that there are more available past the end of the current allocations
    if ($allocPort < 0 && $lastAllocated < $maxPort) $allocPort = $lastAllocated + 1;
    
    if ($allocPort >= 0)
    {
    	// Add the allocation to the database
    	$rec = new stdClass();
    	$rec->avuuid = $avuuid;
    	$rec->name = $name;
    	$rec->port = $allocPort;
    	$rec->lastActive = time();
    	insert_record('sloodle_opensim_instance', $rec);
    	sloodle_debug("Allocated port {$allocPort} to OpenSim instance \"{$name}\"");
    	return $allocPort;
   	}
    return false;
}




/**
Create a new OpenSim instance based on the given data.
This only copies template data (files and database) and sets up user data. It does NOT change the configuration files.
There is no return value -- the script will terminate with an error message if something fails.
@param string $template The name of the template to base this instance on
@param string $instance The name of the instance to create
@param string $avname First and Second name of the avatar who will be using this instance
@param string $avuuid UUID of the avatar who will be using this instance
@param string $password The password to assign for the avatar login
*/
function sloodle_tracker_create_opensim_instance($template, $instance, $avname, $avuuid, $password)
{
    global $CFG;
    
    // Make sure the template and instance names have been specified
    if (empty($template)) error('Blank template name supplied to OpenSim instance creation function.');
    if (empty($instance)) error('Blank instance name supplied to OpenSim instance creation function.');
    // Make absolutely sure that the file paths are present -- we don't want to screw this up :)
    if (empty($CFG->sloodle_tracker_opensim_templates_folder)) error('OpenSim templates folder is not specified in SLOODLE Tracker configuration.');
    if (empty($CFG->sloodle_tracker_opensim_instances_folder)) error('OpenSim instances folder is not specified in SLOODLE Tracker configuration.');
    
    // Malicious code-checking -- ensure the template and instance names only contain alphanumeric characters and underscores
    if (preg_match("/[^a-zA-Z0-9_]/", $template)) error('Invalid characters found in template name');
    if (preg_match("/[^a-zA-Z0-9_]/", $instance)) error('Invalid characters found in instance name');
    
    
    // FILE COPYING //
    
    // Construct the source and destination paths
    $fileSrc = $CFG->sloodle_tracker_opensim_templates_folder.'/'.$template;
    $fileDest = $CFG->sloodle_tracker_opensim_instances_folder.'/'.$instance;
    
    // Check to see if the destination folder already exists
    if (is_dir($fileDest)) error("OpenSim instance folder \"{$instance}\" already exists.");
    
    // Escape the arguments to ensure they are safe for the command line
    $fileSrcArg = escapeshellarg($fileSrc);
    $fileDestArg = escapeshellarg($fileDest);
    
    // Attempt to do the copying
    sloodle_debug("Copying OpenSim template from \"{$fileSrc}\" to instance at \"{$fileDest}\"");
    if (sloodle_tracker_platform_is_windows())
    {
        exec("xcopy {$fileSrcArg} {$fileDestArg} /e /i > nul");
    } else {
        exec("cp -R {$fileSrcArg} {$fileDestArg}");
    }

    // Check to see if the directory exists
    if (!is_dir($fileDest)) error('File copying failed, or PHP does not have access to the required location.');
    
    
    // DATABASE COPYING //
    
    // Attempt a connection to the OpenSim database
    $osDBlink = mysql_connect($CFG->sloodle_tracker_opensim_db_host, $CFG->sloodle_tracker_opensim_db_user, $CFG->sloodle_tracker_opensim_db_password);
    sloodle_debug("Copying database {$template} to {$instance}");
    
    // Attempt to create a new database
    if (!mysql_query("CREATE DATABASE {$instance}", $osDBlink)) error('Failed to create OpenSim instance database. Please ensure the database user account has CREATE permission.');
    
    // Read the list of tables from the template
    $templateTables = array();
    $result = mysql_query("SHOW TABLES FROM `{$template}`", $osDBlink);
    if (!$result) error("Failed to query template database for table list.");
    while (($row = mysql_fetch_row($result)) != null)
    {
        $templateTables[] = $row[0];
    }
    
    // Copy the table structure and data into the instance database
    foreach ($templateTables as $table)
    {
        $query = "  CREATE TABLE `{$instance}`.`{$table}`
                    LIKE `{$template}`.`{$table}`";
                    
        if (!mysql_query($query, $osDBlink)) error("Failed to copy table structure from template database.");
        
        $query = "  INSERT INTO `{$instance}`.`{$table}`
                    SELECT *
                    FROM `{$template}`.`{$table}`";
                    
        if (!mysql_query($query, $osDBlink)) error("Failed to copy table data from template database.");
    }
    
    
    // Write the avatar details into the instance database
	list($avname1,$avname2) = split(" ",$avname,2);
    $query = "UPDATE `{$instance}`.`useraccounts` SET `PrincipalID` = '{$avuuid}', `FirstName` = '{$avname1}', `LastName` = '{$avname2}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into instance user accounts table.");
    
    $query = "UPDATE `{$instance}`.`auth` SET `UUID` = '{$avuuid}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into instance auth table.");
    
    $query = "UPDATE `{$instance}`.`inventoryfolders` SET `agentID` = '{$avuuid}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into instance inventory table.");
    
    // Close the database connection
    mysql_close($osDBlink);
}


/**
Delete the named OpenSim instance files and database tables.
@param string $instance Name of the instance to delete
@return int 1 if successful, -1 if database could not be dropped, -2 if files could not be deleted, or -3 for both errors. Will return -100 if the instance name was invalid.
*/
function sloodle_tracker_delete_opensim_instance($instance)
{
	global $CFG;

	// Make sure it's a valid instance name (this stops people deleting other folders or databases)
	if (preg_match("/[^a-zA-Z0-9_]/", $instance)) return -100;
	
	$filesDeleted = false;
	$dbDeleted = false;
	
	// Connect to the OpenSim database
	if (!empty($CFG->sloodle_tracker_opensim_db_host) && !empty($CFG->sloodle_tracker_opensim_db_user) && !empty($CFG->sloodle_tracker_opensim_db_password))
	{
		$osDBlink = mysql_connect($CFG->sloodle_tracker_opensim_db_host, $CFG->sloodle_tracker_opensim_db_user, $CFG->sloodle_tracker_opensim_db_password);
		if ($osDBlink)
		{
			// Attempt to drop the entire database
			$result = mysql_query("DROP DATABASE `{$instance}`", $osDBlink);
			if ($result) $dbDeleted = true;
			mysql_close($osDBlink);
		}
	}
	
	// Attempt to delete the entire instance folder
	if (!empty($CFG->sloodle_tracker_opensim_instances_folder))
	{
		$instance_dir = $CFG->sloodle_tracker_opensim_instances_folder.'/'.$instance;
		$instance_dir_arg = escapeshellarg($instance_dir);
		if (is_dir($instance_dir))
		{
			// Use platform-specific shell commands to delete the instance folder
			if (sloodle_tracker_platform_is_windows())
			{
				exec("deltree /Y {$instance_dir_arg}");
			} else {
				exec("rm -Rf {$instance_dir_arg}");
			}
			
			$filesDeleted = true;
		}
	}
	
	// Make sure any allocated ports are deleted
	delete_records('sloodle_opensim_instance', 'name', $instance);
	
	if (!$filesDeleted)
	{
		if (!$dbDeleted)
		{
			sloodle_debug("Failed to delete files and DB");
			return -3; // Deletion failed for both
		}
		sloodle_debug("Failed to delete files");
		return -2; // Deletion just failed for files
	} else {
		if (!$dbDeleted)
		{
			sloodle_debug("Failed to delete db");
			return -1; // Deletion just failed for db
		}
	}
	sloodle_debug("Deletion successful");
	return 1; // Deletion worked for both
}


/**
Configure the named OpenSim instance to use a specific port and database.
@param string $instance Name of the OpenSim instance to configure. This is also the name of the database to use.
@param int $port The port number allocated for this instance
*/
function sloodle_tracker_configure_opensim_instance($instance, $port)
{
	global $CFG;
	@ini_set('auto_detect_line_endings', 1);
	
	// Make sure there are no malicious characters in the instance name
	if (preg_match("/[^a-zA-Z0-9_]/", $instance)) error('Invalid character(s) in instance name');
	$instance_dir = $CFG->sloodle_tracker_opensim_instances_folder.'/'.$instance;

// // Regions.ini // //
    // Open the existing region file for reading, and an output file for writing
    sloodle_debug("Updating configuration file: ".$instance_dir.'/bin/Regions/Regions.ini');
    $fileIn = fopen($instance_dir.'/bin/Regions/Regions.ini', 'rt');
    if (!$fileIn) error("Failed to open regions file for configuration input.");
    $fileOut = fopen($instance_dir.'/bin/Regions/Regions-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new regions file for output.");
    }
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual
    	
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "InternalAddress") !== false) $lineOut = "InternalAddress = 0.0.0.0\n";
			else if (strpos($lineIn, "InternalPort") !== false) $lineOut = "InternalPort = {$port}\n";
			else if (strpos($lineIn, "ExternalHostName") !== false) $lineOut = "ExternalHostName  = {$CFG->sloodle_tracker_opensim_address}\n";
			else $lineOut = $lineIn;
    	}
    	
    	fwrite($fileOut, $lineOut);
    }
    
    fclose($fileIn);
    fclose($fileOut);
    
    // Delete our original file, and move our new one into its place
    unlink($instance_dir.'/bin/Regions/Regions.ini');
    rename($instance_dir.'/bin/Regions/Regions-new.ini', $instance_dir.'/bin/Regions/Regions.ini');
    
// // OpenSim.ini // //
	
	// Open the existing region file for reading, and an output file for writing
	sloodle_debug("Updating configuration file at: ".$instance_dir.'/bin/OpenSim.ini');
    $fileIn = fopen($instance_dir.'/bin/OpenSim.ini', 'rt');
    if (!$fileIn) error("Failed to open OpenSim.ini file for configuration input.");
    $fileOut = fopen($instance_dir.'/bin/OpenSim-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new OpenSim.ini file for output.");
    }
    
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual
    	
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "http_listener_port") !== false) $lineOut = "http_listener_port = {$port}\n";
			else if (strpos($lineIn, "freeswitch_service_port") !== false) $lineOut = "freeswitch_service_port = {$port}\n";
			else if (strpos($lineIn, "storage_connection_string") !== false) $lineOut = "storage_connection_string=\"Data Source={$CFG->sloodle_tracker_opensim_db_host};Database={$instance};User ID={$CFG->sloodle_tracker_opensim_db_user};Password={$CFG->sloodle_tracker_opensim_db_password};\"\n";
			else $lineOut = $lineIn;
    	}
    	
    	fwrite($fileOut, $lineOut);
    }
    
    fclose($fileIn);
    fclose($fileOut);
    
    // Delete our original file, and move our new one into its place
    unlink($instance_dir.'/bin/OpenSim.ini');
    rename($instance_dir.'/bin/OpenSim-new.ini', $instance_dir.'/bin/OpenSim.ini');
    
// // StandaloneCommon.ini // //
	
	// Open the existing region file for reading, and an output file for writing
	sloodle_debug("Updating configuration file at: ".$instance_dir.'/bin/config-include/StandaloneCommon.ini');
    $fileIn = fopen($instance_dir.'/bin/config-include/StandaloneCommon.ini', 'rt');
    if (!$fileIn) error("Failed to open StandaloneCommon.ini file for configuration input.");
    $fileOut = fopen($instance_dir.'/bin/config-include/StandaloneCommon-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new StandaloneCommon.ini file for output.");
    }
    
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual
    	
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "ConnectionString") !== false) $lineOut = "ConnectionString = \"Data Source={$CFG->sloodle_tracker_opensim_db_host};Database={$instance};User ID={$CFG->sloodle_tracker_opensim_db_user};Password={$CFG->sloodle_tracker_opensim_db_password};\"\n";
			else $lineOut = $lineIn;
    	}
    	
    	fwrite($fileOut, $lineOut);
    }
    
    fclose($fileIn);
    fclose($fileOut);
    
    // Delete our original file, and move our new one into its place
    unlink($instance_dir.'/bin/config-include/StandaloneCommon.ini');
    rename($instance_dir.'/bin/config-include/StandaloneCommon-new.ini', $instance_dir.'/bin/config-include/StandaloneCommon.ini');
    
}


/**
Start the named instance of OpenSim running.
@param string $instance The name of the instance to start running. This lets us find the executable.
@return bool True if successful or false otherwise
*/
function sloodle_tracker_launch_opensim_instance($instance)
{
	global $CFG;
	// Make sure the instance name is OK
	if (preg_match("/[^a-zA-Z0-9_]/", $instance)) return false;
	// Make sure we can see an executable in the expected location
	$execPath = $CFG->sloodle_tracker_opensim_instances_folder."/{$instance}/bin/opensim.exe";
	$execPathArg = escapeshellarg($execPath);
	if (!is_file($execPath))
	{
		sloodle_debug("No file found at expected executable path: {$execPath}");
		return false;
	}
	
	// Attempt to run it
	if (sloodle_tracker_platform_is_windows())
	{
		$folderName = str_replace('/', "\\", dirname($execPath));
		sloodle_debug("Launching OpenSim instance with command: start /D{$folderName} {$execPathArg}");
		pclose(popen("\"start_opensim.bat\" ".$folderName, "r"));
		//shell_exec("start /D{$folderName} {$execPathArg}");
	} else {
		sloodle_debug("Launching OpenSim instance with command: nohup mono {$execPathArg} 2> /dev/null & echo $!");
		shell_exec("nohup {$execPathArg} 2> /dev/null & echo $!");
	}
	
	return true;
}


/**
Create a new OpenSim template based on the given data.
This only copies template data (files and database) and sets up user data. It does NOT change the configuration files.
There is no return value -- the script will terminate with an error message if something fails.
@param string $template The name of the template to create
@param string $avname First and Second name of the avatar who will be using this instance
@param string $avuuid UUID of the avatar who will be using this instance
@param string $password The password to assign for the avatar login
*/
function sloodle_tracker_create_opensim_template($template, $avname, $avuuid, $password)
{
    global $CFG;
    // Make sure the template and instance names have been specified
    if (empty($template)) error('Blank template name supplied to OpenSim instance creation function.');
    // Make absolutely sure that the file paths are present -- we don't want to screw this up :)
    if (empty($CFG->sloodle_tracker_opensim_templates_folder)) error('OpenSim templates folder is not specified in SLOODLE Tracker configuration.');
    // Malicious code-checking -- ensure the template and instance names only contain alphanumeric characters and underscores
    if (preg_match("/[^a-zA-Z0-9_]/", $template)) error('Invalid characters found in template name');
	
    // FILE COPYING //
    // Construct the source and destination paths
    $fileSrc = $CFG->sloodle_tracker_main_opensim_installation_folder.'/opensim';
	$fileDest = $CFG->sloodle_tracker_opensim_templates_folder.'/'.$template;
    // Check to see if the destination folder already exists
    if (is_dir($fileDest)) error("OpenSim template folder \"{$template}\" already exists.");
    // Escape the arguments to ensure they are safe for the command line
    $fileSrcArg = escapeshellarg($fileSrc);
	$fileDestArg = escapeshellarg($fileDest);
    // Attempt to do the copying
    sloodle_debug("Copying main OpenSim installation from \"{$fileSrc}\" to template at \"{$fileDest}\"");
    if (sloodle_tracker_platform_is_windows())
    {
        exec("xcopy {$fileSrcArg} {$fileDestArg} /e /i > nul");
    } else {
        exec("cp -R {$fileSrcArg} {$fileDestArg}");
    }
    // Check to see if the directory exists
    if (!is_dir($fileDest)) error('File copying failed, or PHP does not have access to the required location.');
	
    // DATABASE COPYING //
    // Attempt a connection to the OpenSim database
	$databaseName = $CFG->sloodle_tracker_main_opensim_db;
    $osDBlink = mysql_connect($CFG->sloodle_tracker_opensim_db_host, $CFG->sloodle_tracker_opensim_db_user, $CFG->sloodle_tracker_opensim_db_password);
    sloodle_debug("Copying database {$databaseName} to {$template}");
    // Attempt to create a new database
    if (!mysql_query("CREATE DATABASE {$template}", $osDBlink)) error('Failed to create OpenSim template database. Please ensure the database user account has CREATE permission.');
    // Read the list of tables from the template
    $opensimTables = array();
    $result = mysql_query("SHOW TABLES FROM `{$databaseName}`", $osDBlink);
    if (!$result) error("Failed to query main database for table list.");
    while (($row = mysql_fetch_row($result)) != null)
    {
        $opensimTables[] = $row[0];
    }
    // Copy the table structure and data into the instance database
    foreach ($opensimTables as $table)
    {
        $query = "  CREATE TABLE `{$template}`.`{$table}` LIKE `{$databaseName}`.`{$table}`";
        if (!mysql_query($query, $osDBlink)) error("Failed to copy table structure from main database.");
        $query = "  INSERT INTO `{$template}`.`{$table}` SELECT * FROM `{$databaseName}`.`{$table}`";
        if (!mysql_query($query, $osDBlink)) error("Failed to copy table data from main database.");
    }
    // Write the avatar details into the instance database
	list($avname1,$avname2) = split(" ",$avname,2);
    $query = "UPDATE `{$template}`.`useraccounts` SET `PrincipalID` = '{$avuuid}', `FirstName` = '{$avname1}', `LastName` = '{$avname2}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into template user accounts table.");
    $query = "UPDATE `{$template}`.`auth` SET `UUID` = '{$avuuid}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into template auth table.");
    $query = "UPDATE `{$template}`.`inventoryfolders` SET `agentID` = '{$avuuid}' WHERE 1";
    if (!mysql_query($query, $osDBlink)) error("Failed to write avatar details into template inventory table.");
    // Close the database connection
    mysql_close($osDBlink);
}


/**
Configure the named OpenSim template to use a specific port and database.
@param string $template Name of the OpenSim template to configure. This is also the name of the database to use.
@param int $port The port number allocated for this instance
*/
function sloodle_tracker_configure_opensim_template($template, $port)
{
	global $CFG;
	@ini_set('auto_detect_line_endings', 1);
	
	// Make sure there are no malicious characters in the instance name
	if (preg_match("/[^a-zA-Z0-9_]/", $template)) error('Invalid character(s) in template name');
	$template_dir = $CFG->sloodle_tracker_opensim_templates_folder.'/'.$template;

// // Regions.ini // //
    // Open the existing region file for reading, and an output file for writing
    sloodle_debug("Updating configuration file: ".$template_dir.'/bin/Regions/Regions.ini');
    $fileIn = fopen($template_dir.'/bin/Regions/Regions.ini', 'rt');
    if (!$fileIn) error("Failed to open regions file for configuration input.");
    $fileOut = fopen($template_dir.'/bin/Regions/Regions-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new regions file for output.");
    }
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "InternalAddress") !== false) $lineOut = "InternalAddress = 0.0.0.0\n";
			else if (strpos($lineIn, "InternalPort") !== false) $lineOut = "InternalPort = {$port}\n";
			else if (strpos($lineIn, "ExternalHostName") !== false) $lineOut = "ExternalHostName  = {$CFG->sloodle_tracker_opensim_address}\n";
			else $lineOut = $lineIn;
    	}
    	fwrite($fileOut, $lineOut);
    }
    fclose($fileIn);
    fclose($fileOut);
    // Delete our original file, and move our new one into its place
    unlink($template_dir.'/bin/Regions/Regions.ini');
    rename($template_dir.'/bin/Regions/Regions-new.ini', $template_dir.'/bin/Regions/Regions.ini');
    
// // OpenSim.ini // //
	// Open the existing region file for reading, and an output file for writing
	sloodle_debug("Updating configuration file at: ".$template_dir.'/bin/OpenSim.ini');
    $fileIn = fopen($template_dir.'/bin/OpenSim.ini', 'rt');
    if (!$fileIn) error("Failed to open OpenSim.ini file for configuration input.");
    $fileOut = fopen($template_dir.'/bin/OpenSim-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new OpenSim.ini file for output.");
    }
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual	
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "http_listener_port") !== false) $lineOut = "http_listener_port = {$port}\n";
			else if (strpos($lineIn, "freeswitch_service_port") !== false) $lineOut = "freeswitch_service_port = {$port}\n";
			else if (strpos($lineIn, "storage_connection_string") !== false) $lineOut = "storage_connection_string=\"Data Source={$CFG->sloodle_tracker_opensim_db_host};Database={$template};User ID={$CFG->sloodle_tracker_opensim_db_user};Password={$CFG->sloodle_tracker_opensim_db_password};\"\n";
			else $lineOut = $lineIn;
    	}
    	fwrite($fileOut, $lineOut);
    }
    fclose($fileIn);
    fclose($fileOut);
    // Delete our original file, and move our new one into its place
    unlink($template_dir.'/bin/OpenSim.ini');
    rename($template_dir.'/bin/OpenSim-new.ini', $template_dir.'/bin/OpenSim.ini');
    
// // StandaloneCommon.ini // //
	// Open the existing region file for reading, and an output file for writing
	sloodle_debug("Updating configuration file at: ".$template_dir.'/bin/config-include/StandaloneCommon.ini');
    $fileIn = fopen($template_dir.'/bin/config-include/StandaloneCommon.ini', 'rt');
    if (!$fileIn) error("Failed to open StandaloneCommon.ini file for configuration input.");
    $fileOut = fopen($template_dir.'/bin/config-include/StandaloneCommon-new.ini', 'wt');
    if (!$fileOut)
    {
    	fclose($fileIn);
    	error("Failed to open new StandaloneCommon.ini file for output.");
    }
    // Read/write the file line-by-line
    $lineIn = "";
    while (!feof($fileIn))
    {
    	$lineIn = fgets($fileIn, 4096); // Potential bug - if a line is longer than this then we'll need a buffered approach. Also potential problems in the event that line separators are unusual
    	// Ignore any line that is empty or starts with a semi-colon
    	$lineInTrimmed = trim($lineIn);
    	if ($lineInTrimmed == '' || $lineInTrimmed[0] == ';')
    	{
    		$lineOut = $lineIn;
    	} else {    	
			// If this is a line of configuration data that we are looking for then modify it - otherwise pass it straight through
			if (strpos($lineIn, "ConnectionString") !== false) $lineOut = "ConnectionString = \"Data Source={$CFG->sloodle_tracker_opensim_db_host};Database={$template};User ID={$CFG->sloodle_tracker_opensim_db_user};Password={$CFG->sloodle_tracker_opensim_db_password};\"\n";
			else $lineOut = $lineIn;
    	}
    	fwrite($fileOut, $lineOut);
    }
    fclose($fileIn);
    fclose($fileOut);
    // Delete our original file, and move our new one into its place
    unlink($template_dir.'/bin/config-include/StandaloneCommon.ini');
    rename($template_dir.'/bin/config-include/StandaloneCommon-new.ini', $template_dir.'/bin/config-include/StandaloneCommon.ini');
}

/**
Start the named template of OpenSim running.
@param string $template The name of the template to start running. This lets us find the executable.
@return bool True if successful or false otherwise
*/
function sloodle_tracker_launch_opensim_template($template)
{
	global $CFG;
	// Make sure the instance name is OK
	if (preg_match("/[^a-zA-Z0-9_]/", $template)) return false;
	// Make sure we can see an executable in the expected location
	$execPath = $CFG->sloodle_tracker_opensim_templates_folder."/{$template}/bin/opensim.exe";
	$execPathArg = escapeshellarg($execPath);
	if (!is_file($execPath))
	{
		sloodle_debug("No file found at expected executable path: {$execPath}");
		return false;
	}
	// Attempt to run it
	if (sloodle_tracker_platform_is_windows())
	{
		$folderName = str_replace('/', "\\", dirname($execPath));
		sloodle_debug("Launching OpenSim template with command: start /D{$folderName} {$execPathArg}");
		pclose(popen("\"start_opensim.bat\" ".$folderName, "r"));
		//shell_exec("start /D{$folderName} {$execPathArg}");
	} else {
		sloodle_debug("Launching OpenSim template with command: nohup mono {$execPathArg} 2> /dev/null & echo $!");
		shell_exec("nohup {$execPathArg} 2> /dev/null & echo $!");
	}
	return true;
}

/**
Start the process to create a new template.
@param string $template_name The name of the template to create.
@param class $user A class containing all data of the sloodle user
*/
function create_new_opensim_template($template_name,$user)
{
 // Attempt to fetch the user's avatar data
 //echo "user: ".$user->id;
 $avdata = get_record('sloodle_users', 'userid', $user->id);
 if (!$avdata)
 {
    // No avatar data already -- create some.
    // No point re-creating the avatar data all the time in the Moodle database.
    $avdata = new stdClass();
    $avdata->userid = $user->id;
    $avdata->avname = $user->firstname.' '.$user->lastname;
    $avdata->uuid = sloodle_tracker_generate_unique_avatar_uuid();
    $avdata->lastactive = time();
    $avdata->id = insert_record('sloodle_users', $avdata);
    if (!$avdata) error(get_string('failedcreatesloodleuser','sloodle'));
 }
 // Get the port for our new template
 $port = sloodle_tracker_get_opensim_template_port($template_name);
 if ($port == -1)
 {
    error(get_string('tracker:instancealreadyallocatedport', 'sloodle'));
 }
 else if ($port === false)
 {
    error(get_string('tracker:noportsavailable', 'sloodle'));
 }
 // Create and configure the new template
 sloodle_tracker_create_opensim_template($template_name, $avdata->avname, $avdata->uuid, 'password');
 sloodle_tracker_configure_opensim_template($template_name, $port);
}

/**
Launch a OpenSim template.
@param string $template_name The name of the template to create.
*/
function launch_opensim_template($template_name)
{ 
 // Run opensim as a background task
 if (!sloodle_tracker_launch_opensim_template($template_name))
 {
	error("Unable to launch OpenSim template.");
 }
}

/**
Get the port for the opensim templates.
@param string $name The name of the OpenSim template.
@return int|bool Returns the allocate port number if successful, false if there were no spare ports, or -1 if the instance was already allocated a port.
*/
function sloodle_tracker_get_opensim_template_port($name)
{
    global $CFG;
    // Get the port reserved for opensim templates
    $port = (int)$CFG->sloodle_tracker_opensim_port_template;
    sloodle_debug("Allocated port {$port} to OpenSim template \"{$name}\"");
    return $port;
}
?>