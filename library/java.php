<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($user_id) {
    $req = mysql_query("select `name`, `text` from `lib` where `id` = '" . $id . "' and `type` = 'bk' and `moder`='1' LIMIT 1;");
    if (mysql_num_rows($req) == 0) {
        echo '<p>ERROR</p>';
        require_once('../incfiles/end.php');
        exit;
    }
    $res = mysql_fetch_array($req);
    // Создаем JAR файл
    if (!file_exists('../files/library/' . $id . '.jar')) {
        $midlet_name = mb_substr($res['name'], 0, 10);
        $midlet_name = iconv('UTF-8', 'windows-1251', $midlet_name);
        // Записываем текст статьи
        $files = fopen("java/textfile.txt", 'w+');
        flock($files, LOCK_EX);
        $book_name = iconv('UTF-8', 'windows-1251', $res['name']);
        $book_text = iconv('UTF-8', 'windows-1251', $res['text']);
        $result = "\r\n" . $book_name . "\r\n\r\n----------\r\n\r\n" . $book_text . "\r\n\r\nDownloaded from " . $set['homeurl'];
        fputs($files, $result);
        flock($files, LOCK_UN);
        fclose($files);
        // Записываем манифест
        $manifest_text = 'Manifest-Version: 1.0
MIDlet-1: Book ' . $id . ', , br.BookReader
MIDlet-Name: Book ' . $id .
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
        require_once('../incfiles/lib/pclzip.lib.php');
        $archive = new PclZip('../files/library/' . $id . '.jar');
        $list = $archive->create('java', PCLZIP_OPT_REMOVE_PATH, 'java');
        if (!file_exists('../files/library/' . $id . '.jar')) {
            echo '<p>Error creating JAR file</p>';
            require_once('../incfiles/end.php');
            exit;
        }
    }

    // Создаем JAD файл
    if (!file_exists('../files/library/' . $id . '.jad')) {
        $filesize = filesize('../files/library/' . $id . '.jar');
        $jad_text = 'Manifest-Version: 1.0
MIDlet-1: Book ' . $id . ', , br.BookReader
MIDlet-Name: Book ' . $id .
            '
MIDlet-Vendor: JohnCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)
MIDlet-Jar-Size: ' . $filesize . '
MIDlet-Jar-URL: ' . $set['homeurl'] . '/library/files/' . $id . '.jar';
        $files = fopen('../files/library/' . $id . '.jad', 'w+');
        flock($files, LOCK_EX);
        fputs($files, $jad_text);
        flock($files, LOCK_UN);
        fclose($files);
    }
    echo $lng_lib['download_java_help'] . '<br /><br />' .
        $lng['title'] . ': ' . $res['name'] . '<br />' .
        $lng['download'] . ': <a href="../files/library/' . $id . '.jar">JAR</a> | <a href="../files/library/' . $id . '.jad">JAD</a>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['to_article'] . '</a></p>';
} else {
    echo '<p>' . $lng['access_guest_forbidden'] . '</p>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
}

?>