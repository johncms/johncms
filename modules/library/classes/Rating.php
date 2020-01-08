<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Library;

use Johncms\System\Users\User;
use Johncms\System\View\Extension\Assets;
use PDO;

/**
 * Звездный рейтинг статей
 * Class Rating
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Rating
{
    /**
     * @var Assets
     */
    private $asset;

    /**
     * @var PDO
     */
    private $db;

    /**
     * обязательный аргумент, индификатор статьи
     * @var int
     */
    private $lib_id;

    /**
     * Rating constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->db = di(PDO::class);
        $this->asset = di(Assets::class);

        $this->lib_id = $id;
        $this->check();
    }

    /**
     * Чекер события нажатия кнопки
     */
    private function check(): void
    {
        if (isset($_POST['rating_submit'])) {
            $this->addVote($_POST['vote']);
        }
    }

    /**
     * Добавление|обновление рейтинговой звезды
     * @param $point (0 - 5)
     * return redirect на страницу для голосования
     */
    private function addVote($point): void
    {
        $user = di(User::class);

        $point = in_array($point, range(0, 5), true) ? $point : 0;
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ?');
        $stmt->execute([$user->id, $this->lib_id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare('UPDATE `cms_library_rating` SET `point` = ? WHERE `user_id` = ? AND `st_id` = ?');
            $stmt->execute([$point, $user->id, $this->lib_id]);
        } elseif ($this->lib_id > 0 && $user->isValid()) {
            $stmt = $this->db->prepare('INSERT INTO `cms_library_rating` (`user_id`, `st_id`, `point`) VALUES (?, ?, ?)');
            $stmt->execute([$user->id, $this->lib_id, $point]);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * Получение средней оценки (количество закрашенных звезд)
     * @return int
     */
    private function getRate(): int
    {
        $stmt = $this->db->prepare('SELECT AVG(`point`) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);

        return (int) floor($stmt->fetchColumn() * 2) / 2;
    }

    /**
     * Вывод закрашенных звезд по рейтингу
     * @param int $anchor
     * @return string
     */
    public function viewRate(int $anchor = 0): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);

        return ($anchor ? '<a href="#rating">' : '') .
            '<img src="' . $this->asset->url('images/old/star.' . (str_replace('.', '-', (string) $this->getRate())) . '.gif') . '" alt="">' .
            ($anchor ? '</a>' : '') .
            ' (' . $stmt->fetchColumn() . ')';
    }

    /**
     * Вывод формы для голосования
     * @return string
     */
    public function printVote(): string
    {
        global $user; //TODO: убрать эту жесть!

        $stmt = $this->db->prepare('SELECT `point` FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ? LIMIT 1');
        $userVote = $stmt->execute([$user->id, $this->lib_id]) ? $stmt->fetchColumn() : -1;

        $return = PHP_EOL;

        $return .= '<form action="?id=' . $this->lib_id . '&amp;vote" method="post"><div class="gmenu" style="padding: 8px">' . PHP_EOL;
        $return .= '<a id="rating"></a>';
        for ($r = 0; $r < 6; $r++) {
            $return .= ' <input type="radio" ' . ($r === $userVote ? 'checked="checked" ' : '') . 'name="vote" value="' . $r . '" />' . $r;
        }
        $return .= '<br><input type="submit" name="rating_submit" value="' . _t('Vote') . '" />' . PHP_EOL;
        $return .= '</div></form>' . PHP_EOL;

        return $return;
    }
}
