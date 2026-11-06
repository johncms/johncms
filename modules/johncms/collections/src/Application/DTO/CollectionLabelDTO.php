<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use InvalidArgumentException;

/** Stored source label; translated only when displayed in the current locale. */
final readonly class CollectionLabelDTO
{
    public function __construct(public string $domain, public string $message)
    {
        if (strlen($domain) > 255 || ! preg_match('/^[a-zA-Z0-9_.-]+$/D', $domain) || trim($message) === '' || mb_strlen($message) > 255) {
            throw new InvalidArgumentException('A declaration label requires a domain and a message of 1..255 characters.');
        }
    }

    /** @return array{domain: string, message: string} */
    public function toArray(): array
    {
        return ['domain' => $this->domain, 'message' => $this->message];
    }

    /** Gettext extraction marker: its arguments must be literal domain and source message. */
    public static function translatableLabel(string $domain, string $message): self
    {
        return new self($domain, $message);
    }
}
