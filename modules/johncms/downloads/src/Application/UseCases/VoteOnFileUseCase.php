<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\VoteResultDTO;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;

final readonly class VoteOnFileUseCase
{
    public function execute(int $fileId, bool $isPlus, bool $alreadyVoted): VoteResultDTO
    {
        $file = DownloadFile::query()->find($fileId);

        if ($file === null) {
            throw new FileNotFoundException();
        }

        $rate = explode('|', $file->rate ?? '');
        $plus  = ! empty($rate[0]) ? (int) $rate[0] : 0;
        $minus = ! empty($rate[1]) ? (int) $rate[1] : 0;

        if ($alreadyVoted) {
            return new VoteResultDTO($plus, $minus, false);
        }

        $isPlus ? ++$plus : ++$minus;

        DownloadFile::query()->where('id', $fileId)->update(['rate' => $plus . '|' . $minus]);

        return new VoteResultDTO($plus, $minus, true);
    }
}
