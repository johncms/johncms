<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Редактирование категорий
if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    require_once('../incfiles/head.php');

    $req = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);
    $res = $req->fetch();

    if (!$req->rowCount() || !is_dir($res['dir'])) {
        echo _t('The directory does not exist') . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
        exit;
    }

    // Сдвиг категорий
    if (isset($_GET['up']) || isset($_GET['down'])) {
        if (isset($_GET['up'])) {
            $order = 'DESC';
            $val = '<';
        } else {
            $order = 'ASC';
            $val = '>';
        }

        $req_two = $db->query("SELECT * FROM `download__category` WHERE `refid` = '" . $res['refid'] . "' AND `sort` $val '" . $res['sort'] . "' ORDER BY `sort` $order LIMIT 1");

        if ($req_two->rowCount()) {
            $res_two = $req_two->fetch();
            $db->exec("UPDATE `download__category` SET `sort` = '" . $res_two['sort'] . "' WHERE `id` = '" . $id . "' LIMIT 1");
            $db->exec("UPDATE `download__category` SET `sort` = '" . $res['sort'] . "' WHERE `id` = '" . $res_two['id'] . "' LIMIT 1");
        }

        header('location: ' . $url . '?id=' . $res['refid']);
        exit;
    }

    // Изменяем данные
    if (isset($_POST['submit'])) {
        $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';

        if (empty($rus_name)) {
            $error[] = _t('The required fields are not filled');
        }

        $error_format = false;

        if ($rights == 9 && isset($_POST['user_down'])) {
            $format = isset($_POST['format']) ? trim($_POST['format']) : false;
            $format_array = explode(', ', $format);
            foreach ($format_array as $value) {
                if (!in_array($value, $defaultExt)) {
                    $error_format .= 1;
                }
            }
            $user_down = 1;
            $format_files = htmlspecialchars($format);
        } else {
            $user_down = 0;
            $format_files = '';
        }

        if ($error_format) {
            $error[] = $lng['extensions_ok'] . ': ' . implode(', ', $defaultExt);
        }

        if ($error) {
            echo $error . ' <a href="' . $url . '?act=edit_cat&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
            exit;
        }

        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';

        $stmt = $db->prepare("
            UPDATE `download__category` SET
            `field`    = ?,
            `text`     = ?,
            `desc`     = ?,
            `rus_name` = ?
            WHERE `id` = ?
        ");

        $stmt->execute([
            $user_down,
            $format_files,
            $desc,
            $rus_name,
            $id,
        ]);

        header('location: ' . $url . '?id=' . $id);
    } else {
        $name = htmlspecialchars($res['rus_name']);
        echo '<div class="phdr"><b>' . $lng['download_edit_cat'] . ':</b> ' . $name . '</div>' .
            '<div class="menu"><form action="' . $url . '?act=edit_cat&amp;id=' . $id . '" method="post">' .
            $lng['dir_name_view'] . ':<br/><input type="text" name="rus_name" value="' . $name . '"/><br/>' .
            $lng['dir_desc'] . ' (max. 500):<br/><textarea name="desc" rows="4">' . htmlspecialchars($res['desc']) . '</textarea><br/>';

        if ($rights == 9) {
            echo '<div class="sub"><input type="checkbox" name="user_down" value="1"' . ($res['field'] ? ' checked="checked"' : '') . '/> ' . $lng['user_download'] . '<br/>' .
                $lng['extensions'] . ':<br/><input type="text" name="format" value="' . $res['text'] . '"/></div>' .
                '<div class="sub">' . $lng['extensions_ok'] . ':<br /> ' . implode(', ', $defaultExt) . '</div>';
        }

        echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></form></div>';
    }

    echo '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
    require_once('../incfiles/end.php');
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
