--
-- Структура таблицы `cms_ads`
--
DROP TABLE IF EXISTS `cms_ads`;
CREATE TABLE `cms_ads` (
  `id` int(11) NOT NULL auto_increment,
  `type` int(2) NOT NULL,
  `view` int(2) NOT NULL,
  `layout` int(2) NOT NULL,
  `count` int(11) NOT NULL,
  `count_link` int(11) NOT NULL,
  `name` text NOT NULL,
  `link` text NOT NULL,
  `to` int(10) NOT NULL default '0',
  `color` varchar(10) NOT NULL,
  `time` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `font` int(2) NOT NULL,
  `mesto` int(2) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_ban_ip`
--
DROP TABLE IF EXISTS `cms_ban_ip`;
CREATE TABLE `cms_ban_ip` (
  `id` int(11) NOT NULL auto_increment,
  `ip1` int(11) NOT NULL,
  `ip2` int(11) NOT NULL,
  `ban_type` tinyint(4) NOT NULL default '0',
  `link` varchar(100) NOT NULL,
  `who` varchar(25) NOT NULL,
  `reason` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip1` (`ip1`),
  UNIQUE KEY `ip2` (`ip2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_ban_users`
--
DROP TABLE IF EXISTS `cms_ban_users`;
CREATE TABLE `cms_ban_users` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `ban_time` int(11) NOT NULL,
  `ban_while` int(11) NOT NULL,
  `ban_type` tinyint(4) NOT NULL default '1',
  `ban_who` varchar(30) NOT NULL,
  `ban_ref` int(11) NOT NULL,
  `ban_reason` text NOT NULL,
  `ban_raz` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_time` (`ban_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_counters`
--
DROP TABLE IF EXISTS `cms_counters`;
CREATE TABLE IF NOT EXISTS `cms_counters` (
  `id` int(11) NOT NULL auto_increment,
  `sort` int(11) NOT NULL default '1',
  `name` varchar(30) NOT NULL,
  `link1` text NOT NULL,
  `link2` text NOT NULL,
  `mode` tinyint(4) NOT NULL default '1',
  `switch` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `chat`
--
DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `realid` int(2) NOT NULL,
  `type` char(3) NOT NULL,
  `time` int(15) NOT NULL,
  `from` varchar(25) NOT NULL,
  `to` varchar(15) NOT NULL,
  `dpar` char(3) NOT NULL,
  `text` text NOT NULL,
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `nas` text NOT NULL,
  `otv` int(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `from` (`from`),
  KEY `to` (`to`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_guests`
--
DROP TABLE IF EXISTS `cms_guests`;
CREATE TABLE `cms_guests` (
  `session_id` char(32) NOT NULL,
  `ip` int(11) NOT NULL,
  `browser` tinytext NOT NULL,
  `lastdate` int(11) NOT NULL,
  `sestime` int(11) NOT NULL,
  `movings` int(11) NOT NULL default '0',
  `place` varchar(30) NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `time` (`lastdate`),
  KEY `place` (`place`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `download`
--
DROP TABLE IF EXISTS `download`;
CREATE TABLE `download` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `adres` text NOT NULL,
  `time` int(11) NOT NULL,
  `name` text NOT NULL,
  `type` varchar(4) NOT NULL,
  `avtor` varchar(25) NOT NULL,
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `text` text NOT NULL,
  `screen` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `forum`
--
DROP TABLE IF EXISTS `forum`;
CREATE TABLE `forum` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL default '0',
  `type` char(1) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL,
  `from` varchar(25) NOT NULL default '',
  `realid` int(3) NOT NULL default '0',
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `text` text NOT NULL,
  `close` tinyint(1) NOT NULL default '0',
  `close_who` varchar(25) NOT NULL,
  `vip` tinyint(1) NOT NULL default '0',
  `edit` text NOT NULL,
  `tedit` int(11) NOT NULL default '0',
  `kedit` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `close` (`close`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_rdm`
--
DROP TABLE IF EXISTS `cms_forum_rdm`;
CREATE TABLE IF NOT EXISTS `cms_forum_rdm` (
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY  (`topic_id`,`user_id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_forum_files`
--
DROP TABLE IF EXISTS `cms_forum_files`;
CREATE TABLE `cms_forum_files` (
  `id` int(11) NOT NULL auto_increment,
  `cat` int(11) NOT NULL,
  `subcat` int(11) NOT NULL,
  `topic` int(11) NOT NULL,
  `post` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `filename` text NOT NULL,
  `filetype` tinyint(4) NOT NULL,
  `dlcount` int(11) NOT NULL,
  `del` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cat` (`cat`),
  KEY `subcat` (`subcat`),
  KEY `topic` (`topic`),
  KEY `post` (`post`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `forum_vote`
--
DROP TABLE IF EXISTS `forum_vote`;
CREATE TABLE `forum_vote` (
  `id` int(11) NOT NULL auto_increment,
  `type` int(2) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `topic` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `forum_vote_us`
--
DROP TABLE IF EXISTS `forum_vote_us`;
CREATE TABLE `forum_vote_us` (
  `id` int(11) NOT NULL auto_increment,
  `user` int(11) NOT NULL default '0',
  `topic` int(11) NOT NULL,
  `vote` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `gallery`
--
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `type` char(2) NOT NULL,
  `avtor` varchar(25) NOT NULL,
  `text` text NOT NULL,
  `name` text NOT NULL,
  `user` binary(1) NOT NULL,
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `time` (`time`),
  KEY `type` (`type`),
  KEY `avtor` (`avtor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `guest`
--
DROP TABLE IF EXISTS `guest`;
CREATE TABLE `guest` (
  `id` int(11) NOT NULL auto_increment,
  `adm` tinyint(1) NOT NULL default '0',
  `time` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(25) NOT NULL,
  `text` text NOT NULL,
  `ip` int(11) NOT NULL,
  `browser` tinytext NOT NULL,
  `admin` varchar(25) NOT NULL,
  `otvet` text NOT NULL,
  `otime` int(11) NOT NULL,
  `edit_who` varchar(20) NOT NULL,
  `edit_time` int(11) NOT NULL,
  `edit_count` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `adm` (`adm`),
  KEY `time` (`time`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `lib`
--
DROP TABLE IF EXISTS `lib`;
CREATE TABLE `lib` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `type` varchar(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `announce` text NOT NULL,
  `avtor` varchar(25) NOT NULL,
  `text` mediumtext NOT NULL,
  `ip` int(11) NOT NULL,
  `soft` text NOT NULL,
  `moder` tinyint(1) NOT NULL default '0',
  `count` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `type` (`type`),
  KEY `moder` (`moder`),
  KEY `time` (`time`),
  KEY `refid` (`refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `news`
--
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(11) NOT NULL auto_increment,
  `time` int(11) NOT NULL,
  `avt` varchar(25) NOT NULL default '',
  `name` text NOT NULL,
  `text` text NOT NULL,
  `kom` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Структура таблицы `privat`
--
DROP TABLE IF EXISTS `privat`;
CREATE TABLE `privat` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(25) NOT NULL default '',
  `text` text NOT NULL,
  `time` varchar(25) NOT NULL default '',
  `author` varchar(25) NOT NULL default '',
  `type` char(3) NOT NULL default '',
  `chit` char(3) NOT NULL default '',
  `temka` text NOT NULL,
  `otvet` binary(1) NOT NULL default '\0',
  `me` varchar(25) NOT NULL default '',
  `cont` varchar(25) NOT NULL default '',
  `ignor` varchar(25) NOT NULL default '',
  `attach` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `me` (`me`),
  KEY `ignor` (`ignor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `cms_settings`
--
DROP TABLE IF EXISTS `cms_settings`;
CREATE TABLE `cms_settings` (
  `key` tinytext NOT NULL,
  `val` text NOT NULL,
  PRIMARY KEY  (`key`(30))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `cms_settings`
--
INSERT INTO `cms_settings` (`key`, `val`) VALUES
('meta_desc', ''),
('emailadmina', ''),
('meta_key', ''),
('sdvigclock', '0'),
('copyright', 'JohnCMS 3.1.0'),
('homeurl', ''),
('karma', 'a:6:{s:12:"karma_points";i:5;s:10:"karma_time";i:86400;s:5:"forum";i:50;s:4:"time";i:0;s:2:"on";i:1;s:3:"adm";i:0;}'),
('admp', 'panel'),
('flsz', '1000'),
('gzip', '1'),
('clean_time', ''),
('mod_reg', '2'),
('mod_forum', '2'),
('mod_chat', '2'),
('mod_guest', '2'),
('mod_lib', '2'),
('mod_gal', '2'),
('mod_down_comm', '1'),
('mod_down', '2'),
('mod_lib_comm', '1'),
('mod_gal_comm', '1'),
('skindef', 'default'),
('news', 'a:8:{s:4:"view";i:1;s:4:"size";i:500;s:8:"quantity";i:2;s:4:"days";i:5;s:6:"breaks";i:1;s:7:"smileys";i:0;s:4:"tags";i:1;s:3:"kom";i:1;}');

--
-- Структура таблицы `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `immunity` tinyint(1) NOT NULL,
  `name` varchar(25) NOT NULL default '',
  `name_lat` varchar(40) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `failed_login` tinyint(4) NOT NULL default '0',
  `imname` varchar(25) NOT NULL default '',
  `sex` varchar(2) NOT NULL default '',
  `komm` int(10) NOT NULL default '0',
  `postforum` int(10) NOT NULL default '0',
  `postguest` int(11) NOT NULL default '0',
  `postchat` int(10) NOT NULL default '0',
  `otvetov` int(11) NOT NULL default '0',
  `yearofbirth` int(4) NOT NULL default '0',
  `datereg` int(11) NOT NULL default '0',
  `lastdate` int(11) NOT NULL default '0',
  `mail` varchar(50) NOT NULL default '',
  `icq` int(9) NOT NULL default '0',
  `skype` varchar(50) NOT NULL,
  `jabber` varchar(50) NOT NULL,
  `www` varchar(50) NOT NULL default '',
  `about` text NOT NULL,
  `live` varchar(50) NOT NULL default '',
  `mibile` varchar(50) NOT NULL default '',
  `rights` int(1) NOT NULL default '0',
  `status` text NOT NULL,
  `ip` varchar(25) NOT NULL default '',
  `browser` text NOT NULL,
  `time` int(11) NOT NULL default '0',
  `preg` binary(1) NOT NULL default '\0',
  `regadm` varchar(25) NOT NULL default '',
  `kod` int(15) NOT NULL default '0',
  `mailvis` tinyint(1) NOT NULL default '1',
  `dayb` int(2) NOT NULL default '0',
  `monthb` int(2) NOT NULL default '0',
  `vrrat` int(11) NOT NULL default '0',
  `alls` varchar(25) NOT NULL default '',
  `balans` int(11) NOT NULL default '0',
  `sestime` int(15) NOT NULL default '0',
  `total_on_site` int(11) NOT NULL default '0',
  `lastpost` int(11) NOT NULL,
  `rest_code` varchar(32) NOT NULL,
  `rest_time` int(11) NOT NULL,
  `movings` int(11) NOT NULL default '0',
  `place` varchar(30) NOT NULL,
  `set_user` text NOT NULL,
  `set_forum` text NOT NULL,
  `set_chat` text NOT NULL,
  `karma` int(11) NOT NULL default '0',
  `karma_time` int(11) NOT NULL default '0',
  `plus_minus` varchar(40) NOT NULL default '0|0',
  `karma_off` int(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name_lat` (`name_lat`),
  KEY `lastdate` (`lastdate`),
  KEY `place` (`place`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `karma_users`
--
DROP TABLE IF EXISTS `karma_users`;
CREATE TABLE `karma_users` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `karma_user` int(11) NOT NULL,
  `points` int(2) NOT NULL,
  `type` int(1) NOT NULL,
  `time` int(11) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `karma_user` (`karma_user`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Структура таблицы `vik`
--
DROP TABLE IF EXISTS `vik`;
CREATE TABLE `vik` (
  `id` int(11) NOT NULL auto_increment,
  `vopros` text NOT NULL,
  `otvet` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
