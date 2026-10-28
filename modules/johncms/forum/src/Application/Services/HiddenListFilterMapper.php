<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;

/**
 * The filter of the list of the hidden content of the forum and the query string of its address,
 * both ways.
 *
 *     tab      topics — the topics; the posts by default
 *     q        a part of the name of the author or of the topic
 *     author   the number of an account
 *     topic    the number of a topic, of the posts
 *     section  the number of a section, of the topics
 *     by       the name of who hid it
 */
final readonly class HiddenListFilterMapper
{
    public const string PATH = '/admin/forum/hidden';

    /** Where emptying the list is confirmed and done, with the same filter. */
    public const string PURGE_PATH = '/admin/forum/hidden/purge';

    private const int SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query Anything that is not one of the values above reads as
     *                                    its default.
     */
    public function fromQuery(array $query): HiddenListFilter
    {
        $tab = HiddenListTab::tryFrom($this->string($query, 'tab')) ?? HiddenListTab::POSTS;
        $hiddenBy = trim($this->string($query, 'by'));

        return new HiddenListFilter(
            tab: $tab,
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            author: $this->number($query, 'author'),
            topic: $tab === HiddenListTab::POSTS ? $this->number($query, 'topic') : null,
            section: $tab === HiddenListTab::TOPICS ? $this->number($query, 'section') : null,
            hiddenBy: $hiddenBy !== '' ? $hiddenBy : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(HiddenListFilter $filter): array
    {
        $query = [];

        if ($filter->tab !== HiddenListTab::POSTS) {
            $query['tab'] = $filter->tab->value;
        }

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        foreach (['author' => $filter->author, 'topic' => $filter->topic, 'section' => $filter->section] as $key => $value) {
            if ($value !== null) {
                $query[$key] = (string) $value;
            }
        }

        if ($filter->hiddenBy !== null) {
            $query['by'] = $filter->hiddenBy;
        }

        return $query;
    }

    public function url(HiddenListFilter $filter, string $path = self::PATH): string
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

    /**
     * @param array<string, mixed> $query
     */
    private function number(array $query, string $key): ?int
    {
        $value = $this->string($query, $key);

        return ctype_digit($value) ? (int) $value : null;
    }
}
