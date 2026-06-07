<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface WhoisClientInterface
{
    /**
     * Выполняет WHOIS-запрос по IP-адресу на доступных серверах.
     *
     * @param string $ip
     * @return array<string, string> Карта «whois-сервер => отфильтрованный текст ответа», без дубликатов
     */
    public function lookup(string $ip): array;
}
