<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

/**
 * Чтение и сохранение настроек почты (ключ `mail` в mail.local.php).
 */
interface MailConfigRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getMail(): array;

    /**
     * @param array<string, mixed> $mail
     * @throws ConfigWriteException
     */
    public function saveMail(array $mail): void;
}
