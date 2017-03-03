<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms;

class Comments
{
    // Служебные данные
    private $object_table;                                // Таблица комментируемых объектов
    private $comments_table;                              // Таблица с комментариями
    private $sub_id = false;                              // Идентификатор комментируемого объекта
    private $item;                                        // Локальный идентификатор
    private $owner = false;
    private $ban = false;                                 // Находится ли юзер в бане?
    private $url;                                         // URL формируемых ссылок

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var \Johncms\Tools
     */
    private $tools;

    /**
     * @var Api\UserInterface::class
     */
    private $systemUser;

    // Права доступа
    private $access_reply = false;                        // Возможность отвечать на комментарий
    private $access_edit = false;                         // Возможность редактировать комментарий
    private $access_delete = false;                       // Возможность удалять комментарий
    private $access_level = 6;                            // Уровень доступа для Администрации

    // Параметры отображения комментариев
    public $min_lenght = 4;                               // Мин. к-во символов в комментарии
    public $max_lenght = 5000;                            // Макс. к-во символов в комментарии
    public $captcha = false;                              // Показывать CAPTCHA

    // Возвращаемые значения
    public $total = 0;                                    // Общее число комментариев объекта
    public $added = false;                                // Метка добавления нового комментария

    function __construct($arg = [])
    {
        global $mod, $start, $kmess;

        /** @var \Psr\Container\ContainerInterface $container */
        $container = \App::getContainer();
        $this->tools = $container->get(Api\ToolsInterface::class);
        $this->db = $container->get(\PDO::class);
        $this->systemUser = $container->get(Api\UserInterface::class );

        $this->comments_table = $arg['comments_table'];
        $this->object_table = !empty($arg['object_table']) ? $arg['object_table'] : false;
        $homeurl = \App::getContainer()->get('config')['johncms']['homeurl'];

        if (!empty($arg['sub_id_name']) && !empty($arg['sub_id'])) {
            $this->sub_id = $arg['sub_id'];
            $this->url = $arg['script'] . '&amp;' . $arg['sub_id_name'] . '=' . $arg['sub_id'];
        } else {
            $this->url = $arg['script'];
        }

        $this->item = isset($_GET['item']) ? abs(intval($_GET['item'])) : false;

        // Получаем данные пользователя
        $this->ban = !empty($this->systemUser->ban);

        // Назначение пользовательских прав
        if (isset($arg['owner'])) {
            $this->owner = $arg['owner'];

            if ($this->systemUser->isValid() && $arg['owner'] == $this->systemUser->id && !$this->ban) {
                $this->access_delete = isset($arg['owner_delete']) ? $arg['owner_delete'] : false;
                $this->access_reply = isset($arg['owner_reply']) ? $arg['owner_reply'] : false;
                $this->access_edit = isset($arg['owner_edit']) ? $arg['owner_edit'] : false;
            }
        }

        // Открываем доступ для Администрации
        if ($this->systemUser->rights >= $this->access_level) {
            $this->access_reply = true;
            $this->access_edit = true;
            $this->access_delete = true;
        }

        switch ($mod) {
            case 'reply':
                // Отвечаем на комментарий
                if ($this->systemUser->isValid() && $this->item && $this->access_reply && !$this->ban) {
                    echo '<div class="phdr"><a href="' . $this->url . '"><b>' . $arg['title'] . '</b></a> | ' . _t('Reply', 'system') . '</div>';
                    $req = $this->db->query("SELECT * FROM `" . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                    if ($req->rowCount()) {
                        $res = $req->fetch();
                        $attributes = unserialize($res['attributes']);

                        if (!empty($res['reply']) && $attributes['reply_rights'] > $this->systemUser->rights) {
                            echo $this->tools->displayError(_t('Administrator already replied to this message', 'system'), '<a href="' . $this->url . '">' . _t('Back', 'system') . '</a>');
                        } elseif (isset($_POST['submit'])) {
                            $message = $this->msg_check();

                            if (empty($message['error'])) {
                                $attributes['reply_id'] = $this->systemUser->id;
                                $attributes['reply_rights'] = $this->systemUser->rights;
                                $attributes['reply_name'] = $this->systemUser->name;
                                $attributes['reply_time'] = time();

                                $this->db->prepare('
                                  UPDATE `' . $this->comments_table . '` SET
                                  `reply` = ?,
                                  `attributes` = ?
                                  WHERE `id` = ?
                                ')->execute([
                                    $message['text'],
                                    serialize($attributes),
                                    $this->item,
                                ]);

                                header('Location: ' . str_replace('&amp;', '&', $this->url));
                            } else {
                                echo $this->tools->displayError($message['error'], '<a href="' . $this->url . '&amp;mod=reply&amp;item=' . $this->item . '">' . _t('Back', 'system') . '</a>');
                            }
                        } else {
                            $text = '<a href="' . $homeurl . '/profile/?user=' . $res['user_id'] . '"><b>' . $attributes['author_name'] . '</b></a>' .
                                ' (' . $this->tools->displayDate($res['time']) . ')<br />' .
                                $this->tools->checkout($res['text']);
                            $reply = $this->tools->checkout($res['reply']);
                            echo $this->msg_form('&amp;mod=reply&amp;item=' . $this->item, $text, $reply) .
                                '<div class="phdr"><a href="' . $this->url . '">' . _t('Back', 'system') . '</a></div>';
                        }
                    } else {
                        echo $this->tools->displayError(_t('Wrong data'), '<a href="' . $this->url . '">' . _t('Back', 'system') . '</a>');
                    }
                }
                break;

            case 'edit':
                // Редактируем комментарий
                if ($this->systemUser->isValid() && $this->item && $this->access_edit && !$this->ban) {
                    echo '<div class="phdr"><a href="' . $this->url . '"><b>' . $arg['title'] . '</b></a> | ' . _t('Edit', 'system') . '</div>';
                    $req = $this->db->query("SELECT * FROM `" . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                    if ($req->rowCount()) {
                        $res = $req->fetch();
                        $attributes = unserialize($res['attributes']);
                        $user = $this->tools->getUser($res['user_id']);

                        if ($user['rights'] > $this->systemUser->rights) {
                            echo $this->tools->displayError(_t('You cannot edit posts of higher administration', 'system'), '<a href="' . $this->url . '">' . _t('Back', 'system') . '</a>');
                        } elseif (isset($_POST['submit'])) {
                            $message = $this->msg_check();

                            if (empty($message['error'])) {
                                $attributes['edit_id'] = $this->systemUser->id;
                                $attributes['edit_name'] = $this->systemUser->name;
                                $attributes['edit_time'] = time();

                                if (isset($attributes['edit_count'])) {
                                    ++$attributes['edit_count'];
                                } else {
                                    $attributes['edit_count'] = 1;
                                }

                                $this->db->prepare('
                                  UPDATE `' . $this->comments_table . '` SET
                                  `text` = ?,
                                  `attributes` = ?
                                  WHERE `id` = ?
                                ')->execute([
                                    $message['text'],
                                    serialize($attributes),
                                    $this->item,
                                ]);

                                header('Location: ' . str_replace('&amp;', '&', $this->url));
                            } else {
                                echo $this->tools->displayError($message['error'], '<a href="' . $this->url . '&amp;mod=edit&amp;item=' . $this->item . '">' . _t('Back', 'system') . '</a>');
                            }
                        } else {
                            $author = '<a href="' . $homeurl . '/profile/?user=' . $res['user_id'] . '"><b>' . $attributes['author_name'] . '</b></a>';
                            $author .= ' (' . $this->tools->displayDate($res['time']) . ')<br />';
                            $text = $this->tools->checkout($res['text']);
                            echo $this->msg_form('&amp;mod=edit&amp;item=' . $this->item, $author, $text);
                        }
                    } else {
                        echo $this->tools->displayError(_t('Wrong data', 'system'), '<a href="' . $this->url . '">' . _t('Back', 'system') . '</a>');
                    }

                    echo '<div class="phdr"><a href="' . $this->url . '">' . _t('Back', 'system') . '</a></div>';
                }
                break;

            case 'del':
                // Удаляем комментарий
                if ($this->systemUser->isValid() && $this->item && $this->access_delete && !$this->ban) {
                    if (isset($_GET['yes'])) {
                        $req = $this->db->query("SELECT * FROM `" . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                        if ($req->rowCount()) {
                            $res = $req->fetch();

                            if (isset($_GET['all'])) {
                                // Удаляем все комментарии выбранного пользователя
                                $count = $this->db->query("SELECT COUNT(*) FROM `" . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "' AND `user_id` = '" . $res['user_id'] . "'")->fetchColumn();
                                $this->db->exec("DELETE FROM `" . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "' AND `user_id` = '" . $res['user_id'] . "'");
                            } else {
                                // Удаляем отдельный комментарий
                                $count = 1;
                                $this->db->exec("DELETE FROM `" . $this->comments_table . "` WHERE `id` = '" . $this->item . "'");
                            }

                            // Вычитаем баллы из статистики пользователя
                            $req_u = $this->db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

                            if ($req_u->rowCount()) {
                                $res_u = $req_u->fetch();
                                $count = $res_u['komm'] > $count ? $res_u['komm'] - $count : 0;
                                $this->db->exec("UPDATE `users` SET `komm` = '$count' WHERE `id` = '" . $res['user_id'] . "'");
                            }

                            // Обновляем счетчик комментариев
                            $this->msg_total(1);
                        }
                        header('Location: ' . str_replace('&amp;', '&', $this->url));
                    } else {
                        echo '<div class="phdr"><a href="' . $this->url . '"><b>' . $arg['title'] . '</b></a> | ' . _t('Delete', 'system') . '</div>' .
                            '<div class="rmenu"><p>' . _t('Do you really want to delete?', 'system') . '<br />' .
                            '<a href="' . $this->url . '&amp;mod=del&amp;item=' . $this->item . '&amp;yes">' . _t('Delete', 'system') . '</a> | ' .
                            '<a href="' . $this->url . '">' . _t('Cancel', 'system') . '</a><br />' .
                            '<div class="sub">' . _t('Clear all messages from this user', 'system') . '<br />' .
                            '<span class="red"><a href="' . $this->url . '&amp;mod=del&amp;item=' . $this->item . '&amp;yes&amp;all">' . _t('Clear', 'system') . '</a></span>' .
                            '</div></p></div>' .
                            '<div class="phdr"><a href="' . $this->url . '">' . _t('Back', 'system') . '</a></div>';
                    }
                }
                break;

            default:
                if (!empty($arg['context_top'])) {
                    echo $arg['context_top'];
                }

                // Добавляем новый комментарий
                if ($this->systemUser->isValid() && !$this->ban && !$this->tools->isIgnor($this->owner) && isset($_POST['submit']) && ($message = $this->msg_check(1)) !== false) {
                    if (empty($message['error'])) {
                        // Записываем комментарий в базу
                        $this->add_comment($message['text']);
                        $this->total = $this->msg_total(1);
                        $_SESSION['code'] = $message['code'];
                    } else {
                        // Показываем ошибки, если есть
                        echo $this->tools->displayError($message['error']);
                        $this->total = $this->msg_total();
                    }
                } else {
                    $this->total = $this->msg_total();
                }

                // Показываем форму ввода
                if ($this->systemUser->isValid() && !$this->ban && !$this->tools->isIgnor($this->owner)) {
                    echo $this->msg_form();
                }

                // Показываем список комментариев
                echo '<div class="phdr"><b>' . $arg['title'] . '</b></div>';

                if ($this->total > $kmess) {
                    echo '<div class="topmenu">' . $this->tools->displayPagination($this->url . '&amp;', $start, $this->total, $kmess) . '</div>';
                }

                if ($this->total) {
                    $req = $this->db->query("SELECT `" . $this->comments_table . "`.*, `" . $this->comments_table . "`.`id` AS `subid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                    FROM `" . $this->comments_table . "` LEFT JOIN `users` ON `" . $this->comments_table . "`.`user_id` = `users`.`id`
                    WHERE `sub_id` = '" . $this->sub_id . "' ORDER BY `subid` DESC LIMIT $start, $kmess");
                    $i = 0;

                    while ($res = $req->fetch()) {
                        $attributes = unserialize($res['attributes']);
                        $res['name'] = $attributes['author_name'];
                        $res['ip'] = $attributes['author_ip'];
                        $res['ip_via_proxy'] = isset($attributes['author_ip_via_proxy']) ? $attributes['author_ip_via_proxy'] : 0;
                        $res['browser'] = $attributes['author_browser'];
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        $menu = [
                            $this->access_reply ? '<a href="' . $this->url . '&amp;mod=reply&amp;item=' . $res['subid'] . '">' . _t('Reply', 'system') . '</a>' : '',
                            $this->access_edit ? '<a href="' . $this->url . '&amp;mod=edit&amp;item=' . $res['subid'] . '">' . _t('Edit', 'system') . '</a>' : '',
                            $this->access_delete ? '<a href="' . $this->url . '&amp;mod=del&amp;item=' . $res['subid'] . '">' . _t('Delete', 'system') . '</a>' : '',
                        ];
                        $text = $this->tools->checkout($res['text'], 1, 1);
                        $text = $this->tools->smilies($text, $res['rights'] >= 1 ? 1 : 0);

                        if (isset($attributes['edit_count'])) {
                            $text .= '<br /><span class="gray"><small>' . _t('Edited', 'system') . ': <b>' . $attributes['edit_name'] . '</b>' .
                                ' (' . $this->tools->displayDate($attributes['edit_time']) . ') <b>' .
                                '[' . $attributes['edit_count'] . ']</b></small></span>';
                        }

                        if (!empty($res['reply'])) {
                            $reply = $this->tools->checkout($res['reply'], 1, 1);
                            $reply = $this->tools->smilies($reply, $attributes['reply_rights'] >= 1 ? 1 : 0);
                            $text .= '<div class="' . ($attributes['reply_rights'] ? '' : 'g') . 'reply"><small>' .
                                '<a href="' . $homeurl . '/profile/?user=' . $attributes['reply_id'] . '"><b>' . $attributes['reply_name'] . '</b></a>' .
                                ' (' . $this->tools->displayDate($attributes['reply_time']) . ')</small><br>' . $reply . '</div>';
                        }

                        $user_arg = [
                            'header' => ' <span class="gray">(' . $this->tools->displayDate($res['time']) . ')</span>',
                            'body' => $text,
                            'sub' => implode(' | ', array_filter($menu)),
                            'iphide' => ($this->systemUser->rights ? false : true),
                        ];
                        echo $this->tools->displayUser($res, $user_arg);
                        echo '</div>';
                        ++$i;
                    }
                } else {
                    echo '<div class="menu"><p>' . _t('The list is empty', 'system') . '</p></div>';
                }

                echo '<div class="phdr">' . _t('Total', 'system') . ': ' . $this->total . '</div>';

                if ($this->total > $kmess) {
                    echo '<div class="topmenu">' . $this->tools->displayPagination($this->url . '&amp;', $start, $this->total, $kmess) . '</div>' .
                        '<p><form action="' . $this->url . '" method="post">' .
                        '<input type="text" name="page" size="2"/>' .
                        '<input type="submit" value="' . _t('To Page', 'system') . ' &gt;&gt;"/>' .
                        '</form></p>';
                }

                if (!empty($arg['context_bottom'])) {
                    echo $arg['context_bottom'];
                }
        }
    }

    // Добавляем комментарий в базу
    private function add_comment($message)
    {
        /** @var \Psr\Container\ContainerInterface $container */
        $container = \App::getContainer();

        /** @var Api\EnvironmentInterface $env */
        $env = $container->get(Api\EnvironmentInterface::class);

        // Формируем атрибуты сообщения
        $attributes = [
            'author_name' => $this->systemUser->name,
            'author_ip' => $env->getIp(),
            'author_ip_via_proxy' => $env->getIpViaProxy(),
            'author_browser' => $env->getUserAgent(),
        ];

        // Записываем комментарий в базу
        $this->db->prepare('
          INSERT INTO `' . $this->comments_table . '` SET
          `sub_id` = ?,
          `user_id` = ?,
          `text` = ?,
          `reply` = \'\',
          `time` = ?,
          `attributes` = ?
        ')->execute([
            intval($this->sub_id),
            $this->systemUser->id,
            $message,
            time(),
            serialize($attributes),
        ]);

        // Обновляем статистику пользователя
        $this->db->exec("UPDATE `users` SET `komm` = '" . ++$this->systemUser->komm . "', `lastpost` = '" . time() . "' WHERE `id` = '" . $this->systemUser->id . "'");

        if ($this->owner && $this->systemUser->id == $this->owner) {
            $this->db->exec("UPDATE `users` SET `comm_old` = '" . $this->systemUser->komm . "' WHERE `id` = '" . $this->systemUser->id . "'");
        }

        $this->added = true;
    }

    // Форма ввода комментария
    private function msg_form($submit_link = '', $text = '', $reply = '')
    {
        return '<div class="gmenu"><form name="form" action="' . $this->url . $submit_link . '" method="post"><p>' .
            (!empty($text) ? '<div class="quote">' . $text . '</div></p><p>' : '') .
            '<b>' . _t('Message', 'system') . '</b>: <small>(Max. ' . $this->max_lenght . ')</small><br />' .
            '</p><p>' . \App::getContainer()->get(Api\BbcodeInterface::class)->buttons('form', 'message') .
            '<textarea rows="' . $this->systemUser->getConfig()->fieldHeight . '" name="message">' . $reply . '</textarea><br>' .
            '<input type="hidden" name="code" value="' . rand(1000, 9999) . '" /><input type="submit" name="submit" value="' . _t('Send', 'system') . '"/></p></form></div>';
    }

    // Проверка текста сообщения
    // $rpt_check (boolean)    проверка на повтор сообщений
    private function msg_check($rpt_check = false)
    {
        $error = [];
        $message = isset($_POST['message']) ? mb_substr(trim($_POST['message']), 0, $this->max_lenght) : false;
        $code = isset($_POST['code']) ? intval($_POST['code']) : null;
        $code_chk = isset($_SESSION['code']) ? $_SESSION['code'] : null;
        $translit = isset($_POST['translit']);

        // Проверяем код
        if ($code == $code_chk) {
            return false;
        }

        // Проверяем на минимально допустимую длину
        if (mb_strlen($message) < $this->min_lenght) {
            $error[] = _t('Text is too short', 'system');
        } else {
            // Проверка на флуд
            $flood = \App::getContainer()->get(Api\ToolsInterface::class)->antiflood();

            if ($flood) {
                $error[] = _t('You cannot add the message so often<br>Please, wait', 'system') . ' ' . $flood . '&#160;' . _t('seconds', 'system');
            }
        }

        // Проверка на повтор сообщений
        if (!$error && $rpt_check) {
            $req = $this->db->query("SELECT * FROM `" . $this->comments_table . "` WHERE `user_id` = '" . $this->systemUser->id . "' ORDER BY `id` DESC LIMIT 1");
            $res = $req->fetch();

            if (mb_strtolower($message) == mb_strtolower($res['text'])) {
                $error[] = _t('Message already exists', 'system');
            }
        }

        // Возвращаем результат
        return [
            'code' => $code,
            'text' => $message,
            'error' => $error,
        ];
    }

    // Счетчик комментариев
    private function msg_total($update = false)
    {
        $total = $this->db->query("SELECT COUNT(*) FROM `" . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "'")->fetchColumn();

        if ($update) {
            // Обновляем счетчики в таблице объекта
            $this->db->exec("UPDATE `" . $this->object_table . "` SET `comm_count` = '$total' WHERE `id` = '" . $this->sub_id . "'");
        }

        return $total;
    }
}
