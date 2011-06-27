DROP TABLE IF EXISTS `cms_lng_list`;
DROP TABLE IF EXISTS `cms_lng_phrases`;

ALTER TABLE `users` DROP `set_language`;
ALTER TABLE `users` DROP `postchat`;
ALTER TABLE `users` DROP `otvetov`;
ALTER TABLE `users` DROP `mailact`;
ALTER TABLE `users` DROP `vrrat`;
ALTER TABLE `users` DROP `cctx`;
ALTER TABLE `users` DROP `alls`;
ALTER TABLE `users` DROP `balans`;
ALTER TABLE `users` DROP `set_chat`;
ALTER TABLE `users` DROP `kod`;
ALTER TABLE `users` ADD `smileys` text NOT NULL;

DELETE FROM `cms_settings` WHERE `key` = 'lng_id' LIMIT 1;
UPDATE `cms_settings` SET `key` = 'lng' WHERE `key` = 'lng_iso' LIMIT 1;

ALTER TABLE `users` ADD `ip_via_proxy` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `ip`;

TRUNCATE TABLE `cms_users_iphistory`;
ALTER TABLE `cms_users_iphistory` CHANGE `ip` `ip` BIGINT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `cms_users_iphistory` ADD `ip_via_proxy` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `ip`;

UPDATE `users` SET `set_user` = '';
UPDATE `users` SET `lastpost` = '0';

DROP TABLE IF EXISTS `cms_guests`;
DROP TABLE IF EXISTS `cms_sessions`;
CREATE TABLE `cms_sessions` (
  `session_id` char(32) NOT NULL DEFAULT '',
  `ip` bigint(11) NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint(11) NOT NULL DEFAULT '0',
  `browser` varchar(255) NOT NULL DEFAULT '',
  `lastdate` int(10) unsigned NOT NULL DEFAULT '0',
  `sestime` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `movings` smallint(5) unsigned NOT NULL DEFAULT '0',
  `place` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`session_id`),
  KEY `lastdate` (`lastdate`),
  KEY `place` (`place`(10))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `lib` CHANGE `name` `name` TINYTEXT NOT NULL;
ALTER TABLE `lib` ADD FULLTEXT (`name`);
ALTER TABLE `lib` ADD FULLTEXT (`text`);

ALTER TABLE `forum` CHANGE `ip` `ip_old` TEXT NOT NULL;
ALTER TABLE `forum` ADD `ip` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `text`;
ALTER TABLE `forum` ADD `ip_via_proxy` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `ip`;