<?php
/**
* This is the English language file for Sloodle.
* It is included automatically by the Moodle framework.
* Retrieve strings using the Moodle get_string or print_string functions.
* @package sloodlelang
*/

$string['alreadyauthenticated'] = 'A Second Life avatar has already been linked and authenticated for your Moodle account.';

$string['allocated'] = 'Allocated';
$string['allentries'] = 'All Sloodle Entries';
$string['allentries:info'] = 'This lists all Sloodle user entries for the entire site. These may be avatars or LoginZone allocations, and may or may not be linked to a Moodle account.';

$string['autoreg'] = 'User Auto-Registration';
$string['autoreg:allowforsite'] = 'Allow auto-registration for this site';
$string['autoreg:allowforcourse'] = 'Allow auto-registration for this course';


$string['avatarnotfound'] = 'Your Second Life avatar could not be found in the database. Please try using the Registration Booth again, and ensure you followed the correct URL to reach this page.';
$string['avatarnotlinked'] = 'Your Second Life avatar is not yet linked to your Moodle account. Please use an authentication device, such as a Registration Booth or a LoginZone.';
$string['avatarname'] = 'Avatar name';
$string['avataruuid'] = 'Avatar UUID';

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
$string['createnotecard'] = 'Create notecard';

$string['databasequeryfailed'] = 'Database query failed.';
$string['delete'] = 'Delete';
$string['deletecancelled'] = 'Deletion cancelled.';
$string['deletionfailed'] = 'Deletion failed';
$string['deletionsuccessful'] = 'Deletion successful';
$string['disabled'] = 'Disabled';

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

$string['getnewloginzoneallocation'] = 'Click here to get a new LoginZone allocation.';

$string['help:primpassword'] = 'What is the Prim Password for?';
$string['help:userediting'] = 'What is the risk?';
$string['help:autoreg'] = 'What is auto-registration?';
$string['help:versionnumbers'] = 'What do these numbers mean?';
$string['help:multipleentries'] = 'Why are there multiple entries? What does it mean?';

$string['ID'] = 'ID';
$string['invalidid'] = 'Invalid ID';
$string['invalidcourseid'] = 'Invalid course ID';
$string['insufficientpermission'] = 'You do not have sufficient permission';
$string['insufficientpermissiontoviewpage'] = 'You do not have sufficient permission to view this page.';

$string['linkedtomoodleusernum'] = 'Moodle User #';

$string['loginsecuritytokenfailed'] = 'Your login security token is not valid. Please try using the Registration Booth again, and ensure you followed the correct URL to reach this page.';
$string['loginzoneposition'] = 'LoginZone Position?';
$string['loginzone:datamissing'] = 'Some of the Login Zone data could not be found.';
$string['loginzone:entry'] = 'Sloodle LoginZone Entry';
$string['loginzone:mayneedrerez'] = 'The Login Zone may need to be re-rezzed.';
$string['loginzone:useteleportlink'] = 'A Login Position has been allocated for you. Please use the following link to teleport to it:';
$string['loginzone:teleport'] = 'Click here to teleport to the Login Zone.';
$string['loginzone:expirynote'] = 'Please note that your Login Position will expire in 15 minutes. If you do not manage to use it in this time, then you will need to return here to re-activate it.';
$string['loginzone:allocationfailed'] = 'Failed to allocate a Login Position for you. Please wait a few minutes and try again.';
$string['loginzone:allocationerror'] = 'An error occurred while allocating a Login Position.';

$string['minute'] = 'minute';
$string['minutes'] = 'minutes';

$string['moduletype'] = 'Module Type';
$string['moduletype:controller'] = 'Sloodle Controller';
$string['moduletype:distributor'] = 'Distributor';

$string['modulename'] = 'Sloodle Module';
$string['modulenameplural'] = 'Sloodle Modules';
$string['modulenotfound'] = 'Sloodle module not found.';
$string['modulesetup'] = 'Module Setup';
$string['moduletype'] = 'Sloodle Module Type';
$string['moduletypemismatch'] = 'Sloodle module type mismatch. You cannot change the Sloodle module type after it is created.';
$string['moduletypeunknown'] = 'Sloodle module type unknown.';
$string['moodleadminindex'] = 'Moodle administration index';
$string['moodleusernotfound'] = 'That Moodle user does not appear to exist. It may have been completely deleted from the database, or else you may have the wrong user ID.';
$string['moodleuserprofile'] = 'Moodle user profile';
$string['multipleentries'] = 'Warning: there are multiple Sloodle entries associated with this Moodle account.';

$string['name'] = 'Name';
$string['needadmin'] = 'You need administrator privileges to continue.';
$string['No'] = 'No';
$string['noguestaccess'] = 'Sorry, you cannot use guest login here.';
$string['nosloodleusers'] = 'No users registered with Sloodle';
$string['nodeletepermission'] = 'You do not have permission to delete this entry.';
$string['noentries'] = 'No entries found.';
$string['nouserdata'] = 'There is no user data to display.';
$string['nowenrol'] = 'Please continue to enrol in this course.';
$string['notenrolled'] = 'User not enrolled in this course.';
$string['numsloodleentries'] = '# Sloodle entries';

