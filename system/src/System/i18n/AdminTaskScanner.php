<?php

declare(strict_types=1);

namespace Johncms\System\i18n;

use Gettext\Translation;
use Gettext\Translations;
use Johncms\AdminTasks\AsAdminTask;
use ReflectionClass;

/**
 * Collects the title and the description of a maintenance task, which are written in its
 * #[AsAdminTask] attribute rather than in a call PhpScanner would see.
 *
 * The strings go to the domain the attribute names, the one the maintenance screen translates
 * them with, not to the domain of the directory the file sits in.
 */
final readonly class AdminTaskScanner
{
    /**
     * @param array<string, Translations> $translations Keyed by domain.
     */
    public function __construct(private array $translations)
    {
    }

    public function scanFile(string $file): void
    {
        $code = (string) file_get_contents($file);
        if (! str_contains($code, 'AsAdminTask')) {
            return;
        }

        $class = $this->className($code);
        if ($class === null || ! class_exists($class)) {
            return;
        }

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getAttributes(AsAdminTask::class) as $attribute) {
            $task = $attribute->newInstance();
            $translations = $this->translations[$task->domain] ?? null;
            if ($translations === null) {
                continue;
            }

            foreach ([$task->title, $task->description] as $original) {
                if ($original === null || $original === '') {
                    continue;
                }

                $translation = $translations->find(null, $original) ?? Translation::create(null, $original);
                $translation->getReferences()->add($file, (int) $reflection->getStartLine());
                $translations->add($translation);
            }
        }
    }

    /**
     * The name of the class a file declares, read from its tokens: the file is not included here,
     * the autoloader does that when the class is asked for.
     */
    private function className(string $code): ?string
    {
        $tokens = token_get_all($code);
        $namespace = '';

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->nextName($tokens, $index) ?? '';
            }

            if ($token[0] === T_CLASS) {
                $name = $this->nextName($tokens, $index);

                return $name === null ? null : ltrim($namespace . '\\' . $name, '\\');
            }
        }

        return null;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function nextName(array $tokens, int $index): ?string
    {
        for ($i = $index + 1, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true)) {
                return $token[1];
            }
            if (! is_array($token) || $token[0] !== T_WHITESPACE) {
                return null;
            }
        }

        return null;
    }
}
