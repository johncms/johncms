<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

final readonly class CollectionItemValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /** @param Collection<int, ContentCollectionField> $definitions */
    public function validate(CollectionItemFormDTO $form, Collection $definitions): CollectionItemFormDTO
    {
        $name = trim($form->name);
        $code = trim($form->code);
        $result = $this->validator->validate(['name' => $name, 'code' => $code], [
            'name' => [new StringLength(max: 255)],
            'code' => [new StringLength(max: 255, allowEmpty: true), new Pattern(
                '/^[\w.+-]+$/D',
                allowEmpty: true,
                message: d__('collections', 'Use letters, digits, dot, plus, hyphen or underscore in the code.')
            )],
        ]);
        $sort = filter_var($form->sort, FILTER_VALIDATE_INT, ['options' => ['min_range' => -2147483648, 'max_range' => 2147483647]]);
        if ($sort === false) {
            $result = $result->withError('sort', d__('collections', 'Enter an integer between -2147483648 and 2147483647.'));
        }
        if ($form->previewText !== null && strlen($form->previewText) > 65535) {
            $result = $result->withError('preview_text', d__('collections', 'The preview text must not exceed 65535 bytes.'));
        }
        $from = $this->date($form->activeFrom);
        $to = $this->date($form->activeTo);
        foreach (['active_from' => [$form->activeFrom, $from], 'active_to' => [$form->activeTo, $to]] as $key => [$raw, $date]) {
            if ($raw !== null && trim($raw) !== '' && $date === null) {
                $result = $result->withError($key, d__('collections', 'Enter a valid date and time.'));
            }
        }
        if ($from !== null && $to !== null && $from > $to) {
            $result = $result->withError('active_to', d__('collections', 'The end date must not precede the start date.'));
        }
        $values = [];
        foreach ($form->values as $key => $raw) {
            if (! $definitions->contains('code', $key)) {
                $result = $result->withError('values.' . $key, d__('collections', 'Unknown collection field.'));
            }
        }
        foreach ($definitions as $field) {
            $key = 'values.' . $field->code;
            $raw = $form->values[$field->code] ?? ($field->multiple ? [] : '');
            if (($field->multiple && (! is_array($raw) || ! array_is_list($raw))) || (! $field->multiple && ! is_string($raw))) {
                $result = $result->withError($key, d__('collections', 'Submit a single value or a list as defined by the field.'));
                continue;
            }
            $list = is_array($raw) ? $raw : [$raw];
            $prepared = [];
            foreach ($list as $value) {
                if (! is_string($value)) {
                    $result = $result->withError($key, d__('collections', 'Submit a single value or a list as defined by the field.'));
                    continue;
                }
                $value = trim($value);
                if ($value === '' && $field->type !== FieldType::Boolean) {
                    continue;
                }
                $error = $this->valueError($field->type, $value);
                if ($error !== null) {
                    $result = $result->withError($key, $error);
                    continue;
                }
                if (in_array($field->type, [FieldType::String_, FieldType::Select, FieldType::File], true)) {
                    $result = $result->merge($this->validator->validate([$key => $value], [$key => [new StringLength(max: 255)]]));
                }
                $prepared[] = match ($field->type) {
                    FieldType::Date => $this->date($value, true),
                    FieldType::Datetime => $this->date($value),
                    FieldType::Boolean => $value === '1' ? '1' : '0',
                    default => $value,
                };
            }
            if ($field->required && ($field->type !== FieldType::Boolean || $field->multiple) && $prepared === [] && ! $result->hasError($key)) {
                $result = $result->withError($key, d__('collections', 'Fill in the field "%s"', $field->name));
            }
            $values[$field->code] = $field->multiple ? $prepared : ($prepared[0] ?? '');
        }
        $result->throwIfInvalid(static fn (array $errors): CollectionItemFormException => new CollectionItemFormException($errors));
        return new CollectionItemFormDTO(
            $form->collectionId,
            $form->sectionId,
            $code,
            $name,
            $form->active,
            $from,
            $to,
            $sort,
            $form->previewText,
            $form->detailText,
            $values
        );
    }

    private function valueError(FieldType $type, string $value): ?string
    {
        $valid = match ($type) {
            FieldType::Integer => filter_var($value, FILTER_VALIDATE_INT) !== false,
            FieldType::Relation => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false,
            FieldType::Double => is_numeric($value) && is_finite((float) $value),
            FieldType::Boolean => in_array($value, ['', '0', '1'], true),
            FieldType::Date => $this->date($value, true) !== null,
            FieldType::Datetime => $this->date($value) !== null,
            default => true,
        };
        return $valid ? null : d__('collections', 'Enter a valid value for this field type.');
    }

    private function date(?string $raw, bool $dateOnly = false): ?string
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
