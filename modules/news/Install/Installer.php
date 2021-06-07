<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace News\Install;

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

    private function createTables(): void
    {
        $schema = Capsule::schema();

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
                $table->longText('attached_files')->nullable();
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
                $table->longText('attached_files')->nullable();
                $table->softDeletes();

                $table->foreign('article_id')
                    ->references('id')
                    ->on('news_articles')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );
    }
}
