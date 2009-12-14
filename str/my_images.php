<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);

$headmod = 'anketa';
$textl = 'Редактирование Анкеты';
require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');
require_once ('../incfiles/class_upload.php');

if (!$user_id) {
    display_error('Только для зарегистрированных посетителей');
    require_once ('../incfiles/end.php');
    exit;
}

if ($id && $id != $user_id && $rights >= 7) {
    // Если был запрос на юзера, то получаем его данные
    $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
    if (mysql_num_rows($req)) {
        $user = mysql_fetch_assoc($req);
        if ($user['rights'] > $datauser['rights']) {
            // Если не хватает прав, выводим ошибку
            echo display_error('Вы не можете редактировать анкету старшего Вас по должности');
            require_once ('../incfiles/end.php');
            exit;
        }
    }
    else {
        echo display_error('Такого пользователя не существует');
        require_once ('../incfiles/end.php');
        exit;
    }
}
else {
    $id = false;
    $user = $datauser;
}

switch ($act) {
    case 'up_avatar' :
        echo '<div class="phdr"><b>Выгружаем аватар</b></div>';
        if (isset ($_POST['submit'])) {
            $handle = new upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $user['id'];
                $handle->allowed = array('image/jpeg', 'image/gif', 'image/png');
                $handle->file_max_size = 1024 * $flsz;
                $handle->file_overwrite = true;
                $handle->image_resize = true;
                $handle->image_x = 32;
                $handle->image_y = 32;
                $handle->image_convert = 'png';
                $handle->process('../files/avatar/');
                if ($handle->processed) {
                        echo '<div class="gmenu"><p>Аватар загружен<br /><a href="my_data.php?id=' . $user['id'] . '">Продолжить</a></p></div>';
                        echo '<div class="phdr"><a href="anketa.php?id=' . $user['id'] . '">В анкету</a></div>';
                }
                else {
                    echo display_error($handle->error);
                }
                $handle->clean();
            }
        }
        else {
            echo '<form enctype="multipart/form-data" method="post" action="my_images.php?act=up_avatar&amp;id=' . $user['id'] . '"><div class="menu"><p>';
            echo 'Выберите изображение:<br /><input type="file" name="imagefile" value="" />';
            echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $flsz) . '" />';
            echo '</p><p><input type="submit" name="submit" value="Выгрузить" />';
            echo '</p></div></form>';
            echo '<div class="phdr"><small>Для выгрузки разрешены файлы JPG, JPEG, PNG, GIF<br />Размер файла не должен превышать ' . $flsz . 'кб.<br />';
            echo 'Вне зависимости от разрешения исходного файла, он будет преобразован в размер 32х32<br />';
            echo 'Новое изображение заменит старое (если оно было)';
            echo 'Для лучшего результата, исходное изображение долдно иметь равное соотношение сторон</small></div>';
        }
        break;

    case 'up_photo' :
        echo '<div class="phdr"><b>Выгружаем фото</b></div>';
        if (isset ($_POST['submit'])) {
            $handle = new upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $user['id'];
                $handle->allowed = array('image/jpeg', 'image/gif', 'image/png');
                $handle->file_max_size = 1024 * $flsz;
                $handle->file_overwrite = true;
                $handle->image_resize = true;
                $handle->image_x = 320;
                $handle->image_ratio_y = true;
                $handle->image_convert = 'jpg';
                $handle->process('../files/photo/');
                if ($handle->processed) {
                    // Обрабатываем превьюшку
                    $handle->file_new_name_body = $user['id'] . '_small';
                    $handle->file_overwrite = true;
                    $handle->image_resize = true;
                    $handle->image_x = 100;
                    $handle->image_ratio_y = true;
                    $handle->image_convert = 'jpg';
                    $handle->process('../files/photo/');
                    if ($handle->processed) {
                        echo '<div class="gmenu"><p>Фотография загружена<br /><a href="my_data.php?id=' . $user['id'] . '">Продолжить</a></p></div>';
                        echo '<div class="phdr"><a href="anketa.php?id=' . $user['id'] . '">В анкету</a></div>';
                    }
                    else {
                        echo display_error($handle->error);
                    }
                }
                else {
                    echo display_error($handle->error);
                }
                $handle->clean();
            }
        }
        else {
            echo '<form enctype="multipart/form-data" method="post" action="my_images.php?act=up_photo&amp;id=' . $user['id'] . '"><div class="menu"><p>';
            echo 'Выберите изображение:<br /><input type="file" name="imagefile" value="" />';
            echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $flsz) . '" />';
            echo '</p><p><input type="submit" name="submit" value="Выгрузить" />';
            echo '</p></div></form>';
            echo '<div class="phdr"><small>Для выгрузки разрешены файлы JPG, JPEG, PNG, GIF<br />Размер файла не должен превышать ' . $flsz . 'кб.<br />';
            echo 'Вне зависимости от разрешения исходного файла, он будет преобразован в размеры не превышающие 640х480<br />';
            echo 'Новое изображение заменит старое (если оно было)</small></div>';
        }
        break;
}

require_once ('../incfiles/end.php');

?>