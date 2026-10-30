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
    ) {
    }

    public function withSearch(string $search): self
    {
        return new self($search, $this->section, $this->status);
    }

    public function withSection(?int $section): self
    {
        return new self($this->search, $section, $this->status);
    }

    public function withStatus(?ItemStatus $status): self
    {
        return new self($this->search, $this->section, $status);
    }

    public function isEmpty(): bool
    {
        return $this->search === '' && $this->section === null && $this->status === null;
    }
}
