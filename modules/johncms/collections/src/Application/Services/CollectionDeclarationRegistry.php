<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use InvalidArgumentException;
use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionDeclarationProviderInterface;
use Johncms\Modules\ModuleRegistry;

final readonly class CollectionDeclarationRegistry
{
    /** @var list<CollectionDeclarationProviderInterface> */
    private array $providers;

    /** @param iterable<CollectionDeclarationProviderInterface> $providers */
    public function __construct(iterable $providers = [], private ?ModuleRegistry $modules = null)
    {
        $this->providers = [...$providers];
    }

    /** @return list<array{owner: string, declaration: CollectionDeclarationDTO}> */
    public function all(): array
    {
        $result = $seen = [];
        foreach ($this->providers as $provider) {
            $owner = $provider->owner();
            if (strlen($owner) > 255 || ! preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $owner) || ($this->modules !== null && ! isset($this->modules->enabled()[$owner]))) {
                throw new InvalidArgumentException('Unknown collection declaration owner: ' . $owner);
            }
            foreach ($provider->collections() as $declaration) {
                if (! $declaration instanceof CollectionDeclarationDTO) {
                    throw new InvalidArgumentException('Collection providers must return CollectionDeclarationDTO values.');
                }
                $identity = $owner . '/' . $declaration->key;
                if (isset($seen[$identity])) {
                    throw new InvalidArgumentException('Duplicate collection declaration: ' . $identity);
                }
                $seen[$identity] = true;
                $result[] = ['owner' => $owner, 'declaration' => $declaration];
            }
        }
        return $result;
    }
}
