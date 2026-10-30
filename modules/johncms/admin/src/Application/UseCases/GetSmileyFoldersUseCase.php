<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Smilies\SmileyFolders;

/**
 * The folders of the smilies as the admin panel shows them: how many pictures each holds, a few of
 * them, and the code one of them is written with.
 */
final readonly class GetSmileyFoldersUseCase
{
    public const int SAMPLES = 14;

    public function __construct(private SmileyFolders $folders)
    {
    }

    /**
     * @return list<array{folder: string, kind: string, count: int, samples: list<string>, code: string}>
     */
    public function execute(): array
    {
        $rows = [];
        foreach ($this->folders->pictures() as $folder => $files) {
            $kind = str_starts_with($folder, SmileyFolders::USER . '/') ? SmileyFolders::USER : $folder;
            $first = $files === [] ? 'name' : pathinfo($files[0], PATHINFO_FILENAME);

            $rows[] = [
                'folder'  => $folder,
                'kind'    => $kind,
                'count'   => count($files),
                'samples' => array_map(
                    static fn(string $file): string => '/assets/emoticons/' . $folder . '/' . $file,
                    array_slice($files, 0, self::SAMPLES)
                ),
                'code'    => $kind === SmileyFolders::SIMPLY ? ':' . $first : ':' . $first . ':',
            ];
        }

        return $rows;
    }
}
