<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use Johncms\Utils\Transliterator;

final readonly class GetMySmiliesUseCase
{
    public function __construct(
        private User $currentUser,
        private SmiliesRendererInterface $smiliesRenderer,
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
                'smile'     => Transliterator::toCyrillic($smile),
                'picture'   => $this->smiliesRenderer->render($smile, $this->currentUser->rights >= 1),
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
