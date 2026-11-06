<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use InvalidArgumentException;
use Johncms\Modules\Collections\Domain\Repository\CollectionSearchRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer;

final readonly class CollectionTagSuggestions
{
    public function __construct(private CollectionSearchRepositoryInterface $index)
    {
    }

    /** @return list<string> */
    public function suggest(int $collectionId, string $fieldCode, string $prefix, int $limit = 10, bool $published = true): array
    {
        if ($limit < 1 || $limit > 100 || mb_strlen($prefix) > 255) {
            throw new InvalidArgumentException('Use a tag prefix up to 255 characters and limit 1..100.');
        }
        return $this->index->suggestTags($collectionId, $fieldCode, CollectionTextNormalizer::normalize($prefix), $limit, $published);
    }
}
