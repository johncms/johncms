ALTER TABLE `cms_album_files` ADD `unread_comments` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `forum` ADD `curators` text NOT NULL;