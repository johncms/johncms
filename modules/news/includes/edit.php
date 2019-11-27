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
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface  $user
 * @var Mobicms\Render\Engine       $view
 */

// Editing news
if ($user->rights >= 6) {
    // Add an item to the navigation chain
    $nav_chain->add(_t('Edit news'), '');

    if (! $id) {
        echo $view->render('system::pages/result', [
            'title'    => _t('News'),
            'type'     => 'alert-danger',
            'message'  => _t('Wrong data'),
            'back_url' => '/news/',
        ]);
        exit;
    }

    if (! empty($_POST)) {
        $error = [];

        if (empty($_POST['name'])) {
            $error[] = _t('You have not entered news title');
        }

        if (empty($_POST['text'])) {
            $error[] = _t('You have not entered news text');
        }

        $name = htmlspecialchars(trim($_POST['name']));
        $text = trim($_POST['text']);

        if (! $error) {
            $db->prepare('
                      UPDATE `news` SET
                      `name` = ?,
                      `text` = ?
                      WHERE `id` = ?
                    ')->execute([
                $name,
                $text,
                $id,
            ]);
        } else {
            echo $view->render('system::pages/result', [
                'title'    => _t('Edit news'),
                'message'  => $error,
                'type'     => 'alert-danger',
                'back_url' => '/news/edit/' . $id,
            ]);
        }

        echo $view->render('system::pages/result', [
            'title'    => _t('Edit news'),
            'message'  => _t('News changed'),
            'type'     => 'alert-success',
            'back_url' => '/news/',
        ]);
    } else {
        $res = $db->query("SELECT * FROM `news` WHERE `id` = '${id}'")->fetch();
        echo $view->render('news::edit', [
            'id'   => $id,
            'name' => $res['name'],
            'text' => htmlentities($res['text'], ENT_QUOTES, 'UTF-8'),
        ]);
    }
} else {
    pageNotFound();
}
