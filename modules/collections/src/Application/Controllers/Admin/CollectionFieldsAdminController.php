<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldListItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionFieldsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class CollectionFieldsAdminController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ListCollectionFieldsUseCase $listFields,
        private SaveCollectionFieldUseCase $saveField,
        private DeleteCollectionFieldUseCase $deleteField,
    ) {
        $this->controllerContext->initModule('collections');
    }

    public function index(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $this->breadcrumbs($collection);
        $title = __('Fields');
        $this->render->addData($this->menu($title, $collection->name));

        return $this->render->render('collections::admin/fields/index', [
            'collection_name' => $collection->name,
            'items'           => $this->mapRows($collection_id, $this->listFields->getByCollection($collection_id)),
            'add_url'         => $this->baseUrl($collection_id) . '/new',
            'back_url'        => '/admin/collections',
            'success_message' => $this->pullFlash(),
        ]);
    }

    public function newForm(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        return $this->renderForm($collection, null, $this->defaultFields());
    }

    public function editForm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $field = $this->findOwnedField($collection_id, $id);
        if ($field === null) {
            return $this->wrongData($collection_id);
        }

        return $this->renderForm($collection, $id, $this->fieldsFromField($field));
    }

    public function store(int $collection_id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        if (! $this->isCsrfValid()) {
            return $this->wrongData($collection_id);
        }

        $id = $this->request->bodyInt('id') ?: null;
        if ($id !== null && $this->findOwnedField($collection_id, $id) === null) {
            return $this->wrongData($collection_id);
        }

        $fields = $this->fieldsFromRequest();
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($collection, $id, $fields, $errors);
        }

        try {
            $isUpdate = $this->saveField->execute($id, $this->dtoFromFields($collection_id, $fields));
        } catch (CollectionFieldCodeAlreadyExistsException) {
            return $this->renderForm($collection, $id, $fields, [__('A field with this code already exists')]);
        }

        $_SESSION['success_message'] = $isUpdate ? __('Changes saved') : __('Created successfully');
        redirect($this->baseUrl($collection_id));
    }

    public function deleteConfirm(int $collection_id, int $id): string
    {
        $collection = $this->collectionRepository->findById($collection_id);
        if ($collection === null) {
            return $this->collectionNotFound();
        }

        $field = $this->findOwnedField($collection_id, $id);
        if ($field === null) {
            return $this->wrongData($collection_id);
        }

        $this->breadcrumbs($collection);
        $title = __('Delete');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        return $this->render->render('collections::admin/delete_confirm', [
            'message'     => __('Are you sure you want to delete the field?'),
            'name'        => $field->name,
            'form_action' => $this->baseUrl($collection_id) . '/' . $id . '/delete',
            'back_url'    => $this->baseUrl($collection_id),
        ]);
    }

    public function delete(int $collection_id, int $id): string
    {
        if ($this->collectionRepository->findById($collection_id) === null) {
            return $this->collectionNotFound();
        }

        if ($this->isCsrfValid() && $this->findOwnedField($collection_id, $id) !== null) {
            $this->deleteField->execute($id);
            $_SESSION['success_message'] = __('Deleted successfully');
        }

        redirect($this->baseUrl($collection_id));
    }

    private function findOwnedField(int $collectionId, int $id): ?ContentCollectionField
    {
        $field = $this->fieldRepository->findById($id);

        return $field !== null && $field->collection_id === $collectionId ? $field : null;
    }

    /**
     * @param list<CollectionFieldListItemDTO> $items
     * @return list<array<string, mixed>>
     */
    private function mapRows(int $collectionId, array $items): array
    {
        $base = $this->baseUrl($collectionId);

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'code'       => $item->code,
                'name'       => $item->name,
                'type_label' => $this->typeLabel($item->type),
                'required'   => $item->required,
                'multiple'   => $item->multiple,
                'sort'       => $item->sort,
                'edit_url'   => $base . '/' . $item->id . '/edit',
                'delete_url' => $base . '/' . $item->id . '/delete',
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(ContentCollection $collection, ?int $id, array $fields, array $errors = []): string
    {
        $this->breadcrumbs($collection);
        $title = $id !== null ? __('Edit field') : __('New field');
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $collection->name));

        return $this->render->render('collections::admin/fields/form', [
            'form_action'  => $this->baseUrl($collection->id),
            'back_url'     => $this->baseUrl($collection->id),
            'id'           => $id,
            'fields'       => $fields,
            'type_options' => $this->typeOptions(),
            'errors'       => $errors,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(): array
    {
        return [
            'code'     => trim($this->request->body('code', '')),
            'name'     => trim($this->request->body('name', '')),
            'type'     => $this->request->body('type', ''),
            'required' => $this->request->hasBody('required') ? 1 : 0,
            'multiple' => $this->request->hasBody('multiple') ? 1 : 0,
            'sort'     => $this->request->bodyInt('sort', 100),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromField(ContentCollectionField $field): array
    {
        return [
            'code'     => $field->code,
            'name'     => $field->name,
            'type'     => $field->type->value,
            'required' => $field->required ? 1 : 0,
            'multiple' => $field->multiple ? 1 : 0,
            'sort'     => $field->sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(): array
    {
        return [
            'code'     => '',
            'name'     => '',
            'type'     => FieldType::String_->value,
            'required' => 0,
            'multiple' => 0,
            'sort'     => 100,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(int $collectionId, array $fields): CollectionFieldFormDTO
    {
        return new CollectionFieldFormDTO(
            collectionId: $collectionId,
            code: (string) $fields['code'],
            name: (string) $fields['name'],
            type: FieldType::from((string) $fields['type']),
            required: (bool) $fields['required'],
            multiple: (bool) $fields['multiple'],
            sort: (int) $fields['sort'],
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

        if (FieldType::tryFrom((string) $fields['type']) === null) {
            $errors[] = __('Unknown field type');
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];
        foreach (FieldType::cases() as $case) {
            $options[$case->value] = $this->typeLabel($case);
        }

        return $options;
    }

    private function typeLabel(FieldType $type): string
    {
        return match ($type) {
            FieldType::String_  => __('String'),
            FieldType::Text     => __('Text'),
            FieldType::Html     => __('HTML'),
            FieldType::Integer  => __('Integer'),
            FieldType::Double   => __('Number (fractional)'),
            FieldType::Boolean  => __('Boolean'),
            FieldType::Date     => __('Date'),
            FieldType::Datetime => __('Date and time'),
            FieldType::File     => __('File'),
            FieldType::Select   => __('Select'),
            FieldType::Relation => __('Relation'),
        };
    }

    private function baseUrl(int $collectionId): string
    {
        return '/admin/collections/' . $collectionId . '/fields';
    }

    private function breadcrumbs(ContentCollection $collection): void
    {
        $this->navChain->add(__('Collections'), '/admin/collections');
        $this->navChain->add($collection->name, $this->baseUrl($collection->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title, string $collectionName): array
    {
        return [
            'title'       => $title . ' — ' . $collectionName,
            'page_title'  => $title,
            'module_menu' => ['collections' => true],
        ];
    }

    private function collectionNotFound(): string
    {
        $this->render->addData($this->menu(__('Collections'), __('Collections')));

        return $this->render->render('system::pages/result', [
            'title'    => __('Collections'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => '/admin/collections',
        ]);
    }

    private function wrongData(int $collectionId): string
    {
        $this->render->addData($this->menu(__('Fields'), __('Fields')));

        return $this->render->render('system::pages/result', [
            'title'    => __('Fields'),
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => $this->baseUrl($collectionId),
        ]);
    }

    private function pullFlash(): string
    {
        if (empty($_SESSION['success_message'])) {
            return '';
        }

        $message = (string) $_SESSION['success_message'];
        unset($_SESSION['success_message']);

        return $message;
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
