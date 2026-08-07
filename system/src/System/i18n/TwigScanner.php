<?php

declare(strict_types=1);

namespace Johncms\System\i18n;

use Gettext\Translation;
use Gettext\Translations;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Source;

/**
 * Collects the translatable strings of Twig templates, the way PhpScanner collects them from PHP.
 *
 * It walks the parsed template rather than the compiled PHP: compilation turns a function call
 * into a lookup on an extension, and the name the string was written under is gone by then.
 *
 * Only literal arguments are collected — a call built from a variable has no string to extract,
 * which is true of the PHP scanner as well.
 */
final class TwigScanner
{
    /**
     * Number of leading arguments before the message, and whether a plural form follows it.
     *
     * @var array<string, array{domain: bool, plural: bool}>
     */
    private const FUNCTIONS = [
        '__'   => ['domain' => false, 'plural' => false],
        'n__'  => ['domain' => false, 'plural' => true],
        'd__'  => ['domain' => true, 'plural' => false],
        'dn__' => ['domain' => true, 'plural' => true],
    ];

    private string $defaultDomain = '';

    /**
     * @param array<string, Translations> $translations Keyed by domain.
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly array $translations,
        private readonly ?Environment $mailTwig = null,
    ) {
    }

    public function setDefaultDomain(string $domain): void
    {
        $this->defaultDomain = $domain;
    }

    public function scanFile(string $file): void
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return;
        }

        // A template of an email is written against the mail environment; parsing it with the
        // web one would fail on the functions only mail has.
        $twig = $this->mailTwig !== null && str_contains(str_replace(DS, '/', $file), '/templates/emails/')
            ? $this->mailTwig
            : $this->twig;

        $this->walk(
            $twig->parse($twig->tokenize(new Source($code, $file, $file))),
            $file
        );
    }

    private function walk(Node $node, string $file): void
    {
        if ($node instanceof FunctionExpression) {
            $this->collect($node, $file);
        }

        foreach ($node as $child) {
            $this->walk($child, $file);
        }
    }

    private function collect(FunctionExpression $node, string $file): void
    {
        $name = (string) $node->getAttribute('name');
        $signature = self::FUNCTIONS[$name] ?? null;

        if ($signature === null) {
            return;
        }

        $arguments = [];
        foreach ($node->getNode('arguments') as $argument) {
            $arguments[] = $argument instanceof ConstantExpression ? $argument->getAttribute('value') : null;
        }

        $domain = $signature['domain'] ? array_shift($arguments) : $this->defaultDomain;
        $original = array_shift($arguments);
        $plural = $signature['plural'] ? array_shift($arguments) : null;

        if (! is_string($domain) || ! is_string($original) || $original === '') {
            return;
        }

        $translations = $this->translations[$domain] ?? null;
        if ($translations === null) {
            return;
        }

        $translation = $translations->find(null, $original) ?? Translation::create(null, $original);

        if (is_string($plural) && $plural !== '') {
            $translation->setPlural($plural);
        }

        $translation->getReferences()->add($file, $node->getTemplateLine());
        $translations->add($translation);
    }
}
