<?php

declare(strict_types=1);

const DEBUG = true;
const _IN_JOHNCMS = true;

require '../system/bootstrap.php';

/** @var PDO $db */
$db = di(PDO::class);


$files = $db->query('SELECT * FROM download__files');

while ($file = $files->fetch()) {
    $new_path = str_replace('../files/', 'upload/', $file['dir']);
    $db->query("UPDATE download__files SET dir = '" . $new_path . "' WHERE id = " . $file['id']);
}

echo 'Update complete!';
