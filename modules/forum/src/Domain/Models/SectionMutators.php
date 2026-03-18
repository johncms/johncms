<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Models;

use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;

/**
 * Trait SectionMutators
 *
 * @package Forum\Models
 * @property string $calculated_meta_description
 * @property string $calculated_meta_keywords
 */
trait SectionMutators
{
    /**
     * Ссылка на страницу просмотра раздела
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return di(ForumSectionPathService::class)->getSectionUrl($this);
    }

    /**
     * Section meta description
     *
     * @return string
     */
    public function getCalculatedMetaDescriptionAttribute(): string
    {
        if (! empty($this->meta_description)) {
            return $this->meta_description;
        }

        $config = config('forum')['settings'];
        $template = $config['section_description'] ?? '';
        return trim(
            str_replace(
                [
                    '#name#',
                    '#description#',
                ],
                [
                    $this->name,
                    strip_tags($this->description),
                ],
                $template
            )
        );
    }

    /**
     * Section meta keywords
     *
     * @return string
     */
    public function getCalculatedMetaKeywordsAttribute(): string
    {
        if (! empty($this->meta_keywords)) {
            return $this->meta_keywords;
        }

        $config = config('forum')['settings'];
        $template = $config['section_keywords'] ?? '';
        return trim(
            str_replace(
                [
                    '#name#',
                    '#description#',
                ],
                [
                    $this->name,
                    strip_tags($this->description),
                ],
                $template
            )
        );
    }
}
