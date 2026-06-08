<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

interface SmiliesScannerInterface
{
    /**
     * Сканирует каталоги смайлов и возвращает карту кодов в готовую разметку
     * в формате кэша смайлов: ['usr' => [code => html], 'adm' => [code => html]].
     *
     * @return array{usr: array<string, string>, adm: array<string, string>}
     */
    public function scan(): array;
}
