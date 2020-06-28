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

use Johncms\System\Legacy\Tools;
use PDO;

/**
 * Класс дерева (Nested Sets)
 * Class Tree
 *
 * @package Library
 * @author  Koenig(Compolomus)
 */
class Tree
{
    /**
     * Массив результата
     *
     * @var array
     */
    private $result = [];

    /**
     * Массив количества удаленных объектов
     *
     * @var array
     */
    private $cleaned = ['images' => 0, 'comments' => 0, 'tags' => 0];

    /**
     * Обязательный аргумент, индификатор текущей вложенности parent
     *
     * @var int
     */
    private $start_id;

    /** @var PDO $db */
    private $db;

    public function __construct(int $id)
    {
        $this->start_id = $id;
        $this->db = di(PDO::class);
    }

    /**
     * Рекурсивно проходит по дереву собирая в массив типы и уникальные иды каталогов
     *
     * @param int $id
     * @return Tree
     */
    public function getAllChildsId(int $id = 0): self
    {
        $id = (int) ($id === 0 ? $this->start_id : $id);
        $stmt = $this->db->prepare('SELECT `dir` FROM `library_cats` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $dirtype = (bool) $stmt->fetchColumn();
        $stmt = $this->db->prepare('SELECT `id` FROM ' . ($dirtype ? '`library_cats`' : '`library_texts`') . ' WHERE ' . ($dirtype ? '`parent`' : '`cat_id`') . ' = ?');
        $stmt->execute([$id]);
        $this->result['dirs'][$id] = $id;
        if ($stmt->rowCount()) {
            while ($child = $stmt->fetch()) {
                $this->result[($dirtype ? 'dirs' : 'texts')][$child['id']] = $child['id'];
                if ($dirtype) {
                    $this->getAllChildsId($child['id']);
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
    public function cleanTrash($data): array
    {
        if (! is_array($data)) {
            $stmt = $this->db->prepare('DELETE FROM `cms_library_comments` WHERE `sub_id` = ?');
            $stmt->execute([$data]);
            $this->cleaned['comments'] += $stmt->rowCount();

            $obj = new Hashtags($data);
            $this->cleaned['tags'] += $obj->delTags();

            // Utils::unlinkImages($data); ???
            if (file_exists(UPLOAD_PATH . 'library/images/small/' . $data . '.png')) {
                unlink(UPLOAD_PATH . 'library/images/big/' . $data . '.png');
                unlink(UPLOAD_PATH . 'library/images/orig/' . $data . '.png');
                unlink(UPLOAD_PATH . 'library/images/small/' . $data . '.png');
                $this->cleaned['images'] += 3;
            }
        } else {
            array_map([$this, 'cleanTrash'], $data);
        }

        return $this->cleaned;
    }

    /**
     * Удаляет ветку , возвращает количество удаленных каталогов, статей, тегов, коментариев и изображений в массиве
     *
     * @param void
     * @return array
     */
    public function cleanDir(): array
    {
        $array = $this->result();
        $dirs = array_key_exists('dirs', $array) ? $array['dirs'] : 0;
        $texts = array_key_exists('texts', $array) ? $array['texts'] : 0;

        $trash = $this->cleanTrash($array['texts']);

        $place_holders_dirs = implode(', ', array_fill(0, count($dirs), '?'));
        $place_holders_texts = implode(',', array_fill(0, count($texts), '?'));

        $stmt = $this->db->prepare('DELETE FROM `library_cats` WHERE `id` IN(' . $place_holders_dirs . ')');
        $stmt->execute(array_values($dirs));
        $dirs = $stmt->rowCount();
        $stmt = $this->db->prepare('DELETE FROM `library_texts` WHERE `id` IN(' . $place_holders_texts . ')');
        $stmt->execute(array_values($texts));
        $texts = $stmt->rowCount();
        if ($texts) {
            $this->db->exec('DELETE FROM `cms_library_rating` WHERE `st_id` NOT IN (SELECT `id` FROM `library_texts`)');
            $this->db->exec('DELETE FROM `cms_library_comments` WHERE `sub_id` NOT IN (SELECT `id` FROM `library_texts`)');
        }

        return array_merge(['dirs' => $dirs, 'texts' => $texts], $trash);
    }

    /**
     * Рекурсивно проходит по ветке и собирает дочерние вложения
     *
     * @param int $parent
     * @return Tree
     */
    public function getChildsDir(int $parent = 0): self
    {
        $parent = (int) ($parent === 0 ? $this->start_id : $parent);
        $stmt = $this->db->prepare('SELECT `id` FROM `library_cats` WHERE `parent` = ? AND `dir` = 1');
        $stmt->execute([$parent]);
        if ($stmt->rowCount()) {
            while ($child = $stmt->fetch()) {
                $this->result[] = $child['id'];
                $this->getChildsDir($child['id']);
            }
        }

        return $this;
    }

    /**
     * Рекурсивно проходит по дереву до корня, собирает массив с идами и именами разделов
     *
     * @param int $id
     * @return Tree
     */
    public function processNavPanel(int $id = 0): self
    {
        $id = (int) ($id === 0 ? $this->start_id : $id);
        $stmt = $this->db->prepare('SELECT `id`, `name`, `parent` FROM `library_cats` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $parent = $stmt->fetch();
        $this->result[] = ['id' => $parent['id'], 'name' => $parent['name']];
        if ($parent['parent'] !== 0) {
            $this->processNavPanel($parent['parent']);
        } else {
            krsort($this->result);
        }

        return $this;
    }

    /**
     * Собирает ссылки в верхнюю панель навигации
     *
     * @param void
     * @return void
     */
    public function printNavPanel(): void
    {
        ViewHelper::printNavPanel($this->result());
    }

    /**
     * Получение результата
     *
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }
}