$string['Object'] = 'Object';
$string['objectconfig:header'] = 'Sloodle Object Configuration';
$string['objectconfig:body'] = 'Some Sloodle objects will require a configuration notecard before you can use them with your Moodle installation. Click the following link to get the text for a configuration notecard:';

$string['objectauth'] = 'Sloodle Object Authorization';
$string['objectauthcancelled'] = 'You have cancelled the object authorization.';
$string['objectauthfailed'] = 'Object authorization has failed.';
$string['objectauthsent'] = 'Object authorization has been sent successfully.';
$string['objectname'] = 'Object Name';
$string['objectuuid'] = 'Object UUID';

$string['or'] = 'or';

$string['primpass'] = 'Prim Password';
$string['primpass:set'] = 'Set Prim Password Number';
$string['primpass:setdesc'] = 'The password should be a number which is between 5 and 9 digits long, and which does not start with a 0. Please note that if you change this password, you will need to update the configuration of all your Second Life objects which use it.';
$string['primpass:save'] = 'Save Prim Password';
$string['primpass:change'] = 'Change Prim Password';
$string['primpass:changedesc'] = 'The password should be a number which is between 5 and 9 digits long, and which does not start with a 0. Please note that if you change this password, you will need to update the scripts in all your Second Life objects that use it.';
$string['primpass:isset'] = 'Prim Password is set.';
$string['primpass:issetdesc'] = 'Your prim password will be automatically included in your LSL scripts.';
$string['primpass:errornotset'] = 'Error: Prim password isn\'t set, and I couldn\'t create one';

$string['primpass:invalidtype'] = 'Prim Password was an invalid type. Should be a string.';
$string['primpass:tooshort'] = 'Prim Password should be at least 5 digits long.';
$string['primpass:toolong'] = 'Prim Password should be at most 9 digits long.';
$string['primpass:numonly'] = 'Prim Password should only contain numeric digits (0 to 9).';
$string['primpass:error'] = 'Prim Password Error';
$string['primpass:updated'] = 'Prim Password updated';
$string['primpass:leadingzero'] = 'Prim Password should not start with a 0.';
$string['primpass:random'] = 'A random prim password has been automatically generated for you.';

$string['releasenum'] = 'Module release number';

$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['secondarytablenotfound'] = 'Secondary Sloodle module table not found. Module instance may need to be created again.';

$string['selectobject'] = 'Select Object';
$string['selectuser'] = 'Select User';
$string['sendobject'] = 'Send Object';
$string['showavatarsonly'] = 'Only show accounts with Sloodle entries';

$string['sloodle'] = 'Sloodle';
$string['sloodlenotinstalled'] = 'Sloodle does not appear to be installed yet. Please use visit the Moodle administration index to finish Sloodle installation:';
$string['sloodlesetup'] = 'Sloodle Setup';
$string['sloodleversion'] = 'Sloodle Version';

$string['sloodleobjectdistributor'] = 'Sloodle Object Distributor';
$string['sloodleobjectdistributor:nochannel'] = 'Distribution channel not available - Object not rezzed in-world?';
$string['sloodleobjectdistributor:unknowncommand'] = 'Distributor command not recognised.';
$string['sloodleobjectdistributor:usernotfound'] = 'Unable to find requested user.';
$string['sloodleobjectdistributor:successful'] = 'Object distribution successful.';
$string['sloodleobjectdistributor:failed'] = 'Object distribution failed.';
$string['sloodleobjectdistributor:noobjects'] = 'No objects are currently available for distribution. The Sloodle Object Distributor may need to be given contents?';
$string['sloodleobjectdistributor:sendtomyavatar'] = 'Send to me';
$string['sloodleobjectdistributor:sendtoanotheravatar'] = 'Send to another avatar';

$string['sloodleuserediting:allowteachers'] = 'Allow teachers to edit Sloodle user data';
$string['sloodleuserediting'] = 'Sloodle User Editing';
$string['sloodleuserprofile'] = 'Sloodle User Profile';
$string['sloodleuserprofiles'] = 'Sloodle User Profiles';
$string['specialpages'] = 'Special Pages';

$string['status'] = 'Status';
$string['submit'] = 'Submit';

$string['unknownuser'] = 'unknown user';
$string['unlinkedsloodleentries'] = 'Unlinked Sloodle User Entries';
$string['unlinkedsloodleentries:desc'] = 'These are entries in the Sloodle users table which are not linked to a particular Moodle account. When in-world registration tools are used, temporary entries like this are created until the user logs into Moodle to authenticate themselves. Only delete entries if they have been lingering for a long time, or you suspect them of causing problems.';

$string['userlinkfailed'] = 'There was an error while trying to link your avatar to your Moodle account.';
$string['userlinksuccessful'] = 'Your avatar was successfully linked to your Moodle account. All Sloodle objects linked to this site should now recognised you automatically.';
$string['usersearch'] = 'User search';

$string['uuid'] = 'UUID';

$string['viewunlinked'] = 'View unlinked Sloodle entries';
$string['viewall'] = 'View all Sloodle entries';

$string['welcometosloodle'] = 'Welcome to Sloodle';

$string['xmlrpc:unexpectedresponse'] = 'Not getting the expected XMLRPC response. Is Second Life broken again?';
$string['xmlrpc:error'] = 'XMLRPC Error';
$string['xmlrpc:channel'] = 'XMLRPC Channel';

$string['Yes'] = 'Yes';

?>
