<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Files\FileStore;
use Johncms\Files\StoredFileDTO;
use Johncms\Http\UploadedFileDTO;
use Johncms\Image\StoreEditorImageUseCase;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Throwable;

final readonly class CollectionFieldUpload
{
    public function __construct(
        private CollectionItemFiles $attachments,
        private CollectionFileRepositoryInterface $links,
        private ContentCollectionFieldRepositoryInterface $fields,
        private ContentCollectionRepositoryInterface $collections,
        private CollectionTransactionInterface $transaction,
        private CollectionFilePolicy $policy,
        private FileStore $files,
        private StoreEditorImageUseCase $images,
        private EnsureCollectionContentAccessUseCase $access,
        private CurrentUser $user,
    ) {
    }

    public function upload(int $collectionId, string $code, string $token, UploadedFileDTO $upload): StoredFileDTO
    {
        $this->access->execute($collectionId);
        $field = $this->fields->findByCode($collectionId, $code);
        $this->requireField($field);
        $this->requireContext($token, $collectionId);
        if (! $upload->isValid() || ! is_file($upload->tmpPath)) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'Choose a file that was uploaded successfully.')]]);
        }
        $size = filesize($upload->tmpPath);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload->tmpPath);
        if ($size === false || $mime === false || ! isset(CollectionFilePolicy::TYPES[$mime])) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'This file content type is not supported.')]]);
        }
        $this->policy->assertAllowed($code, $size, $mime, $field->settings ?? []);
        $name = pathinfo($upload->clientName ?? 'file', PATHINFO_FILENAME) . '.' . CollectionFilePolicy::TYPES[$mime];
        $safe = new UploadedFileDTO($name, $mime, $size, $upload->tmpPath, $upload->error);
        $directory = 'collections/' . $collectionId;
        $file = str_starts_with($mime, 'image/') ? $this->images->execute($safe, $directory, \Johncms\Image\EditorImageFormat::Original) : $this->files->storeUpload($safe, $directory);
        try {
            $this->transaction->run(function () use ($collectionId, $code, $token, $field, $file): void {
                $this->collections->findForUpdate($collectionId) ?? throw new CollectionItemFormException(['collection_id' => [d__('collections', 'Collection not found.')]]);
                $current = $this->fields->findForUpdate($field->id);
                $this->requireField($current);
                if ($current->collection_id !== $collectionId || $current->code !== $code) {
                    throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The field changed during upload. Reopen the form and try again.')]]);
                }
                $this->requireContext($token, $collectionId);
                $this->policy->assertStored($file->id, $code, $current->settings ?? []);
                $this->links->register($file->id, true);
                $this->links->lockAsset($file->id);
                $this->links->linkContext($token, $file->id, $current->id);
            });
        } catch (Throwable $exception) {
            $this->files->delete($file->id);
            throw $exception;
        }
        return $file;
    }

    /** @phpstan-assert ContentCollectionField $field */
    private function requireField(?ContentCollectionField $field): void
    {
        if ($field === null || $field->type !== FieldType::File || ($field->settings['file_storage'] ?? 'legacy') !== 'registry') {
            throw new CollectionItemFormException(['files' => [d__('collections', 'Choose a registered file field of this collection.')]]);
        }
    }

    private function requireContext(string $token, int $collectionId): void
    {
        $context = $this->links->context($token, true);
        $this->attachments->contextFiles($token, $collectionId, $context['item_id'] ?? null, $this->user->id());
        $this->access->executeAction($collectionId, ($context['item_id'] ?? null) === null ? 'create' : 'edit');
    }
}
