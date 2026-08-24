<?php

/**
 * The tables of the collections module as they stood when migrations were introduced.
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
        $this->createCollections();
        $this->createCollectionFields();
        $this->createCollectionSections();
        $this->addSectionParentKey();
        $this->createCollectionItems();
        $this->createCollectionItemValues();
    }

    private function createCollections(): void
    {
        if ($this->schema->hasTable('collections')) {
            return;
        }

        $this->schema->create('collections', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->integer('sort')->default(100);
            $table->boolean('active')->default(true);
            // Whether the collection is reachable by a public URL / sitemap.
            // A private collection stays fully manageable and API-readable, it just mints no front URL.
            $table->boolean('public')->default(true);
            $table->timestamps();
        });
    }

    private function createCollectionFields(): void
    {
        if ($this->schema->hasTable('collection_fields')) {
            return;
        }

        $this->schema->create('collection_fields', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('collection_id')->unsigned()->index();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->boolean('multiple')->default(false);
            $table->integer('sort')->default(100);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'code'], 'collection_field_code');
            $table->foreign('collection_id')
                ->references('id')
                ->on('collections')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }

    private function createCollectionSections(): void
    {
        if ($this->schema->hasTable('collection_sections')) {
            return;
        }

        $this->schema->create('collection_sections', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('collection_id')->unsigned()->index();
            $table->integer('parent')->unsigned()->nullable()->index();
            $table->string('name');
            $table->string('code')->index();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(100);
            $table->timestamps();
            $table->unique(['collection_id', 'parent', 'code'], 'collection_section_code');
            $table->foreign('collection_id')
                ->references('id')
                ->on('collections')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }

    /**
     * A section points at its parent section, so the key can only be added once the table it
     * points at exists.
     */
    private function addSectionParentKey(): void
    {
        if ($this->schema->hasForeignKey('collection_sections', 'collection_sections_parent_foreign')) {
            return;
        }

        $this->schema->alter('collection_sections', static function (TableDefinition $table): void {
            $table->foreign('parent')
                ->references('id')
                ->on('collection_sections')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }

    private function createCollectionItems(): void
    {
        if ($this->schema->hasTable('collection_items')) {
            return;
        }

        $this->schema->create('collection_items', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('collection_id')->unsigned()->index();
            $table->integer('section_id')->unsigned()->nullable()->index();
            $table->string('name');
            $table->string('code')->index();
            $table->boolean('active')->default(true);
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_to')->nullable();
            $table->integer('sort')->default(100);
            $table->text('preview_text')->nullable();
            $table->longText('detail_text')->nullable();
            $table->integer('view_count')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'section_id', 'code'], 'collection_item_code');
            $table->index(['collection_id', 'active', 'sort'], 'collection_item_listing');
            $table->foreign('collection_id')
                ->references('id')
                ->on('collections')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
            $table->foreign('section_id')
                ->references('id')
                ->on('collection_sections')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::SetNull);
        });
    }

    private function createCollectionItemValues(): void
    {
        if ($this->schema->hasTable('collection_item_values')) {
            return;
        }

        $this->schema->create('collection_item_values', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('item_id')->unsigned()->index();
            $table->integer('field_id')->unsigned()->index();
            $table->string('value_string')->nullable();
            $table->bigInteger('value_int')->nullable();
            $table->double('value_double')->nullable();
            $table->dateTime('value_date')->nullable();
            $table->longText('value_text')->nullable();
            $table->integer('sort')->default(0);
            $table->index(['item_id', 'field_id'], 'collection_value_item_field');
            $table->index(['field_id', 'value_int'], 'collection_value_field_int');
            $table->index(['field_id', 'value_string'], 'collection_value_field_string');
            $table->index(['field_id', 'value_date'], 'collection_value_field_date');
            $table->foreign('item_id')
                ->references('id')
                ->on('collection_items')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
            $table->foreign('field_id')
                ->references('id')
                ->on('collection_fields')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });
    }
};
