create table prefix_sloodle_users ( 
id SERIAL PRIMARY KEY,
userid integer not null,
uuid varchar(255) not null default '',
avname varchar(255) not null default '',
loginposition varchar(255) not null default '',
loginpositionexpires varchar(255) not null default '',
loginpositionregion varchar(255) not null default '',
loginsecuritytoken varchar(255) not null default ''
);

create table prefix_sloodle_config ( 
id SERIAL PRIMARY KEY,
name varchar(255) not null default '',
value varchar(255) not null default ''
);
