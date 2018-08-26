<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNCMS') or die('Restricted access');

class mainpage {
    public $news;         // Текст новостей
    public $newscount;    // Общее к-во новостей
    public $lastnewsdate; // Дата последней новости
    private $settings = array ();

    function __construct() {
        global $set;
        $this->settings = unserialize($set['news']);
        $this->newscount = $this->newscount() . $this->lastnewscount();
        $this->news = $this->news();
    }

    // Запрос свежих новостей на Главную
    private function news() {
        global $lng;
        if ($this->settings['view'] > 0) {
            $reqtime = $this->settings['days'] ? time() - ($this->settings['days'] * 86400) : 0;
            $stmt = core::$db->query("SELECT * FROM `news` WHERE `time` > '$reqtime' ORDER BY `time` DESC LIMIT " . $this->settings['quantity']);
            if ($stmt->rowCount()) {
                $i = 0;
                $news = '';
                while ($res = $stmt->fetch()) {
                    $text = $res['text'];
                    // Если текст больше заданного предела, обрезаем
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text = mb_substr($text, 0, $this->settings['size']);
                    }
                    $text = functions::checkout($text, ($this->settings['breaks'] ? 1 : 0), ($this->settings['tags'] ? 1 : 2));
                    // Обрабатываем смайлы
                    if ($this->settings['smileys']) {
                        $text = functions::smileys($text);
                    }
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text .= ' <a href="news/index.php">' . $lng['next'] . '...</a>';
                    }
                    // Определяем режим просмотра заголовка - текста
                    $news .= '<div class="news">';
                    switch ($this->settings['view']) {
                        case 2:
                            $news .= '<a href="news/index.php">' . _e($res['name']) . '</a>';
                            break;

                        case 3:
                            $news .= $text;
                            break;
                            default :
                        $news .= '<b>' . _e($res['name']) . '</b><br />' . $text;
                    }
                    // Ссылка на каменты
                    if (!empty($res['kom']) && $this->settings['view'] != 2 && $this->settings['kom'] == 1) {
                        $komm = core::$db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['kom'] . "'")->fetchColumn() - 1;
                        if ($komm >= 0) {
                            $news .= '<br /><a href="../forum/?id=' . $res['kom'] . '">' . $lng['discuss'] . '</a> (' . $komm . ')';
                        }
                    }
                    $news .= '</div>';
                    ++$i;
                }
                return $news;
            } else {
                return '';
            }
        }
    }

    // Счетчик всех новостей
    private function newscount() {
        $res = core::$db->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
        return ($res > 0 ? $res : '0');
    }

    // Счетчик свежих новостей
    private function lastnewscount() {
        $res = core::$db->query("SELECT COUNT(*) FROM `news` WHERE `time` > '" . (time() - 259200) . "'")->fetchColumn();
        return ($res > 0 ? '/<span class="red">+' . $res . '</span>' : '');
    }
}