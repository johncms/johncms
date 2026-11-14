<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use RuntimeException;

/**
 * The pictures of a text cannot be given to the record. The message is meant for the author.
 */
final class EditorUploadBindException extends RuntimeException
{
}
