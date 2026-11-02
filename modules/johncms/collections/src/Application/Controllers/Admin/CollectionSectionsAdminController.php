<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\SectionTreeItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class CollectionSectionsAdminController
{
    public function __construct(
        private NavChain $navChain,
        private CollectionWorkspaceMapper $workspace,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionSectionRepositoryInterface $sectionRepository,
        private ListCollectionSectionsUseCase $listSections,
        private SaveCollectionSectionUseCase $saveSection,
        private DeleteCollectionSectionUseCase $deleteSection,
        private Session $session,
        private CollectionDeletionPlanner $deletions,
    ) {
    }

    public function index(int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findWithCounts($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $this->breadcrumbs($collection);

        return new ViewResponse('@collections/admin/sections.twig', [
            'page_title' => $collection->name,
            'collection' => $this->workspace->head($collection, CollectionWorkspaceMapper::SECTIONS),
        ] + $this->menu(__('Sections'), $collection->name, $collection) + [
            'rows'            => $this->mapRows($collection_id, $this->listSections->getTree($collection_id)),
            'add_url'         => $this->baseUrl($collection_id) . '/new',
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $parent = $this->queryParent($request);
        if ($parent !== null && $this->resolveParent($collection_id, $parent) === null) {
            redirect($this->baseUrl($collection_id));
        }

        return $this->renderForm($collection, null, $this->defaultFields($parent));
    }

    public function editForm(int $collection_id, int $id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        if ($section === null) {
            return $this->wrongData($collection_id);
        }

        return $this->renderForm($collection, $id, $this->fieldsFromSection($section));
    }

    public function store(Request $request, int $collection_id): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $id = $request->bodyInt('id') ?: null;

        if ($id !== null && $this->findOwnedSection($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }
        $fields = $this->fieldsFromRequest($request, $this->postParent($request));
        try {
            $this->saveSection->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionSectionFormException $exception) {
            return $this->renderForm($collection, $id, $fields, array_merge(...array_values($exception->errors)));
        } catch (\JsonException) {
            return $this->renderForm($collection, $id, $fields, [d__('collections', 'Use a JSON object of at most 65535 bytes for section metadata.')]);
        } catch (CollectionSectionCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A section with this code already exists')]);
        }

        $this->session->flash('success_message', $id !== null ? __('Changes saved') : __('Created successfully'));
        redirect($this->baseUrl($collection_id));
    }

    public function deleteConfirm(Request $request, int $collection_id, int $id): ViewResponse
    {
        return $this->renderDeleteConfirm($request, $collection_id, $id);
    }

    /** @param list<string> $errors */
    private function renderDeleteConfirm(Request $request, int $collection_id, int $id, array $errors = [], string $mode = '', string $target = ''): ViewResponse
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $section = $this->findOwnedSection($collection_id, $id);
        if ($section === null) {
            return $this->wrongData($collection_id);
        }

        try {
            $preview = $this->deletions->plan($collection_id, $id)->preview;
        } catch (CollectionDeletionException) {
            return $this->wrongData($collection_id);
        }
        $dialog = [
            'title'       => __('Delete section'),
            'message'     => __('Are you sure you want to delete the section?')
                . ' ' . __('Nested sections will be deleted too.'),
            'name'        => $section->name,
            'facts' => [
                ['label' => __('Subsections'), 'value' => $preview->sections - 1],
                ['label' => __('Items in the subtree'), 'value' => $preview->items],
            ],
            'hidden' => ['revision' => $preview->revision],
            'errors' => $errors,
            'deletion' => ['mode' => $mode, 'target' => $target, 'options' => $this->sectionOptions($collection_id, $id)],
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->baseUrl($collection_id),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/delete-dialog.twig', $dialog);
        }

        $this->breadcrumbs($collection);
        $this->navChain->add($dialog['title']);

        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($dialog['title'], $collection->name, $collection) + [
            'dialog' => $dialog,
        ]);
    }

    public function delete(Request $request, int $collection_id, int $id): ViewResponse
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }
        if ($this->findOwnedSection($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }
        $modeInput = $request->body('mode', '');
        $targetInput = $request->body('target', '');
        try {
            $mode = SectionDeletionMode::tryFrom($modeInput);
            if ($mode === null) {
                throw new CollectionDeletionException(['mode' => [__('Choose whether to delete or move the items.')]]);
            }
            $targetId = $mode === SectionDeletionMode::MoveItems
                ? filter_var($targetInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            $this->deleteSection->execute($id, $collection_id, $mode, $targetId !== false ? $targetId : null, $request->body('revision', ''));
        } catch (CollectionDeletionException $exception) {
            return $this->renderDeleteConfirm($request, $collection_id, $id, array_merge(...array_values($exception->errors)), $modeInput, $targetInput);
        } catch (CollectionWriteConflictException) {
            return $this->renderDeleteConfirm($request, $collection_id, $id, [__('The contents changed during deletion. Check the counts and try again.')], $modeInput, $targetInput);
        }
        $this->session->flash('success_message', __('Deleted successfully'));
        redirect($this->baseUrl($collection_id));
    }

    private function findOwnedSection(int $collectionId, int $id): ?ContentCollectionSection
    {
        $section = $this->sectionRepository->findById($id);

        return $section !== null && $section->collection_id === $collectionId ? $section : null;
    }

    private function resolveParent(int $collectionId, ?int $parent): ?ContentCollectionSection
    {
        return $parent !== null ? $this->findOwnedSection($collectionId, $parent) : null;
    }

    /**
     * @param list<SectionTreeItemDTO> $items
     * @return list<array<string, mixed>>
     */
    private function mapRows(int $collectionId, array $items): array
    {
        $base = $this->baseUrl($collectionId);
        $itemsBase = CollectionWorkspaceMapper::url($collectionId);
        $action = static fn (string $url, string $title, string $icon, bool $danger = false, bool $dialog = false): array => [
            'method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => $dialog, 'fields' => [],
        ];

        $rows = [];
        foreach ($items as $item) {
            $section = $item->section;
            $editUrl = $base . '/' . $section->id . '/edit';
            $itemsUrl = $itemsBase . '?section=' . $section->id;

            $rows[] = [
                'id'                => $section->id,
                'name'              => $section->name,
                'code'              => $section->code,
                'active'            => $section->active,
                'depth'             => $item->depth,
                'items'             => $item->items,
                'items_in_subtree'  => $item->itemsInSubtree,
                'items_url'         => $itemsUrl,
                'edit_url'          => $editUrl,
                'actions'           => [
                    $action($editUrl, __('Edit'), 'pencil'),
                    $action($itemsUrl, __('Items of the section'), 'file-text'),
                    $action($itemsBase . '/new?section=' . $section->id, __('New item'), 'plus'),
                    $action($base . '/new?parent=' . $section->id, __('New subsection'), 'folder-plus'),
                    $action($base . '/' . $section->id . '/delete', __('Delete'), 'trash-2', danger: true, dialog: true),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = []): ViewResponse
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit section') : __('New section');
        $this->navChain->add($title);

        return new ViewResponse('@collections/admin/section-form.twig', $this->menu($title, $collection->name, $collection) + [
            'form_action' => $this->baseUrl($collection->id),
            'back_url'    => $this->baseUrl($collection->id),
            'id'          => $id,
            'fields'      => $fields,
            'section_options' => $this->sectionOptions($collection->id, $id),
            'errors'      => $errors,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request, ?int $parent): array
    {
        return [
            'parent'      => $parent,
            'code'        => trim($request->body('code', '')),
            'name'        => trim($request->body('name', '')),
            'description' => trim($request->body('description', '')),
            'active'      => $request->hasBody('active') ? 1 : 0,
            'sort'        => $request->body('sort', '100'),
            'metadata'    => $request->body('metadata', '{}'),
            'seo' => array_filter([
                'meta_title' => $request->hasBody('meta_title') ? $request->body('meta_title') : null,
                'meta_description' => $request->hasBody('meta_description') ? $request->body('meta_description') : null,
                'meta_keywords' => $request->hasBody('meta_keywords') ? $request->body('meta_keywords') : null,
            ], static fn ($value): bool => $value !== null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromSection(ContentCollectionSection $section): array
    {
        return [
            'parent'      => $section->parent,
            'code'        => $section->code,
            'name'        => $section->name,
            'description' => (string) $section->description,
            'active'      => $section->active ? 1 : 0,
            'sort'        => $section->sort,
            'seo' => array_filter($section->metadata ?? [], static fn ($value): bool => is_string($value)),
            'metadata'    => json_encode($section->metadata ?: new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(?int $parent): array
    {
        return [
            'parent'      => $parent,
            'code'        => '',
            'name'        => '',
            'description' => '',
            'active'      => 1,
            'sort'        => 100,
            'metadata'    => '{}',
            'seo' => [],
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(int $collectionId, array $fields): CollectionSectionFormDTO
    {
        return new CollectionSectionFormDTO(
            collectionId: $collectionId,
            parent: $fields['parent'] !== null ? (int) $fields['parent'] : null,
            code: (string) $fields['code'],
            name: (string) $fields['name'],
            description: $fields['description'] !== '' ? (string) $fields['description'] : null,
            active: (bool) $fields['active'],
            sort: $fields['sort'],
            metadata: array_replace($this->metadataFromJson($fields['metadata']), $fields['seo'] ?? []),
        );
    }

    /** @return array<string, mixed> */
    private function metadataFromJson(string $json): array
    {
        if (! str_starts_with(ltrim($json), '{')) {
            throw new \JsonException('Section metadata must be an object.');
        }
        return json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    }

    /** @return list<array{id: int, label: string}> */
    private function sectionOptions(int $collectionId, ?int $id): array
    {
        $tree = SectionTree::of($this->sectionRepository->getAllByCollection($collectionId));
        $excluded = $id !== null ? $tree->subtreeOf($id) : [];
        $options = [];
        foreach ($tree->ordered() as $sectionId) {
            if (! in_array($sectionId, $excluded, true)) {
                $options[] = ['id' => $sectionId, 'label' => implode(' › ', $tree->pathOf($sectionId))];
            }
        }
        return $options;
    }

    private function queryParent(Request $request): ?int
    {
        return ($request->queryInt('parent')) ?: null;
    }

    private function postParent(Request $request): ?int
    {
        return ($request->bodyInt('parent')) ?: null;
    }

    private function baseUrl(int $collectionId): string
    {
        return '/admin/collections/' . $collectionId . '/sections';
    }

    private function breadcrumbs(ContentCollection $collection): void
    {
        if ($this->workspace->canManageSchema()) {
            $this->navChain->add(__('Collections'), '/admin/collections');
        }
        $this->navChain->add($collection->name, $this->baseUrl($collection->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title, string $collectionName, ?ContentCollection $collection = null): array
    {
        return [
            'title'       => $title . ' — ' . $collectionName,
            'page_title'  => $title,
            'module_menu' => $collection !== null ? $this->workspace->menu($collection) : ['collections' => true],
        ];
    }

    private function collectionNotFound(): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Collections'), __('Collections')) + [
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => '/admin/collections',
        ]);
    }

    private function wrongData(int $collectionId): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Sections'), __('Sections')) + [
            'title'    => __('Sections'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => $this->baseUrl($collectionId),
        ]);
    }
}
