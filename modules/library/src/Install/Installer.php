<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Library\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createTables();
    }

    public function uninstall(): void
    {
    }

    public function installDemoData(): void
    {
        $now = time();

        // Sections. `dir` = 1 marks a directory that holds subsections, `dir` = 0 a section with articles.
        // `user_add` = 1 allows visitors to publish their own articles in the section.
        $sections = [
            ['id' => 1, 'parent' => 0, 'name' => 'Программирование', 'dir' => 1, 'user_add' => 0,
                'description' => 'Статьи и руководства по разработке'],
            ['id' => 2, 'parent' => 1, 'name' => 'PHP', 'dir' => 0, 'user_add' => 0,
                'description' => 'Материалы по языку PHP'],
            ['id' => 3, 'parent' => 1, 'name' => 'JavaScript', 'dir' => 0, 'user_add' => 0,
                'description' => 'Материалы по JavaScript'],
            ['id' => 4, 'parent' => 0, 'name' => 'Проза', 'dir' => 0, 'user_add' => 0,
                'description' => 'Рассказы и очерки'],
            ['id' => 5, 'parent' => 0, 'name' => 'Пользовательские статьи', 'dir' => 0, 'user_add' => 1,
                'description' => 'Раздел, в который посетители могут добавлять свои статьи'],
        ];

        $pos = 0;
        foreach ($sections as $section) {
            Capsule::table('library_cats')->insert([
                'id'          => $section['id'],
                'parent'      => $section['parent'],
                'name'        => $section['name'],
                'slug'        => Str::slug($section['name']),
                'description' => $section['description'],
                'dir'         => $section['dir'],
                'pos'         => ++$pos,
                'user_add'    => $section['user_add'],
            ]);
        }

        // The first two non-admin users are seeded by the installer and act as demo authors and commenters.
        $demoUsers = Capsule::table('users')
            ->where('id', '>', 1)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();
        $author  = $demoUsers[0] ?? null;
        $reader  = $demoUsers[1] ?? $demoUsers[0] ?? null;

        $articles = [
            ['id' => 1, 'cat_id' => 2, 'name' => 'Быстрый старт с PHP',
                'announce'    => 'Установка PHP и первый скрипт',
                'text'        => '<p>PHP — популярный язык для веб-разработки. В этой статье мы рассмотрим установку интерпретатора и запуск первого скрипта.</p>',
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 2, 'cat_id' => 2, 'name' => 'Массивы в PHP',
                'announce'    => 'Индексные и ассоциативные массивы',
                'text'        => '<p>Массивы в PHP бывают индексными и ассоциативными. Разберём основные функции для работы с ними.</p>',
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 3, 'cat_id' => 3, 'name' => 'Основы JavaScript',
                'announce'    => 'Переменные и функции',
                'text'        => '<p>JavaScript выполняется в браузере и делает страницы интерактивными. Начнём с переменных и функций.</p>',
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 4, 'cat_id' => 4, 'name' => 'Осенний вечер',
                'announce'    => 'Небольшой рассказ',
                'text'        => '<p>За окном шёл тёплый осенний дождь, и город неспешно погружался в вечерние сумерки.</p>',
                'uploader_id' => 1, 'uploader' => 'admin'],
            // Article in the user-writable section is attributed to a demo user.
            ['id' => 5, 'cat_id' => 5, 'name' => 'Моя первая статья',
                'announce'    => 'Пример пользовательской публикации',
                'text'        => '<p>Это пример статьи, добавленной пользователем. В этом разделе каждый желающий может опубликовать свой материал.</p>',
                'uploader_id' => $author->id ?? 1, 'uploader' => $author->name ?? 'admin'],
        ];

        foreach ($articles as $article) {
            Capsule::table('library_texts')->insert([
                'id'          => $article['id'],
                'cat_id'      => $article['cat_id'],
                'name'        => $article['name'],
                'slug'        => Str::slug($article['name']),
                'announce'    => $article['announce'],
                'text'        => $article['text'],
                'uploader'    => $article['uploader'],
                'uploader_id' => $article['uploader_id'],
                'count_views' => 0,
                'premod'      => 1,
                'comments'    => 1,
                'comm_count'  => 0,
                'time'        => $now,
            ]);
        }

        // Demo comments from different users on a couple of articles.
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';
        $comments = [
            ['sub_id' => 1, 'user' => $author, 'text' => '<p>Отличное введение, всё по делу!</p>'],
            ['sub_id' => 1, 'user' => $reader, 'text' => '<p>Спасибо, добавил статью в закладки.</p>'],
            ['sub_id' => 5, 'user' => $reader, 'text' => '<p>Здорово, что можно публиковать свои материалы.</p>'],
        ];

        $commentCounts = [];
        foreach ($comments as $comment) {
            if ($comment['user'] === null) {
                continue;
            }
            Capsule::table('cms_library_comments')->insert([
                'sub_id'     => $comment['sub_id'],
                'time'       => $now,
                'user_id'    => $comment['user']->id,
                'text'       => $comment['text'],
                'reply'      => '',
                'attributes' => serialize([
                    'author_name'         => $comment['user']->name,
                    'author_ip'           => 2130706433,
                    'author_ip_via_proxy' => 0,
                    'author_browser'      => $ua,
                ]),
            ]);
            $commentCounts[$comment['sub_id']] = ($commentCounts[$comment['sub_id']] ?? 0) + 1;
        }

        foreach ($commentCounts as $articleId => $count) {
            Capsule::table('library_texts')->where('id', $articleId)->update(['comm_count' => $count]);
        }

        // Demo ratings from users on a few articles (point range 0-5, one vote per user and article).
        $ratings = [
            ['st_id' => 1, 'user' => $author, 'point' => 5],
            ['st_id' => 1, 'user' => $reader, 'point' => 4],
            ['st_id' => 3, 'user' => $author, 'point' => 4],
            ['st_id' => 5, 'user' => $reader, 'point' => 5],
        ];
        foreach ($ratings as $rating) {
            if ($rating['user'] === null) {
                continue;
            }
            Capsule::table('cms_library_rating')->insert([
                'user_id' => $rating['user']->id,
                'st_id'   => $rating['st_id'],
                'point'   => $rating['point'],
            ]);
        }
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        $connection = Capsule::connection();

        // Библиотека
        if (! $schema->hasTable('library_cats')) {
            $schema->create(
                'library_cats',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('parent')->unsigned()->default(0);
                    $table->string('name')->default('');
                    $table->string('slug')->nullable();
                    $table->text('description');
                    $table->boolean('dir')->default(0);
                    $table->integer('pos')->unsigned()->default(0);
                    $table->boolean('user_add')->default(0);
                    $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
                }
            );
        }

        if (! $schema->hasTable('library_texts')) {
            $schema->create(
                'library_texts',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('cat_id')->unsigned()->default(0);
                    $table->mediumText('text');
                    $table->string('name')->default('')->index('name');
                    $table->string('slug')->nullable();
                    $table->text('announce');
                    $table->string('uploader')->default('');
                    $table->integer('uploader_id')->unsigned()->default(0);
                    $table->integer('count_views')->unsigned()->default(0);
                    $table->boolean('premod')->default(0);
                    $table->boolean('comments')->default(0);
                    $table->integer('comm_count')->unsigned()->default(0);
                    $table->integer('time')->unsigned()->default(0);
                }
            );
            $connection->statement('ALTER TABLE `library_texts` ADD FULLTEXT `text` (`text`)');
            $connection->statement('ALTER TABLE `library_texts` ADD FULLTEXT `idx_name` (`name`)');
        }

        $schema->create(
            'library_tags',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('lib_text_id')->unsigned()->default(0)->index('lib_text_id');
                $table->string('tag_name')->default('')->index('tag_name');
            }
        );

        $schema->create(
            'cms_library_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
                $table->integer('time')->default(0);
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        $schema->create(
            'cms_library_rating',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('st_id')->unsigned();
                $table->tinyInteger('point');
                $table->index(['user_id', 'st_id'], 'user_article');
            }
        );
    }
}
