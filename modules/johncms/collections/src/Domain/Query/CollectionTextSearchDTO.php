<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use InvalidArgumentException;
use Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer;

final readonly class CollectionTextSearchDTO
{
    public const int MAX_LENGTH = 255;

    public string $term;

    /** @param list<string> $fields Explicit additional custom field codes. */
    public function __construct(string $term, public array $fields = [])
    {
        $this->term = CollectionTextNormalizer::normalize($term);
        if ($this->term === '' || mb_strlen($this->term) > self::MAX_LENGTH || ! array_is_list($fields)) {
            throw new InvalidArgumentException('Search requires a term of 1..255 characters and a list of custom fields.');
        }
        foreach ($fields as $code) {
            if (! is_string($code) || ! preg_match('/^[a-z0-9_-]{1,255}$/D', $code)) {
                throw new InvalidArgumentException('Search field codes must be valid collection field identifiers.');
            }
        }
    }

    /** A term typed by a visitor, cut to the limit; empty when nothing searchable is left in it. */
    public static function normalizeTerm(string $term): string
    {
        return mb_substr(CollectionTextNormalizer::normalize($term), 0, self::MAX_LENGTH);
    }

    /** @return list<string> */
    public function references(): array
    {
        return ['item.name', 'item.preview_text', 'item.detail_text', ...array_map(static fn (string $code): string => 'field.' . $code, $this->fields)];
    }
}
