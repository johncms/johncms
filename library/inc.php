<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

// config
$sql = mysql_query("SELECT `id`, `pos` FROM `library_cats` WHERE " . ($do == 'dir' ? '`parent`=' . $id : '`parent`=0') . " ORDER BY `pos` ASC");
$y = 0;
$arrsort = array();

if (mysql_result(mysql_query("SELECT count(*) FROM `library_cats` WHERE " . ($do == 'dir' ? '`parent`=' . $id : '`parent`=0')), 0)) {
    while ($row = mysql_fetch_assoc($sql)) {
        $y++;
        $arrsort[$y] = $row['id'] . '|' . $row['pos'];
    }
}

$adm = ($rights > 4) ? true : false;
$kmess = 10; // количество на странице
$i = 0;


// func //

function redir404()
{
    global $set;
    header('Status: 404 Not Found');
    header('Location: ' . $set['homeurl'] . '/?err');
    exit;
}

function position($text, $chr)
{
    $result = mb_strpos($text, $chr);

    return $result !== false ? $result : 100;
}

function trim_small($array)
{
    $newarray = array();
    foreach ($array as $value) {
        if (mb_strlen($value) > 3) {
            $newarray[] = $value;
        }
    }

    return $newarray;
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
    * Рекурсивно проходит по дереву до корня, собирает массив с идами ми именами разделов
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
        $dirtype = mysql_result(mysql_query("SELECT count(*) FROM `library_cats` WHERE `id` = " . $id), 0) ? mysql_result(mysql_query("SELECT `dir` FROM `library_cats` WHERE `id` = " . $id . " LIMIT 1"), 0) : 0;
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
    * Очистка статей, удаляет коментарии, картинки и теги от статей
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
    * @param viod
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
            $return[] = $x == $cnt ? '<b>' . $v . '</b>' : '<a href="?do=dir&amp;id=' . $k . '">' . $v . '</a>';
            $x++;
        }

        return '<a href="?"><b>' . $lng['library'] . '</b></a> | ' . implode(' | ', $return);
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

    public function __construct($in, $link_url = '?act=tags&amp;tag=', $link_text = '?do=text&amp;id=')
    {
        $this->link_url = $link_url;
        $this->link_text = $link_text;
        $this->in = $in;
    }

    public function link_tags()
    {
        $this->res = array_map(array($this, 'tpl_tag'), $this->in);

        return $this;
    }
    
    public function tpl_tag($n) 
    {
        return '<a href="' . $this->link_url . $n . '">#' . $n . '</a>';
    }

    public function link_separator($sepatator = ' | ')
    {
        $this->res = implode($sepatator, $this->res ? $this->res : $this->in);

        return $this;
    }

    public function link_stats()
    {
        $this->res = array_map(array($this, 'tpl_stat'), $this->in);

        return $this;
    }
    
    public function tpl_stat($n) 
    {
        return '<a href="' . $this->link_text . $n['id'] . '">' . $n['name'] . '</a>';
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
    private $db_tags = false;
    private $db_texts = false;
    private $lib_id = false;

    public function __construct($id, $db = 'library_tags', $db_text = 'library_texts')
    {
        $this->lib_id = $id;
        $this->db_tags = $db;
        $this->db_texts = $db_text;
    }

    public function get_all_tag_stats($tag)
    {
        $cnt = mysql_result(mysql_query("SELECT count(*) FROM `" . $this->db_tags . "` WHERE `tag_name` = '" . mysql_real_escape_string($tag) . "'"), 0);
        if ($cnt) {
            $res = array();
            $sql = mysql_query("SELECT `lib_text_id` FROM `" . $this->db_tags . "` WHERE `tag_name` = '" . mysql_real_escape_string($tag) . "'");
            while ($row = mysql_fetch_assoc($sql)) {
                $res[] = $row['lib_text_id'];
            }

            return $res;
            /*
            $sql = mysql_query("select `" . $this->db_tags . "`.`lib_text_id`, `" . $this->db_texts . "`.`name` from " . $this->db_tags . " join " . $this->db_texts . " on `" . $this->db_tags . "`.`tag_name` = '" . mysql_real_escape_string($tag) . "' and `" . $this->db_tags . "`.`lib_text_id` = `" . $this->db_texts . "`.`id`");
                        $res[] = array('id' => $row['lib_text_id'], 'name' => $row['name']);
                        $obj = new Link_view($res);
                        return $obj->link_stats()->result();
            */
        } else {
            return null;
        }
    }

    public function get_all_stat_tags($tpl = 0)
    {
        $cnt = mysql_result(mysql_query("SELECT count(*) FROM `" . $this->db_tags . "` WHERE `lib_text_id` = " . $this->lib_id), 0);
        if ($cnt) {
            $res = array();
            $sql = mysql_query("SELECT `tag_name` FROM `" . $this->db_tags . "` WHERE `lib_text_id` = " . $this->lib_id);
            while ($row = mysql_fetch_assoc($sql)) {
                $res[] = $row['tag_name'];
            }
            $obj = new Link_view($res);
            if ($tpl == 1) {
                return $obj->link_tags()->link_separator()->result();
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
            $res = "INSERT INTO `" . $this->db_tags . "` (`lib_text_id`, `tag_name`) VALUES ";
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
        mysql_query("DELETE FROM `" . $this->db_tags . "` WHERE `lib_text_id` = " . $this->lib_id);
        return mysql_affected_rows();
    }

    public function isset_tag($tag)
    {
        return $this->lib_id ? (mysql_num_rows(mysql_query("SELECT * FROM `" . $this->db_tags . "` WHERE `lib_text_id` = " . $this->lib_id . " AND `tag_name` = '" . mysql_real_escape_string($tag) . "'")) ? 1 : 0) : 0;
    }

    public function valid_tag($tag)
    {
        return preg_replace(array('/[^[:alnum:]]/ui', "/\_\_+/"), '_', preg_quote(mb_strtolower($tag)));
    }
}