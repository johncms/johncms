------------------------------------------------------------
-- Удаляем ненужные таблицы
------------------------------------------------------------
DROP TABLE IF EXISTS `cms_lng_list`;
DROP TABLE IF EXISTS `cms_lng_phrases`;
DROP TABLE IF EXISTS `chat`;
DROP TABLE IF EXISTS `vik`;

------------------------------------------------------------
-- Модифицируем таблицы
------------------------------------------------------------
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