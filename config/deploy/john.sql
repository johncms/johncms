-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 27 2022 г., 07:19
-- Версия сервера: 8.0.27
-- Версия PHP: 8.0.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `john9`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cms_ads`
--

CREATE TABLE `cms_ads` (
  `id` int UNSIGNED NOT NULL,
  `type` tinyint UNSIGNED DEFAULT '0',
  `view` tinyint UNSIGNED DEFAULT '0',
  `layout` tinyint UNSIGNED DEFAULT '0',
  `count` int UNSIGNED DEFAULT '0',
  `count_link` int UNSIGNED DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` int UNSIGNED DEFAULT '0',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `time` int UNSIGNED DEFAULT '0',
  `day` int UNSIGNED DEFAULT '0',
  `mesto` tinyint UNSIGNED DEFAULT '0',
  `bold` tinyint UNSIGNED DEFAULT '0',
  `italic` tinyint UNSIGNED DEFAULT '0',
  `underline` tinyint UNSIGNED DEFAULT '0',
  `show` tinyint UNSIGNED DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_cat`
--

CREATE TABLE `cms_album_cat` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_comments`
--

CREATE TABLE `cms_album_comments` (
  `id` int UNSIGNED NOT NULL,
  `sub_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_downloads`
--

CREATE TABLE `cms_album_downloads` (
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `file_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_files`
--

CREATE TABLE `cms_album_files` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `album_id` int UNSIGNED NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `img_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tmb_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `comments` tinyint(1) NOT NULL DEFAULT '1',
  `comm_count` int UNSIGNED NOT NULL DEFAULT '0',
  `access` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `vote_plus` int NOT NULL DEFAULT '0',
  `vote_minus` int NOT NULL DEFAULT '0',
  `views` int UNSIGNED NOT NULL DEFAULT '0',
  `downloads` int UNSIGNED NOT NULL DEFAULT '0',
  `unread_comments` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_views`
--

CREATE TABLE `cms_album_views` (
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `file_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_album_votes`
--

CREATE TABLE `cms_album_votes` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `file_id` int UNSIGNED NOT NULL DEFAULT '0',
  `vote` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_ban_ip`
--

CREATE TABLE `cms_ban_ip` (
  `id` int UNSIGNED NOT NULL,
  `ip1` bigint NOT NULL DEFAULT '0',
  `ip2` bigint NOT NULL DEFAULT '0',
  `ban_type` tinyint NOT NULL DEFAULT '0',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_ban_users`
--

CREATE TABLE `cms_ban_users` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL DEFAULT '0',
  `ban_time` int NOT NULL DEFAULT '0',
  `ban_while` int NOT NULL DEFAULT '0',
  `ban_type` tinyint NOT NULL DEFAULT '1',
  `ban_who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ban_ref` int NOT NULL DEFAULT '0',
  `ban_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ban_raz` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_contact`
--

CREATE TABLE `cms_contact` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `from_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `type` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `friends` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ban` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `man` tinyint UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_counters`
--

CREATE TABLE `cms_counters` (
  `id` int UNSIGNED NOT NULL,
  `sort` int NOT NULL DEFAULT '1',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `link1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` tinyint NOT NULL DEFAULT '1',
  `switch` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_forum_files`
--

CREATE TABLE `cms_forum_files` (
  `id` int UNSIGNED NOT NULL,
  `cat` int UNSIGNED NOT NULL DEFAULT '0',
  `subcat` int UNSIGNED NOT NULL DEFAULT '0',
  `topic` int UNSIGNED NOT NULL DEFAULT '0',
  `post` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `filename` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filetype` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `dlcount` int UNSIGNED NOT NULL DEFAULT '0',
  `del` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_forum_rdm`
--

CREATE TABLE `cms_forum_rdm` (
  `topic_id` int UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_forum_vote`
--

CREATE TABLE `cms_forum_vote` (
  `id` int UNSIGNED NOT NULL,
  `type` int NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `topic` int UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_forum_vote_users`
--

CREATE TABLE `cms_forum_vote_users` (
  `id` int UNSIGNED NOT NULL,
  `user` int NOT NULL DEFAULT '0',
  `topic` int NOT NULL,
  `vote` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_library_comments`
--

CREATE TABLE `cms_library_comments` (
  `id` int UNSIGNED NOT NULL,
  `sub_id` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int NOT NULL DEFAULT '0',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_library_rating`
--

CREATE TABLE `cms_library_rating` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `st_id` int UNSIGNED NOT NULL,
  `point` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_mail`
--

CREATE TABLE `cms_mail` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `from_id` int UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `sys` tinyint(1) NOT NULL DEFAULT '0',
  `delete` int UNSIGNED NOT NULL DEFAULT '0',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `count` int NOT NULL DEFAULT '0',
  `size` int NOT NULL DEFAULT '0',
  `them` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `spam` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_sessions`
--

CREATE TABLE `cms_sessions` (
  `session_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastdate` int UNSIGNED NOT NULL DEFAULT '0',
  `sestime` int UNSIGNED NOT NULL DEFAULT '0',
  `views` int UNSIGNED NOT NULL DEFAULT '0',
  `movings` smallint UNSIGNED NOT NULL DEFAULT '0',
  `place` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cms_sessions`
--

INSERT INTO `cms_sessions` (`session_id`, `ip`, `ip_via_proxy`, `browser`, `lastdate`, `sestime`, `views`, `movings`, `place`) VALUES
('22b12117c0e888925a6537bc8ad9e59e', 1862754475, 0, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_0_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36', 1648838542, 1648838542, 1, 1, '/'),
('505026539fd24d278c5e569667aba6d6', 3515687808, 0, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36', 1648835169, 1648835169, 1, 1, '/'),
('c1e9771d61ac792ec583a5ce42f815a2', 1862755344, 0, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_0_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36', 1648835172, 1648835172, 1, 1, '/'),
('c990e3710a379fabfd833a0433c6fe00', 136249923, 0, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36', 1648834910, 1648834910, 1, 1, '/'),
('f2cc3597b4f218a045af2a717e3e5602', 3451483595, 0, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36', 1648838537, 1648838537, 1, 1, '/');

-- --------------------------------------------------------

--
-- Структура таблицы `cms_users_data`
--

CREATE TABLE `cms_users_data` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `val` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_users_guestbook`
--

CREATE TABLE `cms_users_guestbook` (
  `id` int UNSIGNED NOT NULL,
  `sub_id` int UNSIGNED NOT NULL,
  `time` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cms_users_iphistory`
--

CREATE TABLE `cms_users_iphistory` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `download__bookmark`
--

CREATE TABLE `download__bookmark` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `file_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `download__category`
--

CREATE TABLE `download__category` (
  `id` int UNSIGNED NOT NULL,
  `refid` int UNSIGNED NOT NULL DEFAULT '0',
  `dir` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` int UNSIGNED NOT NULL DEFAULT '0',
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` int UNSIGNED NOT NULL DEFAULT '0',
  `desc` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `download__category`
--

INSERT INTO `download__category` (`id`, `refid`, `dir`, `sort`, `name`, `total`, `rus_name`, `text`, `field`, `desc`) VALUES
(1, 0, 'upload/downloads/files/Java', 1624019316, 'Java', 5, 'Java', 'jar', 1, 'Описание папки не знаю что тут писать. игры как игры.');

-- --------------------------------------------------------

--
-- Структура таблицы `download__comments`
--

CREATE TABLE `download__comments` (
  `id` int UNSIGNED NOT NULL,
  `sub_id` int UNSIGNED NOT NULL,
  `time` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `download__files`
--

CREATE TABLE `download__files` (
  `id` int UNSIGNED NOT NULL,
  `refid` int UNSIGNED NOT NULL DEFAULT '0',
  `dir` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` int UNSIGNED NOT NULL DEFAULT '0',
  `rate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0|0',
  `about` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `comm_count` int UNSIGNED NOT NULL DEFAULT '0',
  `updated` int UNSIGNED DEFAULT NULL,
  `tag` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jadkey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `md5hash` binary(16) DEFAULT NULL,
  `online` int UNSIGNED NOT NULL DEFAULT '0',
  `3d` int UNSIGNED NOT NULL DEFAULT '0',
  `bluetooth` int UNSIGNED NOT NULL DEFAULT '0',
  `vendor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `mirrors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `download__files`
--

INSERT INTO `download__files` (`id`, `refid`, `dir`, `time`, `name`, `type`, `user_id`, `rus_name`, `text`, `field`, `rate`, `about`, `desc`, `comm_count`, `updated`, `tag`, `jadkey`, `md5hash`, `online`, `3d`, `bluetooth`, `vendor`, `mirrors`) VALUES
(1, 1, 'upload/downloads/files/Java', 1624023421, 'Galaxy_on_fire_2.jar', 2, 1, 'filename-GOF2s60v3', ' 240x320 : S60 3ed Rus', 10, '0|0', 'Добро пожаловать в мир Galaxy On Fire 2, долгожданное продолжение многократно награжденной космической action-игры для твоего мобильного телефона!\r\nИсследуй захватывающий мир Вселенной и открой для себя все ее тайны и опасности в игре с потрясающей трехмерной графикой и беспрецедентной глубиной геймплея!\r\nГерой первого эпизода игры, Кейт Т. Максвелл, дрейфует во времени и пространстве после сбоя гипердвигателя его космического корабля. Он просыпается 35 земных лет спустя в изменившейся Вселенной: неизвестный враг через временные тоннели проникает в Галактику и атакует солнечные системы!\r\nСудьба Галактики в твоих руках!\r\n- Спасибо TokAreVisH за присланные версии.\r\n- В моде Motya новые корабли , схемы и вооружения.\r\n- спс sinaz за лекарство\r\n- спс Intеr за версию для S40.\r\n\r\nКоротко о файле:\r\nВ игре вся графика как в оригинале(планеты, солнца, текстуры немного измененные) в хорошем качестве!\r\nВ игре оставлены только 11 кораблей!\r\n\r\nИз игры убраны\r\n00 блики солнца\r\n01 туманности\r\n02 прозрачный фон в ангаре и при диалогах\r\n03 убраны часть кораблей(вынужденая операция)\r\n04 изменены станции(стали меньше в размерах) это я сделал ещё в ранних версиях.\r\nВобщем самая оптимальная версия и графика вас приятно удивит!\r\nПриятной игры!\r\n2015г.', '', 0, 1624023425, 'Online,3d', 'jadddkey', NULL, 1, 1, 1, 'FishLabs', 'http://oldfag.top/downloads/?act=view&id=4164'),
(2, 1, 'upload/downloads/files/Java', 1624024821, 'saveus.jar', 2, 1, 'file name', 'Скачать', 4, '0|0', 'dffdsf', '', 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL),
(3, 1, 'upload/downloads/files/Java', 1624030837, '3245.jar', 2, 1, '2345', 'Скачать', 5, '0|0', '2345', '', 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL),
(4, 1, 'upload/downloads/files/Java', 1624032294, '32.jar', 2, 1, '4545', 'Скачать', 5, '0|0', '', '', 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL),
(5, 1, 'upload/downloads/files/Java', 1624032667, '21q3123.jar', 2, 1, '123123', 'Скачать', 4, '0|0', '', '', 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `download__more`
--

CREATE TABLE `download__more` (
  `id` int UNSIGNED NOT NULL,
  `refid` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int UNSIGNED NOT NULL DEFAULT '0',
  `moderate` int UNSIGNED NOT NULL DEFAULT '0',
  `jadkey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `md5hash` binary(16) DEFAULT NULL,
  `userid` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `download__more`
--

INSERT INTO `download__more` (`id`, `refid`, `time`, `name`, `rus_name`, `size`, `moderate`, `jadkey`, `md5hash`, `userid`) VALUES
(1, 1, 1624024685, 'file1_7868768.jar', 'Скачать дополнительный файл', 1674226, 0, NULL, 0xb28283e982066ba8131b7356d94be81d, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `email_messages`
--

CREATE TABLE `email_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `priority` int DEFAULT NULL COMMENT 'Priority of sending the message',
  `locale` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The language used for displaying the message',
  `template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template name',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT 'Event fields',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'The time when the message was sent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `forum_messages`
--

CREATE TABLE `forum_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `topic_id` int NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` int DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` bigint DEFAULT NULL,
  `ip_via_proxy` bigint DEFAULT NULL,
  `pinned` tinyint(1) DEFAULT NULL,
  `editor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edit_time` int DEFAULT NULL,
  `edit_count` int DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `forum_messages`
--

INSERT INTO `forum_messages` (`id`, `topic_id`, `text`, `date`, `user_id`, `user_name`, `user_agent`, `ip`, `ip_via_proxy`, `pinned`, `editor_name`, `edit_time`, `edit_count`, `deleted`, `deleted_by`) VALUES
(1, 1, 'Мы рады приветствовать Вас на нашем сайте :)\r\nДавайте знакомиться!', 1571257080, 1, 'admin', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `forum_sections`
--

CREATE TABLE `forum_sections` (
  `id` int UNSIGNED NOT NULL,
  `parent` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int NOT NULL DEFAULT '100',
  `access` int DEFAULT NULL,
  `section_type` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `forum_sections`
--

INSERT INTO `forum_sections` (`id`, `parent`, `name`, `description`, `meta_description`, `meta_keywords`, `sort`, `access`, `section_type`) VALUES
(1, 0, 'Общение', 'Свободное общение на любую тему', '', NULL, 1, 0, 0),
(2, 1, 'О разном', '', '', NULL, 1, 0, 1),
(3, 1, 'Знакомства', '', '', NULL, 2, 0, 1),
(4, 1, 'Жизнь ресурса', '', '', NULL, 3, 0, 1),
(5, 1, 'Новости', '', '', NULL, 4, 0, 1),
(6, 1, 'Предложения и пожелания', '', '', NULL, 5, 0, 1),
(7, 1, 'Разное', '', '', NULL, 6, 0, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `forum_topic`
--

CREATE TABLE `forum_topic` (
  `id` int UNSIGNED NOT NULL,
  `section_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `post_count` int DEFAULT NULL,
  `mod_post_count` int DEFAULT NULL,
  `last_post_date` int DEFAULT NULL,
  `last_post_author` int UNSIGNED DEFAULT NULL,
  `last_post_author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_message_id` bigint DEFAULT NULL,
  `mod_last_post_date` int DEFAULT NULL,
  `mod_last_post_author` int UNSIGNED DEFAULT NULL,
  `mod_last_post_author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mod_last_message_id` bigint DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `closed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curators` mediumtext COLLATE utf8mb4_unicode_ci,
  `pinned` tinyint(1) DEFAULT NULL,
  `has_poll` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `forum_topic`
--

INSERT INTO `forum_topic` (`id`, `section_id`, `name`, `description`, `meta_description`, `meta_keywords`, `view_count`, `user_id`, `user_name`, `created_at`, `post_count`, `mod_post_count`, `last_post_date`, `last_post_author`, `last_post_author_name`, `last_message_id`, `mod_last_post_date`, `mod_last_post_author`, `mod_last_post_author_name`, `mod_last_message_id`, `closed`, `closed_by`, `deleted`, `deleted_by`, `curators`, `pinned`, `has_poll`) VALUES
(1, 3, 'Привет всем!', '', '', NULL, 11, 1, 'admin', '2019-10-16 20:18:00', 1, 1, 1571257080, 1, 'admin', 1, 1571257080, 1, 'admin', 1, NULL, NULL, NULL, NULL, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `guest`
--

CREATE TABLE `guest` (
  `id` int UNSIGNED NOT NULL,
  `adm` tinyint(1) NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` bigint NOT NULL DEFAULT '0',
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `otvet` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `otime` int UNSIGNED NOT NULL DEFAULT '0',
  `edit_who` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `edit_time` int UNSIGNED NOT NULL DEFAULT '0',
  `edit_count` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `attached_files` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `guest`
--

INSERT INTO `guest` (`id`, `adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`, `edit_who`, `edit_time`, `edit_count`, `attached_files`) VALUES
(1, 1, 1217060516, 1, 'admin', 'Добро пожаловать в Админ Клуб!\r\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\nПростым пользователям доступ сюда закрыт.', 2130706433, 'Opera/9.51', '', '', 0, '', 0, 0, NULL),
(2, 0, 1217060536, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, 'Opera/9.51', 'admin', 'Проверка ответа Администратора', 1217064021, '', 0, 0, NULL),
(3, 0, 1217061125, 1, 'admin', 'Гостевая поддерживает полноценное форматирование текста в визуальном редакторе:<br>\n<span style=\"font-weight: bold\">жирный</span><br>\n<span style=\"font-style:italic\">курсив</span><br>\n<span style=\"text-decoration:underline\">подчеркнутый</span><br>\n<span style=\"color:red\">красный</span><br>\n<span style=\"color:green\">зеленый</span><br>\n<span style=\"color:blue\">синий</span><br>\nВставку ссылок: <a href=\"https://johncms.com\">https://johncms.com</a>, картинок, таблиц, видео и многого другого', 2130706433, 'Opera/9.51', '', '', 0, '', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `karma_users`
--

CREATE TABLE `karma_users` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `karma_user` int UNSIGNED NOT NULL DEFAULT '0',
  `points` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `type` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `library_cats`
--

CREATE TABLE `library_cats` (
  `id` int UNSIGNED NOT NULL,
  `parent` int UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dir` tinyint(1) NOT NULL DEFAULT '0',
  `pos` int UNSIGNED NOT NULL DEFAULT '0',
  `user_add` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `library_tags`
--

CREATE TABLE `library_tags` (
  `id` int UNSIGNED NOT NULL,
  `lib_text_id` int UNSIGNED NOT NULL DEFAULT '0',
  `tag_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `library_texts`
--

CREATE TABLE `library_texts` (
  `id` int UNSIGNED NOT NULL,
  `cat_id` int UNSIGNED NOT NULL DEFAULT '0',
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `announce` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploader` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uploader_id` int UNSIGNED NOT NULL DEFAULT '0',
  `count_views` int UNSIGNED NOT NULL DEFAULT '0',
  `premod` tinyint(1) NOT NULL DEFAULT '0',
  `comments` tinyint(1) NOT NULL DEFAULT '0',
  `comm_count` int UNSIGNED NOT NULL DEFAULT '0',
  `time` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `news_articles`
--

CREATE TABLE `news_articles` (
  `id` int UNSIGNED NOT NULL,
  `section_id` int UNSIGNED DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `active_from` datetime DEFAULT NULL,
  `active_to` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `preview_text` text COLLATE utf8mb4_unicode_ci,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_count` int DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `attached_files` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `news_comments`
--

CREATE TABLE `news_comments` (
  `id` int UNSIGNED NOT NULL,
  `article_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `attached_files` longtext COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `news_search_index`
--

CREATE TABLE `news_search_index` (
  `id` int UNSIGNED NOT NULL,
  `article_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `news_sections`
--

CREATE TABLE `news_sections` (
  `id` int UNSIGNED NOT NULL,
  `parent` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `news_votes`
--

CREATE TABLE `news_votes` (
  `id` int UNSIGNED NOT NULL,
  `article_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `vote` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `id` int UNSIGNED NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module name',
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Event type',
  `user_id` int UNSIGNED NOT NULL COMMENT 'User identifier',
  `sender_id` int UNSIGNED DEFAULT NULL COMMENT 'Sender identifier',
  `entity_id` int UNSIGNED DEFAULT NULL COMMENT 'Entity identifier',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT 'Event fields',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Read date',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name_lat` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rights` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `failed_login` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `imname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sex` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `komm` int UNSIGNED NOT NULL DEFAULT '0',
  `postforum` int UNSIGNED NOT NULL DEFAULT '0',
  `postguest` int UNSIGNED NOT NULL DEFAULT '0',
  `yearofbirth` int UNSIGNED NOT NULL DEFAULT '0',
  `datereg` int UNSIGNED NOT NULL DEFAULT '0',
  `lastdate` int UNSIGNED NOT NULL DEFAULT '0',
  `mail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icq` int UNSIGNED NOT NULL DEFAULT '0',
  `skype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jabber` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `www` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `about` text COLLATE utf8mb4_unicode_ci,
  `live` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mibile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` bigint NOT NULL DEFAULT '0',
  `ip_via_proxy` bigint NOT NULL DEFAULT '0',
  `browser` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `preg` tinyint(1) NOT NULL DEFAULT '0',
  `regadm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mailvis` tinyint(1) NOT NULL DEFAULT '0',
  `dayb` int NOT NULL DEFAULT '0',
  `monthb` int NOT NULL DEFAULT '0',
  `sestime` int UNSIGNED NOT NULL DEFAULT '0',
  `total_on_site` int UNSIGNED NOT NULL DEFAULT '0',
  `lastpost` int UNSIGNED NOT NULL DEFAULT '0',
  `rest_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rest_time` int UNSIGNED NOT NULL DEFAULT '0',
  `movings` int UNSIGNED NOT NULL DEFAULT '0',
  `place` text COLLATE utf8mb4_unicode_ci,
  `set_user` text COLLATE utf8mb4_unicode_ci,
  `set_forum` text COLLATE utf8mb4_unicode_ci,
  `set_mail` text COLLATE utf8mb4_unicode_ci,
  `karma_plus` int NOT NULL DEFAULT '0',
  `karma_minus` int NOT NULL DEFAULT '0',
  `karma_time` int UNSIGNED NOT NULL DEFAULT '0',
  `karma_off` tinyint(1) NOT NULL DEFAULT '0',
  `comm_count` int UNSIGNED NOT NULL DEFAULT '0',
  `comm_old` int UNSIGNED NOT NULL DEFAULT '0',
  `smileys` text COLLATE utf8mb4_unicode_ci,
  `notification_settings` text COLLATE utf8mb4_unicode_ci,
  `email_confirmed` tinyint(1) DEFAULT NULL,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `name_lat`, `password`, `rights`, `failed_login`, `imname`, `sex`, `komm`, `postforum`, `postguest`, `yearofbirth`, `datereg`, `lastdate`, `mail`, `icq`, `skype`, `jabber`, `www`, `about`, `live`, `mibile`, `status`, `ip`, `ip_via_proxy`, `browser`, `preg`, `regadm`, `mailvis`, `dayb`, `monthb`, `sestime`, `total_on_site`, `lastpost`, `rest_code`, `rest_time`, `movings`, `place`, `set_user`, `set_forum`, `set_mail`, `karma_plus`, `karma_minus`, `karma_time`, `karma_off`, `comm_count`, `comm_old`, `smileys`, `notification_settings`, `email_confirmed`, `confirmation_code`, `new_email`, `admin_notes`) VALUES
(1, 'Chifty', 'chifty', '93167f04a02623ee6f24da9581144960', 9, 0, '', 'm', 0, 0, 0, 0, 1624018769, 1624033010, 'chifth@gmail.com', 0, '', '', 'http://j9.chifty.top', '', '', '', '', 1539631057, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36 OPR/76.0.4017.177', 1, '', 0, 0, 0, 1624033003, 0, 0, '', 0, 1, '/downloads?act=files_upload&id=1', 'a:0:{}', 'a:0:{}', 'a:0:{}', 0, 0, 1624018773, 0, 0, 0, 'a:0:{}', NULL, 1, NULL, NULL, NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cms_ads`
--
ALTER TABLE `cms_ads`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cms_album_cat`
--
ALTER TABLE `cms_album_cat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `access` (`access`);

--
-- Индексы таблицы `cms_album_comments`
--
ALTER TABLE `cms_album_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_id` (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `cms_album_downloads`
--
ALTER TABLE `cms_album_downloads`
  ADD PRIMARY KEY (`user_id`,`file_id`);

--
-- Индексы таблицы `cms_album_files`
--
ALTER TABLE `cms_album_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `album_id` (`album_id`),
  ADD KEY `access` (`access`);

--
-- Индексы таблицы `cms_album_views`
--
ALTER TABLE `cms_album_views`
  ADD PRIMARY KEY (`user_id`,`file_id`);

--
-- Индексы таблицы `cms_album_votes`
--
ALTER TABLE `cms_album_votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Индексы таблицы `cms_ban_ip`
--
ALTER TABLE `cms_ban_ip`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip1` (`ip1`),
  ADD UNIQUE KEY `ip2` (`ip2`);

--
-- Индексы таблицы `cms_ban_users`
--
ALTER TABLE `cms_ban_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ban_time` (`ban_time`);

--
-- Индексы таблицы `cms_contact`
--
ALTER TABLE `cms_contact`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_user` (`user_id`,`from_id`),
  ADD KEY `time` (`time`),
  ADD KEY `ban` (`ban`);

--
-- Индексы таблицы `cms_counters`
--
ALTER TABLE `cms_counters`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cms_forum_files`
--
ALTER TABLE `cms_forum_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat` (`cat`),
  ADD KEY `subcat` (`subcat`),
  ADD KEY `topic` (`topic`),
  ADD KEY `post` (`post`);

--
-- Индексы таблицы `cms_forum_rdm`
--
ALTER TABLE `cms_forum_rdm`
  ADD PRIMARY KEY (`topic_id`,`user_id`),
  ADD KEY `time` (`time`);

--
-- Индексы таблицы `cms_forum_vote`
--
ALTER TABLE `cms_forum_vote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_topic` (`type`,`topic`),
  ADD KEY `type` (`type`),
  ADD KEY `topic` (`topic`);

--
-- Индексы таблицы `cms_forum_vote_users`
--
ALTER TABLE `cms_forum_vote_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_user` (`topic`,`user`),
  ADD KEY `topic` (`topic`);

--
-- Индексы таблицы `cms_library_comments`
--
ALTER TABLE `cms_library_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_id` (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `cms_library_rating`
--
ALTER TABLE `cms_library_rating`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_article` (`user_id`,`st_id`);

--
-- Индексы таблицы `cms_mail`
--
ALTER TABLE `cms_mail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `from_id` (`from_id`),
  ADD KEY `time` (`time`),
  ADD KEY `read` (`read`),
  ADD KEY `sys` (`sys`),
  ADD KEY `delete` (`delete`);

--
-- Индексы таблицы `cms_sessions`
--
ALTER TABLE `cms_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `lastdate` (`lastdate`);

--
-- Индексы таблицы `cms_users_data`
--
ALTER TABLE `cms_users_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `key` (`key`);

--
-- Индексы таблицы `cms_users_guestbook`
--
ALTER TABLE `cms_users_guestbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_id` (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `cms_users_iphistory`
--
ALTER TABLE `cms_users_iphistory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_ip` (`ip`);

--
-- Индексы таблицы `download__bookmark`
--
ALTER TABLE `download__bookmark`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Индексы таблицы `download__category`
--
ALTER TABLE `download__category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refid` (`refid`),
  ADD KEY `total` (`total`);

--
-- Индексы таблицы `download__comments`
--
ALTER TABLE `download__comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_id` (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `download__files`
--
ALTER TABLE `download__files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `md5hash` (`md5hash`),
  ADD KEY `refid` (`refid`),
  ADD KEY `time` (`time`),
  ADD KEY `type` (`type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comm_count` (`comm_count`),
  ADD KEY `updated` (`updated`);

--
-- Индексы таблицы `download__more`
--
ALTER TABLE `download__more`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `md5hash` (`md5hash`),
  ADD KEY `refid` (`refid`),
  ADD KEY `time` (`time`);

--
-- Индексы таблицы `email_messages`
--
ALTER TABLE `email_messages`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `forum_messages`
--
ALTER TABLE `forum_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `deleted` (`deleted`);
ALTER TABLE `forum_messages` ADD FULLTEXT KEY `text` (`text`);

--
-- Индексы таблицы `forum_sections`
--
ALTER TABLE `forum_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`);

--
-- Индексы таблицы `forum_topic`
--
ALTER TABLE `forum_topic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted` (`deleted`);

--
-- Индексы таблицы `guest`
--
ALTER TABLE `guest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adm` (`adm`),
  ADD KEY `time` (`time`),
  ADD KEY `ip` (`ip`);

--
-- Индексы таблицы `karma_users`
--
ALTER TABLE `karma_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `karma_user` (`karma_user`),
  ADD KEY `type` (`type`);

--
-- Индексы таблицы `library_cats`
--
ALTER TABLE `library_cats`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `library_tags`
--
ALTER TABLE `library_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lib_text_id` (`lib_text_id`),
  ADD KEY `tag_name` (`tag_name`);

--
-- Индексы таблицы `library_texts`
--
ALTER TABLE `library_texts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);
ALTER TABLE `library_texts` ADD FULLTEXT KEY `text` (`text`);

--
-- Индексы таблицы `news_articles`
--
ALTER TABLE `news_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_code` (`section_id`,`code`),
  ADD KEY `news_articles_section_id_index` (`section_id`),
  ADD KEY `news_articles_code_index` (`code`),
  ADD KEY `news_articles_tags_index` (`tags`);

--
-- Индексы таблицы `news_comments`
--
ALTER TABLE `news_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_comments_article_id_index` (`article_id`);

--
-- Индексы таблицы `news_search_index`
--
ALTER TABLE `news_search_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_search_index_article_id_index` (`article_id`);

--
-- Индексы таблицы `news_sections`
--
ALTER TABLE `news_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_sections_parent_index` (`parent`),
  ADD KEY `news_sections_code_index` (`code`);

--
-- Индексы таблицы `news_votes`
--
ALTER TABLE `news_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_user` (`article_id`,`user_id`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_module_type_entity` (`user_id`,`module`,`event_type`,`entity_id`),
  ADD KEY `notifications_user_id_index` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name_lat` (`name_lat`),
  ADD KEY `lastdate` (`lastdate`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cms_ads`
--
ALTER TABLE `cms_ads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_album_cat`
--
ALTER TABLE `cms_album_cat`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_album_comments`
--
ALTER TABLE `cms_album_comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_album_files`
--
ALTER TABLE `cms_album_files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_album_votes`
--
ALTER TABLE `cms_album_votes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_ban_ip`
--
ALTER TABLE `cms_ban_ip`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_ban_users`
--
ALTER TABLE `cms_ban_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_contact`
--
ALTER TABLE `cms_contact`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_counters`
--
ALTER TABLE `cms_counters`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_forum_files`
--
ALTER TABLE `cms_forum_files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_forum_vote`
--
ALTER TABLE `cms_forum_vote`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_forum_vote_users`
--
ALTER TABLE `cms_forum_vote_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_library_comments`
--
ALTER TABLE `cms_library_comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_library_rating`
--
ALTER TABLE `cms_library_rating`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_mail`
--
ALTER TABLE `cms_mail`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_users_data`
--
ALTER TABLE `cms_users_data`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_users_guestbook`
--
ALTER TABLE `cms_users_guestbook`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cms_users_iphistory`
--
ALTER TABLE `cms_users_iphistory`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `download__bookmark`
--
ALTER TABLE `download__bookmark`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `download__category`
--
ALTER TABLE `download__category`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `download__comments`
--
ALTER TABLE `download__comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `download__files`
--
ALTER TABLE `download__files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `download__more`
--
ALTER TABLE `download__more`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `email_messages`
--
ALTER TABLE `email_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `forum_messages`
--
ALTER TABLE `forum_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `forum_sections`
--
ALTER TABLE `forum_sections`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `forum_topic`
--
ALTER TABLE `forum_topic`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `guest`
--
ALTER TABLE `guest`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `karma_users`
--
ALTER TABLE `karma_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `library_cats`
--
ALTER TABLE `library_cats`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `library_tags`
--
ALTER TABLE `library_tags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `library_texts`
--
ALTER TABLE `library_texts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `news_articles`
--
ALTER TABLE `news_articles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `news_comments`
--
ALTER TABLE `news_comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `news_search_index`
--
ALTER TABLE `news_search_index`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `news_sections`
--
ALTER TABLE `news_sections`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `news_votes`
--
ALTER TABLE `news_votes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `news_comments`
--
ALTER TABLE `news_comments`
  ADD CONSTRAINT `news_comments_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `news_search_index`
--
ALTER TABLE `news_search_index`
  ADD CONSTRAINT `news_search_index_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `news_votes`
--
ALTER TABLE `news_votes`
  ADD CONSTRAINT `news_votes_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `news_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
