<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Updates\SystemOperationContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionFieldSettingsTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionsApiInterface $items;
    private CollectionSchemaApiInterface $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'settings-test', 'name' => 'Settings test']);
        $this->items = $this->container()->get(CollectionsApiInterface::class);
        $this->schema = $this->container()->get(CollectionSchemaApiInterface::class);
    }

    public function testDefaultsApplyOnlyOnCreationAndExplicitEmptyValuesSuppressThem(): void
    {
        $this->field('headline', FieldType::String_, ['default' => 'Initial', 'min_length' => 3, 'max_length' => 20]);
        $id = $this->items->addItem($this->form('first'));
        self::assertSame('Initial', $this->items->getItem($id)->values['headline']);
        $empty = $this->items->addItem($this->form('explicit-empty', ['headline' => '']));
        self::assertArrayNotHasKey('headline', $this->items->getItem($empty)->values);
        $this->items->updateItem($id, $this->form('first'));
        self::assertArrayNotHasKey('headline', $this->items->getItem($id)->values);
        $imported = $this->container()->get(SystemOperationContext::class)->runTrusted(fn () => $this->container()->get(CollectionsImportApiInterface::class)->importItem($this->form('historical'), new CollectionItemHistoryDTO(null, null)));
        self::assertArrayNotHasKey('headline', $this->items->getItem($imported)->values);
    }

    public function testDateAndMultipleBooleanDefaultsKeepTheirTypesAndOrder(): void
    {
        $this->field('date', FieldType::Date, ['default' => '2001-02-03']);
        $this->field('flags', FieldType::Boolean, ['default' => ['1', '0'], 'max_values' => 2], true);
        $id = $this->items->addItem($this->form('typed'));
        self::assertSame('2001-02-03 00:00:00', $this->items->getItem($id)->values['date']->format('Y-m-d H:i:s'));
        self::assertSame([true, false], $this->items->getItem($id)->values['flags']);
        $id = $this->items->addItem($this->form('empty-row', ['flags' => ['0', '', '1']]));
        self::assertSame([false, true], $this->items->getItem($id)->values['flags']);
    }

    public function testSharedConstraintsRejectApiValuesAndPreserveTheExistingItem(): void
    {
        $this->field('text', FieldType::Text, ['min_length' => 3, 'max_length' => 5, 'max_values' => 2], true);
        $this->field('number', FieldType::Integer, ['min_value' => PHP_INT_MAX - 1, 'max_value' => PHP_INT_MAX]);
        $id = $this->items->addItem($this->form('valid', ['text' => ['abc'], 'number' => (string) PHP_INT_MAX]));
        foreach ([['text' => ['no']], ['text' => ['abcdef']], ['text' => ['abc', 'def', 'ghi']], ['number' => (string) (PHP_INT_MAX - 2)]] as $invalid) {
            try {
                $this->items->updateItem($id, $this->form('invalid', $invalid));
                self::fail('Shared field limits must reject invalid values.');
            } catch (CollectionItemFormException $exception) {
                self::assertArrayHasKey('values.' . array_key_first($invalid), $exception->errors);
                self::assertSame('valid', $this->items->getItem($id)->code);
                self::assertSame(['abc'], $this->items->getItem($id)->values['text']);
            }
        }
    }

    public function testInvalidSettingsAndDefaultsNeverChangeTheSchema(): void
    {
        foreach ([['min_length' => 8, 'max_length' => 2], ['default' => 'oops'], ['max_values' => 0], ['min_value' => '2'], ['default' => ['1']]] as $settings) {
            try {
                $this->field('number', FieldType::Integer, $settings);
                self::fail('Invalid defaults or limits must not be persisted.');
            } catch (CollectionSchemaException) {
                self::assertSame(0, Capsule::table('collection_fields')->where('collection_id', $this->collection)->count());
            }
        }
        $this->field('text', FieldType::String_, ['default' => 'keep']);
        $field = $this->schema->getFields($this->collection)[0];
        try {
            $this->schema->saveField($field->id, new CollectionFieldFormDTO(collectionId: $this->collection, code: 'text', name: 'Text', type: FieldType::String_, required: false, multiple: false, sort: 100, settings: ['max_length' => 1]));
            self::fail('An existing default must remain valid when limits change.');
        } catch (CollectionSchemaException) {
            self::assertSame('keep', $this->schema->getFields($this->collection)[0]->settings['default']);
        }
    }

    public function testPopulatedTypeOrMultiplicityCannotBeChangedByOrdinarySchemaWriters(): void
    {
        $field = $this->field('value', FieldType::String_);
        $id = $this->items->addItem($this->form('entry', ['value' => '12']));
        foreach ([[FieldType::Integer, false], [FieldType::String_, true]] as [$type, $multiple]) {
            try {
                $this->schema->saveField($field, new CollectionFieldFormDTO($this->collection, 'value', 'Value', $type, false, $multiple, 100));
                self::fail('A filled field needs an explicit trusted conversion.');
            } catch (CollectionSchemaException) {
                self::assertSame('12', $this->items->getItem($id)->values['value']);
            }
        }
        $empty = $this->field('unused', FieldType::String_);
        $this->schema->saveField($empty, new CollectionFieldFormDTO($this->collection, 'unused', 'Unused', FieldType::Integer, false, true, 100));
        self::assertSame(FieldType::Integer, $this->schema->getFields($this->collection)[1]->type);
    }

    public function testAdminSettingsFormPersistsTypedDefaultsAndEscapesHints(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'code' => 'headline', 'name' => 'Headline', 'type' => 'string', 'sort' => '100', 'hint' => '<b>Raw hint</b>',
            'use_default' => '1', 'default_value' => 'Initial', 'min_length' => '3', 'max_length' => '20',
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        $field = $this->schema->getFields($this->collection)[0];
        self::assertSame(3, $field->settings['min_length']);
        self::assertSame('Initial', $field->settings['default']);
        $html = (string) $this->handleRequest('/admin/collections/' . $this->collection . '/items/new', cookies: $this->actingAs($editor))->getContent();
        self::assertStringContainsString('&lt;b&gt;Raw hint&lt;/b&gt;', $html);
        self::assertMatchesRegularExpression('/name="field_headline"[^>]*value="Initial"/', $html);
        $bad = $this->postForm('/admin/collections/' . $this->collection . '/items', ['name' => 'Rejected', 'active' => '1', 'sort' => '100', 'field_headline' => 'ab'], $editor);
        self::assertSame(200, $bad->getStatusCode());
        self::assertSame(0, Capsule::table('collection_items')->where('collection_id', $this->collection)->count());
    }

    public function testMultilineDefaultsRenderAsIndependentValuesAndSubmitWithoutSplitting(): void
    {
        $defaults = ["First\nsecond line", "Third\nfourth line"];
        $this->field('paragraphs', FieldType::Text, ['default' => $defaults], true);
        $editor = FunctionalUserFactory::createSupervisor();
        $html = (string) $this->handleRequest('/admin/collections/' . $this->collection . '/items/new', cookies: $this->actingAs($editor))->getContent();
        self::assertSame(3, HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('[data-key="field_paragraphs"] [data-repeat-rows] textarea')->length);
        self::assertStringContainsString("First\nsecond line</textarea>", $html);
        $response = $this->postForm('/admin/collections/' . $this->collection . '/items', [
            'name' => 'Paragraphs', 'sort' => '100', 'active' => '1', 'field_paragraphs' => [...$defaults, ''],
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($defaults, $this->items->getItemByCode($this->collection, null, 'paragraphs')->values['paragraphs']);
    }

    /** @param array<string, mixed> $settings */
    private function field(string $code, FieldType $type, array $settings = [], bool $multiple = false): int
    {
        return $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->collection, code: $code, name: ucfirst($code), type: $type, required: false, multiple: $multiple, sort: 100, settings: $settings));
    }

    /** @param array<string, string|list<string>> $values */
    private function form(string $code, array $values = []): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, $code, $code, true, null, null, 100, null, null, $values);
    }
}
