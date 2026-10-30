<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeReservedException;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class CollectionsAdminController
{
    private const URL = '/admin/collections';

    public function __construct(
        private NavChain $navChain,
        private CollectionWorkspaceMapper $workspace,
        private ContentCollectionRepositoryInterface $repository,
        private ListCollectionsUseCase $listCollections,
        private SaveCollectionUseCase $saveCollection,
        private DeleteCollectionUseCase $deleteCollection,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->listCollections->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $collections = $this->listCollections->getPage($pagination->getPerPage(), $pagination->getOffset());

        $title = __('Collections');
        $this->navChain->add($title, self::URL);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        return new ViewResponse('@collections/admin/collections.twig', $this->menu($meta->title, $title) + [
            'items'           => array_map($this->mapRow(...), $collections),
            'total'           => $pagination->getTotal(),
            'per_page'        => $pagination->getPerPage(),
            'add_url'         => self::URL . '/new',
            'pagination'      => $pagination->render(),
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(): ViewResponse
    {
        return $this->renderForm(null, $this->defaultFields());
    }

    public function settings(int $id): ViewResponse
    {
        $collection = $this->repository->findWithCounts($id);
        if ($collection === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderSettings($collection, $this->fieldsFromCollection($collection));
    }

    public function store(Request $request): ViewResponse
    {
        $id = $request->bodyInt('id') ?: null;
        $fields = $this->fieldsFromRequest($request);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderAgain($id, $fields, $errors);
        }

        try {
            $savedId = $this->saveCollection->execute($id, $this->dtoFromFields($fields));
        } catch (CollectionCodeAlreadyExistsException) {
            return $this->renderAgain($id, $fields, [__('A collection with this code already exists')]);
        } catch (CollectionCodeReservedException) {
            return $this->renderAgain($id, $fields, [__('This code is reserved and cannot be used')]);
        }

        if ($id !== null) {
            $this->session->flash('success_message', __('Changes saved'));
            redirect(CollectionWorkspaceMapper::url($savedId, CollectionWorkspaceMapper::SETTINGS));
        }

        // A new collection has nothing to hold yet: what its items carry comes first.
        $this->session->flash('success_message', __('The collection is created. Add the fields its items carry.'));
        redirect(CollectionWorkspaceMapper::url($savedId, CollectionWorkspaceMapper::FIELDS));
    }

    public function deleteConfirm(Request $request, int $id): ViewResponse
    {
        $collection = $this->repository->findWithCounts($id);
        if ($collection === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Delete collection');
        $dialog = [
            'title'       => $title,
            'message'     => __('The collection is deleted with all its items, sections and fields. This cannot be undone.'),
            'name'        => $collection->name,
            'facts'       => [
                ['label' => __('Items'), 'value' => (int) $collection->getAttribute('items_count')],
                ['label' => __('Sections'), 'value' => (int) $collection->getAttribute('sections_count')],
                ['label' => __('Fields'), 'value' => (int) $collection->getAttribute('fields_count')],
            ],
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => CollectionWorkspaceMapper::url($id, CollectionWorkspaceMapper::SETTINGS),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/delete-dialog.twig', $dialog);
        }

        $this->navChain->add(__('Collections'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($title, $title) + [
            'dialog' => $dialog,
        ]);
    }

    public function delete(int $id): ViewResponse
    {
        $this->deleteCollection->execute($id);
        $this->session->flash('success_message', __('Deleted successfully'));

        redirect(self::URL);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(CollectionListItemDTO $item): array
    {
        $url = static fn (string $tab): string => CollectionWorkspaceMapper::url($item->id, $tab);
        $actions = [
            $this->action($url(CollectionWorkspaceMapper::ITEMS), __('Items'), 'file-text'),
        ];
        if ($item->hasSections) {
            $actions[] = $this->action($url(CollectionWorkspaceMapper::SECTIONS), __('Sections'), 'folder');
        }
        $actions[] = $this->action($url(CollectionWorkspaceMapper::FIELDS), __('Fields'), 'list');
        $actions[] = $this->action($url(CollectionWorkspaceMapper::SETTINGS), __('Settings'), 'settings');
        if ($item->publicUrl !== '') {
            $actions[] = $this->action($item->publicUrl, __('Open on the site'), 'external-link');
        }
        $actions[] = $this->action(self::URL . '/' . $item->id . '/delete', __('Delete'), 'trash-2', danger: true, dialog: true);

        return [
            'code'         => $item->code,
            'name'         => $item->name,
            'active'       => $item->active,
            'public'       => $item->public,
            'has_sections' => $item->hasSections,
            'items'        => $item->itemCount,
            'sections'     => $item->sectionCount,
            'fields'       => $item->fieldCount,
            'url'          => $url(CollectionWorkspaceMapper::ITEMS),
            'actions'      => $actions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function action(string $url, string $title, string $icon, bool $danger = false, bool $dialog = false): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => $dialog, 'fields' => []];
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(?int $id, array $fields, array $errors = []): ViewResponse
    {
        $title = __('New collection');
        $this->navChain->add(__('Collections'), self::URL);
        $this->navChain->add($title);
        return new ViewResponse('@collections/admin/collection-form.twig', $this->menu($title, $title) + [
            'form_action' => self::URL,
            'id'          => $id,
            'fields'      => $fields,
            'errors'      => $errors,
            'cancel_url'  => self::URL,
        ]);
    }

    /**
     * The settings of an existing collection: its form as a tab of the collection.
     *
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderSettings(ContentCollection $collection, array $fields, array $errors = []): ViewResponse
    {
        $this->navChain->add(__('Collections'), self::URL);
        $this->navChain->add($collection->name, CollectionWorkspaceMapper::url($collection->id));

        return new ViewResponse('@collections/admin/collection/settings.twig', [
            'title'           => __('Settings') . ' — ' . $collection->name,
            'page_title'      => $collection->name,
            'module_menu'     => ['collections' => true],
            'collection'      => $this->workspace->head($collection, CollectionWorkspaceMapper::SETTINGS),
            'form_action'     => self::URL,
            'id'              => $collection->id,
            'fields'          => $fields,
            'errors'          => $errors,
            'cancel_url'      => CollectionWorkspaceMapper::url($collection->id),
            'delete_url'      => self::URL . '/' . $collection->id . '/delete',
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderAgain(?int $id, array $fields, array $errors): ViewResponse
    {
        $collection = $id !== null ? $this->repository->findWithCounts($id) : null;

        return $collection !== null
            ? $this->renderSettings($collection, $fields, $errors)
            : $this->renderForm(null, $fields, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        return [
            'code'         => trim($request->body('code', '')),
            'name'         => trim($request->body('name', '')),
            'description'  => trim($request->body('description', '')),
            'active'       => $request->hasBody('active') ? 1 : 0,
            'public'       => $request->hasBody('public') ? 1 : 0,
            'sort'         => $request->bodyInt('sort', 100),
            'has_sections' => $request->hasBody('has_sections') ? 1 : 0,
            'per_page'     => max(1, abs($request->bodyInt('per_page', 10))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromCollection(ContentCollection $collection): array
    {
        $settings = $collection->settings ?? [];

        return [
            'code'         => $collection->code,
            'name'         => $collection->name,
            'description'  => (string) $collection->description,
            'active'       => $collection->active ? 1 : 0,
            'public'       => $collection->public ? 1 : 0,
            'sort'         => $collection->sort,
            'has_sections' => ! empty($settings['has_sections']) ? 1 : 0,
            'per_page'     => (int) ($settings['per_page'] ?? 10),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(): array
    {
        return [
            'code'         => '',
            'name'         => '',
            'description'  => '',
            'active'       => 1,
            'public'       => 1,
            'sort'         => 100,
            'has_sections' => 1,
            'per_page'     => 10,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(array $fields): CollectionFormDTO
    {
        return new CollectionFormDTO(
            code: (string) $fields['code'],
            name: (string) $fields['name'],
            description: $fields['description'] !== '' ? (string) $fields['description'] : null,
            active: (bool) $fields['active'],
            public: (bool) $fields['public'],
            sort: (int) $fields['sort'],
            hasSections: (bool) $fields['has_sections'],
            perPage: (int) $fields['per_page'],
        );
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<string>
     */
    private function validate(array $fields): array
    {
        $errors = [];
        if ($fields['code'] === '' || $fields['name'] === '') {
            $errors[] = __('The required fields are not filled');
        }

        if ($fields['code'] !== '' && ! preg_match('/^[a-z0-9_-]+$/', (string) $fields['code'])) {
            $errors[] = __('The code may contain only lowercase latin letters, digits, hyphen and underscore');
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title, string $pageTitle): array
    {
        return [
            'title'       => $title,
            'page_title'  => $pageTitle,
            'module_menu' => ['collections' => true],
        ];
    }

    private function error(string $message): ViewResponse
    {
        $title = __('Collections');
        return new ViewResponse('@admin/pages/result.twig', $this->menu($title, $title) + [
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::URL,
        ]);
    }
}
