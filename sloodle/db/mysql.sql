
create table prefix_sloodle_users
( 
id int(10) unsigned not null auto_increment,
userid int(10) unsigned not null,
uuid varchar(255) not null default '',
avname varchar(255) not null default '',
loginposition varchar(255) not null default '',
loginpositionexpires varchar(255) not null default ''
PRIMARY KEY  (`id`),
UNIQUE (`userid`,`uuid`)
)

create table prefix_sloodle_config
( 
id int(10) unsigned not null auto_increment,
name varchar(255) not null default '',
value varchar(255) not null default '',
PRIMARY KEY  (`id`),
UNIQUE (`id`),
UNIQUE (`name`)
);
