<?php
 
defined('_IN_JOHNCMS') or die('Error: restricted access');
$adm ?: redir404();

  if (isset($_POST['submit'])) {
    if (empty($_POST['name'])) {
      echo functions::display_error($lng['error_empty_title'], '<a href="?act=mkdir&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
      require_once ('../incfiles/end.php');
      exit;
    }
    $lastinsert = $db->query('SELECT MAX(`id`) FROM `library_cats`')->fetchColumn();
    ++$lastinsert;
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $type = intval($_POST['type']);
    $stmt = $db->prepare('INSERT INTO `library_cats` (`parent`, `name`, `description`, `dir`, `pos`) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$id, $name, $desc, $type, $lastinsert]);
    if ($stmt->rowCount()) {
      echo '<div>' . $lng_lib['category_created'] . '</div><div><a href="?do=dir&amp;id=' . $id . '">' . $lng_lib['to_category'] . '</a></div>';
    }
  }
  else {
    echo '<div class="phdr"><strong><a href="?">' . $lng['library'] . '</a></strong> | ' . $lng_lib['create_category'] . '</div>'  
    . '<form action="?act=mkdir&amp;id=' . $id . '" method="post">' 
    . '<div class="menu">'
    . '<h3>' . $lng['title'] . ':</h3>' 
    . '<div><input type="text" name="name" /></div>' 
    . '<h3>' . $lng_lib['add_dir_descriptions'] . ':</h3>' 
    . '<div><textarea name="description" rows="4" cols="20"></textarea></div>' 
    . '<h3>' . $lng_lib['category_type'] . '</h3>' 
    . '<div><select name="type">' 
    . '<option value="1">' . $lng_lib['categories'] . '</option>' 
    . '<option value="0">' . $lng_lib['articles'] . '</option>' 
    . '</select></div>' 
    . '<div><input type="submit" name="submit" value="' . $lng['save'] . '"/></div>' 
    . '</div></form>' 
    . '<p><a href ="?">' . $lng['back'] . '</a></p>';
  }