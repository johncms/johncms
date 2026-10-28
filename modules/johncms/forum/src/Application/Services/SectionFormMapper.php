<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Application\DTO\SectionFormDTO;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

/**
 * The form of a section of the forum as the section holds it, and what forum::admin/section-form
 * draws of it: the values, the places in the tree it can go to, the choices of what it holds and
 * of who writes in it.
 */
final readonly class SectionFormMapper
{
    public function __construct(
        private ForumTreeOptions $treeOptions,
    ) {
    }

    public function fromSection(ForumSection $section): SectionFormDTO
    {
        return new SectionFormDTO(
            parentId: (int) $section->parent,
            name: $section->name,
            description: (string) $section->description,
            sort: $section->sort,
            sectionType: (int) $section->section_type,
            access: (int) $section->access,
            metaKeywords: (string) $section->meta_keywords,
            metaDescription: (string) $section->meta_description,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(SectionFormDTO $form): array
    {
        return [
            'parent'           => $form->parentId,
            'name'             => $form->name,
            'description'      => $form->description,
            'sort'             => $form->sort,
            'section_type'     => $form->sectionType,
            'access'           => $form->access,
            'meta_keywords'    => $form->metaKeywords,
            'meta_description' => $form->metaDescription,
        ];
    }

    /**
     * The root and every section that holds sections, by its path; the section itself and those
     * under it are left out, as a section cannot go under itself.
     *
     * @param Collection<int, ForumSection> $sections Every section, in the order of sort.
     * @return list<array{id: int, title: string}>
     */
    public function parentOptions(Collection $sections, ?int $sectionId): array
    {
        return $this->treeOptions->sectionHolders($sections, $sectionId);
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    public function typeOptions(): array
    {
        return [
            ['value' => ForumTreeOptions::TYPE_SECTIONS, 'label' => __('Sections'), 'hint' => __('A folder for other sections; no topics are started in it.')],
            ['value' => ForumTreeOptions::TYPE_TOPICS, 'label' => __('Topics'), 'hint' => __('Members start topics in it.')],
        ];
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    public function accessOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('Common access'), 'hint' => __('Members write as everywhere on the forum.')],
            ['value' => 1, 'label' => __('Authors are curators'), 'hint' => __('The author of a new topic becomes its curator.')],
            ['value' => 2, 'label' => __('Authors edit the first post'), 'hint' => __('The author of a topic can edit its first post at any time, not only while it is the last.')],
            ['value' => 4, 'label' => __('Only for reading'), 'hint' => __('Nobody but the moderators writes in it.')],
        ];
    }

    /**
     * What a saved section is beyond its form.
     *
     * @return array<string, mixed>
     */
    public function facts(ForumSection $section, int $sections, int $topics): array
    {
        return [
            'id'       => $section->id,
            'slug'     => (string) $section->slug,
            'sections' => $sections,
            'topics'   => $topics,
        ];
    }
}
