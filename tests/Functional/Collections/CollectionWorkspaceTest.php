<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceSettings;
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

    /** @return array<string, mixed> */
    private function settings(int $id): array
    {
        return $this->container()->get(ContentCollectionRepositoryInterface::class)->findById($id)->settings ?? [];
    }

    /** @param array<string, mixed>|null $workspace */
    private function form(string $code, ?array $workspace = null): CollectionFormDTO
    {
        return new CollectionFormDTO($code, 'Collection', null, true, false, 100, true, 20, workspace: $workspace);
    }
}
