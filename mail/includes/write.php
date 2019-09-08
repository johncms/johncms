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

defined('_IN_JOHNCMS') or die('Error: restricted access');

$set_mail = unserialize($user['set_mail']);
$out = '';
$total = 0;
$ch = 0;
$mod = isset($_REQUEST['mod']) ? $_REQUEST['mod'] : '';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

if ($id) {
    $req = $db->query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");

    if (!$req->rowCount()) {
        $textl = _t('Mail');
        require_once('../system/head.php');
        echo $tools->displayError(_t('User does not exists'));
        require_once("../system/end.php");
        exit;
    }

    $qs = $req->fetch();

    if ($mod == 'clear') {
        $textl = _t('Mail');
        require_once('../system/head.php');
        echo '<div class="phdr"><b>' . _t('Clear messages') . '</b></div>';

        if (isset($_POST['clear'])) {
            $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='$id' AND `from_id`='" . $systemUser->id . "') OR (`user_id`='" . $systemUser->id . "' AND `from_id`='$id')) AND `delete`!='" . $systemUser->id . "'")->fetchColumn();

            if ($count_message) {
                $req = $db->query("SELECT `cms_mail`.* FROM `cms_mail` WHERE ((`cms_mail`.`user_id`='$id' AND `cms_mail`.`from_id`='" . $systemUser->id . "') OR (`cms_mail`.`user_id`='" . $systemUser->id . "' AND `cms_mail`.`from_id`='$id')) AND `cms_mail`.`delete`!='" . $systemUser->id . "' LIMIT " . $count_message);

                while ($row = $req->fetch()) {
                    if ($row['delete']) {
                        if ($row['file_name']) {
                            if (file_exists('../files/mail/' . $row['file_name']) !== false) {
                                @unlink('../files/mail/' . $row['file_name']);
                            }
                        }

                        $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                    } else {
                        if ($row['read'] == 0 && $row['user_id'] == $systemUser->id) {
                            if ($row['file_name']) {
                                if (file_exists('../files/mail/' . $row['file_name']) !== false) {
                                    @unlink('../files/mail/' . $row['file_name']);
                                }
                            }

                            $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                        } else {
                            $db->exec("UPDATE `cms_mail` SET `delete` = '" . $systemUser->id . "' WHERE `id` = '" . $row['id'] . "' LIMIT 1");
                        }
                    }
                }
            }

            echo '<div class="gmenu"><p>' . _t('Messages are deleted') . '</p></div>';
        } else {
            echo '<div class="rmenu">
			<form action="index.php?act=write&amp;mod=clear&amp;id=' . $id . '" method="post">
			<p>' . _t('Confirm the deletion of messages') . '</p>
			<p><input type="submit" name="clear" value="' . _t('Delete') . '"/></p>
			</form>
			</div>';
        }

        echo '<div class="phdr"><a href="index.php?act=write&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
        echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
        require_once('../system/end.php');
        exit;
    }
}

if (empty($_SESSION['error'])) {
    $_SESSION['error'] = '';
}

$out .= '<div class="phdr"><b>' . _t('Mail') . '</b></div>';

