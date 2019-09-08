DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `cms_settings`;
DROP TABLE IF EXISTS `download`;

ALTER TABLE `users` CHANGE `imname` `imname` VARCHAR(100) NOT NULL DEFAULT '';

--
-- Структура таблицы `download__category`
--
DROP TABLE IF EXISTS `download__category`;
CREATE TABLE `download__category` (
  `id`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refid`    INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `dir`      TEXT             NOT NULL,
  `sort`     INT(11)          NOT NULL DEFAULT '0',
  `name`     TEXT             NOT NULL,
  `total`    INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `rus_name` TEXT             NOT NULL,
  `text`     TEXT             NOT NULL,
  `field`    INT(11)          NOT NULL DEFAULT '0',
  `desc`     TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `total` (`total`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `download__files`
--
DROP TABLE IF EXISTS `download__files`;
CREATE TABLE `download__files` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refid`      INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `dir`        TEXT             NOT NULL,
  `time`       INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `name`       TEXT             NOT NULL,
  `type`       INT(2)  UNSIGNED NOT NULL DEFAULT '0',
  `user_id`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `rus_name`   TEXT             NOT NULL,
  `text`       TEXT             NOT NULL,
  `field`      INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `rate`       VARCHAR(30)      NOT NULL DEFAULT '0|0',
  `about`      TEXT             NOT NULL,
  `desc`       TEXT             NOT NULL,
  `comm_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `comm_count` (`comm_count`),
  KEY `type` (`type`),
  KEY `user_id` (`user_id`),
  KEY `time` (`time`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `download__more`
--
DROP TABLE IF EXISTS `download__more`;
CREATE TABLE `download__more` (
  `id`       INT(11) NOT NULL AUTO_INCREMENT,
  `refid`    INT(11) NOT NULL,
  `time`     INT(11) NOT NULL,
  `name`     TEXT    NOT NULL,
  `rus_name` TEXT    NOT NULL,
  `size`     INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `time` (`time`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `download__comments`
--
DROP TABLE IF EXISTS `download__comments`;
CREATE TABLE `download__comments` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sub_id`     INT(10) UNSIGNED NOT NULL,
  `time`       INT(11)          NOT NULL,
  `user_id`    INT(10) UNSIGNED NOT NULL,
  `text`       TEXT             NOT NULL,
  `reply`      TEXT             NOT NULL,
  `attributes` TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `download__bookmark`
--
DROP TABLE IF EXISTS `download__bookmark`;
CREATE TABLE `download__bookmark` (
  `id`      INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `file_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `user_id` (`user_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Исправляем индексы
--
ALTER TABLE library_texts DROP INDEX text;
ALTER TABLE `library_texts` ADD INDEX(`name`);
ALTER TABLE `library_texts` ADD FULLTEXT(`text`);

--
-- Конвертация таблицы `cms_ads`
--
ALTER TABLE `cms_ads` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_ads` CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_ads` CHANGE `link` `link` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_ads` CHANGE `color` `color` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_album_cat`
--
ALTER TABLE `cms_album_cat` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_album_cat` CHANGE `name` `name` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_album_cat` CHANGE `password` `password` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_album_comments`
--
ALTER TABLE `cms_album_comments` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_album_comments` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_album_comments` CHANGE `reply` `reply` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_album_comments` CHANGE `attributes` `attributes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_album_downloads`
--
ALTER TABLE `cms_album_downloads` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_album_files`
--
ALTER TABLE `cms_album_files` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_album_files` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_album_files` CHANGE `img_name` `img_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_album_files` CHANGE `tmb_name` `tmb_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_album_views`
--
ALTER TABLE `cms_album_views` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_album_votes`
--
ALTER TABLE `cms_album_votes` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_ban_ip`
--
ALTER TABLE `cms_ban_ip` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_ban_ip` CHANGE `link` `link` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_ban_ip` CHANGE `who` `who` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_ban_ip` CHANGE `reason` `reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_ban_users`
--
ALTER TABLE `cms_ban_users` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_ban_users` CHANGE `ban_who` `ban_who` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_ban_users` CHANGE `ban_reason` `ban_reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_ban_users` CHANGE `ban_raz` `ban_raz` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_contact`
--
ALTER TABLE `cms_contact` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_counters`
--
ALTER TABLE `cms_counters` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_counters` CHANGE `name` `name` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_counters` CHANGE `link1` `link1` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_counters` CHANGE `link2` `link2` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_forum_files`
--
ALTER TABLE `cms_forum_files` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_forum_files` CHANGE `filename` `filename` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_forum_rdm`
--
ALTER TABLE `cms_forum_rdm` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_forum_vote`
--
ALTER TABLE `cms_forum_vote` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_forum_vote` CHANGE `name` `name` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_forum_vote_users`
--
ALTER TABLE `cms_forum_vote_users` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `cms_mail`
--
ALTER TABLE `cms_mail` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_mail` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_mail` CHANGE `file_name` `file_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_mail` CHANGE `them` `them` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_sessions`
--
ALTER TABLE `cms_sessions` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_sessions` CHANGE `session_id` `session_id` CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_sessions` CHANGE `browser` `browser` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_sessions` CHANGE `place` `place` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_users_data`
--
ALTER TABLE `cms_users_data` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_users_data` CHANGE `key` `key` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `cms_users_data` CHANGE `val` `val` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_users_guestbook`
--
ALTER TABLE `cms_users_guestbook` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_users_guestbook` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_users_guestbook` CHANGE `reply` `reply` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_users_guestbook` CHANGE `attributes` `attributes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_users_iphistory`
--
ALTER TABLE `cms_users_iphistory` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- //////////////////////////////////////////////////////////////////////////////////
-- Конвертация таблицы `download__bookmark`
--
ALTER TABLE `download__bookmark` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `download__category`
--
ALTER TABLE `download__category` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `download__category` CHANGE `dir` `dir` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__category` CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__category` CHANGE `rus_name` `rus_name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__category` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__category` CHANGE `desc` `desc` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `download__comments`
--
ALTER TABLE `download__comments` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `download__comments` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__comments` CHANGE `reply` `reply` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__comments` CHANGE `attributes` `attributes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `download__files`
--
ALTER TABLE `download__files` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `download__files` CHANGE `dir` `dir` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__files` CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__files` CHANGE `rus_name` `rus_name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__files` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__files` CHANGE `rate` `rate` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `download__files` CHANGE `about` `about` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__files` CHANGE `desc` `desc` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `download__more`
--
ALTER TABLE `download__more` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `download__more` CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `download__more` CHANGE `rus_name` `rus_name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

-- ///////////////////////////////////////////////////////////////////////////////////
-- Конвертация таблицы `forum`
--
ALTER TABLE `forum` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `forum` CHANGE `type` `type` CHAR(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `forum` CHANGE `from` `from` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `forum` CHANGE `soft` `soft` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `forum` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `forum` CHANGE `close_who` `close_who` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `forum` CHANGE `edit` `edit` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `forum` CHANGE `curators` `curators` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `guest`
--
ALTER TABLE `guest` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `guest` CHANGE `name` `name` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `guest` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `guest` CHANGE `admin` `admin` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `guest` CHANGE `otvet` `otvet` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `guest` CHANGE `edit_who` `edit_who` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `karma_users`
--
ALTER TABLE `karma_users` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `karma_users` CHANGE `name` `name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `karma_users` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `library_cats`
--
ALTER TABLE `library_cats` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `library_cats` CHANGE `name` `name` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `library_cats` CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `library_texts`
--
ALTER TABLE `library_texts` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `library_texts` CHANGE `text` `text` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `library_texts` CHANGE `name` `name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `library_texts` CHANGE `announce` `announce` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `library_texts` CHANGE `uploader` `uploader` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `library_tags`
--
ALTER TABLE `library_tags` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `library_tags` CHANGE `tag_name` `tag_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

--
-- Конвертация таблицы `cms_library_comments`
--
ALTER TABLE `cms_library_comments` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `cms_library_comments` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_library_comments` CHANGE `reply` `reply` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `cms_library_comments` CHANGE `attributes` `attributes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `cms_library_rating`
--
ALTER TABLE `cms_library_rating` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Конвертация таблицы `news`
--
ALTER TABLE `news` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `news` CHANGE `avt` `avt` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `news` CHANGE `name` `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `news` CHANGE `text` `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

--
-- Конвертация таблицы `users`
--
ALTER TABLE `users` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `users` CHANGE `name` `name` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `name_lat` `name_lat` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `password` `password` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `imname` `imname` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `sex` `sex` VARCHAR(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `mail` `mail` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `skype` `skype` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `jabber` `jabber` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `www` `www` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `about` `about` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `live` `live` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `mibile` `mibile` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `status` `status` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `browser` `browser` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `regadm` `regadm` VARCHAR(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `rest_code` `rest_code` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `place` `place` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
ALTER TABLE `users` CHANGE `set_user` `set_user` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `set_forum` `set_forum` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `set_mail` `set_mail` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `smileys` `smileys` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
