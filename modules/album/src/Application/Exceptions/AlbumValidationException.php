<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumValidationException extends ValidationException
{
    /**
     * The errors are stored in the property inherited from ValidationException and read back
     * through getErrors(). Redeclaring it here — promoted, typed and readonly — is a fatal
     * error at class-declaration time ("Cannot redeclare non-readonly property ... as
     * readonly"), so every validation failure in the album form used to be a white screen.
     *
     * @param list<string> $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('Validation error');

        $this->errors = $errors;
    }
}