if (isset($_POST['submit']) && empty($systemUser->ban['1']) && empty($systemUser->ban['3']) && !$tools->isIgnor($id)) {
    if (!$id) {
        $name = isset($_POST['nick']) ? $tools->rusLat(trim($_POST['nick'])) : '';
    }

    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $newfile = '';
    $sizefile = 0;
    $do_file = false;
    $do_file_mini = false;

    $error = [];

    if (!$id && empty($name)) {
        $error[] = _t('Specify the recipient\'s login');
    }

    if (empty($text)) {
        $error[] = _t('Message cannot be empty');
    }

    if (($id && $id == $systemUser->id) || !$id && $systemUser->name_lat == $name) {
        $error[] = _t('You cannot send messages to yourself');
    }

    $flood = $tools->antiflood();

    if ($flood) {
        $error[] = sprintf(_t('You cannot add the message so often. Please, wait %d sec.'), $flood);
    }

    if (empty($error)) {
        if (!$id) {
            $query = $db->query("SELECT * FROM `users` WHERE `name_lat` = " . $db->quote($name) . " LIMIT 1");

            if (!$query->rowCount()) {
                $error[] = _t('User does not exists');
            } else {
                $user = $query->fetch();
                $id = $user['id'];
                $set_mail = unserialize($user['set_mail']);
            }
        } else {
            $set_mail = unserialize($qs['set_mail']);
        }

        if (empty($error)) {
            if ($set_mail) {
                if ($systemUser->rights < 1) {
                    if ($set_mail['access']) {
                        if ($set_mail['access'] == 1) {
                            $query = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $id . "' AND `from_id`='" . $systemUser->id . "' LIMIT 1");

                            if (!$query->rowCount()) {
                                $error[] = _t('To this user can write only contacts');
                            }
                        } else {
                            if ($set_mail['access'] == 2) {
                                $query = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $id . "' AND `from_id`='" . $systemUser->id . "' AND `friends`='1' LIMIT 1");

                                if (!$query->rowCount()) {
                                    $error[] = _t('To this user can write only friends');
                                }
                            }
                        }
                    }
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

        if (!empty($_FILES['fail']['error'])) {
            $error[] = _t('Error uploading file');
        }

    } else {
        if (isset($_POST['fail']) && mb_strlen($_POST['fail']) > 0) {
            $do_file_mini = true;
            $array = explode('file=', $_POST['fail']);
            $fname = mb_strtolower($array[0]);
            $filebase64 = $array[1];
            $fsize = strlen(base64_decode($filebase64));

            if (empty($fsize)) {
                $error[] = _t('Error uploading file');
            }
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
            $error[] = _t('It is forbidden to upload files without a name');
        }

        if (empty($info['fileext'])) {
            $error[] = _t('It is forbidden to upload files without extension');
        }

        if ($fsize > (1024 * $config['flsz'])) {
            $error[] = _t('The size of the file exceeds the maximum allowable upload');
        }

        if (preg_match("/[^a-z0-9.()+_-]/", $info['filename'])) {
            $error[] = _t('File name contains invalid characters');
        }

        if (!in_array($info['fileext'], $ext)) {
            $error[] = _t('Forbidden file type! By uploading permitted only files with the following extension') . ': ' . implode(', ', $ext);
        }

        $newfile = $info['filename'] . '.' . $info['fileext'];
        $sizefile = $fsize;
    }

    if (empty($error)) {
        $ignor = $db->query("SELECT COUNT(*) FROM `cms_contact`
		WHERE `user_id`='" . $systemUser->id . "'
		AND `from_id`='" . $id . "'
		AND `ban`='1';")->fetchColumn();

        if ($ignor) {
            $error[] = _t('The user at your ignore list. Sending the message is impossible.');
        }

        if (empty($error)) {
            $ignor_m = $db->query("SELECT COUNT(*) FROM `cms_contact`
			WHERE `user_id`='" . $id . "'
			AND `from_id`='" . $systemUser->id . "'
			AND `ban`='1';")->fetchColumn();

            if ($ignor_m) {
                $error[] = _t('The user added you in the ignore list. Sending the message isn\'t possible.');
            }
        }
    }

    if (empty($error)) {
        $q = $db->query("SELECT * FROM `cms_contact`
		WHERE `user_id`='" . $systemUser->id . "' AND `from_id`='" . $id . "'");

        if (!$q->rowCount()) {
            $db->exec("INSERT INTO `cms_contact` SET
			`user_id` = '" . $systemUser->id . "',
			`from_id` = '" . $id . "',
			`time` = '" . time() . "'");
            $ch = 1;
        }

        $q1 = $db->query("SELECT * FROM `cms_contact`
		WHERE `user_id`='" . $id . "' AND `from_id`='" . $systemUser->id . "'");

        if (!$q1->rowCount()) {
            $db->exec("INSERT INTO `cms_contact` SET
			`user_id` = '" . $id . "',
			`from_id` = '" . $systemUser->id . "',
			`time` = '" . time() . "'");
            $ch = 1;
        }

    }

    // Проверка наличия файла с таким же именем
    if (!empty($newfile) && file_exists('../files/mail/' . $newfile) !== false) {
        $newfile = time() . '_' . $newfile;
    }

    if (empty($error) && $do_file) {
        if ((move_uploaded_file($_FILES['fail']['tmp_name'], '../files/mail/' . $newfile)) === true) {
            @ chmod('../files/mail/' . $newfile, 0666);
            @unlink($_FILES['fail']['tmp_name']);
        } else {
            $error[] = _t('Error uploading file');
        }
    }

    if (empty($error) && $do_file_mini) {
        if (strlen($filebase64) > 0) {
            $FileName = '../files/mail/' . $newfile;
            $filedata = base64_decode($filebase64);
            $fid = @fopen($FileName, "wb");
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
                $error[] = _t('Error uploading file');
            }
        } else {
            $error[] = _t('Error uploading file');
        }
    }

    // Проверяем на повтор сообщения
    if (empty($error)) {
        $rres = $db->query("SELECT * FROM `cms_mail`
        WHERE `user_id` = " . $systemUser->id . "
        AND `from_id` = $id
        ORDER BY `id` DESC
        LIMIT 1
        ")->fetch();

        if ($rres['text'] == $text) {
            $error[] = _t('Message already exists');
        }
    }


    if (empty($error)) {
        $db->query("INSERT INTO `cms_mail` SET
		`user_id` = '" . $systemUser->id . "',
		`from_id` = '" . $id . "',
		`text` = " . $db->quote($text) . ",
		`time` = '" . time() . "',
		`file_name` = " . $db->quote($newfile) . ",
		`size` = '" . $sizefile . "'");

        $db->exec("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '" . $systemUser->id . "'");

        if ($ch == 0) {
            $db->exec("UPDATE `cms_contact` SET `time` = '" . time() . "' WHERE `user_id` = '" . $systemUser->id . "' AND `from_id` = '" . $id . "'");
            $db->exec("UPDATE `cms_contact` SET `time` = '" . time() . "' WHERE `user_id` = '" . $id . "' AND `from_id` = '" . $systemUser->id . "'");
        }

        header('Location: index.php?act=write' . ($id ? '&id=' . $id : ''));
        exit;
    } else {
        $out .= '<div class="rmenu">' . implode('<br />', $error) . '</div>';
    }
}

if (!$tools->isIgnor($id) && empty($systemUser->ban['1']) && empty($systemUser->ban['3'])) {

    $out .= isset($_SESSION['error']) ? $_SESSION['error'] : '';
    $out .= '<div class="gmenu">' .
        '<form name="form" action="index.php?act=write' . ($id ? '&amp;id=' . $id : '') . '" method="post"  enctype="multipart/form-data">' .
        ($id ? '' : '<p><input type="text" name="nick" maxlength="15" value="' . (!empty($_POST['nick']) ? htmlspecialchars(trim($_POST['nick'])) : '') . '" placeholder="' . _t('To Whom') . '?"/></p>') .
        '<p>';
    $out .= $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'text');
    $out .= '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="text"></textarea></p>';
    $out .= '<p><input type="file" name="fail" style="width: 100%; max-width: 160px"/></p>';
    $out .= '<p><input type="submit" name="submit" value="' . _t('Send') . '"/></p>' .
        '</form></div>' .
        '<div class="phdr"><b>' . ($id && isset($qs) ? _t('Personal correspondence with') . ' <a href="../profile/?user=' . $qs['id'] . '">' . $qs['name'] . '</a>' : _t('Send a message')) . '</b></div>';
}

if ($id) {

    $total = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='$id' AND `from_id`='" . $systemUser->id . "') OR (`user_id`='" . $systemUser->id . "' AND `from_id`='$id')) AND `sys`!='1' AND `delete`!='" . $systemUser->id . "' AND `spam`='0'")->fetchColumn();

    if ($total) {

        if ($total > $kmess) {
            $out .= '<div class="topmenu">' . $tools->displayPagination('index.php?act=write&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
        }

        $req = $db->query("SELECT `cms_mail`.*, `cms_mail`.`id` as `mid`, `cms_mail`.`time` as `mtime`, `users`.*
            FROM `cms_mail`
            LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
            WHERE ((`cms_mail`.`user_id`='$id' AND `cms_mail`.`from_id`='" . $systemUser->id . "') OR (`cms_mail`.`user_id`='" . $systemUser->id . "' AND `cms_mail`.`from_id`='$id'))
            AND `cms_mail`.`delete`!='" . $systemUser->id . "'
            AND `cms_mail`.`sys`!='1'
            AND `cms_mail`.`spam`='0'
            ORDER BY `cms_mail`.`time` DESC
            LIMIT " . $start . "," . $kmess);

        $i = 1;
        $mass_read = [];

        while ($row = $req->fetch()) {
            if (!$row['read']) {
                $out .= '<div class="gmenu">';
            } else {
                if ($row['from_id'] == $systemUser->id) {
                    $out .= '<div class="list2">';
                } else {
                    $out .= '<div class="list1">';
                }
            }

            if ($row['read'] == 0 && $row['from_id'] == $systemUser->id) {
                $mass_read[] = $row['mid'];
            }

            $post = $row['text'];
            $post = $tools->checkout($post, 1, 1);
            $post = $tools->smilies($post, $row['rights'] >= 1 ? 1 : 0);

            if ($row['file_name']) {
                $post .= '<div class="func">' . _t('File') . ': <a href="index.php?act=load&amp;id=' . $row['mid'] . '">' . $row['file_name'] . '</a> (' . formatsize($row['size']) . ')(' . $row['count'] . ')</div>';
            }

            $subtext = '<a href="index.php?act=delete&amp;id=' . $row['mid'] . '">' . _t('Delete') . '</a>';
            $arg = [
                'header'  => '(' . $tools->displayDate($row['mtime']) . ')',
                'body'    => $post,
                'sub'     => $subtext,
                'stshide' => 1,
            ];
            $out .= $tools->displayUser($row, $arg);
            $out .= '</div>';
            ++$i;
        }

        //Ставим метку о прочтении
        if ($mass_read) {
            $result = implode(',', $mass_read);
            $db->exec("UPDATE `cms_mail` SET `read`='1' WHERE `from_id`='" . $systemUser->id . "' AND `id` IN (" . $result . ")");
        }
    } else {
        $out .= '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }

    $out .= '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        $out .= '<div class="topmenu">' . $tools->displayPagination('index.php?act=write&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
        $out .= '<p><form action="index.php" method="get">
			<input type="hidden" name="act" value="write"/>
			<input type="hidden" name="id" value="' . $id . '"/>
			<input type="text" name="page" size="2"/>
			<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }
}

$textl = _t('Mail');
require_once('../system/head.php');
echo $out;
echo '<p>';

if ($total) {
    echo '<a href="index.php?act=write&amp;mod=clear&amp;id=' . $id . '">' . _t('Clear messages') . '</a><br>';
}

echo '<a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
unset($_SESSION['error']);
