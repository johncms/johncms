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
    'forum_topic',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_topic', ['slug'])) {
            $table->string('slug')->nullable()->after('name');
        }
    }
);

$topics = $connection->table('forum_topic')
    ->select(['id', 'section_id', 'name'])
    ->orderBy('section_id')
    ->orderBy('id')
    ->get();

foreach ($topics as $topic) {
    $sectionId = (int) ($topic->section_id ?? 0);
    $baseSlug = Str::slug((string) $topic->name);
    if ($baseSlug === '') {
        $baseSlug = 'topic';
    }

    $slug = $baseSlug;
    $suffix = 2;
    while (
        $connection->table('forum_topic')
            ->where('section_id', $sectionId)
            ->where('slug', $slug)
            ->where('id', '!=', $topic->id)
            ->exists()
    ) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $connection->table('forum_topic')
        ->where('id', $topic->id)
        ->update(['slug' => $slug]);
}

try {
    $connection->statement('ALTER TABLE `forum_topic` ADD UNIQUE `forum_topic_section_slug_unique` (`section_id`, `slug`)');
} catch (Throwable) {
}

echo 'The update was completed successfully';
