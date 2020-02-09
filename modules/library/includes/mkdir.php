<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

if (! $adm) {
    Library\Utils::redir404();
}

$title = __('Create Section');
$nav_chain->add($title);

$created = false;
if (isset($_POST['submit'])) {
    if (empty($_POST['name'])) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => __('You have not entered the name'),
                'back_url'      => '?act=mkdir&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
    } else {
        $lastinsert = $db->query('SELECT MAX(`id`) FROM `library_cats`')->fetchColumn();
        ++$lastinsert;
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $type = (int) ($_POST['type']);
        $stmt = $db->prepare('INSERT INTO `library_cats` (`parent`, `name`, `description`, `dir`, `pos`) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, $name, $desc, $type, $lastinsert]);

        if ($stmt->rowCount()) {
            $created = true;
        }
    }
}

echo $view->render(
    'library::mkdir',
    [
        'title'      => $title,
        'page_title' => $page_title ?? $title,
        'error'      => $error,
        'id'         => $id,
        'created'    => $created,
    ]
);
