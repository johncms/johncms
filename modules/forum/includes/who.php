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
 * @var Johncms\Api\UserInterface $user
 */

if (! $user->isValid()) {
    header('Location: ./');
    exit;
}

if ($id) {
    // Показываем общий список тех, кто в выбранной теме
    $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $topic = $req->fetch();
        $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%'")->fetchColumn();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
        }

        if ($total) {
            $req = $db->query(
                'SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time(
                    ) - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, " . $user->config->kmess
            );

            for ($i = 0; $res = $req->fetch(); ++$i) {
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
    $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%'")->fetchColumn();

    if ($start >= $total) {
        // Исправляем запрос на несуществующую страницу
        $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
    }

    $items = [];
    if ($total) {
        $req = $db->query(
            'SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time(
                ) - 300) . " AND `place` LIKE '/forum%' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, " . $user->config->kmess
        );

        for ($i = 0; $res = $req->fetch(); ++$i) {
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
                case 'topic':
                    $topic = $db->query('SELECT * FROM `forum_topic` WHERE `id`= ' . $place_id)->fetch();
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
                    $message = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '" . $place_id . "'")->fetch();
                    if (! empty($message)) {
                        $req_m = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $message['topic_id'] . "'");
                        if ($req_m->rowCount()) {
                            $res_m = $req_m->fetch();
                            $place = _t('In the Topic') . ' &quot;<a href="?type=topic&id=' . $res_m['id'] . '">' . (empty($res_m['name']) ? '-----' : $res_m['name']) . '</a>&quot;';
                        }
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
exit;
