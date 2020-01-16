<?php

/*
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

$created = false;
if (isset($_POST['submit'])) {
    if (empty($_POST['name'])) {
        $error = $tools->displayError(__('You have not entered the name'), '<a href="?act=mkdir&amp;id=' . $id . '">' . __('Repeat') . '</a>');
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
        'error'   => $error,
        'id'      => $id,
        'created' => $created,
    ]
);
