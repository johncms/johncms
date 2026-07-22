<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\Users\User;
use Johncms\Utils\Transliterator;

final readonly class GetUserSmiliesUseCase
{
    public const USER_SMILIES_MAX = 20;

    public function __construct(
        private User $currentUser,
    ) {
    }

    public function isValidCategory(string $cat): bool
    {
        $validCats = array_map('basename', glob(ASSETS_PATH . 'emoticons/user/*', GLOB_ONLYDIR) ?: []);

        return in_array($cat, $validCats, true);
    }

    public function categoryTitle(string $cat): string
    {
        return $this->categories()[$cat] ?? ucfirst(htmlspecialchars($cat));
    }

    public function count(string $cat): int
    {
        return count($this->files($cat));
    }

    public function userSmiliesCount(): int
    {
        return count($this->userSmilies());
    }

    /**
     * @return list<array{can_add: bool, lat_smile: string, smile: string, picture: string}>
     */
    public function getPage(string $cat, int $limit, int $offset): array
    {
        $userSmilies = $this->userSmilies();

        $items = [];
        foreach (array_slice($this->files($cat), $offset, $limit) as $file) {
            $smile = preg_replace('#^(.*?)\.(gif|jpg|png)$#isU', '$1', basename($file));
            $items[] = [
                'can_add'   => $this->currentUser->isValid() && ! in_array($smile, $userSmilies),
                'lat_smile' => $smile,
                'smile'     => Transliterator::toCyrillic($smile),
                'picture'   => '/assets/emoticons/user/' . $cat . '/' . basename($file),
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function files(string $cat): array
    {
        return glob(ASSETS_PATH . 'emoticons/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE) ?: [];
    }

    /**
     * @return list<string>
     */
    private function userSmilies(): array
    {
        return is_array($this->currentUser->smileys) ? array_values($this->currentUser->smileys) : [];
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        return [
            'animals'       => __('Animals'),
            'brawl_weapons' => __('Brawl, Weapons'),
            'emotions'      => __('Emotions'),
            'flowers'       => __('Flowers'),
            'food_alcohol'  => __('Food, Alcohol'),
            'gestures'      => __('Gestures'),
            'holidays'      => __('Holidays'),
            'love'          => __('Love'),
            'misc'          => __('Miscellaneous'),
            'music'         => __('Music, Dancing'),
            'sports'        => __('Sports'),
            'technology'    => __('Technology'),
        ];
    }
}
