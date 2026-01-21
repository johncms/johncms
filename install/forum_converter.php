<?php

declare(strict_types=1);

require '../system/bootstrap.php';

/** @var PDO $db */
$db = di(PDO::class);
$tools = di(\Johncms\System\Legacy\Tools::class);


$messages = $db->query('SELECT * FROM forum_messages');

while ($item = $messages->fetch()) {
    $text = $tools->checkout($item['text'], 1, 1);
    $db->query("UPDATE forum_messages SET text = '" . $text . "' WHERE id = " . $item['id']);
}

echo 'Update complete!';
