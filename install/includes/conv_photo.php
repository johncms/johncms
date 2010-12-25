<?php
@ini_set("max_execution_time", "1200");
define('_IN_JOHNCMS', 1);
$rootpath = '';
require('incfiles/core.php');

/*
-----------------------------------------------------------------
Конвертируем права доступа для фотографий
-----------------------------------------------------------------
*/
$req = mysql_query("SELECT * FROM `cms_album_files`");
while($res = mysql_fetch_assoc($req)){
    $req_a = mysql_query("SELECT * FROM `cms_album_cat` WHERE `id` = '" . $res['album_id'] . "'");
    $res_a = mysql_fetch_assoc($req_a);
    mysql_query("UPDATE `cms_album_files` SET
    `access` = '" . $res_a['access'] . "'
    WHERE `id` = '" . $res['id'] . "'");
}

/*
-----------------------------------------------------------------
Конвертируем личные альбомы
-----------------------------------------------------------------
*/
/*
require('incfiles/lib/class.upload.php');
$req_a = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'al' AND `user` = '1'");
// Получаем список альбомов юзера
while ($res_a = mysql_fetch_assoc($req_a)) {
    // Проверяем, есть ли юзер?
    $req_u = mysql_query("SELECT `id` FROM `users` WHERE `name` = '" . $res_a['avtor'] . "' LIMIT 1");
    if (mysql_num_rows($req_u)) {
        // Если юзер есть, обрабатываем дальше
        $file_list = array ();
        $res_u = mysql_fetch_assoc($req_u);
        echo $res_a['avtor'] . ' ' . $res_u['id'] . '<br />';
        $req_f = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'ft' AND `refid` = '" . $res_a['id'] . "'");
        if (mysql_num_rows($req_f)) {
            // Получаем список фотографий юзера
            while ($res_f = mysql_fetch_assoc($req_f)) {
                // Проверяем, есть ли файл
                if (file_exists('gallery/foto/' . $res_f['name'])) {
                    echo ' - ' . $res_f['name'] . '<br />';
                    // Заносим список файлов в массив
                    $file_list[] = $res_f['name'];
                }
            }
        }
        // если есть файлы, конвертируем их
        if (count($file_list)) {
            // Создаем альбом
            mysql_query("INSERT INTO `cms_album_cat` SET
                `user_id` = '" . $res_u['id'] . "',
                `sort` = '1',
                `name` = 'Old Album',
                `description` = 'It is transferred from old Gallery',
                `access` = '4'
            ");
            $al = mysql_insert_id();
            foreach ($file_list as $file) {
                $handle = new upload('gallery/foto/' . $file);
                $handle->file_new_name_body = 'img_' . $realtime;
                $handle->allowed = array (
                    'image/jpeg',
                    'image/gif',
                    'image/png'
                );
                $handle->image_resize = true;
                $handle->image_x = 640;
                $handle->image_y = 480;
                $handle->image_ratio_no_zoom_in = true;
                $handle->image_convert = 'jpg';
                $handle->process('files/users/album/' . $res_u['id'] . '/');
                $img_name = $handle->file_dst_name;
                if ($handle->processed) {
                    // Обрабатываем превьюшку
                    $handle->file_new_name_body = 'tmb_' . $realtime;
                    $handle->image_resize = true;
                    $handle->image_x = 80;
                    $handle->image_y = 80;
                    $handle->image_ratio_no_zoom_in = true;
                    $handle->image_convert = 'jpg';
                    $handle->process('files/users/album/' . $res_u['id'] . '/');
                    $tmb_name = $handle->file_dst_name;
                    if ($handle->processed) {
                        mysql_query("INSERT INTO `cms_album_files` SET
                            `album_id` = '$al',
                            `user_id` = '" . $res_u['id'] . "',
                            `img_name` = '" . mysql_real_escape_string($img_name) . "',
                            `tmb_name` = '" . mysql_real_escape_string($tmb_name) . "',
                            `time` = '$realtime',
                            `access` = '4'
                        ");
                    }
                }
                $handle->clean();
                unset($handle);
                mysql_query("DELETE FROM `gallery` WHERE `refid` = '" . $res_a['id'] . "'");
            }
        }
    }
}
*/
?>