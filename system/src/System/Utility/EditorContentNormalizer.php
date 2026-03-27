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

        return $normalized;
    }
}
