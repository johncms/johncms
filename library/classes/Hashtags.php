<?php

namespace Library;

class Hashtags
{
    private $lib_id = false;
    /** @var PDO $db */
    private $db;

    public function __construct($id = 0)
    {
        $this->lib_id = $id;
        $this->db = \App::getContainer()->get(\PDO::class);
    }

    public function get_all_tag_stats($tag)
    {
        $stmt = $this->db->prepare('SELECT `lib_text_id` FROM `library_tags` WHERE `tag_name` = ?');
        $stmt->execute([$tag]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
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
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            $obj = new Links($res);
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
        uasort($return, 'Library\Utils::' . $sort);
        
        return $return;
        
        } else {
            return false;
        }
    }
    
    public function cloud($array) 
    {
        if (sizeof($array) > 0) {
            $obj = new Links($array);
            
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