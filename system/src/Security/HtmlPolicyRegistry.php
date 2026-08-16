<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use RuntimeException;

/**
 * The policies the modules declared, by name.
 *
 * The providers are read once, on the first question: a page that sanitizes nothing does not
 * pay for policies it never uses.
 */
final class HtmlPolicyRegistry
{
    /** @var array<string, HtmlPolicyDefinition>|null */
    private ?array $definitions = null;

    /**
     * @param iterable<HtmlPolicyProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers = [],
    ) {
    }

    public function get(string $name): HtmlPolicyDefinition
    {
        $definitions = $this->definitions();

        return $definitions[$name] ?? throw UnknownHtmlPolicyException::forName($name, array_keys($definitions));
    }

    public function has(string $name): bool
    {
        return isset($this->definitions()[$name]);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array<string, HtmlPolicyDefinition>
     */
    private function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->policies() as $definition) {
                // Two modules claiming one name would mean the rules that clean a text depend on
                // the order the container happened to build them in.
                if (isset($definitions[$definition->name])) {
                    throw new RuntimeException(
                        sprintf(
                            'The HTML policy "%s" is declared twice; %s declares a name another provider already took.',
                            $definition->name,
                            $provider::class
                        )
                    );
                }

                $definitions[$definition->name] = $definition;
            }
        }

        return $this->definitions = $definitions;
    }
}
