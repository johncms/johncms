<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Users\User;

class Database
{
    public static function createTables(): void
    {
        $schema = Capsule::schema();
        $connection = Capsule::connection();

        $schema->dropAllTables();

        // Реклама
        $schema->create(
            'cms_ads',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->tinyInteger('type')->unsigned()->default(0)->nullable();
                $table->tinyInteger('view')->unsigned()->default(0)->nullable();
                $table->tinyInteger('layout')->unsigned()->default(0)->nullable();
                $table->integer('count')->unsigned()->default(0)->nullable();
                $table->integer('count_link')->unsigned()->default(0)->nullable();
                $table->text('name');
                $table->text('link');
                $table->integer('to')->unsigned()->default(0)->nullable();
                $table->string('color')->default('')->nullable();
                $table->integer('time')->unsigned()->default(0)->nullable();
                $table->integer('day')->unsigned()->default(0)->nullable();
                $table->tinyInteger('mesto')->unsigned()->default(0)->nullable();
                $table->tinyInteger('bold')->unsigned()->default(0)->nullable();
                $table->tinyInteger('italic')->unsigned()->default(0)->nullable();
                $table->tinyInteger('underline')->unsigned()->default(0)->nullable();
                $table->tinyInteger('show')->unsigned()->default(0)->nullable();
            }
        );

