<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Utils\Transliterator;

final readonly class GetAdminSmiliesUseCase
{
    public const USER_SMILIES_MAX = 20;

    public function __construct(
        private CurrentUser $currentUser,
    ) {
    }

    public function count(): int
    {
        return count($this->files());
    }

    public function userSmiliesCount(): int
    {
        return count($this->userSmilies());
    }

    /**
     * @return list<array{can_add: bool, lat_smile: string, smile: string, picture: string}>
     */
    public function getPage(int $limit, int $offset): array
    {
        $userSmilies = $this->userSmilies();

        $items = [];
        foreach (array_slice($this->files(), $offset, $limit) as $file) {
            $smile = preg_replace('#^(.*?)\.(gif|jpg|png)$#isU', '$1', $file, 1);
            $items[] = [
                'can_add'   => $this->currentUser->isValid() && ! in_array($smile, $userSmilies),
                'lat_smile' => $smile,
                'smile'     => Transliterator::toCyrillic($smile),
                'picture'   => '/assets/emoticons/admin/' . $file,
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function files(): array
    {
        $files = glob(ASSETS_PATH . 'emoticons/admin/*.{gif,jpg,png}', GLOB_BRACE) ?: [];

        return array_map('basename', $files);
    }

    /**
     * @return list<string>
     */
    private function userSmilies(): array
    {
        return is_array($this->currentUser->user()->smileys) ? array_values($this->currentUser->user()->smileys) : [];
    }
}
