<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms;

class NewsWidget
{
    public $news;         // Текст новостей
    public $newscount;    // Общее к-во новостей
    public $lastnewsdate; // Дата последней новости
    private $settings = [];
    private $check;
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var \Johncms\Tools
     */
    private $tools;
    
    public function __construct()
    {
        /** @var \Psr\Container\ContainerInterface $container */
        $container = \App::getContainer();

        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get(Api\ToolsInterface::class);
        $this->settings = $container->get('config')['johncms']['news'];
        $this->check = $this->newscount();
        $this->newscount = $this->check->all_news . ($this->check->new_news ? ' / <span class="red">+' . $this->check->new_news . '</span>' : null);
        $this->news = $this->news();
    }
    
    // Запрос свежих новостей на Главную
    private function news()
    {
        if (/*$this->check->new_news && */$this->settings['view'] > 0) {
            $reqtime = $this->settings['days'] ? time() - ($this->settings['days'] * 86400) : 0;
            $req = $this->db->query("SELECT nws.*, tpc.post_count FROM `news` nws
LEFT JOIN forum_topic tpc ON `tpc`.`id`=`nws`.`kom`
WHERE `nws`.`time` > '$reqtime' ORDER BY `nws`.`time` DESC LIMIT " . $this->settings['quantity']);

            if ($req->rowCount()) {
                $i = 0;
                $news = '';

                while ($res = $req->fetch()) {
                    $text = $res['text'];
                    $moreLink = '';

                    // Если текст больше заданного предела, обрезаем
                    if (mb_strlen($text) > $this->settings['size']) {
                        $text = mb_substr($text, 0, $this->settings['size']);
                        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                        $moreLink = ' &gt;&gt; <a href="news/index.php">' . _t('show more', 'system') . '...</a>';
                    }

                    $text = $this->tools->checkout(
                        $text,
                        $this->settings['breaks'] ? 1 : 2,
                        $this->settings['tags'] ? 1 : 2
                    );

                    if ($this->settings['smileys']) {
                        $text = $this->tools->smilies($text);
                    }

                    $text = $text . $moreLink;

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
                        $komm = 0;
                        
                        if($res['post_count']) {
                            $komm = $res['post_count'] - 1;
                        }                        
                        
                        $news .= '<br /><a href="../forum/?type=topic&id=' . $res['kom'] . '">' . _t('Discuss', 'system') . '</a> (' . $komm . ')';
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
        $counters = $this->db->query('SELECT (
SELECT COUNT(*) FROM `news`) all_news, (
SELECT COUNT(*) FROM `news` WHERE `time` > ' . (time() - 259200) . ') new_news')->fetch(\PDO::FETCH_OBJ);
        return $counters;
    }
}
