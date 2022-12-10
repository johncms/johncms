<?php

declare(strict_types=1);

namespace Johncms\View\Components;

use Johncms\View\Render;

class Avatar extends AbstractBladeComponent
{
    public function __construct(
        protected ?string $avatarUrl = null,
        protected ?string $username = null
    ) {
    }

    public function render(): string
    {
        $firstSymbols = ! empty($this->username) ? $this->getFirstSymbols($this->username) : '';
        return di(Render::class)->render('system::app/avatar', [
            'avatar_url'    => $this->avatarUrl,
            'username'      => $this->username,
            'first_symbols' => $firstSymbols,
            'color'         => $this->getColor($this->username),
        ]);
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
