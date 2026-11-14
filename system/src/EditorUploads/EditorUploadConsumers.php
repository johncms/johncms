<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use LogicException;

/**
 * The records that have their editor uploads tracked, collected from what the modules tagged.
 */
final readonly class EditorUploadConsumers
{
    /** @var array<string, EditorUploadConsumerInterface> */
    private array $consumers;

    /**
     * @param iterable<EditorUploadConsumerInterface> $consumers
     */
    public function __construct(iterable $consumers)
    {
        $byType = [];
        foreach ($consumers as $consumer) {
            $byType[$consumer->ownerType()] = $consumer;
        }
        $this->consumers = $byType;
    }

    public function get(string $ownerType): EditorUploadConsumerInterface
    {
        return $this->consumers[$ownerType]
            ?? throw new LogicException(sprintf('No editor upload consumer declares the owner type "%s".', $ownerType));
    }

    /** @return list<string> */
    public function directories(): array
    {
        return array_values(array_unique(array_map(
            static fn(EditorUploadConsumerInterface $consumer): string => $consumer->directory(),
            $this->consumers
        )));
    }
}
