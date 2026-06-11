<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class GetMySmiliesUseCase
{
    public function __construct(
        private User $currentUser,
        private Tools $tools,
    ) {
    }

    public function count(): int
    {
        return count($this->smilies());
    }

    /**
     * @return list<array{can_del: bool, lat_smile: string, smile: string, picture: string}>
     */
    public function getPage(int $limit, int $offset): array
    {
        $items = [];
        foreach (array_slice($this->smilies(), $offset, $limit) as $value) {
            $smile = ':' . $value . ':';
            $items[] = [
                'can_del'   => true,
                'lat_smile' => $value,
                'smile'     => $this->tools->trans($smile),
                'picture'   => $this->tools->smilies($smile, $this->currentUser->rights >= 1 ? 1 : 0),
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function smilies(): array
    {
        return is_array($this->currentUser->smileys) ? array_values($this->currentUser->smileys) : [];
    }
}
