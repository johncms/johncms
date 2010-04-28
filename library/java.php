<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

$id = intval(trim($_GET['id']));
if ($user_id && $user_ps) {
    $req = mysql_query("select `name`, `text` from `lib` where `id` = '" . $id . "' and `type` = 'bk' and `moder`='1' LIMIT 1;");
    if (mysql_num_rows($req) == 0) {
        echo '<p>Статья не найдена</p>';
        require_once ("../incfiles/end.php");
        exit;
    }
    $res = mysql_fetch_array($req);

    // Создаем JAR файл
    if (!file_exists('files/' . $id . '.jar')) {
        $midlet_name = mb_substr($res['name'], 0, 10);
        $midlet_name = iconv('UTF-8', 'windows-1251', $midlet_name);

        // Записываем текст статьи
        $files = fopen("java/textfile.txt", 'w+');
        flock($files, LOCK_EX);
        $book_name = iconv('UTF-8', 'windows-1251', $res['name']);
        $book_text = iconv('UTF-8', 'windows-1251', $res['text']);
        $result = "\r\n" . $book_name . "\r\n\r\n----------\r\n\r\n" . $book_text . "\r\n\r\nDownloaded from $home";
        fputs($files, $result);
        flock($files, LOCK_UN);
        fclose($files);

        // Записываем манифест
        $manifest_text = 'Manifest-Version: 1.0
MIDlet-1: Книга ' . $id . ', , br.BookReader
MIDlet-Name: Книга ' . $id .
        '
MIDlet-Vendor: JohnCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)';
        $files = fopen("java/META-INF/MANIFEST.MF", 'w+');
        flock($files, LOCK_EX);
        fputs($files, $manifest_text);
        flock($files, LOCK_UN);
        fclose($files);

        // Создаем архив
        require_once ('../incfiles/class_pclzip.php');
        $archive = new PclZip('files/' . $id . '.jar');
        $list = $archive->create('java', PCLZIP_OPT_REMOVE_PATH, 'java');
        if (!file_exists('files/' . $id . '.jar')) {
            echo '<p>Ошибка создания JAR файла</p>';
            require_once ("../incfiles/end.php");
            exit;
        }
    }

    // Создаем JAD файл
    if (!file_exists('files/' . $id . '.jad')) {
        $filesize = filesize('files/' . $id . '.jar');
        $jad_text = 'Manifest-Version: 1.0
MIDlet-1: Книга ' . $id . ', , br.BookReader
MIDlet-Name: Книга ' . $id .
        '
MIDlet-Vendor: JohnCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)
MIDlet-Jar-Size: ' . $filesize
        . '
MIDlet-Jar-URL: ' . $home . '/library/files/' . $id . '.jar';
        $files = fopen('files/' . $id . '.jad', 'w+');
        flock($files, LOCK_EX);
        fputs($files, $jad_text);
        flock($files, LOCK_UN);
        fclose($files);
    }

    echo 'На этой странице Вы можете скачать Java (MIDP-2) книгу с нужной вам статьей.<br /><br />';
    echo 'Название: ' . $res['name'] . '<br />';
    echo 'Скачать: <a href="files/' . $id . '.jar">JAR</a> | <a href="files/' . $id . '.jad">JAD</a>';
    echo '<p><a href="index.php?id=' . $id . '">К статье</a></p>';
}
else {
    echo '<p>Внимание!<br />Скачать книгу в Java формате могут только зарегистрированные пользователи.</p>';
    echo '<p><a href="index.php?id=' . $id . '">К статье</a></p>';
}

?>