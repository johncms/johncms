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

defined('_IN_JOHNADM') or die('Error: restricted access');
define('ROOT_DIR', '..');

if ($rights < 7)
    die('Error: restricted access');

class scaner {
    ////////////////////////////////////////////////////////////
    // Класс сканера                                          //
    ////////////////////////////////////////////////////////////
    public $scan_folders = array (
        '',
        '/cache',
        '/chat',
        '/download',
        '/forum',
        '/gallery',
        '/incfiles',
        '/library',
        '/pages',
        '/pratt',
        '/rss',
        '/smileys',
        '/str',
        '/theme',
        '/panel',
        '/install'
    );
    public $good_files = array (
        '../.htaccess',
        '../login.php',
        '../captcha.php',
        '../exit.php',
        '../go.php',
        '../index.php',
        '../read.php',
        '../registration.php',
        '../cache/.htaccess',
        '../chat/chat_footer.php',
        '../chat/chat_header.php',
        '../chat/hall.php',
        '../chat/index.php',
        '../chat/room.php',
        '../chat/who.php',
        '../download/addkomm.php',
        '../download/arc.php',
        '../download/arctemp/index.php',
        '../download/cut.php',
        '../download/delcat.php',
        '../download/delmes.php',
        '../download/dfile.php',
        '../download/down.php',
        '../download/files/.htaccess',
        '../download/files/index.php',
        '../download/fonts/index.php',
        '../download/graftemp/index.php',
        '../download/img/index.php',
        '../download/import.php',
        '../download/index.php',
        '../download/komm.php',
        '../download/makdir.php',
        '../download/mp3.php',
        '../download/mp3temp/index.php',
        '../download/new.php',
        '../download/opis.php',
        '../download/preview.php',
        '../download/rat.php',
        '../download/refresh.php',
        '../download/ren.php',
        '../download/renf.php',
        '../download/screen/index.php',
        '../download/screen.php',
        '../download/search.php',
        '../download/select.php',
        '../download/trans.php',
        '../download/upl/index.php',
        '../download/upl.php',
        '../download/view.php',
        '../download/zip.php',
        '../forum/addfile.php',
        '../forum/addvote.php',
        '../forum/close.php',
        '../forum/deltema.php',
        '../forum/delvote.php',
        '../forum/editpost.php',
        '../forum/editvote.php',
        '../forum/faq.php',
        '../forum/file.php',
        '../forum/files/.htaccess',
        '../forum/files/index.php',
        '../forum/files.php',
        '../forum/filter.php',
        '../forum/index.php',
        '../forum/loadtem.php',
        '../forum/massdel.php',
        '../forum/moders.php',
        '../forum/new.php',
        '../forum/nt.php',
        '../forum/per.php',
        '../forum/post.php',
        '../forum/read.php',
        '../forum/ren.php',
        '../forum/restore.php',
        '../forum/say.php',
        '../forum/search.php',
        '../forum/tema.php',
        '../forum/temtemp/index.php',
        '../forum/thumbinal.php',
        '../forum/trans.php',
        '../forum/users.php',
        '../forum/vip.php',
        '../forum/vote.php',
        '../forum/vote_img.php',
        '../forum/who.php',
        '../gallery/addkomm.php',
        '../gallery/album.php',
        '../gallery/cral.php',
        '../gallery/del.php',
        '../gallery/delf.php',
        '../gallery/delmes.php',
        '../gallery/edf.php',
        '../gallery/edit.php',
        '../gallery/foto/.htaccess',
        '../gallery/foto/index.php',
        '../gallery/index.php',
        '../gallery/komm.php',
        '../gallery/load.php',
        '../gallery/new.php',
        '../gallery/preview.php',
        '../gallery/razd.php',
        '../gallery/temp/index.php',
        '../gallery/trans.php',
        '../gallery/upl.php',
        '../incfiles/.htaccess',
        '../incfiles/ban.php',
        '../incfiles/char.php',
        '../incfiles/class_ipinit.php',
        '../incfiles/class_mainpage.php',
        '../incfiles/class_pclzip.php',
        '../incfiles/class_upload.php',
        '../incfiles/core.php',
        '../incfiles/db.php',
        '../incfiles/end.php',
        '../incfiles/func.php',
        '../incfiles/head.php',
        '../incfiles/index.php',
        '../incfiles/mp3.php',
        '../incfiles/pear.php',
        '../library/addkomm.php',
        '../library/del.php',
        '../library/edit.php',
        '../library/files/index.php',
        '../library/index.php',
        '../library/java.php',
        '../library/komm.php',
        '../library/load.php',
        '../library/mkcat.php',
        '../library/moder.php',
        '../library/new.php',
        '../library/search.php',
        '../library/symb.php',
        '../library/temp/index.php',
        '../library/topread.php',
        '../library/trans.php',
        '../library/write.php',
        '../pages/index.php',
        '../pages/mainmenu.php',
        '../pratt/.htaccess',
        '../pratt/index.php',
        '../rss/rss.php',
        '../smileys/admin/index.php',
        '../smileys/index.php',
        '../smileys/simply/index.php',
        '../smileys/user/index.php',
        '../str/anketa.php',
        '../str/avatar.php',
        '../str/brd.php',
        '../str/cont.php',
        '../str/guest.php',
        '../str/ignor.php',
        '../str/index.php',
        '../str/karma.php',
        '../str/moders.php',
        '../str/my_data.php',
        '../str/my_images.php',
        '../str/my_pass.php',
        '../str/my_set.php',
        '../str/my_stat.php',
        '../str/news.php',
        '../str/online.php',
        '../str/pradd.php',
        '../str/redirect.php',
        '../str/skl.php',
        '../str/smile.php',
        '../str/users.php',
        '../str/users_ban.php',
        '../str/users_search.php',
        '../str/users_top.php',
        '../panel/index.php',
        '../panel/mod_ads.php',
        '../panel/mod_chat.php',
        '../panel/mod_counters.php',
        '../panel/mod_karma.php',
        '../panel/mod_news.php',
        '../panel/sys_access.php',
        '../panel/sys_antispy.php',
        '../panel/sys_flood.php',
        '../panel/sys_smileys.php',
        '../panel/usr_adm.php',
        '../panel/usr_ban.php',
        '../panel/usr_del.php',
        '../panel/usr_list.php',
        '../panel/usr_reg.php',
        '../panel/usr_search_ip.php',
        '../panel/usr_search_nick.php',
        '../panel/sys_ipban.php',
        '../panel/mod_forum.php',
        '../panel/sys_set.php'
    );
    public $snap_base = 'scan_snapshot.dat';
    public $snap_files = array ();
    public $bad_files = array ();
    public $snap = false;
    public $track_files = array ();
    private $checked_folders = array ();
    private $cache_files = array ();

