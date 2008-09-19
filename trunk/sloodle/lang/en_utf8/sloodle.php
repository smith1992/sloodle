<?php
/**
* This is the English language file for Sloodle.
* It is included automatically by the Moodle framework.
* Retrieve strings using the Moodle get_string or print_string functions.
* @package sloodlelang
*/

$string['accesslevel'] = 'Access Level';
$string['accesslevel:public'] = 'Public';
$string['accesslevel:owner'] = 'Owner';
$string['accesslevel:group'] = 'Group';
$string['accesslevel:course'] = 'Course';
$string['accesslevel:site'] = 'Site';
$string['accesslevel:staff'] = 'Staff';

$string['accesslevelobject'] = 'Object Access Level';
$string['accesslevelobject:desc'] = 'This determines who may access the object in-world';
$string['accesslevelobject:use'] = 'Use object';
$string['accesslevelobject:control'] = 'Control object';

$string['accesslevelserver'] = 'Server Access Level';
$string['accesslevelserver:desc'] = 'This determines who may use the server resource';

$string['activeobjects'] = 'Active objects';
$string['activeobjectlifetime'] = 'Active object lifetime (days)';
$string['activeobjectlifetime:info'] = 'The number of days before which an active object will expire if not used.';
$string['alreadyauthenticated'] = 'A Second Life avatar has already been linked and authenticated for your Moodle account.';

$string['allocated'] = 'Allocated';
$string['allentries'] = 'All Sloodle Entries';
$string['allentries:info'] = 'This lists all Sloodle user entries for the entire site. These may be avatars or LoginZone allocations, and may or may not be linked to a Moodle account.';
$string['allowguests'] = 'Allow guests to use the tool';
$string['allowguests:note'] = 'Does not apply if auto-registration and auto-enrolment are enabled.';
$string['allowautodeactivation'] = 'Allow auto-deactivation';

$string['authorizingfor'] = 'Authorizing for: ';
$string['authorizedfor'] = 'Authorized for: ';
$string['authorizedobjects'] = 'Authorized Objects';

$string['autoenrol'] = 'User Auto-Enrolment';
$string['autoenrol:allowforsite'] = 'Allow auto-enrolment for this site';
$string['autoenrol:allowforcourse'] = 'Allow auto-enrolment for this course';
$string['autoenrol:courseallows'] = 'This course allows auto-enrolment';
$string['autoenrol:coursedisallows'] = 'This course does not allow auto-enrolment';
$string['autoenrol:disabled'] = 'Auto-enrolment is disabled on this site';

$string['autoreg'] = 'User Auto-Registration';
$string['autoreg:allowforsite'] = 'Allow auto-registration for this site';
$string['autoreg:allowforcourse'] = 'Allow auto-registration for this course';
$string['autoreg:courseallows'] = 'This course allows auto-registration';
$string['autoreg:coursedisallows'] = 'This course does not allow auto-registration';
$string['autoreg:disabled'] = 'Auto-registration is disabled on this site';

$string['avatarnotlinked'] = 'Your Second Life avatar is not yet linked to your Moodle account. Please use an authentication device, such as a Registration Booth or a LoginZone.';
$string['avatarname'] = 'Avatar name';
$string['avataruuid'] = 'Avatar UUID';
$string['avatarsearch'] = 'Avatar Search';

$string['backtosloodlesetup'] = 'Back to the Sloodle Setup page';

$string['cfgnotecard:header'] = 'Sloodle Configuration Notecard';
$string['cfgnotecard:generate'] = 'Generate Notecard';
$string['cfgnotecard:instructions'] = 'To configure a Sloodle object, edit or create a notecard called \'sloodle_config\' in its inventory, and add the text from the box below.';
$string['cfgnotecard:security'] = 'For security reasons, you should make sure that the \'sloodle_config\' notecard *and* the object itself cannot be modified by the next owner.';
$string['cfgnotecard:setnote'] = 'Note: if you configure a Sloodle Set, then it will automatically configure any other objects it creates (although you can still manually configure them if you want to).';

