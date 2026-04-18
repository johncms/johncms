<?php

declare(strict_types=1);

namespace Johncms\System\Utility;

final class EditorContentNormalizer
{
    public function trimEdgeEmptyBlocks(string $html): string
    {
        $normalized = trim($html);
        if ($normalized === '') {
            return '';
        }

        $emptyBlockContent = '(?:\s|&nbsp;|&#160;|&#xA0;|<br\s*/?>)*';
        $leadingPattern = '~^\s*<(p|div)\b[^>]*>' . $emptyBlockContent . '</\1>\s*~iu';
        $trailingPattern = '~\s*<(p|div)\b[^>]*>' . $emptyBlockContent . '</\1>\s*$~iu';

        do {
            $previous = $normalized;
            $normalized = (string) preg_replace($leadingPattern, '', $normalized, 1);
            $normalized = (string) preg_replace($trailingPattern, '', $normalized, 1);
            $normalized = trim($normalized);
        } while ($normalized !== '' && $normalized !== $previous);

        $normalized = (string) preg_replace(
            '/<figure\b[^>]*>\s*<img(?![^>]*\bsrc\s*=\s*["\'][^"\']+["\'])[^>]*>\s*<\/figure>/iu',
            '',
            $normalized
        );
        $normalized = trim($normalized);

        $plainText = trim(html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $hasValidImage = (bool) preg_match('/<img\b[^>]*\bsrc\s*=\s*["\'][^"\']+["\']/iu', $normalized);
        if ($plainText === '' && ! $hasValidImage) {
            return '';
        }

        return $normalized;
    }
}
