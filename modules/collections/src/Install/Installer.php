<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Install;

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
        $schema = Capsule::schema();
        // Drop in reverse dependency order.
        $schema->dropIfExists('collection_item_values');
        $schema->dropIfExists('collection_items');
        $schema->dropIfExists('collection_sections');
        $schema->dropIfExists('collection_fields');
        $schema->dropIfExists('collections');
    }

    public function installDemoData(): void
    {
        // Load the module's translation domain so demo strings render in the installer's language.
        $this->loadTranslations();

        $nowDt = date('Y-m-d H:i:s');

        $collectionId = Capsule::table('collections')->insertGetId([
            'code'        => 'blog',
            'name'        => d__('collections', 'Blog'),
            'description' => d__('collections', 'Demo blog collection.'),
            'settings'    => json_encode(['has_sections' => true, 'per_page' => 10], JSON_UNESCAPED_UNICODE),
            'sort'        => 100,
            'active'      => 1,
            'public'      => 1,
            'created_at'  => $nowDt,
            'updated_at'  => $nowDt,
        ]);

        $authorFieldId = Capsule::table('collection_fields')->insertGetId([
            'collection_id' => $collectionId,
            'code'          => 'author',
            'name'          => d__('collections', 'Author'),
            'type'          => 'string',
            'required'      => 0,
            'multiple'      => 0,
            'sort'          => 100,
            'settings'      => null,
            'created_at'    => $nowDt,
            'updated_at'    => $nowDt,
        ]);

        $ratingFieldId = Capsule::table('collection_fields')->insertGetId([
            'collection_id' => $collectionId,
            'code'          => 'rating',
            'name'          => d__('collections', 'Rating'),
            'type'          => 'integer',
            'required'      => 0,
            'multiple'      => 0,
            'sort'          => 200,
            'settings'      => null,
            'created_at'    => $nowDt,
            'updated_at'    => $nowDt,
        ]);

        $sectionName = d__('collections', 'Technology');
        $sectionId = Capsule::table('collection_sections')->insertGetId([
            'collection_id' => $collectionId,
            'parent'        => null,
            'name'          => $sectionName,
            'code'          => Str::slug($sectionName),
            'description'   => null,
            'active'        => 1,
            'sort'          => 100,
            'created_at'    => $nowDt,
            'updated_at'    => $nowDt,
        ]);

        $itemName = d__('collections', 'Hello, world!');
        $itemId = Capsule::table('collection_items')->insertGetId([
            'collection_id' => $collectionId,
            'section_id'    => $sectionId,
            'name'          => $itemName,
            'code'          => Str::slug($itemName),
            'active'        => 1,
            'active_from'   => null,
            'active_to'     => null,
            'sort'          => 100,
            'preview_text'  => d__('collections', '<p>The first item of the demo collection.</p>'),
            'detail_text'   => d__('collections', '<p>The first item of the demo collection. Edit it in the admin panel.</p>'),
            'view_count'    => 0,
            'created_by'    => 1,
            'updated_by'    => 1,
            'created_at'    => $nowDt,
            'updated_at'    => $nowDt,
        ]);

        Capsule::table('collection_item_values')->insert([
            [
                'item_id'      => $itemId,
                'field_id'     => $authorFieldId,
                'value_string' => d__('collections', 'John'),
                'value_int'    => null,
                'value_double' => null,
                'value_date'   => null,
                'value_text'   => null,
                'sort'         => 0,
            ],
            [
                'item_id'      => $itemId,
                'field_id'     => $ratingFieldId,
                'value_string' => null,
                'value_int'    => 5,
                'value_double' => null,
                'value_date'   => null,
                'value_text'   => null,
                'sort'         => 0,
            ],
        ]);
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('collections', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            $translator->addTranslationDomain('collections', MODULES_PATH . 'collections/locale', false);
        }
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();

        $schema->create(
            'collections',
            static function (Blueprint $table) {
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
            }
        );

        $schema->create(
            'collection_fields',
            static function (Blueprint $table) {
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
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );

        $schema->create(
            'collection_sections',
            static function (Blueprint $table) {
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
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );

        // Self-referencing FK added after creation to avoid self-reference issues in CREATE TABLE.
        $schema->table('collection_sections', static function (Blueprint $table) {
            $table->foreign('parent')
                ->references('id')
                ->on('collection_sections')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        $schema->create(
            'collection_items',
            static function (Blueprint $table) {
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
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->foreign('section_id')
                    ->references('id')
                    ->on('collection_sections')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }
        );

        $schema->create(
            'collection_item_values',
            static function (Blueprint $table) {
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
                    ->onUpdate('cascade')
                    ->onDelete('cascade');

                $table->foreign('field_id')
                    ->references('id')
                    ->on('collection_fields')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );
    }
}
