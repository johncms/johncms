<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();
$connection = Capsule::connection();

$schema->table(
    'forum_sections',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_sections', ['slug'])) {
            $table->string('slug')->nullable()->after('name');
        }
    }
);

$connection->table('forum_sections')
    ->whereNull('parent')
    ->update(['parent' => 0]);

$reservedSlugs = [
    'addfile',
    'addvote',
    'bulk-delete-posts',
    'change-topic',
    'close',
    'delete-post',
    'delete-post-file',
    'delete-topic',
    'delvote',
    'download-file',
    'edit-post',
    'editvote',
    'files',
    'filter',
    'latest-topics',
    'move-topic',
    'new-message',
    'new-topic',
    'pin-topic',
    'poll-vote',
    'poll-voters',
    'post',
    'reply-message',
    'restore-post',
    'restore-topic',
    'search',
    'topic-visitors',
    'topics-period',
    'unread',
    'visitors',
];

$sections = $connection->table('forum_sections')
    ->select(['id', 'parent', 'name'])
    ->orderBy('parent')
    ->orderBy('id')
    ->get();

foreach ($sections as $section) {
    $parentId = (int) ($section->parent ?? 0);
    $baseSlug = Str::slug((string) $section->name);
    if ($baseSlug === '') {
        $baseSlug = 'section-' . $section->id;
    }

    if (in_array($baseSlug, $reservedSlugs, true)) {
        $baseSlug .= '-section';
    }

    $slug = $baseSlug;
    $suffix = 2;

    while (
        $connection->table('forum_sections')
            ->where('parent', $parentId)
            ->where('slug', $slug)
            ->where('id', '!=', $section->id)
            ->exists()
    ) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $connection->table('forum_sections')
        ->where('id', $section->id)
        ->update(['slug' => $slug]);
}

try {
    $connection->statement('ALTER TABLE `forum_sections` ADD UNIQUE `forum_sections_parent_slug_unique` (`parent`, `slug`)');
} catch (Throwable) {
}

echo 'The update was completed successfully';
