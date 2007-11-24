CREATE TABLE prefix_sloodle
(
    `id` int(10) unsigned NOT NULL auto_increment,
    `course` int(10) unsigned NOT NULL default '0',
    `name` varchar(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    INDEX `course` (`course`)
) COMMENT='For future use. Defines Sloodle module instances.';
  
CREATE TABLE prefix_sloodle_users
( 
    `id` int(10) unsigned NOT NULL auto_increment,
    `userid` int(10) unsigned NOT NULL DEFAULT '0',
    `uuid` varchar(255) DEFAULT '',
    `avname` varchar(255) DEFAULT '',
    `loginposition` varchar(255) DEFAULT '',
    `loginpositionexpires` varchar(255) DEFAULT '',
    `loginpositionregion` varchar(255) DEFAULT '',
    `loginsecuritytoken` varchar(255) DEFAULT '',
    PRIMARY KEY (`id`),
    INDEX `userid` (`userid`),
    UNIQUE `uuid` (`uuid`)
) COMMENT='Associates Moodle user IDs with Second Life avatar UUIDs and names';
  
CREATE TABLE prefix_sloodle_active_object (
	`id` int(10) unsigned NOT NULL auto_increment,
    `sloodle_classroom_setup_profile_id` int(10) DEFAULT '0',
    `uuid` varchar(255) NOT NULL DEFAULT '',
    `name` varchar(255) NOT NULL DEFAULT '',
    `master_uuid` varchar(255) NOT NULL DEFAULT '',
    `authenticated_by_userid` varchar(255) NOT NULL DEFAULT '',
    `pwd` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) COMMENT='Keeps track of active in-world Sloodle objects';
  
CREATE TABLE prefix_sloodle_classroom_setup_profile (
	`id` int(10) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL DEFAULT '',
    `courseid` int(10) DEFAULT '0',
	PRIMARY KEY (`id`)
) COMMENT='Contains classroom setup profiles';
  
CREATE TABLE prefix_sloodle_classroom_setup_profile_entry (
	`id` int(10) unsigned NOT NULL auto_increment,
    `sloodle_classroom_setup_profile_id` int(10) DEFAULT '0',
    `name` varchar(255) NOT NULL DEFAULT '',
    `uuid` varchar(255) NOT NULL DEFAULT '',
    `relative_position` varchar(255) DEFAULT '',
	PRIMARY KEY (`id`)
) COMMENT='Contains individual item entries for each classroom setup profile';
  