<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Captcha\CaptchaProviderInterface;
use Johncms\Captcha\CaptchaProviderOptions;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\CaptchaSettingField;
use Johncms\Captcha\CaptchaSettingType;
use Johncms\Modules\Admin\Application\DTO\CaptchaSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\CaptchaConfigRepositoryInterface;

/**
 * Saves the captcha settings into captcha.local.php.
 *
 * The form comes from a browser, so the shape of the configuration is not taken from it: only
 * providers the registry knows and only the fields they declare are written, in the types those
 * fields describe.
 */
final readonly class UpdateCaptchaSettingsUseCase
{
    public function __construct(
        private CaptchaProviderRegistry $registry,
        private CaptchaConfigRepositoryInterface $config,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(CaptchaSettingsDTO $dto): void
    {
        $providers = [];

        foreach ($dto->providers as $key => $values) {
            $provider = $this->registry->get((string) $key);

            if ($provider === null) {
                continue;
            }

            $providers[$provider->key()] = ['options' => $this->options($provider, $values)];
        }

        $this->config->save(
            [
                // An unknown provider would leave the site without a captcha it can build, so the
                // choice is only taken when something answers to it.
                'default'   => $this->registry->has($dto->default)
                    ? $dto->default
                    : (string) config('captcha.default', ''),
                'providers' => $providers,
            ]
        );
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string|int|float|bool>
     */
    private function options(CaptchaProviderInterface $provider, array $values): array
    {
        $stored = CaptchaProviderOptions::forProvider($provider->key());
        $options = [];

        foreach ($provider->settingsFields() as $field) {
            $submitted = trim((string) ($values[$field->key] ?? ''));

            if ($field->type === CaptchaSettingType::Password && $submitted === '') {
                // An empty secret field means "leave it as it is": the page never shows the
                // stored value, so an empty input is the normal state of a saved provider rather
                // than a request to clear it.
                $options[$field->key] = $stored->string($field->key);
                continue;
            }

            $options[$field->key] = $this->cast($field, $submitted, $values);
        }

        return $options;
    }

    /**
     * Stored in the type the field describes: everything reading these settings takes them as
     * they are, and a threshold saved as the string "0.5" would compare as one.
     *
     * @param array<string, string> $values
     */
    private function cast(CaptchaSettingField $field, string $submitted, array $values): string|int|float|bool
    {
        return match ($field->type) {
            // An unchecked box submits nothing at all, so its absence is the answer.
            CaptchaSettingType::Checkbox => array_key_exists($field->key, $values),
            CaptchaSettingType::Number => is_float($field->default) || str_contains($submitted, '.')
                ? (float) $submitted
                : (int) $submitted,
            default => $submitted,
        };
    }
}
