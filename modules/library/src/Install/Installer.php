<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Library\Install;

use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Johncms\System\i18n\Translator;

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

        // Load the module's own translation domain so demo strings are rendered in the language
        // selected by the user running the installer (falls back to the English source strings).
        $this->loadTranslations();

        // Sections. `dir` = 1 marks a directory that holds subsections, `dir` = 0 a section with articles.
        // `user_add` = 1 allows visitors to publish their own articles in the section.
        $sections = [
            ['id' => 1, 'parent' => 0, 'name' => d__('library', 'Programming'), 'dir' => 1, 'user_add' => 0,
                'description' => d__('library', 'Articles and guides on development')],
            ['id' => 2, 'parent' => 1, 'name' => 'PHP', 'dir' => 0, 'user_add' => 0,
                'description' => d__('library', 'Materials on the PHP language')],
            ['id' => 3, 'parent' => 1, 'name' => 'JavaScript', 'dir' => 0, 'user_add' => 0,
                'description' => d__('library', 'Materials on JavaScript')],
            ['id' => 4, 'parent' => 0, 'name' => d__('library', 'Prose'), 'dir' => 0, 'user_add' => 0,
                'description' => d__('library', 'Short stories and essays')],
            ['id' => 5, 'parent' => 0, 'name' => d__('library', 'User articles'), 'dir' => 0, 'user_add' => 1,
                'description' => d__('library', 'A section where visitors can publish their own articles')],
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
            ['id' => 1, 'cat_id' => 2, 'name' => d__('library', 'Getting started with PHP'),
                'announce'    => d__('library', 'Installing PHP and your first script'),
                'text'        => d__('library', '<p>PHP is a popular language for web development. In this article we cover installing the interpreter and running your first script.</p>'),
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 2, 'cat_id' => 2, 'name' => d__('library', 'Arrays in PHP'),
                'announce'    => d__('library', 'Indexed and associative arrays'),
                'text'        => d__('library', '<p>Arrays in PHP can be indexed or associative. Let us look at the main functions for working with them.</p>'),
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 3, 'cat_id' => 3, 'name' => d__('library', 'JavaScript basics'),
                'announce'    => d__('library', 'Variables and functions'),
                'text'        => d__('library', '<p>JavaScript runs in the browser and makes pages interactive. Let us start with variables and functions.</p>'),
                'uploader_id' => 1, 'uploader' => 'admin'],
            ['id' => 4, 'cat_id' => 4, 'name' => d__('library', 'An autumn evening'),
                'announce'    => d__('library', 'A short story'),
                'text'        => d__('library', '<p>A warm autumn rain fell outside the window, and the city slowly sank into the evening twilight.</p>'),
                'uploader_id' => 1, 'uploader' => 'admin'],
            // Article in the user-writable section is attributed to a demo user.
            ['id' => 5, 'cat_id' => 5, 'name' => d__('library', 'My first article'),
                'announce'    => d__('library', 'An example of a user publication'),
                'text'        => d__('library', '<p>This is an example of an article added by a user. In this section anyone can publish their own material.</p>'),
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
            ['sub_id' => 1, 'user' => $author, 'text' => d__('library', '<p>Great introduction, straight to the point!</p>')],
            ['sub_id' => 1, 'user' => $reader, 'text' => d__('library', '<p>Thanks, I bookmarked the article.</p>')],
            ['sub_id' => 5, 'user' => $reader, 'text' => d__('library', '<p>It is great that you can publish your own materials.</p>')],
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

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('library', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the library catalog.
            $translator->addTranslationDomain('library', MODULES_PATH . 'library/locale', false);
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