$string['changecourse'] = 'Change Course';
$string['choosecourse'] = 'Choose the course you want to use in Second Life.';
$string['clickchangeit'] = 'Click here to change it';
$string['clickhere'] = 'click here';
$string['clicktodeleteentry'] = 'Click here to delete this entry.';
$string['clicktoteleportanyway'] = 'Click here to teleport to the Sloodle site in-world anyway.';
$string['clicktovisitsloodle.org'] = 'Click here to visit Sloodle.org';
$string['configerror'] = 'Configuration Error';
$string['confirmobjectauth'] = 'Do you want to authorize this object?';
$string['confirmdelete'] = 'Are you sure?';
$string['confirmdeleteuserobjects'] = 'Are you sure you want to delete all these user objects?';
$string['controlaccess'] = 'You can control access to your courses by enabling or disabling the Sloodle Controllers';

//$string['controllerinfo'] = 'This page represents a Sloodle Controller. These are used to control communications between Second Life and Moodle, keeping the site secure. This page is primarily for use by teachers and administrators.';

$string['controllerinfo'] = 'This course is linked to learning activities in Second Life. This page is provided to allow students to check whether the Second Life interface is currently enabled, and for instructors to configure the interface.';

$string['courseconfig'] = 'Sloodle Course Configuration';
$string['courseconfig:info'] = 'On this page, you can configure the Sloodle settings which affect your entire course. However, some of the settings may be disabled on your Moodle site by an administrator.<br/><br/><b>Please note:</b> auto-registration and auto-enrolment are not suitable for all Moodle installations. Please read the documentation about each one before enabling them.';
$string['coursesettings'] = 'Course Settings';
$string['createnotecard'] = 'Create notecard';

$string['databasequeryfailed'] = 'Database query failed.';
$string['delete'] = 'Delete';
$string['deletecancelled'] = 'Deletion cancelled.';
$string['deleteselected'] = 'Delete Selected';
$string['deletionfailed'] = 'Deletion failed';
$string['deletionsuccessful'] = 'Deletion successful';
$string['disabled'] = 'Disabled';
$string['day'] = 'day';
$string['days'] = 'days';

$string['deleteuserobjects'] = 'Delete User Objects';
$string['deleteuserobjects:help'] = 'Click this button to delete all the user objects associated with the above avatar(s)';

$string['editcourse'] = 'Edit Sloodle Course Settings';
$string['enabled'] = 'Enabled';
$string['enteravatarname'] = 'Enter avatar name';
$string['error'] = 'Error';
$string['errorlinkedsloodleuser'] = 'An error occurred while trying to find Sloodle user data linked to your Moodle account.';
$string['error:expectedsearchorcourse'] = 'Expected search string or course ID.';
$string['expired'] = 'Expired';
$string['expiresin'] = 'expires in';

$string['failedupdate'] = 'Update failed.';
$string['failedcreatesloodleuser'] = 'Failed to create a Sloodle user account for you. Please try again.';
$string['failedaddinstance'] = 'Failed to add a new Sloodle module instance.';
$string['failedaddsecondarytable'] = 'Failed to add the secondary table for the Sloodle module instance.';
$string['failedcourseload'] = 'Failed to load Sloodle course data.';
$string['failedauth-trydifferent'] = 'Failed to authorise the object. Please try a different controller.';

$string['getnewloginzoneallocation'] = 'Click here to get a new LoginZone allocation.';
$string['generalconfiguration'] = 'General Configuration';

$string['help:primpassword'] = 'What is the Prim Password for?';
$string['help:userediting'] = 'What is the risk?';
$string['help:autoreg'] = 'What is auto-registration?';
$string['help:autoenrol'] = 'What is auto-enrolment?';
$string['help:versionnumbers'] = 'What do these numbers mean?';
$string['help:multipleentries'] = 'Why are there multiple entries? What does it mean?';
$string['hour'] = 'hour';
$string['hours'] = 'hours';

$string['ID'] = 'ID';
$string['idletimeoutseconds'] = 'Idle timeout (seconds)';
$string['invalidid'] = 'Invalid ID';
$string['invalidcourseid'] = 'Invalid course ID';
$string['isauthorized'] = 'Is Authorized?';
$string['insufficientpermission'] = 'You do not have sufficient permission';
$string['insufficientpermissiontoviewpage'] = 'You do not have sufficient permission to view this page.';

$string['lastactive'] = 'Last Sloodle Activity';
$string['lastupdated'] = 'Last Updated';
$string['lastused'] = 'Last Used';
$string['linkedtomoodleusernum'] = 'Moodle User #';
$string['listentoobjects'] = 'Listen to object chat';

