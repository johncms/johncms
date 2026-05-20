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
        "SELECT id, text, reply
         FROM cms_library_comments
         WHERE id > {$lastId}
         ORDER BY id ASC
         LIMIT {$chunkSize}"
    );

    $count = 0;

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $convertedText = $tools->checkout($row['text'], 1, 1);
        $convertedReply = $row['reply'] !== '' ? $tools->checkout($row['reply'], 1, 1) : '';

        $update = $db->prepare('UPDATE cms_library_comments SET text = :text, reply = :reply WHERE id = :id');
        $update->execute(['text' => $convertedText, 'reply' => $convertedReply, 'id' => $row['id']]);

        $lastId = (int) $row['id'];
        $count++;
    }

    if ($count === 0) {
        break;
    }

    echo "Processed up to ID {$lastId}\n";
}

echo 'Update complete!';
