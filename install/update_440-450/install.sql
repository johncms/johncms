ALTER TABLE `cms_ads` CHANGE `type` `type` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `view` `view` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `layout` `layout` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `count` `count` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `count_link` `count_link` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `to` `to` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `color` `color` VARCHAR( 10 ) NOT NULL DEFAULT '';
ALTER TABLE `cms_ads` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `day` `day` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_ads` CHANGE `mesto` `mesto` TINYINT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_ads`;

ALTER TABLE `cms_album_cat` CHANGE `sort` `sort` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_cat` CHANGE `name` `name` VARCHAR( 40 ) NOT NULL DEFAULT '';
ALTER TABLE `cms_album_cat` CHANGE `password` `password` VARCHAR( 20 ) NOT NULL DEFAULT '';
ALTER TABLE `cms_album_cat` CHANGE `access` `access` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_cat` ADD INDEX ( `user_id` );
ALTER TABLE `cms_album_cat` ADD INDEX ( `access` );
OPTIMIZE TABLE `cms_album_cat`;

ALTER TABLE `cms_album_comments` DROP INDEX `module`;
ALTER TABLE `cms_album_comments` DROP `module`;
ALTER TABLE `cms_album_comments` CHANGE `sub_id` `sub_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_comments` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_comments` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_album_comments`;

ALTER TABLE `cms_album_downloads` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_downloads` CHANGE `file_id` `file_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_downloads` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_album_downloads`;

ALTER TABLE `cms_album_files` CHANGE `img_name` `img_name` VARCHAR( 100 ) NOT NULL DEFAULT '';
ALTER TABLE `cms_album_files` CHANGE `tmb_name` `tmb_name` VARCHAR( 100 ) NOT NULL DEFAULT '';
ALTER TABLE `cms_album_files` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_files` CHANGE `comm_count` `comm_count` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_album_files`;

ALTER TABLE `cms_album_views` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_views` CHANGE `file_id` `file_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_views` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_album_views`;

ALTER TABLE `cms_album_votes` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_album_votes` CHANGE `file_id` `file_id` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_album_votes`;

ALTER TABLE `cms_forum_files` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `cms_forum_files` CHANGE `cat` `cat` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `subcat` `subcat` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `topic` `topic` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `post` `post` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `filetype` `filetype` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `dlcount` `dlcount` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_files` CHANGE `del` `del` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_forum_files`;

ALTER TABLE `cms_forum_rdm` CHANGE `topic_id` `topic_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_rdm` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_rdm` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_forum_rdm`;

ALTER TABLE `cms_forum_vote` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `cms_forum_vote` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_vote` CHANGE `topic` `topic` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cms_forum_vote` CHANGE `count` `count` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `cms_forum_vote`;

DROP TABLE IF EXISTS `cms_users_data`;
CREATE TABLE `cms_users_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `key` varchar(30) NOT NULL DEFAULT '',
  `val` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `key` (`key`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

ALTER TABLE `forum` DROP INDEX `realid`;
ALTER TABLE `forum` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `forum` CHANGE `refid` `refid` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `forum` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `forum` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `forum` CHANGE `close_who` `close_who` VARCHAR( 25 ) NOT NULL DEFAULT '';
ALTER TABLE `forum` CHANGE `tedit` `tedit` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `forum` CHANGE `kedit` `kedit` INT( 2 ) UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `forum`;

ALTER TABLE `guest` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `guest` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `guest` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `guest` CHANGE `otime` `otime` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `guest` CHANGE `edit_time` `edit_time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `guest` CHANGE `edit_count` `edit_count` TINYINT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `guest`;

ALTER TABLE `karma_users` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `karma_users` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `karma_users` CHANGE `name` `name` VARCHAR( 50 ) NOT NULL DEFAULT '';
ALTER TABLE `karma_users` CHANGE `karma_user` `karma_user` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `karma_users` CHANGE `points` `points` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `karma_users` CHANGE `type` `type` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `karma_users` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `karma_users`;

ALTER TABLE `lib` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `lib` CHANGE `refid` `refid` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `lib` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `lib` CHANGE `count` `count` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `lib`;

ALTER TABLE `news` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `news` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `news` CHANGE `kom` `kom` INT UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `news`;

ALTER TABLE `privat` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `privat` CHANGE `time` `time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `privat` CHANGE `otvet` `otvet` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `privat` ADD INDEX ( `type` );
ALTER TABLE `privat` ADD INDEX ( `chit` );
OPTIMIZE TABLE `privat`;

ALTER TABLE `users` DROP `set_language`;
ALTER TABLE `users` DROP `time`;
ALTER TABLE `users` DROP `vrrat`;
ALTER TABLE `users` DROP `alls`;
ALTER TABLE `users` DROP `kod`;
ALTER TABLE `users` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` CHANGE `failed_login` `failed_login` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `imname` `imname` VARCHAR( 50 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `komm` `komm` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `postforum` `postforum` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `postguest` `postguest` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `datereg` `datereg` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `lastdate` `lastdate` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `icq` `icq` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `skype` `skype` VARCHAR( 50 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `jabber` `jabber` VARCHAR( 50 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `live` `live` VARCHAR( 100 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `rights` `rights` TINYINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `sestime` `sestime` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `total_on_site` `total_on_site` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `lastpost` `lastpost` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `rest_code` `rest_code` VARCHAR( 32 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `rest_time` `rest_time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `movings` `movings` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `place` `place` VARCHAR( 30 ) NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `karma_time` `karma_time` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `users` CHANGE `karma_off` `karma_off` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
OPTIMIZE TABLE `users`;