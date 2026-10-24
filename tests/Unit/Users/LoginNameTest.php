<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Users\LoginName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginNameTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nicknames(): iterable
    {
        yield 'latin' => ['Tester', 'tester'];
        yield 'cyrillic' => ['Вася Пупкин', 'vasia_pupkin'];
        yield 'a run of symbols' => ['Rock & Roll!!', 'rock_roll'];
        yield 'symbols at the edges' => ['  _Gollum_  ', 'gollum'];
        yield 'nothing but symbols' => ['!!!', ''];
    }

    #[DataProvider('nicknames')]
    public function testANicknameSignsInUnderItsLatinForm(string $nickname, string $loginName): void
    {
        self::assertSame($loginName, LoginName::of($nickname));
    }
}
