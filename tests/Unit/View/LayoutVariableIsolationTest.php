<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use PHPUnit\Framework\TestCase;

/**
 * A {% set %} in a layout lives in the context its blocks are rendered in, so a name a layout
 * keeps for itself silently shadows the variable of the page that happens to be called the same.
 * It cost the notifications page its list: the layout set "notifications" to the sidebar
 * counters, and the page saw those instead of its own.
 *
 * Hence the convention this test pins: every variable a layout assigns is prefixed with layout_.
 * Components are not checked — they are included with "only", so their context is their own.
 */
final class LayoutVariableIsolationTest extends TestCase
{
    public function testALayoutOnlyAssignsPrefixedVariables(): void
    {
        $offenders = [];

        foreach ($this->layouts() as $layout) {
            preg_match_all('/{%-?\s*set\s+([a-zA-Z_][a-zA-Z0-9_]*)/', (string) file_get_contents($layout), $matches);

            foreach ($matches[1] as $variable) {
                if (! str_starts_with($variable, 'layout_')) {
                    $offenders[] = basename($layout) . ': ' . $variable;
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            'A layout must prefix the variables it sets with layout_, or it shadows the data of '
            . 'the page rendered in it: ' . implode(', ', $offenders)
        );
    }

    /**
     * @return array<string>
     */
    private function layouts(): array
    {
        $layouts = [];

        foreach ((array) glob(THEMES_PATH . '*' . DS . 'templates' . DS . '{,admin' . DS . '}layouts' . DS . '*.twig', GLOB_BRACE) as $layout) {
            $layouts[] = (string) $layout;
        }

        return $layouts;
    }
}
