<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\UseCases\GetPublicItemUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Updates\SystemOperationContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionSelectFieldTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionsApiInterface $api;
    private CollectionSchemaApiInterface $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'choices', 'name' => 'Choices', 'active' => true, 'public' => true]);
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->schema = $this->container()->get(CollectionSchemaApiInterface::class);
    }

    public function testRetiredChoicesCanOnlyBeKeptByTheirExistingItemAndHistoricalImports(): void
    {
        $field = $this->field($this->options());
        $id = $this->api->addItem($this->form('existing', 'a'));
        $options = $this->options();
        $options[0]['active'] = false;
        $this->field($options, id: $field);
        $this->api->updateItem($id, $this->form('existing', 'a'));
        $this->api->patchItem($id, new CollectionItemPatchDTO(attributes: ['name' => 'Edited']));
        self::assertSame('a', $this->api->getItem($id)->values['choice']);
        try {
            $this->api->addItem($this->form('forged', 'a'));
            self::fail('A retired choice cannot be selected on another item.');
        } catch (CollectionItemFormException $exception) {
            self::assertArrayHasKey('values.choice', $exception->errors);
        }
        $imported = $this->container()->get(SystemOperationContext::class)->runTrusted(fn () => $this->container()->get(CollectionsImportApiInterface::class)->importItem($this->form('history', 'a'), new CollectionItemHistoryDTO(null, null)));
        self::assertSame('a', $this->api->getItem($imported)->values['choice']);
        $this->api->updateItem($id, $this->form('existing', 'b'));
        $this->expectException(CollectionItemFormException::class);
        $this->api->updateItem($id, $this->form('existing', 'a'));
    }

    public function testUsedCodesCannotBeRemovedOrRenamedAndLabelsCanChange(): void
    {
        $field = $this->field($this->options());
        $id = $this->api->addItem($this->form('entry', 'a'));
        foreach ([[['code' => 'b', 'label' => 'B']], [['code' => 'A', 'label' => 'Changed case']]] as $options) {
            try {
                $this->field($options, id: $field);
                self::fail('Referenced codes must stay stable, including their letter case.');
            } catch (CollectionSchemaException) {
                self::assertSame('a', $this->api->getItem($id)->values['choice']);
            }
        }
        $this->field([['code' => 'a', 'label' => '<b>New label</b>']], id: $field);
        self::assertSame('a', $this->api->getItem($id)->values['choice']);
        $view = $this->container()->get(GetPublicItemUseCase::class)->format($this->api->getItem($id));
        self::assertSame(['<b>New label</b>'], $view->values[0]['values']);
        self::assertSame(1, count($this->schema->getFields($this->collection)[0]->settings['options']));
    }

    public function testMultipleChoicesHaveExactCodesAndStableUniqueOrder(): void
    {
        $this->field([['code' => '01', 'label' => 'Zero one'], ['code' => '1', 'label' => 'One']], true);
        $id = $this->api->addItem($this->form('multiple', ['1', '01', '1']));
        self::assertSame(['1', '01'], $this->api->getItem($id)->values['choice']);
        $html = (string) $this->handleRequest($this->itemsUrl() . '/' . $id . '/edit', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $selected = [];
        foreach ($document->querySelectorAll('[data-key="field_choice"] [data-repeat-rows] select option[selected]') as $option) {
            $selected[] = $option->getAttribute('value');
        }
        self::assertSame(['1', '01', ''], $selected);
        $this->expectException(CollectionItemFormException::class);
        $this->api->addItem($this->form('unknown', ['outside']));
    }

    public function testOptionsAndDefaultsAreValidatedTogether(): void
    {
        foreach ([[['code' => 'a', 'label' => 'A'], ['code' => 'A', 'label' => 'Duplicate']], [['code' => 'bad code', 'label' => 'Bad']], [['code' => 'a', 'label' => 'A', 'active' => null]]] as $options) {
            try {
                $this->field($options);
                self::fail('Invalid or ambiguous option definitions must be rejected.');
            } catch (CollectionSchemaException) {
                self::assertSame(0, Capsule::table('collection_fields')->where('collection_id', $this->collection)->count());
            }
        }
        $this->expectException(CollectionSchemaException::class);
        $this->schema->saveField(null, new CollectionFieldFormDTO($this->collection, 'choice', 'Choice', FieldType::Select, false, false, 100, settings: ['options' => [['code' => 'a', 'label' => 'A', 'active' => false]], 'default' => 'a']));
    }

    public function testAdminOptionEditingAndItemSelectionUseLabelsAndAvailability(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', [
            'code' => 'choice', 'name' => 'Choice', 'type' => 'select', 'sort' => '100', 'option_code' => ['a', 'b', ''],
            'option_label' => ['<b>A label</b>', 'Retired', ''], 'option_active' => ['1', '0', '1'],
        ], $user);
        self::assertSame(302, $response->getStatusCode());
        $html = (string) $this->handleRequest($this->itemsUrl() . '/new', cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('select[name="field_choice"] option[value="b"][disabled]'));
        self::assertStringContainsString('&lt;b&gt;A label&lt;/b&gt;', $html);
        self::assertSame(302, $this->postForm($this->itemsUrl(), ['name' => 'Selected', 'sort' => '100', 'field_choice' => 'a'], $user)->getStatusCode());
        $bad = $this->postForm($this->itemsUrl(), ['name' => 'Forged', 'sort' => '100', 'field_choice' => 'b'], $user);
        self::assertSame(200, $bad->getStatusCode());
        self::assertSame(1, Capsule::table('collection_items')->count());
    }

    public function testLegacyUnknownChoiceIsKeptAndReportedButCannotBeSelectedElsewhere(): void
    {
        $field = $this->field($this->options());
        $id = $this->api->addItem($this->form('legacy', 'a'));
        Capsule::table('collection_item_values')->where('item_id', $id)->where('field_id', $field)->update(['value_string' => 'legacy-code']);
        $this->api->updateItem($id, $this->form('legacy', 'legacy-code'));
        self::assertSame('legacy-code', $this->api->getItem($id)->values['choice']);
        $html = (string) $this->handleRequest($this->itemsUrl() . '/' . $id . '/edit', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();
        self::assertNotNull(HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('option[value="legacy-code"][selected]'));
        $this->expectException(CollectionItemFormException::class);
        $this->api->addItem($this->form('new', 'legacy-code'));
    }

    public function testTruncatedNativeFormsCannotPartiallyOverwriteOptionsOrItems(): void
    {
        $field = $this->field($this->options());
        $item = $this->api->addItem($this->form('entry', 'a'));
        $user = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm($this->itemsUrl(), ['collection_form_started' => '1', 'id' => $item, 'name' => 'Truncated'], $user);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('entry', $this->api->getItem($item)->name);
        self::assertSame('a', $this->api->getItem($item)->values['choice']);
        $response = $this->postForm('/admin/collections/' . $this->collection . '/fields', ['collection_form_started' => '1', 'id' => $field, 'code' => 'choice', 'name' => 'Truncated', 'type' => 'select'], $user);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($this->options(), $this->schema->getFields($this->collection)[0]->settings['options']);
        $html = (string) $this->handleRequest($this->itemsUrl() . '/' . $item . '/edit', cookies: $this->actingAs($user))->getContent();
        self::assertGreaterThan(strrpos($html, 'name="sort"'), strrpos($html, 'name="collection_form_complete"'));
    }

    /** @param list<array<string, mixed>> $options */
    private function field(array $options, bool $multiple = false, ?int $id = null): int
    {
        return $this->schema->saveField($id, new CollectionFieldFormDTO($this->collection, 'choice', 'Choice', FieldType::Select, false, $multiple, 100, inList: true, settings: ['options' => $options]));
    }

    /** @return list<array{code: string, label: string, active: bool}> */
    private function options(): array
    {
        return [['code' => 'a', 'label' => 'A', 'active' => true], ['code' => 'b', 'label' => 'B', 'active' => true]];
    }

    /** @param string|list<string> $choice */
    private function form(string $code, string|array $choice): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, $code, $code, true, null, null, 100, null, null, ['choice' => $choice]);
    }

    private function itemsUrl(): string
    {
        return '/admin/collections/' . $this->collection . '/items';
    }
}
