<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class IpWhoisResultDTO
{
    /**
     * @param string $ip
     * @param array<string, string> $results Карта «whois-сервер => текст ответа»
     */
    public function __construct(
        public string $ip,
        public array $results,
    ) {
    }

    public function count(): int
    {
        return count($this->results);
    }
}
