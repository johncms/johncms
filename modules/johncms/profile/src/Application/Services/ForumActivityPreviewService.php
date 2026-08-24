<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Services;

use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;

final readonly class ForumActivityPreviewService
{
    public function __construct(
        private ContentRendererInterface $content,
    ) {
    }

    /**
     * Build a short plain-text preview of a forum post.
     */
    public function make(string $rawText, bool $authorIsStaff): string
    {
        $text = $this->content->toPlainText($rawText, new ContentContext(adminSmilies: $authorIsStaff));

        return mb_strimwidth($text, 0, 300, '...');
    }
}
