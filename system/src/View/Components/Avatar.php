<?php

declare(strict_types=1);

namespace Johncms\View\Components;

use Illuminate\Contracts\View\View;
use Johncms\View\Render;

class Avatar extends AbstractBladeComponent
{
    public string $firstSymbol = '';
    public string $color = '';

    public function __construct(
        public ?string $avatarUrl = null,
        public ?string $username = null,
    ) {
        $this->firstSymbol = ! empty($this->username) ? $this->getFirstSymbols($this->username) : '';
        $this->color = $this->getColor($this->username);
    }

    public function render(): View
    {
        return di(Render::class)->make('system::app/avatar');
    }

    private function getFirstSymbols(string $username): string
    {
        $return = '';
        $name = explode(' ', $username);
        $return .= mb_substr($name[0], 0, 1);
        if (! empty($name[1])) {
            $return .= mb_substr($name[1], 0, 1);
        }
        return $return;
    }

    private function getColor(?string $userName = ''): string
    {
        $colors = config('johncms.avatar_colors');
        return $colors[(mb_strlen((string) $userName) % (is_countable($colors) ? count($colors) : 0))] ?? $colors[0];
    }
}
