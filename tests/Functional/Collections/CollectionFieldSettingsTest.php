<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use DateTimeImmutable;
use Johncms\Files\FileStore;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionFilePolicy;
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
        self::assertSame(2, HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('[data-key="field_paragraphs"] [data-repeat-rows] textarea')->length);
        self::assertStringContainsString("First\nsecond line</textarea>", $html);
        $response = $this->postForm('/admin/collections/' . $this->collection . '/items', [
            'name' => 'Paragraphs', 'sort' => '100', 'active' => '1', 'field_paragraphs' => [...$defaults, ''],
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($defaults, $this->items->getItemByCode($this->collection, null, 'paragraphs')->values['paragraphs']);
    }

    public function testAdminSavesOnlyApplicableLimitsForEveryFieldType(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        foreach (FieldType::cases() as $type) {
            $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
                'code' => $type->value,
                'name' => $type->value,
                'type' => $type->value,
                'min_length' => '2',
                'max_length' => '10',
                'min_value' => '-3',
                'max_value' => '3',
                'max_values' => 'invalid hidden limit',
                'target_collection_id' => (string) $this->collection,
            ], $editor);
            self::assertSame(302, $response->getStatusCode(), $type->value);
            $fields = $this->schema->getFields($this->collection);
            $field = $fields[array_key_last($fields)];
            $text = in_array($type, [FieldType::String_, FieldType::Text, FieldType::Html], true);
            $numeric = in_array($type, [FieldType::Integer, FieldType::Double], true);
            self::assertSame($text ? 2 : null, $field->settings['min_length']);
            self::assertSame($text ? 10 : null, $field->settings['max_length']);
            self::assertSame($numeric ? -3 : null, $field->settings['min_value']);
            self::assertSame($numeric ? 3 : null, $field->settings['max_value']);
            self::assertNull($field->settings['max_values']);
        }
    }

    public function testAdminClearsPreviousLimitsWhenTypeOrMultiplicityChanges(): void
    {
        $id = $this->field('value', FieldType::Integer, ['min_value' => 1, 'max_value' => 10, 'max_values' => 2], true);
        $editor = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'id' => (string) $id,
            'code' => 'value',
            'name' => 'Value',
            'type' => 'string',
            'min_length' => '3',
            'max_length' => '20',
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        $field = $this->schema->getFields($this->collection)[0];
        self::assertNull($field->settings['min_value']);
        self::assertNull($field->settings['max_value']);
        self::assertNull($field->settings['max_values']);
        self::assertSame(3, $field->settings['min_length']);
        self::assertSame(20, $field->settings['max_length']);
        self::assertFalse($field->multiple);
    }

    public function testAdminKeepsFractionalLimitsAndMultipleValueLimit(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'code' => 'value',
            'name' => 'Value',
            'type' => 'double',
            'multiple' => '1',
            'min_value' => '-1.5',
            'max_value' => '2.5',
            'max_values' => '2',
            'min_length' => 'invalid hidden limit',
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        $field = $this->schema->getFields($this->collection)[0];
        self::assertSame(-1.5, $field->settings['min_value']);
        self::assertSame(2.5, $field->settings['max_value']);
        self::assertSame(2, $field->settings['max_values']);
        self::assertNull($field->settings['min_length']);
    }

    public function testAdminPreservesExistingSelectLengthLimits(): void
    {
        $id = $this->field('choice', FieldType::Select, [
            'min_length' => 2,
            'max_length' => 10,
            'options' => [['code' => 'yes', 'label' => 'Yes', 'active' => true]],
            'default' => 'yes',
        ]);
        $editor = FunctionalUserFactory::createSupervisor();
        $html = (string) $this->handleRequest('/admin/collections/' . $this->collection . '/fields/' . $id . '/edit', cookies: $this->actingAs($editor))->getContent();
        self::assertStringContainsString('data-field-types="string text html select"', $html);
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'id' => (string) $id,
            'code' => 'choice',
            'name' => 'Choice',
            'type' => 'select',
            'min_length' => '2',
            'max_length' => '10',
            'option_code' => ['yes'],
            'option_label' => ['Yes'],
            'option_active' => ['1'],
            'use_default' => '1',
            'default_value' => 'yes',
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        $settings = $this->schema->getFields($this->collection)[0]->settings;
        self::assertSame(2, $settings['min_length']);
        self::assertSame(10, $settings['max_length']);
        self::assertSame('yes', $settings['default']);
    }

    public function testDefaultsPreserveZeroEmptyValuesAndDatetimeSeconds(): void
    {
        $this->field('zero', FieldType::Boolean, ['default' => '0']);
        $this->field('empty', FieldType::String_, ['default' => '']);
        $this->field('empty_list', FieldType::Text, ['default' => []], true);
        $this->field('moment', FieldType::Datetime, ['default' => '2026-11-06 12:34:56']);
        $id = $this->items->addItem($this->form('defaults'));
        $values = $this->items->getItem($id)->values;
        self::assertFalse($values['zero']);
        self::assertArrayNotHasKey('empty', $values);
        self::assertArrayNotHasKey('empty_list', $values);
        self::assertSame('2026-11-06 12:34:56', $values['moment']->format('Y-m-d H:i:s'));
        $fields = $this->schema->getFields($this->collection);
        self::assertSame('', $fields[1]->settings['default']);
        self::assertSame([], $fields[2]->settings['default']);
    }

    public function testFilledFieldFormLocksStructureAndStillSavesOtherSettings(): void
    {
        $fieldId = $this->field('value', FieldType::String_, [], true);
        $this->items->addItem($this->form('entry', ['value' => ['saved']]));
        $editor = FunctionalUserFactory::createSupervisor();
        $html = (string) $this->handleRequest('/admin/collections/' . $this->collection . '/fields/' . $fieldId . '/edit', cookies: $this->actingAs($editor))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertTrue($document->querySelector('#type')->hasAttribute('disabled'));
        self::assertTrue($document->querySelector('#multiple')->hasAttribute('disabled'));
        self::assertSame('string', $document->querySelector('input[type="hidden"][name="type"]')->getAttribute('value'));
        self::assertSame('1', $document->querySelector('input[type="hidden"][name="multiple"]')->getAttribute('value'));
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'id' => (string) $fieldId,
            'code' => 'value',
            'name' => 'Renamed',
            'type' => 'string',
            'multiple' => '1',
            'hint' => 'Changed hint',
        ], $editor);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('Changed hint', $this->schema->getFields($this->collection)[0]->settings['hint']);
    }

    public function testUnsavedRelationChoicesAreScoped(): void
    {
        $target = Fixture::insert('collections', ['code' => 'target', 'name' => 'Target']);
        $selected = Fixture::insert('collection_items', ['collection_id' => $target, 'name' => 'Selected']);
        Fixture::insert('collection_items', ['collection_id' => $this->collection, 'name' => 'Foreign']);
        $editor = FunctionalUserFactory::createSupervisor();
        $data = ['type' => 'relation', 'target_collection_id' => (string) $target, 'default_query' => 'no matches', 'default_value' => (string) $selected];
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields/choices', $data, $editor);
        self::assertSame(200, $response->getStatusCode());
        $options = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)['options'];
        self::assertSame([(string) $selected], array_column($options, 'code'));
        self::assertStringContainsString('Selected', $options[0]['label']);
        $member = FunctionalUserFactory::createWithPermissions([]);
        self::assertSame(403, $this->postForm('/admin/collections/' . $this->collection . '/fields/choices', $data, $member)->getStatusCode());
    }

    public function testAFileFieldHasNoDefault(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $data = ['code' => 'file', 'name' => 'File', 'type' => 'file', 'use_default' => '1', 'default_value' => '7', 'file_constraints_present' => '1', 'max_file_bytes' => '1024', 'allowed_mime_types' => ['text/plain']];
        self::assertSame(302, $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor)->getStatusCode());
        self::assertNull($this->schema->getFields($this->collection)[0]->settings['default']);
        try {
            $this->field('forced', FieldType::File, ['default' => '7']);
            self::fail('A declared default of a file field must be refused.');
        } catch (CollectionSchemaException $exception) {
            self::assertArrayHasKey('default', $exception->errors);
        }
    }

    public function testAFileSizeIsShownInTheLargestExactUnit(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $id = $this->field('file', FieldType::File, ['max_file_bytes' => 5242880, 'allowed_mime_types' => ['text/plain']]);
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/admin/collections/' . $this->collection . '/fields/' . $id . '/edit', cookies: $this->actingAs($editor))->getContent(), LIBXML_NOERROR);
        self::assertSame('5', $document->querySelector('[name="max_file_bytes"]')->getAttribute('value'));
        self::assertSame('1048576', $document->querySelector('[name="file_size_unit"] option[selected]')->getAttribute('value'));
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/admin/collections/' . $this->collection . '/fields/new', cookies: $this->actingAs($editor))->getContent(), LIBXML_NOERROR);
        self::assertSame('10', $document->querySelector('[name="max_file_bytes"]')->getAttribute('value'));
        self::assertSame(count(CollectionFilePolicy::TYPES), $document->querySelectorAll('[name="allowed_mime_types[]"]')->length);
    }

    public function testANewFieldGoesAfterTheLastOne(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->collection . '/fields/new';
        $document = HTMLDocument::createFromString((string) $this->handleRequest($url, cookies: $this->actingAs($editor))->getContent(), LIBXML_NOERROR);
        self::assertSame('100', $document->querySelector('[name="sort"]')->getAttribute('value'));
        $this->field('first', FieldType::String_);
        $document = HTMLDocument::createFromString((string) $this->handleRequest($url, cookies: $this->actingAs($editor))->getContent(), LIBXML_NOERROR);
        self::assertSame('110', $document->querySelector('[name="sort"]')->getAttribute('value'));
    }

    public function testRolesFitTheTypeOfTheField(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->collection . '/fields';
        $response = $this->postForm($url, ['code' => 'title', 'name' => 'Title', 'type' => 'boolean', 'role' => 'meta_title'], $editor);
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('#error-role'));
        self::assertSame(302, $this->postForm($url, ['code' => 'title', 'name' => 'Title', 'type' => 'string', 'role' => 'meta_title'], $editor)->getStatusCode());
        $response = $this->postForm($url, ['code' => 'other', 'name' => 'Other', 'type' => 'text', 'role' => 'meta_title'], $editor);
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('#error-role'));
    }

    public function testACustomRoleStaysAvailableInTheForm(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $id = $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->collection, code: 'own', name: 'Own', type: FieldType::Integer, required: false, multiple: false, sort: 100, role: 'module_role'));
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/admin/collections/' . $this->collection . '/fields/' . $id . '/edit', cookies: $this->actingAs($editor))->getContent(), LIBXML_NOERROR);
        self::assertSame('module_role', $document->querySelector('[name="role"] option[selected]')->getAttribute('value'));
    }

    public function testAMaximumLengthOfZeroIsRefused(): void
    {
        $this->expectException(CollectionSchemaException::class);
        $this->field('nothing', FieldType::String_, ['max_length' => 0]);
    }

    public function testADefaultDoesNotSurviveAChangeOfItsKind(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $data = ['code' => 'value', 'name' => 'Value', 'type' => 'string', 'default_type' => 'relation', 'use_default' => '1', 'default_value' => '36'];
        self::assertSame(302, $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor)->getStatusCode());
        self::assertNull($this->schema->getFields($this->collection)[0]->settings['default']);
        $data = ['code' => 'kept', 'name' => 'Kept', 'type' => 'text', 'default_type' => 'string', 'use_default' => '1', 'default_value' => 'Kept'];
        self::assertSame(302, $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor)->getStatusCode());
        self::assertSame('Kept', $this->schema->getFields($this->collection)[1]->settings['default']);
    }

    public function testFileSizeUnitsAreExactAndInvalidFractionsAreRejected(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $data = ['code' => 'file', 'name' => 'File', 'type' => 'file', 'file_constraints_present' => '1', 'max_file_bytes' => '1.5', 'file_size_unit' => '1048576', 'allowed_mime_types' => ['text/plain']];
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame(1572864, $this->schema->getFields($this->collection)[0]->settings['max_file_bytes']);
        $data['code'] = 'invalid';
        $data['max_file_bytes'] = '0.1';
        $data['file_size_unit'] = '1024';
        self::assertSame(200, $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor)->getStatusCode());
        self::assertCount(1, $this->schema->getFields($this->collection));
    }

    public function testFieldErrorsIdentifyDefaultRowsAndDuplicateOptionCodes(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->collection . '/fields';
        $response = $this->postForm($url, [
            'code' => 'text', 'name' => 'Text', 'type' => 'text', 'multiple' => '1',
            'min_length' => '3', 'use_default' => '1', 'default_values' => ['valid', 'no'],
        ], $editor);
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('#error-default_values-1'));
        self::assertStringContainsString('>no</textarea>', (string) $response->getContent());
        $response = $this->postForm($url, [
            'code' => 'choice', 'name' => 'Choice', 'type' => 'select',
            'option_code' => ['same', 'SAME'], 'option_label' => ['First', 'Second'], 'option_active' => ['1', '1'],
        ], $editor);
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('#error-option_code-1'));
        self::assertNotNull($document->querySelector('[data-field-error-summary]'));
        self::assertCount(0, $this->schema->getFields($this->collection));
    }

    public function testCurrentDatePresetsAreResolvedOnOpeningAndPostedValuesStayFixed(): void
    {
        $this->field('day', FieldType::Date, ['date_preset' => 'current']);
        $this->field('moment', FieldType::Datetime, ['date_preset' => 'current'], true);
        $editor = FunctionalUserFactory::createSupervisor();
        $offset = sprintf('%+d hours', (int) (config('johncms')['timeshift'] ?? 0));
        $before = (new DateTimeImmutable())->modify($offset);
        $response = $this->handleRequest('/admin/collections/' . $this->collection . '/items/new', cookies: $this->actingAs($editor));
        $after = (new DateTimeImmutable())->modify($offset);
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        $day = $document->querySelector('[name="field_day"]')->getAttribute('value');
        $moment = $document->querySelector('[name="field_moment[]"]')->getAttribute('value');
        self::assertContains($day, [$before->format('Y-m-d'), $after->format('Y-m-d')]);
        $shown = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $moment);
        self::assertNotFalse($shown);
        self::assertGreaterThanOrEqual($before->getTimestamp(), $shown->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $shown->getTimestamp());
        self::assertSame('1', $document->querySelector('[name="field_moment[]"]')->getAttribute('step'));
        $url = '/admin/collections/' . $this->collection . '/items';
        $data = ['name' => '', 'code' => 'frozen', 'sort' => '100', 'field_day' => '2001-02-03', 'field_moment' => ['2001-02-03T04:05:06']];
        $response = $this->postForm($url, $data, $editor);
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame('2001-02-03', $document->querySelector('[name="field_day"]')->getAttribute('value'));
        self::assertSame('2001-02-03T04:05:06', $document->querySelector('[name="field_moment[]"]')->getAttribute('value'));
        $data['name'] = 'Frozen';
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        $item = $this->items->getItemByCode($this->collection, null, 'frozen');
        self::assertSame('2001-02-03', $item->values['day']->format('Y-m-d'));
        self::assertSame('2001-02-03 04:05:06', $item->values['moment'][0]->format('Y-m-d H:i:s'));
        $response = $this->handleRequest($url . '/' . $item->id . '/edit', cookies: $this->actingAs($editor));
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame('2001-02-03', $document->querySelector('[name="field_day"]')->getAttribute('value'));
        self::assertSame('2001-02-03T04:05:06', $document->querySelector('[name="field_moment[]"]')->getAttribute('value'));
    }

    public function testAdminSwitchesDatePresetModesWithoutRetainingConflictingDefaults(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->collection . '/fields';
        $data = ['code' => 'day', 'name' => 'Day', 'type' => 'date', 'date_default_mode' => 'current', 'default_value' => '2001-02-03'];
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        $field = $this->schema->getFields($this->collection)[0];
        self::assertSame('current', $field->settings['date_preset']);
        self::assertNull($field->settings['default']);
        $data['id'] = (string) $field->id;
        $data['date_default_mode'] = 'fixed';
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        self::assertNull($this->schema->getFields($this->collection)[0]->settings['date_preset']);
        self::assertSame('2001-02-03', $this->schema->getFields($this->collection)[0]->settings['default']);
        $data['date_default_mode'] = 'none';
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        self::assertNull($this->schema->getFields($this->collection)[0]->settings['default']);
        $this->expectException(CollectionSchemaException::class);
        $this->field('invalid', FieldType::String_, ['date_preset' => 'current']);
    }

    public function testRelationDefaultNeedsNoToggleAndIsClearedByAnEmptyValue(): void
    {
        $target = Fixture::insert('collections', ['code' => 'relation-target', 'name' => 'Target']);
        $record = Fixture::insert('collection_items', ['collection_id' => $target, 'name' => '<b>Selected title</b>']);
        $editor = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->collection . '/fields';
        $data = ['code' => 'relation', 'name' => 'Relation', 'type' => 'relation', 'target_collection_id' => (string) $target, 'default_value' => (string) $record];
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        $field = $this->schema->getFields($this->collection)[0];
        self::assertSame((string) $record, $field->settings['default']);
        $data['id'] = (string) $field->id;
        $response = $this->handleRequest($url . '/' . $field->id . '/edit', cookies: $this->actingAs($editor));
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertTrue($document->querySelector('[data-default-toggle]')->hasAttribute('hidden'));
        self::assertTrue($document->querySelector('[data-default-single]')->hasAttribute('hidden'));
        $options = json_decode($document->querySelector('[data-default-lookup]')->getAttribute('data-options'), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('<b>Selected title</b>', $options[0]['label'], 'The picker gets the name of the chosen record as text.');
        $data['default_value'] = '';
        self::assertSame(302, $this->postForm($url, $data, $editor)->getStatusCode());
        self::assertNull($this->schema->getFields($this->collection)[0]->settings['default']);
    }

    public function testRepeatedRelationDefaultsAreStoredOnceInTheirOrder(): void
    {
        $first = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'name' => 'First']);
        $second = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'name' => 'Second']);
        $editor = FunctionalUserFactory::createSupervisor();
        $data = [
            'code' => 'relations', 'name' => 'Relations', 'type' => 'relation', 'multiple' => '1', 'target_collection_id' => (string) $this->collection,
            'default_values' => [(string) $second, '', (string) $first, (string) $second],
        ];
        self::assertSame(302, $this->postForm('/admin/collections/' . $this->collection . '/fields', $data, $editor)->getStatusCode());
        self::assertSame([(string) $second, (string) $first], $this->schema->getFields($this->collection)[0]->settings['default']);
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
