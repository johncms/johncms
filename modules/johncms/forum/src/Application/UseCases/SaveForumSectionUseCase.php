<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\SectionFormDTO;
use Johncms\Modules\Forum\Application\Exceptions\SectionFormException;
use Johncms\Modules\Forum\Application\Services\ForumSlugGenerator;
use Johncms\Modules\Forum\Application\Services\ForumTreeOptions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use Johncms\Validator\ValidatorInterface;

/**
 * Adds a section of the forum or changes one, from the form of the panel.
 *
 * A section goes under the root or under a section that holds sections, never under itself or a
 * section of its own. Its address is made of the name and kept free under the parent. A section
 * with an empty order, and one moved to another parent, goes to the end there; the files of a
 * moved section follow it. A section that holds something keeps the kind of what it holds.
 */
final readonly class SaveForumSectionUseCase
{
    /** The ways a section can be written in, by the value of its access column. */
    public const array ACCESS = [0, 1, 2, 4];

    public function __construct(
        private ForumStructureRepositoryInterface $repository,
        private ForumSlugGenerator $slugGenerator,
        private ForumTreeOptions $treeOptions,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws SectionFormException
     */
    public function create(SectionFormDTO $form): ForumSection
    {
        $fields = $this->validated($form, null);
        $fields['slug'] = $this->slugGenerator->generate($fields['name'], $form->parentId);
        $fields['sort'] = $form->sort ?? $this->repository->nextSort($form->parentId);

        return $this->repository->create($fields);
    }

    /**
     * @throws SectionFormException
     */
    public function update(ForumSection $section, SectionFormDTO $form): void
    {
        $fields = $this->validated($form, $section);
        $oldParent = (int) $section->parent;
        $moved = $form->parentId !== $oldParent;

        $fields['slug'] = $this->slugGenerator->generate($fields['name'], $form->parentId, $section->id);
        $fields['sort'] = $moved || $form->sort === null ? $this->repository->nextSort($form->parentId) : $form->sort;

        if ($moved) {
            $this->repository->moveSectionFiles($section->id, $oldParent, $form->parentId);
        }

        $this->repository->update($section, $fields);
    }

    /**
     * @return array<string, mixed>
     * @throws SectionFormException
     */
    private function validated(SectionFormDTO $form, ?ForumSection $section): array
    {
        $fields = [
            'name'             => trim($form->name),
            'description'      => trim($form->description),
            'meta_keywords'    => trim($form->metaKeywords),
            'meta_description' => trim($form->metaDescription),
        ];

        $result = $this->validator->validate($fields, [
            'name'             => [new StringLength(min: 2, max: 150)],
            'description'      => [new StringLength(max: 500, allowEmpty: true)],
            'meta_keywords'    => [new StringLength(max: 255, allowEmpty: true)],
            'meta_description' => [new StringLength(max: 500, allowEmpty: true)],
        ]);

        if (! in_array($form->sectionType, [ForumTreeOptions::TYPE_SECTIONS, ForumTreeOptions::TYPE_TOPICS], true)) {
            $result = $result->withError('section_type', __('Choose what the section holds'));
        }

        if (! in_array($form->access, self::ACCESS, true)) {
            $result = $result->withError('access', __('Choose who writes in the section'));
        }

        $result = $this->checkParent($result, $form->parentId, $section);

        if ($section !== null) {
            $result = $this->checkType($result, $section, $form->sectionType);
        }

        $result->throwIfInvalid(static fn (array $errors): SectionFormException => new SectionFormException($errors));

        return $fields + [
            'parent'       => $form->parentId,
            'section_type' => $form->sectionType,
            'access'       => $form->access,
        ];
    }

    private function checkParent(ValidationResult $result, int $parentId, ?ForumSection $section): ValidationResult
    {
        $allowed = array_column($this->treeOptions->sectionHolders($this->repository->tree(), $section?->id), 'id');

        return in_array($parentId, $allowed, true)
            ? $result
            : $result->withError('parent', __('Choose a section that holds sections, not this one or a section under it'));
    }

    /**
     * Sections and topics cannot share a section, so what a section holds decides its kind.
     */
    private function checkType(ValidationResult $result, ForumSection $section, int $type): ValidationResult
    {
        if ($type === (int) $section->section_type) {
            return $result;
        }

        if ($type === ForumTreeOptions::TYPE_TOPICS && $this->repository->countChildSections($section->id) > 0) {
            return $result->withError('section_type', __('The section holds sections: move them out before it can hold topics'));
        }

        if ($type === ForumTreeOptions::TYPE_SECTIONS && $this->repository->countTopics($section->id) > 0) {
            return $result->withError('section_type', __('The section holds topics: move them out before it can hold sections'));
        }

        return $result;
    }
}
