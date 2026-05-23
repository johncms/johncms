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

$reservedCategorySlugs = [
    'new',
    'top',
    'search',
    'favorites',
    'user-files',
    'load',
    'comments',
    'upload',
    'moderation',
    'edit-file',
    'delete-file',
    'edit-screen',
    'additional-files',
    'move-file',
    'import',
    'scan-dir',
    'recount',
    'top-users',
    'comments-review',
    'categories',
];

// --- download__category ---

$schema->table(
    'download__category',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumn('download__category', 'slug')) {
            $table->string('slug')->nullable()->after('name');
        }
    }
);

$categories = $connection->table('download__category')
    ->select(['id', 'refid', 'rus_name'])
    ->orderBy('refid')
    ->orderBy('id')
    ->get();

foreach ($categories as $category) {
    $parentId = (int) $category->refid;
    $baseSlug = Str::slug((string) $category->rus_name);
    if ($baseSlug === '') {
        $baseSlug = 'section-' . $category->id;
    }

    if (in_array($baseSlug, $reservedCategorySlugs, true)) {
        $baseSlug .= '-section';
    }

    $slug = $baseSlug;
    $suffix = 2;

    while (
        $connection->table('download__category')
            ->where('refid', $parentId)
            ->where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->exists()
    ) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $connection->table('download__category')
        ->where('id', $category->id)
        ->update(['slug' => $slug]);
}

try {
    $connection->statement('ALTER TABLE `download__category` ADD UNIQUE `download__category_refid_slug_unique` (`refid`, `slug`)');
} catch (Throwable) {
}

echo 'download__category: slugs generated.' . PHP_EOL;

// --- download__files ---

$schema->table(
    'download__files',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumn('download__files', 'slug')) {
            $table->string('slug')->nullable()->after('rus_name');
        }
    }
);

$files = $connection->table('download__files')
    ->select(['id', 'refid', 'rus_name'])
    ->orderBy('refid')
    ->orderBy('id')
    ->get();

foreach ($files as $file) {
    $categoryId = (int) $file->refid;
    $baseSlug = Str::slug((string) $file->rus_name);
    if ($baseSlug === '') {
        $baseSlug = 'file';
    }

    $slug = $baseSlug;
    $suffix = 2;

    while (
        $connection->table('download__files')
            ->where('refid', $categoryId)
            ->where('slug', $slug)
            ->where('id', '!=', $file->id)
            ->exists()
    ) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $connection->table('download__files')
        ->where('id', $file->id)
        ->update(['slug' => $slug]);
}

try {
    $connection->statement('ALTER TABLE `download__files` ADD UNIQUE `download__files_refid_slug_unique` (`refid`, `slug`)');
} catch (Throwable) {
}

echo 'download__files: slugs generated.' . PHP_EOL;
echo 'The update was completed successfully' . PHP_EOL;
