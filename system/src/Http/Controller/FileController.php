<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Files\FileStore;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hands out a stored file that the web server cannot serve itself.
 *
 * Files on a public disk are fetched straight from it and never reach this controller; a
 * private disk lives outside the document root, and this is the only way in. That is what
 * makes a private disk possible at all — and the reason to move a directory of attachments
 * onto one: on the public disk anybody who has the address has the file, forever, whatever the
 * site thinks about it afterwards.
 *
 * A word about what this does *not* do: it serves any file that is registered, to anybody who
 * knows its id. The CMS has no notion yet of who owns an attachment — three modules relate
 * files to their records in three different ways — so there is nothing here to ask. Until there
 * is, a private disk buys the two things a public one cannot: paths that cannot be guessed or
 * listed, and files that stop being reachable the moment their row is deleted.
 *
 * A disk that fails is left to the kernel: a registered file its disk will not give up is a
 * broken installation, and answering 404 would send whoever reports it looking for a deleted
 * attachment instead.
 */
final readonly class FileController
{
    /**
     * How long a browser may keep the file. It is addressed by an id that is never reused, and
     * the contents behind it never change.
     */
    private const int MAX_AGE = 604800;

    public function __construct(
        private FileStore $files,
    ) {
    }

    /**
     * @throws PageNotFoundException
     */
    public function download(int $id): Response
    {
        $file = $this->files->openStream($id);
        if ($file === null) {
            throw new PageNotFoundException();
        }

        $stream = $file->stream;
        $response = new StreamedResponse(
            static function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            }
        );

        // Set here rather than left to Response::prepare(), which the kernel never calls.
        $response->headers->set('Content-Type', $file->mimeType);
        $response->headers->set('Content-Length', (string) $file->size);
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $file->name, 'file')
        );
        // Private: a shared cache in front of the site must not keep a copy of a file that is
        // deliberately not on a public disk.
        $response->setPrivate();
        $response->setMaxAge(self::MAX_AGE);

        return $response;
    }
}
