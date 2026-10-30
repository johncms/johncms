<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Domain\Models\ContentCollection;

/**
 * The head shared by the pages of one collection: its name, where it is on the site, and a tab
 * for each part of it — the items, the sections, the fields and the settings.
 */
final readonly class CollectionWorkspaceMapper
{
    public const string ITEMS = 'items';
    public const string SECTIONS = 'sections';
    public const string FIELDS = 'fields';
    public const string SETTINGS = 'settings';

    public static function url(int $collectionId, string $tab = self::ITEMS): string
    {
        return '/admin/collections/' . $collectionId . '/' . $tab;
    }

    /**
     * @param ContentCollection $collection Loaded with the counts of the repository (findWithCounts).
     * @return array<string, mixed>
     */
    public function head(ContentCollection $collection, string $active): array
    {
        $hasSections = ! empty($collection->settings['has_sections']);
        $tabs = [
            [self::ITEMS, __('Items'), (int) $collection->getAttribute('items_count')],
            [self::SECTIONS, __('Sections'), (int) $collection->getAttribute('sections_count')],
            [self::FIELDS, __('Fields'), (int) $collection->getAttribute('fields_count')],
            [self::SETTINGS, __('Settings'), null],
        ];

        return [
            'id'         => $collection->id,
            'name'       => $collection->name,
            'code'       => $collection->code,
            'active'     => $collection->active,
            'public'     => $collection->public,
            'public_url' => $collection->publicUrl(),
            'tabs'       => array_values(array_map(
                static fn (array $tab): array => [
                    'title'  => $tab[1],
                    'url'    => self::url($collection->id, $tab[0]),
                    'active' => $tab[0] === $active,
                    'count'  => $tab[2],
                ],
                // The sections are shown only to a collection that uses them.
                array_filter($tabs, static fn (array $tab): bool => $tab[0] !== self::SECTIONS || $hasSections)
            )),
        ];
    }
}
