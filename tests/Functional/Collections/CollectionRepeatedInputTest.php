<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionRepeatedInputTest extends FunctionalTestCase
{
    private int $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'repeated', 'name' => 'Repeated']);
    }

    public function testReorderedValuesAreSavedWithTheRestOfTheItem(): void
    {
        $this->field(FieldType::Text);
        $api = $this->container()->get(CollectionsApiInterface::class);
        $id = $api->addItem(new CollectionItemFormDTO($this->collection, null, 'entry', 'Original', true, null, null, 100, null, null, ['notes' => ["First\nline", 'Second']]));
        $user = FunctionalUserFactory::createSupervisor();
        $data = ['id' => $id, 'name' => 'Edited', 'code' => 'entry', 'sort' => '123', 'detail_text' => 'Unsaved body', 'field_notes' => ["First\nline", 'Second', '']];
        $data['field_notes'] = ['Second', "First\nline", ''];
        self::assertSame(302, $this->postForm($this->itemsUrl(), $data, $user)->getStatusCode());
        self::assertSame(['Second', "First\nline"], $api->getItem($id)->values['notes']);
        self::assertSame('Edited', $api->getItem($id)->name);
    }

    public function testReorderedDefaultsAreSavedInTheirNewOrder(): void
    {
        $id = $this->field(FieldType::Text, ['default' => ["First\nline", 'Second']]);
        $user = FunctionalUserFactory::createSupervisor();
        $data = ['id' => $id, 'code' => 'notes', 'name' => 'Notes', 'type' => 'text', 'multiple' => '1', 'sort' => '100', 'use_default' => '1', 'default_values' => ["First\nline", 'Second', '']];
        $url = '/admin/collections/' . $this->collection . '/fields';
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $data['default_values'] = ['Second', "First\nline", ''];
        self::assertSame(302, $this->postForm($url, $data, $user)->getStatusCode());
        self::assertSame(['Second', "First\nline"], $schema->getFields($this->collection)[0]->settings['default']);
    }

    public function testValidationFailureKeepsExactRowsAndVisibleFieldErrors(): void
    {
        $this->field(FieldType::Text, ['min_length' => 3]);
        $response = $this->postForm($this->itemsUrl(), ['name' => 'Entry', 'sort' => '100', 'field_notes' => ['ab', 'valid']], FunctionalUserFactory::createSupervisor());
        $html = (string) $response->getContent();
        self::assertSame(['ab', 'valid'], $this->values($html, 'field_notes'));
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('[data-key="field_notes"][aria-invalid="true"] .invalid-feedback.d-block'));
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    public function testHtmlValuesKeepTheirMarkupAndContentAccessIsRequired(): void
    {
        $this->field(FieldType::Html);
        $data = ['name' => '', 'field_notes' => ["<p>First\nline</p>", '']];
        $response = $this->postForm($this->itemsUrl(), $data, FunctionalUserFactory::createSupervisor());
        self::assertSame(["<p>First\nline</p>", ''], $this->values((string) $response->getContent(), 'field_notes'));
        self::assertSame(403, $this->postForm($this->itemsUrl(), $data, FunctionalUserFactory::create())->getStatusCode());
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    /** @param array<string, mixed> $settings */
    private function field(FieldType $type, array $settings = []): int
    {
        return $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO(collectionId: $this->collection, code: 'notes', name: 'Notes', type: $type, required: false, multiple: true, sort: 100, settings: $settings));
    }

    private function itemsUrl(): string
    {
        return '/admin/collections/' . $this->collection . '/items';
    }

    /** @return list<string> */
    private function values(string $html, string $key): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $values = [];
        foreach ($document->querySelectorAll('[data-key="' . $key . '"] [data-repeat-rows] textarea, [data-key="' . $key . '"] [data-repeat-rows] select') as $input) {
            $values[] = $input->localName === 'select' ? ($input->querySelector('option[selected]')?->getAttribute('value') ?? '') : $input->textContent;
        }
        return $values;
    }
}
