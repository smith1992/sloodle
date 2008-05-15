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

$string['avatarnotfound'] = 'Your Second Life avatar could not be found in the database. Please try using the Registration Booth again, and ensure you followed the correct URL to reach this page.';
$string['avatarnotlinked'] = 'Your Second Life avatar is not yet linked to your Moodle account. Please use an authentication device, such as a Registration Booth or a LoginZone.';
$string['avatarname'] = 'Avatar name';
$string['avataruuid'] = 'Avatar UUID';
$string['avatarsearch'] = 'Avatar Search';

$string['backtosloodlesetup'] = 'Back to the Sloodle Setup page';

$string['cfgnotecard:header'] = 'Sloodle Configuration Notecard';
$string['cfgnotecard:choosecourse'] = 'Select which course you would like to configure your Sloodle objects to communicate with:';
$string['cfgnotecard:paste'] = 'Paste the following in the sloodle_config notecard in your Sloodle objects.'; //Deprecated
$string['cfgnotecard:generate'] = 'Generate notecard text';
$string['cfgnotecard:instructions'] = 'To configure a Sloodle object, edit or create a notecard called \'sloodle_config\' in its inventory, and add the text from the box below.';
$string['cfgnotecard:security'] = 'For security reasons, you should make sure that the \'sloodle_config\' notecard *and* the object itself cannot be modified by the next owner.';
$string['cfgnotecard:inworld'] = '* If you prefer to configure your object in-world, just delete or rename the sloodle_config notecard in the Sloodle Set object. It will ask your avatar for the appropriate settings.';
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
$string['controlaccess'] = 'You can control access to your courses by enabling or disabling the Sloodle Controllers';
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
$string['invalidid'] = 'Invalid ID';
$string['invalidcourseid'] = 'Invalid course ID';
$string['insufficientpermission'] = 'You do not have sufficient permission';
$string['insufficientpermissiontoviewpage'] = 'You do not have sufficient permission to view this page.';

$string['lastactive'] = 'Last Sloodle Activity';
$string['lastupdated'] = 'Last Updated';
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

$string['name'] = 'Name';
$string['needadmin'] = 'You need administrator privileges to continue.';
$string['No'] = 'No';
$string['noobjectconfig'] = 'No configuration options for this object.';
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

$string['nochatrooms'] = 'There are no chatrooms available in this course.';
$string['nodistributors'] = 'There are no distributors available in this course.';

$string['Object'] = 'Object';
$string['objectdetails'] = 'Object Details';
$string['objectconfig:header'] = 'Sloodle Object Configuration';
$string['objectconfig:body'] = 'Some Sloodle objects will require a configuration notecard before you can use them with your Moodle installation. Click the following link to get the text for a configuration notecard:';

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

$string['pendingallocations'] = 'Pending Allocations';
$string['pendingavatars'] = 'Pending Avatars';
//$string['pendingavatars:info'] = '';

$string['position'] = 'Position';

$string['primpass'] = 'Prim Password';
$string['primpass:invalidtype'] = 'Prim Password was an invalid type. Should be a string.';
$string['primpass:tooshort'] = 'Prim Password should be at least 5 digits long.';
$string['primpass:toolong'] = 'Prim Password should be at most 9 digits long.';
$string['primpass:numonly'] = 'Prim Password should only contain numeric digits (0 to 9).';
$string['primpass:error'] = 'Prim Password Error';
$string['primpass:updated'] = 'Prim Password updated';
$string['primpass:leadingzero'] = 'Prim Password should not start with a 0.';

$string['releasenum'] = 'Module release number';
$string['region'] = 'Region';
$string['refreshtimeseconds'] = 'Refresh time (seconds)';

$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['secondarytablenotfound'] = 'Secondary Sloodle module table not found. Module instance may need to be created again.';

$string['selectchatroom'] = 'Select Chatroom';
$string['selectdistributor'] = 'Select Distributor';
$string['selectobject'] = 'Select Object';
$string['selectuser'] = 'Select User';
$string['selectcontroller'] = 'Select Controller';
$string['sendobject'] = 'Send Object';
$string['setting'] = 'Settings';
$string['showavatarsonly'] = 'Only show accounts with Sloodle entries';
$string['size'] = 'Size';

$string['sloodle'] = 'Sloodle';
$string['sloodlenotinstalled'] = 'Sloodle does not appear to be installed yet. Please use visit the Moodle administration index to finish Sloodle installation:';
$string['sloodlesetup'] = 'Sloodle Setup';
$string['sloodleversion'] = 'Sloodle Version';

$string['sloodle:staff'] = 'Sloodle Staff member';
$string['sloodle:objectauth'] = 'Authorise objects for Sloodle access';
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

$string['unknown'] = 'unknown';
$string['unknownuser'] = 'unknown user';

$string['userlinkfailed'] = 'There was an error while trying to link your avatar to your Moodle account.';
$string['userlinksuccessful'] = 'Your avatar was successfully linked to your Moodle account. All Sloodle objects linked to this site should now recognised you automatically.';
$string['usersearch'] = 'User search';

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

$string['Yes'] = 'Yes';

?>
