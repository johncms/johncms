<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Mail\EmailMessage;
use Johncms\Mail\EmailSender;
use Johncms\Mail\MailFactory;
use Johncms\Mail\MailRenderer;
use Johncms\Mail\Queue\EloquentEmailQueue;
use Johncms\Mail\Queue\MailQueueSettings;
use Johncms\Mail\Schema\MailSchema;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\Support\BootsInMemoryDatabase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Driven against the real queue on an in-memory database, because what matters here is the state a
 * message is left in — a delivery that failed must stay in the queue rather than be recorded as
 * sent, which is what used to happen.
 */
final class EmailSenderTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const TEMPLATE = 'welcome.twig';

    /** @var list<Email> */
    private array $delivered = [];

    protected function setUp(): void
    {
        $this->bootDatabase();
        MailSchema::create(Capsule::schema());
        $this->delivered = [];
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    private function sender(?MailQueueSettings $settings = null, ?\Throwable $sendFailure = null): EmailSender
    {
        $mailFactory = $this->createMock(MailFactory::class);
        $mailFactory->method('createEmail')->willReturnCallback(
            static fn(): Email => (new Email())->from('site@example.com')
        );
        $mailFactory->method('send')->willReturnCallback(
            function (Email $email) use ($sendFailure): void {
                if ($sendFailure !== null) {
                    throw $sendFailure;
                }

                $this->delivered[] = $email;
            }
        );

        $renderer = new MailRenderer(
            new Environment(new ArrayLoader([self::TEMPLATE => 'Hello, {{ user_name }}!']))
        );

        return new EmailSender(
            new EloquentEmailQueue($settings ?? new MailQueueSettings()),
            $renderer,
            $mailFactory,
            new NullLogger()
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function queueMessage(array $fields = [], string $template = self::TEMPLATE): EmailMessage
    {
        return EmailMessage::query()->create(
            [
                'priority' => 100,
                'locale'   => 'en',
                'template' => $template,
                'fields'   => $fields + ['email_to' => 'user@example.com', 'name_to' => 'User'],
            ]
        );
    }

    private function row(EmailMessage $message): EmailMessage
    {
        return EmailMessage::query()->findOrFail($message->id);
    }

    // -----------------------------------------------------------------------------------

    public function testAMessageIsRenderedAddressedAndMarkedSent(): void
    {
        $message = $this->queueMessage(['subject' => 'Welcome', 'user_name' => 'Ann']);

        $result = $this->sender()->send(5);

        self::assertSame(1, $result->sent);
        self::assertCount(1, $this->delivered);

        $email = $this->delivered[0];
        self::assertSame('Welcome', $email->getSubject());
        self::assertSame('Hello, Ann!', $email->getHtmlBody());
        self::assertSame('"User" <user@example.com>', $email->getTo()[0]->toString());
        self::assertNotNull($this->row($message)->getRawOriginal('sent_at'));
    }

    /**
     * A name holding an `@` used to break sending outright, so it is dropped rather than obeyed.
     */
    public function testASenderNameThatWouldBreakTheHeaderIsDropped(): void
    {
        $this->queueMessage(['name_to' => 'user@example.com']);

        $this->sender()->send(5);

        self::assertSame('user@example.com', $this->delivered[0]->getTo()[0]->toString());
    }

    // -----------------------------------------------------------------------------------
    // What used to lose mail
    // -----------------------------------------------------------------------------------

    public function testATransportFailureKeepsTheMessageInTheQueue(): void
    {
        $message = $this->queueMessage();

        $result = $this->sender(sendFailure: new TransportException('Connection refused'))->send(5);

        self::assertSame(0, $result->sent);
        self::assertSame(1, $result->retrying);

        $row = $this->row($message);
        self::assertNull($row->getRawOriginal('sent_at'));
        self::assertNull($row->getRawOriginal('failed_at'));
        self::assertSame(1, $row->attempts);
        self::assertSame('Connection refused', $row->last_error);
    }

    public function testAMessageIsGivenUpOnOnceTheAttemptsRunOut(): void
    {
        $settings = new MailQueueSettings(maxAttempts: 1, retryDelays: [60]);
        $message = $this->queueMessage();

        $result = $this->sender($settings, new TransportException('Connection refused'))->send(5);

        self::assertSame(1, $result->failed);
        self::assertNotNull($this->row($message)->getRawOriginal('failed_at'));
        self::assertNull($this->row($message)->getRawOriginal('sent_at'));
    }

    /**
     * The rest of the batch has to go out: one unsendable message used to abort the whole run, and
     * with the row left untouched it would abort every following run as well.
     */
    public function testOneUnsendableMessageDoesNotStopTheBatch(): void
    {
        $broken = $this->queueMessage(['email_to' => 'not an address']);
        $good = $this->queueMessage();

        $result = $this->sender()->send(5);

        self::assertSame(1, $result->sent);
        self::assertSame(1, $result->failed);
        self::assertNotNull($this->row($broken)->getRawOriginal('failed_at'));
        self::assertNotNull($this->row($good)->getRawOriginal('sent_at'));
    }

    public function testAMessageThatCannotBeRenderedIsGivenUpOnRatherThanRetried(): void
    {
        $message = $this->queueMessage(template: 'missing.twig');

        $result = $this->sender()->send(5);

        self::assertSame(1, $result->failed);
        self::assertSame([], $this->delivered);

        $row = $this->row($message);
        self::assertNotNull($row->getRawOriginal('failed_at'));
        // And it is not silently recorded as delivered, the way it used to be.
        self::assertNull($row->getRawOriginal('sent_at'));
        self::assertNotNull($row->last_error);
    }

    public function testAMessageWithoutARecipientIsGivenUpOn(): void
    {
        $message = $this->queueMessage(['email_to' => '   ']);

        $result = $this->sender()->send(5);

        self::assertSame(1, $result->failed);
        self::assertNotNull($this->row($message)->getRawOriginal('failed_at'));
    }

    // -----------------------------------------------------------------------------------

    public function testTheBatchSizeIsHonoured(): void
    {
        $this->queueMessage();
        $this->queueMessage();
        $this->queueMessage();

        $result = $this->sender()->send(2);

        self::assertSame(2, $result->sent);
        self::assertSame(2, $result->processed());
        self::assertSame(1, EmailMessage::query()->whereNull('sent_at')->whereNull('failed_at')->count());
    }

    public function testAnEmptyQueueIsNotAFailure(): void
    {
        $result = $this->sender()->send(5);

        self::assertSame(0, $result->processed());
    }
}
