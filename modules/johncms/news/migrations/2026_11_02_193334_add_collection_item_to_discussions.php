<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        foreach (['news_comments', 'news_votes'] as $name) {
            $this->schema->alter($name, static function (TableDefinition $table): void {
                $table->integer('article_id')->unsigned()->nullable()->change();
                $table->integer('item_id')->unsigned()->nullable()->index();
            });
        }
        $this->schema->alter('news_votes', static function (TableDefinition $table): void {
            $table->unique(['item_id', 'user_id'], 'news_item_user');
        });
    }
};
