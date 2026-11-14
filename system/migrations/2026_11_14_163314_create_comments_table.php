<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * The comments of every module that discusses its objects through the core: a file of the
     * downloads, an article of the library, a photo of an album, the guestbook of a profile.
     *
     * The subject is a key and an id, with no foreign key: the core does not know the tables of
     * the modules. A comment is ordered by the time it was written, since the comments carried
     * over from the old tables do not get their ids in that order.
     */
    public function up(): void
    {
        $this->schema->create('comments', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('subject_type', 32);
            $table->integer('subject_id')->unsigned();
            $table->integer('user_id')->unsigned()->index();
            $table->longText('text');
            $table->string('author_name')->default('');
            $table->string('author_ip', 45)->nullable();
            $table->string('author_ip_via_proxy', 45)->nullable();
            $table->text('author_user_agent')->nullable();
            $table->boolean('staff_smilies')->default(false);
            $table->dateTime('created_at');
            $table->dateTime('edited_at')->nullable();
            $table->integer('edited_by')->unsigned()->nullable();
            $table->string('editor_name')->nullable();
            $table->integer('edit_count')->unsigned()->default(0);
            $table->index(['subject_type', 'subject_id', 'created_at', 'id'], 'comments_subject_order');
            $table->index(['subject_type', 'created_at', 'id'], 'comments_type_order');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('comments');
    }
};
