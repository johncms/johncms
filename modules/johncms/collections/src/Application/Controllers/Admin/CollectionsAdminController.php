<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Gettext\TranslatorFunctions;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeReservedException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Application\Services\CollectionFormLayout;
use Johncms\Modules\Collections\Application\Services\CollectionListSettings;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceSettings;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\NavChain;

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
        private CollectionDeletionPlanner $deletions,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
        private PermissionRegistry $permissions,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private CollectionWorkspaceSettings $workspaceSettings,
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
        if ($request->hasBody('collection_form_started') && $request->body('collection_form_complete') !== '1') {
            $collection = $id !== null ? $this->repository->findWithCounts($id) : null;
            return $this->renderAgain(
                $id,
                $collection !== null ? $this->fieldsFromCollection($collection) : $this->defaultFields(),
                [d__('collections', 'The form was truncated. Nothing was saved. Reduce the number of values or ask the administrator to raise the form input limit.')],
            );
        }
        $fields = $this->fieldsFromRequest($request);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderAgain($id, $fields, $errors);
        }

        try {
            $savedId = $this->saveCollection->execute($id, $this->dtoFromFields($fields));
        } catch (CollectionSchemaException $exception) {
            return $this->renderAgain($id, $fields, [$exception->getMessage()]);
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
        return $this->renderDeleteConfirm($request, $id);
    }

    /** @param list<string> $errors */
    private function renderDeleteConfirm(Request $request, int $id, array $errors = []): ViewResponse
    {
        $collection = $this->repository->findWithCounts($id);
        if ($collection === null) {
            return $this->error(__('Wrong data'));
        }

        try {
            $preview = $this->deletions->plan($id)->preview;
        } catch (CollectionDeletionException) {
            return $this->error(__('Wrong data'));
        }
        $title = __('Delete collection');
        $dialog = [
            'title'       => $title,
            'message'     => __('The collection is deleted with all its items, sections and fields. This cannot be undone.'),
            'name'        => $collection->name,
            'facts'       => [
                ['label' => __('Items'), 'value' => $preview->items],
                ['label' => __('Sections'), 'value' => $preview->sections],
                ['label' => __('Fields'), 'value' => $preview->fields],
            ],
            'hidden' => ['revision' => $preview->revision],
            'relation_impact' => $preview->relations,
            'errors' => $errors,
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

    public function delete(Request $request, int $id): ViewResponse
    {
        try {
            $this->deleteCollection->execute($id, $request->body('revision', ''));
        } catch (CollectionDeletionException $exception) {
            return $this->renderDeleteConfirm($request, $id, array_merge(...array_values($exception->errors)));
        } catch (CollectionWriteConflictException) {
            return $this->renderDeleteConfirm($request, $id, [__('The contents changed during deletion. Check the counts and try again.')]);
        }
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
        $fields['workspace_labels'] = array_filter($fields['workspace_labels'], is_string(...));
        $fields['permission_options'] = $this->permissionOptions($fields['content_permission']);
        $fields['action_titles'] = EnsureCollectionContentAccessUseCase::actions();
        $fields['action_permissions'] = array_filter($fields['action_permissions'], is_string(...));
        foreach ($fields['action_permissions'] as $permission) {
            $fields['permission_options'][$permission] ??= $permission;
        }
        $fields['workspace_label_titles'] = $this->workspaceSettings->labelTitles();
        $fields['workspace_defaults'] = $this->workspaceSettings->defaults();
        $fields = $this->listingFields($fields, null);
        $fields['layout_rows'] = [];
        $fields['layout_blocks'] = (new CollectionFormLayout())->blocks();
        $fields['search_options'] = [];
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
        $fields = $this->listingFields($fields, $collection);
        $fields['managed'] = $collection->owner_module !== null;
        $fields['workspace_defaults'] = $this->workspaceSettings->defaults($collection);
        $layout = new CollectionFormLayout();
        $fields['layout_blocks'] = $layout->blocks();
        $fields['workspace_layout']['hidden_blocks'] ??= $layout->hidden($collection);
        $fields['layout_rows'] = [];
        $fields['search_options'] = [];
        foreach ($this->fieldRepository->getByCollection($collection->id) as $field) {
            $config = $fields['workspace_layout']['fields'][$field->code] ?? [];
            $default = $collection->settings['_workspace']['layout']['fields'][$field->code] ?? [];
            $group = $default['group'] ?? '';
            $fields['layout_rows'][] = [
                'code' => $field->code, 'name' => $field->name,
                'group' => is_string($config['group'] ?? '') ? ($config['group'] ?? '') : '',
                'sort' => is_int($config['sort'] ?? '') ? $config['sort'] : '',
                'default_group' => is_array($group) ? TranslatorFunctions::getTranslator()->dgettext($group['domain'], $group['message']) : $group,
                'default_sort' => $default['sort'] ?? $field->sort,
            ];
            if (in_array($field->type, [FieldType::String_, FieldType::Text, FieldType::Html], true)) {
                $fields['search_options'][$field->code] = $field->name;
            }
        }
        $fields['workspace_labels'] = array_filter($fields['workspace_labels'], is_string(...));
        $fields['permission_options'] = $this->permissionOptions($fields['content_permission']);
        $fields['action_titles'] = EnsureCollectionContentAccessUseCase::actions();
        $fields['action_permissions'] = array_filter($fields['action_permissions'], is_string(...));
        foreach ($fields['action_permissions'] as $permission) {
            $fields['permission_options'][$permission] ??= $permission;
        }
        $fields['workspace_label_titles'] = $this->workspaceSettings->labelTitles();
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

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function listingFields(array $fields, ?ContentCollection $collection): array
    {
        $listing = new CollectionListSettings();
        $fields['listing_columns'] = $listing->columns();
        $fields['listing_filters'] = $listing->filters();
        $fields['listing_sorts'] = $listing->sorts();
        $model = $collection !== null ? clone $collection : new ContentCollection();
        $settings = $model->settings ?? [];
        $settings['workspace']['listing'] = $fields['workspace_listing'];
        $model->settings = $settings;
        $fields['listing_values'] = $listing->settings($model);
        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private function permissionOptions(string $current): array
    {
        $options = [];
        foreach ($this->permissions->all() as $key => $permission) {
            $options[$key] = $permission->label;
        }
        $options[$current] ??= $current;
        return $options;
    }

    /** @return array<string, mixed> */
    private function fieldsFromRequest(Request $request): array
    {
        $existing = $request->bodyInt('id') ? $this->repository->findById($request->bodyInt('id')) : null;
        $layout = $existing?->settings['workspace']['layout'] ?? [];
        $listing = $existing?->settings['workspace']['listing'] ?? [];
        if ($request->hasBody('listing_present')) {
            $listing = [
                'columns' => $request->bodyList('list_columns'), 'filters' => $request->bodyList('list_filters'),
                'sort' => ['field' => $request->body('list_order'), 'direction' => $request->body('list_direction')],
            ];
        }
        if ($request->hasBody('layout_present')) {
            $layout = ['fields' => [], 'hidden_blocks' => $request->bodyList('hidden_blocks')];
            foreach ($request->bodyList('layout_fields') as $code => $config) {
                if (! is_array($config)) {
                    $layout['fields'][$code] = $config;
                    continue;
                }
                foreach ($config as $key => $value) {
                    if (is_string($value) && trim($value) === '') {
                        continue;
                    }
                    $layout['fields'][$code][$key] = $key === 'sort' && is_string($value) && preg_match('/^-?[0-9]{1,7}$/D', $value) ? (int) $value : $value;
                }
            }
        }
        return [
            'slug_linked' => ! $request->hasBody('slug_binding_present') || $request->hasBody('slug_linked'),
            'code'         => trim($request->body('code', '')),
            'name'         => trim($request->body('name', '')),
            'description'  => trim($request->body('description', '')),
            'active'       => $request->hasBody('active') ? 1 : 0,
            'public'       => $request->hasBody('public') ? 1 : 0,
            'sort'         => $request->bodyInt('sort', 100),
            'has_sections' => $request->hasBody('has_sections') ? 1 : 0,
            'per_page'     => max(1, abs($request->bodyInt('per_page', 10))),
            'content_permission' => trim($request->body('content_permission', 'collections.manage')),
            'search_fields' => $request->hasBody('search_fields') ? $request->bodyList('search_fields') : [],
            'action_permissions_present' => $request->hasBody('action_permissions_present') || $request->hasBody('action_permissions'),
            'action_permissions' => array_filter($request->bodyList('action_permissions'), static fn ($value): bool => $value !== ''),
            'workspace_present' => $request->hasBody('workspace_present') || $request->hasBody('workspace_labels'),
            'workspace_layout' => $layout,
            'workspace_listing' => $listing,
            'workspace_labels' => array_filter($request->bodyList('workspace_labels'), static fn ($value): bool => ! is_string($value) || trim($value) !== ''),
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
            'content_permission' => $settings['_content_permission'] ?? 'collections.manage',
            'search_fields' => $settings['search_fields'] ?? [],
            'action_permissions_present' => true,
            'action_permissions' => $settings['content_actions'] ?? [],
            'workspace_present' => true,
            'workspace_labels' => $settings['workspace']['labels'] ?? [],
            'workspace_layout' => $settings['workspace']['layout'] ?? [],
            'workspace_listing' => $settings['workspace']['listing'] ?? [],
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
            'content_permission' => 'collections.manage',
            'search_fields' => [],
            'action_permissions_present' => true,
            'action_permissions' => [],
            'workspace_present' => true,
            'workspace_labels' => [],
            'workspace_layout' => [],
            'workspace_listing' => [],
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
            contentPermission: $fields['content_permission'],
            searchFields: $fields['search_fields'],
            actionPermissions: $fields['action_permissions_present'] ? $fields['action_permissions'] : null,
            workspace: $fields['workspace_present'] ? ['labels' => $fields['workspace_labels'], 'layout' => $fields['workspace_layout'], 'listing' => $fields['workspace_listing']] : null,
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

        if ($fields['code'] !== '' && ! preg_match('/^(?!\.{1,2}$)[\w.+-]+$/D', (string) $fields['code'])) {
            $errors[] = d__('collections', 'Use letters, digits, dot, plus, hyphen or underscore in the code.');
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
