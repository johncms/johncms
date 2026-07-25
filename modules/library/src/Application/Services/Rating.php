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

use Johncms\System\Users\User;
use Johncms\System\View\Extension\Assets;
use PDO;

class Rating
{
    private Assets $asset;

    private PDO $db;

    private int $lib_id;

    public function __construct(int $id)
    {
        $this->db     = di(PDO::class);
        $this->asset  = di(Assets::class);
        $this->lib_id = $id;
        $this->check();
    }

    private function check(): void
    {
        if (isset($_POST['rating_submit'])) {
            $this->addVote((int) $_POST['vote']);
        }
    }

    private function addVote(int $point): void
    {
        $user = di(User::class);

        $point = in_array($point, range(0, 5), true) ? $point : 0;
        $stmt  = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ?');
        $stmt->execute([$user->id, $this->lib_id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare('UPDATE `cms_library_rating` SET `point` = ? WHERE `user_id` = ? AND `st_id` = ?');
            $stmt->execute([$point, $user->id, $this->lib_id]);
        } elseif ($this->lib_id > 0 && $user->isValid()) {
            $stmt = $this->db->prepare('INSERT INTO `cms_library_rating` (`user_id`, `st_id`, `point`) VALUES (?, ?, ?)');
            $stmt->execute([$user->id, $this->lib_id, $point]);
        }

        redirect($this->safeRefererUrl());
    }

    /**
     * Builds a same-origin redirect target from the Referer header.
     *
     * The Referer is attacker-controlled input: a crafted value could point to an
     * external host and turn this into an open redirect. Only the path and query
     * string are kept (the host is always discarded), so the result never leaves
     * the current site regardless of what the header contains.
     *
     * A path starting with a second slash or a backslash is rejected as well:
     * browsers normalise "/\evil.com" into the protocol-relative "//evil.com",
     * which would leave the site despite the leading slash.
     */
    private function safeRefererUrl(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $path    = parse_url($referer, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return '/library/';
        }

        if (str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return '/library/';
        }

        $query = parse_url($referer, PHP_URL_QUERY);

        return is_string($query) ? $path . '?' . $query : $path;
    }

    private function getRate(): int
    {
        $stmt = $this->db->prepare('SELECT AVG(`point`) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);

        return (int) (floor($stmt->fetchColumn() * 2) / 2);
    }

    public function viewRate(int $anchor = 0): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `cms_library_rating` WHERE `st_id` = ?');
        $stmt->execute([$this->lib_id]);
        $url = $this->asset->url('images/old/star.' . (str_replace('.', '-', (string) $this->getRate())) . '.gif');

        return '<img src="' . $url . '" alt="">' . ' (' . $stmt->fetchColumn() . ')';
    }

    public function printVote(): string
    {
        $user = di(User::class);

        $stmt     = $this->db->prepare('SELECT `point` FROM `cms_library_rating` WHERE `user_id` = ? AND `st_id` = ? LIMIT 1');
        $userVote = $stmt->execute([$user->id, $this->lib_id]) ? $stmt->fetchColumn() : -1;

        return ViewHelper::printVote($this->lib_id, $userVote);
    }
}
