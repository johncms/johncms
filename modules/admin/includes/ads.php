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
 */

$title = __('Advertisement');
$nav_chain->add($title, '/admin/ads/');
$data = [];

switch ($mod) {
    case 'edit':
        // Добавляем / редактируем ссылку
        $title = ($id ? __('Edit link') : __('Add link'));
        $nav_chain->add($title);

        if ($id) {
            // Если ссылка редактироется, запрашиваем ее данные в базе
            $req = $db->query('SELECT * FROM `cms_ads` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'    => $title,
                        'type'     => 'alert-danger',
                        'message'  => __('Wrong data'),
                        'back_url' => '/admin/ads/',
                    ]
                );
                exit;
            }
        } else {
            $res = ['link' => 'http://'];
        }

        if (isset($_POST['submit'])) {
            $link = isset($_POST['link']) ? trim($_POST['link']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $bold = isset($_POST['bold']) ? 1 : 0;
            $italic = isset($_POST['italic']) ? 1 : 0;
            $underline = isset($_POST['underline']) ? 1 : 0;
            $show = isset($_POST['show']) ? 1 : 0;
            $view_type = isset($_POST['view']) ? abs((int) ($_POST['view'])) : 0;
            $day = isset($_POST['day']) ? abs((int) ($_POST['day'])) : 0;
            $count = isset($_POST['count']) ? abs((int) ($_POST['count'])) : 0;
            $day = isset($_POST['day']) ? abs((int) ($_POST['day'])) : 0;
            $layout = isset($_POST['layout']) ? abs((int) ($_POST['layout'])) : 0;
            $type = isset($_POST['type']) ? (int) ($_POST['type']) : 0;
            $mesto = isset($_POST['mesto']) ? abs((int) ($_POST['mesto'])) : 0;
            $color = isset($_POST['color']) ? mb_substr(trim($_POST['color']), 0, 6) : '';
            $error = [];

            if (empty($link) || empty($name)) {
                $error[] = __('The required fields are not filled');
            }

            if ($type > 3 || $type < 0) {
                $type = 0;
            }

            if (! $mesto) {
                $total = $db->query("SELECT COUNT(*) FROM `cms_ads` WHERE `mesto` = '" . $mesto . "' AND `type` = '" . $type . "'")->fetchColumn();

                if ($total) {
                    $error[] = __('This place is occupied');
                }
            }

            if ($color) {
                if (preg_match("/[^\da-fA-F_]+/", $color)) {
                    $error[] = __('Invalid characters');
                }
                if (strlen($color) < 6) {
                    $error[] = __('Color is specified incorrectly');
                }
            }

            if ($error) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'    => $title,
                        'type'     => 'alert-danger',
                        'message'  => $error,
                        'back_url' => '/admin/ads/?from=addlink',
                    ]
                );
                exit;
            }

            if ($id) {
                // Обновляем ссылку после редактирования
                $db->prepare(
                    '
                  UPDATE `cms_ads` SET
                  `type` = ?,
                  `view` = ?,
                  `link` = ?,
                  `name` = ?,
                  `color` = ?,
                  `count_link` = ?,
                  `day` = ?,
                  `layout` = ?,
                  `show` = ?,
                  `bold` = ?,
                  `italic` = ?,
                  `underline` = ?
                  WHERE `id` = ?
                '
                )->execute(
                    [
                        $type,
                        $view_type,
                        $link,
                        $name,
                        $color,
                        $count,
                        $day,
                        $layout,
                        $show,
                        $bold,
                        $italic,
                        $underline,
                        $id,
                    ]
                );
            } else {
                // Добавляем новую ссылку
                $req = $db->query('SELECT `mesto` FROM `cms_ads` ORDER BY `mesto` DESC LIMIT 1');

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $mesto = $res['mesto'] + 1;
                } else {
                    $mesto = 1;
                }

                $db->prepare(
                    '
                  INSERT INTO `cms_ads` SET
                  `type` = ?,
                  `view` = ?,
                  `mesto` = ?,
                  `link` = ?,
                  `name` = ?,
                  `color` = ?,
                  `count_link` = ?,
                  `day` = ?,
                  `layout` = ?,
                  `show` = ?,
                  `time` = ?,
                  `to` = 0,
                  `bold` = ?,
                  `italic` = ?,
                  `underline` = ?
                '
                )->execute(
                    [
                        $type,
                        $view_type,
                        $mesto,
                        $link,
                        $name,
                        $color,
                        $count,
                        $day,
                        $layout,
                        $show,
                        time(),
                        $bold,
                        $italic,
                        $underline,
                    ]
                );
            }

            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => ($id ? __('Link successfully changed') : __('Link successfully added')),
                    'back_url'      => '/admin/ads/?sort=' . $type,
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            $data['fields'] = [
                'link'       => ! empty($res['link']) ? htmlentities($res['link']) : '',
                'name'       => ! empty($res['name']) ? htmlentities($res['link']) : '',
                'color'      => $res['color'] ?? '',
                'count_link' => $res['count_link'] ?? '',
                'day'        => $res['day'] ?? '',
                'view'       => $res['view'] ?? '',
                'type'       => $res['type'] ?? '',
                'layout'     => $res['layout'] ?? '',
            ];
            $data['fields'] = array_merge($data['fields'], $res);

            $data['form_action'] = '?mod=edit' . ($id ? '&amp;id=' . $id : '');
            echo $view->render(
                'admin::ads_add',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    case 'down':
        // Перемещаем на позицию вниз
        if ($id) {
            $req = $db->query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $mesto = $res['mesto'];

                $req = $db->query("SELECT * FROM `cms_ads` WHERE `mesto` > '${mesto}' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` ASC");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto}' WHERE `id` = '${id2}'");
                }
            }
        }
        header('Location: ' . getenv('HTTP_REFERER'));
        break;

    case 'up':
        // Перемещаем на позицию вверх
        if ($id) {
            $req = $db->query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $mesto = $res['mesto'];

                $req = $db->query("SELECT * FROM `cms_ads` WHERE `mesto` < '${mesto}' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` DESC");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto}' WHERE `id` = '${id2}'");
                }
            }
        }
        header('Location: ' . getenv('HTTP_REFERER') . '');
        break;

    case 'del':
        // Удаляем ссылку
        if ($id) {
            $title = __('Delete');
            $nav_chain->add($title);
            if (isset($_POST['submit'])) {
                $db->exec("DELETE FROM `cms_ads` WHERE `id` = '${id}'");
                header('Location: ' . $_POST['ref']);
            } else {
                $data['hidden_fields'] = [
                    [
                        'name'  => 'ref',
                        'value' => htmlspecialchars($_SERVER['HTTP_REFERER']),
                    ],
                ];
                $data['message'] = __('Are you sure want to delete link?');
                $data['form_action'] = '??act=ads&amp;mod=clear';
                $data['back_url'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
                echo $view->render(
                    'admin::ads_confirm',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            }
        }
        break;

    case 'clear':
        // Очистка базы от неактивных ссылок
        if (isset($_POST['submit'])) {
            $db->exec("DELETE FROM `cms_ads` WHERE `to` = '1'");
            $db->query('OPTIMIZE TABLE `cms_ads`');
            header('location: ?act=ads');
        } else {
            $data['message'] = __('Are you sure you want to delete all inactive links?');
            $data['form_action'] = '??act=ads&amp;mod=clear';
            $data['back_url'] = '/admin/ads/';
            echo $view->render(
                'admin::ads_confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    case 'show':
        // Восстанавливаем / скрываем ссылку
        if ($id) {
            $req = $db->query("SELECT * FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $db->exec("UPDATE `cms_ads` SET `to`='" . ($res['to'] ? 0 : 1) . "' WHERE `id` = '${id}'");
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        break;

    default:
        // Главное меню модуля управления рекламой
        $type = $request->getQuery('type', 0, FILTER_VALIDATE_INT);
        $data['filters'] = [
            [
                'url'    => '?act=ads',
                'name'   => __('Before the menu'),
                'active' => ! $type,
            ],
            [
                'url'    => '?act=ads&amp;type=1',
                'name'   => __('After the menu'),
                'active' => $type === 1,
            ],
            [
                'url'    => '?act=ads&amp;type=2',
                'name'   => __('At the top of the page'),
                'active' => $type === 2,
            ],
            [
                'url'    => '?act=ads&amp;type=3',
                'name'   => __('At the bottom of the page'),
                'active' => $type === 3,
            ],
        ];

        $array_placing = [
            __('All pages'),
            __('Only on Homepage'),
            __('On all, except Homepage'),
        ];
        $array_show = [
            __('Everyone'),
            __('Guests'),
            __('Users'),
        ];

        $total = $db->query("SELECT COUNT(*) FROM `cms_ads` WHERE `type` = '${type}'")->fetchColumn();

        if ($total) {
            $req = $db->query("SELECT * FROM `cms_ads` WHERE `type` = '${type}' ORDER BY `mesto` ASC LIMIT " . $start . ',' . $user->config->kmess);
            $items = [];
            while ($res = $req->fetch()) {
                $name = str_replace('|', '; ', $res['name']);
                $name = htmlentities($name, ENT_QUOTES, 'UTF-8');
                $res['name'] = $name;
                $res['link'] = htmlspecialchars($res['link']);
                $res['display_time'] = $tools->displayDate($res['time']);
                $res['place'] = $array_placing[$res['layout']];
                $res['show_for'] = $array_show[$res['view']];

                // Вычисляем условия договора на рекламу
                $agreement = [];
                $remains = [];

                if (! empty($res['count_link'])) {
                    $agreement[] = $res['count_link'] . ' ' . __('hits');
                    $remains_count = $res['count_link'] - $res['count'];
                    if ($remains_count > 0) {
                        $remains[] = $remains_count . ' ' . __('hits');
                    }
                }

                if (! empty($res['day'])) {
                    $agreement[] = $tools->timecount($res['day'] * 86400);
                    $remains_count = $res['day'] * 86400 - (time() - $res['time']);
                    if ($remains_count > 0) {
                        $remains[] = $tools->timecount($remains_count);
                    }
                }

                $res['agreement'] = ! empty($agreement) ? implode(', ', $agreement) : '';
                $res['remains'] = ! empty($remains) ? implode(', ', $remains) : '';

                $styles = '';
                if (! empty($res['color'])) {
                    $styles .= __('Color:') . ' ' . $res['color'];
                }
                if (! empty($res['bold'])) {
                    $styles .= ' ' . __('Bold');
                }
                if (! empty($res['italic'])) {
                    $styles .= ' ' . __('Italic');
                }
                if (! empty($res['underline'])) {
                    $styles .= ' ' . __('Underline');
                }
                $res['styles'] = '';
                $items[] = $res;
            }
        }

        if ($total > $user->config->kmess) {
            $data['pagination'] = $tools->displayPagination('?act=ads&amp;type=' . $type . '&amp;', $start, $total, $user->config->kmess);
        }

        $data['back_url'] = '/admin/';
        $data['total'] = $total ?? 0;
        $data['items'] = $items ?? [];

        echo $view->render(
            'admin::ads_index',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