    function scan() {
        // Сканирование на соответствие дистрибутиву
        foreach ($this->scan_folders as $data) {
            $this->scan_files(ROOT_DIR . $data);
        }
    }

    function snapscan() {
        // Сканирование по образу
        if (file_exists('../cache/' . $this->snap_base)) {
            $filecontents = file('../cache/' . $this->snap_base);
            foreach ($filecontents as $name => $value) {
                $filecontents[$name] = explode("|", trim($value));
                $this->track_files[$filecontents[$name][0]] = $filecontents[$name][1];
            }
            $this->snap = true;
        }
        foreach ($this->scan_folders as $data) {
            $this->scan_files(ROOT_DIR . $data);
        }
    }

    function snap() {
        // Добавляем снимок надежных файлов в базу
        foreach ($this->scan_folders as $data) {
            $this->scan_files(ROOT_DIR . $data, true);
        //$this->scan_files(ROOT_DIR . $data);
        }
        $filecontents = "";
        foreach ($this->snap_files as $idx => $data) {
            $filecontents .= $data['file_path'] . "|" . $data['file_crc'] . "\r\n";
        }
        $filehandle = fopen('../cache/' . $this->snap_base, "w+");
        fwrite($filehandle, $filecontents);
        fclose($filehandle);
        @chmod('../cache/' . $this->snap_base, 0666);
    }

    function scan_files($dir, $snap = false) {
        // Служебная функция сканирования
        if (!isset($file))
            $file = false;
        $this->checked_folders[] = $dir . '/' . $file;
        if ($dh = @opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..' or $file == '.svn' or $file == '.DS_store') {
                    continue;
                }
                if (is_dir($dir . '/' . $file)) {
                    if ($dir != ROOT_DIR)
                        $this->scan_files($dir . '/' . $file, $snap);
                } else {
                    if ($this->snap or $snap)
                        $templates = "|tpl";
                    else
                        $templates = "";
                    if (preg_match("#.*\.(php|cgi|pl|perl|php3|php4|php5|php6|phtml|py|htaccess" . $templates . ")$#i", $file)) {
                        $folder = str_replace("../..", ".", $dir);
                        $file_size = filesize($dir . '/' . $file);
                        $file_crc = strtoupper(dechex(crc32(file_get_contents($dir . '/' . $file))));
                        $file_date = date("d.m.Y H:i:s", filectime($dir . '/' . $file));
                        if ($snap) {
                            $this->snap_files[] = array (
                                'file_path' => $folder . '/' . $file,
                                'file_crc' => $file_crc
                            );
                        } else {
                            if ($this->snap) {
                                if ($this->track_files[$folder . '/' . $file] != $file_crc and !in_array($folder . '/' . $file, $this->cache_files))
                                    $this->bad_files[] = array (
                                        'file_path' => $folder . '/' . $file,
                                        'file_name' => $file,
                                        'file_date' => $file_date,
                                        'type' => 1,
                                        'file_size' => $file_size
                                    );
                            } else {
                                if (!in_array($folder . '/' . $file, $this->good_files) or $file_size > 300000)
                                    $this->bad_files[] = array (
                                        'file_path' => $folder . '/' . $file,
                                        'file_name' => $file,
                                        'file_date' => $file_date,
                                        'type' => 0,
                                        'file_size' => $file_size
                                    );
                            }
                        }
                    }
                }
            }
        }
    }
}

