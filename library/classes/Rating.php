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

namespace Library;

/**
 * Звездный рейтинг статей
 * Class Rating
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Rating
{
    /**
     * обязательный аргумент, индификатор статьи
     * @var int
     */
    private $lib_id = false;

    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var \Johncms\Api\ToolsInterface
     */
    private $tools;

    /**
     * Rating constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $container = \App::getContainer();
        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get(\Johncms\Api\ToolsInterface::class);

        $this->lib_id = $id;
        $this->check();
    }

    /**
     * Чекер события нажатия кнопки
     */
    private function check()
    {
        if (isset($_POST['rating_submit'])) {
            $this->addVote($_POST['vote']);
        }
    }

    /**
     * Добавление|обновление рейтинговой звезды
     * @param $point (0 - 5)
     * @return redirect на страницу для голосования
     */
    private function addVote($point)
    {
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

    /**
     * Получение средней оценки (количество закрашенных звезд)
     * @return float|int
     */
    private function getRate()
    {
        $stmt = $this->db->prepare('SELECT AVG(`point`) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);

        return floor($stmt->fetchColumn() * 2) / 2;
    }

    /**
     * Вывод закрашенных звезд по рейтингу
     * @param int $anchor
     * @return string
     */
    public function viewRate($anchor = 0)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);
        $res = ($anchor ? '<a href="#rating">' : '') . $this->tools->image('rating/star.' . (str_replace('.', '-',
                    (string) $this->getRate())) . '.gif',
                ['alt' => 'rating ' . $this->lib_id . ' article']) . ($anchor ? '</a>' : '') . ' (' . $stmt->fetchColumn() . ')';

        return $res;
    }

    /**
     * Вывод формы для голосования
     * @return string
     */
    public function printVote()
    {
        global $systemUser;

        $stmt = $this->db->prepare('SELECT `point` FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ? LIMIT 1');
        $userVote = $stmt->execute([$systemUser->id, $this->lib_id]) ? $stmt->fetchColumn() : -1;
        
        $return = PHP_EOL;

        $return .= '<form action="index.php?id=' . $this->lib_id . '&amp;vote" method="post"><div class="gmenu" style="padding: 8px">' . PHP_EOL;
        $return .= '<a id="rating"></a>';
        for ($r = 0; $r < 6; $r++) {
            $return .= ' <input type="radio" ' . ($r == $userVote ? 'checked="checked" ' : '') . 'name="vote" value="' . $r . '" />' . $r;
        }
        $return .= '<br><input type="submit" name="rating_submit" value="' . _t('Vote') . '" />' . PHP_EOL;
        $return .= '</div></form>' . PHP_EOL;

        return $return;
    }
}
