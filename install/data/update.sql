--
-- Создаем таблицу `cms_album_cat`
--
DROP TABLE IF EXISTS `cms_album_cat`;
CREATE TABLE `cms_album_cat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `name` varchar(40) NOT NULL,
  `description` text NOT NULL,
  `password` varchar(20) NOT NULL,
  `access` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `access` (`access`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_album_comments`
--
DROP TABLE IF EXISTS `cms_album_comments`;
CREATE TABLE `cms_album_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int(10) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `text` text NOT NULL,
  `reply` text NOT NULL,
  `attributes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_album_downloads`
--
DROP TABLE IF EXISTS `cms_album_downloads`;
CREATE TABLE `cms_album_downloads` (
  `user_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_album_files`
--
DROP TABLE IF EXISTS `cms_album_files`;
CREATE TABLE `cms_album_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `album_id` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  `img_name` varchar(100) NOT NULL,
  `tmb_name` varchar(100) NOT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  `comments` tinyint(1) NOT NULL DEFAULT '1',
  `comm_count` int(11) NOT NULL DEFAULT '0',
  `access` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `vote_plus` int(11) NOT NULL,
  `vote_minus` int(11) NOT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `downloads` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `album_id` (`album_id`),
  KEY `access` (`access`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_album_views`
--
DROP TABLE IF EXISTS `cms_album_views`;
CREATE TABLE `cms_album_views` (
  `user_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_album_votes`
--
DROP TABLE IF EXISTS `cms_album_votes`;
CREATE TABLE `cms_album_votes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `vote` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_lng_list`
--
DROP TABLE IF EXISTS `cms_lng_list`;
CREATE TABLE `cms_lng_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iso` char(2) NOT NULL,
  `name` varchar(50) NOT NULL,
  `build` int(11) NOT NULL DEFAULT '0',
  `attr` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iso` (`iso`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_lng_phrases`
--
DROP TABLE IF EXISTS `cms_lng_phrases`;
CREATE TABLE `cms_lng_phrases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `language_id` int(10) unsigned NOT NULL,
  `module` varchar(10) NOT NULL,
  `keyword` varchar(30) NOT NULL,
  `default` text NOT NULL,
  `custom` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `language_id` (`language_id`),
  KEY `module` (`module`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_users_guestbook`
--
DROP TABLE IF EXISTS `cms_users_guestbook`;
CREATE TABLE `cms_users_guestbook` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int(10) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `text` text NOT NULL,
  `reply` text NOT NULL,
  `attributes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Создаем таблицу `cms_users_iphistory`
--
DROP TABLE IF EXISTS `cms_users_iphistory`;
CREATE TABLE `cms_users_iphistory` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ip` bigint(11) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Удаляем старый Чат
--
DROP TABLE `chat`;
DROP TABLE `vik`;
ALTER TABLE `users` DROP `otvetov`;
ALTER TABLE `users` DROP `postchat`;
ALTER TABLE `users` DROP `balans`;
ALTER TABLE `users` DROP `set_chat`;

--
-- Модифицируем таблицы Форума
--
RENAME TABLE `forum_vote` TO `cms_forum_vote`;
RENAME TABLE `forum_vote_us` TO `cms_forum_vote_users`;
ALTER TABLE `cms_forum_vote` ADD INDEX ( `type` );
ALTER TABLE `cms_forum_vote` ADD INDEX ( `topic` );
ALTER TABLE `cms_forum_vote_users` ADD INDEX ( `topic` );

--
-- Модифицируем таблицу `guest`
--
ALTER TABLE `guest` CHANGE `ip` `ip` BIGINT( 11 ) NOT NULL DEFAULT '0';

--
-- Модифицируем таблицу `cms_ads`
--
ALTER TABLE `cms_ads` DROP `font`;
ALTER TABLE `cms_ads` ADD `bold` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` ADD `italic` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` ADD `underline` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` ADD `show` BOOLEAN NOT NULL DEFAULT '0';

--
-- Модифицируем таблицу `cms_guests`
--
TRUNCATE `cms_guests`;
ALTER TABLE `cms_guests` CHANGE `ip` `ip` BIGINT( 11 ) NOT NULL DEFAULT '0';

--
-- Модифицируем таблицу `cms_ban_ip`
--
ALTER TABLE `cms_ban_ip` CHANGE `ip1` `ip1` BIGINT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `cms_ban_ip` CHANGE `ip2` `ip2` BIGINT( 11 ) NOT NULL DEFAULT '0';

--
-- Модифицируем таблицу `users`
--
ALTER TABLE `users` DROP `immunity`;
ALTER TABLE `users` DROP `set_user`;
ALTER TABLE `users` DROP `set_forum`;
ALTER TABLE `users` ADD `set_language` tinyint(4) NOT NULL AFTER `place`;
ALTER TABLE `users` ADD `set_user` TEXT NOT NULL AFTER `set_language`;
ALTER TABLE `users` ADD `set_forum` TEXT NOT NULL AFTER `set_user`;
ALTER TABLE `users` ADD `karma_plus` INT NOT NULL DEFAULT '0' AFTER `set_forum`;
ALTER TABLE `users` ADD `karma_minus` INT NOT NULL DEFAULT '0' AFTER `karma_plus`;
ALTER TABLE `users` CHANGE `id` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` CHANGE `failed_login` `failed_login` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `ip` `ip` BIGINT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `mailvis` `mailvis` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `preg` `preg` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD `comm_count` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` ADD `comm_old` INT UNSIGNED NOT NULL DEFAULT '0';

--
-- Модифицируем записи таблицы `cms_settings`
--
DELETE FROM `cms_settings` WHERE `key` = 'nickadmina';
DELETE FROM `cms_settings` WHERE `key` = 'nickadmina2';
DELETE FROM `cms_settings` WHERE `key` = 'rashstr';
DELETE FROM `cms_settings` WHERE `key` = 'mod_chat';
UPDATE `cms_settings` SET `key` = 'email' WHERE `key` = 'emailadmina';
UPDATE `cms_settings` SET `key` = 'timeshift' WHERE `key` = 'sdvigclock';
INSERT INTO `cms_settings` (`key`, `val`) VALUES
('activity', '1'),
('lng_id', ''),
('lng_iso', '');