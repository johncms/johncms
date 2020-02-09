<?php

declare(strict_types=1);

const DEBUG = true;
const _IN_JOHNCMS = true;

require '../system/bootstrap.php';

/** @var PDO $db */
$db = di(PDO::class);

$db->query("ALTER TABLE `users` CHANGE COLUMN `place` `place` VARCHAR(100) NOT NULL DEFAULT '' AFTER `movings`;");
$db->query("UPDATE forum_topic SET pinned = NULL WHERE pinned = 0;");

echo 'Update complete!';
