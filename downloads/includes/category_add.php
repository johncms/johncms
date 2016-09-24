<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    require_once '../incfiles/head.php';

    if (!$id) {
        $load_cat = $files_path;
    } else {
        $req_down = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
        $res_down = $req_down->fetch();

        if (!$req_down->rowCount() || !is_dir($res_down['dir'])) {
            echo $lng['not_found_dir'] . '<a href="' . $url . '">' . $lng['download_title'] . '</a>';
            exit;
        }

        $load_cat = $res_down['dir'];
    }

    if (isset($_POST['submit'])) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';
        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
        $user_down = isset($_POST['user_down']) ? 1 : 0;
        $format = $user_down && isset($_POST['format']) ? trim($_POST['format']) : false;
        $error = [];

        if (empty($name)) {
            $error[] = $lng['error_empty_fields'];
        }

        if (preg_match("/[^0-9a-zA-Z]+/", $name)) {
            $error[] = $error[] = $lng['error_wrong_symbols'];
        }

        if ($rights == 9 && $user_down) {
            foreach (explode(',', $format) as $value) {
                if (!in_array(trim($value), $defaultExt)) {
                    $error[] = $lng['extensions_ok'] . ': ' . implode(', ', $defaultExt);
                    break;
                }
            }
        }

        if ($error) {
            echo $error . ' <a href="' . $url . '?act=add_cat&amp;id=' . $id . '">' . $lng['repeat'] . '</a>';
            exit;
        }

        if (empty($rus_name)) {
            $rus_name = $name;
        }

        $dir = false;
        $load_cat = $load_cat . '/' . $name;

        if (!is_dir($load_cat)) {
            $dir = mkdir($load_cat, 0777);
        }

        if ($dir == true) {
            chmod($load_cat, 0777);

            $stmt = $db->prepare("
                INSERT INTO `download__category`
                (`refid`, `dir`, `sort`, `name`, `desc`, `field`, `text`, `rus_name`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $id,
                $load_cat,
                time(),
                $name,
                $desc,
                $user_down,
                $format,
                $rus_name,
            ]);
            $cat_id = $db->lastInsertId();

            echo '<div class="phdr"><b>' . $lng['add_cat_title'] . '</b></div>' .
                '<div class="list1"><p>' . $lng['add_cat_ok'] . '<br><a href="' . $url . '?id=' . $cat_id . '">' . _t('Continue') . '</a></p></div>';
        } else {
            echo $lng['add_cat_error'] . '<a href="' . $url . 'act=add_cat&amp;id=' . $id . '">' . $lng['repeat'] . '</a>';
            exit;
        }
    } else {
        echo '<div class="phdr"><b>' . $lng['add_cat_title'] . '</b></div><div class="menu">' .
            '<form action="' . $url . '?act=add_cat&amp;id=' . $id . '" method="post">' .
            $lng['dir_name'] . ' [A-Za-z0-9]:<br/><input type="text" name="name"/><br/>' .
            $lng['dir_name_view'] . ':<br/><input type="text" name="rus_name"/><br/>' .
            $lng['dir_desc'] . ' (max. 500):<br/><textarea name="desc" cols="24" rows="4"></textarea><br/>';

        if ($rights == 9) {
            echo '<div class="sub"><input type="checkbox" name="user_down" value="1" /> ' . $lng['user_download'] . '<br/>' .
                $lng['extensions'] . ':<br/><input type="text" name="format"/></div>' .
                '<div class="sub">' . $lng['extensions_ok'] . ':<br /> ' . implode(', ', $defaultExt) . '</div>';
        }

        echo '<p><input type="submit" name="submit" value="' . $lng['add_cat'] . '"/></p></form></div>';
    }

    echo '<div class="phdr">';

    if ($id) {
        echo '<a href="' . $url . '?id=' . $id . '">' . $lng['back'] . '</a> | ';
    }

    echo '<a href="?">' . _t('Back') . '</a></div>';
    require_once '../incfiles/end.php';
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
