<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\UpdateUserCommand;
use Johncms\Modules\Admin\Application\Exceptions\UserEditException;
use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\LoginName;
use Johncms\Users\User;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\InArray;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Saves the profile of an account from the panel. The staff change it as it is: the address takes
 * effect at once, without the confirmation by mail the owner goes through.
 *
 * The text is kept as it was typed and escaped where it is printed. A new nickname brings a new
 * login name with it — the account signs in under name_lat — and both have to be free.
 */
final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserEditRepositoryInterface $users,
        private ValidatorInterface $validator,
    ) {
    }

    public function execute(User $user, UpdateUserCommand $command): void
    {
        $others = static fn ($query) => $query->where('id', '!=', $user->id);
        $hasBirthday = $command->birthDay > 0 || $command->birthMonth > 0 || $command->birthYear > 0;

        $fields = [
            'mail'        => $command->mail,
            'imname'      => $command->fullName,
            'live'        => $command->city,
            'about'       => $command->about,
            'mibile'      => $command->phone,
            'skype'       => $command->skype,
            'jabber'      => $command->jabber,
            'www'         => $command->site,
            'status'      => $command->status,
            // No birthday reads as no value, which the range rules let through; a part left out of
            // one is a zero, which they do not.
            'dayb'        => $hasBirthday ? $command->birthDay : null,
            'monthb'      => $hasBirthday ? $command->birthMonth : null,
            'yearofbirth' => $hasBirthday ? $command->birthYear : null,
        ];

        $rules = [
            'mail'        => [
                new EmailAddress(allowEmpty: true),
                new ModelNotExists(model: User::class, field: 'mail', exclude: $others, allowEmpty: true),
            ],
            'imname'      => [new StringLength(max: 100, allowEmpty: true)],
            'live'        => [new StringLength(max: 100, allowEmpty: true)],
            'about'       => [new StringLength(max: 5000, allowEmpty: true)],
            'mibile'      => [new StringLength(max: 50, allowEmpty: true)],
            'skype'       => [new StringLength(max: 50, allowEmpty: true)],
            'jabber'      => [new StringLength(max: 50, allowEmpty: true)],
            'www'         => [new StringLength(max: 50, allowEmpty: true)],
            'status'      => [new StringLength(max: 50, allowEmpty: true)],
            // A birthday is all three parts or none of them.
            'dayb'        => [new Between(min: 1, max: 31, allowEmpty: ! $hasBirthday)],
            'monthb'      => [new Between(min: 1, max: 12, allowEmpty: ! $hasBirthday)],
            'yearofbirth' => [new Between(min: 1900, max: (int) date('Y'), allowEmpty: ! $hasBirthday)],
        ];

        if ($command->name !== null) {
            $fields['name'] = $command->name;
            $fields['name_lat'] = LoginName::of($command->name);
            $rules = LoginName::rules($user->id, 25) + $rules;
        }

        if ($command->sex !== null) {
            $fields['sex'] = $command->sex;
            // An account registered before the gender was asked for may have none; it stays so.
            $rules['sex'] = [new InArray(haystack: ['m', 'zh'], allowEmpty: true)];
        }

        if ($command->notes !== null) {
            $fields['admin_notes'] = $command->notes;
            $rules['admin_notes'] = [new StringLength(max: 10000, allowEmpty: true)];
        }

        $result = LoginName::errorsAtNickname($this->validator->validate($fields, $rules));

        $result->throwIfInvalid(static fn (array $errors): UserEditException => new UserEditException($errors));

        $fields['mailvis'] = $command->mailVisible;
        foreach (['dayb', 'monthb', 'yearofbirth'] as $part) {
            $fields[$part] = (int) $fields[$part];
        }

        if ($command->karmaOff !== null) {
            $fields['karma_off'] = $command->karmaOff;
        }

        $this->users->update($user->id, $fields);
    }
}
