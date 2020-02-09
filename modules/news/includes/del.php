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

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

// Delete news
if ($user->rights >= 6) {
    // Add an item to the navigation chain
    $nav_chain->add(__('Delete news'), '');

    if (isset($_POST['yes'])) {
        $db->query("DELETE FROM `news` WHERE `id` = '${id}'");
        echo $view->render(
            'system::pages/result',
            [
                'title'    => __('Delete news'),
                'message'  => __('News deleted'),
                'type'     => 'alert-success',
                'back_url' => '/news/',
            ]
        );
    } else {
        echo $view->render('news::confirm_delete', ['id' => $id]);
    }
} else {
    pageNotFound();
}
