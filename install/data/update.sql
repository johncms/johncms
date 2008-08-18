--
-- Удаляем ненужные таблицы
--
DROP TABLE IF EXISTS `ban`;
DROP TABLE IF EXISTS `moder`;
DROP TABLE IF EXISTS `ban_ip`;

--
-- Создаем таблицу настроек
--
DROP TABLE IF EXISTS `cms_settings`;
CREATE TABLE `cms_settings` (
  `key` tinytext character set utf8 NOT NULL,
  `val` text character set utf8 NOT NULL,
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
-- Создаем таблицу `cms_ban_ip`
--
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
-- Создаем таблицу `cms_ban_users`
--
DROP TABLE IF EXISTS `cms_ban_users`;
CREATE TABLE `cms_ban_users` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `ban_time` int(11) NOT NULL,
  `ban_while` int(11) NOT NULL,
  `ban_type` tinyint(4) NOT NULL default '1',
  `ban_who` varchar(30) NOT NULL,
  `ban_reason` text NOT NULL,
  `ban_raz` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_time` (`ban_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Модифицируем таблицу `guest`
--
ALTER TABLE `guest` ADD `adm` BOOL NOT NULL DEFAULT '0' AFTER `id` ;
ALTER TABLE `guest` ADD INDEX ( `adm` ) ;

--
-- Модифицируем таблицу `lib`
--
ALTER TABLE `lib` CHANGE `moder` `moder` BOOL NOT NULL DEFAULT '0';

--
-- Модифицируем таблицу `users`
--
ALTER TABLE `users` DROP `ban`;
ALTER TABLE `users` DROP `why`;
ALTER TABLE `users` DROP `who`;
ALTER TABLE `users` DROP `bantime`;
ALTER TABLE `users` DROP `fban`;
ALTER TABLE `users` DROP `fwhy`;
ALTER TABLE `users` DROP `fwho`;
ALTER TABLE `users` DROP `ftime`;
ALTER TABLE `users` DROP `chban`;
ALTER TABLE `users` DROP `chwhy`;
ALTER TABLE `users` DROP `chwho`;
ALTER TABLE `users` DROP `chtime`;