<?php

/**
 * The tables of the news module as they stood when migrations were introduced.
 *
 * Part of the baseline, so it creates only what is missing: a site upgrading from 9.9 already has
 * them and has to arrive at the same schema as a fresh installation.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createNewsSections();
        $this->createNewsArticles();
        $this->createNewsVotes();
        $this->createNewsSearchIndex();
        $this->createNewsComments();
    }

    private function createNewsSections(): void
    {
        if ($this->schema->hasTable('news_sections')) {
            return;
        }

        $this->schema->create('news_sections', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('parent')->index()->nullable();
            $table->string('name');
            $table->string('code')->index()->nullable();
            $table->text('text')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createNewsArticles(): void
    {
        if ($this->schema->hasTable('news_articles')) {
            return;
        }

        $this->schema->create('news_articles', static function (TableDefinition $table): void {
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
        });
    }

    private function createNewsVotes(): void
    {
        if ($this->schema->hasTable('news_votes')) {
            return;
        }

        $this->schema->create('news_votes', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('vote');
            $table->unique(['article_id', 'user_id'], 'article_user');
            $table->foreign('article_id')
                ->references('id')
                ->on('news_articles')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }

    private function createNewsSearchIndex(): void
    {
        if ($this->schema->hasTable('news_search_index')) {
            return;
        }

        $this->schema->create('news_search_index', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('article_id')->unsigned()->index();
            $table->longText('text');
            $table->foreign('article_id')
                ->references('id')
                ->on('news_articles')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }

    private function createNewsComments(): void
    {
        if ($this->schema->hasTable('news_comments')) {
            return;
        }

        $this->schema->create('news_comments', static function (TableDefinition $table): void {
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
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }
};
