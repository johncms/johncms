<?php

define('_IN_JOHNCMS', 1);
$rootpath = '';
require('incfiles/core.php');

// Добавляем новые поля
mysql_query("ALTER TABLE `users` ADD `karma_plus` INT NOT NULL DEFAULT '0' AFTER `set_chat`");
mysql_query("ALTER TABLE `users` ADD `karma_minus` INT NOT NULL DEFAULT '0' AFTER `karma_plus`");
// Конвертируем данные
$req = mysql_query("SELECT `id`, `plus_minus` FROM `users`");
while($res = mysql_fetch_assoc($req)){
    $karma = explode('|', $res['plus_minus']);
    $karma_plus = $karma[0] ? $karma[0] : '0';
    $karma_minus = $karma[1] ? $karma[1] : '0';
    mysql_query("UPDATE `users` SET
        `karma_plus` = '$karma_plus',
        `karma_minus` = '$karma_minus'
        WHERE `id` = '" . $res['id'] . "'
    ");
}
// Удаляем старые поля
mysql_query("ALTER TABLE `users` DROP `karma`");
mysql_query("ALTER TABLE `users` DROP `plus_minus`");
// Оптимизируем таблицу
mysql_query("OPTIMIZE TABLE `users`");
echo 'Карма сконвертирована';
  
?>