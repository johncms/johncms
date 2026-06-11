<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\UseCases;

use Johncms\Users\User;

final readonly class GetAvatarsUseCase
{
    public const PER_PAGE = 50;

    public function __construct(
        private User $currentUser,
    ) {
    }

    public function directoryExists(string $id): bool
    {
        return is_dir($this->directory($id));
    }

    public function directoryTitle(string $id): string
    {
        $nameFile = $this->directory($id) . '/name.txt';

        return is_file($nameFile)
            ? htmlentities((string) file_get_contents($nameFile), ENT_QUOTES, 'utf-8')
            : $id;
    }

    public function count(string $id): int
    {
        return count($this->files($id));
    }

    /**
     * @return list<array{picture: string, set_url: string}>
     */
    public function getPage(string $id, int $limit, int $offset): array
    {
        $items = [];
        foreach (array_slice($this->files($id), $offset, $limit) as $file) {
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            $items[] = [
                'picture' => '/assets/avatars/' . $id . '/' . basename($file),
                'set_url' => ($this->currentUser->isValid() && is_numeric($baseName))
                    ? '/help/avatars/' . $id . '/set/' . $baseName . '/'
                    : '',
            ];
        }

        return $items;
    }

    private function directory(string $id): string
    {
        return ASSETS_PATH . 'avatars/' . $id;
    }

    /**
     * @return list<string>
     */
    private function files(string $id): array
    {
        return glob($this->directory($id) . '/*.png') ?: [];
    }
}
