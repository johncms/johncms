<?php

// config
/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$adm = ($rights > 4) ? true : false;
#$kmess = 10; // количество на странице
$i = 0;


// func //

function redir404()
{
    $config = App::getContainer()->get('config')['johncms'];

    ob_get_level() and ob_end_clean();
    header('Location: ' . $config['homeurl'] . '/?err');
    exit;
}

function position($text, $chr)
{
    $result = mb_strpos($text, $chr);

    return $result !== false ? $result : 100;
}

function cmprang($a, $b) 
{
    if ($a['rang'] == $b['rang']) {
        return 0;
    }
    return ($a['rang'] > $b['rang']) ? -1 : 1;
}

function cmpalpha($a, $b) 
{
    if ($a['name'] == $b['name']) {
        return 0;
    }
    return ($a['name'] < $b['name']) ? -1 : 1;
}

//
// classes //

/**
* Класс дерева
*/
class Tree
{
    private $result = [];
    private $cleaned = ['images' => 0, 'comments' => 0, 'tags' => 0];
    private $start_id = false;
    private $child;
    private $parent;
    /** @var PDO $db */
    private $db;

    public function __construct($id)
    {
        $this->start_id = $id;
        $this->db = App::getContainer()->get(PDO::class);
    }
    /**
    * Рекурсивно проходит по дереву до корня, собирает массив с идами и именами разделов
    * 
    * @param integer $id
    * @return Tree
    */
    public function process_nav_panel($id = 0)
    {
        $id = $id == 0 ? $this->start_id : $id;
        $stmt = $this->db->prepare('SELECT `id`, `name`, `parent` FROM `library_cats` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $this->parent = $stmt->fetch();
        $this->result[$this->parent['id']] = $this->parent['name'];
        if ($this->parent['parent'] != 0) {
            $this->process_nav_panel($this->parent['parent']);
        } else {
            ksort($this->result);
        }
        
        return $this;
    }
    /**
    * Рекурсивно проходит по дереву собирая в массив типы и уникальные иды каталогов
    * 
    * @param integer $id
    * @return Tree
    */
    public function get_all_childs_id($id = 0)
    {
        $id = $id == 0 ? $this->start_id : $id;
        $stmt = $this->db->prepare('SELECT `dir` FROM `library_cats` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $dirtype = $stmt->fetchColumn();
        $stmt = $this->db->prepare('SELECT `id` FROM ' . ($dirtype ? '`library_cats`' : '`library_texts`') . ' WHERE ' . ($dirtype ? '`parent`' : '`cat_id`') . ' = ?');
        $stmt->execute([$id]);
        $this->result['dirs'][$id] = $id;
        if ($stmt->rowCount() > 0) {
            while ($this->child = $stmt->fetch()) {
                $this->result[($dirtype ? 'dirs' : 'texts')][$this->child['id']] = $this->child['id'];
                if ($dirtype) {
                    $this->get_all_childs_id($this->child['id']);
                }
            }
        }

        return $this;
    }
    /**
    * Очистка статей, удаляет комментарии, картинки и теги от статей
    * 
    * @param mixed $data
    * @return array
    */
    public function clean_trash($data) {
        if (!is_array($data)) {
            $stmt = $this->db->prepare('DELETE FROM `cms_library_comments` WHERE `sub_id` = ?');
            $stmt->execute([$data]);
            $this->cleaned['comments'] += $stmt->rowCount();
            
            $obj = new Hashtags($data);
            $this->cleaned['tags'] += $obj->del_tags();
            
            if (file_exists('../files/library/images/small/' . $data . '.png')) {
                unlink('../files/library/images/big/' . $data . '.png'); 
                unlink('../files/library/images/orig/' . $data . '.png');
                unlink('../files/library/images/small/' . $data . '.png');
                $this->cleaned['images'] += 3;
            } 
        } else {
            array_map(array($this, 'clean_trash'), $data);
        }
        
        return $this->cleaned;
    }
    /**
    * Удаляет ветку , возвращает количество удаленных каталогов, статей, тегов, коментариев и изображений в массиве
    * 
    * @param void
    * @return array
    */
    public function clean_dir()
    {
        $array = $this->result();
        $dirs = array_key_exists('dirs', $array) ? $array['dirs'] : 0;
        $texts = array_key_exists('texts', $array) ? $array['texts'] : 0;
        
        $trash = $this->clean_trash($array['texts']);
        
        $place_holders_dirs = implode(', ', array_fill(0, count($dirs), '?'));
        $place_holders_texts = implode(',', array_fill(0, count($texts), '?'));
        
        $stmt = $this->db->prepare('DELETE FROM `library_cats` WHERE `id` IN(' . $place_holders_dirs . ')');
        $stmt->execute(array_values($dirs));
        $dirs = $stmt->rowCount();
        $stmt = $this->db->prepare('DELETE FROM `library_texts` WHERE `id` IN(' . $place_holders_texts . ')');
        $stmt->execute(array_values($texts));
        $texts = $stmt->rowCount();
        
        return array_merge(['dirs' => $dirs, 'texts' => $texts], $trash);
    }
    /**
    * Рекурсивно проходит по ветке и собирает дочерние вложения
    * 
    * @param integer $parent
    * @return Tree
    */
    public function get_childs_dir($parent = 0)
    {
        $parent = $parent == 0 ? $this->start_id : $parent;
        $stmt = $this->db->prepare('SELECT `id` FROM `library_cats` WHERE `parent` = ? AND `dir` = 1');
        $stmt->execute([$parent]);
        if ($stmt->rowCount() > 0) {
            while ($this->child = $stmt->fetch()) {
                $this->result[] = $this->child['id'];
                $this->get_childs_dir($this->child['id']);
            }
        }

        return $this;
    }
    /**
    * Собирает ссылки в верхнюю панель навигации
    * 
    * @param void
    * @return string
    */ 
    public function print_nav_panel()
    {
        $array = $this->result();
        $cnt = count($array);
        $return =[];
        $x = 1;
        foreach ($array as $k => $v) {
            $return[] = $x == $cnt ? '<strong>' . $v . '</strong>' : '<a href="?do=dir&amp;id=' . $k . '">' . functions::checkout($v) . '</a>';
            $x++;
        }

        return '<a href="?"><strong>' . _t('Library') . '</strong></a> | ' . implode(' | ', $return);
    }

    public function result()
    {
        return $this->result;
    }
}

class Link_view
{
    private $link_url;
    private $link_text;
    private $in;
    private $res;
    /** @var PDO $db */
    private $db;

    public function __construct($in, $link_url = '?act=tags&amp;tag=', $link_text = 'index.php?id=')
    {
        $this->link_url = $link_url;
        $this->link_text = $link_text;
        $this->in = $in;
        $this->db = App::getContainer()->get(PDO::class);
    }

    public function proccess($tpl)
    {
        if ($this->in) {
            $this->res = array_map([$this, $tpl], $this->in);

            return $this;
        } else {
            return false;
        }
    }
    
    public function tpl_tag($n) 
    {
        return '<a href="' . $this->link_url . $n . '">' . functions::checkout($n) . '</a>';
    }
    
    public function tpl_cloud($n) 
    {
        return '<a href="' . $this->link_url . functions::checkout($n['name']) . '"><span style="font-size: ' . $n['rang'] . 'em;">' . functions::checkout($n['name']) . '</span></a>';
    }

    public function link_separator($sepatator = ' | ')
    {
        if ($this->in) {
            $this->res = implode($sepatator, $this->res ? $this->res : $this->in);

            return $this;
        } else {
            return false;
        }
    }

    public function result()
    {
        return $this->res;
    }

    public function get_in()
    {
        return $this->in;
    }

}

class Hashtags
{
    private $lib_id = false;
    /** @var PDO $db */
    private $db;

    public function __construct($id = 0)
    {
        $this->lib_id = $id;
        $this->db = App::getContainer()->get(PDO::class);
    }

    public function get_all_tag_stats($tag)
    {
        $stmt = $this->db->prepare('SELECT `lib_text_id` FROM `library_tags` WHERE `tag_name` = ?');
        $stmt->execute([$tag]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } else {
            return null;
        }
        
        return $res;
    }

    public function get_all_stat_tags($tpl = 0)
    {
        $stmt = $this->db->prepare('SELECT `tag_name` FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $obj = new Link_view($res);
            if ($tpl == 1) {
                return $obj->proccess('tpl_tag')->link_separator()->result();
            } else {
                return $obj->link_separator(', ')->result();
            }
        } else {
            return null; // у статьи нет тегов
        }
    }

    public function add_tags($tags)
    {
        if (empty($tags)) {
            return null;
        } else {
            $stmt = $this->db->prepare('INSERT INTO `library_tags` (`lib_text_id`, `tag_name`) VALUES (?, ?)');
            foreach ($tags as $tag) {
                if (!$this->isset_tag($this->valid_tag($tag))) {
                    $stmt->execute([$this->lib_id, $this->valid_tag($tag)]);
                }
            }
        }
        
        return $stmt->rowCount();
    }

    public function del_tags()
    {
        $stmt = $this->db->prepare('DELETE FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);
        return $stmt->rowCount();
    }

    public function isset_tag($tag)
    {
        $stmt = $this->db->prepare('SELECT * FROM `library_tags` WHERE `lib_text_id` = ? AND `tag_name` = ?');
        $stmt->execute([$this->lib_id, $tag]);
        
        return $stmt->rowCount() > 0 ? true : false;
    }

    public function valid_tag($tag)
    {
        return preg_replace(['/[^[:alnum:]]/ui', '/\s\s+/'], ' ', preg_quote(mb_strtolower($tag)));
    }
    
    public function array_cloudtags() 
    {
        $result = [];
        $stmt = $this->db->query('SELECT `tag_name`, COUNT(*) as `count` FROM `library_tags` GROUP BY `tag_name` ORDER BY `count` DESC;');
        if ($stmt->rowCount()) {
            while($row = $stmt->fetch()) {
                $result[$row['tag_name']] = $row['count'];
            }
        return $result;            
        } else {
            return false;
        }
    }
    
    public function tag_rang($sort = 'cmpalpha') 
    {
        $array = $this->array_cloudtags();
        if ($array) {
            $return = [];
            $max = max(array_values($array));
            $min = min(array_values($array));
            foreach ($array as $key => $value) {
                if ($value > ($max * 0.8)) {
                    $tmp = 2.3;
                } elseif ($value < ($min * 1.2)) {
                    $tmp = 0.8;
                } else {
                    $tmp = round(($max + $value) / $max, 2);
                }

            $return[] = ['name' => $key, 'rang' => $tmp];
        }
        uasort($return, $sort);
        
        return $return;
        
        } else {
            return false;
        }
    }
    
    public function cloud($array) 
    {
        if (sizeof($array) > 0) {
            $obj = new Link_view($array);
            
            return $obj->proccess('tpl_cloud')->link_separator(PHP_EOL)->result();        
        } else {
            return $this->get_cache();
        }
    }
    
    public function del_cache() {
        file_exists('../files/cache/cmpranglibcloud.dat') ? unlink('../files/cache/cmpranglibcloud.dat') : false;
        file_exists('../files/cache/cmpalphalibcloud.dat') ? unlink('../files/cache/cmpalphalibcloud.dat') : false;
    }
    
    public function get_cache($sort = 'cmpalpha') {
        if (file_exists('../files/cache/' . $sort . 'libcloud.dat')) {
            return file_get_contents('../files/cache/' . $sort . 'libcloud.dat');
        } else {
            return $this->set_cache($sort);
        }
    }
    
    public function set_cache($sort = 'cmpalpha') {
        $obj = new self();
        $tags = $this->db->query('SELECT `id` FROM `library_tags` LIMIT 1')->rowCount();
        $res = ($tags > 0 ? $obj->cloud($obj->tag_rang($sort)) : '<p>' . _t('The list is empty') . '</p>');
        file_put_contents('../files/cache/' . $sort . 'libcloud.dat', $res);
        
        return $this->get_cache($sort);
    }
}

class Rating
{
    private $lib_id = false;
    /** @var PDO $db */
    private $db;

    public function __construct($id) {
        $this->db = App::getContainer()->get(PDO::class);
        $this->lib_id = $id;
        $this->check();
    }
    
    public function check() {
        if (isset($_POST['rating_submit'])) {
            $this->add_vote($_POST['vote']);
        }    
    }
    
    public function add_vote($point) {
        global $user_id;
                
        $point = in_array($point, range(0, 5)) ? $point : 0;
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ?');
        $stmt->execute([$user_id, $this->lib_id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare('UPDATE `cms_library_rating` SET `point` = ? WHERE `user_id` = ? AND `st_id` = ?');
            $stmt->execute([$point, $user_id, $this->lib_id]);
        } elseif ($user_id && $this->lib_id > 0) {
            $stmt = $this->db->prepare('INSERT INTO `cms_library_rating` (`user_id`, `st_id`, `point`) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, $this->lib_id, $point]);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    public function get_rate() {
        $stmt = $this->db->prepare('SELECT AVG(`point`) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);
        
        return floor($stmt->fetchColumn() * 2) / 2;
    }
    
    public function view_rate($anchor = 0) {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);
        $res = ($anchor ? '<a href="#rating">' : '') . functions::image('rating/star.' . (str_replace('.', '-', (string) $this->get_rate())) . '.gif', ['alt' => 'rating ' . $this->lib_id . ' article']) . ($anchor ? '</a>' : '') . ' (' . $stmt->fetchColumn() . ')';
        
        return $res;
    }
    
    public function get_vote() {
        global $user_id;
        
        $stmt = $this->db->prepare('SELECT `point` FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ? LIMIT 1');
                        
        return $stmt->execute([$user_id, $this->lib_id]) ? $stmt->fetchColumn() : -1;
    }
    
    public function print_vote() {
        
        $return = PHP_EOL;
        
        $return .= '<form action="index.php?id=' . $this->lib_id . '&amp;vote" method="post"><div class="gmenu" style="padding: 8px">' . PHP_EOL;
        $return .= '<a id="rating"></a>';
        for($r = 0; $r < 6; $r++) {
            $return .= ' <input type="radio" ' . ($r == $this->get_vote() ? 'checked="checked" ' : '') . 'name="vote" value="' . $r . '" />' . $r;
        }
        $return .= '<br><input type="submit" name="rating_submit" value="' . _t('Vote') . '" />' . PHP_EOL;
        $return .= '</div></form>' . PHP_EOL;
        
        return $return . PHP_EOL;
    }
}
