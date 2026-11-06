<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\ModuleRegistry;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;
use Tests\Support\RecordingCollectionChangeHandler;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CollectionChangeHooksTest extends FunctionalTestCase
{
    public function testExternalHandlerReceivesWritesFromBothFormAndApi(): void
    {
        $handler = new RecordingCollectionChangeHandler();
        $container = $this->container();
        self::assertInstanceOf(ContainerBuilder::class, $container);
        $container->getDefinition(CollectionChangeDispatcher::class)->setSynthetic(true);
        $container->set(CollectionChangeDispatcher::class, new CollectionChangeDispatcher([$handler], $this->container()->get(ModuleRegistry::class), new CollectionTransaction()));
        $collection = Fixture::insert('collections', ['code' => 'hooks', 'name' => 'Hooks']);
        $user = FunctionalUserFactory::createSupervisor();
        $response = $this->postForm('/admin/collections/' . $collection . '/items', [
            'name' => 'From form', 'code' => 'from-form', 'active' => '1', 'sort' => '100',
        ], $user);
        self::assertSame(302, $response->getStatusCode());
        $this->container()->get(CollectionsApiInterface::class)->addItem(new CollectionItemFormDTO($collection, null, 'from-api', 'From API', true, null, null, 100, null, null, []));
        self::assertSame(['from-form', 'from-api'], array_map(static fn ($change) => $change->after->code, $handler->within));
        self::assertSame([], $handler->committed);
    }

    public function testUnavailableOwnerBlocksAllDeletionScopesButAllowsEditing(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'owned', 'name' => 'Owned', 'owner_module' => 'example/unavailable']);
        $section = Fixture::insert('collection_sections', ['collection_id' => $collection, 'code' => 'section', 'name' => 'Section']);
        $api = $this->container()->get(CollectionsApiInterface::class);
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $id = $api->addItem(new CollectionItemFormDTO($collection, $section, 'entry', 'Entry', true, null, null, 100, null, null, []));
        foreach ([fn () => $api->deleteItem($id), fn () => $sections->deleteSection($collection, $section, SectionDeletionMode::DeleteItems), fn () => $api->deleteCollection($collection)] as $delete) {
            try {
                $delete();
                self::fail('The unavailable owner must protect its related data.');
            } catch (CollectionDeletionException $exception) {
                self::assertArrayHasKey('owner', $exception->errors);
            }
        }
        $api->patchItem($id, new CollectionItemPatchDTO(attributes: ['name' => 'Edited']));
        self::assertSame('Edited', $api->getItem($id)->name);
        self::assertNotNull($sections->findSection($collection, $section));
    }
}
