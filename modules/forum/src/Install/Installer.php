<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Forum\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

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
        $connection = Capsule::connection();

        $connection->statement(
            "INSERT INTO `forum_sections` (`id`, `parent`, `name`, `slug`, `description`, `meta_description`, `meta_keywords`, `sort`, `access`, `section_type`) VALUES
(1, 0, 'Общение', 'obshhenie', 'Свободное общение на любую тему', '', NULL, 1, 0, 0),
(2, 1, 'О разном', 'o-raznom', '', '', NULL, 1, 0, 1),
(3, 1, 'Знакомства', 'znakomstva', '', '', NULL, 2, 0, 1),
(4, 1, 'Жизнь ресурса', 'zhizn-resursa', '', '', NULL, 3, 0, 1),
(5, 1, 'Новости', 'novosti', '', '', NULL, 4, 0, 1),
(6, 1, 'Предложения и пожелания', 'predlozheniya-i-pozhelaniya', '', '', NULL, 5, 0, 1),
(7, 1, 'Разное', 'raznoe', '', '', NULL, 6, 0, 1);"
        );

        $now = time();
        $connection->statement(
            'INSERT INTO `forum_topic` '
            . '(`id`, `section_id`, `name`, `slug`, `description`, `meta_description`, `meta_keywords`, `view_count`, '
            . '`user_id`, `user_name`, `created_at`, `post_count`, `mod_post_count`, `last_post_date`, `last_post_author`, '
            . '`last_post_author_name`, `last_message_id`, `mod_last_post_date`, `mod_last_post_author`, '
            . '`mod_last_post_author_name`, `mod_last_message_id`, `closed`, `closed_by`, `deleted`, `deleted_by`, '
            . '`curators`, `pinned`, `has_poll`) VALUES '
            . "(1, 3, 'Привет всем!', 'privet-vsem', '', '', NULL, 1, 1, 'admin', '" . date('Y-m-d H:i:s', $now) . "', "
            . "1, 1, $now, 1, 'admin', 1, $now, 1, 'admin', 1, NULL, NULL, NULL, NULL, '', NULL, NULL);"
        );
        $connection->statement(
            'INSERT INTO `forum_messages` '
            . '(`id`, `topic_id`, `text`, `date`, `user_id`, `user_name`, `user_agent`, `ip`, `ip_via_proxy`, '
            . '`pinned`, `editor_name`, `edit_time`, `edit_count`, `deleted`, `deleted_by`) VALUES '
            . "(1, 1, '<p>Мы рады приветствовать Вас на нашем сайте :)</p><p>Давайте знакомиться!</p>', $now, 1, 'admin', "
            . "'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) "
            . "Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44', "
            . '2130706433, 0, NULL, NULL, NULL, NULL, NULL, NULL);'
        );
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        $connection = Capsule::connection();

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
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->integer('sort')->default('100');
                $table->integer('access')->nullable();
                $table->integer('section_type')->nullable();

                $table->unique(['parent', 'slug'], 'forum_sections_parent_slug_unique');
            }
        );

        $schema->create(
            'forum_topic',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('section_id')->unsigned()->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
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

                $table->unique(['section_id', 'slug'], 'forum_topic_section_slug_unique');
            }
        );

        $schema->create(
            'forum_message_files',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('message_id')->unsigned()->index('forum_message_files_message_id');
                $table->bigInteger('file_id')->unsigned()->index('forum_message_files_file_id');
                $table->dateTime('created_at')->nullable();

                $table->unique(['message_id', 'file_id'], 'forum_message_files_message_file_unique');
            }
        );
    }
}
