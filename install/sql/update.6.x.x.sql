DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `cms_settings`;
DROP TABLE IF EXISTS `download`;

ALTER TABLE `users` CHANGE `imname` `imname` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `library_texts` CHANGE `count_comments` `comm_count` INT(11) UNSIGNED NOT NULL DEFAULT '0';

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
  DEFAULT CHARSET = utf8;

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
  DEFAULT CHARSET = utf8;

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
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

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
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

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
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;
