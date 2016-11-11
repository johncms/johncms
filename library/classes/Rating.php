<?php

namespace Library;

class Rating
{
    private $lib_id = false;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var \Johncms\Tools
     */
    private $tools;

    public function __construct($id) {
        $container = \App::getContainer();
        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get('tools');
     
        $this->lib_id = $id;
        $this->check();
    }
    
    public function check() {
        if (isset($_POST['rating_submit'])) {
            $this->add_vote($_POST['vote']);
        }    
    }
    
    public function add_vote($point) {
        global $systemUser;
                
        $point = in_array($point, range(0, 5)) ? $point : 0;
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ?');
        $stmt->execute([$systemUser->id, $this->lib_id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare('UPDATE `cms_library_rating` SET `point` = ? WHERE `user_id` = ? AND `st_id` = ?');
            $stmt->execute([$point, $systemUser->id, $this->lib_id]);
        } elseif ($systemUser->isValid() && $this->lib_id > 0) {
            $stmt = $this->db->prepare('INSERT INTO `cms_library_rating` (`user_id`, `st_id`, `point`) VALUES (?, ?, ?)');
            $stmt->execute([$systemUser->id, $this->lib_id, $point]);
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
        $res = ($anchor ? '<a href="#rating">' : '') . $this->tools->image('rating/star.' . (str_replace('.', '-', (string) $this->get_rate())) . '.gif', ['alt' => 'rating ' . $this->lib_id . ' article']) . ($anchor ? '</a>' : '') . ' (' . $stmt->fetchColumn() . ')';
        
        return $res;
    }
    
    public function get_vote() {
        global $systemUser;
        
        $stmt = $this->db->prepare('SELECT `point` FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ? LIMIT 1');
                        
        return $stmt->execute([$systemUser->id, $this->lib_id]) ? $stmt->fetchColumn() : -1;
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