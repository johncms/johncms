<?php

define('_IN_JOHNCMS', 1);
require '../system/bootstrap.php';

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

$req = $db->query('SELECT `id`, `about` FROM `users`');

while ($res = $req->fetch()) {
    $stmt = $db->prepare('UPDATE `users` SET `about` = ? WHERE `id` = ?');

    if (!empty($res['about'])) {
        $out = str_replace('<br />', '', $res['about']);
        $out = html_entity_decode($out, ENT_QUOTES, 'UTF-8');
        $stmt->execute([$out, $res['id']]);
    }
}
