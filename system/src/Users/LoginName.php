<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Illuminate\Support\Str;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\NotEmpty;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;

/**
 * The name an account signs in under (users.name_lat): the nickname transliterated to Latin and
 * lowercased, every run of anything but letters and digits made into one underscore —
 * "Вася Пупкин!" signs in as "vasia_pupkin".
 *
 * Registration, the sign-in, the panel, the profile, the search of the community and the
 * installer all derive it here: a nickname has to read the same wherever it is typed, or the
 * account it names cannot be signed into.
 */
final class LoginName
{
    public static function of(string $nickname): string
    {
        return Str::slug($nickname, '_');
    }

    /**
     * The rules a nickname is checked by wherever one is chosen or changed. They read two fields
     * of the data: "name", the nickname, and "name_lat", what of() makes of it — a nickname of
     * nothing but symbols leaves no name to sign in under, and two nicknames that differ only in
     * them leave the same one.
     *
     * @param int|null $exceptUserId The account whose own nickname this is, when it changes.
     * @param int      $maxLength    Accounts older than the limit of registration keep a longer one.
     * @return array{name: list<RuleInterface>, name_lat: list<RuleInterface>}
     */
    public static function rules(?int $exceptUserId = null, int $maxLength = 20): array
    {
        $others = $exceptUserId === null ? null : static fn ($query) => $query->where('id', '!=', $exceptUserId);

        return [
            'name'     => [
                new StringLength(min: 2, max: $maxLength),
                new ModelNotExists(model: User::class, field: 'name', exclude: $others),
            ],
            'name_lat' => [
                new NotEmpty(message: d__('system', 'The nickname needs at least one letter or digit')),
                new ModelNotExists(model: User::class, field: 'name_lat', exclude: $others, allowEmpty: true),
            ],
        ];
    }

    /**
     * The errors of the login name told at the nickname, the field the visitor typed.
     */
    public static function errorsAtNickname(ValidationResult $result): ValidationResult
    {
        $errors = $result->getErrors();

        if (! isset($errors['name_lat'])) {
            return $result;
        }

        if (! isset($errors['name'])) {
            $errors['name'] = $errors['name_lat'];
        }
        unset($errors['name_lat']);

        return new ValidationResult($errors);
    }
}
