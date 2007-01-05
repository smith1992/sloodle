
create table prefix_sloodle_users
( 
id int(10) unsigned not null auto_increment,
userid int(10) unsigned not null,
uuid varchar(255) not null default '',
avname varchar(255) not null default '',
loginposition varchar(255) not null default '',
loginpositionexpires varchar(255) not null default '',
loginpositionregion varchar(255) not null default '',
loginsecuritytoken varchar(255) not null default '',
PRIMARY KEY  (`id`),
UNIQUE (`userid`),
UNIQUE (`uuid`)
);

create table prefix_sloodle_config
( 
id int(10) unsigned not null auto_increment,
name varchar(255) not null default '',
value varchar(255) not null default '',
PRIMARY KEY  (`id`),
UNIQUE (`id`),
UNIQUE (`name`)
);

CREATE TABLE prefix_sloodle_active_object (
	id int(10) unsigned not null auto_increment,
    sloodle_classroom_setup_profile_id int(10),
    uuid varchar(255) not null default '',
    name varchar(255) not null default '',
    master_uuid varchar(255) not null default '',
    authenticated_by_userid varchar(255) not null default '',
    pwd varchar(255) not null default '',
	PRIMARY KEY  (`id`),
	UNIQUE (`id`)
);

CREATE TABLE prefix_sloodle_classroom_setup_profile (
	id int(10) unsigned not null auto_increment,
    name varchar(255) not null default '',
    courseid int(10),
	PRIMARY KEY  (`id`),
	UNIQUE (`id`)
);

CREATE TABLE prefix_sloodle_classroom_setup_profile_entry (
	id int(10) unsigned not null auto_increment,
    sloodle_classroom_setup_profile_id int(10),
    name varchar(255) not null default '',
    uuid varchar(255) not null default '',
    relative_position varchar(255) not null default '',
	PRIMARY KEY  (`id`),
	UNIQUE (`id`)
);


