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

defined('_IN_JOHNCMS') or die('Restricted access');

class mainpage {
    public $news;    // Текст новостей
    public $newscount;    // Общее к-во новостей
    public $lastnewsdate;    // Дата последней новости
    private $settings = array();

    function __construct() {
        global $set;
        global $realtime;
        $this->settings = unserialize($set['news']);
        $this->newscount = $this->newscount() . $this->lastnewscount();
        $this->news = $this->news();
    }

    // Запрос свежих новостей на Главную
    private function news() {
        global $realtime;
        if ($this->settings['view'] > 0) {
            $reqtime = $realtime - ($this->settings['days'] * 86400);
            $req = mysql_query("SELECT * FROM `news` WHERE `time` > '" . $reqtime . "' ORDER BY `time` DESC LIMIT " . $this->settings['quantity']);
            if (mysql_num_rows($req) > 0) {
                $news = '<div class="bmenu">Новости</div>';
                while ($res = mysql_fetch_array($req)) {
                    $text = $res['text'];
                    // Если текст больше заданного предела, обрезаем
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text = mb_substr($text, 0, $this->settings['size']);
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                        $text .= ' <a href="str/news.php">Дальше...</a>';
                    }
                    else {
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                    }
                    // Если включены переносы, то обрабатываем
                    if ($this->settings['breaks'])
                        $text = str_replace("\r\n", "<br/>", $text);
                    // Парсинг смайлов
                    if ($this->settings['smileys']) {
                        $text = call_user_func('smileys', $text);                        //TODO: Проверить Админские смайлы
                    }
                    // Обрабатываем тэги
                    if ($this->settings['tags']) {
                        $text = call_user_func('tags', $text);
                    }
                    else {
                        $text = call_user_func('notags', $text);
                    }
                    // Определяем режим просмотра заголовка - текста
                    $news .= '<div class="news">';
                    switch ($this->settings['view']) {
                        case 2 :
                            $news .= '<a href="str/news.php">' . $res['name'] . '</a>';
                            break;
                        case 3 :
                            $news .= $text;
                            break;
                        default :
                            $news .= '<u>' . $res['name'] . '</u><br />' . $text;
                    }
                    // Ссылка на каменты
                    if (!empty ($res['kom']) && $this->settings['view'] != 2 && $this->settings['kom'] == 1) {
                        $mes = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['kom'] . "'");
                        $komm = mysql_result($mes, 0) - 1;
                        if ($komm >= 0)
                            $news .= '<br /><a href="../forum/?id=' . $res['kom'] . '">Обсудить</a> (' . $komm . ')';
                    }
                    $news .= '</div>';
                    ++$i;
                }
                return $news;
            }
            else {
                return false;
            }
        }
    }

    // Счетчик всех новостей
    private function newscount() {
        $req = mysql_query("SELECT COUNT(*) FROM `news`");
        $res = mysql_result($req, 0);
        return ($res > 0 ? $res : '0');
    }

    // Счетчик свежих новостей
    private function lastnewscount() {
        global $realtime;
        $ltime = $realtime - (86400 * 3);
        $req = mysql_query("SELECT COUNT(*) FROM `news` WHERE `time` > '" . $ltime . "'");
        $res = mysql_result($req, 0);
        return ($res > 0 ? '/<font color="#FF0000">+' . $res . '</font>' : false);
    }
}

?>