<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * A generated image served from the cache directory.
 *
 * Marked private rather than public: a preview is only reached through a controller that has
 * already decided this visitor may see the picture, and a shared cache in front of the site
 * would hand the copy it kept to everyone else as well. The browser of the visitor who asked
 * still keeps it, which is what the repeated requests on a page full of thumbnails need.
 *
 * The headers a BinaryFileResponse normally gets in Response::prepare() are set here instead:
 * the kernel deliberately never calls prepare(), because it would turn the body of a legacy
 * controller that sent its own Content-Type into text/html (see ResponseNormalizer).
 */
final class CachedImageResponse extends BinaryFileResponse
{
    private const int MAX_AGE = 604800;

    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->headers->set('Content-Type', $this->file->getMimeType() ?: 'application/octet-stream');
        $this->headers->set('Content-Length', (string) $this->file->getSize());

        $this->setPrivate();
        $this->setMaxAge(self::MAX_AGE);
        $this->setLastModified(\DateTimeImmutable::createFromFormat('U', (string) $this->file->getMTime()) ?: null);
    }

    public function sendHeaders(?int $statusCode = null): static
    {
        // PHP has already sent the Cache-Control of the session, and sendHeaders() replaces
        // nothing but Content-Type — a second one would be appended to it rather than take its
        // place, and the browser would go on revalidating every thumbnail of the page.
        header_remove('Cache-Control');

        return parent::sendHeaders($statusCode);
    }
}
