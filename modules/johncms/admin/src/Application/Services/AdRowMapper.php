<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\Utils\DateFormatterInterface;

/**
 * Prepares an advertising link for a row of the list: the title as the site shows it, who sees
 * it and where, how much of its limits is used, and whether it is shown now.
 */
final readonly class AdRowMapper
{
    public const string STATUS_SHOWN = 'shown';
    public const string STATUS_HIDDEN = 'hidden';
    public const string STATUS_EXHAUSTED = 'exhausted';

    public function __construct(
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * @param iterable<Ad> $ads
     * @return list<array<string, mixed>>
     */
    public function mapMany(iterable $ads): array
    {
        $rows = [];
        foreach ($ads as $ad) {
            $rows[] = $this->map($ad);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function map(Ad $ad): array
    {
        // The site picks one of the titles at random on every page.
        $names = array_values(array_filter(array_map(trim(...), explode('|', $ad->name)), static fn(string $name): bool => $name !== ''));
        $expiresAt = $ad->expiresAt();

        return [
            'id'          => $ad->id,
            'name'        => $names[0] ?? $ad->name,
            'other_names' => array_slice($names, 1),
            'link'        => $ad->link,
            'color'       => preg_match('/^[\da-f]{6}$/i', $ad->color) === 1 ? $ad->color : '',
            'bold'        => $ad->bold === 1,
            'italic'      => $ad->italic === 1,
            'underline'   => $ad->underline === 1,
            'audience'    => $this->audienceLabels()[$ad->view] ?? '',
            'layout'      => $this->layoutLabels()[$ad->layout] ?? '',
            'direct_link' => $ad->show === 1,
            'hits'        => $ad->count,
            'hit_limit'   => $ad->count_link,
            'started_at'  => $this->dateFormatter->format($ad->time),
            'expires_at'  => $expiresAt === null ? '' : $this->dateFormatter->format($expiresAt),
            'status'      => $this->status($ad),
        ];
    }

    private function status(Ad $ad): string
    {
        if ($ad->isExhausted(time())) {
            return self::STATUS_EXHAUSTED;
        }

        return $ad->isHidden() ? self::STATUS_HIDDEN : self::STATUS_SHOWN;
    }

    /** @return array<int, string> */
    private function layoutLabels(): array
    {
        return [__('All pages'), __('Only on Homepage'), __('On all, except Homepage')];
    }

    /**
     * @return array<int, string>
     */
    private function audienceLabels(): array
    {
        return [__('Everyone'), __('Guests'), __('Users')];
    }
}
