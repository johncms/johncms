--
-- Обновляемся с 4.0.0
--
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
  `session_id`   CHAR(32)             NOT NULL DEFAULT '',
  `ip`           BIGINT(11)           NOT NULL DEFAULT '0',
  `ip_via_proxy` BIGINT(11)           NOT NULL DEFAULT '0',
  `browser`      VARCHAR(255)         NOT NULL DEFAULT '',
  `lastdate`     INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `sestime`      INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `views`        INT(10) UNSIGNED     NOT NULL DEFAULT '0',
  `movings`      SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  `place`        VARCHAR(100)         NOT NULL DEFAULT '',
  PRIMARY KEY (`session_id`),
  KEY `lastdate` (`lastdate`),
  KEY `place` (`place`(10))
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

ALTER TABLE `cms_album_files` ADD `unread_comments` BOOLEAN NOT NULL DEFAULT '0';

ALTER TABLE `forum` CHANGE `ip` `ip_old` TEXT NOT NULL;
ALTER TABLE `forum` ADD `ip` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `text`;
ALTER TABLE `forum` ADD `ip_via_proxy` BIGINT( 11 ) NOT NULL DEFAULT '0' AFTER `ip`;
ALTER TABLE `forum` ADD `curators` text NOT NULL;




--
-- Обновляемся с 4.5.1
--
DROP TABLE IF EXISTS `cms_contact`;
CREATE TABLE IF NOT EXISTS `cms_contact` (
  `id`      INT(10)    UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10)    UNSIGNED NOT NULL DEFAULT '0',
  `from_id` INT(10)    UNSIGNED NOT NULL DEFAULT '0',
  `time`    INT(10)    UNSIGNED NOT NULL DEFAULT '0',
  `type`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `friends` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `ban`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `man`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_user` (`user_id`, `from_id`),
  KEY `time` (`time`),
  KEY `ban` (`ban`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_mail`
--
DROP TABLE IF EXISTS `privat`;
DROP TABLE IF EXISTS `cms_mail`;
CREATE TABLE IF NOT EXISTS `cms_mail` (
  `id`        INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `from_id`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `text`      TEXT                NOT NULL,
  `time`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `read`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `sys`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `delete`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `file_name` VARCHAR(100)        NOT NULL DEFAULT '',
  `count`     INT(10)             NOT NULL DEFAULT '0',
  `size`      INT(10)             NOT NULL DEFAULT '0',
  `them`      VARCHAR(100)        NOT NULL DEFAULT '',
  `spam`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `from_id` (`from_id`),
  KEY `time` (`time`),
  KEY `read` (`read`),
  KEY `sys` (`sys`),
  KEY `delete` (`delete`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

INSERT INTO `cms_settings` (`key`, `val`) VALUES
  ('them_message', ''),
  ('setting_mail', ''),
  ('reg_message', '');

ALTER TABLE `users` ADD `set_mail` TINYTEXT NOT NULL AFTER `set_forum`;
ALTER TABLE  `users` CHANGE  `karma_plus`  `karma_plus` INT NOT NULL DEFAULT  '0';
ALTER TABLE  `users` CHANGE  `karma_minus`  `karma_minus` INT NOT NULL DEFAULT  '0';




--
-- Структура таблицы `library_cats`
--
DROP TABLE IF EXISTS `library_cats`;
CREATE TABLE `library_cats` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent`      INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `name`        VARCHAR(200)     NOT NULL DEFAULT '',
  `description` TEXT             NOT NULL,
  `dir`         TINYINT(1)       NOT NULL DEFAULT '0',
  `pos`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_add`    TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `library_texts`
--
DROP TABLE IF EXISTS `library_texts`;
CREATE TABLE `library_texts` (
  `id`             INT(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cat_id`         INT(10)  UNSIGNED NOT NULL DEFAULT '0',
  `text`           MEDIUMTEXT        NOT NULL,
  `name`           VARCHAR(255)      NOT NULL DEFAULT '',
  `announce`       TEXT,
  `uploader`       VARCHAR(100)      NOT NULL DEFAULT '',
  `uploader_id`    INT(10)  UNSIGNED NOT NULL DEFAULT '0',
  `count_views`    INT(10) UNSIGNED  NOT NULL DEFAULT '0',
  `premod`         TINYINT(1)        NOT NULL DEFAULT '0',
  `comments`       TINYINT(1)        NOT NULL DEFAULT '0',
  `count_comments` INT(10)  UNSIGNED NOT NULL DEFAULT '0',
  `time`           INT(10) UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `text` (`text`, `name`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `library_tags`
--
DROP TABLE IF EXISTS `library_tags`;
CREATE TABLE `library_tags` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lib_text_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `tag_name`    VARCHAR(255)     NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `lib_text_id` (`lib_text_id`),
  KEY `tag_name` (`tag_name`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_library_comments`
--
DROP TABLE IF EXISTS `cms_library_comments`;
CREATE TABLE `cms_library_comments` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sub_id`     INT(11) UNSIGNED NOT NULL,
  `time`       INT(11)          NOT NULL,
  `user_id`    INT(11) UNSIGNED NOT NULL,
  `text`       TEXT             NOT NULL,
  `reply`      TEXT,
  `attributes` TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_id` (`sub_id`),
  KEY `user_id` (`user_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_library_rating`
--
DROP TABLE IF EXISTS `cms_library_rating`;
CREATE TABLE IF NOT EXISTS `cms_library_rating` (
  `id`      INT(11)    NOT NULL AUTO_INCREMENT,
  `user_id` INT(11)    NOT NULL,
  `st_id`   INT(11)    NOT NULL,
  `point`   TINYINT(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`, `st_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;
