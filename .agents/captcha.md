# Captcha

Telling a visitor from a bot runs behind one contract: a form asks `Johncms\Captcha\CaptchaManager`
for a challenge and validates the answer with the `Captcha` rule. Which provider actually answers
is a setting, and it may be one a module brought along.

## Using it in a form

1. **Controller.** Ask for the challenge and hand it to the template. Issue it *after* the
   validation of a submitted form: checking an answer spends it, so a form shown again has to
   carry a new one.

   ```php
   return new ViewResponse('@guestbook/public/index.twig', [
       'captcha' => $showCaptcha ? $this->captcha->challenge(GuestbookForm::CAPTCHA_SCOPE) : null,
   ]);
   ```

2. **Reading the answer.** Never hardcode the name of the field: the built-in provider answers in
   `code`, a remote service in a field of its own (`g-recaptcha-response`, `smart-token`).

   ```php
   'code' => $request->body($this->captcha->fieldName(), ''),
   ```

3. **Rule.** `new Captcha(scope: self::CAPTCHA_SCOPE)`. The rule requires a value like every
   `RequiresValueInterface` rule, and the message a visitor gets comes from what the provider says
   went wrong — a service that could not be reached does not read as a mistake of theirs.

4. **Template.** One component, whichever provider answered:

   ```twig
   {% include '@theme/components/captcha.twig' with {captcha: captcha, errors: errors.code|default([])} only %}
   ```

## Scopes

A scope names the form the captcha guards (`login`, `registration`, `guestbook`, …) and is what
keeps two forms open in two tabs from overwriting each other's answer — one shared session key
used to. The owner of the form owns the constant: `AuthenticateUserUseCase::CAPTCHA_SCOPE` for
both sign-in screens, a `CAPTCHA_SCOPE` on the form class of a module.

The same scope has to be used for issuing and for checking, or the answer is looked for under a
key nothing was written to.

## The shipped providers

| Key | What it is |
| --- | --- |
| `image` | the built-in picture with a code. Needs nothing, works offline, and is what everything falls back to |
| `hcaptcha` | a checkbox, with a puzzle behind it for whoever looks suspicious |
| `smartcaptcha` | Yandex SmartCaptcha. Same idea; its keys are named client key / server key |
| `recaptcha_v3` | Google reCAPTCHA v3: no widget, a score. Below the threshold a visitor is refused with nothing to solve, and the page loads scripts from Google |

None of the three services needs a composer package: a token, one HTTP request and a `<script>`
tag is all they are, and `symfony/http-client` is already a dependency. They are switched on by
filling in their keys, and a provider without them is never put in front of a visitor.

## Settings

Edited in the panel at `/admin/settings/captcha`. The page lists whatever the registry holds and
draws the fields each provider declares, so a captcha a module brought along appears there by
itself; `UpdateCaptchaSettingsUseCase` writes `captcha.local.php` and drops the compiled
container, the way the mail settings do.

The files themselves — `config/autoload/captcha.global.php`, overridden in `captcha.local.php`:

* `captcha.default` — the key of the provider in use. A key nothing is registered under, or a
  provider whose settings are incomplete, falls back to the built-in picture: a captcha that
  cannot work must not silently turn into no captcha at all.
* `captcha.providers.<key>.options` — the settings of one provider, read through
  `CaptchaProviderOptions::forProvider()` at call time rather than injected, so a change written
  by the panel takes effect without the compiled container being rebuilt.

## Adding a provider

Implement `CaptchaProviderInterface` anywhere — core or a module. The container tags it
(`registerExtensionPoints()` in `PSRContainerFactory`), the registry picks it up, and it appears
both in the forms and in the settings of the panel. Nothing in the core is touched.

What the four methods are for:

* `key()`, `label()`, `isConfigured()` — identity, the name in the panel, and whether it has the
  keys it needs. An unconfigured provider is never put in front of a visitor.
* `settingsFields()` — what the panel asks an administrator for, as a list of
  `CaptchaSettingField`. The settings form is drawn from this, so a provider needs no page of its
  own. A `Password` field is never sent back to the browser: the page shows whether it is filled,
  not what it holds.
* `challenge($scope)` — a `CaptchaChallenge`: the template of the widget, the name of the field,
  and whatever that template reads (a picture, a site key).
* `verify($answer, $scope, $clientIp)` — a `CaptchaResult`. Say *why* it failed
  (`CaptchaFailure`): a wrong answer, an expired challenge and an unreachable service are three
  different things to the visitor and to whoever reads the log.

A remote service is less than that: `AbstractRemoteCaptchaProvider` already does the request,
the timeouts and the two key fields, so a provider on top of it is an address, the names of the
fields in the payload and what the answer means — the way `AbstractOAuth2Provider` makes a
sign-in service thirty lines.

A provider ships its widget template in its own namespace; the shipped ones live in
`@theme/components/captcha/`.

## Traps

* **An answer is spent by being checked.** A picture answered once must not stay answerable, or a
  captured submission could be replayed until it got through. A controller does not need to clear
  anything afterwards — and must issue a new challenge for the form it shows again.
* **A remote provider needs the visitor's address**, so `verify()` takes it. The rule reads it off
  the `RequestStack` rather than through `Environment`: that one insists on a request being
  served, and a form may be validated outside an HTTP cycle.
* **The field name is not the form key.** The registration form keeps its data under `captcha`
  while the input is named `code`; what has to match the provider is the name read off the
  request, not the key of the array.
