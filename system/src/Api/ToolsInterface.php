<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Api;

interface ToolsInterface
{
    public function antiflood();

    public function checkout($string, $br, $tags);

    public function displayDate(int $var);

    public function displayError($error, $link = '');

    public function displayPagination($url, $start, $total, $kmess);

    public function displayPlace(string $place, int $user_id = 0) : string;

    public function displayUser($user, array $arg = []);

    public function getFlag($locale);

    public function getSkin();

    public function getUser($id);

    public function isIgnor($id);

    public function rusLat($str);

    public function smilies($string, $adm = false);

    public function timecount(int $var);

    public function recountForumTopic($topic_id);

    public function trans($str); // DEPRECATED!!!
}
