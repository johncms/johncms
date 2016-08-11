<?php

class sitemap
{
    // Настройки карты форума
    private $cache_forum_map = 72;           // Время кэширования карты форума (часов)
    private $cache_forum_contents = 48;      // Время кэширования оглавления форума (часов)
    private $cache_forum_file = 'map_forum'; // Имя файла кэша (без расширения)

    // Настройки карты Библиотеки
    private $cache_lib_map = 72;         // Время кэширования карты библиотеки (часов)
    private $cache_lib_contents = 48;    // Время кэширования оглавления библиотеки (часов)
    private $cache_lib_file = 'map_lib'; // Имя файла кэша (без расширения)

    // Системные настройки
    private $links_count = 140; // Число ссылок в блоке
    private $set;               // Системные настройки модуля
    private $page;              //

    /**
     * @var PDO
     */
    private $db;

    function __construct()
    {
        global $set;
        $this->set = isset($set['sitemap']) ? unserialize($set['sitemap']) : [];
        $this->page = isset($_GET['p']) ? abs(intval($_GET['p'])) : 0;

        $this->db = App::getContainer()->get(PDO::class);
    }

    /**
     * Карта сайта
     *
     * @return string
     */
    public function site()
    {
        return ($this->set['forum'] ? '<p><b>Forum Map</b><br />' . $this->forum_map() . '</p>' : '') .
        ($this->set['lib'] ? '<p><b>Library Map</b><br />' . $this->library_map() . '</p>' : '');
    }

    /**
     * Содержание разделов форума
     *
     * @return bool|string
     */
    public function forum_contents()
    {
        global $set, $id, $lng;
        $file = ROOTPATH . 'files/cache/' . $this->cache_forum_file . '_' . $id . ($this->page ? '_' . $this->page : '') . '.dat';
        if (!$id) {
            return functions::display_error($lng['error_wrong_data']);
        }
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_forum_contents * 3600)) {
            return file_get_contents($file);
        } else {
            $req = $this->db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r'");

            if ($req->rowCount()) {
                $row = [];
                $res = $req->fetch();
                $req_t = $this->db->query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 't' AND `close` != '1' ORDER BY `time` DESC LIMIT " . ($this->page * $this->links_count) . ", " . $this->links_count);

                if ($req_t->rowCount()) {
                    while ($res_t = $req_t->fetch()) {
                        $row[] = '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $res_t['id'] . '">' . $res_t['text'] . '</a>';
                    }

                    $out = '<div class="phdr"><b>' . $lng['forum'] . '</b> | ' . $res['text'] . '</div><div class="menu">' . implode('<br />' . "\r\n", $row) . '</div>';

                    return file_put_contents($file, $out) ? $out : 'Forum Contents cache error';
                }
            }
        }

        return false;
    }

    /**
     * Содержание разделов Библиотеки
     *
     * @return bool|string
     */
    public function library_contents()
    {
        global $set, $id, $lng;

        $file = ROOTPATH . 'files/cache/' . $this->cache_lib_file . '_' . $id . ($this->page ? '_' . $this->page : '') . '.dat';

        if (!$id) {
            return functions::display_error($lng['error_wrong_data']);
        }

        if (file_exists($file) && filemtime($file) > (time() - $this->cache_lib_contents * 3600)) {
            return file_get_contents($file);
        } else {
            $req = $this->db->query("SELECT * FROM `library_cats` WHERE `id` = '$id'");

            if ($req->rowCount()) {
                $row = [];
                $res = $req->fetch();
                $req_a = $this->db->query("SELECT * FROM `library_texts` WHERE `cat_id` = '$id' AND `premod` = '1' ORDER BY `time` ASC LIMIT " . ($this->page * $this->links_count) . ", " . $this->links_count);

                if ($req_a->rowCount()) {
                    while ($res_a = $req_a->fetch()) {
                        $row[] = '<a href="' . $set['homeurl'] . '/library/index.php?do=text&amp;id=' . $res_a['id'] . '">' . functions::checkout($res_a['name']) . '</a>';
                    }

                    $out = '<div class="phdr"><b>' . $lng['library'] . '</b> | ' . $res['name'] . '</div><div class="menu">' . implode('<br />' . "\r\n", $row) . '</div>';

                    return file_put_contents($file, $out) ? $out : 'Library Contents cache error';
                }
            }
        }

        return false;
    }

    /**
     * Кэш карты Форума
     *
     * @return bool|string
     */
    private function forum_map()
    {
        global $set;
        $file = ROOTPATH . 'files/cache/' . $this->cache_forum_file . '.dat';
        if (file_exists($file) && filemtime($file) > (time() - $this->cache_forum_map * 3600)) {
            return file_get_contents($file);
        } else {
            $req = $this->db->query("SELECT * FROM `forum` WHERE `type` = 'r'");

            if ($req->rowCount()) {
                while ($res = $req->fetch()) {
                    $count = $this->db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 't' AND `close` != '1'")->fetchColumn();

                    if ($count) {
                        $text = html_entity_decode($res['text']);
                        $text = mb_substr($text, 0, 40);
                        $pages = ceil($count / $this->links_count);

                        if ($pages > 1) {
                            for ($i = 0; $i < $pages; $i++) {
                                $out[] = '<a href="' . $set['homeurl'] . '/forum/contents.php?id=' . $res['id'] . '&amp;p=' . $i . '">' . functions::checkout($text) . ' (' . ($i + 1) . ')</a>';
                            }
                        } else {
                            $out[] = '<a href="' . $set['homeurl'] . '/forum/contents.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>';
                        }
                    }
                }

                if (isset($out)) {
                    return file_put_contents($file, implode('<br />' . "\r\n", $out)) ? implode('<br />', $out) : 'Forum cache error';
                }
            }
        }

        return false;
    }

    /**
     * Кэш карты Библиотеки
     *
     * @return bool|string
     */
    private function library_map()
    {
        global $set;
        $file = ROOTPATH . 'files/cache/' . $this->cache_lib_file . '.dat';

        if (file_exists($file) && filemtime($file) > (time() - $this->cache_lib_map * 3600)) {
            return file_get_contents($file);
        } else {
            $req = $this->db->query("SELECT * FROM `library_cats` WHERE `dir` = '0'");

            if ($req->rowCount()) {
                while ($res = $req->fetch()) {
                    $count = $this->db->query("SELECT COUNT(*) FROM `library_texts` WHERE `cat_id` = '" . $res['id'] . "' AND `premod` = '1'")->fetchColumn();

                    if ($count) {
                        $text = html_entity_decode($res['name']);
                        $text = mb_substr($text, 0, 40);
                        $pages = ceil($count / $this->links_count);

                        if ($pages > 1) {
                            for ($i = 0; $i < $pages; $i++) {
                                $out[] = '<a href="' . $set['homeurl'] . '/library/contents.php?id=' . $res['id'] . '&amp;p=' . $i . '">' . functions::checkout($text) . ' (' . ($i + 1) . ')</a>';
                            }
                        } else {
                            $out [] = '<a href="' . $set['homeurl'] . '/library/contents.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>';
                        }
                    }
                }
                if (isset($out)) {
                    return file_put_contents($file, implode('<br />' . "\r\n", $out)) ? implode('<br />', $out) : 'Library cache error';
                }
            }
        }

        return false;
    }
}
