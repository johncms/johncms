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

// Delete news
if ($user->rights >= 6) {
    // Add an item to the navigation chain
    $nav_chain->add(_t('Delete news'), '');

    if (isset($_GET['yes'])) {
        $db->query("DELETE FROM `news` WHERE `id` = '${id}'");
        echo $view->render('news::result', [
            'title'    => _t('Delete news'),
            'message'  => _t('News deleted'),
            'type'     => 'success',
            'back_url' => '/news/',
        ]);
    } else {
        echo $view->render('news::confirm_delete', ['id' => $id]);
    }
} else {
    pageNotFound();
}
