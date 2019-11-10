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
 * @var PDO                        $db
 * @var Johncms\Api\UserInterface  $user
 * @var League\Plates\Engine       $view
 */

// Удаление новости
if ($user->rights >= 6) {
    echo '<div class="phdr"><a href="./"><b>' . _t('News') . '</b></a> | ' . _t('Delete') . '</div>';

    if (isset($_GET['yes'])) {
        $db->query("DELETE FROM `news` WHERE `id` = '${id}'");
        echo '<p>' . _t('Article deleted') . '<br><a href="./">' . _t('Back to news') . '</a></p>';
    } else {
        echo '<div class="rmenu padding">' . _t('Do you really want to delete?') . '<br>' .
            '<a href="?do=del&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | <a href="./">' . _t('Cancel') . '</a></div>';
    }

    echo $view->render('system::app/old_content', [
        'title'   => _t('News'),
        'content' => ob_get_clean(),
    ]);
} else {
    pageNotFound();
}
