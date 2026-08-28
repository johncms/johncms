<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class EditorImageSettingsDTO
{
    public function __construct(
        /** The largest upload the editor accepts, in kilobytes. */
        public int $maxSize,
        /** Bounds a stored picture is scaled down to fit in; zero leaves a side unconstrained. */
        public int $maxWidth,
        public int $maxHeight,
        /** Encoding quality of a re-encoded picture, in percent. */
        public int $quality,
        /** One of the values of \Johncms\Image\EditorImageFormat. */
        public string $convert,
    ) {
    }
}
