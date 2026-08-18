<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Image;

use RuntimeException;

/**
 * The image could not be processed: it is not an image, the format is not supported, the
 * target is not writable, or the driver failed on it.
 *
 * Every failure of the processor arrives as this exception, whichever library raised it. A
 * caller that wants to report "the picture could not be uploaded" catches this and nothing
 * else — a bare \Exception would swallow genuine bugs along with it.
 */
final class ImageProcessingException extends RuntimeException
{
}
