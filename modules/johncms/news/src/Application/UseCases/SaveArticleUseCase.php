<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Johncms\Modules\News\Application\DTO\ArticleFormDTO;
use Johncms\Modules\News\Application\Exceptions\ArticleFormException;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use Johncms\Validator\ValidatorInterface;

/**
 * Adds an article or changes one, from the form of the panel.
 *
 * The code is made into a slug — of the name when none is given — and has to be free in the
 * section the article stands in. The text is kept as it was typed, the empty paragraphs the editor
 * leaves at its edges trimmed, and escaped where it is printed. The search reads a text of its own,
 * written along with the article.
 */
final readonly class SaveArticleUseCase
{
    public const DATE_FORMAT = 'd.m.Y H:i';

    public function __construct(
        private NewsArticleRepositoryInterface $articles,
        private GetSectionTreeUseCase $getTree,
        private ValidatorInterface $validator,
        private EditorContentNormalizer $normalizer,
    ) {
    }

    /**
     * @throws ArticleFormException
     */
    public function create(ArticleFormDTO $form, int $authorId): NewsArticle
    {
        $attributes = $this->validated($form, null);
        $attributes['created_by'] = $authorId;
        $attributes['attached_files'] = $form->attachedFiles;

        $article = $this->articles->create($attributes);
        $this->articles->index($article->id, $this->searchText($attributes));

        return $article;
    }

    /**
     * @throws ArticleFormException
     */
    public function update(NewsArticle $article, ArticleFormDTO $form, int $editorId): void
    {
        $attributes = $this->validated($form, $article->id);
        $attributes['updated_by'] = $editorId;
        // A file uploaded while the form was open joins those the article has.
        $attributes['attached_files'] = array_values(array_unique(array_merge((array) $article->attached_files, $form->attachedFiles)));

        $this->articles->update($article, $attributes);
        $this->articles->index($article->id, $this->searchText($attributes));
    }

    /**
     * @return array<string, mixed>
     * @throws ArticleFormException
     */
    private function validated(ArticleFormDTO $form, ?int $articleId): array
    {
        $fields = [
            'name'        => trim($form->name),
            'page_title'  => trim($form->pageTitle),
            'code'        => Str::slug(trim($form->code) !== '' ? trim($form->code) : trim($form->name)),
            'keywords'    => trim($form->keywords),
            'description' => trim($form->description),
            'tags'        => trim($form->tags),
        ];

        $result = $this->validator->validate($fields, [
            'name'        => [new StringLength(max: 255)],
            'page_title'  => [new StringLength(max: 255, allowEmpty: true)],
            'code'        => [new StringLength(max: 255)],
            'keywords'    => [new StringLength(max: 255, allowEmpty: true)],
            'description' => [new StringLength(max: 255, allowEmpty: true)],
            'tags'        => [new StringLength(max: 255, allowEmpty: true)],
        ]);

        $activeFrom = $this->date($form->activeFrom);
        $activeTo = $this->date($form->activeTo);
        $result = $this->dateErrors($result, 'active_from', $form->activeFrom, $activeFrom);
        $result = $this->dateErrors($result, 'active_to', $form->activeTo, $activeTo);

        $sectionId = $form->sectionId;
        if ($sectionId !== NewsListFilter::ROOT && ! $this->getTree->execute()->has($sectionId)) {
            $result = $result->withError('section_id', __('Choose one of the sections'));
        }

        if (! $result->hasError('code') && ! $result->hasError('section_id') && $this->articles->isCodeTaken($fields['code'], $sectionId, $articleId)) {
            $result = $result->withError('code', __('An article with this code already exists'));
        }

        $result->throwIfInvalid(static fn (array $errors): ArticleFormException => new ArticleFormException($errors));

        return $fields + [
            'active'       => $form->active,
            'section_id'   => $sectionId,
            'active_from'  => $activeFrom?->format('Y-m-d H:i:s'),
            'active_to'    => $activeTo?->format('Y-m-d H:i:s'),
            'preview_text' => $this->normalizer->trimEdgeEmptyBlocks(trim($form->previewText)),
            'text'         => $this->normalizer->trimEdgeEmptyBlocks(trim($form->text)),
        ];
    }

    private function date(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value);

        return $date !== false && $date->format(self::DATE_FORMAT) === $value ? $date : null;
    }

    private function dateErrors(ValidationResult $result, string $field, string $value, ?DateTimeImmutable $date): ValidationResult
    {
        return trim($value) !== '' && $date === null
            ? $result->withError($field, __('Enter the date as dd.mm.yyyy hh:mm'))
            : $result;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function searchText(array $attributes): string
    {
        return $attributes['name'] . strip_tags(' ' . $attributes['preview_text'] . ' ' . $attributes['text']);
    }
}
