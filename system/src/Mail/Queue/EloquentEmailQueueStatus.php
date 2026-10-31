<?php

declare(strict_types=1);

namespace Johncms\Mail\Queue;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Mail\EmailMessage;

final readonly class EloquentEmailQueueStatus implements EmailQueueStatusInterface
{
    public function counts(int $overdueBefore): array
    {
        $cutoff = gmdate('Y-m-d H:i:s', $overdueBefore);

        return [
            'failed' => EmailMessage::query()->whereNull('sent_at')->whereNotNull('failed_at')->count(),
            'overdue' => EmailMessage::query()->whereNull('sent_at')->whereNull('failed_at')
                ->where('created_at', '<', $cutoff)
                ->where(static function (Builder $query) use ($cutoff): void {
                    $query->whereNull('available_at')->orWhere('available_at', '<', $cutoff);
                })
                ->count(),
        ];
    }
}
