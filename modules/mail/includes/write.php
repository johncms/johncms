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

$set_mail = unserialize((string) $user->set_mail, ['allowed_classes' => false]);
$out = '';
$total = 0;
$ch = 0;
$mod = $_REQUEST['mod'] ?? '';

$title = __('Mail');
$nav_chain->add($title);

if ($id) {
    $req = $db->query("SELECT * FROM `users` WHERE `id` = '${id}' LIMIT 1");

    if (! $req->rowCount()) {
        echo $view->render(
            'system::pages/result',
            [
                'title'   => $title,
                'type'    => 'alert-danger',
                'message' => __('User does not exists'),
            ]
        );
        exit;
    }

    $qs = $req->fetch();

    if ($mod === 'clear') {
        $title = __('Clear messages');
        $nav_chain->add($title);

        if (isset($_POST['clear'])) {
            $count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail`
                WHERE ((`user_id`='${id}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='${id}'))
                AND `delete`!='" . $user->id . "'"
            )->fetchColumn();

            if ($count_message) {
                $req = $db->query(
                    "SELECT `cms_mail`.* FROM `cms_mail`
                    WHERE ((`cms_mail`.`user_id`='${id}' AND `cms_mail`.`from_id`='" . $user->id . "') OR (`cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`from_id`='${id}'))
                    AND `cms_mail`.`delete`!='" . $user->id . "' LIMIT " . $count_message
                );

                while ($row = $req->fetch()) {
                    if ($row['delete']) {
                        if ($row['file_name'] && file_exists(UPLOAD_PATH . 'mail/' . $row['file_name']) !== false) {
                            @unlink(UPLOAD_PATH . 'mail/' . $row['file_name']);
                        }
                        $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                    } elseif ($row['read'] === 0 && $row['user_id'] === $user->id) {
                        if ($row['file_name'] && file_exists(UPLOAD_PATH . 'mail/' . $row['file_name']) !== false) {
                            @unlink(UPLOAD_PATH . 'mail/' . $row['file_name']);
                        }
                        $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                    } else {
                        $db->exec("UPDATE `cms_mail` SET `delete` = '" . $user->id . "' WHERE `id` = '" . $row['id'] . "' LIMIT 1");
                    }
                }
            }

            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Messages are deleted'),
                    'back_url'      => '?act=write&amp;id=' . $id,
                    'back_url_name' => __('Back'),
                ]
            );
        } else {
            $data = [
                'form_action'     => '?act=write&amp;mod=clear&amp;id=' . $id,
                'message'         => __('Confirm the deletion of messages'),
                'back_url'        => '?act=write&amp;id=' . $id,
                'submit_btn_name' => __('Delete'),
                'hidden_inputs'   => [
                    [
                        'name'  => 'clear',
                        'value' => true,
                    ],
                ],
            ];
            echo $view->render(
                'mail::confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        exit;
    }
}

