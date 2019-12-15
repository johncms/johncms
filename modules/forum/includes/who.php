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
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if (! $user->isValid()) {
    header('Location: ./');
    exit;
}

$sql = 'SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ? AND `place` LIKE ?';
$sql2 = 'SELECT * FROM `cms_sessions` WHERE `lastdate` > ? AND `place` LIKE ? ORDER BY `movings` DESC LIMIT ?, ?';

if ($id) {
    // Показываем общий список тех, кто в выбранной теме
    $topic = $db->query("SELECT `name` FROM `forum_topic` WHERE `id` = '${id}'")->fetchColumn();

    if ($topic) {

        $params = [(time() - 300), '/forum?type=topic&id=' . $id . '%'];
        if (! $do) {
            $sql = 'SELECT COUNT(*) FROM `users` WHERE `lastdate` > ? AND `place` LIKE ? OR `place` = ?';
            $sql2 = 'SELECT * FROM `users` WHERE `lastdate` > ? AND `place` LIKE ? OR `place` = ? ORDER BY `name` LIMIT ?, ?';
            $params = [(time() - 300), '/forum?type=topic&id=' . $id . '%', '/forum?act=who&id=' . $id];
        }
        $req = $db->prepare($sql);
        $req->execute($params);
        $total = $req->fetchColumn();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
        }

        if ($total) {
            $params = array_merge($params, [$start, $user->config->kmess]);
            $req = $db->prepare($sql2);
            $req->execute($params);

            foreach ($req as $res) {
                if (empty($res['name'])) {
                    $res['name'] = _t('Guest', 'system');
                }

                $res['user_avatar'] = '';
                if (! empty($res['id'])) {
                    $avatar = 'users/avatar/' . $res['id'] . '.png';
                    if (file_exists(UPLOAD_PATH . $avatar)) {
                        $res['user_avatar'] = UPLOAD_PUBLIC_PATH . $avatar;
                    }
                }

                $res['user_profile_link'] = '';
                if (! empty($res['id']) && $user->isValid() && $user->id != $res['id']) {
                    $res['user_profile_link'] = '/profile/?user=' . $res['id'];
                }

                $res['user_rights_name'] = '';
                if (! empty($res['rights'])) {
                    $res['user_rights_name'] = $user_rights_names[$res['rights']] ?? '';
                }

                $res['user_is_online'] = time() <= $res['lastdate'] + 300;

                $res['search_ip_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip']);
                $res['ip'] = long2ip($res['ip']);
                $res['search_ip_via_proxy_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip_via_proxy']);
                $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;

                $res['place'] = '';
                $items[] = $res;
            }
        }
    } else {
        header('Location: ./');
    }

    echo $view->render(
        'forum::who',
        [
            'title'           => _t('Who in Topic'),
            'page_title'      => _t('Who in Topic'),
            'empty_message'   => _t('The list is empty'),
            'items'           => $items ?? [],
            'pagination'      => $tools->displayPagination('?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess),
            'total'           => $total,
            'topic'           => $topic,
            'is_users'        => $do !== 'guest',
            'users_list_url'  => '?act=who&amp;id=' . $id,
            'guests_list_url' => '?act=who&amp;do=guest&amp;id=' . $id,
            'show_period'     => false,
            'id'              => $id,
        ]
    );
} else {
    // Показываем общий список тех, кто в форуме
    $params = [(time() - 300), '/forum%'];
    if (! $do) {
        $sql = 'SELECT COUNT(*) FROM `users` WHERE `lastdate` > ? AND `place` LIKE ?';
        $sql2 = 'SELECT * FROM `users` WHERE `lastdate` > ? AND `place` LIKE ? ORDER BY `name` LIMIT ?, ?';
    }
    $req = $db->prepare($sql);
    $req->execute($params);
    $total = $req->fetchColumn();

    if ($start >= $total) {
        // Исправляем запрос на несуществующую страницу
        $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
    }

    $items = [];
    if ($total) {
        $params = array_merge($params, [$start, $user->config->kmess]);
        $req = $db->prepare($sql2);
        $req->execute($params);
        unset($sql, $sql2);

        foreach ($req as $res) {
            // Вычисляем местоположение
            $place = '';
            $parsed_url = [];
            if (! empty($res['place'])) {
                $parsed_url = parse_url($res['place']);
                if (! empty($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $parsed_url);
                }
            }

            $place_id = 0;
            $act_type = '';
            $place = 'forum';

            if (! empty($parsed_url['act'])) {
                $place = $parsed_url['act'];
                $place_id = $parsed_url['id'] ?? 0;
                $act_type = $parsed_url['type'] ?? '';
            } elseif (! empty($parsed_url['type'])) {
                $place = $parsed_url['type'];
                $place_id = $parsed_url['id'];
            } elseif (! empty($parsed_url['id'])) {
                $place = 'section';
                $place_id = $parsed_url['id'];
            }

            switch ($place) {
                case 'forum':
                    $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    break;

                case 'who':
                    $place = _t('Here, in the List');
                    break;

                case 'files':
                    $place = '<a href="?act=files">' . _t('Looking forum files') . '</a>';
                    break;

                case 'new':
                    $place = '<a href="?act=new">' . _t('In the unreads') . '</a>';
                    break;

                case 'search':
                    $place = '<a href="search.php">' . _t('Forum search') . '</a>';
                    break;

                case 'section':
                    $section = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $place_id)->fetch();
                    if (! empty($section)) {
                        $link = '<a href="?id=' . $section['id'] . '">' . (empty($section['name']) ? '-----' : $section['name']) . '</a>';
                        $place = _t('In the Category') . ' &quot;' . $link . '&quot;';
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'topics':
                    $topics = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $place_id)->fetch();
                    if (! empty($topics)) {
                        $link = '<a href="?type=topics&id=' . $topics['id'] . '">' . (empty($topics['name']) ? '-----' : $topics['name']) . '</a>';
                        $place = _t('In the Section') . ' &quot;' . $link . '&quot;';
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'say':
                    $sql = 'SELECT `frt`.`id`, `frt`.`name` FROM `forum_messages` frm
LEFT JOIN `forum_topic` frt ON `frt`.`id`=`frm`.`topic_id` WHERE `frm`.`id`= ?';
                case 'topic':
                    if (empty($sql)) {
                        $sql = 'SELECT `id`, `name` FROM `forum_topic` WHERE `id`= ?';
                    }
                    $req = $db->prepare($sql);
                    $req->execute([$place_id]);

                    $topic = $req->fetch();

                    if (! empty($topic)) {
                        $link = '<a href="?type=topic&id=' . $topic['id'] . '">' . (empty($topic['name']) ? '-----' : $topic['name']) . '</a>';

                        if ($act_type == 'reply') {
                            $place = _t('Answers in the Topic') . ' &quot;' . $link . '&quot;';
                        } else {
                            $place = (($place == 'say') ? _t('Writes in the Topic') . ' &quot;' : _t('In the Topic') . ' &quot;') . $link . '&quot;';
                        }
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'show_post':
                    $message = $db->query("SELECT `frt`.`id`, `frt`.`name` FROM `forum_messages` frm
LEFT JOIN `forum_topic` frt ON `frt`.`id`=`frm`.`topic_id` WHERE `frm`.`id` = '" . $place_id . "'")->fetch();
                    if (! empty($message)) {
                        $place = _t('In the Topic') . ' &quot;<a href="?type=topic&id=' . $message['id'] . '">' . (empty($message['name']) ? '-----' : $message['name']) . '</a>&quot;';
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                default:
                    $place = '<a href="./">' . _t('In the forum Main') . '</a>';
            }

            if (empty($res['name'])) {
                $res['name'] = _t('Guest', 'system');
            }

            $res['user_avatar'] = '';
            if (! empty($res['id'])) {
                $avatar = 'users/avatar/' . $res['id'] . '.png';
                if (file_exists(UPLOAD_PATH . $avatar)) {
                    $res['user_avatar'] = UPLOAD_PUBLIC_PATH . $avatar;
                }
            }

            $res['user_profile_link'] = '';
            if (! empty($res['id']) && $user->isValid() && $user->id != $res['id']) {
                $res['user_profile_link'] = '/profile/?user=' . $res['id'];
            }

            $res['user_rights_name'] = '';
            if (! empty($res['rights'])) {
                $res['user_rights_name'] = $user_rights_names[$res['rights']] ?? '';
            }

            $res['user_is_online'] = time() <= $res['lastdate'] + 300;

            $res['search_ip_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip']);
            $res['ip'] = long2ip($res['ip']);
            $res['search_ip_via_proxy_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip_via_proxy']);
            $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;

            $res['place'] = $place;
            $items[] = $res;
        }
    }

    echo $view->render(
        'forum::who',
        [
            'title'           => _t('Who in Forum'),
            'page_title'      => _t('Who in Forum'),
            'empty_message'   => _t('The list is empty'),
            'items'           => $items ?? [],
            'pagination'      => $tools->displayPagination('?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess),
            'total'           => $total,
            'is_users'        => $do !== 'guest',
            'users_list_url'  => '?act=who',
            'guests_list_url' => '?act=who&amp;do=guest',
            'show_period'     => false,
        ]
    );
}
