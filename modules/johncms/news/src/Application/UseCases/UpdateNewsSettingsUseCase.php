<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\News\Application\DTO\NewsSettingsDTO;
use Johncms\Modules\News\Application\Exceptions\NewsSettingsException;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Saves the settings of the news: the block of the home page and the meta tags of the pages of the
 * module. The text is kept as it was typed and escaped where it is printed.
 */
final readonly class UpdateNewsSettingsUseCase
{
    public const MAX_QUANTITY = 50;
    public const MAX_DAYS = 3650;

    public function __construct(
        private NewsConfigRepositoryInterface $config,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws NewsSettingsException
     * @throws ConfigWriteException
     */
    public function execute(NewsSettingsDTO $settings): void
    {
        $values = [
            'homepage_show'            => $settings->homepageShow,
            'homepage_quantity'        => $settings->homepageQuantity,
            'homepage_days'            => $settings->homepageDays,
            'title'                    => trim($settings->title),
            'meta_keywords'            => trim($settings->metaKeywords),
            'meta_description'         => trim($settings->metaDescription),
            'section_title'            => trim($settings->sectionTitle),
            'section_meta_keywords'    => trim($settings->sectionMetaKeywords),
            'section_meta_description' => trim($settings->sectionMetaDescription),
            'article_title'            => trim($settings->articleTitle),
            'article_meta_keywords'    => trim($settings->articleMetaKeywords),
            'article_meta_description' => trim($settings->articleMetaDescription),
        ];

        $title = [new StringLength(max: 250, allowEmpty: true)];
        $keywords = [new StringLength(max: 500, allowEmpty: true)];
        $description = [new StringLength(max: 1000, allowEmpty: true)];

        $this->validator->validate($values, [
            'homepage_quantity'        => [new Between(min: 1, max: self::MAX_QUANTITY)],
            'homepage_days'            => [new Between(min: 0, max: self::MAX_DAYS)],
            'title'                    => $title,
            'meta_keywords'            => $keywords,
            'meta_description'         => $description,
            'section_title'            => $title,
            'section_meta_keywords'    => $keywords,
            'section_meta_description' => $description,
            'article_title'            => $title,
            'article_meta_keywords'    => $keywords,
            'article_meta_description' => $description,
        ])->throwIfInvalid(static fn (array $errors): NewsSettingsException => new NewsSettingsException($errors));

        $this->config->saveSettings($values);
    }
}
