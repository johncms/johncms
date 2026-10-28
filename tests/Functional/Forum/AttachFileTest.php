<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Forum\Application\Exceptions\UploadException;
use Johncms\Modules\Forum\Application\UseCases\AttachFileToPostUseCase;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;
use Twig\Markup;

/**
 * A file of a type the forum does not take is refused with the list of the types it does take.
 */
final class AttachFileTest extends FunctionalTestCase
{
    public function testTheRefusalOfAForbiddenTypeIsMarkupWithTheTypesEscaped(): void
    {
        $member = FunctionalUserFactory::create();
        $message = Fixture::insert('forum_messages', ['topic_id' => 1, 'user_id' => $member->id, 'date' => time(), 'text' => 'Hello']);

        try {
            $this->container()->get(AttachFileToPostUseCase::class)->execute(
                $message,
                ['documents' => ['txt', '<b>']],
                1024,
                new UploadedFileDTO('virus.exe', 'application/octet-stream', 10, '/nonexistent', UPLOAD_ERR_OK),
            );
            self::fail('A forbidden type was accepted.');
        } catch (UploadException $exception) {
            $error = $exception->getErrors()[0];

            self::assertInstanceOf(Markup::class, $error);
            self::assertStringContainsString('<br>', (string) $error);
            self::assertStringContainsString('txt, &lt;b&gt;', (string) $error);
        }
    }
}
