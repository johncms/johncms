<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

/**
 * The rows of the list of the items of a collection, as collections::admin/items-table draws
 * them: what an item is, where it stands, whether the site shows it, the values of the fields
 * shown as columns, and what can be done to it.
 */
final readonly class ItemListRowMapper
{
    private const int TEXT_LENGTH = 60;

    /**
     * @param Collection<int, ContentCollectionItem> $items  Loaded with their values and fields.
     * @param list<ContentCollectionField>           $fields The fields shown as columns.
     * @param string                                 $listUrl The page of the list the rows are on,
     *                                                        for a deleting to return to.
     * @return list<array<string, mixed>>
     */
    public function rows(Collection $items, array $fields, SectionTree $tree, ContentCollection $collection, string $listUrl, ?DateTimeInterface $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $base = CollectionWorkspaceMapper::url($collection->id);
        $publicBase = $collection->publicUrl();
        $rows = [];

        foreach ($items as $item) {
            $status = ItemStatus::of($item->active, $item->active_from, $item->active_to, $now);
            $values = $item->getValuesMap();
            $editUrl = $base . '/' . $item->id . '/edit';
            $section = $item->section_id;

            $actions = [['method' => 'get', 'url' => $editUrl, 'title' => __('Edit'), 'icon' => 'pencil', 'danger' => false, 'dialog' => false, 'fields' => []]];
            if ($publicBase !== '' && $status === ItemStatus::PUBLISHED) {
                $path = $section !== null ? $tree->codePathOf($section) : [];
                $actions[] = [
                    'method' => 'get',
                    'url'    => $publicBase . ($path !== [] ? '/' . implode('/', $path) : '') . '/' . $item->code . '.html',
                    'title'  => __('Open on the site'),
                    'icon'   => 'external-link',
                    'danger' => false,
                    'dialog' => false,
                    'fields' => [],
                ];
            }
            $actions[] = [
                'method' => 'get',
                'url'    => $base . '/' . $item->id . '/delete?back=' . rawurlencode($listUrl),
                'title'  => __('Delete'),
                'icon'   => 'trash-2',
                'danger' => true,
                'dialog' => true,
                'fields' => [],
            ];

            $rows[] = [
                'id'           => $item->id,
                'name'         => $item->name,
                'code'         => $item->code,
                'edit_url'     => $editUrl,
                'section'      => $section !== null ? implode(' › ', $tree->pathOf($section)) : '',
                'status'       => $status->value,
                'status_title' => ItemListControlsMapper::statusTitle($status),
                'values'       => array_map(fn (ContentCollectionField $field): array => $this->value($field, $values[$field->code] ?? null), $fields),
                'updated'      => $this->timestamp($item->getRawOriginal('updated_at')),
                'actions'      => $actions,
            ];
        }

        return $rows;
    }

    /**
     * A value as a cell shows it: kind (text, number, yes_no, date, empty) and the value itself.
     *
     * @return array{kind: string, value: mixed, with_time?: bool}
     */
    private function value(ContentCollectionField $field, mixed $value): array
    {
        if (is_array($value)) {
            $parts = array_filter(array_map(fn (mixed $one): string => (string) $this->value($field, $one)['value'], $value), static fn (string $part): bool => $part !== '');

            return $parts === [] ? ['kind' => 'empty', 'value' => ''] : ['kind' => 'text', 'value' => implode(', ', $parts)];
        }

        if ($value === null || $value === '') {
            return ['kind' => 'empty', 'value' => ''];
        }

        return match ($field->type) {
            FieldType::Boolean                  => ['kind' => 'yes_no', 'value' => (bool) $value],
            FieldType::Integer, FieldType::Double => ['kind' => 'number', 'value' => $this->number($value)],
            FieldType::Date, FieldType::Datetime => ['kind' => 'date', 'value' => $this->timestamp($value), 'with_time' => $field->type === FieldType::Datetime],
            FieldType::Text, FieldType::Html    => ['kind' => 'text', 'value' => $this->shorten(strip_tags((string) $value))],
            default                             => ['kind' => 'text', 'value' => $this->shorten((string) $value)],
        };
    }

    /**
     * A number in full, grouped by thousands, with the fraction it has: a price is not shortened
     * to "75K".
     */
    private function number(mixed $value): string
    {
        $number = (float) $value;
        if ($number === floor($number)) {
            return number_format($number, 0, ',', "\u{00A0}");
        }

        // Up to two digits of the fraction, without the zeros at its end: 1,5 rather than 1,50.
        return rtrim(rtrim(number_format($number, 2, ',', "\u{00A0}"), '0'), ',');
    }

    private function shorten(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return mb_strlen($text) > self::TEXT_LENGTH ? mb_substr($text, 0, self::TEXT_LENGTH - 1) . '…' : $text;
    }

    private function timestamp(mixed $raw): int
    {
        if (! is_string($raw) || $raw === '') {
            return 0;
        }

        $time = strtotime($raw);

        return $time === false ? 0 : $time;
    }
}
