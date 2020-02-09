<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Albums;

use Johncms\System\Users\User;
use Johncms\System\Legacy\Tools;
use PDO;

/**
 * Class Photo
 *
 * @property int $id
 * @property int $user_id
 * @property string $user_name
 * @property int $album_id
 * @property string $description
 * @property string $formatted_description
 * @property string $preview_text
 * @property string $img_name
 * @property string $tmb_name
 * @property int $time
 * @property string $comments
 * @property int $comm_count
 * @property int $access
 * @property int $vote_plus
 * @property int $vote_minus
 * @property int $rating
 * @property int $views
 * @property int $downloads
 * @property int $unread_comments
 * @property string $detail_url
 * @property string $preview_picture
 * @property string $picture
 * @property string $comments_url
 * @property string $download_url
 * @property string $user_albums_url
 * @property string $user_album_url
 * @property string $like_url
 * @property string $dislike_url
 * @property string $album_name
 * @property bool $can_vote
 * @property string $display_date
 *
 * @package Albums
 */
class Photo
{
    /** @var array Массив с данными фотографии */
    private $photo;

    /** @var Tools */
    public $tools;

    /** @var User */
    public $user;

    /** @var PDO */
    public $db;

    public function __construct(array $photo)
    {
        $this->photo = $photo;
        $this->tools = di(Tools::class);
        $this->user = di(User::class);
        $this->db = di(PDO::class);
    }

    /**
     * Получаем определенное поле
     *
     * @param string $name
     * @return mixed|string
     */
    public function getAttribute(string $name)
    {
        $value = $this->photo[$name] ?? '';
        $method = $this->getMethodName($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        return $value;
    }

    /**
     * Получаем название метода для получения атрибута
     *
     * @param $name
     * @return string
     */
    private function getMethodName(string $name): string
    {
        $name = ucwords(str_replace(['-', '_'], ' ', $name));
        $name = (string) str_replace(' ', '', $name);
        return 'get' . $name . 'Attribute';
    }

    /**
     * Динамическое получение атрибутов
     *
     * @param $name
     * @return mixed|string
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Установка значения
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->photo[$name] = $value;
    }

    /**
     * Проверка существования атрибута
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->photo[$name]);
    }

    /**
     * Обработка описания
     *
     * @return string
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $value = $this->tools->checkout($this->description, 1, 0);
        return $this->tools->smilies($value);
    }

    /**
     * Укороченный текст описания
     *
     * @return string
     */
    public function getPreviewTextAttribute(): string
    {
        $text = $this->tools->checkout($this->description, 0, 0);
        if (mb_strlen($text) > 100) {
            $text = mb_substr($text, 0, 97) . '...';
        }
        return $text;
    }

    /**
     * Детальная страница просмотра фотографии
     *
     * @return string
     */
    public function getDetailUrlAttribute(): string
    {
        return './show?al=' . $this->album_id . '&amp;img=' . $this->id . '&amp;user=' . $this->user_id . '&amp;view=1';
    }

    /**
     * Фотография для предпросмотра
     *
     * @return string
     */
    public function getPreviewPictureAttribute(): string
    {
        $preview = UPLOAD_PATH . 'users/album/' . $this->user_id . '/' . $this->tmb_name;
        if (is_file($preview)) {
            return pathToUrl($preview);
        }
        return '';
    }

    /**
     * Фотография для предпросмотра
     *
     * @return string
     */
    public function getPictureAttribute(): string
    {
        $picture = UPLOAD_PATH . 'users/album/' . $this->user_id . '/' . $this->img_name;
        if (is_file($picture)) {
            return pathToUrl($picture);
        }
        return '';
    }

    /**
     * Отформатированная дата
     *
     * @return string
     */
    public function getDisplayDateAttribute(): string
    {
        return $this->tools->displayDate($this->time);
    }

    /**
     * URL страницы комментариев
     *
     * @return string
     */
    public function getCommentsUrlAttribute(): string
    {
        return './comments?img=' . $this->id;
    }

    /**
     * URL на загрузку фотографии
     *
     * @return string
     */
    public function getDownloadUrlAttribute(): string
    {
        return './image_download?img=' . $this->id;
    }

    /**
     * URL страницы списка альбомов
     *
     * @return string
     */
    public function getUserAlbumsUrlAttribute(): string
    {
        return './list?user=' . $this->user_id;
    }

    /**
     * URL страницу альбома в котором лежит фотография
     *
     * @return string
     */
    public function getUserAlbumUrlAttribute(): string
    {
        return './show?al=' . $this->album_id . '&amp;user=' . $this->user_id;
    }

    /**
     * URL кнопки Нравится
     *
     * @return string
     */
    public function getLikeUrlAttribute(): string
    {
        return './vote?mod=plus&amp;img=' . $this->id;
    }

    /**
     * URL кнопки Не нравится
     *
     * @return string
     */
    public function getDislikeUrlAttribute(): string
    {
        return './vote?mod=minus&amp;img=' . $this->id;
    }

    /**
     * Название альбома
     *
     * @param $value
     * @return string
     */
    public function getAlbumNameAttribute($value): string
    {
        return $this->tools->checkout($value);
    }

    /**
     * Может ли пользователь голосовать за фотографию
     *
     * @return bool
     */
    public function getCanVoteAttribute(): bool
    {
        $can_vote = ($this->user->id !== $this->user_id && empty($this->user->ban) && $this->user->postforum > 5 && $this->user->datereg < (time() - 259200));
        if ($can_vote) {
            $req = $this->db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '" . $this->user->id . "' AND `file_id` = '" . $this->id . "' LIMIT 1");
            if (! $req->rowCount()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Рейтинг
     *
     * @return int
     */
    public function getRatingAttribute(): int
    {
        return ($this->vote_plus - $this->vote_minus);
    }
}
