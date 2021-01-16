<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\System\Legacy\Tools;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();
$connection = Capsule::connection();

$schema::defaultStringLength(191);

$connection->statement('ALTER TABLE cms_ads ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_cat ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_downloads ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_files ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_views ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_votes ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_ban_ip ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_ban_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_contact ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_counters ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_files ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_rdm ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_vote ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_vote_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_library_comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_library_rating ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_mail ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_sessions ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_data ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_guestbook ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_iphistory ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__bookmark ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__category ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__files ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__more ENGINE = InnoDB');
$connection->statement('ALTER TABLE guest ENGINE = InnoDB');
$connection->statement('ALTER TABLE karma_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE library_cats ENGINE = InnoDB');

try {
    $connection->statement('ALTER TABLE `library_tags` DROP INDEX `tag_name`, ADD INDEX `tag_name` (`tag_name`(191)) USING BTREE;');
    $connection->statement('ALTER TABLE `library_texts` DROP INDEX `name`, ADD INDEX `name` (`name`(191)) USING BTREE;');
} catch (Exception $exception) {
}

$connection->statement('ALTER TABLE library_tags ENGINE = InnoDB');
$connection->statement('ALTER TABLE library_texts ENGINE = InnoDB');
$connection->statement('ALTER TABLE news ENGINE = InnoDB');

try {
    $connection->statement('ALTER TABLE `users` DROP INDEX `place`');
    $connection->statement('ALTER TABLE `cms_sessions` DROP INDEX `place`');
} catch (Exception $exception) {
}

$connection->statement('ALTER TABLE `users` CHANGE `place` `place` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;');
$connection->statement('ALTER TABLE `cms_sessions` CHANGE `place` `place` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;');

$schema->table(
    'forum_sections',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_sections', ['meta_description', 'meta_keywords'])) {
            $table->text('meta_description')->nullable()->after('description');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        }
        if ($schema->hasColumns('forum_sections', ['old_id'])) {
            $table->dropColumn(['old_id']);
        }
    }
);

$schema->table(
    'forum_topic',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_topic', ['meta_description', 'meta_keywords'])) {
            $table->text('meta_description')->nullable()->after('description');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        }
        if ($schema->hasColumns('forum_topic', ['old_id'])) {
            $table->dropColumn(['old_id']);
        }
    }
);

if ($schema->hasColumns('forum_messages', ['old_id'])) {
    $schema->table(
        'forum_messages',
        static function (Blueprint $table) {
            $table->dropColumn(['old_id']);
        }
    );
}

$schema->dropIfExists('forum_redirects');

// Tables for the news module
if (! $schema->hasTable('news_sections')) {
    $schema->create(
        'news_sections',
        static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent')->index()->nullable();
            $table->string('name');
            $table->string('code')->index()->nullable();
            $table->text('text')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        }
    );
}

if (! $schema->hasTable('news_articles')) {
    $schema->create(
        'news_articles',
        static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->unsigned()->nullable()->index();
            $table->boolean('active')->nullable();
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_to')->nullable();
            $table->string('name');
            $table->string('page_title')->nullable();
            $table->string('code')->index();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();
            $table->text('preview_text')->nullable();
            $table->longText('text');
            $table->integer('view_count')->nullable();
            $table->string('tags')->nullable()->index();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['section_id', 'code'], 'section_code');
        }
    );
}

if (! $schema->hasTable('news_votes')) {
    $schema->create(
        'news_votes',
        static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('vote');

            $table->unique(['article_id', 'user_id'], 'article_user');

            $table->foreign('article_id')
                ->references('id')
                ->on('news_articles')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        }
    );
}

if (! $schema->hasTable('news_search_index')) {
    $schema->create(
        'news_search_index',
        static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned()->index();
            $table->longText('text');

            $table->foreign('article_id')
                ->references('id')
                ->on('news_articles')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        }
    );
}

if (! $schema->hasTable('news_comments')) {
    $schema->create(
        'news_comments',
        static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned()->index();
            $table->integer('user_id')->unsigned();
            $table->longText('text');
            $table->text('user_data');
            $table->dateTime('created_at');
            $table->softDeletes();

            $table->foreign('article_id')
                ->references('id')
                ->on('news_articles')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        }
    );
}

$tools = di(Tools::class);

$old_news = $connection->table('news')->get();

foreach ($old_news as $item) {
    $user = (new \Johncms\Users\User())->where('name', $item->avt)->first();
    $article = new \News\Models\NewsArticle();
    $article->active = true;
    $article->section_id = 0;
    $article->name = $item->name;
    $article->code = \Illuminate\Support\Str::slug($item->name);
    $article->text = $tools->checkout($item->text, 1, 1);
    $article->created_at = \Carbon\Carbon::createFromTimestamp($item->time);
    $article->created_by = $user->id ?? null;
    $article->save();
}

echo 'Update complete!';
