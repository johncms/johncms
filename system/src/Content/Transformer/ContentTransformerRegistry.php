<?php

declare(strict_types=1);

namespace Johncms\Content\Transformer;

/**
 * The steps of the pipeline, in the order they run.
 *
 * The services are read once, on the first text rendered: a page that shows no user content
 * does not pay for the transformers it never runs.
 */
final class ContentTransformerRegistry
{
    /** @var list<ContentTransformerInterface>|null */
    private ?array $sorted = null;

    /**
     * @param iterable<ContentTransformerInterface> $transformers
     */
    public function __construct(
        private readonly iterable $transformers = [],
    ) {
    }

    /**
     * @return list<ContentTransformerInterface>
     */
    public function all(): array
    {
        if ($this->sorted !== null) {
            return $this->sorted;
        }

        $transformers = iterator_to_array($this->transformers, false);
        // Stable since PHP 8.0, so two steps of the same priority keep the order the container
        // built them in rather than swapping between requests.
        usort(
            $transformers,
            static fn (
                ContentTransformerInterface $a,
                ContentTransformerInterface $b
            ): int => $b->priority() <=> $a->priority()
        );

        return $this->sorted = $transformers;
    }
}
