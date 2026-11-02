<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use DateTimeImmutable;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Updates\SystemOperationContext;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

final readonly class SaveCollectionSectionUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
        private ContentCollectionRepositoryInterface $collections,
        private CollectionTransactionInterface $transaction,
        private ValidatorInterface $validator,
        private SystemOperationContext $context,
    ) {
    }

    /** Creates or updates a section; historical dates are allowed only for trusted new imports. */
    public function execute(?int $id, CollectionSectionFormDTO $dto, ?DateTimeImmutable $createdAt = null, ?DateTimeImmutable $updatedAt = null): int
    {
        if ($createdAt !== null || $updatedAt !== null) {
            $this->context->requireActive();
            if ($id !== null || $createdAt === null || $updatedAt === null) {
                throw new \InvalidArgumentException('Import requires a new section and both historical dates.');
            }
        }
        return $this->transaction->run(function () use ($id, $dto, $createdAt, $updatedAt): int {
            // The collection lock serializes moves and sibling codes, including NULL parents.
            $result = $this->validator->validate(['name' => trim($dto->name), 'code' => trim($dto->code)], [
                'name' => [new StringLength(max: 255)],
                'code' => [new StringLength(max: 255), new Pattern('/^(?!\.{1,2}$)[\w.+-]+$/D')],
            ]);
            if ($this->collections->findForUpdate($dto->collectionId) === null) {
                $result = $result->withError('collection_id', d__('collections', 'Collection not found.'));
            }
            if ($id !== null) {
                $section = $this->repository->findForUpdate($id);
                if ($section === null || $section->collection_id !== $dto->collectionId) {
                    $result = $result->withError('id', d__('collections', 'The section does not belong to this collection or no longer exists.'));
                }
            }
            if ($dto->parent !== null) {
                $parent = $this->repository->findForUpdate($dto->parent);
                if ($parent === null || $parent->collection_id !== $dto->collectionId) {
                    $result = $result->withError('parent', d__('collections', 'Choose a section of this collection.'));
                } elseif ($id !== null && in_array($dto->parent, SectionTree::of($this->repository->getAllForUpdate($dto->collectionId))->subtreeOf($id), true)) {
                    $result = $result->withError('parent', d__('collections', 'A section cannot be moved into itself or its descendants.'));
                }
            }
            $sort = filter_var($dto->sort, FILTER_VALIDATE_INT);
            if ($sort === false || $sort < -2147483648 || $sort > 2147483647) {
                $result = $result->withError('sort', d__('collections', 'Enter an integer between -2147483648 and 2147483647.'));
            }
            if ($dto->description !== null && strlen($dto->description) > 65535) {
                $result = $result->withError('description', d__('collections', 'The section description must not exceed 65535 bytes.'));
            }
            try {
                $json = json_encode($dto->metadata, JSON_THROW_ON_ERROR, 32);
                if (strlen($json) > 65535 || ($dto->metadata !== [] && array_is_list($dto->metadata)) || ! $this->isJsonData($dto->metadata)) {
                    throw new \JsonException();
                }
            } catch (\JsonException) {
                $result = $result->withError('metadata', d__('collections', 'Use a JSON object of at most 65535 bytes for section metadata.'));
            }
            $result->throwIfInvalid(static fn (array $errors): CollectionSectionFormException => new CollectionSectionFormException($errors));
            $existing = $this->repository->findByCodeForUpdate($dto->collectionId, $dto->parent, trim($dto->code));
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionSectionCodeAlreadyExistsException();
            }
            $attributes = [
                'collection_id' => $dto->collectionId, 'parent' => $dto->parent,
                'name' => trim($dto->name), 'code' => trim($dto->code), 'description' => $dto->description,
                'active' => $dto->active, 'sort' => $sort, 'metadata' => $dto->metadata,
            ];
            if ($id !== null) {
                $this->repository->update($id, $attributes);
                return $id;
            }
            if ($createdAt !== null && $updatedAt !== null) {
                $attributes['created_at'] = CollectionItemHistoryDTO::timestamp($createdAt);
                $attributes['updated_at'] = CollectionItemHistoryDTO::timestamp($updatedAt);
            }
            return $this->repository->create($attributes)->id;
        });
    }

    private function isJsonData(mixed $value): bool
    {
        if (! is_array($value)) {
            return $value === null || is_scalar($value);
        }
        foreach ($value as $child) {
            if (! $this->isJsonData($child)) {
                return false;
            }
        }
        return true;
    }
}