if (isset($_POST['submit']) && empty($user->ban['1']) && empty($user->ban['3']) && ! $tools->isIgnor($id)) {
    if (! $id) {
        $name = isset($_POST['nick']) ? $tools->rusLat(trim($_POST['nick'])) : '';
    }

    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $newfile = '';
    $sizefile = 0;
    $do_file = false;
    $do_file_mini = false;

    $error = [];

    if (! $id && empty($name)) {
        $error[] = __('Specify the recipient\'s login');
    }

    if (empty($text)) {
        $error[] = __('Message cannot be empty');
    }

    if (($id && $id === $user->id) || (! $id && $user->name_lat === $name)) {
        $error[] = __('You cannot send messages to yourself');
    }

    $flood = $tools->antiflood();

    if ($flood) {
        $error[] = sprintf(__('You cannot add the message so often. Please, wait %d sec.'), $flood);
    }

    if (empty($error)) {
        if (! $id) {
            $query = $db->query('SELECT * FROM `users` WHERE `name_lat` = ' . $db->quote($name) . ' LIMIT 1');

            if (! $query->rowCount()) {
                $error[] = __('User does not exists');
            } else {
                $foundUser = $query->fetch();
                $id = $foundUser['id'];
                $set_mail = unserialize($foundUser['set_mail'], ['allowed_classes' => false]);
            }
        } else {
            $set_mail = unserialize($qs['set_mail'], ['allowed_classes' => false]);
        }

        if ($set_mail && empty($error) && $user->rights < 1 && $set_mail['access']) {
            if ($set_mail['access'] === 1) {
                $query = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $id . "' AND `from_id`='" . $user->id . "' LIMIT 1");

                if (! $query->rowCount()) {
                    $error[] = __('To this user can write only contacts');
                }
            } elseif ($set_mail['access'] === 2) {
                $query = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $id . "' AND `from_id`='" . $user->id . "' AND `friends`='1' LIMIT 1");

                if (! $query->rowCount()) {
                    $error[] = __('To this user can write only friends');
                }
            }
        }
    }

    function parseFileName($var = '')
    {
        if (empty($var)) {
            return false;
        }

        $file_ext = pathinfo($var, PATHINFO_EXTENSION);
        $file_body = mb_substr($var, 0, mb_strripos($var, '.'));
        $info['filename'] = mb_strtolower(mb_substr(str_replace('.', '_', $file_body), 0, 38));
        $info['fileext'] = mb_strtolower($file_ext);

        return $info;
    }

    $info = [];

    if (isset($_FILES['fail']['size']) && $_FILES['fail']['size'] > 0) {
        $do_file = true;
        $fname = $_FILES['fail']['name'];
        $fsize = $_FILES['fail']['size'];

        if (! empty($_FILES['fail']['error'])) {
            $error[] = __('Error uploading file');
        }
    } elseif (isset($_POST['fail']) && mb_strlen($_POST['fail']) > 0) {
        $do_file_mini = true;
        $array = explode('file=', $_POST['fail']);
        $fname = mb_strtolower($array[0]);
        $filebase64 = $array[1];
        $fsize = strlen(base64_decode($filebase64));

        if (empty($fsize)) {
            $error[] = __('Error uploading file');
        }
    }


    if (empty($error) && ($do_file || $do_file_mini)) {
        // Файлы Windows
        $ext_win = [
            'exe',
            'msi',
        ];

        // Файлы Java
        $ext_java = [
            'jar',
            'jad',
        ];

        // Файлы SIS
        $ext_sis = [
            'sis',
            'sisx',
            'apk',
        ];

        // Файлы документов и тексты
        $ext_doc = [
            'txt',
            'pdf',
            'doc',
            'docx',
            'rtf',
            'djvu',
            'xls',
            'xlsx',
        ];

        // Файлы картинок
        $ext_pic = [
            'jpg',
            'jpeg',
            'gif',
            'png',
            'bmp',
            'wmf',
        ];

        // Файлы архивов
        $ext_zip = [
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
        ];

        // Файлы видео
        $ext_video = [
            '3gp',
            'avi',
            'flv',
            'mpeg',
            'mp4',
        ];

        // Звуковые файлы
        $ext_audio = [
            'mp3',
            'amr',
        ];

        $ext = array_merge($ext_win, $ext_java, $ext_sis, $ext_doc, $ext_pic, $ext_zip, $ext_video, $ext_audio);
        $info = parseFileName($fname);

        if (empty($info['filename'])) {
            $error[] = __('It is forbidden to upload files without a name');
        }

        if (empty($info['fileext'])) {
            $error[] = __('It is forbidden to upload files without extension');
        }

        if ($fsize > (1024 * $config['flsz'])) {
            $error[] = __('The size of the file exceeds the maximum allowable upload');
        }

        if (preg_match('/[^a-z0-9.()+_-]/', $info['filename'])) {
            $error[] = __('File name contains invalid characters');
        }

        if (! in_array($info['fileext'], $ext, true)) {
            $error[] = __('Forbidden file type! By uploading permitted only files with the following extension') . ': ' . implode(', ', $ext);
        }

        $newfile = $info['filename'] . '.' . $info['fileext'];
        $sizefile = $fsize;
    }

    if (empty($error)) {
        $ignor = $db->query(
            "SELECT COUNT(*) FROM `cms_contact`
		WHERE `user_id`='" . $user->id . "'
		AND `from_id`='" . $id . "'
		AND `ban`='1';"
        )->fetchColumn();

        if ($ignor) {
            $error[] = __('The user at your ignore list. Sending the message is impossible.');
        }

        if (empty($error)) {
            $ignor_m = $db->query(
                "SELECT COUNT(*) FROM `cms_contact`
			WHERE `user_id`='" . $id . "'
			AND `from_id`='" . $user->id . "'
			AND `ban`='1';"
            )->fetchColumn();

            if ($ignor_m) {
                $error[] = __('The user added you in the ignore list. Sending the message isn\'t possible.');
            }
        }
    }

    if (empty($error)) {
        $q = $db->query(
            "SELECT * FROM `cms_contact`
		WHERE `user_id`='" . $user->id . "' AND `from_id`='" . $id . "'"
        );

        if (! $q->rowCount()) {
            $db->exec(
                "INSERT INTO `cms_contact` SET
			`user_id` = '" . $user->id . "',
			`from_id` = '" . $id . "',
			`time` = '" . time() . "'"
            );
            $ch = 1;
        }

        $q1 = $db->query(
            "SELECT * FROM `cms_contact`
		WHERE `user_id`='" . $id . "' AND `from_id`='" . $user->id . "'"
        );

        if (! $q1->rowCount()) {
            $db->exec(
                "INSERT INTO `cms_contact` SET
			`user_id` = '" . $id . "',
			`from_id` = '" . $user->id . "',
			`time` = '" . time() . "'"
            );
            $ch = 1;
        }
    }

    // Проверка наличия файла с таким же именем
    if (! empty($newfile) && file_exists(UPLOAD_PATH . 'mail/' . $newfile) !== false) {
        $newfile = time() . '_' . $newfile;
    }

    if (empty($error) && $do_file) {
        if ((move_uploaded_file($_FILES['fail']['tmp_name'], UPLOAD_PATH . 'mail/' . $newfile)) === true) {
            @ chmod(UPLOAD_PATH . 'mail/' . $newfile, 0666);
            @unlink($_FILES['fail']['tmp_name']);
        } else {
            $error[] = __('Error uploading file');
        }
    }

    if (empty($error) && $do_file_mini) {
        if (strlen($filebase64) > 0) {
            $FileName = UPLOAD_PATH . 'mail/' . $newfile;
            $filedata = base64_decode($filebase64);
            $fid = @fopen($FileName, 'wb');
            if ($fid) {
                if (flock($fid, LOCK_EX)) {
                    fwrite($fid, $filedata);
                    flock($fid, LOCK_UN);
                }
                fclose($fid);
            }

            if (file_exists($FileName) && filesize($FileName) == strlen($filedata)) {
                @ chmod($FileName, 0666);
                unset($FileName);
            } else {
                $error[] = __('Error uploading file');
            }
        } else {
            $error[] = __('Error uploading file');
        }
    }

    // Проверяем на повтор сообщения
    if (empty($error)) {
        $rres = $db->query(
            'SELECT * FROM `cms_mail`
        WHERE `user_id` = ' . $user->id . "
        AND `from_id` = ${id}
        ORDER BY `id` DESC
        LIMIT 1
        "
        )->fetch();

        if ($rres['text'] === $text) {
            $error[] = __('Message already exists');
        }
    }

    if (empty($error)) {
        $db->query(
            "INSERT INTO `cms_mail` SET
		`user_id` = '" . $user->id . "',
		`from_id` = '" . $id . "',
		`text` = " . $db->quote($text) . ",
		`time` = '" . time() . "',
		`file_name` = " . $db->quote($newfile) . ",
		`size` = '" . $sizefile . "'"
        );

        $db->exec("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '" . $user->id . "'");

        if ($ch === 0) {
            $db->exec("UPDATE `cms_contact` SET `time` = '" . time() . "' WHERE `user_id` = '" . $user->id . "' AND `from_id` = '" . $id . "'");
            $db->exec("UPDATE `cms_contact` SET `time` = '" . time() . "' WHERE `user_id` = '" . $id . "' AND `from_id` = '" . $user->id . "'");
        }

        header('Location: ?act=write' . ($id ? '&id=' . $id : ''));
        exit;
    }
}