////////////////////////////////////////////////////////////
// Антишпион, сканирование на подозрительные файлы        //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Антишпион</div>';
$scaner = new scaner();

switch ($mod) {
    case 'scan':
        // Сканируем на соответствие дистрибутиву
        $scaner->scan();
        echo '<div class="bmenu">Сканирование по дистрибутиву</div>';
        if (count($scaner->bad_files)) {
            echo '<div class="rmenu">Несоответствие дистрибутиву<br /><small>Внимание! Все файлы, перечисленные в списке необходимо удалить, так, как они представляют угрозу для безопасности Вашего сайта.</small></div>';
            echo '<div class="menu">';
            foreach ($scaner->bad_files as $idx => $data) {
                echo $data['file_path'] . '<br />';
            }
            echo '</div><div class="rmenu">Всего файлов: ' . count($scaner->bad_files) .
                '<br /><small>Если обнаруженные файлы относятся к дополнительным модулям, которые были устанавлены и Вы уверены в их надежности, можете игнорировать предупреждение.</small></div>';
        } else {
            echo '<div class="gmenu"><h3>Отлично!</h3>Список файлов соот ветствует дистрибутиву</div>';
        }
        echo '<div class="phdr"><a href="index.php?act=sys_antispy&amp;mod=scan">Пересканировать</a></div>';
        break;

    case 'snapscan':
        // Сканируем на соответствие образу
        $scaner->snapscan();
        echo '<div class="bmenu">Сканирование по образу</div>';
        if (count($scaner->track_files) == 0) {
            echo '<p>Образ файлов еще не был создан.</p><p><a href="index.php?act=sys_antispy&amp;mod=snap">Создание образа</a></p>';
        } else {
            if (count($scaner->bad_files)) {
                echo '<div class="rmenu">Несоответствие образу<br /><small>Внимание!!! Вам необходимо обратить внимание на все файлы из данного списка. Они были добавлены, или модифицированы с момента создания образа.</small></div>';
                echo '<div class="menu">';
                foreach ($scaner->bad_files as $idx => $data) {
                    echo $data['file_path'] . '<br />';
                }
                echo '</div><div class="rmenu">Всего файлов: ' . count($scaner->bad_files) . '</div>';
            } else {
                echo '<div class="gmenu">Отлично!<br />Все файлы соответствуют ранее сделанному образу.</div>';
            }
            echo '<div class="phdr"><a href="index.php?act=sys_antispy&amp;mod=snapscan">Пересканировать</a></div>';
        }
        break;

    case 'snap':
        // Добавляем в базу образы файлов
        if (isset($_POST['submit'])) {
            $scaner->snap();
            echo '<div class="gmenu"><p>Образ файлов успешно создан</p></div>';
            echo '<div class="phdr"><a href="index.php?act=sys_antispy">Продолжить</a></div>';
        } else {
            echo '<div class="bmenu">Создание образа</div>';
            echo
                '<div class="rmenu"><b>ВНИМАНИЕ!!!</b><br />Перед продолжением, убедитесь, что все файлы, которые были выявлены в режиме сканирования "<a href="main.php?do=antispy&amp;act=scan">Дистрибутив</a>" и "<a href="main.php?do=antispy&amp;act=check">По образу</a>" надежны и не содержат несанкционированных модификаций.</div>';
            echo '<div class="menu"><p>Данная процедура создает список всех скриптовых файлов Вашего сайта, вычисляет их контрольные суммы и заносит в базу, для последующего сравнения.</p>';
            echo '<p><form action="index.php?act=sys_antispy&amp;mod=snap" method="post"><input type="submit" name="submit" value="Создать образ" /></form></p></div>';
            echo '<div class="phdr"><a href="index.php?act=sys_antispy">Назад</a> (отмена)</div>';
        }
        break;

    default:
        echo '<div class="menu"><p><h3>Режим сканирования</h3><ul>';
        echo '<li><a href="index.php?act=sys_antispy&amp;mod=scan">Дистрибутив</a><br />';
        echo '<small>Выявление "лишних" файлов, тех, что не входят в оригинальный дистрибутив</small></li>';
        echo '<li><a href="index.php?act=sys_antispy&amp;mod=snapscan">По образу</a><br />';
        echo '<small>Сравнение списка и контрольных сумм файлов с заранее сделанным образом.<br />';
        echo 'Позволяет выявить неизвестные файлы, и несанкционированные изменения.</small></li>';
        echo '<li><a href="index.php?act=sys_antispy&amp;mod=snap">Создание образа</a><br />';
        echo '<small>Делается "снимок" всех скриптовых файлов сайта, вычисляется их контрольные суммы и запоминается в базе</small></li>';
        echo '</ul></p></div><div class="phdr">&nbsp;</div>';
}

echo '<p>' . ($mod ? '<a href="index.php?act=sys_antispy">Меню сканера</a><br />' : '') . '<a href="index.php">Админ панель</a></p>';

?>
