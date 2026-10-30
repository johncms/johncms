<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Domain\Models\Counter;

/**
 * The rows of the list of the counters, as admin::counters draws them: where the site prints a
 * counter, whether it waits for the cookie consent, and what can be done to it.
 */
final readonly class CounterRowMapper
{
    private const string URL = '/admin/counters';

    /**
     * @param iterable<Counter> $counters In the order of the site.
     * @return list<array<string, mixed>>
     */
    public function rows(iterable $counters): array
    {
        $counters = is_array($counters) ? $counters : iterator_to_array($counters, false);
        $last = count($counters) - 1;
        $rows = [];

        foreach (array_values($counters) as $position => $counter) {
            $base = self::URL . '/' . $counter->id;
            $enabled = $counter->switch === 1;

            $actions = [$this->action('get', $base . '/edit', __('Edit'), 'pencil')];
            if ($position > 0) {
                $actions[] = $this->action('post', $base . '/up', __('Move up'), 'arrow-up');
            }
            if ($position < $last) {
                $actions[] = $this->action('post', $base . '/down', __('Move down'), 'arrow-down');
            }
            $actions[] = $this->action('post', $base . '/toggle', $enabled ? __('Turn off') : __('Turn on'), $enabled ? 'eye-off' : 'eye', fields: ['enabled' => $enabled ? 0 : 1]);
            $actions[] = $this->action('get', $base . '/delete', __('Delete'), 'trash-2', danger: true, dialog: true);

            $rows[] = [
                'id'             => $counter->id,
                'name'           => $counter->name,
                'edit_url'       => $base . '/edit',
                'where'          => self::where($counter),
                'enabled'        => $enabled,
                'cookie_consent' => $counter->require_cookie_consent === 1,
                'actions'        => $actions,
            ];
        }

        return $rows;
    }

    /**
     * Where the site prints the counter, worked out from its mode and the codes it has.
     */
    public static function where(Counter $counter): string
    {
        return match ($counter->mode) {
            2, 3    => __('On every page'),
            default => trim($counter->link2) === ''
                ? __('Only on the homepage')
                : __('On every page, its own code on the homepage'),
        };
    }

    /**
     * @param array<string, int> $fields
     * @return array<string, mixed>
     */
    private function action(string $method, string $url, string $title, string $icon, bool $danger = false, bool $dialog = false, array $fields = []): array
    {
        return ['method' => $method, 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => $dialog, 'fields' => $fields];
    }
}
