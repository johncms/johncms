<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Events\AuthEventType;
use Johncms\Modules\Admin\Domain\Enums\AuthLogPeriod;
use Johncms\Modules\Admin\Domain\ValueObjects\AuthLogFilter;

/**
 * The filter of the sign-in log and the query string of its address, both ways.
 *
 *     q       a part of a nickname, an account number, an IP address
 *     event   the key of an event the core knows
 *     period  day | week | month
 *     user    the number of an account, as its page links here
 */
final readonly class AuthLogFilterMapper
{
    private const SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query Anything that is not one of the values above reads as
     *                                    its default. Only a key the core knows is accepted as the
     *                                    event: an arbitrary string has no business in the query.
     */
    public function fromQuery(array $query): AuthLogFilter
    {
        $user = $this->string($query, 'user');

        return new AuthLogFilter(
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            event: AuthEventType::tryFrom($this->string($query, 'event'))?->value,
            period: AuthLogPeriod::tryFrom($this->string($query, 'period')) ?? AuthLogPeriod::ANY,
            userId: ctype_digit($user) && (int) $user > 0 ? (int) $user : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(AuthLogFilter $filter): array
    {
        $query = [];

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        if ($filter->event !== null) {
            $query['event'] = $filter->event;
        }

        if ($filter->period !== AuthLogPeriod::ANY) {
            $query['period'] = $filter->period->value;
        }

        if ($filter->userId !== null) {
            $query['user'] = (string) $filter->userId;
        }

        return $query;
    }

    public function url(string $path, AuthLogFilter $filter): string
    {
        $query = http_build_query($this->toQuery($filter));

        return $path . ($query !== '' ? '?' . $query : '');
    }

    /**
     * @param array<string, mixed> $query
     */
    private function string(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
