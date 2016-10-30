<?php

namespace Johncms;

class NewsWidget
{
    public $news;         // Текст новостей
    public $newscount;    // Общее к-во новостей
    public $lastnewsdate; // Дата последней новости
    private $settings = [];

    /**
     * @var \PDO
     */
    private $db;

    public function __construct()
    {
        /** @var \Interop\Container\ContainerInterface $container */
        $container = \App::getContainer();
        $this->db = $container->get(\PDO::class);
        $this->settings = unserialize($container->get('config')['johncms']['news']);
        $this->newscount = $this->newscount() . $this->lastnewscount();
        $this->news = $this->news();
    }

    // Запрос свежих новостей на Главную
    private function news()
    {
        if ($this->settings['view'] > 0) {
            $reqtime = $this->settings['days'] ? time() - ($this->settings['days'] * 86400) : 0;
            $req = $this->db->query("SELECT * FROM `news` WHERE `time` > '$reqtime' ORDER BY `time` DESC LIMIT " . $this->settings['quantity']);

            if ($req->rowCount()) {
                $i = 0;
                $news = '';

                while ($res = $req->fetch()) {
                    $text = $res['text'];

                    // Если текст больше заданного предела, обрезаем
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text = mb_substr($text, 0, $this->settings['size']);
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                        $text .= ' <a href="news/index.php">' . _t('show more', 'system') . '...</a>';
                    } else {
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                    }

                    // Если включены переносы, то обрабатываем
                    if ($this->settings['breaks']) {
                        $text = str_replace("\r\n", "<br>", $text);
                    }

                    // Обрабатываем тэги
                    if ($this->settings['tags']) {
                        $text = bbcode::tags($text);
                    } else {
                        $text = \App::getContainer()->get('bbcode')->notags($text);
                    }

                    // Обрабатываем смайлы
                    if ($this->settings['smileys']) {
                        $text = functions::smileys($text);
                    }

                    // Определяем режим просмотра заголовка - текста
                    $news .= '<div class="news">';
                    switch ($this->settings['view']) {
                        case 2:
                            $news .= '<a href="news/index.php">' . $res['name'] . '</a>';
                            break;

                        case 3:
                            $news .= $text;
                            break;
                        default :
                            $news .= '<b>' . $res['name'] . '</b><br />' . $text;
                    }

                    // Ссылка на каменты
                    if (!empty($res['kom']) && $this->settings['view'] != 2 && $this->settings['kom'] == 1) {
                        $mes = $this->db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['kom'] . "'")->fetchColumn();
                        $komm = $mes - 1;

                        if ($komm >= 0) {
                            $news .= '<br /><a href="../forum/?id=' . $res['kom'] . '">' . _t('Discuss', 'system') . '</a> (' . $komm . ')';
                        }
                    }
                    $news .= '</div>';
                    ++$i;
                }

                return $news;
            }
        }

        return false;
    }

    /**
     * Счетчик всех новостей
     *
     * @return string
     */
    private function newscount()
    {
        $count = $this->db->query("SELECT COUNT(*) FROM `news`")->fetchColumn();

        return ($count ? $count : '0');
    }

    /**
     * Счетчик свежих новостей
     *
     * @return bool|string
     */
    private function lastnewscount()
    {
        $count = $this->db->query("SELECT COUNT(*) FROM `news` WHERE `time` > '" . (time() - 259200) . "'")->fetchColumn();

        return ($count > 0 ? '/<span class="red">+' . $count . '</span>' : false);
    }
}
