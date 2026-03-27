<?php

declare(strict_types=1);

require '../system/bootstrap.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

/** @var PDO $db */
$db = di(PDO::class);

$chunkSize = 500;
$lastId = 0;
$processed = 0;
$updated = 0;

$select = $db->prepare(
    'SELECT `id`, `name`
     FROM `forum_topic`
     WHERE `id` > :last_id
     ORDER BY `id` ASC
     LIMIT :limit'
);

$update = $db->prepare(
    'UPDATE `forum_topic`
     SET `name` = :name
     WHERE `id` = :id'
);

while (true) {
    $select->bindValue(':last_id', $lastId, PDO::PARAM_INT);
    $select->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
    $select->execute();

    /** @var array<int, array{id: string|int, name: string}> $topics */
    $topics = $select->fetchAll(PDO::FETCH_ASSOC);

    if ($topics === []) {
        break;
    }

    foreach ($topics as $topic) {
        $topicId = (int) $topic['id'];
        $name = (string) $topic['name'];
        $decodedName = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($decodedName !== $name) {
            $update->bindValue(':name', $decodedName, PDO::PARAM_STR);
            $update->bindValue(':id', $topicId, PDO::PARAM_INT);
            $update->execute();
            ++$updated;
        }

        ++$processed;
        $lastId = $topicId;
    }

    echo "Processed up to topic ID {$lastId}; total processed: {$processed}; updated: {$updated}\n";
}

echo "Done. Processed: {$processed}; updated: {$updated}\n";
