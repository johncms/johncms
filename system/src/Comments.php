<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\System\Container\Factory;
use Johncms\System\Http\Environment;
use Johncms\System\Users\User;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use PDO;
use Psr\Container\ContainerInterface;

class Comments
{
    /** @var bool|mixed Таблица комментируемых объектов */
    private $object_table;

    /** @var string Таблица с комментариями */
    private $comments_table;

    /** @var string Namespace для шаблонов */
    private $templates_namespace;

    /** @var bool|mixed Идентификатор комментируемого объекта */
    private $sub_id = false;

    /** @var bool|int Локальный идентификатор */
    private $item;

    /** @var bool|int */
    private $owner = false;

    /** @var bool Имеет ли юзер активный бан? */
    private $ban = false;

    /** @var string URL формируемых ссылок */
    private $url;

    /** @var Render */
    private $view;

    /** @var PDO */
    private $db;

    /** @var Tools */
    private $tools;

    /** @var User */
    private $systemUser;

    /** @var bool Возможность отвечать на комментарий */
    private $access_reply = false;

    /** @var bool Возможность редактировать комментарий */
    private $access_edit = false;

    /** @var bool Возможность удалять комментарий */
    private $access_delete = false;

    /** @var int Уровень доступа для Администрации */
    private $access_level = 6;

    // Параметры отображения комментариев

    /** @var int Мин. к-во символов в комментарии */
    public $min_lenght = 4;

    /** @var int Макс. к-во символов в комментарии */
    public $max_lenght = 5000;

    /** @var bool Показывать CAPTCHA */
    public $captcha = false;

    // Возвращаемые значения

    /** @var int Общее число комментариев объекта */
    public $total = 0;

    /** @var bool Метка добавления нового комментария */
    public $added = false;

    /** @var string Страница возврата назад */
    public $back_url = '';

    /** @var NavChain $nav_chain */
    public $nav_chain;

