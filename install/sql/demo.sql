--
-- ДЕМО данные Новостей
--
INSERT INTO `news` (`time`, `avt`, `name`, `text`, `kom`) VALUES
(1217062347, 'admin', 'Ресурс начал работу!', 'Добро пожаловать на сайт!\r\nМы надеемся, что Вам тут понравится и Вы будете нашим постоянным посетителем.', 1);

--
-- ДЕМО данные Форума
--
INSERT INTO `forum_sections` (`id`, `parent`, `name`, `description`, `sort`, `access`, `section_type`, `old_id`) VALUES
(1, 0, 'Общение', 'Свободное общение на любую тему', 1, 0, 0, NULL),
(2, 1, 'О разном', '', 1, 0, 1, NULL),
(3, 1, 'Знакомства', '', 2, 0, 1, NULL),
(4, 1, 'Жизнь ресурса', '', 3, 0, 1, NULL),
(5, 1, 'Новости', '', 4, 0, 1, NULL),
(6, 1, 'Предложения и пожелания', '', 5, 0, 1, NULL),
(7, 1, 'Разное', '', 6, 0, 1, NULL);

INSERT INTO `forum_topic` (`id`, `section_id`, `name`, `description`, `view_count`, `user_id`, `user_name`, `created_at`, `post_count`, `mod_post_count`, `last_post_date`, `last_post_author`, `last_post_author_name`, `last_message_id`, `mod_last_post_date`, `mod_last_post_author`, `mod_last_post_author_name`, `mod_last_message_id`, `closed`, `closed_by`, `deleted`, `deleted_by`, `curators`, `pinned`, `has_poll`, `old_id`) VALUES
(1, 3, 'Привет всем!', NULL, 1, 1, 'admin', '2019-10-16 20:18:00', 1, 1, 1571257080, 1, 'admin', 1, 1571257080, 1, 'admin', 1, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL);

INSERT INTO `forum_messages` (`id`, `topic_id`, `text`, `date`, `user_id`, `user_name`, `user_agent`, `ip`, `ip_via_proxy`, `pinned`, `editor_name`, `edit_time`, `edit_count`, `deleted`, `deleted_by`, `old_id`) VALUES
(1, 1, 'Мы рады приветствовать Вас на нашем сайте :)\r\nДавайте знакомиться!', 1571257080, 1, 'admin', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- ДЕМО данные Гостевой
--
INSERT INTO `guest` (`adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`) VALUES
(1, 1217060516, 1, 'admin', 'Добро пожаловать в Админ Клуб!\r\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\nПростым пользователям доступ сюда закрыт.', 2130706433, 'Opera/9.51', '', '', 0),
(0, 1217060536, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, 'Opera/9.51', 'admin', 'Проверка ответа Администратора', 1217064021),
(0, 1217061125, 1, 'admin', 'Для зарегистрированных пользователей Гостевая поддерживает BBcode:\r\n[b]жирный[/b]\r\n[i]курсив[/i]\r\n[u]подчеркнутый[/u]\r\n[red]красный[/red]\r\n[green]зеленый[/green]\r\n[blue]синий[/blue]\r\n\r\nи ссылки:\r\nhttp://gazenwagen.com\r\n\r\nДля гостей, эти функции закрыты.', 2130706433, 'Opera/9.51', '', '', 0);