        // Альбомы
        $schema->create(
            'cms_album_cat',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->integer('sort')->unsigned()->default(0);
                $table->string('name');
                $table->text('description');
                $table->string('password')->nullable();
                $table->integer('access')->nullable()->index('access');
            }
        );

        $schema->create(
            'cms_album_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
                $table->integer('time')->unsigned()->default(0);
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        $schema->create(
            'cms_album_downloads',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->integer('album_id')->unsigned()->index('album_id');
                $table->text('description');
                $table->string('img_name')->default('');
                $table->string('tmb_name')->default('');
                $table->integer('time')->unsigned()->default(0);
                $table->boolean('comments')->default(1);
                $table->integer('comm_count')->unsigned()->default(0);
                $table->tinyInteger('access')->unsigned()->default(0)->index('access');
                $table->integer('vote_plus')->default(0);
                $table->integer('vote_minus')->default(0);
                $table->integer('views')->unsigned()->default(0);
                $table->integer('downloads')->unsigned()->default(0);
                $table->boolean('unread_comments')->default(0);
            }
        );

        $schema->create(
            'cms_album_views',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_votes',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->integer('file_id')->unsigned()->default(0)->index('file_id');
                $table->tinyInteger('vote');
            }
        );

        // Баны
        $schema->create(
            'cms_ban_ip',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->bigInteger('ip1')->default(0)->unique('ip1');
                $table->bigInteger('ip2')->default(0)->unique('ip2');
                $table->tinyInteger('ban_type')->default(0);
                $table->string('link')->default('');
                $table->string('who')->default('');
                $table->text('reason');
                $table->integer('date')->default(0);
            }
        );

        $schema->create(
            'cms_ban_users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->default(0)->index('user_id');
                $table->integer('ban_time')->default(0)->index('ban_time');
                $table->integer('ban_while')->default(0);
                $table->tinyInteger('ban_type')->default(1);
                $table->string('ban_who')->default('');
                $table->integer('ban_ref')->default(0);
                $table->text('ban_reason');
                $table->string('ban_raz')->default('');
            }
        );

        // Контакты
        $schema->create(
            'cms_contact',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('from_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->tinyInteger('type')->unsigned()->default(1);
                $table->tinyInteger('friends')->unsigned()->default(0);
                $table->tinyInteger('ban')->unsigned()->default(0)->index('ban');
                $table->tinyInteger('man')->unsigned()->default(0);
                $table->unique(['user_id', 'from_id'], 'id_user');
            }
        );

        // Счетчики
        $schema->create(
            'cms_counters',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sort')->default(1);
                $table->string('name')->default('');
                $table->text('link1');
                $table->text('link2');
                $table->tinyInteger('mode')->default(1);
                $table->boolean('switch')->default(0);
            }
        );

        // Файлы форума
        $schema->create(
            'cms_forum_files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('cat')->unsigned()->default(0)->index('cat');
                $table->integer('subcat')->unsigned()->default(0)->index('subcat');
                $table->integer('topic')->unsigned()->default(0)->index('topic');
                $table->integer('post')->unsigned()->default(0)->index('post');
                $table->integer('time')->unsigned()->default(0);
                $table->text('filename');
                $table->tinyInteger('filetype')->unsigned()->default(0);
                $table->integer('dlcount')->unsigned()->default(0);
                $table->boolean('del')->default(0);
            }
        );

        // Непрочитанное форума
        $schema->create(
            'cms_forum_rdm',
            static function (Blueprint $table) {
                $table->integer('topic_id')->unsigned()->default(0);
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->primary(['topic_id', 'user_id'], 'topic_user');
            }
        );

        // Опросы форума
        $schema->create(
            'cms_forum_vote',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('type')->default(0)->index('type');
                $table->integer('time')->unsigned()->default(0);
                $table->integer('topic')->unsigned()->default(0)->index('topic');
                $table->string('name');
                $table->integer('count')->unsigned()->default(0);
                $table->index(['type', 'topic'], 'type_topic');
            }
        );

        // Участники опросов
        $schema->create(
            'cms_forum_vote_users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user')->default(0);
                $table->integer('topic')->index('topic');
                $table->integer('vote');
                $table->index(['topic', 'user'], 'topic_user');
            }
        );

        // Почта
        $schema->create(
            'cms_mail',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->integer('from_id')->unsigned()->default(0)->index('from_id');
                $table->text('text');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->boolean('read')->default(0)->index('read');
                $table->boolean('sys')->default(0)->index('sys');
                $table->integer('delete')->unsigned()->default(0)->index('delete');
                $table->string('file_name')->default('');
                $table->integer('count')->default(0);
                $table->integer('size')->default(0);
                $table->string('them')->default('');
                $table->boolean('spam')->default(0);
            }
        );

        // Гостевые сессии
        $schema->create(
            'cms_sessions',
            static function (Blueprint $table) {
                $table->char('session_id', 32)->default('')->primary();
                $table->bigInteger('ip')->default(0);
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->string('browser')->default('');
                $table->integer('lastdate')->unsigned()->default(0)->index('lastdate');
                $table->integer('sestime')->unsigned()->default(0);
                $table->integer('views')->unsigned()->default(0);
                $table->smallInteger('movings')->unsigned()->default(0);
                $table->text('place');
            }
        );

        // Некоторые данные пользователя
        $schema->create(
            'cms_users_data',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->string('key')->default('')->index('key');
                $table->text('val');
            }
        );

        // Пользовательские гостевые
        $schema->create(
            'cms_users_guestbook',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->index('sub_id');
                $table->integer('time');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        // История IP
        $schema->create(
            'cms_users_iphistory',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->bigInteger('ip')->default(0)->index('user_ip');
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->integer('time')->unsigned();
            }
        );

        // Закладки в загрузках
        $schema->create(
            'download__bookmark',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->index('user_id');
                $table->integer('file_id')->index('file_id');
            }
        );

        // Категории в загрузках
        $schema->create(
            'download__category',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('sort')->default(0);
                $table->text('name');
                $table->integer('total')->unsigned()->default(0)->index('total');
                $table->text('rus_name');
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->text('desc');
            }
        );

        // Комментарии в загрузках
        $schema->create(
            'download__comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->index('sub_id');
                $table->integer('time');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        // Файлы в загрузках
        $schema->create(
            'download__files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->integer('type')->unsigned()->default(0)->index('type');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('rus_name');
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->string('rate')->default('0|0');
                $table->text('about');
                $table->text('desc');
                $table->integer('comm_count')->unsigned()->default(0)->index('comm_count');
            }
        );

        // Доп файлы в загрузках
        $schema->create(
            'download__more',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->text('rus_name');
                $table->integer('size')->unsigned()->default(0);
            }
        );

        // Гостевая
        $schema->create(
            'guest',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('adm')->default(0)->index('adm');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->integer('user_id')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('text');
                $table->bigInteger('ip')->default(0)->index('ip');
                $table->string('browser')->default('');
                $table->string('admin')->default('');
                $table->text('otvet');
                $table->integer('otime')->unsigned()->default(0);
                $table->string('edit_who')->default('');
                $table->integer('edit_time')->unsigned()->default(0);
                $table->tinyInteger('edit_count')->unsigned()->default(0);
            }
        );

        // Карма
        $schema->create(
            'karma_users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->string('name')->default('');
                $table->integer('karma_user')->unsigned()->default(0)->index('karma_user');
                $table->tinyInteger('points')->unsigned()->default(0);
                $table->tinyInteger('type')->unsigned()->default(0)->index('type');
                $table->integer('time')->unsigned()->default(0);
                $table->text('text');
            }
        );

        // Библиотека
        $schema->create(
            'library_cats',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('description');
                $table->boolean('dir')->default(0);
                $table->integer('pos')->unsigned()->default(0);
                $table->boolean('user_add')->default(0);
            }
        );

        $schema->create(
            'library_texts',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('cat_id')->unsigned()->default(0);
                $table->mediumText('text');
                $table->string('name')->default('')->index('name');
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

        // Tables for the news module
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

        $schema->create(
            'news_articles',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('section_id')->unsigned()->nullable()->index();
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

        // Пользователи
        $schema->create(
            'users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->default('');
                $table->string('name_lat', 100)->default('')->index('name_lat');
                $table->string('password')->default('');
                $table->tinyInteger('rights')->unsigned()->default(0);
                $table->tinyInteger('failed_login')->unsigned()->default(0);
                $table->string('imname')->default('');
                $table->string('sex', 5)->default('');
                $table->integer('komm')->unsigned()->default(0);
                $table->integer('postforum')->unsigned()->default(0);
                $table->integer('postguest')->unsigned()->default(0);
                $table->integer('yearofbirth')->unsigned()->default(0);
                $table->integer('datereg')->unsigned()->default(0);
                $table->integer('lastdate')->unsigned()->default(0)->index('lastdate');
                $table->string('mail')->default('');
                $table->integer('icq')->unsigned()->default(0);
                $table->string('skype')->default('');
                $table->string('jabber')->default('');
                $table->string('www')->default('');
                $table->text('about')->nullable();
                $table->string('live')->default('');
                $table->string('mibile')->default('');
                $table->string('status')->default('');
                $table->bigInteger('ip')->default(0);
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->text('browser');
                $table->boolean('preg')->default(0);
                $table->string('regadm')->default('');
                $table->boolean('mailvis')->default(0);
                $table->integer('dayb')->default(0);
                $table->integer('monthb')->default(0);
                $table->integer('sestime')->unsigned()->default(0);
                $table->integer('total_on_site')->unsigned()->default(0);
                $table->integer('lastpost')->unsigned()->default(0);
                $table->string('rest_code')->default('');
                $table->integer('rest_time')->unsigned()->default(0);
                $table->integer('movings')->unsigned()->default(0);
                $table->text('place')->nullable();
                $table->text('set_user')->nullable();
                $table->text('set_forum')->nullable();
                $table->text('set_mail')->nullable();
                $table->integer('karma_plus')->default(0);
                $table->integer('karma_minus')->default(0);
                $table->integer('karma_time')->unsigned()->default(0);
                $table->boolean('karma_off')->default(0);
                $table->integer('comm_count')->unsigned()->default(0);
                $table->integer('comm_old')->unsigned()->default(0);
                $table->text('smileys')->nullable();
                $table->text('notification_settings')->nullable();
                $table->boolean('email_confirmed')->nullable();
                $table->string('confirmation_code')->nullable();
                $table->string('new_email')->nullable();
                $table->text('admin_notes')->nullable();
            }
        );

        // Форум
        $schema->create(
            'forum_messages',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('topic_id')->index('topic_id');
                $table->longText('text');
                $table->integer('date')->nullable();
                $table->integer('user_id')->unsigned();
                $table->string('user_name')->nullable();
                $table->string('user_agent')->nullable();
                $table->bigInteger('ip')->nullable();
                $table->bigInteger('ip_via_proxy')->nullable();
                $table->boolean('pinned')->nullable();
                $table->string('editor_name')->nullable();
                $table->integer('edit_time')->nullable();
                $table->integer('edit_count')->nullable();
                $table->boolean('deleted')->nullable()->index('deleted');
                $table->string('deleted_by')->nullable();
            }
        );
        $connection->statement('ALTER TABLE `forum_messages` ADD FULLTEXT `text` (`text`)');

        $schema->create(
            'forum_sections',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent')->nullable()->index('parent');
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->integer('sort')->default('100');
                $table->integer('access')->nullable();
                $table->integer('section_type')->nullable();
            }
        );

        $schema->create(
            'forum_topic',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('section_id')->unsigned()->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->integer('view_count')->nullable();
                $table->integer('user_id')->unsigned();
                $table->string('user_name')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->integer('post_count')->nullable();
                $table->integer('mod_post_count')->nullable();
                $table->integer('last_post_date')->nullable();
                $table->integer('last_post_author')->unsigned()->nullable();
                $table->string('last_post_author_name')->nullable();
                $table->bigInteger('last_message_id')->nullable();
                $table->integer('mod_last_post_date')->nullable();
                $table->integer('mod_last_post_author')->unsigned()->nullable();
                $table->string('mod_last_post_author_name')->nullable();
                $table->bigInteger('mod_last_message_id')->nullable();
                $table->boolean('closed')->nullable();
                $table->string('closed_by')->nullable();
                $table->boolean('deleted')->nullable()->index('deleted');
                $table->string('deleted_by')->nullable();
                $table->mediumText('curators')->nullable();
                $table->boolean('pinned')->nullable();
                $table->boolean('has_poll')->nullable();
            }
        );

        // Уведомления
        $schema->create(
            'notifications',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->string('module')->comment('Module name');
                $table->string('event_type')->comment('Event type');
                $table->integer('user_id')->unsigned()->index()->comment('User identifier');
                $table->integer('sender_id')->unsigned()->nullable()->comment('Sender identifier');
                $table->integer('entity_id')->unsigned()->nullable()->comment('Entity identifier');
                $table->text('fields')->nullable()->comment('Event fields');
                $table->timestamp('read_at')->nullable()->comment('Read date');
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->index(['user_id', 'module', 'event_type', 'entity_id'], 'user_module_type_entity');
            }
        );

        // Email
        $schema->create(
            'email_messages',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('priority')->nullable()->comment('Priority of sending the message');
                $table->string('locale', 8)->comment('The language used for displaying the message');
                $table->string('template')->comment('Template name');
                $table->text('fields')->nullable()->comment('Event fields');
                $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
                $table->timestamps();
            }
        );
    }

    public static function installDemo(): void
    {
        $connection = Capsule::connection();
        $user = (new User())->find(1);

        $connection->table('news')->insert(
            [
                'time' => time(),
                'avt'  => $user->name,
                'name' => __('Welcome to our website!'),
                'text' => "Hello!\r\nWe hope that You will like it here and you will be our regular visitor.",
            ]
        );

        $connection->statement(
            "INSERT INTO `forum_sections` (`id`, `parent`, `name`, `description`, `meta_description`, `meta_keywords`, `sort`, `access`, `section_type`) VALUES
(1, 0, 'Общение', 'Свободное общение на любую тему', '', NULL, 1, 0, 0),
(2, 1, 'О разном', '', '', NULL, 1, 0, 1),
(3, 1, 'Знакомства', '', '', NULL, 2, 0, 1),
(4, 1, 'Жизнь ресурса', '', '', NULL, 3, 0, 1),
(5, 1, 'Новости', '', '', NULL, 4, 0, 1),
(6, 1, 'Предложения и пожелания', '', '', NULL, 5, 0, 1),
(7, 1, 'Разное', '', '', NULL, 6, 0, 1);"
        );

        $connection->statement(
            "INSERT INTO `forum_topic` (`id`, `section_id`, `name`, `description`, `meta_description`, `meta_keywords`, `view_count`, `user_id`, `user_name`, `created_at`, `post_count`, `mod_post_count`, `last_post_date`, `last_post_author`, `last_post_author_name`, `last_message_id`, `mod_last_post_date`, `mod_last_post_author`, `mod_last_post_author_name`, `mod_last_message_id`, `closed`, `closed_by`, `deleted`, `deleted_by`, `curators`, `pinned`, `has_poll`) VALUES
(1, 3, 'Привет всем!', '', '', NULL, 1, 1, 'admin', '2019-10-16 20:18:00', 1, 1, 1571257080, 1, 'admin', 1, 1571257080, 1, 'admin', 1, NULL, NULL, NULL, NULL, '', NULL, NULL);"
        );
        $connection->statement(
            "INSERT INTO `forum_messages` (`id`, `topic_id`, `text`, `date`, `user_id`, `user_name`, `user_agent`, `ip`, `ip_via_proxy`, `pinned`, `editor_name`, `edit_time`, `edit_count`, `deleted`, `deleted_by`) VALUES
(1, 1, 'Мы рады приветствовать Вас на нашем сайте :)\r\nДавайте знакомиться!', 1571257080, 1, 'admin', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL);"
        );

        $connection->statement(
            "INSERT INTO `guest` (`adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`) VALUES
(1, 1217060516, 1, 'admin', 'Добро пожаловать в Админ Клуб!\r\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\nПростым пользователям доступ сюда закрыт.', 2130706433, 'Opera/9.51', '', '', 0),
(0, 1217060536, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, 'Opera/9.51', 'admin', 'Проверка ответа Администратора', 1217064021),
(0, 1217061125, 1, 'admin', 'Для зарегистрированных пользователей Гостевая поддерживает BBcode:\r\n[b]жирный[/b]\r\n[i]курсив[/i]\r\n[u]подчеркнутый[/u]\r\n[red]красный[/red]\r\n[green]зеленый[/green]\r\n[blue]синий[/blue]\r\n\r\nи ссылки:\r\nhttp://gazenwagen.com\r\n\r\nДля гостей, эти функции закрыты.', 2130706433, 'Opera/9.51', '', '', 0);"
        );
    }
}
