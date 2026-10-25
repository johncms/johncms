<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Illuminate\Support\Str;
use Johncms\Modules\News\Application\DTO\SectionFormDTO;
use Johncms\Modules\News\Application\Exceptions\SectionFormException;
use Johncms\Modules\News\Application\Section;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Adds a section or changes one, from the form of the panel.
 *
 * The code is made into a slug — of the name when none is given — and has to be free under the
 * parent. A section goes under the root or any section but itself and those under it. The tree is
 * cached for the addresses of the site, so every save drops the cache.
 */
final readonly class SaveSectionUseCase
{
    public function __construct(
        private NewsSectionRepositoryInterface $sections,
        private GetSectionTreeUseCase $getTree,
        private ValidatorInterface $validator,
        private Section $sectionCache,
    ) {
    }

    /**
     * @throws SectionFormException
     */
    public function create(SectionFormDTO $form): NewsSection
    {
        $section = $this->sections->create($this->validated($form, null));
        $this->sectionCache->clearCache();

        return $section;
    }

    /**
     * @throws SectionFormException
     */
    public function update(NewsSection $section, SectionFormDTO $form): void
    {
        $this->sections->update($section, $this->validated($form, $section->id));
        $this->sectionCache->clearCache();
    }

    /**
     * @return array<string, mixed>
     * @throws SectionFormException
     */
    private function validated(SectionFormDTO $form, ?int $sectionId): array
    {
        $fields = [
            'name'        => trim($form->name),
            'code'        => Str::slug(trim($form->code) !== '' ? trim($form->code) : trim($form->name)),
            'keywords'    => trim($form->keywords),
            'description' => trim($form->description),
            'text'        => trim($form->text),
        ];

        $result = $this->validator->validate($fields, [
            'name'        => [new StringLength(max: 255)],
            'code'        => [new StringLength(max: 255)],
            'keywords'    => [new StringLength(max: 255, allowEmpty: true)],
            'description' => [new StringLength(max: 255, allowEmpty: true)],
            'text'        => [new StringLength(max: 65000, allowEmpty: true)],
        ]);

        $parentId = $form->parentId;
        if ($parentId !== NewsListFilter::ROOT) {
            $tree = $this->getTree->execute();
            $own = $sectionId !== null ? $tree->subtreeOf($sectionId) : [];

            if (! $tree->has($parentId) || in_array($parentId, $own, true)) {
                $result = $result->withError('parent', __('Choose one of the sections, not this one or a section under it'));
            }
        }

        if (! $result->hasError('code') && ! $result->hasError('parent') && $this->sections->isCodeTaken($fields['code'], $parentId, $sectionId)) {
            $result = $result->withError('code', __('A section with this code already exists'));
        }

        $result->throwIfInvalid(static fn (array $errors): SectionFormException => new SectionFormException($errors));

        return $fields + ['parent' => $parentId];
    }
}
