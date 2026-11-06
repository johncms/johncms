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

    public function __construct(
        private ?CollectionOwnerUrls $ownerUrls = null,
        private CollectionApiMapper $apiMapper = new CollectionApiMapper(),
        private ?\Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase $access = null,
        private ?\Johncms\Modules\ModuleRegistry $modules = null,
        private CollectionWorkspaceSettings $configuration = new CollectionWorkspaceSettings(),
    ) {
    }

    public static function url(int $collectionId, string $tab = self::ITEMS): string
    {
        return '/admin/collections/' . $collectionId . '/' . $tab;
    }

    public static function returnUrl(int $collectionId, string $back): string
    {
        $list = self::url($collectionId);
        if (preg_match('/[\x00-\x20\\\\]/', $back)) {
            return $list;
        }
        return $back === $list || str_starts_with($back, $list . '?') ? $back : $list;
    }

    /**
     * @param ContentCollection $collection Loaded with the counts of the repository (findWithCounts).
     * @return array<string, mixed>
     */
    public function head(ContentCollection $collection, string $active): array
    {
        $hasSections = ! empty($collection->settings['has_sections']);
        $tabs = [
            [self::ITEMS, $this->labels($collection)['plural'], (int) $collection->getAttribute('items_count')],
            [self::SECTIONS, __('Sections'), (int) $collection->getAttribute('sections_count')],
            [self::FIELDS, __('Fields'), (int) $collection->getAttribute('fields_count')],
            [self::SETTINGS, __('Settings'), null],
        ];

        if ($this->access !== null && ! $this->access->canManageSchema()) {
            $tabs = array_values(array_filter($tabs, static fn (array $tab): bool => ! in_array($tab[0], [self::FIELDS, self::SETTINGS], true)));
        }
        if ($this->access !== null && ! $this->access->allowsAction($collection, 'sections')) {
            $tabs = array_values(array_filter($tabs, static fn (array $tab): bool => $tab[0] !== self::SECTIONS));
        }
        $ownerSettings = $this->ownerUrls?->settings($this->apiMapper->collection($collection));
        $mappedTabs = array_values(array_map(
            static fn (array $tab): array => [
                'title' => $tab[1], 'url' => self::url($collection->id, $tab[0]),
                'active' => $tab[0] === $active, 'count' => $tab[2],
            ],
            array_filter($tabs, static fn (array $tab): bool => $tab[0] !== self::SECTIONS || $hasSections)
        ));
        if ($ownerSettings !== null) {
            $mappedTabs[] = ['title' => d__('collections', 'Module settings'), 'url' => $ownerSettings, 'active' => false, 'count' => null];
        }
        return [
            'id'          => $collection->id,
            'name'       => $collection->name,
            'code'       => $collection->code,
            'active'     => $collection->active,
            'public'     => $collection->public,
            'public_url' => $collection->owner_module !== null && $collection->active ? ($this->ownerUrls?->collection($this->apiMapper->collection($collection)) ?? '') : $collection->publicUrl(),
            'tabs'       => $mappedTabs,
        ];
    }

    /** @return array<string, string> */
    public function labels(ContentCollection $collection): array
    {
        return $this->configuration->labels($collection);
    }

    /** @return array<string, bool> */
    public function menu(ContentCollection $collection): array
    {
        $owner = $collection->owner_module;
        $alias = $owner !== null ? ($this->modules?->enabled()[$owner]->alias ?? null) : null;
        return $alias !== null ? [$alias => true, self::url($collection->id) => true] : [($this->canManageSchema() ? 'collections' : self::url($collection->id)) => true];
    }

    public function canManageSchema(): bool
    {
        return $this->access?->canManageSchema() ?? true;
    }
}
