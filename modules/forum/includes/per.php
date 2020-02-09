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
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    if (! $id) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    $typ = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");
    if (! $typ->rowCount()) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_POST['submit'])) {
        $razd = isset($_POST['razd']) ? abs((int) ($_POST['razd'])) : false;
        if (! $razd) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $typ1 = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${razd}'");
        if (! $typ1->rowCount()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $db->exec(
            "UPDATE `forum_topic` SET
            `section_id` = '${razd}'
            WHERE `id` = '${id}'
        "
        );
        header("Location: ?type=topic&id=${id}");
    } else {
        // Перенос темы
        $ms = $typ->fetch();

        if (empty($_GET['other'])) {
            $rz1 = $db->query("SELECT * FROM `forum_sections` WHERE id='" . $ms['section_id'] . "'")->fetch();
            $other = $rz1['parent'];
        } else {
            $other = (int) ($_GET['other']);
        }

        $fr1 = $db->query("SELECT * FROM `forum_sections` WHERE id='" . $other . "'")->fetch();
        $raz = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '" . $fr1['id'] . "' AND section_type = 1 AND  `id` != '" . $ms['section_id'] . "' ORDER BY `sort` ASC");

        $current_sections = [];
        while ($raz1 = $raz->fetch()) {
            $current_sections[] = $raz1;
        }

        $frm = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '${other}' AND (section_type != 1 OR section_type IS NULL) ORDER BY `sort` ASC");
        $other_categories = [];
        while ($frm1 = $frm->fetch()) {
            $other_categories[] = $frm1;
        }

        echo $view->render(
            'forum::move_topic',
            [
                'title'            => __('Move topic'),
                'page_title'       => __('Move topic'),
                'id'               => $id,
                'current_section'  => $fr1,
                'current_sections' => $current_sections,
                'other_categories' => $other_categories,
                'back_url'         => '?type=topic&id=' . $id,
            ]
        );
    }
}
