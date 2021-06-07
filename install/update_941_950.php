<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Checker\DBChecker;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();
$connection = Capsule::connection();

$db_checker = new DBChecker();
$version_info = $db_checker->versionInfo();
if ($version_info['error']) {
    $schema::defaultStringLength(191);
}

if (! $schema->hasTable('files')) {
    $schema->create(
        'files',
        static function (Blueprint $table) {
            $table->id();
            $table->string('storage')->index();
            $table->string('name');
            $table->string('path');
            $table->integer('size')->unsigned()->nullable();
            $table->string('md5', 32)->nullable();
            $table->string('sha1', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        }
    );
}

$schema->table(
    'guest',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('guest', ['attached_files'])) {
            $table->longText('attached_files')->nullable();
        }
    }
);

$schema->table(
    'news_articles',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('news_articles', ['attached_files'])) {
            $table->longText('attached_files')->nullable();
        }
    }
);

$schema->table(
    'news_comments',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('news_comments', ['attached_files'])) {
            $table->longText('attached_files')->nullable();
        }
    }
);

// Guestbook posts converter
if (empty($_SESSION['converted_posts'])) {
    $_SESSION['converted_posts'] = [];
}
$tools = di(\Johncms\System\Legacy\Tools::class);
$posts = (new \Guestbook\Models\Guestbook())->get();
foreach ($posts as $post) {
    if (! in_array($post->id, $_SESSION['converted_posts'])) {
        $post->text = $tools->checkout($post->text, 1, 1);
        $post->save();
        $_SESSION['converted_posts'][] = $post->id;
    }
}

// News comments converter
if (empty($_SESSION['converted_comments'])) {
    $_SESSION['converted_comments'] = [];
}
$tools = di(\Johncms\System\Legacy\Tools::class);
$posts = (new \News\Models\NewsComments())->get();
foreach ($posts as $post) {
    if (! in_array($post->id, $_SESSION['converted_comments'])) {
        $post->text = $tools->checkout($post->text, 1, 1);
        $post->save();
        $_SESSION['converted_posts'][] = $post->id;
    }
}

echo 'The update was completed successfully';
