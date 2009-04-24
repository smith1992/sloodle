# $Id: mysql.sql,v 1.2 2006/08/28 16:41:20 mark-nielsen Exp $

CREATE TABLE `prefix_freemail` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `description` text NOT NULL default '',
  `timemodified` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) COMMENT='FreeMail';
# --------------------------------------------------------