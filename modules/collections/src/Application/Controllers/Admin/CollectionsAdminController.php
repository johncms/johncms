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
            'add_url'         => self::URL . '/new',
            'pagination'      => $pagination->render(),
            'success_message' => $this->session->getFlash('success_message') ?? '',
        ]);
    }

    public function newForm(): ViewResponse
    {
        return $this->renderForm(null, $this->defaultFields());
    }

    public function editForm(int $id): ViewResponse
    {
        $collection = $this->repository->findById($id);
        if ($collection === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderForm($id, $this->fieldsFromCollection($collection));
    }

    public function store(Request $request): ViewResponse
    {
        $id = $request->bodyInt('id') ?: null;
        $fields = $this->fieldsFromRequest($request);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($id, $fields, $errors);
        }

        try {
            $isUpdate = $this->saveCollection->execute($id, $this->dtoFromFields($fields));
        } catch (CollectionCodeAlreadyExistsException) {
            return $this->renderForm($id, $fields, [__('A collection with this code already exists')]);
        } catch (CollectionCodeReservedException) {
            return $this->renderForm($id, $fields, [__('This code is reserved and cannot be used')]);
        }

        $this->session->flash('success_message', $isUpdate ? __('Changes saved') : __('Created successfully'));
        redirect(self::URL);
    }

    public function deleteConfirm(int $id): ViewResponse
    {
        $collection = $this->repository->findById($id);
        if ($collection === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Delete');
        $this->navChain->add(__('Collections'), self::URL);
        $this->navChain->add($title);
        return new ViewResponse('@collections/admin/delete-confirm.twig', $this->menu($title, $title) + [
            'message'     => __('Are you sure you want to delete the collection?'),
            'name'        => $collection->name,
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL,
        ]);
    }

    public function delete(int $id): ViewResponse
    {
        $this->deleteCollection->execute($id);
        $this->session->flash('success_message', __('Deleted successfully'));

        redirect(self::URL);
    }

    private function mapRow(CollectionListItemDTO $item): array
    {
        return [
            'code'         => $item->code,
            'name'         => $item->name,
            'active'       => $item->active,
            'sort'         => $item->sort,
            'has_sections' => $item->hasSections,
            'fields_url'   => self::URL . '/' . $item->id . '/fields',
            'sections_url' => self::URL . '/' . $item->id . '/sections',
            'items_url'    => self::URL . '/' . $item->id . '/items',
            'edit_url'     => self::URL . '/' . $item->id . '/edit',
            'delete_url'   => self::URL . '/' . $item->id . '/delete',
        ];
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(?int $id, array $fields, array $errors = []): ViewResponse
    {
        $title = $id !== null ? __('Edit collection') : __('New collection');
        $this->navChain->add(__('Collections'), self::URL);
        $this->navChain->add($title);
        return new ViewResponse('@collections/admin/collection-form.twig', $this->menu($title, $title) + [
            'form_action' => self::URL,
            'id'          => $id,
            'fields'      => $fields,
            'errors'      => $errors,
        ]);
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