$data = [];
$data['errors'] = $error ?? [];

if (empty($user->ban['1']) && empty($user->ban['3']) && ! $tools->isIgnor($id)) {
    $data['form_action'] = '?act=write' . ($id ? '&amp;id=' . $id : '');
    $data['show_nick_input'] = empty($id);
    $data['nick'] = (! empty($_POST['nick']) ? htmlspecialchars(trim($_POST['nick'])) : '');
    $data['bbcode'] = di(Johncms\System\Legacy\Bbcode::class)->buttons('form', 'text');
}

if ($id) {
    $total = $db->query(
        "SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='${id}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='${id}')) AND `sys`!='1' AND `delete`!='" . $user->id . "' AND `spam`='0'"
    )->fetchColumn();

    if ($total) {
        $req = $db->query(
            "SELECT `cms_mail`.*, `cms_mail`.`id` as `mid`, `cms_mail`.`time` as `mtime`, `users`.*
            FROM `cms_mail`
            LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
            WHERE ((`cms_mail`.`user_id`='${id}' AND `cms_mail`.`from_id`='" . $user->id . "') OR (`cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`from_id`='${id}'))
            AND `cms_mail`.`delete`!='" . $user->id . "'
            AND `cms_mail`.`sys`!='1'
            AND `cms_mail`.`spam`='0'
            ORDER BY `cms_mail`.`time` DESC
            LIMIT " . $start . ',' . $user->config->kmess
        );

        $i = 1;
        $mass_read = [];
        $items = [];
        while ($row = $req->fetch()) {
            if ($row['read'] === 0 && $row['from_id'] === $user->id) {
                $mass_read[] = $row['mid'];
            }

            $post = $row['text'];
            $post = $tools->checkout($post, 1, 1);
            $post = $tools->smilies($post, $row['rights'] >= 1 ? 1 : 0);

            $row['text'] = $post;
            $row['display_date'] = $tools->displayDate($row['mtime']);

            $row['user_id'] = $row['id'];
            $user_properties = new \Johncms\UserProperties();
            $user_data = $user_properties->getFromArray($row);
            $row = array_merge($row, $user_data);
            if ($row['file_name']) {
                $row['files'] = [
                    [
                        'file_size' => formatsize($row['size']),
                        'file_url'  => '?act=load&amp;id=' . $row['mid'],
                        'dlcount'   => $row['count'],
                        'filename'  => $row['file_name'],
                    ],
                ];
            }
            $row['browser'] = htmlspecialchars($row['browser']);
            $row['delete_url'] = '?act=delete&amp;id=' . $row['mid'];

            $items[] = $row;
        }

        //Ставим метку о прочтении
        if ($mass_read) {
            $result = implode(',', $mass_read);
            $db->exec("UPDATE `cms_mail` SET `read`='1' WHERE `from_id`='" . $user->id . "' AND `id` IN (" . $result . ')');
        }
    }

    $data['back_url'] = '../profile/?act=office';

    $data['total'] = $total;
    $data['pagination'] = $tools->displayPagination('?act=write&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess);
    $data['items'] = $items ?? [];
    $data['clear_url'] = '?act=write&amp;mod=clear&amp;id=' . $id;
    echo $view->render(
        'mail::messages',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
