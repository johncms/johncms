--
-- Структура таблицы `cms_ads`
--
DROP TABLE IF EXISTS `cms_ads`;
CREATE TABLE `cms_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(2) NOT NULL,
  `view` int(2) NOT NULL,
  `layout` int(2) NOT NULL,
  `count` int(11) NOT NULL,
  `count_link` int(11) NOT NULL,
  `name` text NOT NULL,
  `link` text NOT NULL,
  `to` int(10) NOT NULL DEFAULT '0',
  `color` varchar(10) NOT NULL,
  `time` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `mesto` int(2) NOT NULL,
  `bold` tinyint(1) NOT NULL DEFAULT '0',
  `italic` tinyint(1) NOT NULL DEFAULT '0',
  `underline` tinyint(1) NOT NULL DEFAULT '0',
  `show` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_album_cat`
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
-- Структура таблицы `cms_album_comments`
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
-- Структура таблицы `cms_album_downloads`
--
DROP TABLE IF EXISTS `cms_album_downloads`;
CREATE TABLE `cms_album_downloads` (
  `user_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_album_files`
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
-- Структура таблицы `cms_album_views`
--
DROP TABLE IF EXISTS `cms_album_views`;
CREATE TABLE `cms_album_views` (
  `user_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_album_votes`
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
-- Структура таблицы `cms_ban_ip`
--
DROP TABLE IF EXISTS `cms_ban_ip`;
CREATE TABLE `cms_ban_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip1` bigint(11) NOT NULL DEFAULT '0',
  `ip2` bigint(11) NOT NULL DEFAULT '0',
  `ban_type` tinyint(4) NOT NULL DEFAULT '0',
  `link` varchar(100) NOT NULL,
  `who` varchar(25) NOT NULL,
  `reason` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip1` (`ip1`),
  UNIQUE KEY `ip2` (`ip2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_ban_users`
--
DROP TABLE IF EXISTS `cms_ban_users`;
CREATE TABLE `cms_ban_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `ban_time` int(11) NOT NULL DEFAULT '0',
  `ban_while` int(11) NOT NULL DEFAULT '0',
  `ban_type` tinyint(4) NOT NULL DEFAULT '1',
  `ban_who` varchar(30) NOT NULL DEFAULT '',
  `ban_ref` int(11) NOT NULL DEFAULT '0',
  `ban_reason` text NOT NULL,
  `ban_raz` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_time` (`ban_time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_counters`
--
DROP TABLE IF EXISTS `cms_counters`;
CREATE TABLE `cms_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` int(11) NOT NULL DEFAULT '1',
  `name` varchar(30) NOT NULL,
  `link1` text NOT NULL,
  `link2` text NOT NULL,
  `mode` tinyint(4) NOT NULL DEFAULT '1',
  `switch` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_files`
--
DROP TABLE IF EXISTS `cms_forum_files`;
CREATE TABLE `cms_forum_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat` int(11) NOT NULL,
  `subcat` int(11) NOT NULL,
  `topic` int(11) NOT NULL,
  `post` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `filename` text NOT NULL,
  `filetype` tinyint(4) NOT NULL,
  `dlcount` int(11) NOT NULL,
  `del` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cat` (`cat`),
  KEY `subcat` (`subcat`),
  KEY `topic` (`topic`),
  KEY `post` (`post`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_rdm`
--
DROP TABLE IF EXISTS `cms_forum_rdm`;
CREATE TABLE `cms_forum_rdm` (
  `topic_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`,`user_id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_vote`
--
DROP TABLE IF EXISTS `cms_forum_vote`;
CREATE TABLE `cms_forum_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(2) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  `topic` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `topic` (`topic`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_vote_users`
--
DROP TABLE IF EXISTS `cms_forum_vote_users`;
CREATE TABLE `cms_forum_vote_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL DEFAULT '0',
  `topic` int(11) NOT NULL,
  `vote` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic` (`topic`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_guests`
--
DROP TABLE IF EXISTS `cms_guests`;
CREATE TABLE `cms_guests` (
  `session_id` char(32) NOT NULL,
  `ip` bigint(11) NOT NULL DEFAULT '0',
  `browser` tinytext NOT NULL,
  `lastdate` int(11) NOT NULL,
  `sestime` int(11) NOT NULL,
  `movings` int(11) NOT NULL DEFAULT '0',
  `place` varchar(30) NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `time` (`lastdate`),
  KEY `place` (`place`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_settings`
--
DROP TABLE IF EXISTS `cms_settings`;
CREATE TABLE `cms_settings` (
  `key` tinytext NOT NULL,
  `val` text NOT NULL,
  PRIMARY KEY (`key`(30))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `cms_settings`
--
INSERT INTO `cms_settings` (`key`, `val`) VALUES
('lng', 'en'),
('homeurl', ''),
('email', ''),
('timeshift', '0'),
('copyright', 'Powered by JohnCMS'),
('admp', 'panel'),
('flsz', '4000'),
('gzip', '1'),
('clean_time', '0'),
('mod_reg', '2'),
('mod_forum', '2'),
('mod_guest', '2'),
('mod_lib', '2'),
('mod_gal', '2'),
('mod_down_comm', '1'),
('mod_down', '2'),
('mod_lib_comm', '1'),
('mod_gal_comm', '1'),
('meta_key', ''),
('meta_desc', 'Powered by JohnCMS http://johncms.com'),
('skindef', 'default'),
('news', 'a:8:{s:4:"view";i:1;s:4:"size";i:200;s:8:"quantity";i:5;s:4:"days";i:3;s:6:"breaks";i:1;s:7:"smileys";i:1;s:4:"tags";i:1;s:3:"kom";i:1;}'),
('karma', 'a:6:{s:12:"karma_points";i:5;s:10:"karma_time";i:86400;s:5:"forum";i:20;s:4:"time";i:0;s:2:"on";i:1;s:3:"adm";i:0;}'),
('antiflood', 'a:5:{s:4:"mode";i:2;s:3:"day";i:10;s:5:"night";i:30;s:7:"dayfrom";i:10;s:5:"dayto";i:22;}'),
('active', '1');

--
-- Структура таблицы `cms_users_guestbook`
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
-- Структура таблицы `cms_users_iphistory`
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
-- Структура таблицы `download`
--
DROP TABLE IF EXISTS `download`;
CREATE TABLE `download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refid` int(11) NOT NULL DEFAULT '0',
  `adres` text NOT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `type` varchar(4) NOT NULL DEFAULT '',
  `avtor` varchar(25) NOT NULL DEFAULT '',
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `text` text NOT NULL,
  `screen` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `refid` (`refid`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `forum`
--
DROP TABLE IF EXISTS `forum`;
CREATE TABLE `forum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refid` int(11) NOT NULL DEFAULT '0',
  `type` char(1) NOT NULL DEFAULT '',
  `time` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `from` varchar(25) NOT NULL DEFAULT '',
  `realid` int(3) NOT NULL DEFAULT '0',
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `text` text NOT NULL,
  `close` tinyint(1) NOT NULL DEFAULT '0',
  `close_who` varchar(25) NOT NULL,
  `vip` tinyint(1) NOT NULL DEFAULT '0',
  `edit` text NOT NULL,
  `tedit` int(11) NOT NULL DEFAULT '0',
  `kedit` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `close` (`close`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `gallery`
--
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refid` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  `type` varchar(2) NOT NULL DEFAULT '',
  `avtor` varchar(25) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `name` text NOT NULL,
  `user` binary(1) NOT NULL DEFAULT '\0',
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `avtor` (`avtor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `guest`
--
DROP TABLE IF EXISTS `guest`;
CREATE TABLE `guest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adm` tinyint(1) NOT NULL DEFAULT '0',
  `time` int(15) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(25) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `ip` bigint(11) NOT NULL DEFAULT '0',
  `browser` tinytext NOT NULL,
  `admin` varchar(25) NOT NULL DEFAULT '',
  `otvet` text NOT NULL,
  `otime` int(15) NOT NULL DEFAULT '0',
  `edit_who` varchar(20) NOT NULL DEFAULT '',
  `edit_time` int(11) NOT NULL DEFAULT '0',
  `edit_count` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `ip` (`ip`),
  KEY `adm` (`adm`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `karma_users`
--
DROP TABLE IF EXISTS `karma_users`;
CREATE TABLE `karma_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `karma_user` int(11) NOT NULL,
  `points` int(2) NOT NULL,
  `type` int(1) NOT NULL,
  `time` int(11) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `karma_user` (`karma_user`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `lib`
--
DROP TABLE IF EXISTS `lib`;
CREATE TABLE `lib` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refid` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  `type` varchar(4) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `announce` text NOT NULL,
  `avtor` varchar(25) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `ip` int(11) NOT NULL DEFAULT '0',
  `soft` text NOT NULL,
  `moder` tinyint(1) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `moder` (`moder`),
  KEY `time` (`time`),
  KEY `refid` (`refid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `news`
--
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL DEFAULT '0',
  `avt` varchar(25) NOT NULL DEFAULT '',
  `name` text NOT NULL,
  `text` text NOT NULL,
  `kom` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `privat`
--
DROP TABLE IF EXISTS `privat`;
CREATE TABLE `privat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(25) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `time` varchar(25) NOT NULL DEFAULT '',
  `author` varchar(25) NOT NULL DEFAULT '',
  `type` char(3) NOT NULL DEFAULT '',
  `chit` char(3) NOT NULL DEFAULT '',
  `temka` text NOT NULL,
  `otvet` binary(1) NOT NULL DEFAULT '\0',
  `me` varchar(25) NOT NULL DEFAULT '',
  `cont` varchar(25) NOT NULL DEFAULT '',
  `ignor` varchar(25) NOT NULL DEFAULT '',
  `attach` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `me` (`me`),
  KEY `ignor` (`ignor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL DEFAULT '',
  `name_lat` varchar(40) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `rights` int(1) NOT NULL DEFAULT '0',
  `failed_login` tinyint(4) NOT NULL DEFAULT '0',
  `imname` varchar(25) NOT NULL DEFAULT '',
  `sex` varchar(2) NOT NULL DEFAULT '',
  `komm` int(10) NOT NULL DEFAULT '0',
  `postforum` int(10) NOT NULL DEFAULT '0',
  `postguest` int(11) NOT NULL DEFAULT '0',
  `yearofbirth` int(4) NOT NULL DEFAULT '0',
  `datereg` int(11) NOT NULL DEFAULT '0',
  `lastdate` int(11) NOT NULL DEFAULT '0',
  `mail` varchar(50) NOT NULL DEFAULT '',
  `icq` int(9) NOT NULL DEFAULT '0',
  `skype` varchar(50) NOT NULL,
  `jabber` varchar(50) NOT NULL,
  `www` varchar(50) NOT NULL DEFAULT '',
  `about` text NOT NULL,
  `live` varchar(50) NOT NULL DEFAULT '',
  `mibile` varchar(50) NOT NULL DEFAULT '',
  `status` text NOT NULL,
  `ip` bigint(11) NOT NULL DEFAULT '0',
  `browser` text NOT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  `preg` tinyint(1) NOT NULL DEFAULT '0',
  `regadm` varchar(25) NOT NULL DEFAULT '',
  `mailvis` tinyint(1) NOT NULL DEFAULT '0',
  `dayb` int(2) NOT NULL DEFAULT '0',
  `monthb` int(2) NOT NULL DEFAULT '0',
  `sestime` int(15) NOT NULL DEFAULT '0',
  `total_on_site` int(11) NOT NULL DEFAULT '0',
  `lastpost` int(11) NOT NULL,
  `rest_code` varchar(32) NOT NULL,
  `rest_time` int(11) NOT NULL,
  `movings` int(11) NOT NULL DEFAULT '0',
  `place` varchar(30) NOT NULL,
  `set_user` text NOT NULL,
  `set_forum` text NOT NULL,
  `karma_plus` int(11) NOT NULL DEFAULT '0',
  `karma_minus` int(11) NOT NULL DEFAULT '0',
  `karma_time` int(11) NOT NULL DEFAULT '0',
  `karma_off` int(1) NOT NULL,
  `comm_count` int(10) unsigned NOT NULL,
  `comm_old` int(10) unsigned NOT NULL DEFAULT '0',
  `smileys` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name_lat` (`name_lat`),
  KEY `lastdate` (`lastdate`),
  KEY `place` (`place`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;