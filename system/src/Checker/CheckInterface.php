<?php

declare(strict_types=1);

namespace Johncms\Checker;

interface CheckInterface
{
    /**
     * This method must return the name of the check
     */
    public function getName(): string;

    /**
     * This method must return the value of the check.
     * This is displayed in the value column.
     */
    public function getValue(): string;

    /**
     * The check status.
     */
    public function isError(): bool;

    /**
     * Error level. (SystemChecker::CRITICAL, SystemChecker::WARNING, SystemChecker::INFO)
     */
    public function getErrorLevel(): int;

    /**
     * The check description.
     * Describes what the check is for and what needs to be done to make it pass.
     */
    public function getDescription(): string;
}
