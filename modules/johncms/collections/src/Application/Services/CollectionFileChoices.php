<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use InvalidArgumentException;
use Johncms\Auth\CurrentUser;
use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Modules\Collections\Application\DTO\CollectionFileChoiceDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class CollectionFileChoices
{
    public function __construct(
        private CollectionFileRepositoryInterface $links,
        private ContentCollectionFieldRepositoryInterface $fields,
        private FileStore $files,
        private CollectionItemFiles $attachments,
        private CollectionFilePolicy $policy,
        private EnsureCollectionContentAccessUseCase $access,
        private CurrentUser $user,
    ) {
    }

    /** @param list<string> $selected
     * @return list<CollectionFileChoiceDTO>
     */
    public function search(int $collectionId, string $code, ?string $context = null, string $term = '', array $selected = []): array
    {
        $this->access->execute($collectionId);
        $field = $this->fields->findByCode($collectionId, $code);
        if ($field === null || $field->type !== FieldType::File || ($field->settings['file_storage'] ?? 'legacy') !== 'registry' || mb_strlen($term) > 255 || count($selected) > 1000) {
            throw new InvalidArgumentException('Choose a registered file field, a search term up to 255 characters and at most 1000 selected values.');
        }
        $term = trim($term);
        $ids = $this->links->availableForCollection($collectionId, false);
        if ($context !== null) {
            $record = $this->links->context($context);
            $ids = [...$ids, ...$this->attachments->contextFiles($context, $collectionId, $record['item_id'] ?? null, $this->user->id())];
        }
        $ids = array_values(array_unique($ids));
        rsort($ids, SORT_NUMERIC);
        $wanted = array_fill_keys($selected, true);
        $matches = 0;
        $result = [];
        foreach ($ids as $id) {
            $keep = isset($wanted[(string) $id]);
            unset($wanted[(string) $id]);
            if ($matches >= 30 && ! $keep) {
                if ($wanted === []) {
                    break;
                }
                continue;
            }
            try {
                $file = $this->files->find($id);
                $matched = $file !== null && ($term === '' || mb_stripos($file->name, $term) !== false) && $matches < 30;
                if ($file === null || (! $matched && ! $keep)) {
                    continue;
                }
                $mime = $this->policy->assertStored($id, $code, $field->settings ?? []);
            } catch (FileStoreException | CollectionItemFormException) {
                // Unavailable or incompatible entries cannot be selected; retained IDs get a placeholder in the form.
                continue;
            }
            $matches += $matched ? 1 : 0;
            $result[] = new CollectionFileChoiceDTO((string) $id, $file->name, $file->url, $file->size, format_size($file->size), str_starts_with($mime, 'image/'), $matched);
        }
        return $result;
    }
}
