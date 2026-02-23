<?php

declare(strict_types=1);

use Johncms\System\Legacy\Tools;

require '../system/bootstrap.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

/** @var PDO $db */
$db = di(PDO::class);
/** @var Tools $tools */
$tools = di(Tools::class);

$chunkSize = 500;
$lastId = 0;

while (true) {
    $result = $db->query(
        "SELECT id, text
         FROM forum_messages
         WHERE id > {$lastId}
         ORDER BY id ASC
         LIMIT {$chunkSize}"
    );

    $count = 0;

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $converted = $tools->checkout($row['text'], 1, 1);

        $db->query(
            "UPDATE forum_messages
             SET text = " . $db->quote($converted) . "
             WHERE id = {$row['id']}"
        );

        $lastId = (int) $row['id'];
        $count++;
    }

    if ($count === 0) {
        break;
    }

    echo "Processed up to ID {$lastId}\n";
}

echo 'Update complete!';
