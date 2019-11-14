<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                       $db
 * @var Johncms\Api\UserInterface $user
 * @var League\Plates\Engine      $view
 */

// Удаление новости
if ($user->rights >= 6) {
    if (isset($_GET['yes'])) {
        $db->query("DELETE FROM `news` WHERE `id` = '${id}'");
        echo $view->render('news::result', [
            'title'    => _t('Delete'),
            'message'  => _t('Article deleted'),
            'type'     => 'success',
            'back_url' => '/news/',
        ]);
    } else {
        echo $view->render('news::confirm_delete', ['id' => $id]);
    }
} else {
    pageNotFound();
}
