<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use DateTimeImmutable;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Updates\SystemOperationContext;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

final readonly class CollectionsImportApi implements CollectionsImportApiInterface
{
    public function __construct(
        private SystemOperationContext $context,
        private SaveCollectionItemUseCase $save,
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionSectionRepositoryInterface $sections,
        private ValidatorInterface $validator,
    ) {
    }

    public function importItem(CollectionItemFormDTO $form, CollectionItemHistoryDTO $history): int
    {
        $this->context->requireActive();
        return $this->save->execute(null, $form, history: $history);
    }

    public function importSection(CollectionSectionFormDTO $form, DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): int
    {
        $this->context->requireActive();
        return $this->transaction->run(function () use ($form, $createdAt, $updatedAt): int {
            $result = $this->validator->validate(['name' => trim($form->name), 'code' => trim($form->code)], [
                'name' => [new StringLength(max: 255)],
                'code' => [new StringLength(max: 255), new Pattern('/^(?!\.{1,2}$)[\w.+-]+$/D')],
            ]);
            if ($this->collections->findForUpdate($form->collectionId) === null) {
                $result = $result->withError('collection_id', d__('collections', 'Collection not found.'));
            }
            if ($form->parent !== null) {
                $parent = $this->sections->findForUpdate($form->parent);
                if ($parent === null || $parent->collection_id !== $form->collectionId) {
                    $result = $result->withError('parent', d__('collections', 'Choose a section of this collection.'));
                }
            }
            if ($form->sort < -2147483648 || $form->sort > 2147483647) {
                $result = $result->withError('sort', d__('collections', 'Enter an integer between -2147483648 and 2147483647.'));
            }
            if ($form->description !== null && strlen($form->description) > 65535) {
                $result = $result->withError('description', d__('collections', 'The section description must not exceed 65535 bytes.'));
            }
            $result->throwIfInvalid(static fn (array $errors): CollectionSectionFormException => new CollectionSectionFormException($errors));
            if ($this->sections->findByCodeForUpdate($form->collectionId, $form->parent, trim($form->code)) !== null) {
                throw new CollectionSectionCodeAlreadyExistsException();
            }
            return $this->sections->create([
                'collection_id' => $form->collectionId, 'parent' => $form->parent,
                'name' => trim($form->name), 'code' => trim($form->code), 'description' => $form->description,
                'active' => $form->active, 'sort' => $form->sort,
                'created_at' => CollectionItemHistoryDTO::timestamp($createdAt),
                'updated_at' => CollectionItemHistoryDTO::timestamp($updatedAt),
            ])->id;
        });
    }
}