$string['loginsecuritytokenfailed'] = 'Your login security token is not valid. Please try using the Registration Booth again, and ensure you followed the correct URL to reach this page.';

$string['loginzone'] = 'Sloodle LoginZone';
$string['loginzonedata'] = 'LoginZone Data';
$string['loginzoneposition'] = 'LoginZone Position?';
$string['loginzone:datamissing'] = 'Error! Some of the Login Zone data could not be found.';
$string['loginzone:mayneedrez'] = 'The LoginZone may need to be rezzed in-world.';
$string['loginzone:olddata'] = 'Warning! This LoginZone data has not been updated recently, so it may no longer work.';
$string['loginzone:alreadyregistered'] = 'There is already an avatar registered with your Moodle account. If you want to register another avatar, then please visit your Sloodle profile and delete your old avatar first.';
$string['loginzone:allocationfailed'] = 'Failed to allocate a Login Position for you. Please wait a few minutes and try again.';
$string['loginzone:allocationsucceeded'] = 'Successfully allocated a LoginZone.';
$string['loginzone:expirynote'] = 'Please note that your Login Position will expire in 15 minutes. If you do not manage to use it in this time, then you will need to return here to re-activate it.';
$string['loginzone:teleport'] = 'Click here to teleport to the Login Zone.';
$string['loginzone:newallocation'] = 'Generate new LoginZone position';
$string['loginzone:needallocation'] = 'You do not have a LoginZone allocation yet. Please click the button below to get one.';


$string['minute'] = 'minute';
$string['minutes'] = 'minutes';

$string['moduletype'] = 'Module Type';
$string['moduletype:controller'] = 'Sloodle Controller';
$string['moduletype:distributor'] = 'Distributor';

$string['modulename'] = 'Sloodle Module';
$string['modulenameplural'] = 'Sloodle Modules';
$string['modulenotfound'] = 'Sloodle module not found.';
$string['modulesetup'] = 'Module Setup';
$string['moduletypemismatch'] = 'Sloodle module type mismatch. You cannot change the Sloodle module type after it is created.';
$string['moduletypeunknown'] = 'Sloodle module type unknown.';
$string['moodleadminindex'] = 'Moodle administration index';
$string['moodleusernotfound'] = 'That Moodle user does not appear to exist. It may have been completely deleted from the database, or else you may have the wrong user ID.';
$string['moodleuserprofile'] = 'Moodle user profile';
$string['multipleentries'] = 'Warning: there are multiple Sloodle entries associated with this Moodle account.';

$string['month'] = 'month';
$string['months'] = 'months';

$string['name'] = 'Name';
$string['needadmin'] = 'You need administrator privileges to continue.';
$string['No'] = 'No';
$string['noobjectconfig'] = 'No additional configuration options for this object.';
$string['now'] = 'now';
$string['nodistributorinterface'] = 'No Distributor Interface';
$string['noguestaccess'] = 'Sorry, you cannot use guest login here.';
$string['nosloodleusers'] = 'No users registered with Sloodle';
$string['nodeletepermission'] = 'You do not have permission to delete this entry.';
$string['noentries'] = 'No entries found.';
$string['nouserdata'] = 'There is no user data to display.';
$string['nowenrol'] = 'Please continue to enrol in this course.';
$string['notenrolled'] = 'User not enrolled in this course.';
$string['numsloodleentries'] = '# Sloodle entries';
$string['numsettingsstored'] = 'Number of settings stored:';
$string['numobjects'] = 'Number of objects';
$string['numdeleted'] = 'Number deleted';
$string['numprims'] = 'Prim Count: $a';

$string['nochatrooms'] = 'There are no chatrooms available in this course.';
$string['nochoices'] = 'There are no choices available in this course.';
$string['noquizzes'] = 'There are no quizzes available in this course.';
$string['noglossaries'] = 'There are no glossaries available in this course.';
$string['nodistributors'] = 'There are no distributors available in this course.';
$string['nosloodleassignments'] = 'There are no Sloodle-compatible assignments available in this course.';

$string['object:accesschecker'] = 'Access Checker';
$string['object:accesscheckerdoor'] = 'Access Checker Door';
$string['object:chat'] = 'WebIntercom';
$string['object:choice'] = 'Choice';
$string['object:distributor'] = 'Vending Machine';
$string['object:enrolbooth'] = 'Enrolment Booth';
$string['object:glossary'] = 'MetaGloss';
$string['object:loginzone'] = 'LoginZone';
$string['object:primdrop'] = 'PrimDrop';
$string['object:pwreset'] = 'Password Reset';
$string['object:quiz'] = 'Quiz Chair';
$string['object:quiz_pile_on'] = 'Quiz Pile-On';
$string['object:regbooth'] = 'Registration Booth';
$string['object:set'] = 'Sloodle Set';

