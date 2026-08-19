<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

/**
 * A way of telling a visitor from a bot.
 *
 * Deliberately not a contract about pictures and codes. A remote service — reCAPTCHA,
 * SmartCaptcha, hCaptcha — has no picture to draw and no code to remember: it puts a widget on
 * the page and answers a question about a token afterwards. So the contract asks the two
 * questions both kinds can answer: what does the form have to show, and what do we make of what
 * came back.
 *
 * A module registers a provider of its own by implementing this interface — the container tags
 * it (see PSRContainerFactory), the registry picks it up, and it appears both in the forms and
 * in the settings of the panel without a line changed in the core. What the panel draws for it
 * comes from settingsFields().
 */
interface CaptchaProviderInterface
{
    /**
     * The key the provider is known by, in the config and in the settings of the panel. Never
     * renamed: a site naming it in captcha.local.php would silently get a different captcha.
     */
    public function key(): string;

    /** The name of the provider in the panel, translated. */
    public function label(): string;

    /**
     * Whether the provider has what it needs to work. A remote service without its keys is not
     * configured, and the manager refuses to put it in front of visitors.
     */
    public function isConfigured(): bool;

    /**
     * What the panel asks an administrator for. The form is drawn from this list, so a provider
     * shipped by a module needs no page of its own.
     *
     * @return list<CaptchaSettingField>
     */
    public function settingsFields(): array;

    /**
     * The name of the request field the answer arrives in: `code` for the built-in one,
     * `g-recaptcha-response` or `smart-token` for the remote ones. A form reads the value by
     * this name instead of hardcoding one.
     */
    public function fieldName(): string;

    /**
     * What the form has to show, prepared for the given scope.
     *
     * The scope names the form the captcha guards (`login`, `registration`, …). It keeps two
     * forms open in two tabs from overwriting each other's answer, which one shared session key
     * used to do.
     */
    public function challenge(string $scope): CaptchaChallenge;

    /**
     * Whether the answer is good enough to let the submission through.
     *
     * An answer is spent by being checked: the same challenge must not be answerable twice.
     */
    public function verify(string $answer, string $scope, ?string $clientIp = null): CaptchaResult;
}
