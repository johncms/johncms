<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;

$section = di(ForumSectionRepositoryInterface::class)->findById($id);
if ($section === null) {
    pageNotFound();
}

$redirectUrl = di(ForumSectionPathService::class)->getSectionUrl($section);

if (! headers_sent()) {
    http_response_code(301);
    header('Location: ' . $redirectUrl);
}
exit;
