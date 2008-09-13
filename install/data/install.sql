--
-- Структура таблицы `ban_ip`
--
DROP TABLE IF EXISTS `ban`;
DROP TABLE IF EXISTS `ban_ip`;
DROP TABLE IF EXISTS `cms_ban_ip`;
CREATE TABLE `cms_ban_ip` (
  `ip` int(11) NOT NULL default '0',
  `ban_type` tinyint(4) NOT NULL default '0',
  `link` varchar(100) NOT NULL,
  `who` varchar(25) NOT NULL,
  `reason` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY  (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
-- Структура таблицы `count`
--
DROP TABLE IF EXISTS `count`;
CREATE TABLE `count` (
  `id` int(11) NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL,
  `browser` text NOT NULL,
  `time` varchar(25) NOT NULL,
  `where` varchar(100) NOT NULL,
  `name` varchar(25) NOT NULL,
  `dos` binary(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `time` (`time`),
  KEY `where` (`where`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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
  `refid` int(11) NOT NULL,
  `type` char(1) NOT NULL,
  `time` int(11) NOT NULL,
  `from` varchar(25) NOT NULL,
  `to` varchar(25) NOT NULL,
  `realid` int(3) NOT NULL,
  `ip` text NOT NULL,
  `soft` text NOT NULL,
  `text` text NOT NULL,
  `close` binary(1) NOT NULL,
  `vip` binary(1) NOT NULL,
  `moder` binary(1) NOT NULL,
  `edit` text NOT NULL,
  `tedit` int(11) NOT NULL,
  `kedit` int(2) NOT NULL,
  `attach` text NOT NULL,
  `dlcount` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `from` (`from`),
  KEY `to` (`to`),
  KEY `moder` (`moder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  `soft` varchar(100) NOT NULL,
  `admin` varchar(25) NOT NULL,
  `otvet` text NOT NULL,
  `otime` int(11) NOT NULL,
  `edit_who` varchar(20) NOT NULL,
  `edit_time` int(11) NOT NULL,
  `edit_count` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `adm` (`adm`),
  KEY `soft` (`soft`),
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
('nickadmina', ''),
('emailadmina', ''),
('nickadmina2', ''),
('sdvigclock', '0'),
('copyright', ''),
('homeurl', ''),
('rashstr', 'txt'),
('admp', 'panel'),
('flsz', '1000'),
('gzip', '0'),
('rmod', '0'),
('fmod', '0'),
('gb', '0'),
('clean_time', '0'),
('mod_reg', '1'),
('mod_reg_msg', 'Регистрация временно закрыта'),
('mod_forum', '1'),
('mod_forum_msg', 'Форум временно закрыт'),
('mod_chat', '1'),
('mod_chat_msg', 'Чат временно закрыт'),
('mod_guest', '1'),
('mod_guest_msg', 'Гостевая временно закрыта'),
('mod_lib', '1'),
('mod_lib_msg', 'Библиотека временно закрыта'),
('mod_gal', '1'),
('mod_gal_msg', 'Галерея временно закрыта'),
('mod_down', '1'),
('mod_down_msg', 'Загрузки временно закрыты');


--
-- Структура таблицы `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(20) NOT NULL,
  `name_lat` varchar(40) NOT NULL,
  `password` varchar(32) NOT NULL,
  `imname` varchar(35) NOT NULL,
  `sex` char(2) NOT NULL,
  `komm` int(10) NOT NULL default '0',
  `postforum` int(10) NOT NULL default '0',
  `postchat` int(10) NOT NULL default '0',
  `otvetov` int(11) NOT NULL,
  `yearofbirth` int(4) NOT NULL,
  `datereg` int(11) NOT NULL,
  `lastdate` int(11) NOT NULL,
  `mail` varchar(50) NOT NULL default '',
  `icq` int(9) NOT NULL,
  `www` varchar(50) NOT NULL default '',
  `about` text NOT NULL,
  `live` varchar(50) NOT NULL default '',
  `mibile` varchar(50) NOT NULL default '',
  `rights` int(1) NOT NULL,
  `status` text NOT NULL,
  `ip` varchar(25) NOT NULL default '',
  `browser` text NOT NULL,
  `timererfesh` int(2) NOT NULL default '20',
  `kolanywhwere` int(2) NOT NULL default '10',
  `time` int(11) NOT NULL,
  `preg` binary(1) NOT NULL default '\0',
  `regadm` varchar(25) NOT NULL default '',
  `kod` int(15) NOT NULL,
  `mailact` binary(1) NOT NULL default '\0',
  `mailvis` binary(1) NOT NULL default '\0',
  `dayb` int(2) NOT NULL,
  `monthb` int(2) NOT NULL,
  `sdvig` int(2) NOT NULL default '0',
  `offpg` tinyint(1) NOT NULL default '0',
  `offgr` tinyint(1) NOT NULL default '0',
  `offsm` tinyint(1) NOT NULL default '0',
  `offtr` tinyint(1) NOT NULL default '0',
  `pereh` tinyint(1) NOT NULL default '0',
  `nastroy` text NOT NULL,
  `plus` int(3) NOT NULL,
  `minus` int(3) NOT NULL,
  `vrrat` int(11) NOT NULL,
  `upfp` tinyint(1) NOT NULL default '0',
  `farea` tinyint(1) NOT NULL default '0',
  `chmes` int(2) NOT NULL default '10',
  `nmenu` text NOT NULL,
  `carea` tinyint(1) NOT NULL default '0',
  `alls` varchar(25) NOT NULL default '',
  `balans` int(11) NOT NULL,
  `sestime` int(15) NOT NULL,
  `total_on_site` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name_lat` (`name_lat`),
  KEY `lastdate` (`lastdate`)
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
