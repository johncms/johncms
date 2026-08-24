<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\BanIp;

/**
 * Результат подготовки бана по IP: либо ошибки, либо список конфликтующих банов,
 * либо готовые к подтверждению данные диапазона.
 */
final readonly class PreparedIpBanDTO
{
    /**
     * @param list<string> $errors
     * @param Collection<int, BanIp>|null $conflicts
     * @param 'single'|'range'|'mask'|null $mode
     */
    public function __construct(
        public array $errors = [],
        public ?Collection $conflicts = null,
        public ?int $ip1 = null,
        public ?int $ip2 = null,
        public ?string $mode = null,
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== null && $this->conflicts->isNotEmpty();
    }

    public function isReady(): bool
    {
        return ! $this->hasErrors() && ! $this->hasConflicts() && $this->ip1 !== null && $this->ip2 !== null;
    }
}