$string['Object'] = 'Object';
$string['objectdetails'] = 'Object Details';
$string['objectnotinstalled'] = 'Object not installed';
$string['objectconfig:header'] = 'Sloodle Object Configuration';
$string['objectconfig:body'] = 'You can choose to configure some Sloodle objects with a notecard instead of using the common web-based authorisation. It is less secure, as it involves the use of a single prim password for all objects, but it makes it quicker and easier to rez pre-configured objects from your inventory.';
$string['objectconfig:select'] = 'Select which object you would like to create a configuration notecard for from the list below. If multiple versions are available, then they are shown in the brackets -- only use the older versions if the main version does not work.';
$string['objectconfig:noobjects'] = 'There are no object configurations available.';
$string['objectconfig:noprimpassword'] = 'ERROR: The Prim Password has been disabled for this Controller. Please specify a Prim Password if you would like to use notecard configuration.';
$string['objectconfig:backtoform'] = 'Go back to the configuration form.';

$string['objectauth'] = 'Sloodle Object Authorization';
$string['objectauthalready'] = 'This object has already been authorized. If you want to re-authorize it, then please delete its authorization entry from your Sloodle Controller.';
$string['objectauthcancelled'] = 'You have cancelled the object authorization.';
$string['objectauthfailed'] = 'Object authorization has failed.';
$string['objectauthnocontrollers'] = 'There are no Sloodle Controllers on the site. Please create one on a course in order to authorise objects.';
$string['objectauthnopermission'] = 'You do not have the permission to authorise any objects. You may need to create a Sloodle Controller on your course(s).';
$string['objectauthnotfound'] = 'Object not found for authorization.';
$string['objectauthsuccessful'] = 'Object authorization has been successful.';
$string['objectconfiguration'] = 'Object Configuration';
$string['objectname'] = 'Object Name';
$string['objectuuid'] = 'Object UUID';
$string['objecttype'] = 'Object Type';

$string['or'] = 'or';

$string['postedfromsl'] = 'Posted from Second Life';
$string['pendingavatarnotfound'] = 'Could not locate a pending entry for your avatar. Perhaps you are already registered?';
$string['pendingallocations'] = 'Pending Allocations';
$string['pendingavatars'] = 'Pending Avatars';
//$string['pendingavatars:info'] = '';

$string['playsounds'] = 'Play sounds?';
$string['position'] = 'Position';

$string['primpass'] = 'Prim Password';
$string['primpass:invalidtype'] = 'Prim Password was an invalid type. Should be a string.';
$string['primpass:tooshort'] = 'Prim Password should be at least 5 digits long (or leave field blank to disable it).';
$string['primpass:toolong'] = 'Prim Password should be at most 9 digits long (or leave field blank to disable it).';
$string['primpass:numonly'] = 'Prim Password should only contain numeric digits (0 to 9).';
$string['primpass:error'] = 'Prim Password Error';
$string['primpass:updated'] = 'Prim Password updated';
$string['primpass:leadingzero'] = 'Prim Password should not start with a 0.';

$string['randomquestionorder'] = 'Randomize question order?';
$string['releasenum'] = 'Module release number';
$string['region'] = 'Region';
$string['refreshtimeseconds'] = 'Refresh time (seconds)';
$string['repeatquiz'] = 'Automatically repeat the quiz?';
$string['relativeresults'] = 'Show relative results?';

$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['secondarytablenotfound'] = 'Secondary Sloodle module table not found. Module instance may need to be created again.';

$string['searchaliases'] = 'Search Aliases';
$string['searchdefinitions'] = 'Search Definitions';
$string['selectchatroom'] = 'Select Chatroom';
$string['selectchoice'] = 'Select Choice';
$string['selectglossary'] = 'Select Glossary';
$string['selectdistributor'] = 'Select Distributor';
$string['selectassignment'] = 'Select Assignment';
$string['selectquiz'] = 'Select Quiz';
$string['selectobject'] = 'Select Object';
$string['selectuser'] = 'Select User';
$string['selectcontroller'] = 'Select Controller';
$string['sendobject'] = 'Send Object';
$string['setting'] = 'Settings';
$string['showavatarsonly'] = 'Only show accounts with Sloodle entries';
$string['showpartialmatches'] = 'Show Partial Matches';
$string['size'] = 'Size';

