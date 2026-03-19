<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Users\User;

/** @var User $user */
$user = di(User::class);

$topicUrl = di(ForumTopicPathService::class)->getTopicUrlById($id);
if ($topicUrl === null) {
    pageNotFound();
}

$page = isset($_GET['page']) ? abs((int) $_GET['page']) : 0;
$start = isset($_GET['start']) ? abs((int) $_GET['start']) : 0;
if ($page <= 1 && $start > 0) {
    $page = (int) floor($start / max(1, (int) $user->config->kmess)) + 1;
}

$params = [];
if ($page > 1) {
    $params['page'] = $page;
}
if (array_key_exists('clip', $_GET)) {
    $params['clip'] = 1;
}
if (array_key_exists('vote_result', $_GET)) {
    $params['vote_result'] = 1;
}

if ($params !== []) {
    $topicUrl .= '?' . http_build_query($params);
}

if (! headers_sent()) {
    http_response_code(301);
    header('Location: ' . $topicUrl);
}
exit;
