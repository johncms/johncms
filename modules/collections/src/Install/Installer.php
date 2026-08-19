<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Install;

use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;
use Johncms\System\i18n\Translator;

class Installer extends \Johncms\Modules\Installer
{
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
}
