<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Captcha\CaptchaProviderInterface;
use Johncms\Captcha\CaptchaProviderOptions;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\CaptchaSettingField;
use Johncms\Captcha\CaptchaSettingType;
use Johncms\Modules\Admin\Application\DTO\CaptchaProviderRowDTO;
use Johncms\Modules\Admin\Application\DTO\CaptchaSettingRowDTO;

/**
 * The captcha providers as the panel lists them: everything registered, with the settings each of
 * them asks for and the values it currently holds.
 *
 * Nothing here knows a provider by name. The page is built from what the registry holds and from
 * what each provider says it needs, which is what lets a module ship a captcha of its own.
 */
final readonly class GetCaptchaSettingsUseCase
{
    public function __construct(private CaptchaProviderRegistry $registry)
    {
    }

    /**
     * @return list<CaptchaProviderRowDTO>
     */
    public function execute(): array
    {
        $active = (string) config('captcha.default', '');

        return array_values(
            array_map(
                fn (CaptchaProviderInterface $provider): CaptchaProviderRowDTO => new CaptchaProviderRowDTO(
                    key: $provider->key(),
                    label: $provider->label(),
                    isActive: $provider->key() === $active,
                    isConfigured: $provider->isConfigured(),
                    fields: $this->fields($provider),
                ),
                $this->registry->all()
            )
        );
    }

    /**
     * @return list<CaptchaSettingRowDTO>
     */
    private function fields(CaptchaProviderInterface $provider): array
    {
        $options = CaptchaProviderOptions::forProvider($provider->key());

        return array_map(
            function (CaptchaSettingField $field) use ($options): CaptchaSettingRowDTO {
                $stored = $options->raw($field->key, $field->default);

                return new CaptchaSettingRowDTO(
                    key: $field->key,
                    type: $field->type->value,
                    label: $field->label,
                    hint: $field->hint,
                    // A secret is answered for with a flag and nothing else.
                    value: $field->type === CaptchaSettingType::Password ? '' : $this->text($stored),
                    checked: (bool) $stored,
                    hasValue: $this->text($stored) !== '',
                    options: $field->options,
                );
            },
            $provider->settingsFields()
        );
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
