<?php

declare(strict_types=1);

namespace Johncms\Database;

use PDO;
use WeakMap;

/** Connection-scoped callback frames shared by PDO and ORM adapters. */
final class TransactionCallbacks
{
    /** @var WeakMap<PDO, list<list<callable(): void>>>|null */
    private static ?WeakMap $frames = null;

    public static function depth(PDO $pdo): int
    {
        return count(self::$frames[$pdo] ?? []);
    }

    public static function begin(PDO $pdo): void
    {
        self::$frames ??= new WeakMap();
        $frames = self::$frames[$pdo] ?? [];
        $frames[] = [];
        self::$frames[$pdo] = $frames;
    }

    public static function commit(PDO $pdo): void
    {
        $frames = self::$frames[$pdo] ?? [];
        $callbacks = array_pop($frames) ?? [];
        if ($frames !== []) {
            $last = count($frames) - 1;
            $frames[$last] = [...$frames[$last], ...$callbacks];
            self::$frames[$pdo] = $frames;
            return;
        }
        if (self::$frames !== null) {
            unset(self::$frames[$pdo]);
        }
        self::execute($callbacks);
    }

    public static function rollbackTo(PDO $pdo, int $depth): void
    {
        if (self::$frames !== null) {
            self::$frames[$pdo] = array_slice(self::$frames[$pdo] ?? [], 0, $depth);
            if ($depth === 0) {
                unset(self::$frames[$pdo]);
            }
        }
    }

    public static function afterCommit(PDO $pdo, callable $callback): void
    {
        $frames = self::$frames[$pdo] ?? [];
        if ($frames === []) {
            if ($pdo->inTransaction()) {
                throw new \LogicException('Unmanaged PDO transaction: use ConnectionInterface::transaction() or the ORM transaction API.');
            }
            self::execute([$callback]);
            return;
        }
        $frames[count($frames) - 1][] = $callback;
        self::$frames[$pdo] = $frames;
    }

    public static function hasActiveTransaction(): bool
    {
        foreach (self::$frames ?? [] as $pdo => $frames) {
            if ($frames !== [] && $pdo->inTransaction()) {
                return true;
            }
        }
        return false;
    }

    /** @param list<callable(): void> $callbacks */
    private static function execute(array $callbacks): void
    {
        $failures = [];
        foreach ($callbacks as $callback) {
            try {
                $callback();
            } catch (\Throwable $failure) {
                $failures[] = $failure;
            }
        }
        if ($failures !== []) {
            throw new AfterCommitException($failures);
        }
    }
}
