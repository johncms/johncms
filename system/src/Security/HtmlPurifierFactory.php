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

use HTMLPurifier_AttrDef_Enum;
use HTMLPurifier_Config;
use HTMLPurifier_HTMLDefinition;

/**
 * Builds the HTMLPurifier instance that enforces a policy.
 *
 * This is the one place that knows the library: everything above it speaks in terms of
 * HtmlPolicy. Replacing HTMLPurifier means writing another implementation of
 * HtmlSanitizerInterface, not touching the callers.
 */
final class HtmlPurifierFactory
{
    /**
     * Identifies our customizations of the HTML definition in the cache.
     */
    private const DEFINITION_ID = 'johncms';

    /**
     * Bump this whenever the elements or the attributes added in customizeDefinition() change,
     * otherwise the cached definition of the previous revision stays in use.
     */
    private const DEFINITION_REV = 1;

    private const CACHE_DIR = CACHE_PATH . 'htmlpurifier';

    /**
     * Inline formatting and links. The attributes of a link are listed because HTML.Allowed
     * drops every attribute that is not named.
     */
    private const INLINE_ELEMENTS = 'a[href|title|target|rel],b,strong,i,em,u,br';

    public function create(HtmlPolicy $policy): \HTMLPurifier
    {
        $config = $this->baseConfig();

        match ($policy) {
            HtmlPolicy::RichContent         => $this->configureRichContent($config),
            HtmlPolicy::Inline              => $this->configureInline($config, self::INLINE_ELEMENTS),
            HtmlPolicy::InlineWithParagraphs => $this->configureInline($config, self::INLINE_ELEMENTS . ',p,span'),
        };

        return new \HTMLPurifier($config);
    }

    /**
     * The same, for a policy a module declared. The definition is an allow list, so anything it
     * does not name is gone — a module cannot widen what the sanitizer lets through beyond the
     * elements and attributes it enumerates, and never to scripts or event handlers.
     */
    public function createFromDefinition(HtmlPolicyDefinition $definition): \HTMLPurifier
    {
        $config = $this->baseConfig();
        $config->set('HTML.Allowed', $this->compileAllowedElements($definition));
        $config->set('Attr.AllowedClasses', $definition->allowedClasses);
        $config->set('Attr.AllowedFrameTargets', $definition->frameTargets);
        $config->set('URI.AllowedSchemes', array_fill_keys($definition->linkSchemes, true));
        $config->set('AutoFormat.Linkify', $definition->linkify);

        return new \HTMLPurifier($config);
    }

    private function baseConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        // Without a path of our own the definitions are cached inside vendor/, which is wiped
        // by every composer install and is read-only on a properly deployed site.
        $config->set('Cache.SerializerPath', $this->cacheDirectory());

        return $config;
    }

    /**
     * `tag[attr|attr],tag` — the shape HTML.Allowed expects. An element with no attributes is
     * written bare, since an empty pair of brackets is not valid there.
     */
    private function compileAllowedElements(HtmlPolicyDefinition $definition): string
    {
        $parts = [];
        foreach ($definition->elements as $element => $attributes) {
            $parts[] = $attributes === []
                ? $element
                : $element . '[' . implode('|', $attributes) . ']';
        }

        return implode(',', $parts);
    }

    private function configureRichContent(HTMLPurifier_Config $config): void
    {
        $htmlpurifier_config = config('htmlpurifier', ['allowed_classes' => []]);
        // Read on every validation, so the list can be edited without touching the definition cache.
        $config->set('Attr.AllowedClasses', $htmlpurifier_config['allowed_classes']);
        // A bare URL in a post becomes a link. Only rich content gets this: in a short inline
        // text a link is written by hand or not wanted at all.
        $config->set('AutoFormat.Linkify', true);

        // Compiling the customized definition takes a couple of milliseconds of every request.
        // Cached, it is compiled once and then read from data/cache until the revision changes.
        $config->set('HTML.DefinitionID', self::DEFINITION_ID);
        $config->set('HTML.DefinitionRev', self::DEFINITION_REV);

        // Null once the definition is cached: there is nothing left to customize then.
        $def = $config->maybeGetRawHTMLDefinition();
        if ($def !== null) {
            $this->customizeDefinition($def);
        }
    }

    private function configureInline(HTMLPurifier_Config $config, string $allowedElements): void
    {
        $config->set('HTML.Allowed', $allowedElements);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
    }

    private function customizeDefinition(HTMLPurifier_HTMLDefinition $def): void
    {
        $def->addAttribute(
            'a',
            'target',
            new HTMLPurifier_AttrDef_Enum(
                ['_blank', '_self', '_target', '_top']
            )
        );
        $def->addElement(
            'figure',
            'Block',
            'Flow',
            'Common',
            [ // attributes
                'class',
            ]
        );
        $def->addElement(
            'oembed',
            'Block',
            'Flow',
            'Common',
            [ // attributes
                'url' => 'URI',
            ]
        );
        $def->addElement(
            'figcaption',
            'Block',
            'Flow',
            'Common',
            []
        );
    }

    /**
     * The serializer only creates the directory of a definition, its base directory has to exist
     * beforehand. cache:clear removes the whole tree, so it is recreated here on demand.
     */
    private function cacheDirectory(): string
    {
        if (! is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }

        return self::CACHE_DIR;
    }
}
