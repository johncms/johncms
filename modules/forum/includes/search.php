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

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$nav_chain->add(__('Forum search'));

// Функция подсветки результатов запроса
function ReplaceKeywords($search, $text)
{
    $search = str_replace('*', '', $search);

    return mb_strlen($search) < 3 ? $text : preg_replace(
        '|(' . preg_quote($search, '/') . ')|siu',
        '<span style="background-color: #FFFF33">$1</span>',
        $text
    );
}

switch ($mod) {
    case 'reset':
        // Очищаем историю личных поисковых запросов
        if ($user->isValid()) {
            if (isset($_POST['submit'])) {
                $db->exec("DELETE FROM `cms_users_data` WHERE `user_id` = '" . $user->id . "' AND `key` = 'forum_search' LIMIT 1");
                header('Location: ?act=search');
            } else {
                echo $view->render(
                    'forum::clear_search_history',
                    [
                        'title'      => __('Forum search'),
                        'page_title' => __('Forum search'),
                        'back_url'   => '/forum/?act=search',
                    ]
                );
                exit;
            }
        }
        break;

    default:
        // Принимаем данные, выводим форму поиска
        $search_post = isset($_POST['search']) ? trim($_POST['search']) : '';
        $search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
        $search = ! empty($search_post) ? $search_post : $search_get;
        $search = preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $search);
        $search_t = isset($_REQUEST['t']);
        $to_history = false;
        $total = 0;

        // Проверям на ошибки
        $error = (($search && mb_strlen($search) < 4) || mb_strlen($search) > 64);
        $results = [];
        if ($search && ! $error) {
            // Выводим результаты запроса
            $array = explode(' ', $search);
            $count = count($array);
            if ($search_t) {
                $query = $db->quote('%' . $search . '%');
                $total = $db->query(
                    '
                SELECT COUNT(*) FROM `forum_topic`
                WHERE `name` LIKE ' . $query . '
                ' . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)")
                )->fetchColumn();
            } else {
                $query = $db->quote($search);
                $total = $db->query(
                    "
                SELECT COUNT(*) FROM `forum_messages`
                WHERE MATCH (`text`) AGAINST (${query} IN BOOLEAN MODE)
                " . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)")
                )->fetchColumn();
            }

            if ($total) {
                $to_history = true;
                if ($search_t) {
                    $req = $db->query(
                        '
                    SELECT *
                    FROM `forum_topic`
                    WHERE `name` LIKE ' . $query . '
                    ' . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)") . "
                    ORDER BY `name` DESC
                    LIMIT ${start}, " . $user->config->kmess
                    );
                } else {
                    $req = $db->query(
                        "
                    SELECT *, MATCH (`text`) AGAINST (${query} IN BOOLEAN MODE) as `rel`
                    FROM `forum_messages`
                    WHERE MATCH (`text`) AGAINST (${query} IN BOOLEAN MODE)
                    " . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)") . "
                    ORDER BY `rel` DESC
                    LIMIT ${start}, " . $user->config->kmess
                    );
                }

                $i = 0;

                while ($res = $req->fetch()) {
                    if (! $search_t) {
                        // Поиск только в тексте
                        $res_t = $db->query("SELECT `id`,`name` FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
                        $res['name'] = $res_t['name'];
                    } else {
                        // Поиск в названиях тем
                        $res_p = $db->query("SELECT `name` FROM `forum_topic` WHERE `id` = '" . $res['id'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();

                        foreach ($array as $val) {
                            $res['name'] = ReplaceKeywords($val, $res['name']);
                        }
                    }

                    if ($search_t) {
                        $date = $user->rights >= 7 ? $res['mod_last_post_date'] : $res['last_post_date'];
                    } else {
                        $date = $res['date'];
                    }

                    $res['formatted_date'] = $tools->displayDate($date);

                    $text = $search_t ? $res_p['name'] : $res['text'];

                    foreach ($array as $srch) {
                        $needle = strtolower(str_replace('*', '', $srch));
                        $pos = (! empty($res['text']) && ! empty($needle)) ? mb_stripos($res['text'], $needle) : false;
                        if ($pos !== false) {
                            break;
                        }
                    }
                    if (! isset($pos) || $pos < 100) {
                        $pos = 100;
                    }
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $text = $tools->checkout(mb_substr($text, ($pos - 100), 400), 1);
                    if (! $search_t) {
                        foreach ($array as $val) {
                            $text = ReplaceKeywords($val, $text);
                        }
                    }

                    $res['formatted_text'] = $text;
                    $res['read_more'] = '';
                    if (mb_strlen($res['text'] ?? '') > 500) {
                        $res['read_more'] = '/forum/?act=show_post&amp;id=' . $res['id'];
                    }
                    $res['topic_url'] = '/forum/?type=topic&id=' . ($search_t ? $res['id'] : $res_t['id']);

                    $res['post_url'] = '';
                    if (! $search_t) {
                        $res['post_url'] = '/forum/?act=show_post&amp;id=' . $res['id'];
                    }

                    $results[] = $res;
                }
            }
        } elseif ($error) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Forum search'),
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid length'),
                    'back_url'      => '/forum/?act=search',
                    'back_url_name' => __('Repeat'),
                ]
            );
            exit;
        }

        // Обрабатываем и показываем историю личных поисковых запросов
        $history_list = [];
        if ($user->isValid()) {
            $req = $db->query("SELECT * FROM `cms_users_data` WHERE `user_id` = '" . $user->id . "' AND `key` = 'forum_search' LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $history = unserialize($res['val'], ['allowed_classes' => false]);

                // Добавляем запрос в историю
                if ($to_history && ! in_array($search, $history)) {
                    if (count($history) > 20) {
                        array_shift($history);
                    }

                    $history[] = $search;
                    $db->exec(
                        'UPDATE `cms_users_data` SET
                        `val` = ' . $db->quote(serialize($history)) . "
                        WHERE `user_id` = '" . $user->id . "' AND `key` = 'forum_search'
                        LIMIT 1
                    "
                    );
                }

                sort($history);

                foreach ($history as $val) {
                    $history_list[] = '<a href="?act=search&amp;search=' . urlencode($val) . '">' . htmlspecialchars($val) . '</a>';
                }
            } elseif ($to_history) {
                $history[] = $search;
                $db->exec(
                    "INSERT INTO `cms_users_data` SET
                    `user_id` = '" . $user->id . "',
                    `key` = 'forum_search',
                    `val` = " . $db->quote(serialize($history)) . '
                '
                );
            }
        }

        echo $view->render(
            'forum::forum_search',
            [
                'title'             => __('Forum search'),
                'page_title'        => __('Forum search'),
                'pagination'        => $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $user->config->kmess),
                'query'             => $tools->checkout($search, 0, 0),
                'search_t'          => $search_t,
                'results'           => $results,
                'total'             => $total,
                'search_history'    => $history_list,
                'history_reset_url' => '/forum/?act=search&amp;mod=reset',
            ]
        );
}
