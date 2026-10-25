<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\ValueObjects;

use Johncms\Modules\Admin\Domain\Enums\AuthLogPeriod;

/**
 * What the sign-in log is narrowed to. The newest entry always comes first.
 *
 * The account (userId) is what the page of an account links to; the search finds accounts by
 * their nickname or number, and entries by the address they came from.
 */
final readonly class AuthLogFilter
{
    /**
     * @param string|null $event One kind of event, by its key.
     */
    public function __construct(
        public string $search = '',
        public ?string $event = null,
        public AuthLogPeriod $period = AuthLogPeriod::ANY,
        public ?int $userId = null,
    ) {
    }

    public function withSearch(string $search): self
    {
        return new self($search, $this->event, $this->period, $this->userId);
    }

    public function withEvent(?string $event): self
    {
        return new self($this->search, $event, $this->period, $this->userId);
    }

    public function withPeriod(AuthLogPeriod $period): self
    {
        return new self($this->search, $this->event, $period, $this->userId);
    }

    public function withUser(?int $userId): self
    {
        return new self($this->search, $this->event, $this->period, $userId);
    }
}