$string['sloodle'] = 'Sloodle';
$string['sloodlenotinstalled'] = 'Sloodle does not appear to be installed yet. Please use visit the Moodle administration index to finish Sloodle installation:';
$string['sloodlesetup'] = 'Sloodle Setup';
$string['sloodleversion'] = 'Sloodle Version';

$string['sloodle:staff'] = 'Sloodle Staff member';
$string['sloodle:objectauth'] = 'Authorise objects for Sloodle access';
$string['sloodle:userobjectauth'] = 'Authorise user objects for self';
$string['sloodle:uselayouts'] = 'Use classroom layout profiles';
$string['sloodle:editlayouts'] = 'Edit/delete classroom layout profiles';
$string['sloodle:registeravatar'] = 'Register own avatar';
$string['sloodle:distributeself'] = 'Distribute objects to own avatar';
$string['sloodle:distributeothers'] = 'Distribute objects to other avatars';

$string['sloodleobjectdistributor'] = 'Sloodle Object Distributor';
$string['sloodleobjectdistributor:nochannel'] = 'Distribution channel not available - Object not rezzed in-world?';
$string['sloodleobjectdistributor:reset'] = 'Check this to clear the cached Distributor data, including channel UUID and object names.';
$string['sloodleobjectdistributor:unknowncommand'] = 'Distributor command not recognised.';
$string['sloodleobjectdistributor:usernotfound'] = 'Unable to find requested user.';
$string['sloodleobjectdistributor:successful'] = 'Object distribution successful.';
$string['sloodleobjectdistributor:failed'] = 'Object distribution failed.';
$string['sloodleobjectdistributor:noobjects'] = 'No objects are currently available for distribution. The Sloodle Object Distributor may need to be given contents?';
$string['sloodleobjectdistributor:sendtomyavatar'] = 'Send to me';
$string['sloodleobjectdistributor:sendtocustomavatar'] = 'Send to custom avatar';
$string['sloodleobjectdistributor:sendtoanotheravatar'] = 'Send to another avatar';

$string['sloodleuserediting:allowteachers'] = 'Allow teachers to edit Sloodle user data';
$string['sloodleuserediting'] = 'Sloodle User Editing';
$string['sloodleuserprofile'] = 'Sloodle User Profile';
$string['sloodleuserprofiles'] = 'Sloodle User Profiles';
$string['specialpages'] = 'Special Pages';

$string['status'] = 'Status';
$string['storedlayouts'] = 'Stored Layouts';
$string['submit'] = 'Submit';

$string['timeago'] = '$a ago'; // $a = period of time, e.g. "3 weeks"

$string['unknown'] = 'unknown';
$string['unknownuser'] = 'unknown user';

$string['userlinkfailed'] = 'There was an error while trying to link your avatar to your Moodle account.';
$string['userlinksuccessful'] = 'Your avatar was successfully linked to your Moodle account. All Sloodle objects linked to this site should now recognise you automatically.';
$string['usersearch'] = 'User search';
$string['userobjects'] = 'User Objects';
$string['userobjectlifetime'] = 'User object lifetime (days)';
$string['userobjectlifetime:info'] = 'The number of days before which a user-centric object (such as the Toolbar) will expire if not used.';
$string['userobjectauth'] = 'Sloodle User Object Authorization';
$string['usedialogs'] = 'Use dialogs (instead of chat)?';

$string['uuid'] = 'UUID';

$string['viewpending'] = 'View pending avatars';
$string['viewall'] = 'View all Sloodle entries';
$string['viewmyavatar'] = 'View my avatar details';

$string['welcometosloodle'] = 'Welcome to Sloodle';
$string['week'] = 'week';
$string['weeks'] = 'weeks';

$string['xmlrpc:unexpectedresponse'] = 'Not getting the expected XMLRPC response. Is Second Life broken again?';
$string['xmlrpc:error'] = 'XMLRPC Error';
$string['xmlrpc:channel'] = 'XMLRPC Channel';

$string['year'] = 'year';
$string['years'] = 'years';
$string['Yes'] = 'Yes';

?>
