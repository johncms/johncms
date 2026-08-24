<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\DurationFormatter;

/**
 * Готовит строку рекламной ссылки для шаблона списка: вычисляет условия договора
 * (показы/дни), остаток, оформление и человекочитаемые подписи мест показа.
 */
final readonly class AdRowMapper
{
    public function __construct(
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * @param iterable<Ad> $ads
     * @return array<int, array<string, mixed>>
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
        return [
            'id'           => $ad->id,
            'link'         => $ad->link,
            'name'         => str_replace('|', '; ', $ad->name),
            'active'       => $ad->to === 0,
            'direct_link'  => $ad->show === 1,
            'display_time' => $this->dateFormatter->format($ad->time),
            'place'        => $this->placeLabels()[$ad->layout] ?? '',
            'show_for'     => $this->audienceLabels()[$ad->view] ?? '',
            'agreement'    => $this->agreement($ad),
            'remains'      => $this->remains($ad),
            'styles'       => $this->styles($ad),
        ];
    }

    private function agreement(Ad $ad): string
    {
        $parts = [];
        if ($ad->count_link > 0) {
            $parts[] = $ad->count_link . ' ' . __('hits');
        }
        if ($ad->day > 0) {
            $parts[] = DurationFormatter::format($ad->day * 86400);
        }

        return implode(', ', $parts);
    }

    private function remains(Ad $ad): string
    {
        $parts = [];
        if ($ad->count_link > 0) {
            $left = $ad->count_link - $ad->count;
            if ($left > 0) {
                $parts[] = $left . ' ' . __('hits');
            }
        }
        if ($ad->day > 0) {
            $left = $ad->day * 86400 - (time() - $ad->time);
            if ($left > 0) {
                $parts[] = DurationFormatter::format($left);
            }
        }

        return implode(', ', $parts);
    }

    private function styles(Ad $ad): string
    {
        $parts = [];
        if ($ad->color !== '') {
            $parts[] = __('Color:') . ' ' . $ad->color;
        }
        if ($ad->bold) {
            $parts[] = __('Bold');
        }
        if ($ad->italic) {
            $parts[] = __('Italic');
        }
        if ($ad->underline) {
            $parts[] = __('Underline');
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<int, string>
     */
    private function placeLabels(): array
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
