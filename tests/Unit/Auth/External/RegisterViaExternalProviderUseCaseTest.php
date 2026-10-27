<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Gettext\Translator as GettextTranslator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\External\AuthenticateViaExternalProviderUseCase;
use Johncms\Auth\External\ExternalIdentityDTO;
use Johncms\Auth\External\RegisterViaExternalProviderUseCase;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentUserIdentityRepository;
use Johncms\Auth\RegistrationSettings;
use Johncms\Config\ConfigRepository;
use Johncms\Security\ClientInfoDTO;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CurrentUserFactory;
use Tests\Support\RecordingAuthEventLogger;
use Tests\Support\RecordingMailQueue;
use Tests\Support\RunsMigrations;

/**
 * The account created behind an identity, and whether its address counts as confirmed.
 *
 * Only an address the provider vouches for is: one the visitor typed in proves nothing, and an
 * account that takes it on trust hands a stranger's address to password recovery and to the
 * matching of accounts by address.
 */
final class RegisterViaExternalProviderUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const PROVIDER = 'vk';

    private RecordingMailQueue $mailQueue;

    protected function setUp(): void
    {
        $this->bootDatabase();
        TranslatorFunctions::register(new GettextTranslator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        ConfigRepository::init(['johncms' => ['user_email_confirmation' => 1, 'homeurl' => 'https://example.com']]);
        $this->mailQueue = new RecordingMailQueue();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testAnAddressTheProviderVouchesForIsConfirmed(): void
    {
        $user = $this->register(new ExternalIdentityDTO('42', 'owner@example.com', true), 'owner@example.com');

        self::assertTrue($user->email_confirmed);
        self::assertNull($user->confirmation_code);
        self::assertSame([], $this->mailQueue->emails());
    }

    /**
     * VK may withhold the address, and then the visitor types one in. Nothing has proved it is
     * theirs, so it is confirmed the way the registration form confirms one.
     */
    public function testATypedInAddressIsConfirmedByMail(): void
    {
        $user = $this->register(new ExternalIdentityDTO('42', null, false), 'typed@example.com');

        self::assertNull($user->email_confirmed);
        self::assertNotEmpty($user->confirmation_code);
        self::assertCount(1, $this->mailQueue->emails());
        $email = $this->mailQueue->emails()[0];
        self::assertSame('typed@example.com', $email->recipient);
        self::assertSame(
            'https://example.com/registration/confirm-email?id=' . $user->id . '&code=' . $user->confirmation_code,
            $email->variables['link_to_confirm']
        );
    }

    /**
     * The proof covers the address the provider sent, not whatever came in with the form.
     */
    public function testAVerifiedIdentityDoesNotConfirmADifferentAddress(): void
    {
        $user = $this->register(new ExternalIdentityDTO('42', 'owner@example.com', true), 'victim@example.com');

        self::assertNull($user->email_confirmed);
        self::assertCount(1, $this->mailQueue->emails());
    }

    public function testTheCaseOfTheAddressDoesNotMatter(): void
    {
        $user = $this->register(new ExternalIdentityDTO('42', 'Owner@Example.com', true), 'owner@example.com');

        self::assertTrue($user->email_confirmed);
    }

    /**
     * The same as the registration form: on a site that does not confirm addresses the account is
     * marked confirmed, so turning the setting on later does not lock out everybody it let in.
     */
    public function testNothingIsAskedWhenTheSiteDoesNotConfirmAddresses(): void
    {
        ConfigRepository::init(['johncms' => ['user_email_confirmation' => 0]]);

        $user = $this->register(new ExternalIdentityDTO('42', null, false), 'typed@example.com');

        self::assertTrue($user->email_confirmed);
        self::assertNull($user->confirmation_code);
        self::assertSame([], $this->mailQueue->emails());
    }

    private function register(ExternalIdentityDTO $identity, string $email): User
    {
        $eventLogger = new RecordingAuthEventLogger();
        $roles = new EloquentRoleRepository();

        $useCase = new RegisterViaExternalProviderUseCase(
            new AuthenticateViaExternalProviderUseCase(
                new EloquentUserIdentityRepository(),
                new AccessChecker(CurrentUserFactory::guest(), []),
                new PermissionResolver($roles),
                $eventLogger,
                new RegistrationSettings()
            ),
            $eventLogger,
            new RegistrationSettings(),
            $this->mailQueue,
            new Translator()
        );

        $user = $useCase->execute(self::PROVIDER, $identity, 'Newcomer', $email, new ClientInfoDTO('0', '0', 'Test'));

        return User::query()->findOrFail($user->id);
    }

    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->string('name_lat')->default('');
                $table->string('imname')->default('');
                $table->string('mail')->default('');
                $table->string('password')->default('');
                $table->bigInteger('ip')->default(0);
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->text('browser');
                $table->integer('datereg')->default(0);
                $table->integer('lastdate')->default(0);
                $table->integer('sestime')->default(0);
                $table->boolean('preg')->default(0);
                $table->text('set_user')->nullable();
                $table->text('set_forum')->nullable();
                $table->text('set_mail')->nullable();
                $table->text('smileys')->nullable();
                $table->boolean('email_confirmed')->nullable();
                $table->string('confirmation_code')->nullable();
            }
        );
    }
}
