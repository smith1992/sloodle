# $Id: mysql.sql,v 1.3 2006/04/19 22:39:18 michaelpenne Exp $

create table prefix_sloodle_users
                    ( id int(10) unsigned not null auto_increment,
                      userid int(10) unsigned not null,
                      uuid varchar(255) not null,
                      avname varchar(255) not null,
                      PRIMARY KEY  (`id`),
                      UNIQUE (`userid`,`uuid`)
                    );