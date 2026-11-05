<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use Johncms\Modules\Collections\Domain\Enums\FieldType;

/** Scalar rules shared by item input and schema defaults. */
final readonly class CollectionFieldValueRules
{
    /** @param array<string, mixed> $settings
     * @param list<string> $retainedOptions
     */
    public function error(FieldType $type, string $value, array $settings = [], array $retainedOptions = [], bool $allowInactiveOptions = false): ?string
    {
        $valid = match ($type) {
            FieldType::Integer => filter_var($value, FILTER_VALIDATE_INT) !== false,
            FieldType::Relation => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false,
            FieldType::File => ($settings['file_storage'] ?? 'legacy') !== 'registry' || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false,
            FieldType::Double => is_numeric($value) && is_finite((float) $value),
            FieldType::Boolean => in_array($value, ['', '0', '1'], true),
            FieldType::Date => $this->date($value, true) !== null,
            FieldType::Datetime => $this->date($value) !== null,
            default => true,
        };
        if (! $valid) {
            return d__('collections', 'Enter a valid value for this field type.');
        }
        if (in_array($type, [FieldType::String_, FieldType::Text, FieldType::Html, FieldType::Select, FieldType::File], true)) {
            $length = mb_strlen($value);
            $max = $settings['max_length'] ?? (in_array($type, [FieldType::String_, FieldType::Select, FieldType::File], true) ? 255 : null);
            if ($length < ($settings['min_length'] ?? 0) || ($max !== null && $length > $max)) {
                return d__('collections', 'The value does not meet the configured length limits.');
            }
        }
        if (in_array($type, [FieldType::Integer, FieldType::Double], true)) {
            $number = $type === FieldType::Integer ? (int) $value : (float) $value;
            if ((isset($settings['min_value']) && $number < $settings['min_value']) || (isset($settings['max_value']) && $number > $settings['max_value'])) {
                return d__('collections', 'The value is outside the configured numeric range.');
            }
        }
        if ($type === FieldType::Select && ! in_array($value, $retainedOptions, true)) {
            foreach ($settings['options'] ?? [] as $option) {
                if ($option['code'] === $value && (($option['active'] ?? true) || $allowInactiveOptions)) {
                    return null;
                }
            }
            return d__('collections', 'Choose an available option for this field.');
        }
        return null;
    }

    public function date(?string $raw, bool $dateOnly = false): ?string
    {
        if ($raw === null || trim($raw) === '' || str_contains($raw, "\0")) {
            return null;
        }
        $raw = trim($raw);
        foreach ($dateOnly ? ['Y-m-d', 'Y-m-d H:i:s'] : ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $raw);
            if ($date !== false && $date->format($format) === $raw && $date->format('Y') >= '1000' && (! $dateOnly || $date->format('H:i:s') === '00:00:00')) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        return null;
    }
}
