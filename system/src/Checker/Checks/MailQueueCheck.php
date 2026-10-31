<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Mail\Queue\EmailQueueStatusInterface;

final readonly class MailQueueCheck implements SystemCheckInterface
{
    public function __construct(private EmailQueueStatusInterface $queue)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('mail.queue', d__('system', 'Mail queue'), d__('system', 'Background tasks'), 'clock', order: 61);
    }

    public function run(): array
    {
        $counts = $this->queue->counts(time() - 3600);

        return [
            new CheckResultDTO(
                'mail.failed',
                d__('system', 'Emails that could not be delivered'),
                (string) $counts['failed'],
                $counts['failed'] > 0 ? CheckStatus::Failed : CheckStatus::Passed,
                CheckSeverity::Warning,
                d__('system', 'These messages exhausted their delivery attempts. Check mail transport settings and the application log.'),
            ),
            new CheckResultDTO(
                'mail.overdue',
                d__('system', 'Emails waiting for delivery for over an hour'),
                (string) $counts['overdue'],
                $counts['overdue'] > 0 ? CheckStatus::Failed : CheckStatus::Passed,
                CheckSeverity::Warning,
                d__('system', 'Check that cron runs and mail:send-pending completes successfully. Messages scheduled for a later attempt are excluded.'),
            ),
        ];
    }
}
