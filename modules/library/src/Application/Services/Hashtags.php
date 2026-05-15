<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use PDO;

class Hashtags
{
    private int $lib_id;

    private PDO $db;

    public function __construct(int $id = 0)
    {
        $this->lib_id = $id;
        $this->db = di(PDO::class);
    }

    public function getAllTagStats(string $tag): ?array
    {
        $stmt = $this->db->prepare('SELECT `library_texts`.`id`, `library_tags`.`lib_text_id` FROM `library_tags` JOIN `library_texts` ON `library_texts`.`id` = `library_tags`.`lib_text_id` WHERE `tag_name` = ?');
        $stmt->execute([$tag]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } else {
            return null;
        }

        return $res;
    }

    public function getAllStatTags(int $tpl = 0): ?string
    {
        $stmt = $this->db->prepare('SELECT `tag_name` FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);
        if ($stmt->rowCount()) {
            $res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $obj = new Links($res);
            if ($tpl === 1) {
                return $obj->proccess('tplTag')->linkSeparator()->result();
            }

            return $obj->linkSeparator(', ')->result();
        }

        return null;
    }

    public function addTags(array $tags): ?int
    {
        if (empty($tags)) {
            return null;
        }
        $stmt = $this->db->prepare('INSERT INTO `library_tags` (`lib_text_id`, `tag_name`) VALUES (?, ?)');
        foreach ($tags as $tag) {
            if (! $this->issetTag($this->validTag($tag))) {
                $stmt->execute([$this->lib_id, $this->validTag($tag)]);
            }
        }

        return $stmt->rowCount();
    }

    public function delTags(): int
    {
        $stmt = $this->db->prepare('DELETE FROM `library_tags` WHERE `lib_text_id` = ?');
        $stmt->execute([$this->lib_id]);

        return $stmt->rowCount();
    }

    public function issetTag(string $tag): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM `library_tags` WHERE `lib_text_id` = ? AND `tag_name` = ?');
        $stmt->execute([$this->lib_id, $tag]);

        return $stmt->rowCount() > 0;
    }

    public function validTag(string $tag): string
    {
        return preg_replace(['/[^[:alnum:]]/ui', '/\s\s+/'], ' ', preg_quote(mb_strtolower($tag)));
    }

    public function arrayCloudTags(): array|false
    {
        $result = [];
        $stmt   = $this->db->query(
            'SELECT `library_texts`.`id`, `library_tags`.`lib_text_id`, `library_tags`.`tag_name`, COUNT(*) as `count` FROM `library_tags`
            JOIN `library_texts` ON `library_texts`.`id` = `library_tags`.`lib_text_id`
            GROUP BY `tag_name`
            ORDER BY `count` DESC'
        );
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                $result[$row['tag_name']] = $row['count'];
            }

            return $result;
        }

        return false;
    }

    public function tagRang(string $sort = 'cmpalpha'): array|false
    {
        $array = $this->arrayCloudTags();
        if ($array) {
            $return = [];
            $max    = max(array_values($array));
            $min    = min(array_values($array));
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
            uasort($return, [Utils::class, $sort]);

            return $return;
        }

        return false;
    }

    public function cloud(array $array): string
    {
        if (count($array)) {
            $obj = new Links($array);

            return $obj->proccess('tplCloud')->linkSeparator(PHP_EOL)->result();
        }

        return $this->getCache();
    }

    public function delCache(): void
    {
        file_exists(CACHE_PATH . 'cmpranglibcloud.dat') ? unlink(CACHE_PATH . 'cmpranglibcloud.dat') : false;
        file_exists(CACHE_PATH . 'cmpalphalibcloud.dat') ? unlink(CACHE_PATH . 'cmpalphalibcloud.dat') : false;
    }

    public function getCache(string $sort = 'cmpalpha'): string
    {
        if (file_exists(CACHE_PATH . $sort . 'libcloud.dat')) {
            return file_get_contents(CACHE_PATH . $sort . 'libcloud.dat');
        }

        return $this->setCache($sort);
    }

    public function setCache(string $sort = 'cmpalpha'): string
    {
        $obj  = new self();
        $tags = $this->db->query('SELECT `id` FROM `library_tags` LIMIT 1')->rowCount();
        $res  = ($tags > 0 ? $obj->cloud($obj->tagRang($sort)) : '<p>' . __('The list is empty') . '</p>');
        file_put_contents(CACHE_PATH . $sort . 'libcloud.dat', $res);

        return $this->getCache($sort);
    }
}
