<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\ValueObjects;

use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Enums\BanListStatus;
use Johncms\Users\Bans\BanKind;

/**
 * What the ban panel is narrowed to and how it is ordered.
 *
 * One row per account, with the ban of it that ends last; narrowed to a kind, the last ban of
 * that kind.
 */
final readonly class BanListFilter
{
    public function __construct(
        public string $search = '',
        public BanListStatus $status = BanListStatus::ACTIVE,
        public ?BanKind $kind = null,
        public BanListSort $sort = BanListSort::TIME,
    ) {
    }

    public function withSearch(string $search): self
    {
        return new self($search, $this->status, $this->kind, $this->sort);
    }

    public function withStatus(BanListStatus $status): self
    {
        return new self($this->search, $status, $this->kind, $this->sort);
    }

    public function withKind(?BanKind $kind): self
    {
        return new self($this->search, $this->status, $kind, $this->sort);
    }

    public function withSort(BanListSort $sort): self
    {
        return new self($this->search, $this->status, $this->kind, $sort);
    }
}
