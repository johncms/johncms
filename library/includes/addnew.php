<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */
 
defined('_IN_JOHNCMS') or die('Error: restricted access');
$lng_gal = core::load_lng('gallery');

if (($adm || (mysql_result(mysql_query("SELECT `user_add` FROM `library_cats` WHERE `id`=" . $id) , 0) > 0) && isset($id))) {
  // Проверка на флуд
  $flood = functions::antiflood();
  if ($flood) {
    require ('../incfiles/head.php');

    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'], '<br /><a href="?do=dir&amp;id=' . $id . '">' . $lng['back'] . '</a>');
    require ('../incfiles/end.php');
    exit;
  }

  if (isset($_POST['submit'])) {
    if (empty($_POST['name'])) {
      echo functions::display_error($lng['error_empty_title'] . '<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
      require_once ('../incfiles/end.php');
      exit;
    }

    if (!empty($_FILES['textfile']['name'])) {
      $ext = explode('.', $_FILES['textfile']['name']);
      if (mb_strtolower(end($ext)) == 'txt') {
        $newname = $_FILES['textfile']['name'];
        if (move_uploaded_file($_FILES['textfile']['tmp_name'], '../files/library/tmp/' . $newname)) {
          $txt = file_get_contents('../files/library/tmp/' . $newname);
          if (mb_check_encoding($txt, 'UTF-8')) {
          }
          elseif (mb_check_encoding($txt, 'windows-1251')) {
            $txt = iconv('windows-1251', 'UTF-8', $txt);
          }
          elseif (mb_check_encoding($txt, 'KOI8-R')) {
            $txt = iconv('KOI8-R', 'UTF-8', $txt);
          }
          else {
            echo functions::display_error('Файл недопустимой кодировки, предпочтительно UTF-8<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require_once ('../incfiles/end.php');
            exit;
          }

          $text = trim($txt);
          unlink('../files/library/tmp' . DIRECTORY_SEPARATOR . $newname);
        }
        else {
          echo functions::display_error($lng_lib['error_uploading'] . '<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
          require_once ('../incfiles/end.php');
          exit;
        }
      }
      else {
        echo functions::display_error('Не верный формат файла, допустимы *.txt<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
        require_once ('../incfiles/end.php');
        exit;
      }
    }
    elseif (!empty($_POST['text'])) {
      $text = trim($_POST['text']);
    }
    else {
      echo functions::display_error($lng['error_empty_text'] . '<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
      require_once ('../incfiles/end.php');
      exit;
    }

    if ($adm) {
      $md = 1;
    }
    else {
      $md = 0;
    }
    
    $sql = "INSERT INTO `library_texts`
    (`cat_id`, `text`, `name`, `announce`, `premod`, `comments`, `time`, `author`, `count_views`)
    VALUES
    (" . $id . ", '" . mysql_real_escape_string($text) . "', '" . mysql_real_escape_string(mb_substr(trim($_POST['name']) , 0, 100)) . "', '" . (isset($_POST['autoannounce']) ? mysql_real_escape_string(mb_substr(trim($text) , 0, 500)) : mysql_real_escape_string(mb_substr(trim($_POST['announce']), 0, 500))) . "', " . $md . ", " . (isset($_POST['comments']) ? intval($_POST['comments']) : 0) . ", " . time() . ", '" . $login . "', 0)";
    if (mysql_query($sql)) {
      $cid = mysql_insert_id();
      require ('../incfiles/lib/class.upload.php');

      $handle = new upload($_FILES['image']);
      if ($handle->uploaded) {
        // Обрабатываем фото
        $handle->file_new_name_body = $cid;
        $handle->allowed = [
          'image/jpeg',
          'image/gif',
          'image/png'
        ];
        $handle->file_max_size = 1024 * $set['flsz'];
        $handle->file_overwrite = true;
        $handle->image_x = $handle->image_src_x;
        $handle->image_y = $handle->image_src_y;
        $handle->image_convert = 'png';
        $handle->process('../files/library/images/orig/');
        $err_image = $handle->error;
        $handle->file_new_name_body = $cid;
        $handle->file_overwrite = true;
        if ($handle->image_src_y > 240) {
          $handle->image_resize = true;
          $handle->image_x = 240;
          $handle->image_y = $handle->image_src_y * (240 / $handle->image_src_x);
        }
        else {
          $handle->image_x = $handle->image_src_x;
          $handle->image_y = $handle->image_src_y;
        }

        $handle->image_convert = 'png';

        $handle->process('../files/library/images/big/');
        $err_image = $handle->error;
        $handle->file_new_name_body = $cid;
        $handle->file_overwrite = true;
        $handle->image_resize = true;
        $handle->image_x = 32;
        $handle->image_y = 32;
        $handle->image_convert = 'png';
        $handle->process('../files/library/images/small/');
        if ($err_image) {
          echo functions::display_error($lng_gal['error_uploading_photo'] . '<br /><a href="?act=addnew&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
          require_once ('../incfiles/end.php');
          exit;
        }
        $handle->clean();
      }
      
      if (!empty($_POST['tags'])) {
        $tags = array_map('trim', explode(',', $_POST['tags']));
        if (sizeof($tags > 0)) {
            $obj = new Hashtags($cid);
            $obj->add_tags($tags);
        }
      }

      echo '<div>' . $lng_lib['article_added'] . '</div>' . ($md == 0 ? '<div>' . $lng_lib['article_added_thanks'] . '</div>' : '');
      mysql_query("UPDATE `users` SET `lastpost` = " . time() . " WHERE `id` = " . $user_id);
      echo $md == 1 ? '<div><a href="?do=text&amp;id=' . $cid . '">' . $lng_lib['to_article'] . '</a></div>' : '<div><a href="?do=dir&amp;id=' . $id . '">' . $lng_lib['to_category'] . '</a></div>';
    }
  }
  else {
    echo '<div class="phdr"><h3>' . $lng_lib['write_article'] . '</h3></div>'
    . '<form name="form" enctype="multipart/form-data" action="?act=addnew&amp;id=' . $id . '" method="post">'
    . '<div class="menu">'
    . '<h3>' . $lng['title'] . ' (max. 100):</h3>'
    . '<div><input type="text" name="name" /></div>'
    . '<h3>' . $lng_lib['announce'] . ' (max. 500):</h3>'
    . '<div><textarea name="announce" rows="2" cols="20"></textarea></div>'
    . '<h3>' . $lng['text'] . ':</h3>'
    . '<div>' . bbcode::auto_bb('form', 'text') . '<textarea name="text" rows="' . $set_user['field_h'] . '" cols="20"></textarea></div>'
    . '<div><input type="checkbox" name="autoannounce" value="1" />' . $lng_lib['announce_help'] . '</div>'     
    . '<div><input type="checkbox" name="comments" value="1" checked="checked" />' . $lng_lib['comment_article'] . '</div>'
    . '<h3>' . $lng_gal['upload_photo'] . '</h3>'
    . '<div><input type="file" name="image" accept="image/*" /></div>'
    . '<div><b>' . $lng_lib['select_text_file'] . ', ' . mb_strtolower($lng_lib['ignor_input']) . '</b></div>'
    . '<div><input type="file" name="textfile" accept="text/plain" /></div>'
    . '<div><b>' . $lng_lib['input_tags'] . '</b></div>'
    . '<div><input name="tags" type="text" /></div>'
    . '<div><input type="submit" name="submit" value="' . $lng['save'] . '" /></div>'
    . '</div></form>'
    . '<div><a href="?do=dir&amp;id=' . $id . '">' . $lng['back'] . '</a></div>';
  }
}
else {
  header('location: ?');
}