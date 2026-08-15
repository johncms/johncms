<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use Johncms\Utils\Transliterator;
use Twig\Markup;

final readonly class GetMySmiliesUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private User $currentUser,
        private SmiliesRendererInterface $smiliesRenderer,
    ) {
    }

    public function count(): int
    {
        return count($this->smilies());
    }

    /**
     * @return list<array{can_del: bool, lat_smile: string, smile: string, picture: Markup}>
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
                // The renderer returns the image tag of the smiley, so it is markup by contract.
                'picture'   => new Markup($this->smiliesRenderer->render($smile, $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE)), 'UTF-8'),
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
