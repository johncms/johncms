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
  `id`             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cat_id`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `text`           MEDIUMTEXT       NOT NULL,
  `name`           VARCHAR(255)     NOT NULL DEFAULT '',
  `announce`       TEXT,
  `uploader`       VARCHAR(100)     NOT NULL DEFAULT '',
  `uploader_id`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `count_views`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `premod`         TINYINT(1)       NOT NULL DEFAULT '0',
  `comments`       TINYINT(1)       NOT NULL DEFAULT '0',
  `count_comments` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `time`           INT(10) UNSIGNED NOT NULL DEFAULT '0',
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
