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
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\System\Users\User $user
 */

unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

if (empty($_SESSION['uid'])) {
    if (isset($_GET['newup'])) {
        $_SESSION['uppost'] = 1;
    }
    if (isset($_GET['newdown'])) {
        $_SESSION['uppost'] = 0;
    }
}

if ($user->isValid()) {
    switch ($do) {
        case 'reset':
            // Отмечаем все темы как прочитанные
            $ids = $db->query(
                "SELECT `forum_topic`.`id`, `forum_topic`.`last_post_date`
            FROM `forum_topic` LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user->id . "'
            WHERE `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time` OR `cms_forum_rdm`.`topic_id` IS NULL"
            )->fetchAll(PDO::FETCH_ASSOC);

            if (! empty($ids)) {
                foreach ($ids as $val) {
                    $values[] = '(' . $val['id'] . ', ' . $user->id . ', ' . $val['last_post_date'] . ')';
                }
                $db->query(
                    'INSERT INTO cms_forum_rdm (topic_id, user_id, `time`) VALUES ' . implode(',', $values) . '
                    ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)'
                );
            }
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => _t('Unread'),
                    'type'          => 'alert-success',
                    'message'       => _t('All topics marked as read'),
                    'back_url'      => '/forum/',
                    'back_url_name' => _t('Forum'),
                ]
            );
            break;

        case 'period':
            // Показ новых тем за выбранный период
            $vr = isset($_REQUEST['vr']) ? abs((int) ($_REQUEST['vr'])) : 24;
            $vr1 = time() - $vr * 3600;

            if ($user->rights == 9) {
                $req = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `mod_last_post_date` > '${vr1}'");
            } else {
                $req = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `last_post_date` > '${vr1}' AND (`deleted` != '1' OR deleted IS NULL)");
            }
            $count = $req->fetchColumn();

            if ($count) {
                if ($user->rights == 9) {
                    $req = $db->query(
                        "SELECT tpc.*, rzd.`name` AS rzd_name, frm.`name` AS frm_name
                    FROM `forum_topic` tpc
                    JOIN forum_sections rzd ON rzd.id = tpc.section_id
                    JOIN forum_sections frm ON frm.id = rzd.parent
                    WHERE `mod_last_post_date` > '" . $vr1 . "' ORDER BY `mod_last_post_date` DESC LIMIT " . $start . ',' . $user->config->kmess
                    );
                } else {
                    $req = $db->query(
                        "SELECT tpc.*, rzd.`name` AS rzd_name, frm.`name` AS frm_name
                    FROM `forum_topic` tpc
                    JOIN forum_sections rzd ON rzd.id = tpc.section_id
                    JOIN forum_sections frm ON frm.id = rzd.parent
                    WHERE `last_post_date` > '" . $vr1 . "' AND (`deleted` <> '1' OR deleted IS NULL) ORDER BY `last_post_date` DESC LIMIT " . $start . ',' . $user->config->kmess
                    );
                }

                $topics = [];
                while ($res = $req->fetch()) {
                    if ($user->rights >= 7) {
                        $res['show_posts_count'] = $tools->formatNumber($res['mod_post_count']);
                        $res['show_last_author'] = $res['mod_last_post_author_name'];
                        $res['show_last_post_date'] = $tools->displayDate($res['mod_last_post_date']);
                    } else {
                        $res['show_posts_count'] = $tools->formatNumber($res['post_count']);
                        $res['show_last_author'] = $res['last_post_author_name'];
                        $res['show_last_post_date'] = $tools->displayDate($res['last_post_date']);
                    }

                    $res['has_icons'] = ($res['pinned'] || $res['has_poll'] || $res['closed'] || $res['deleted']);

                    $res['url'] = '/forum/?type=topic&amp;id=' . $res['id'];

                    // Url to last page
                    $res['last_page_url'] = $res['url'];
                    $cpg = ceil($res['show_posts_count'] / $user->config->kmess);
                    if ($cpg > 1) {
                        $res['last_page_url'] = '/forum/?type=topic&amp;id=' . $res['id'] . '&amp;page=' . $cpg;
                    }

                    $topics[] = $res;
                }
            }

            echo $view->render(
                'forum::new_topics',
                [
                    'pagination'    => $tools->displayPagination('/forum/?act=new&amp;do=period&amp;vr=' . $vr . '&amp;', $start, $count, $user->config->kmess),
                    'title'         => sprintf(_t('All for period %d hours'), $vr),
                    'page_title'    => sprintf(_t('All for period %d hours'), $vr),
                    'empty_message' => _t('There is nothing new in this forum for selected period'),
                    'topics'        => $topics ?? [],
                    'total'         => $count,
                    'show_period'   => true,
                ]
            );
            break;

        default:
            // Вывод непрочитанных тем (для зарегистрированных)
            $total = di('counters')->forumNew();
            if ($total > 0) {
                $req = $db->query(
                    "SELECT tpc.*, rzd.`name` AS rzd_name, frm.id as frm_id, frm.`name` AS frm_name
                FROM `forum_topic` tpc
                LEFT JOIN `cms_forum_rdm` rdm ON `tpc`.`id` = `rdm`.`topic_id` AND `rdm`.`user_id` = '" . $user->id . "'
                JOIN forum_sections rzd ON rzd.id = tpc.section_id
                JOIN forum_sections frm ON frm.id = rzd.parent
                WHERE " . ($user->rights >= 7 ? '' : "(`tpc`.`deleted` <> '1' OR `tpc`.`deleted` IS NULL) AND ") . "(`rdm`.`topic_id` IS NULL OR `tpc`.`last_post_date` > `rdm`.`time`)
                ORDER BY `tpc`.`last_post_date` DESC LIMIT ${start}, " . $user->config->kmess
                );

                $topics = [];
                while ($res = $req->fetch()) {
                    if ($user->rights >= 7) {
                        $res['show_posts_count'] = $tools->formatNumber($res['mod_post_count']);
                        $res['show_last_author'] = $res['mod_last_post_author_name'];
                        $res['show_last_post_date'] = $tools->displayDate($res['mod_last_post_date']);
                    } else {
                        $res['show_posts_count'] = $tools->formatNumber($res['post_count']);
                        $res['show_last_author'] = $res['last_post_author_name'];
                        $res['show_last_post_date'] = $tools->displayDate($res['last_post_date']);
                    }

                    $res['has_icons'] = ($res['pinned'] || $res['has_poll'] || $res['closed'] || $res['deleted']);

                    $res['url'] = '/forum/?type=topic&amp;id=' . $res['id'];

                    // Url to last page
                    $res['last_page_url'] = $res['url'];
                    $cpg = ceil($res['show_posts_count'] / $user->config->kmess);
                    if ($cpg > 1) {
                        $res['last_page_url'] = '/forum/?type=topic&amp;id=' . $res['id'] . '&amp;page=' . $cpg;
                    }

                    $res['forum_url'] = '';
                    if (! empty($res['frm_id'])) {
                        $res['forum_url'] = '/forum/?id=' . $res['frm_id'];
                    }

                    $res['section_url'] = '';
                    if (! empty($res['section_id'])) {
                        $res['section_url'] = '/forum/?type=topics&id=' . $res['section_id'];
                    }

                    $topics[] = $res;
                }
            }

            echo $view->render(
                'forum::new_topics',
                [
                    'pagination'    => $tools->displayPagination('?act=new&amp;', $start, $total, $user->config->kmess),
                    'title'         => _t('Unread'),
                    'page_title'    => _t('Unread'),
                    'empty_message' => _t('The list is empty'),
                    'topics'        => $topics ?? [],
                    'total'         => $total,
                    'show_period'   => false,
                    'mark_as_read'  => '?act=new&amp;do=reset',
                ]
            );
            break;
    }
} else {
    // Вывод 10 последних тем (для незарегистрированных)
    $req = $db->query(
        'SELECT tpc.*, rzd.`name` AS rzd_name, frm.`name` AS frm_name
    FROM `forum_topic` tpc
    JOIN forum_sections rzd ON rzd.id = tpc.section_id
    JOIN forum_sections frm ON frm.id = rzd.parent
    WHERE (`deleted` <> 1 OR deleted IS NULL)
    ORDER BY `last_post_date` DESC LIMIT 10'
    );

    $total = $req->rowCount();
    if ($total) {
        $topics = [];
        while ($res = $req->fetch()) {
            if ($user->rights >= 7) {
                $res['show_posts_count'] = $tools->formatNumber($res['mod_post_count']);
                $res['show_last_author'] = $res['mod_last_post_author_name'];
                $res['show_last_post_date'] = $tools->displayDate($res['mod_last_post_date']);
            } else {
                $res['show_posts_count'] = $tools->formatNumber($res['post_count']);
                $res['show_last_author'] = $res['last_post_author_name'];
                $res['show_last_post_date'] = $tools->displayDate($res['last_post_date']);
            }

            $res['has_icons'] = ($res['pinned'] || $res['has_poll'] || $res['closed'] || $res['deleted']);

            $res['url'] = '/forum/?type=topic&amp;id=' . $res['id'];

            // Url to last page
            $res['last_page_url'] = $res['url'];
            $cpg = ceil($res['show_posts_count'] / $user->config->kmess);
            if ($cpg > 1) {
                $res['last_page_url'] = '/forum/?type=topic&amp;id=' . $res['id'] . '&amp;page=' . $cpg;
            }

            $res['forum_url'] = '';
            if (! empty($res['frm_id'])) {
                $res['forum_url'] = '/forum/?id=' . $res['frm_id'];
            }

            $res['section_url'] = '';
            if (! empty($res['section_id'])) {
                $res['section_url'] = '/forum/?type=topics&id=' . $res['section_id'];
            }

            $topics[] = $res;
        }
    }

    echo $view->render(
        'forum::new_topics',
        [
            'pagination'    => '',
            'title'         => _t('Last 10'),
            'page_title'    => _t('Last 10'),
            'empty_message' => _t('The list is empty'),
            'topics'        => $topics ?? [],
            'total'         => $total,
            'show_period'   => false,
        ]
    );
}
