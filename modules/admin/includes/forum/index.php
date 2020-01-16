<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */

// Панель управления форумом
$counters = [];
$counters['total_cat'] = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` != 1 OR `section_type` IS NULL')->fetchColumn();
$counters['total_sub'] = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` = 1')->fetchColumn();
$counters['total_thm'] = $db->query('SELECT COUNT(*) FROM `forum_topic`')->fetchColumn();
$counters['total_thm_del'] = $db->query('SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = 1')->fetchColumn();
$counters['total_msg'] = $db->query('SELECT COUNT(*) FROM `forum_messages`')->fetchColumn();
$counters['total_msg_del'] = $db->query('SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = 1')->fetchColumn();
$counters['total_files'] = $db->query('SELECT COUNT(*) FROM `cms_forum_files`')->fetchColumn();
$counters['total_votes'] = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1'")->fetchColumn();

$data['counters'] = $counters;
$data['back_url'] = '/admin/';
echo $view->render(
    'admin::forum/index',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
