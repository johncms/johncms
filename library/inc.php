<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

// config
$sql = mysql_query("SELECT `id`, `pos` FROM `library_cats` WHERE " . ($do == 'dir' ? '`parent`=' . $id : '`parent`=0') . " ORDER BY `pos` ASC");
$y = 0;
$arrsort = array();

if (mysql_result(mysql_query("SELECT COUNT(*) FROM `library_cats` WHERE " . ($do == 'dir' ? '`parent`=' . $id : '`parent`=0')), 0)) {
    while ($row = mysql_fetch_assoc($sql)) {
        $y++;
        $arrsort[$y] = $row['id'] . '|' . $row['pos'];
    }
}

$adm = ($rights > 4) ? true : false;
#$kmess = 10; // количество на странице
$i = 0;


// func //

function redir404()
{
    global $set;
    ob_get_level() and ob_end_clean();
    header('Location: ' . $set['homeurl'] . '/?err');
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
    private $result = array();
    private $cleaned = array('images' => 0, 'comments' => 0, 'tags' => 0);
    private $start_id = false;
    private $child;
    private $parent;

    public function __construct($id)
    {
        $this->start_id = $id;
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
        $this->parent = mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `parent` FROM `library_cats` WHERE id='" . $id . "' LIMIT 1"));
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
        $dirtype = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_cats` WHERE `id` = " . $id), 0) ? mysql_result(mysql_query("SELECT `dir` FROM `library_cats` WHERE `id` = " . $id . " LIMIT 1"), 0) : 0;
        if ($dirtype) {
            $sql = mysql_query("SELECT `id` FROM `library_cats` WHERE `parent`=" . $id);
            $this->result['dirs'][$id] = $id;
        } else {
            $this->result['dirs'][$id] = $id;
            $sql = mysql_query("SELECT `id` FROM `library_texts` WHERE `cat_id`=" . $id);
        }
        if (mysql_num_rows($sql) > 0) {
            while ($this->child = mysql_fetch_assoc($sql)) {
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
            mysql_query("DELETE FROM `cms_library_comments` WHERE `sub_id` = " . $data);
            $this->cleaned['comments'] += mysql_affected_rows();
            
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
                
        mysql_query("DELETE FROM `library_cats` WHERE `id` IN(" . implode(', ', $dirs) . ")");
        $dirs = mysql_affected_rows();
        mysql_query("DELETE FROM `library_texts` WHERE `id` IN(" . implode(', ', $texts) . ")");
        $texts = mysql_affected_rows();
        
        return array_merge(array('dirs' => $dirs, 'texts' => $texts), $trash);
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
        $sql = mysql_query("SELECT `id` FROM `library_cats` WHERE `parent`=" . $parent . " AND `dir`=1");
        if (mysql_num_rows($sql) > 0) {
            while ($this->child = mysql_fetch_assoc($sql)) {
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
        global $lng;
        $array = $this->result();
        $cnt = count($array);
        $return = array();
        $x = 1;
        foreach ($array as $k => $v) {
            $return[] = $x == $cnt ? '<strong>' . $v . '</strong>' : '<a href="?do=dir&amp;id=' . $k . '">' . functions::checkout($v) . '</a>';
            $x++;
        }

        return '<a href="?"><strong>' . $lng['library'] . '</strong></a> | ' . implode(' | ', $return);
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

    public function __construct($in, $link_url = '?act=tags&amp;tag=', $link_text = 'index.php?id=')
    {
        $this->link_url = $link_url;
        $this->link_text = $link_text;
        $this->in = $in;
    }

    public function proccess($tpl)
    {
        if ($this->in) {
            $this->res = array_map(array($this, $tpl), $this->in);

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

    public function __construct($id = 0)
    {
        $this->lib_id = $id;
    }

    public function get_all_tag_stats($tag)
    {
        $cnt = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_tags` WHERE `tag_name` = '" . mysql_real_escape_string($tag) . "'"), 0);
        if ($cnt) {
            $res = array();
            $sql = mysql_query("SELECT `lib_text_id` FROM `library_tags` WHERE `tag_name` = '" . mysql_real_escape_string($tag) . "'");
            while ($row = mysql_fetch_assoc($sql)) {
                $res[] = $row['lib_text_id'];
            }

            return $res;

        } else {
            return null;
        }
    }

    public function get_all_stat_tags($tpl = 0)
    {
        $cnt = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_tags` WHERE `lib_text_id` = " . $this->lib_id), 0);
        if ($cnt) {
            $res = array();
            $sql = mysql_query("SELECT `tag_name` FROM `library_tags` WHERE `lib_text_id` = " . $this->lib_id);
            while ($row = mysql_fetch_assoc($sql)) {
                $res[] = $row['tag_name'];
            }
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
            $res = "INSERT INTO `library_tags` (`lib_text_id`, `tag_name`) VALUES ";
            $array_res = array();
            foreach ($tags as $tag) {
                if (!$this->isset_tag($this->valid_tag($tag))) {
                    $array_res[] = '("' . $this->lib_id . '", "' . mysql_real_escape_string($this->valid_tag($tag)) . '")';
                }
            }
        }
        if (sizeof($array_res) > 0) {
            $res .= implode(', ', $array_res);

            return mysql_query($res) ? mysql_affected_rows() : 0;
        } else {
            return null; // добавлять не чего
        }
    }

    public function del_tags()
    {
        mysql_query("DELETE FROM `library_tags` WHERE `lib_text_id` = " . $this->lib_id);
        return mysql_affected_rows();
    }

    public function isset_tag($tag)
    {
        return $this->lib_id ? (mysql_num_rows(mysql_query("SELECT * FROM `library_tags` WHERE `lib_text_id` = " . $this->lib_id . " AND `tag_name` = '" . mysql_real_escape_string($tag) . "'")) ? 1 : 0) : 0;
    }

    public function valid_tag($tag)
    {
        return preg_replace(array('/[^[:alnum:]]/ui', '/\s\s+/'), ' ', preg_quote(mb_strtolower($tag)));
    }
    
    public function array_cloudtags() 
    {
        $result = array();
        $sql = mysql_query("SELECT `tag_name`, COUNT(*) as `count` FROM `library_tags` GROUP BY `tag_name` ORDER BY `count` DESC;");
        if (mysql_num_rows($sql)) {
            while($row = mysql_fetch_assoc($sql)) {
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
            $return = array();
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

            $return[] = array('name' => $key, 'rang' => $tmp);
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
        global $lng;
        
        $obj = new self();
        $tags = mysql_num_rows(mysql_query("SELECT `id` FROM `library_tags` LIMIT 1"));
        $res = ($tags > 0 ? $obj->cloud($obj->tag_rang($sort)) : '<p>' . $lng['list_empty'] . '</p>');
        file_put_contents('../files/cache/' . $sort . 'libcloud.dat', $res);
        
        return $this->get_cache($sort);
    }
}

class Rating
{
    private $lib_id = false;

    public function __construct($id) {
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
        if (mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = " . $user_id . " AND `st_id` = " . $this->lib_id), 0) > 0) {
            mysql_query("UPDATE `cms_library_rating` SET `point` = " . $point . " WHERE `user_id` = " . $user_id . " AND `st_id` = " . $this->lib_id);
        } elseif ($user_id && $this->lib_id > 0) {
            mysql_query("INSERT INTO `cms_library_rating` (`user_id`, `st_id`, `point`) VALUES (" . $user_id . ", " . $this->lib_id . ", " . $point . ")");
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    public function get_rate() {
        return floor(mysql_result(mysql_query("SELECT AVG(`point`) FROM `cms_library_rating` WHERE `st_id` = " . $this->lib_id), 0) * 2) / 2;
    }
    
    public function view_rate($anchor = 0) {
        $res = ($anchor ? '<a href="#rating">' : '') . functions::image('rating/star.' . (str_replace('.', '-', (string) $this->get_rate())) . '.gif', array('alt' => 'rating ' . $this->lib_id . ' article')) . ($anchor ? '</a>' : '') . ' (' . mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_library_rating` WHERE `st_id` = " . $this->lib_id), 0) . ')';
        
        return $res;
    }
    
    public function get_vote() {
        global $user_id;
        
        $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = " . $user_id . " AND `st_id` = " . $this->lib_id), 0) > 0 ? mysql_result(mysql_query("SELECT `point` FROM `cms_library_rating` WHERE `user_id` = " . $user_id . " AND `st_id` = " . $this->lib_id . " LIMIT 1"), 0) : -1;
        
        return $res;
    }
    
    public function print_vote() {
        global $lng_lib;
        
        $return = PHP_EOL;
        
        $return .= '<form action="index.php?id=' . $this->lib_id . '&amp;vote" method="post"><div class="gmenu" style="padding: 8px">' . PHP_EOL;
        $return .= '<a id="rating"></a>';
        for($r = 0; $r < 6; $r++) {
            $return .= ' <input type="radio" ' . ($r == $this->get_vote() ? 'checked="checked" ' : '') . 'name="vote" value="' . $r . '" />' . $r;
        }
        $return .= '<br /><input type="submit" name="rating_submit" value="' . $lng_lib['vote'] . '" />' . PHP_EOL;
        $return .= '</div></form>' . PHP_EOL;
        
        return $return . PHP_EOL;
    }
}