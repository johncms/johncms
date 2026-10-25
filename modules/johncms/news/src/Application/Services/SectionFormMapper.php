<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use Johncms\Modules\News\Application\DTO\SectionFormDTO;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Services\SectionTree;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

/**
 * The form of a section as the section holds it, and what news::admin/section-form draws of it:
 * the values, the places in the tree it can go to and the facts about a saved section.
 */
final readonly class SectionFormMapper
{
    public function fromSection(NewsSection $section): SectionFormDTO
    {
        return new SectionFormDTO(
            parentId: (int) $section->parent,
            name: $section->name,
            code: $section->code,
            keywords: (string) $section->keywords,
            description: (string) $section->description,
            text: (string) $section->text,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(SectionFormDTO $form): array
    {
        return [
            'parent'      => $form->parentId,
            'name'        => $form->name,
            'code'        => $form->code,
            'keywords'    => $form->keywords,
            'description' => $form->description,
            'text'        => $form->text,
        ];
    }

    /**
     * The root and every section by its path, the section itself and those under it left out: a
     * section cannot go under itself.
     *
     * @return list<array{id: int, title: string}>
     */
    public function parentOptions(SectionTree $tree, ?int $sectionId): array
    {
        $own = $sectionId !== null ? $tree->subtreeOf($sectionId) : [];
        $options = [['id' => NewsListFilter::ROOT, 'title' => __('Root')]];

        foreach ($tree->ordered() as $id) {
            if (! in_array($id, $own, true)) {
                $options[] = ['id' => $id, 'title' => implode(' › ', $tree->pathOf($id))];
            }
        }

        return $options;
    }

    /**
     * What a saved section is beyond its form.
     *
     * @return array<string, int>
     */
    public function facts(NewsSection $section): array
    {
        return [
            'id'      => $section->id,
            'created' => $this->timestamp($section->getRawOriginal('created_at')),
            'updated' => $this->timestamp($section->getRawOriginal('updated_at')),
        ];
    }

    private function timestamp(mixed $raw): int
    {
        $timestamp = is_string($raw) && $raw !== '' ? strtotime($raw) : false;

        return $timestamp !== false ? $timestamp : 0;
    }
}
