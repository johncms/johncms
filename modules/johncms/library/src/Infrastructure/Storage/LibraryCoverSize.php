<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Infrastructure\Storage;

/**
 * The sizes a cover is kept in. The value is the directory it lives in.
 */
enum LibraryCoverSize: string
{
    /** The upload itself, only re-encoded to PNG. What the FB2 export embeds. */
    case Original = 'orig';

    /** The picture of the article page. */
    case Big = 'big';

    /** The icon of a listing. */
    case Small = 'small';
}