    /**
     * Comments constructor.
     *
     * @psalm-suppress PossiblyInvalidArrayAccess
     *
     * @param array $arg
     */
    public function __construct($arg = [])
    {
        global $mod, $start;
        /** @var ContainerInterface $container */
        $container = Factory::getContainer();
        $this->tools = $container->get(Tools::class);
        $this->db = $container->get(PDO::class);
        $this->systemUser = $container->get(User::class);
        $this->view = di(Render::class);
        $this->nav_chain = di(NavChain::class);

        $kmess = $this->systemUser->config->kmess;

        $this->comments_table = $arg['comments_table'];
        $this->object_table = ! empty($arg['object_table']) ? $arg['object_table'] : false;
        $this->back_url = ! empty($arg['back_url']) ? $arg['back_url'] : '';
        $this->templates_namespace = ! empty($arg['templates_namespace']) ? $arg['templates_namespace'] : 'system';
        $homeurl = $container->get('config')['johncms']['homeurl'];

        if (! empty($arg['sub_id_name']) && ! empty($arg['sub_id'])) {
            $this->sub_id = $arg['sub_id'];
            $this->url = $arg['script'] . '&amp;' . $arg['sub_id_name'] . '=' . $arg['sub_id'];
        } else {
            $this->url = $arg['script'];
        }

        $this->item = isset($_GET['item']) ? abs((int) ($_GET['item'])) : false;

        // Получаем данные пользователя
        $this->ban = ! empty($this->systemUser->ban);

        // Назначение пользовательских прав
        if (isset($arg['owner'])) {
            $this->owner = $arg['owner'];

            if ($this->systemUser->isValid() && $arg['owner'] == $this->systemUser->id && ! $this->ban) {
                $this->access_delete = $arg['owner_delete'] ?? false;
                $this->access_reply = $arg['owner_reply'] ?? false;
                $this->access_edit = $arg['owner_edit'] ?? false;
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
                if ($this->systemUser->isValid() && $this->item && $this->access_reply && ! $this->ban) {
                    $this->nav_chain->add(d__('system', 'Reply'));
                    $req = $this->db->query('SELECT * FROM `' . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                    if ($req->rowCount()) {
                        $res = $req->fetch();
                        $attributes = unserialize($res['attributes'], ['allowed_classes' => false]);

                        if (! empty($res['reply']) && $attributes['reply_rights'] > $this->systemUser->rights) {
                            echo $this->view->render(
                                'system::pages/result',
                                [
                                    'title'         => d__('system', 'Downloads'),
                                    'type'          => 'alert-danger',
                                    'message'       => d__('system', 'Administrator already replied to this message'),
                                    'back_url'      => $this->url,
                                    'back_url_name' => d__('system', 'Back'),
                                ]
                            );
                        } elseif (isset($_POST['submit'])) {
                            $message = $this->msgCheck();

                            if (empty($message['error'])) {
                                $attributes['reply_id'] = $this->systemUser->id;
                                $attributes['reply_rights'] = $this->systemUser->rights;
                                $attributes['reply_name'] = $this->systemUser->name;
                                $attributes['reply_time'] = time();

                                $this->db->prepare(
                                    '
                                  UPDATE `' . $this->comments_table . '` SET
                                  `reply` = ?,
                                  `attributes` = ?
                                  WHERE `id` = ?
                                '
                                )->execute(
                                    [
                                        $message['text'],
                                        serialize($attributes),
                                        $this->item,
                                    ]
                                );

                                header('Location: ' . str_replace('&amp;', '&', $this->url));
                            } else {
                                echo $this->view->render(
                                    'system::pages/result',
                                    [
                                        'title'         => d__('system', 'Downloads'),
                                        'type'          => 'alert-danger',
                                        'message'       => $message['error'],
                                        'back_url'      => $this->url . '&amp;mod=reply&amp;item=' . $this->item,
                                        'back_url_name' => d__('system', 'Back'),
                                    ]
                                );
                            }
                        } else {
                            $data = [];
                            $text = '<a href="' . $homeurl . '/profile/?user=' . $res['user_id'] . '"><b>' . $attributes['author_name'] . '</b></a>' .
                                ' (' . $this->tools->displayDate($res['time']) . ')<br />' .
                                $this->tools->checkout($res['text']);
                            $reply = $this->tools->checkout($res['reply']);
                            $data['message_form'] = $this->msgForm('&amp;mod=reply&amp;item=' . $this->item, $text, $reply);

                            $data['back_url'] = $this->url;
                            $data['back_url_name'] = d__('system', 'Back');

                            echo $this->view->render(
                                $this->templates_namespace . '::pages/comments_reply',
                                [
                                    'title'      => d__('system', 'Reply'),
                                    'page_title' => d__('system', 'Reply'),
                                    'data'       => $data,

                                ]
                            );
                        }
                    } else {
                        echo $this->view->render(
                            'system::pages/result',
                            [
                                'title'         => d__('system', 'Downloads'),
                                'type'          => 'alert-danger',
                                'message'       => d__('system', 'Wrong data'),
                                'back_url'      => $this->url,
                                'back_url_name' => d__('system', 'Back'),
                            ]
                        );
                    }
                }
                break;

            case 'edit':
                // Редактируем комментарий
                if ($this->systemUser->isValid() && $this->item && $this->access_edit && ! $this->ban) {
                    $this->nav_chain->add(d__('system', 'Edit'));
                    $req = $this->db->query('SELECT * FROM `' . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                    if ($req->rowCount()) {
                        $res = $req->fetch();
                        $attributes = unserialize($res['attributes'], ['allowed_classes' => false]);
                        $user = $this->tools->getUser((int) $res['user_id']);

                        if ($user->rights > $this->systemUser->rights) {
                            echo $this->view->render(
                                'system::pages/result',
                                [
                                    'title'         => d__('system', 'Downloads'),
                                    'type'          => 'alert-danger',
                                    'message'       => d__('system', 'You cannot edit posts of higher administration'),
                                    'back_url'      => $this->url,
                                    'back_url_name' => d__('system', 'Back'),
                                ]
                            );
                        } elseif (isset($_POST['submit'])) {
                            $message = $this->msgCheck();

                            if (empty($message['error'])) {
                                $attributes['edit_id'] = $this->systemUser->id;
                                $attributes['edit_name'] = $this->systemUser->name;
                                $attributes['edit_time'] = time();

                                if (isset($attributes['edit_count'])) {
                                    ++$attributes['edit_count'];
                                } else {
                                    $attributes['edit_count'] = 1;
                                }

                                $this->db->prepare(
                                    '
                                  UPDATE `' . $this->comments_table . '` SET
                                  `text` = ?,
                                  `attributes` = ?
                                  WHERE `id` = ?
                                '
                                )->execute(
                                    [
                                        $message['text'] ?? '',
                                        serialize($attributes),
                                        $this->item,
                                    ]
                                );

                                header('Location: ' . str_replace('&amp;', '&', $this->url));
                            } else {
                                echo $this->view->render(
                                    'system::pages/result',
                                    [
                                        'title'         => d__('system', 'Downloads'),
                                        'type'          => 'alert-danger',
                                        'message'       => $message['error'],
                                        'back_url'      => $this->url . '&amp;mod=edit&amp;item=' . $this->item,
                                        'back_url_name' => d__('system', 'Back'),
                                    ]
                                );
                            }
                        } else {
                            $author = '<a href="' . $homeurl . '/profile/?user=' . $res['user_id'] . '"><b>' . $attributes['author_name'] . '</b></a>';
                            $author .= ' (' . $this->tools->displayDate($res['time']) . ')<br />';
                            $author .= $this->tools->checkout($res['text'], 1, 1);
                            $text = $this->tools->checkout($res['text']);
                            $data = [];
                            $data['message_form'] = $this->msgForm('&amp;mod=edit&amp;item=' . $this->item, $author, $text);
                            $data['back_url'] = $this->url;
                            $data['back_url_name'] = d__('system', 'Back');

                            echo $this->view->render(
                                $this->templates_namespace . '::pages/comments_reply',
                                [
                                    'title'      => d__('system', 'Edit'),
                                    'page_title' => d__('system', 'Edit'),
                                    'data'       => $data,

                                ]
                            );
                        }
                    } else {
                        echo $this->view->render(
                            'system::pages/result',
                            [
                                'title'         => d__('system', 'Downloads'),
                                'type'          => 'alert-danger',
                                'message'       => d__('system', 'Wrong data'),
                                'back_url'      => $this->url,
                                'back_url_name' => d__('system', 'Back'),
                            ]
                        );
                    }
                }
                break;

            case 'del':
                // Удаляем комментарий
                if ($this->systemUser->isValid() && $this->item && $this->access_delete && ! $this->ban) {
                    $this->nav_chain->add(d__('system', 'Delete'));
                    if (isset($_GET['yes'])) {
                        $req = $this->db->query('SELECT * FROM `' . $this->comments_table . "` WHERE `id` = '" . $this->item . "' AND `sub_id` = '" . $this->sub_id . "' LIMIT 1");

                        if ($req->rowCount()) {
                            $res = $req->fetch();

                            if (isset($_GET['all'])) {
                                // Удаляем все комментарии выбранного пользователя
                                $count = $this->db->query('SELECT COUNT(*) FROM `' . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "' AND `user_id` = '" . $res['user_id'] . "'")->fetchColumn();
                                $this->db->exec('DELETE FROM `' . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "' AND `user_id` = '" . $res['user_id'] . "'");
                            } else {
                                // Удаляем отдельный комментарий
                                $count = 1;
                                $this->db->exec('DELETE FROM `' . $this->comments_table . "` WHERE `id` = '" . $this->item . "'");
                            }

                            // Вычитаем баллы из статистики пользователя
                            $req_u = $this->db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

                            if ($req_u->rowCount()) {
                                $res_u = $req_u->fetch();
                                $count = $res_u['komm'] > $count ? $res_u['komm'] - $count : 0;
                                $this->db->exec("UPDATE `users` SET `komm` = '${count}' WHERE `id` = '" . $res['user_id'] . "'");
                            }

                            // Обновляем счетчик комментариев
                            $this->msgTotal(1);
                        }
                        header('Location: ' . str_replace('&amp;', '&', $this->url));
                    } else {
                        $data = [
                            'delete_url' => $this->url . '&amp;mod=del&amp;item=' . $this->item . '&amp;yes',
                            'back_url'   => $this->url,
                            'clear_url'  => $this->url . '&amp;mod=del&amp;item=' . $this->item . '&amp;yes&amp;all',
                        ];

                        echo $this->view->render(
                            $this->templates_namespace . '::pages/comments_delete',
                            [
                                'title'      => d__('system', 'Delete'),
                                'page_title' => d__('system', 'Delete'),
                                'data'       => $data,

                            ]
                        );
                    }
                }
                break;

            default:
                $data = [];

                if (
                    ! $this->ban &&
                    isset($_POST['submit']) &&
                    $this->systemUser->isValid() &&
                    ! $this->tools->isIgnor($this->owner) &&
                    ($message = $this->msgCheck(true)) !== false
                ) {
                    if (empty($message['error'])) {
                        // Записываем комментарий в базу
                        $this->addComment($message['text']);
                        $this->total = $this->msgTotal(1);
                        $_SESSION['code'] = $message['code'];
                    } else {
                        // Показываем ошибки, если есть
                        $data['error'] = $message['error'];
                        $this->total = $this->msgTotal();
                    }
                } else {
                    $this->total = $this->msgTotal();
                }

                $items = [];
                if ($this->total) {
                    $req = $this->db->query(
                        'SELECT `' . $this->comments_table . '`.*, `' . $this->comments_table . '`.`id` AS `subid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                    FROM `' . $this->comments_table . '` LEFT JOIN `users` ON `' . $this->comments_table . "`.`user_id` = `users`.`id`
                    WHERE `sub_id` = '" . $this->sub_id . "' ORDER BY `subid` DESC LIMIT ${start}, ${kmess}"
                    );

                    while ($res = $req->fetch()) {
                        $attributes = unserialize($res['attributes'], ['allowed_classes' => false]);
                        $res['name'] = $attributes['author_name'];
                        $res['ip'] = $attributes['author_ip'];
                        $res['ip_via_proxy'] = $attributes['author_ip_via_proxy'] ?? 0;
                        $res['user_agent'] = $attributes['author_browser'];
                        $res['created'] = $this->tools->displayDate($res['time']);

                        $res['reply_url'] = '';
                        $res['edit_url'] = '';
                        $res['delete_url'] = '';
                        if ($this->access_reply) {
                            $res['reply_url'] = $this->url . '&amp;mod=reply&amp;item=' . $res['subid'];
                        }
                        if ($this->access_edit) {
                            $res['edit_url'] = $this->url . '&amp;mod=edit&amp;item=' . $res['subid'];
                        }
                        if ($this->access_delete) {
                            $res['delete_url'] = $this->url . '&amp;mod=del&amp;item=' . $res['subid'];
                        }

                        $res['has_edit'] = ($this->access_edit || $this->access_delete);

                        $text = $this->tools->checkout($res['text'], 1, 1);
                        $text = $this->tools->smilies($text, $res['rights'] >= 1 ? 1 : 0);

                        $res['post_text'] = $text;
                        $res['edit_count'] = $attributes['edit_count'] ?? 0;
                        $res['editor_name'] = $attributes['edit_name'] ?? '';
                        $res['edit_time'] = ! empty($attributes['edit_time']) ? $this->tools->displayDate($attributes['edit_time']) : '';

                        $user_properties = new UserProperties();
                        $user_data = $user_properties->getFromArray($res);
                        $res = array_merge($res, $user_data);

                        $res['reply_text'] = '';
                        if (! empty($res['reply'])) {
                            $reply = $this->tools->checkout($res['reply'], 1, 1);
                            $reply = $this->tools->smilies($reply, $attributes['reply_rights'] >= 1 ? 1 : 0);
                            $res['reply_text'] = $reply;
                            $res['reply_time'] = $this->tools->displayDate($attributes['reply_time']);
                            $res['reply_author_url'] = '/profile/?user=' . $attributes['reply_id'];
                            $res['reply_author_name'] = $attributes['reply_name'];
                        }
                        $items[] = $res;
                    }
                }


                $data['items'] = $items;
                $data['total'] = $this->total;

                if (! $this->ban && $this->systemUser->isValid() && ! $this->tools->isIgnor($this->owner)) {
                    $data['message_form'] = $this->msgForm();
                }

                if ($this->total > $this->systemUser->config->kmess) {
                    $data['pagination'] = $this->tools->displayPagination($this->url . '&amp;', $start, $this->total, $this->systemUser->config->kmess);
                }

                echo $this->view->render(
                    $this->templates_namespace . '::pages/comments_list',
                    [
                        'title'      => $arg['title'],
                        'page_title' => $arg['title'],
                        'data'       => $data,
                        'back_url'   => $this->back_url,

                    ]
                );
        }
    }

    // Добавляем комментарий в базу

    /**
     * @param false|string $message
     */
    private function addComment($message): void
    {
        /** @var ContainerInterface $container */
        $container = Factory::getContainer();

        /** @var Environment $env */
        $env = $container->get(Environment::class);

        // Формируем атрибуты сообщения
        $attributes = [
            'author_name'         => $this->systemUser->name,
            'author_ip'           => $env->getIp(),
            'author_ip_via_proxy' => $env->getIpViaProxy(),
            'author_browser'      => $env->getUserAgent(),
        ];

        // Записываем комментарий в базу
        $this->db->prepare(
            '
          INSERT INTO `' . $this->comments_table . '` SET
          `sub_id` = ?,
          `user_id` = ?,
          `text` = ?,
          `reply` = \'\',
          `time` = ?,
          `attributes` = ?
        '
        )->execute(
            [
                (int) ($this->sub_id),
                $this->systemUser->id,
                $message,
                time(),
                serialize($attributes),
            ]
        );

        // Обновляем статистику пользователя
        $this->db->exec("UPDATE `users` SET `komm` = '" . ($this->systemUser->komm + 1) . "', `lastpost` = '" . time() . "' WHERE `id` = '" . $this->systemUser->id . "'");

        if ($this->owner && $this->systemUser->id == $this->owner) {
            $this->db->exec("UPDATE `users` SET `comm_old` = '" . $this->systemUser->komm . "' WHERE `id` = '" . $this->systemUser->id . "'");
        }

        $this->added = true;
    }

    // Форма ввода комментария
    private function msgForm(string $submit_link = '', string $text = '', string $reply = ''): string
    {
        return $this->view->render(
            $this->templates_namespace . '::pages/comments_form',
            [
                'action_url' => $this->url . $submit_link,
                'text'       => $text,
                'reply'      => $reply,
                'max_length' => $this->max_lenght,
                'bb_codes'   => di(Bbcode::class)->buttons('form', 'message'),
                'code'       => rand(1000, 9999),
            ]
        );
    }

    /**
     * Проверка текста сообщения
     *
     * @param bool $rpt_check проверка на повтор сообщений
     * @return array|bool
     */
    private function msgCheck(bool $rpt_check = false)
    {
        $error = [];
        $message = isset($_POST['message']) ? mb_substr(trim($_POST['message']), 0, $this->max_lenght) : '';
        $code = isset($_POST['code']) ? (int) ($_POST['code']) : null;
        $code_chk = $_SESSION['code'] ?? null;

        // Проверяем код
        if ($code == $code_chk) {
            return false;
        }

        // Проверяем на минимально допустимую длину
        if (mb_strlen($message) < $this->min_lenght) {
            $error[] = d__('system', 'Text is too short');
        } else {
            // Проверка на флуд
            $flood = Factory::getContainer()->get(Tools::class)->antiflood();

            if ($flood) {
                $error[] = d__('system', 'You cannot add the message so often<br>Please, wait') . ' ' . $flood . '&#160;' . d__('system', 'seconds');
            }
        }

        // Проверка на повтор сообщений
        if (! $error && $rpt_check) {
            $req = $this->db->query('SELECT * FROM `' . $this->comments_table . "` WHERE `user_id` = '" . $this->systemUser->id . "' ORDER BY `id` DESC LIMIT 1");
            if (($res = $req->fetch()) && mb_strtolower($message) === mb_strtolower((string) $res['text'])) {
                $error[] = d__('system', 'Message already exists');
            }
        }

        // Возвращаем результат
        return [
            'code'  => $code,
            'text'  => $message,
            'error' => $error,
        ];
    }

    // Счетчик комментариев

    /**
     * @param false|int $update
     * @return int
     */
    private function msgTotal($update = false): int
    {
        $total = $this->db->query('SELECT COUNT(*) FROM `' . $this->comments_table . "` WHERE `sub_id` = '" . $this->sub_id . "'")->fetchColumn();

        if ($update) {
            // Обновляем счетчики в таблице объекта
            $this->db->exec('UPDATE `' . $this->object_table . "` SET `comm_count` = '${total}' WHERE `id` = '" . $this->sub_id . "'");
        }

        return (int) $total;
    }
}
