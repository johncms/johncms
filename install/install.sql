--
-- Структура таблицы `cms_ads`
--
DROP TABLE IF EXISTS `cms_ads`;
CREATE TABLE `cms_ads` (
  `id`         INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `type`       TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `view`       TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `layout`     TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `count`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `count_link` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `name`       TEXT                NOT NULL,
  `link`       TEXT                NOT NULL,
  `to`         INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `color`      VARCHAR(10)         NOT NULL DEFAULT '',
  `time`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `day`        INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `mesto`      TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `bold`       TINYINT(1)          NOT NULL DEFAULT '0',
  `italic`     TINYINT(1)          NOT NULL DEFAULT '0',
  `underline`  TINYINT(1)          NOT NULL DEFAULT '0',
  `show`       TINYINT(1)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_album_cat`
--
DROP TABLE IF EXISTS `cms_album_cat`;
CREATE TABLE `cms_album_cat` (
  `id`          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `sort`        INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `name`        VARCHAR(40)         NOT NULL DEFAULT '',
  `description` TEXT                NOT NULL,
  `password`    VARCHAR(20)         NOT NULL DEFAULT '',
  `access`      TINYINT(4) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `access` (`access`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_album_comments`
--
DROP TABLE IF EXISTS `cms_album_comments`;
CREATE TABLE `cms_album_comments` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sub_id`     INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `time`       INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
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
-- Структура таблицы `cms_album_downloads`
--
DROP TABLE IF EXISTS `cms_album_downloads`;
CREATE TABLE `cms_album_downloads` (
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `file_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `time`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`, `file_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_album_files`
--
DROP TABLE IF EXISTS `cms_album_files`;
CREATE TABLE `cms_album_files` (
  `id`              INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`         INT(10) UNSIGNED    NOT NULL,
  `album_id`        INT(10) UNSIGNED    NOT NULL,
  `description`     TEXT                NOT NULL,
  `img_name`        VARCHAR(100)        NOT NULL DEFAULT '',
  `tmb_name`        VARCHAR(100)        NOT NULL DEFAULT '',
  `time`            INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `comments`        TINYINT(1)          NOT NULL DEFAULT '1',
  `comm_count`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `access`          TINYINT(4) UNSIGNED NOT NULL DEFAULT '0',
  `vote_plus`       INT(11)             NOT NULL DEFAULT '0',
  `vote_minus`      INT(11)             NOT NULL DEFAULT '0',
  `views`           INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `downloads`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `unread_comments` TINYINT(1)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `album_id` (`album_id`),
  KEY `access` (`access`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_album_views`
--
DROP TABLE IF EXISTS `cms_album_views`;
CREATE TABLE `cms_album_views` (
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `file_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `time`    INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`, `file_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_album_votes`
--
DROP TABLE IF EXISTS `cms_album_votes`;
CREATE TABLE `cms_album_votes` (
  `id`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `file_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `vote`    TINYINT(2)       NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `file_id` (`file_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_ban_ip`
--
DROP TABLE IF EXISTS `cms_ban_ip`;
CREATE TABLE `cms_ban_ip` (
  `id`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip1`      BIGINT(11)       NOT NULL DEFAULT '0',
  `ip2`      BIGINT(11)       NOT NULL DEFAULT '0',
  `ban_type` TINYINT(4)       NOT NULL DEFAULT '0',
  `link`     VARCHAR(100)     NOT NULL,
  `who`      VARCHAR(25)      NOT NULL,
  `reason`   TEXT             NOT NULL,
  `date`     INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip1` (`ip1`),
  UNIQUE KEY `ip2` (`ip2`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_ban_users`
--
DROP TABLE IF EXISTS `cms_ban_users`;
CREATE TABLE `cms_ban_users` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)          NOT NULL DEFAULT '0',
  `ban_time`   INT(11)          NOT NULL DEFAULT '0',
  `ban_while`  INT(11)          NOT NULL DEFAULT '0',
  `ban_type`   TINYINT(4)       NOT NULL DEFAULT '1',
  `ban_who`    VARCHAR(30)      NOT NULL DEFAULT '',
  `ban_ref`    INT(11)          NOT NULL DEFAULT '0',
  `ban_reason` TEXT             NOT NULL,
  `ban_raz`    VARCHAR(30)      NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ban_time` (`ban_time`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_contact`
--
DROP TABLE IF EXISTS `cms_contact`;
CREATE TABLE IF NOT EXISTS `cms_contact` (
  `id`      INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `from_id` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `time`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
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
-- Структура таблицы `cms_counters`
--
DROP TABLE IF EXISTS `cms_counters`;
CREATE TABLE `cms_counters` (
  `id`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sort`   INT(10)          NOT NULL DEFAULT '1',
  `name`   VARCHAR(30)      NOT NULL,
  `link1`  TEXT             NOT NULL,
  `link2`  TEXT             NOT NULL,
  `mode`   TINYINT(4)       NOT NULL DEFAULT '1',
  `switch` TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_forum_files`
--
DROP TABLE IF EXISTS `cms_forum_files`;
CREATE TABLE `cms_forum_files` (
  `id`       INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cat`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `subcat`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `topic`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `post`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `time`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `filename` TEXT                NOT NULL,
  `filetype` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `dlcount`  INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `del`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cat` (`cat`),
  KEY `subcat` (`subcat`),
  KEY `topic` (`topic`),
  KEY `post` (`post`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_forum_rdm`
--
DROP TABLE IF EXISTS `cms_forum_rdm`;
CREATE TABLE `cms_forum_rdm` (
  `topic_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id`  INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `time`     INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`, `user_id`),
  KEY `time` (`time`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_forum_vote`
--
DROP TABLE IF EXISTS `cms_forum_vote`;
CREATE TABLE `cms_forum_vote` (
  `id`    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`  INT(2)           NOT NULL DEFAULT '0',
  `time`  INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `topic` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `name`  VARCHAR(200)     NOT NULL,
  `count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `topic` (`topic`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_forum_vote_users`
--
DROP TABLE IF EXISTS `cms_forum_vote_users`;
CREATE TABLE `cms_forum_vote_users` (
  `id`    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user`  INT(11)          NOT NULL DEFAULT '0',
  `topic` INT(11)          NOT NULL,
  `vote`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `topic` (`topic`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_mail`
--
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

--
-- Структура таблицы `cms_sessions`
--
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

--
-- Структура таблицы `cms_settings`
--
DROP TABLE IF EXISTS `cms_settings`;
CREATE TABLE `cms_settings` (
  `key` TINYTEXT NOT NULL,
  `val` TEXT     NOT NULL,
  PRIMARY KEY (`key`(30))
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

INSERT INTO `cms_settings` (`key`, `val`) VALUES
  ('active', '1'),
  ('admp', 'panel'),
  ('antiflood', 'a:5:{s:4:"mode";i:2;s:3:"day";i:10;s:5:"night";i:30;s:7:"dayfrom";i:10;s:5:"dayto";i:22;}'),
  ('clean_time', '1'),
  ('copyright', 'Powered by JohnCMS'),
  ('email', ''),
  ('flsz', '4000'),
  ('gzip', '1'),
  ('homeurl', ''),
  ('karma', 'a:6:{s:12:"karma_points";i:5;s:10:"karma_time";i:86400;s:5:"forum";i:20;s:4:"time";i:0;s:2:"on";i:1;s:3:"adm";i:0;}'),
  ('lng', 'en'),
  ('mod_reg', '2'),
  ('mod_forum', '2'),
  ('mod_guest', '2'),
  ('mod_lib', '2'),
  ('mod_gal', '2'),
  ('mod_down_comm', '1'),
  ('mod_down', '2'),
  ('mod_lib_comm', '1'),
  ('mod_gal_comm', '1'),
  ('meta_desc', 'Powered by JohnCMS http://johncms.com'),
  ('meta_key', 'johncms'),
  ('news', 'a:8:{s:4:"view";i:1;s:4:"size";i:200;s:8:"quantity";i:5;s:4:"days";i:3;s:6:"breaks";i:1;s:7:"smileys";i:1;s:4:"tags";i:1;s:3:"kom";i:1;}'),
  ('reg_message', ''),
  ('setting_mail', ''),
  ('skindef', 'default'),
  ('them_message', ''),
  ('timeshift', '0'),
  ('site_access', '2');

--
-- Структура таблицы `cms_users_data`
--
DROP TABLE IF EXISTS `cms_users_data`;
CREATE TABLE `cms_users_data` (
  `id`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `key`     VARCHAR(30)      NOT NULL DEFAULT '',
  `val`     TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `key` (`key`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `cms_users_guestbook`
--
DROP TABLE IF EXISTS `cms_users_guestbook`;
CREATE TABLE `cms_users_guestbook` (
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
-- Структура таблицы `cms_users_iphistory`
--
DROP TABLE IF EXISTS `cms_users_iphistory`;
CREATE TABLE `cms_users_iphistory` (
  `id`           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT(10) UNSIGNED    NOT NULL,
  `ip`           BIGINT(11)          NOT NULL DEFAULT '0',
  `ip_via_proxy` BIGINT(11)          NOT NULL DEFAULT '0',
  `time`         INT(10) UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`ip`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `download`
--
DROP TABLE IF EXISTS `download`;
CREATE TABLE `download` (
  `id`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refid`  INT(11)          NOT NULL DEFAULT '0',
  `adres`  TEXT             NOT NULL,
  `time`   INT(11)          NOT NULL DEFAULT '0',
  `name`   TEXT             NOT NULL,
  `type`   VARCHAR(4)       NOT NULL DEFAULT '',
  `avtor`  VARCHAR(25)      NOT NULL DEFAULT '',
  `ip`     TEXT             NOT NULL,
  `soft`   TEXT             NOT NULL,
  `text`   TEXT             NOT NULL,
  `screen` TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `refid` (`refid`),
  KEY `time` (`time`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `forum`
--
DROP TABLE IF EXISTS `forum`;
CREATE TABLE `forum` (
  `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refid`        INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `type`         CHAR(1)          NOT NULL DEFAULT '',
  `time`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id`      INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `from`         VARCHAR(25)      NOT NULL DEFAULT '',
  `realid`       INT(3)           NOT NULL DEFAULT '0',
  `ip`           BIGINT(11)       NOT NULL DEFAULT '0',
  `ip_via_proxy` BIGINT(11)       NOT NULL DEFAULT '0',
  `soft`         TEXT             NOT NULL,
  `text`         TEXT             NOT NULL,
  `close`        TINYINT(1)       NOT NULL DEFAULT '0',
  `close_who`    VARCHAR(25)      NOT NULL DEFAULT '',
  `vip`          TINYINT(1)       NOT NULL DEFAULT '0',
  `edit`         TEXT             NOT NULL,
  `tedit`        INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `kedit`        INT(2) UNSIGNED  NOT NULL DEFAULT '0',
  `curators`     TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `close` (`close`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `text` (`text`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `gallery`
--
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id`    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `refid` INT(11)          NOT NULL DEFAULT '0',
  `time`  INT(11)          NOT NULL DEFAULT '0',
  `type`  VARCHAR(2)       NOT NULL DEFAULT '',
  `avtor` VARCHAR(25)      NOT NULL DEFAULT '',
  `text`  TEXT             NOT NULL,
  `name`  TEXT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  KEY `type` (`type`),
  KEY `time` (`time`),
  KEY `avtor` (`avtor`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `guest`
--
DROP TABLE IF EXISTS `guest`;
CREATE TABLE `guest` (
  `id`         INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `adm`        TINYINT(1)          NOT NULL DEFAULT '0',
  `time`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `user_id`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `name`       VARCHAR(25)         NOT NULL DEFAULT '',
  `text`       TEXT                NOT NULL,
  `ip`         BIGINT(11)          NOT NULL DEFAULT '0',
  `browser`    TINYTEXT            NOT NULL,
  `admin`      VARCHAR(25)         NOT NULL DEFAULT '',
  `otvet`      TEXT                NOT NULL,
  `otime`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `edit_who`   VARCHAR(20)         NOT NULL DEFAULT '',
  `edit_time`  INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `edit_count` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `ip` (`ip`),
  KEY `adm` (`adm`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `karma_users`
--
DROP TABLE IF EXISTS `karma_users`;
CREATE TABLE `karma_users` (
  `id`         INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `name`       VARCHAR(50)         NOT NULL DEFAULT '',
  `karma_user` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `points`     TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `type`       TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `time`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `text`       TEXT                NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `karma_user` (`karma_user`),
  KEY `type` (`type`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

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
  `tag_name`    VARCHAR(127)     NOT NULL DEFAULT '',
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

--
-- Структура таблицы `news`
--
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `time` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `avt`  VARCHAR(25)      NOT NULL DEFAULT '',
  `name` TEXT             NOT NULL,
  `text` TEXT             NOT NULL,
  `kom`  INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- Структура таблицы `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`            INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(25)         NOT NULL DEFAULT '',
  `name_lat`      VARCHAR(40)         NOT NULL DEFAULT '',
  `password`      VARCHAR(32)         NOT NULL DEFAULT '',
  `rights`        TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `failed_login`  TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `imname`        VARCHAR(50)         NOT NULL DEFAULT '',
  `sex`           VARCHAR(2)          NOT NULL DEFAULT '',
  `komm`          INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `postforum`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `postguest`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `yearofbirth`   INT(4)              NOT NULL DEFAULT '0',
  `datereg`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastdate`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `mail`          VARCHAR(50)         NOT NULL DEFAULT '',
  `icq`           INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `skype`         VARCHAR(50)         NOT NULL DEFAULT '',
  `jabber`        VARCHAR(50)         NOT NULL DEFAULT '',
  `www`           VARCHAR(50)         NOT NULL DEFAULT '',
  `about`         TEXT                NOT NULL,
  `live`          VARCHAR(100)        NOT NULL DEFAULT '',
  `mibile`        VARCHAR(50)         NOT NULL DEFAULT '',
  `status`        VARCHAR(100)        NOT NULL DEFAULT '',
  `ip`            BIGINT(11)          NOT NULL DEFAULT '0',
  `ip_via_proxy`  BIGINT(11)          NOT NULL DEFAULT '0',
  `browser`       TEXT                NOT NULL,
  `preg`          TINYINT(1)          NOT NULL DEFAULT '0',
  `regadm`        VARCHAR(25)         NOT NULL DEFAULT '',
  `mailvis`       TINYINT(1)          NOT NULL DEFAULT '0',
  `dayb`          INT(2)              NOT NULL DEFAULT '0',
  `monthb`        INT(2)              NOT NULL DEFAULT '0',
  `sestime`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `total_on_site` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `lastpost`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `rest_code`     VARCHAR(32)         NOT NULL DEFAULT '',
  `rest_time`     INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `movings`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `place`         VARCHAR(30)         NOT NULL DEFAULT '',
  `set_user`      TEXT                NOT NULL,
  `set_forum`     TEXT                NOT NULL,
  `set_mail`      TEXT                NOT NULL,
  `karma_plus`    INT(11)             NOT NULL DEFAULT '0',
  `karma_minus`   INT(11)             NOT NULL DEFAULT '0',
  `karma_time`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `karma_off`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `comm_count`    INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `comm_old`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  `smileys`       TEXT                NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name_lat` (`name_lat`),
  KEY `lastdate` (`lastdate`),
  KEY `place` (`place`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;
