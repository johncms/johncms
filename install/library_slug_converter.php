<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

require '../system/bootstrap.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

$schema = Capsule::schema();

// --- Categories ---

if (! $schema->hasColumn('library_cats', 'slug')) {
    $schema->table('library_cats', static function (Blueprint $table) {
        $table->string('slug')->nullable()->after('name');
    });
    echo "Added slug column to library_cats\n";
}

if (! $schema->hasIndex('library_cats', 'library_cats_parent_slug_unique')) {
    $schema->table('library_cats', static function (Blueprint $table) {
        $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
    });
    echo "Added unique index on library_cats(parent, slug)\n";
}

Capsule::table('library_cats')->update(['slug' => null]);

$categories = Capsule::table('library_cats')->orderBy('id')->get(['id', 'parent', 'name']);
$usedCatSlugs = [];
$catUpdated = 0;

foreach ($categories as $category) {
    $baseSlug = Str::slug($category->name);
    if ($baseSlug === '') {
        $baseSlug = 'section';
    }

    $slug = $baseSlug;
    $suffix = 2;
    while (isset($usedCatSlugs[$category->parent . ':' . $slug])) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $usedCatSlugs[$category->parent . ':' . $slug] = true;
    Capsule::table('library_cats')->where('id', $category->id)->update(['slug' => $slug]);
    ++$catUpdated;
}

echo "Categories: updated $catUpdated slugs\n";

// --- Articles ---

if (! $schema->hasColumn('library_texts', 'slug')) {
    $schema->table('library_texts', static function (Blueprint $table) {
        $table->string('slug')->nullable()->after('name');
    });
    echo "Added slug column to library_texts\n";
}

Capsule::table('library_texts')->update(['slug' => null]);

$articles = Capsule::table('library_texts')->orderBy('id')->get(['id', 'cat_id', 'name']);
$usedArticleSlugs = [];
$articleUpdated = 0;

foreach ($articles as $article) {
    $baseSlug = Str::slug($article->name);
    if ($baseSlug === '') {
        $baseSlug = 'article';
    }

    $slug = $baseSlug;
    $suffix = 2;
    while (isset($usedArticleSlugs[$article->cat_id . ':' . $slug])) {
        $slug = $baseSlug . '-' . $suffix;
        ++$suffix;
    }

    $usedArticleSlugs[$article->cat_id . ':' . $slug] = true;
    Capsule::table('library_texts')->where('id', $article->id)->update(['slug' => $slug]);
    ++$articleUpdated;
}

echo "Articles: updated $articleUpdated slugs\n";
echo "Done.\n";
