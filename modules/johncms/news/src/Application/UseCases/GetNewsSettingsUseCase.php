<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Application\DTO\NewsSettingsDTO;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;

/**
 * The settings of the news as the form shows them; a key the configuration lacks reads as empty.
 */
final readonly class GetNewsSettingsUseCase
{
    public function __construct(
        private NewsConfigRepositoryInterface $config,
    ) {
    }

    public function execute(): NewsSettingsDTO
    {
        $settings = $this->config->getSettings();
        $text = static fn (string $key): string => is_scalar($settings[$key] ?? null) ? (string) $settings[$key] : '';

        return new NewsSettingsDTO(
            homepageShow: (bool) ($settings['homepage_show'] ?? false),
            homepageQuantity: (int) ($settings['homepage_quantity'] ?? 3),
            homepageDays: (int) ($settings['homepage_days'] ?? 0),
            title: $text('title'),
            metaKeywords: $text('meta_keywords'),
            metaDescription: $text('meta_description'),
            sectionTitle: $text('section_title'),
            sectionMetaKeywords: $text('section_meta_keywords'),
            sectionMetaDescription: $text('section_meta_description'),
            articleTitle: $text('article_title'),
            articleMetaKeywords: $text('article_meta_keywords'),
            articleMetaDescription: $text('article_meta_description'),
        );
    }
}
