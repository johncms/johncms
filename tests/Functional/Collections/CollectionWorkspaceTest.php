<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use DateTimeImmutable;
use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceSettings;
use Johncms\Modules\Collections\Application\Services\ItemListFilterMapper;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionWorkspaceTest extends FunctionalTestCase
{
    public function testCustomLabelsAreLiteralAndEscapedAndSurviveLegacySettingsWrites(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'offers', 'name' => 'Offers']);
        $writer = $this->container()->get(SaveCollectionUseCase::class);
        $writer->execute($collection, $this->form('offers', ['labels' => ['singular' => 'Offer', 'plural' => 'Our offers', 'create' => '<b>Add offer</b>', 'new' => 'New offer', 'edit' => 'Change offer']]));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection . '/items';
        $html = (string) $this->handleRequest($base, cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $button = $document->querySelector('a[href*="/items/new?"]');
        self::assertStringContainsString('<b>Add offer</b>', $button->textContent);
        self::assertNull($button->querySelector('b'));
        $writer->execute($collection, $this->form('offers'));
        self::assertSame('Our offers', $this->settings($collection)['workspace']['labels']['plural']);
        $writer->execute($collection, $this->form('offers', []));
        self::assertSame([], $this->settings($collection)['workspace']);
    }

    public function testWorkspaceRejectsUnknownKeysAndUntrustedTranslationDescriptors(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'validated', 'name' => 'Validated']);
        foreach ([['labels' => ['unknown' => 'Bad']], ['labels' => ['new' => str_repeat('x', 121)]], ['labels' => ['new' => ['domain' => 'news', 'message' => 'News']]], ['_workspace' => []], ['labels' => null]] as $workspace) {
            try {
                $this->container()->get(SaveCollectionUseCase::class)->execute($collection, $this->form('validated', $workspace));
                self::fail('Workspace settings must follow the public schema.');
            } catch (CollectionSchemaException) {
                self::assertArrayNotHasKey('workspace', $this->settings($collection));
            }
        }
        $settings = $this->container()->get(CollectionWorkspaceSettings::class);
        $first = ['labels' => ['new' => ['domain' => 'news', 'message' => '01']]];
        $second = ['labels' => ['new' => ['message' => '01', 'domain' => 'news']]];
        $settings->validate($second, true);
        self::assertTrue($settings->same($first, $second));
        $second['labels']['new']['message'] = '1';
        self::assertFalse($settings->same($first, $second));
    }

    public function testNewsOwnsTranslatedDefaultsAndKeepsUserLabelOverrides(): void
    {
        $sync = $this->container()->get(CollectionDeclarationsSynchronizer::class);
        $sync->synchronize();
        $collection = $this->container()->get(ContentCollectionRepositoryInterface::class)->findByDeclaration('johncms/news', 'articles');
        $labels = $this->container()->get(CollectionWorkspaceSettings::class);
        self::assertSame(d__('news', 'News item'), $labels->labels($collection)['singular']);
        $defaults = $collection->settings['_workspace'];
        $this->container()->get(SaveCollectionUseCase::class)->execute($collection->id, $this->form('news', ['labels' => ['create' => 'My new post']]));
        $sync->synchronize();
        $current = $this->container()->get(ContentCollectionRepositoryInterface::class)->findById($collection->id);
        self::assertTrue($labels->same($defaults, $current->settings['_workspace']));
        self::assertSame('My new post', $labels->labels($current)['create']);
        self::assertSame(d__('news', 'Edit news'), $labels->labels($current)['edit']);
        self::assertSame([], $sync->problems());
    }

    public function testContinueEditingAndCancelKeepTheOriginalListFiltersAndRejectExternalReturns(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'edit-flow', 'name' => 'Edit flow']);
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection . '/items';
        $back = $base . '?search=offer&status=draft';
        $response = $this->postForm($base, ['name' => 'Offer', 'sort' => '100', 'continue' => '1', 'back' => $back], $user);
        self::assertSame(302, $response->getStatusCode());
        $id = Capsule::table('collection_items')->where('collection_id', $collection)->value('id');
        self::assertSame($base . '/' . $id . '/edit?back=' . rawurlencode($back), $response->headers->get('Location'));
        $html = (string) $this->handleRequest($response->headers->get('Location'), cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertSame($back, $document->querySelector('input[name="back"]')->getAttribute('value'));
        self::assertNotNull($document->querySelector('button[name="continue"]'));
        self::assertNotNull($document->querySelector('a[href$="/preview"]'));
        $response = $this->postForm($base, ['id' => $id, 'name' => 'Offer edited', 'sort' => '100', 'back' => $back], $user);
        self::assertSame($back, $response->headers->get('Location'));
        foreach (['https://example.test/', '//example.test/', $back . "\r\nX-Header: forged"] as $external) {
            $response = $this->postForm($base, ['id' => $id, 'name' => 'Safe', 'sort' => '100', 'back' => $external], $user);
            self::assertSame($base, $response->headers->get('Location'));
        }
    }

    public function testSettingsFormResetsOverridesAndRejectsMalformedOrTruncatedLabels(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'label-form', 'name' => 'Labels']);
        $user = FunctionalUserFactory::createSupervisor();
        $form = ['id' => $collection, 'code' => 'label-form', 'name' => 'Labels', 'sort' => '100', 'per_page' => '20', 'workspace_labels' => ['new' => 'Add offer']];
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        self::assertSame('Add offer', $this->settings($collection)['workspace']['labels']['new']);
        $bad = $form;
        $bad['workspace_labels']['new'] = ['domain' => 'news', 'message' => 'News'];
        self::assertSame(200, $this->postForm('/admin/collections', $bad, $user)->getStatusCode());
        self::assertSame('Add offer', $this->settings($collection)['workspace']['labels']['new']);
        $form['collection_form_started'] = '1';
        $form['workspace_labels']['new'] = 'Truncated';
        self::assertSame(200, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        self::assertSame('Add offer', $this->settings($collection)['workspace']['labels']['new']);
        $form['collection_form_complete'] = '1';
        $form['workspace_labels']['new'] = '';
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        self::assertSame([], $this->settings($collection)['workspace']['labels']);
    }

    public function testLayoutGroupsOwnerFieldsWithoutChangingTheirSchema(): void
    {
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $collection = $this->container()->get(ContentCollectionRepositoryInterface::class)->findByDeclaration('johncms/news', 'articles');
        $before = Capsule::table('collection_fields')->where('collection_id', $collection->id)->orderBy('id')->get()->toJson();
        $writer = $this->container()->get(SaveCollectionUseCase::class);
        $writer->execute($collection->id, $this->form('news', ['layout' => ['fields' => [
            'keywords' => ['group' => 'First group', 'sort' => -100],
            'tags' => ['group' => 'Last group', 'sort' => 900],
        ]]]));
        $user = FunctionalUserFactory::createSupervisor();
        $html = (string) $this->handleRequest('/admin/collections/' . $collection->id . '/items/new', cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $inputs = $document->querySelectorAll('[name^="field_"]');
        self::assertSame('field_keywords', $inputs->item(0)->getAttribute('name'));
        self::assertStringContainsString('First group', $html);
        self::assertStringContainsString('Last group', $html);
        self::assertSame($before, Capsule::table('collection_fields')->where('collection_id', $collection->id)->orderBy('id')->get()->toJson());
    }

    public function testPublicationDatesKeepSecondsWhenTheVisibleFormIsSaved(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'seconds', 'name' => 'Seconds']);
        $item = Fixture::insert('collection_items', [
            'collection_id' => $collection,
            'name'          => 'Before',
            'code'          => 'seconds',
            'active'        => 1,
            'active_from'   => '2026-01-01 12:34:56',
            'active_to'     => '2027-01-01 12:34:57',
        ]);
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection . '/items';
        $html = (string) $this->handleRequest($base . '/' . $item . '/edit', cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $form = ['id' => $item, 'name' => 'After', 'code' => 'seconds', 'sort' => '100', 'active' => '1'];
        foreach (['active_from' => '2026-01-01T12:34:56', 'active_to' => '2027-01-01T12:34:57'] as $name => $value) {
            $input = $document->querySelector('input[name="' . $name . '"]');
            self::assertSame($value, $input->getAttribute('value'));
            self::assertSame('1', $input->getAttribute('step'));
            $form[$name] = $input->getAttribute('value');
        }
        self::assertSame(302, $this->postForm($base, $form, $user)->getStatusCode());
        $row = Capsule::table('collection_items')->where('id', $item)->first();
        self::assertSame('After', $row->name);
        self::assertSame('2026-01-01 12:34:56', $row->active_from);
        self::assertSame('2027-01-01 12:34:57', $row->active_to);
    }

    public function testHiddenBlocksPreserveStoredValuesAndCannotHideTheRequiredName(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'hidden', 'name' => 'Hidden']);
        $item = Fixture::insert('collection_items', [
            'collection_id' => $collection, 'name' => 'Before', 'code' => 'canonical', 'active' => 0,
            'preview_text' => '<p>Preview</p>', 'detail_text' => '<p>Detail</p>', 'sort' => 17,
            'active_from' => '2026-01-01 12:34:56', 'active_to' => '2027-01-01 12:34:57',
        ]);
        $workspace = ['layout' => ['hidden_blocks' => ['code', 'preview_text', 'detail_text', 'publication', 'place']]];
        $writer = $this->container()->get(SaveCollectionUseCase::class);
        $writer->execute($collection, $this->form('hidden', $workspace));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection . '/items';
        $html = (string) $this->handleRequest($base . '/' . $item . '/edit', cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('input[name="name"][required]'));
        foreach (['code', 'preview_text', 'detail_text', 'active', 'sort'] as $name) {
            self::assertNull($document->querySelector('[name="' . $name . '"]'));
        }
        self::assertSame(302, $this->postForm($base, ['id' => $item, 'name' => 'After', 'code' => 'forged', 'active' => 1, 'sort' => '99', 'detail_text' => 'forged'], $user)->getStatusCode());
        $row = Capsule::table('collection_items')->where('id', $item)->first();
        self::assertSame('After', $row->name);
        self::assertSame('canonical', $row->code);
        self::assertSame(0, $row->active);
        self::assertSame(17, $row->sort);
        self::assertSame('<p>Preview</p>', $row->preview_text);
        self::assertSame('<p>Detail</p>', $row->detail_text);
        self::assertSame('2026-01-01 12:34:56', $row->active_from);
        self::assertSame('2027-01-01 12:34:57', $row->active_to);
        $this->expectException(CollectionSchemaException::class);
        $writer->execute($collection, $this->form('hidden', ['layout' => ['hidden_blocks' => ['name']]]));
    }

    public function testNativeLayoutSettingsSaveGroupsAndPreserveThemOnOlderLabelOnlyPosts(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'layout-form', 'name' => 'Layout form']);
        Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'subtitle', 'name' => 'Subtitle', 'type' => 'string']);
        $user = FunctionalUserFactory::createSupervisor();
        $form = ['id' => $collection, 'code' => 'layout-form', 'name' => 'Layout form', 'sort' => '100', 'per_page' => '20',
            'workspace_present' => '1', 'layout_present' => '1', 'layout_fields' => ['subtitle' => ['group' => 'Extra <fields>', 'sort' => '-20']],
            'hidden_blocks' => ['preview_text'],
        ];
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        $layout = $this->settings($collection)['workspace']['layout'];
        self::assertSame(-20, $layout['fields']['subtitle']['sort']);
        unset($form['layout_present'], $form['layout_fields'], $form['hidden_blocks']);
        $form['workspace_labels'] = ['new' => 'New offer'];
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        self::assertSame($layout, $this->settings($collection)['workspace']['layout']);
        $html = (string) $this->handleRequest('/admin/collections/' . $collection . '/items/new', cookies: $this->actingAs($user))->getContent();
        self::assertStringContainsString('Extra &lt;fields&gt;', $html);
    }

    public function testLayoutRejectsInvalidFieldReferencesAndOrderingWithoutSaving(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'invalid-layout', 'name' => 'Layout']);
        foreach ([['fields' => ['missing' => ['sort' => 1]]], ['fields' => ['missing' => ['sort' => '1']]], ['fields' => null], ['hidden_blocks' => ['unknown']], ['hidden_blocks' => [null]], ['hidden_blocks' => null]] as $layout) {
            try {
                $this->container()->get(SaveCollectionUseCase::class)->execute($collection, $this->form('invalid-layout', ['layout' => $layout]));
                self::fail('Invalid layouts must be rejected.');
            } catch (CollectionSchemaException) {
                self::assertArrayNotHasKey('workspace', $this->settings($collection));
            }
        }
    }

    public function testConfiguredListColumnsSortingAndFiltersUseTheSamePageQuery(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'listing', 'name' => 'Listing']);
        $user = FunctionalUserFactory::createSupervisor();
        $other = FunctionalUserFactory::create();
        $writer = $this->container()->get(SaveCollectionUseCase::class);
        $writer->execute($collection, $this->form('listing', ['listing' => [
            'columns' => ['author', 'created_at', 'views'], 'filters' => ['author', 'created_at', 'views'],
            'sort' => ['field' => 'view_count', 'direction' => 'desc'],
        ]]));
        Fixture::insert('collection_items', ['collection_id' => $collection, 'name' => 'Few visits', 'code' => 'few', 'created_by' => $user->id, 'view_count' => 2, 'created_at' => '2026-01-02 12:00:00']);
        Fixture::insert('collection_items', ['collection_id' => $collection, 'name' => 'Many visits', 'code' => 'many', 'created_by' => $other->id, 'view_count' => 20, 'created_at' => '2026-01-03 12:00:00']);
        $base = '/admin/collections/' . $collection . '/items';
        $html = (string) $this->handleRequest($base, cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertSame('Many visits', trim($document->querySelector('.admin-collection-items tbody tr:first-child .admin-collection-items__name')->textContent));
        self::assertStringContainsString($other->name, $document->querySelector('tbody')->textContent);
        self::assertNotNull($document->querySelector('input[data-filter-field="created_from"]'));
        $query = '?author=' . $user->id . '&created_from=2026-01-02&created_to=2026-01-02&min_views=1';
        $html = (string) $this->handleRequest($base . $query, cookies: $this->actingAs($user), catch: false)->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertSame(1, $document->querySelectorAll('.admin-collection-items tbody tr')->length);
        self::assertSame('Few visits', trim($document->querySelector('tbody tr td a')->textContent));
        self::assertStringContainsString('author=', rawurldecode($document->querySelector('a[href*="/edit?"]')->getAttribute('href')));
        self::assertStringContainsString(d__('collections', 'Created from'), $html);
    }

    public function testExtraFiltersAreDropdownsWithNamesPeriodsAndThresholds(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'extra-filters', 'name' => 'Extra filters']);
        $user = FunctionalUserFactory::createSupervisor();
        $writer = $this->container()->get(SaveCollectionUseCase::class);
        $writer->execute($collection, $this->form('extra-filters', ['listing' => ['filters' => ['author', 'created_at', 'views']]]));
        Fixture::insert('collection_items', ['collection_id' => $collection, 'name' => 'Written', 'code' => 'written', 'created_by' => $user->id, 'view_count' => 150]);
        $base = '/admin/collections/' . $collection . '/items';
        $document = HTMLDocument::createFromString((string) $this->handleRequest($base, cookies: $this->actingAs($user))->getContent(), LIBXML_NOERROR);
        $filters = $document->querySelector('[data-live-list-region="filters"]');
        self::assertNull($document->querySelector('[data-live-list-region="options"]'), 'The extra filters live among the others.');
        self::assertStringContainsString($user->name, $filters->textContent);
        $today = (new DateTimeImmutable())->format('Y-m-d');
        self::assertNotNull($filters->querySelector('a[href*="created_from=' . $today . '"]'));
        self::assertNotNull($filters->querySelector('a[href*="min_views=100"]'));
        self::assertNotNull($filters->querySelector('input[data-filter-field="created_from"][type="date"]'));
        self::assertNull($filters->querySelector('form'), 'The menu sits inside the form of the live list and holds no form of its own.');

        $document = HTMLDocument::createFromString((string) $this->handleRequest($base . '?author=' . $user->id . '&min_views=100', cookies: $this->actingAs($user))->getContent(), LIBXML_NOERROR);
        $chips = $document->querySelector('.admin-chips')->textContent;
        self::assertStringContainsString($user->name, $chips, 'A chip names the author, not their ID.');
        self::assertStringContainsString('100', $chips);
        self::assertSame(1, $document->querySelectorAll('.admin-collection-items tbody tr')->length);
    }

    public function testListConfigurationAndQueryCannotInjectSortColumns(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'safe-list', 'name' => 'Safe list']);
        foreach ([['sort' => ['field' => 'name desc; DROP TABLE users', 'direction' => 'asc']], ['sort' => ['field' => 'id', 'direction' => 'anything']], ['columns' => ['password']], ['filters' => ['password']], ['columns' => null]] as $listing) {
            try {
                $this->container()->get(SaveCollectionUseCase::class)->execute($collection, $this->form('safe-list', ['listing' => $listing]));
                self::fail('Only supported list controls may be saved.');
            } catch (CollectionSchemaException) {
                self::assertArrayNotHasKey('workspace', $this->settings($collection));
            }
        }
        $mapper = $this->container()->get(ItemListFilterMapper::class);
        $filter = $mapper->fromQuery(['order' => 'invalid', 'direction' => 'invalid', 'author' => '1', 'created_from' => '2026-02-30'], ['filters' => ['created_at'], 'sort' => ['field' => 'id', 'direction' => 'desc']]);
        self::assertSame('id', $filter->sortBy);
        self::assertSame('desc', $filter->direction);
        self::assertNull($filter->author);
        self::assertNull($filter->createdFrom);
        $filter = $mapper->fromQuery(['order' => 'sort', 'direction' => 'asc'], ['sort' => ['field' => 'id', 'direction' => 'desc']]);
        self::assertSame('sort', $mapper->toQuery($filter)['order']);
        self::assertSame('sort', $mapper->fromQuery($mapper->toQuery($filter), ['sort' => ['field' => 'id', 'direction' => 'desc']])->sortBy);
    }

    /** @return array<string, mixed> */
    private function settings(int $id): array
    {
        return $this->container()->get(ContentCollectionRepositoryInterface::class)->findById($id)->settings ?? [];
    }

    /** @param array<string, mixed>|null $workspace */
    private function form(string $code, ?array $workspace = null): CollectionFormDTO
    {
        return new CollectionFormDTO(code: $code, name: 'Collection', description: null, active: true, public: false, sort: 100, hasSections: true, perPage: 20, workspace: $workspace);
    }
}
