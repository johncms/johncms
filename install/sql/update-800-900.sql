ALTER TABLE `users` CHANGE COLUMN `place` `place` VARCHAR(100) NOT NULL DEFAULT '' AFTER `movings`;
UPDATE forum_topic SET pinned = NULL WHERE pinned = 0;
