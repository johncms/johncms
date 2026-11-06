<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\ValueObjects;

use Johncms\Modules\Collections\Domain\Enums\ItemStatus;

/**
 * What the list of the items of a collection in the panel is narrowed to: a search by the name,
 * the code or the id, a section with everything under it, and the status.
 */
final readonly class ItemListFilter
{
    /** The items that stand outside every section. */
    public const int WITHOUT_SECTION = 0;

    public function __construct(
        public string $search = '',
        public ?int $section = null,
        public ?ItemStatus $status = null,
        public string $sortBy = 'sort',
        public string $direction = 'asc',
        public ?int $author = null,
        public ?string $createdFrom = null,
        public ?string $createdTo = null,
        public ?int $minViews = null,
        public bool $explicitOrder = false,
    ) {
        if (! in_array($sortBy, ['sort', 'id', 'name', 'created_at', 'updated_at', 'view_count', 'active_from'], true) || ! in_array($direction, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException('Unsupported panel sorting.');
        }
        foreach ([$createdFrom, $createdTo] as $date) {
            if ($date !== null && (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) {
                throw new \InvalidArgumentException('Use an ISO calendar date.');
            }
        }
        if (($author !== null && $author < 1) || ($minViews !== null && $minViews < 0)) {
            throw new \InvalidArgumentException('Use a positive author ID and nonnegative views.');
        }
    }

    public function withSearch(string $search): self
    {
        return new self($search, $this->section, $this->status, $this->sortBy, $this->direction, $this->author, $this->createdFrom, $this->createdTo, $this->minViews, $this->explicitOrder);
    }

    public function withSection(?int $section): self
    {
        return new self($this->search, $section, $this->status, $this->sortBy, $this->direction, $this->author, $this->createdFrom, $this->createdTo, $this->minViews, $this->explicitOrder);
    }

    public function withStatus(?ItemStatus $status): self
    {
        return new self($this->search, $this->section, $status, $this->sortBy, $this->direction, $this->author, $this->createdFrom, $this->createdTo, $this->minViews, $this->explicitOrder);
    }

    public function isEmpty(): bool
    {
        return $this->search === '' && $this->section === null && $this->status === null && $this->author === null && $this->createdFrom === null && $this->createdTo === null && $this->minViews === null;
    }
}
